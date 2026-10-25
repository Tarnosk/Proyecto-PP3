<?php

namespace App\Modules\Ventas\Logging;

use App\Modules\Ventas\Exceptions\VentaException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * BITÁCORA (LOG) DE UNA VENTA
 *
 * Arma un informe legible de todo lo que pasó con una venta: qué datos llegaron,
 * qué comprobó el sistema en cada paso, qué resultó y qué se le devolvió al front.
 *
 * Se guarda en: storage/logs/ventas/ventas-AAAA-MM-DD.log  (canal "ventas" en config/logging.php)
 *
 * Las líneas se van juntando en memoria y se escriben TODAS JUNTAS al final.
 * Así, aunque entren dos ventas al mismo tiempo, cada una queda en un bloque sin mezclarse.
 *
 * Se usa para TODAS las operaciones que cambian una venta: modificar, anular,
 * restaurar y cambiar de estado (App\Modules\Ventas\Controllers\VentaApiController).
 *
 * Uso desde el controlador:
 *   $log = new VentaLogger($request, 'MODIFICAR VENTA', 'VentaApiController@update', 7);
 *   $log->paso('Validación del cliente', 'El sistema comprueba que el cliente exista.');
 *   $log->ok('Cliente #123 encontrado');
 *   ...
 *   $log->exito($respuesta, 201);   // o ->rechazada($e) / ->error($e, $respuesta)
 */
class VentaLogger
{
    // Ancho de las líneas separadoras del informe.
    private const ANCHO = 78;


    // Código corto que identifica esta venta en el log (ej: "8EM2TM9t").
    private string $operacion;

    // Momento en que empezó la venta, para calcular cuánto tardó.
    private float $inicio;

    // Número y nombre del paso actual (si algo falla, se informa este paso).
    private int $numeroPaso = 0;
    private string $pasoActual = 'Inicio';

    // Líneas acumuladas del informe.
    private array $lineas = [];

    /**
     * @param  string  $titulo  qué operación es, ej: "MODIFICAR VENTA" (aparece en el encabezado)
     * @param  string  $controlador  método que la atiende, ej: "VentaApiController@update"
     * @param  int  $totalPasos  cantidad de pasos de esta operación (para mostrar "PASO 3/7")
     */
    public function __construct(
        Request $request,
        string $titulo = 'NUEVA VENTA',
        string $controlador = 'App\Modules\Ventas\Controllers\VentaController@store',
        private readonly int $totalPasos = 7,
    ) {
        $this->operacion = Str::random(8);
        $this->inicio = microtime(true);

        // Encabezado del informe.
        $this->lineas[] = '';
        $this->lineas[] = str_repeat('=', self::ANCHO);
        $this->lineas[] = $this->centrar("{$titulo}  ·  Operación #{$this->operacion}  ·  ".now()->format('d/m/Y H:i:s'));
        $this->lineas[] = str_repeat('=', self::ANCHO);
        $this->lineas[] = '  Endpoint ......: '.$request->method().' /'.$request->path();
        $this->lineas[] = '  Ruta definida en: app/Modules/Ventas/routes/api.php';
        $this->lineas[] = '  Controlador ...: '.$controlador;
        $this->lineas[] = '  Datos recibidos del front:';
        $this->lineas[] = $this->json($request->all());
    }

    /**
     * Empieza un paso nuevo. $queHace explica, en lenguaje simple, qué comprueba el sistema.
     */
    public function paso(string $titulo, string $queHace): void
    {
        $this->numeroPaso++;
        $this->pasoActual = $titulo;

        $this->lineas[] = '';
        $this->lineas[] = "  [PASO {$this->numeroPaso}/{$this->totalPasos}] {$titulo}";
        $this->lineas[] = "     Qué hace ...: {$queHace}";
    }

    /**
     * Anota que el paso actual salió bien.
     */
    public function ok(string $detalle): void
    {
        $this->lineas[] = "     ✔ {$detalle}";
    }

    /**
     * Anota un dato extra del paso actual (sin marcar OK ni error).
     */
    public function detalle(string $detalle): void
    {
        $this->lineas[] = "       {$detalle}";
    }

    /**
     * La operación terminó bien: se escribe el informe con lo que se devolvió al front.
     *
     * @param  string  $resultado  ej: "VENTA MODIFICADA"
     */
    public function exito(array $respuesta, int $codigoHttp, string $resultado = 'VENTA REGISTRADA'): void
    {
        $this->cerrar(
            'info',
            "✔ {$resultado}  ·  Respuesta HTTP {$codigoHttp} ".self::textoHttp($codigoHttp),
            null,
            $respuesta
        );
    }

    /**
     * La operación NO se hizo por una regla de negocio (404 venta inexistente,
     * 409 venta pagada, 503 otro módulo caído...). No se guardó nada.
     */
    public function bloqueada(VentaException $e): void
    {
        $this->lineas[] = "     ✘ {$e->getMessage()}";

        $this->cerrar(
            'warning',
            '✘ OPERACIÓN RECHAZADA  ·  Respuesta HTTP '.$e->codigoHttp().' '.self::textoHttp($e->codigoHttp()),
            [
                "Falló en ......: PASO {$this->numeroPaso} - {$this->pasoActual}",
                'Motivo ........: '.$e->codigo(),
                'Base de datos .: no se guardó nada.',
            ],
            $e->render()->getData(true)
        );
    }

