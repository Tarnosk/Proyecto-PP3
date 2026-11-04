<?php

namespace App\Modules\ConciliacionBancaria\Services;

use App\Modules\ConciliacionBancaria\Models\ArchivoImportadoLog;
use App\Modules\ConciliacionBancaria\Models\MovimientoBancario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class BankStatementImportService
{
    private BankColumnMapper $mapper;

    public function __construct(BankColumnMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * Importa un archivo de extracto bancario con cálculo de Hash SHA256 y persistencia inmutable.
     *
     * @return array{
     *   success: bool,
     *   mensaje: string,
     *   archivo_id: ?int,
     *   filas_leidas: int,
     *   filas_insertadas: int,
     *   filas_omitidas: int,
     *   filas_error: int,
     *   acreditaciones_para_conciliar: int,
     *   errores_detalle: array<string>
     * }
     */
    public function importar(UploadedFile $archivo, int $idUsuario): array
    {
        $extension = strtolower($archivo->getClientOriginalExtension());
        if (!in_array($extension, ['xls', 'txt'])) {
            throw new Exception("Formato no permitido (.{$extension}). Únicamente se aceptan archivos .xls y .txt de Banco Provincia.");
        }

        $hashSha256 = hash_file('sha256', $archivo->getRealPath());

        // Criterio de Aceptación 2: Non-Repudiation y rechazo si el hash ya existe
        $existeHash = ArchivoImportadoLog::where('hash_sha256', $hashSha256)->first();
        if ($existeHash) {
            throw new Exception("El archivo ya fue importado anteriormente (Hash SHA-256 coincidente con importación #{$existeHash->id_archivo_importado_log} del {$existeHash->created_at}).");
        }

        // Guardar archivo físico en almacenamiento privado de auditoría sin dependencia externa de finfo
        $nombreOriginal = $archivo->getClientOriginalName();
        $targetDir = storage_path('app/extractos_bancarios');
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }
        $nombreGuardado = date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreOriginal);
        $rutaFisica = $targetDir . DIRECTORY_SEPARATOR . $nombreGuardado;
        @copy($archivo->getRealPath(), $rutaFisica);
        $rutaAlmacenada = 'extractos_bancarios/' . $nombreGuardado;

        // Parsear el archivo con el mapper especializado
        $resultadoParsing = $this->mapper->parsear($archivo->getRealPath(), $extension);
        $movimientos = $resultadoParsing['movimientos'];
        $erroresParsing = $resultadoParsing['errores'];

        $filasLeidas = count($movimientos) + count($erroresParsing);
        $filasInsertadas = 0;
        $filasOmitidas = 0;
        $filasError = count($erroresParsing);
        $acreditacionesListas = 0;

        // Registrar cabecera del lote de auditoría
        return DB::transaction(function () use (
            $nombreOriginal,
            $rutaAlmacenada,
            $hashSha256,
            $extension,
            $idUsuario,
            $resultadoParsing,
            $movimientos,
            $erroresParsing,
            $filasLeidas,
            &$filasInsertadas,
            &$filasOmitidas,
            &$filasError,
            &$acreditacionesListas
        ) {
            $log = ArchivoImportadoLog::create([
                'nombre_original' => $nombreOriginal,
                'ruta_almacenada' => $rutaAlmacenada,
                'hash_sha256' => $hashSha256,
                'formato_extension' => $extension,
                'cantidad_filas_leidas' => $filasLeidas,
                'cantidad_filas_insertadas' => 0,
                'cantidad_filas_omitidas' => 0,
                'cantidad_filas_error' => $filasError,
                'id_usuario' => $idUsuario,
                'saldo_inicial' => $resultadoParsing['saldo_inicial'],
                'saldo_final' => $resultadoParsing['saldo_final'],
                'fecha_desde' => $resultadoParsing['fecha_desde'],
                'fecha_hasta' => $resultadoParsing['fecha_hasta'],
            ]);

            foreach ($movimientos as $mov) {
                // Criterio de Aceptación 3: Omitir duplicados por fila (fecha + monto + signo + comprobante + descripcion)
                $existeFila = MovimientoBancario::where('fecha_operacion', $mov['fecha_operacion'])
                    ->where('monto', $mov['monto'])
                    ->where('signo_monto', $mov['signo_monto'])
                    ->where('nro_comprobante_extraido', $mov['nro_comprobante_extraido'])
                    ->where('descripcion_normalizada', $mov['descripcion_normalizada'])
                    ->exists();

                if ($existeFila) {
                    $filasOmitidas++;
                    continue;
                }

                try {
                    MovimientoBancario::create([
                        'id_archivo_importado_log' => $log->id_archivo_importado_log,
                        'fecha_operacion' => $mov['fecha_operacion'],
                        'monto' => $mov['monto'],
                        'signo_monto' => $mov['signo_monto'],
                        'descripcion_original' => $mov['descripcion_original'],
                        'descripcion_normalizada' => $mov['descripcion_normalizada'],
                        'nro_comprobante_extraido' => $mov['nro_comprobante_extraido'],
                        'cuit_extraido' => $mov['cuit_extraido'],
                        'tipo_movimiento' => $mov['tipo_movimiento'],
                        'estado_conciliacion' => $mov['estado_conciliacion'],
                    ]);

                    $filasInsertadas++;

                    if ($mov['tipo_movimiento'] === 'cobranza_cliente' && $mov['estado_conciliacion'] === 'pendiente') {
                        $acreditacionesListas++;
                    }
                } catch (\Throwable $e) {
                    $filasError++;
                    Log::channel('single')->error("Error insertando fila de extracto bancario: " . $e->getMessage(), [
                        'fila' => $mov['nro_fila'] ?? null,
                        'archivo' => $nombreOriginal,
                    ]);
                }
            }

            // Actualizar contadores consolidados en el log
            $log->update([
                'cantidad_filas_insertadas' => $filasInsertadas,
                'cantidad_filas_omitidas' => $filasOmitidas,
                'cantidad_filas_error' => $filasError,
            ]);

            return [
                'success' => true,
                'mensaje' => "Se procesó el extracto bancario correctamente.",
                'archivo_id' => $log->id_archivo_importado_log,
                'filas_leidas' => $filasLeidas,
                'filas_insertadas' => $filasInsertadas,
                'filas_omitidas' => $filasOmitidas,
                'filas_error' => $filasError,
                'acreditaciones_para_conciliar' => $acreditacionesListas,
                'errores_detalle' => $erroresParsing,
            ];
        });
    }
}