    /**
     * La venta fue RECHAZADA porque algún dato no pasó la validación (HTTP 422).
     * No se guardó nada en la base de datos.
     */
    public function rechazada(ValidationException $e): void
    {
        $this->lineas[] = "     ✘ No pasó la validación: {$e->getMessage()}";

        $this->cerrar(
            'warning',
            '✘ VENTA RECHAZADA  ·  Respuesta HTTP 422 Unprocessable Content',
            [
                "Falló en ......: PASO {$this->numeroPaso} - {$this->pasoActual}",
                'Motivo ........: los datos enviados no son válidos (ver "errors" abajo).',
                'Base de datos .: no se guardó nada.',
                'Qué revisar ...: los datos que manda el front o las reglas de validación de este paso.',
            ],
            // Este es el JSON que arma Laravel automáticamente para un error de validación.
            ['message' => $e->getMessage(), 'errors' => $e->errors()]
        );
    }

    /**
     * Error inesperado (base de datos caída, columna inexistente, FK, etc.). HTTP 500.
     * La transacción se deshace: no queda nada guardado a medias.
     */
    public function error(Throwable $e, array $respuesta): void
    {
        $this->lineas[] = '     ✘ Error inesperado en este paso';

        $this->cerrar(
            'error',
            '✘ ERROR AL PROCESAR LA VENTA  ·  Respuesta HTTP 500 Internal Server Error',
            [
                "Falló en ......: PASO {$this->numeroPaso} - {$this->pasoActual}",
                'Tipo de error .: '.get_class($e),
                'Mensaje .......: '.$e->getMessage(),
                'En nuestro código: '.$this->lineaDeNuestroCodigo($e),
                'Base de datos .: transacción deshecha, NO se guardó nada (ni venta, ni detalles, ni cuenta corriente, ni historial).',
                'Qué revisar ...: que MySQL esté encendido, que existan las tablas/columnas y las claves foráneas.',
            ],
            $respuesta
        );
    }

    /**
     * Escribe el bloque completo en el archivo de log.
     */
    private function cerrar(string $nivel, string $resultado, ?array $diagnostico, array $respuesta): void
    {
        $duracion = round((microtime(true) - $this->inicio) * 1000);

        $this->lineas[] = '';
        $this->lineas[] = str_repeat('-', self::ANCHO);
        $this->lineas[] = "  RESULTADO: {$resultado}";
        $this->lineas[] = "  Duración ......: {$duracion} ms";

        foreach ($diagnostico ?? [] as $linea) {
            $this->lineas[] = "  {$linea}";
        }

        $this->lineas[] = '  Respuesta devuelta al front:';
        $this->lineas[] = $this->json($respuesta);
        $this->lineas[] = str_repeat('=', self::ANCHO);
        $this->lineas[] = $this->centrar("FIN DE LA OPERACIÓN #{$this->operacion}");
        $this->lineas[] = str_repeat('=', self::ANCHO);

        // $nivel: info (todo bien), warning (rechazada), error (falla del sistema).
        Log::channel('ventas')->log($nivel, implode(PHP_EOL, $this->lineas));
    }

    /**
     * Busca en qué línea de NUESTRO código (carpeta app/) se originó el error.
     * El error real suele saltar dentro de Laravel (vendor/), que no le sirve al desarrollador.
     */
    private function lineaDeNuestroCodigo(Throwable $e): string
    {
        $carpetaApp = app_path();

        // Recorre el archivo donde saltó el error y luego cada llamada previa (stack trace).
        $lugares = array_merge([['file' => $e->getFile(), 'line' => $e->getLine()]], $e->getTrace());

        foreach ($lugares as $lugar) {
            if (isset($lugar['file']) && str_starts_with($lugar['file'], $carpetaApp)) {
                // Muestra la ruta relativa, ej: app/Modules/Ventas/Services/VentaService.php:245
                return 'app'.str_replace('\\', '/', substr($lugar['file'], strlen($carpetaApp))).':'.$lugar['line'];
            }
        }

        return $e->getFile().':'.$e->getLine();
    }

    /**
     * Convierte un array a JSON legible (con sangría) para el informe.
     */
    private function json(mixed $datos): string
    {
        $json = json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Agrega sangría a cada línea para que quede alineado dentro del bloque.
        return preg_replace('/^/m', '      ', (string) $json);
    }

    /**
     * Centra un texto dentro del ancho del informe.
     */
    private function centrar(string $texto): string
    {
        $espacios = max(0, intdiv(self::ANCHO - mb_strlen($texto), 2));

        return str_repeat(' ', $espacios).$texto;
    }

    /**
     * Texto estándar de un código HTTP, ej: 409 → "Conflict".
     */
    private static function textoHttp(int $codigo): string
    {
        return Response::$statusTexts[$codigo] ?? '';
    }

    /**
     * Formatea un importe como moneda argentina: $ 1.234,50
     */
    public static function pesos(float $monto): string
    {
        return '$ '.number_format($monto, 2, ',', '.');
    }
}
