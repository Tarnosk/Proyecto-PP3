<?php

namespace App\Modules\Ventas\Services;

use App\Modules\Ventas\Contracts\ProductoGateway;
use App\Modules\Ventas\Contracts\StockGateway;
use App\Modules\Ventas\Enums\EstadoVenta;
use App\Modules\Ventas\Exceptions\TransicionEstadoInvalidaException;
use App\Modules\Ventas\Exceptions\VentaNoEditableException;
use App\Modules\Ventas\Exceptions\VentaNoEncontradaException;
use App\Modules\Ventas\Logging\VentaLogger;
use App\Modules\Ventas\Models\Venta;
use App\Modules\Ventas\Models\VentaHistorial;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * EL CEREBRO DEL MÓDULO VENTAS: modificar, anular, restaurar y cambiar estado.
 *
 * Por qué existe: el controlador (VentaApiController) solo recibe el pedido
 * y devuelve la respuesta. TODAS las reglas de negocio viven acá, en un solo
 * lugar. Así se pueden testear y reutilizar (por ejemplo, el día que el
 * grupo de Rendiciones necesite marcar una venta como pagada, llama a
 * cambiarEstado() y listo).
 *
 * Reglas que cumple SIEMPRE:
 *  1. Todo dentro de una TRANSACCIÓN: o se guarda todo, o no se guarda nada.
 *  2. La venta se BLOQUEA mientras se cambia (lockForUpdate): si dos personas
 *     tocan la misma venta al mismo tiempo, la segunda espera a la primera.
 *  3. El estado se revisa DESPUÉS de bloquear: así nadie la puede pagar
 *     justo en el medio de una modificación.
 *  4. Cada cambio deja una fila en venta_historial (quién, qué, cuándo, por qué).
 *  5. Stock se avisa DESPUÉS de guardar: si Stock no responde, la venta no
 *     se deshace; queda anotado en el log para revisarlo.
 *
 * El parámetro $log (VentaLogger) es opcional: si viene, se escribe el paso
 * a paso en storage/logs/ventas/.
 */
class VentaService
{
    public function __construct(
        private readonly ProductoGateway $productos,
        private readonly StockGateway $stock,
        private readonly CalculadoraVenta $calculadora,
    ) {}

    // =====================================================================
    // CONSULTAS (solo leen, no cambian nada)
    // =====================================================================

    /**
     * Lista de ventas con filtros y paginada. La más nueva primero.
     *
     * @param  array  $filtros  estado, cliente_id, desde, hasta, texto, por_pagina (todos opcionales)
     */
    public function listar(array $filtros): LengthAwarePaginator
    {
        $porPagina = min((int) ($filtros['por_pagina'] ?? config('ventas.por_pagina')), (int) config('ventas.por_pagina_max'));

        return Venta::query()
            ->with('detalles')
            // when(condición, ...) solo agrega el filtro si la condición es verdadera.
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($filtros['cliente_id'] ?? null, fn ($q, $cliente) => $q->where('id_cliente', $cliente))
            ->when($filtros['desde'] ?? null, fn ($q, $desde) => $q->whereDate('fecha', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($q, $hasta) => $q->whereDate('fecha', '<=', $hasta))
            ->when($filtros['texto'] ?? null, fn ($q, $texto) => $q->where(function ($q) use ($texto) {
                $q->where('numFactura', 'like', "%{$texto}%")->orWhere('observaciones', 'like', "%{$texto}%");
            }))
            ->orderByDesc('fecha')
            ->orderByDesc('id_venta')
            ->paginate($porPagina);
    }

    /**
     * Una venta con su detalle y su historial. 404 si no existe.
     */
    public function buscar(int $id): Venta
    {
        return Venta::with(['detalles', 'historial'])->find($id)
            ?? throw new VentaNoEncontradaException($id);
    }

    // =====================================================================
    // MODIFICAR (botón "Modificar")
    // =====================================================================

    /**
     * Reemplaza el carrito de una venta y recalcula todo.
     *
     * Se puede cambiar: lista de precios, productos/cantidades/descuentos,
     * descuento global y observaciones.
     * NO se cambia: cliente, número de comprobante ni fecha.
     *
     * @param  array  $datos  ya validados por ModificarVentaRequest
     */
    public function modificar(int $id, array $datos, ?VentaLogger $log = null): Venta
    {
        // El catálogo se pide ANTES de bloquear la venta: si algún día viene de
        // otra API (y tarda), no dejamos la venta bloqueada mientras esperamos.
        $catalogo = collect($this->productos->todos())->keyBy('id')->all();

        [$venta, $cambiosStock] = DB::transaction(function () use ($id, $datos, $catalogo, $log) {
            $venta = $this->bloquear($id, $log);

            $log?->paso('Verificar el estado', 'Solo se puede modificar antes de que el cliente pague (EstadoVenta::permiteEdicion).');
            $estado = $this->exigirEstadoConocido($venta, 'modificar');
            if (! $estado->permiteEdicion()) {
                throw new VentaNoEditableException($venta->id_venta, 'modificar', $estado->value);
            }
            $log?->ok("Estado \"{$estado->value}\": se puede modificar.");

            $log?->paso('Calcular el nuevo total', 'Se recalcula con los precios del servidor (CalculadoraVenta), igual que al crear la venta.');
            $calculo = $this->calculadora->calcular($datos['items'], $catalogo, $datos['lista'], $datos['descuento_global'] ?? null);
            $totalAnterior = (float) $venta->total;
            $log?->ok('Total anterior: '.VentaLogger::pesos($totalAnterior).' → total nuevo: '.VentaLogger::pesos($calculo['total']));

            $log?->paso('Guardar la venta y su detalle', 'Se borran las líneas viejas de detalle_venta, se insertan las nuevas y se actualiza la cabecera.');
            $cantidadesAntes = $this->cantidadesPorProducto($venta->detalles);
            $venta->detalles()->delete();
            $venta->detalles()->createMany($calculo['detalles']);

            $cambios = [
                'total' => $calculo['total'],
                'descuento_global' => $calculo['descuento_global'],
            ];
            // Las observaciones solo se tocan si vinieron en el pedido.
            if (array_key_exists('observaciones', $datos)) {
                $cambios['observaciones'] = trim((string) $datos['observaciones']) ?: config('ventas.observaciones_por_defecto');
            }
            $venta->update($cambios);
            $log?->ok(count($calculo['detalles']).' línea/s guardada/s.');

            $log?->paso('Actualizar la cuenta corriente', 'La deuda del cliente pasa a ser el nuevo total.');
            $this->guardarMovimientoCuentaCorriente($venta, -$calculo['total'], 'venta', "Venta POS #{$venta->id_venta}");
            $log?->ok('Movimiento de cuenta corriente: '.VentaLogger::pesos(-$calculo['total']));

            $log?->paso('Registrar en el historial', 'Queda anotado quién modificó la venta y cuánto cambió el total.');
            $this->registrarHistorial($venta, VentaHistorial::MODIFICACION, $estado->value, $estado->value, null, [
                'total_anterior' => $totalAnterior,
                'total_nuevo' => $calculo['total'],
            ]);
            $log?->ok('Historial guardado.');

            // Stock: lo que antes se vendía y ahora no, vuelve (+); lo nuevo, sale (-).
            $cantidadesDespues = $this->cantidadesPorProducto($calculo['detalles']);
            $cambiosStock = [];
            foreach (array_keys($cantidadesAntes + $cantidadesDespues) as $producto) {
                $cambiosStock[$producto] = ($cantidadesAntes[$producto] ?? 0) - ($cantidadesDespues[$producto] ?? 0);
            }

            return [$venta, $cambiosStock];
        });

        $this->avisarStock($cambiosStock, $venta->id_venta, 'modificacion', $log);

        return $venta->fresh(['detalles', 'historial']);
    }

    // =====================================================================
    // ANULAR (botón "Eliminar")
    // =====================================================================

    /**
     * Anula la venta: NO se borra, queda en estado "cancelada" (el front la
     * muestra en gris) y se puede restaurar.
     *
     * Cuenta corriente: el movimiento de la venta queda en $0 con tipo
     * "venta_anulada". Así el saldo del cliente vuelve a como estaba y en
     * /saldo sigue viéndose que hubo una venta anulada.
     */
    public function anular(int $id, string $motivo, ?VentaLogger $log = null): Venta
    {
        [$venta, $cantidades] = DB::transaction(function () use ($id, $motivo, $log) {
            $venta = $this->bloquear($id, $log);

            $log?->paso('Verificar el estado', 'Solo se puede anular antes de que el cliente pague (EstadoVenta::permiteAnulacion).');
            $estado = $this->exigirEstadoConocido($venta, 'eliminar');
            if ($estado === EstadoVenta::Cancelada) {
                throw new VentaNoEditableException($venta->id_venta, 'eliminar', $estado->value, "La venta #{$venta->id_venta} ya está anulada.");
            }
            if (! $estado->permiteAnulacion()) {
                throw new VentaNoEditableException($venta->id_venta, 'eliminar', $estado->value);
            }
            $log?->ok("Estado \"{$estado->value}\": se puede anular.");

            $log?->paso('Marcar la venta como anulada', 'La venta pasa a "cancelada". No se borra nada.');
            $venta->update(['estado' => EstadoVenta::Cancelada->value]);
            $log?->ok("Estado: {$estado->value} → cancelada. Motivo: {$motivo}");

            $log?->paso('Corregir la cuenta corriente', 'El movimiento de esta venta queda en $ 0 (tipo "venta_anulada").');
            $this->guardarMovimientoCuentaCorriente($venta, 0, 'venta_anulada', "Venta POS #{$venta->id_venta} (ANULADA)");
            $log?->ok('Se le quitó al cliente la deuda de '.VentaLogger::pesos((float) $venta->total).'.');

            $log?->paso('Registrar en el historial', 'Se guarda el estado anterior para poder restaurarla después.');
            $this->registrarHistorial($venta, VentaHistorial::ANULACION, $estado->value, EstadoVenta::Cancelada->value, $motivo);
            $log?->ok('Historial guardado.');

            return [$venta, $this->cantidadesPorProducto($venta->detalles)];
        });

        // Toda la mercadería vuelve al depósito (+).
        $this->avisarStock($cantidades, $venta->id_venta, 'anulacion', $log);

        return $venta->fresh(['detalles', 'historial']);
    }

    // =====================================================================
    // RESTAURAR (des-anular)
    // =====================================================================

    /**
     * Deshace una anulación: la venta vuelve al estado que tenía antes de
     * anularse y la deuda vuelve a la cuenta corriente.
     */
    public function restaurar(int $id, ?VentaLogger $log = null): Venta
    {
        [$venta, $cantidades] = DB::transaction(function () use ($id, $log) {
            $venta = $this->bloquear($id, $log);

            $log?->paso('Verificar el estado', 'Solo se puede restaurar una venta anulada.');
            $estado = $this->exigirEstadoConocido($venta, 'restaurar');
            if (! $estado->permiteRestauracion()) {
                throw new VentaNoEditableException($venta->id_venta, 'restaurar', $estado->value, "La venta #{$venta->id_venta} no está anulada (está {$estado->value}).");
            }
            $log?->ok('La venta está anulada: se puede restaurar.');

            $log?->paso('Volver al estado anterior', 'Se busca en venta_historial en qué estado estaba antes de anularla.');
            $estadoAnterior = $this->estadoAntesDeAnular($venta);
            $venta->update(['estado' => $estadoAnterior->value]);
            $log?->ok("Estado: cancelada → {$estadoAnterior->value}");

            $log?->paso('Devolver la deuda a la cuenta corriente', 'El movimiento de esta venta vuelve a ser el total (tipo "venta").');
            $this->guardarMovimientoCuentaCorriente($venta, -(float) $venta->total, 'venta', "Venta POS #{$venta->id_venta}");
            $log?->ok('Movimiento de cuenta corriente: '.VentaLogger::pesos(-(float) $venta->total));

            $log?->paso('Registrar en el historial', 'Queda anotado quién la restauró.');
            $this->registrarHistorial($venta, VentaHistorial::RESTAURACION, EstadoVenta::Cancelada->value, $estadoAnterior->value);
            $log?->ok('Historial guardado.');

            // La mercadería vuelve a salir del depósito (-).
            $cantidades = array_map(fn (int $cantidad) => -$cantidad, $this->cantidadesPorProducto($venta->detalles));

            return [$venta, $cantidades];
        });

        $this->avisarStock($cantidades, $venta->id_venta, 'restauracion', $log);

        return $venta->fresh(['detalles', 'historial']);
    }

    // =====================================================================
    // CAMBIAR ESTADO (avanzar de fase: preparada, entregada, pagada...)
    // =====================================================================

    /**
     * Avanza la venta a otra fase. Las reglas están en EstadoVenta::siguientes().
     * No toca la cuenta corriente ni el stock: solo cambia la fase.
     */
    public function cambiarEstado(int $id, EstadoVenta $destino, ?string $motivo = null, ?VentaLogger $log = null): Venta
    {
        $venta = DB::transaction(function () use ($id, $destino, $motivo, $log) {
            $venta = $this->bloquear($id, $log);

            $log?->paso('Verificar la transición', 'Se revisa que se pueda pasar del estado actual al pedido (EstadoVenta::siguientes).');
            $estado = EstadoVenta::desdeTexto($venta->estado);

            if ($estado === null) {
                throw new TransicionEstadoInvalidaException($venta->id_venta, (string) $venta->estado, $destino->value, [],
                    "La venta #{$venta->id_venta} tiene un estado desconocido (\"{$venta->estado}\"). Revisar la base de datos.");
            }

            $posibles = array_map(fn (EstadoVenta $e) => $e->value, $estado->siguientes());

            if ($destino === EstadoVenta::Cancelada) {
                throw new TransicionEstadoInvalidaException($venta->id_venta, $estado->value, $destino->value, $posibles,
                    "Para anular una venta usá DELETE /api/ventas/{$venta->id_venta} (pide motivo y corrige la cuenta corriente).");
            }

            if ($estado === $destino) {
                throw new TransicionEstadoInvalidaException($venta->id_venta, $estado->value, $destino->value, $posibles,
                    "La venta #{$venta->id_venta} ya está en estado \"{$estado->value}\".");
            }

            if (! $estado->puedePasarA($destino)) {
                throw new TransicionEstadoInvalidaException($venta->id_venta, $estado->value, $destino->value, $posibles);
            }
            $log?->ok("Transición permitida: {$estado->value} → {$destino->value}");

            $log?->paso('Guardar el nuevo estado', 'Se actualiza la columna venta.estado.');
            $venta->update(['estado' => $destino->value]);
            $log?->ok('Estado guardado.');

            $log?->paso('Registrar en el historial', 'Queda anotado quién cambió la fase y por qué.');
            $this->registrarHistorial($venta, VentaHistorial::CAMBIO_ESTADO, $estado->value, $destino->value, $motivo);
            $log?->ok('Historial guardado.');

            return $venta;
        });

        return $venta->fresh(['detalles', 'historial']);
    }

    // =====================================================================
    // AYUDANTES INTERNOS
    // =====================================================================

    /**
     * Busca la venta y la BLOQUEA hasta que termine la transacción.
     * Si no existe → 404.
     */
    private function bloquear(int $id, ?VentaLogger $log): Venta
    {
        $log?->paso('Buscar y bloquear la venta', 'Se lee la venta y se bloquea para que nadie la cambie al mismo tiempo (lockForUpdate).');

        $venta = Venta::query()->lockForUpdate()->find($id)
            ?? throw new VentaNoEncontradaException($id);

        $venta->load('detalles');
        $log?->ok("Venta #{$venta->id_venta} · N° {$venta->numFactura} · estado: {$venta->estado} · total: ".VentaLogger::pesos((float) $venta->total));

        return $venta;
    }

    /**
     * Convierte el estado guardado al enum. Si en la base hay un texto raro,
     * se bloquea la acción (409) en vez de romper.
     */
    private function exigirEstadoConocido(Venta $venta, string $accion): EstadoVenta
    {
        return $venta->estadoActual() ?? throw new VentaNoEditableException(
            $venta->id_venta, $accion, (string) $venta->estado,
            "La venta #{$venta->id_venta} tiene un estado desconocido (\"{$venta->estado}\"): no se puede {$accion}."
        );
    }

    /**
     * Estado que tenía la venta justo antes de su última anulación.
     * Si no hay registro (venta anulada fuera del sistema) vuelve a "pendiente",
     * que es el más seguro porque obliga a confirmarla de nuevo.
     */
    private function estadoAntesDeAnular(Venta $venta): EstadoVenta
    {
        $texto = VentaHistorial::query()
            ->where('id_venta', $venta->id_venta)
            ->where('accion', VentaHistorial::ANULACION)
            ->orderByDesc('id')
            ->value('estado_anterior');

        $estado = EstadoVenta::desdeTexto($texto);

        return ($estado !== null && $estado->permiteAnulacion()) ? $estado : EstadoVenta::Pendiente;
    }

    /**
     * Crea o actualiza EL movimiento de cuenta corriente de esta venta.
     * Cada venta tiene como máximo uno (venta_id es único en esa tabla).
     */
    private function guardarMovimientoCuentaCorriente(Venta $venta, float $importe, string $tipo, string $descripcion): void
    {
        $tabla = DB::table('cuenta_corriente_movimientos');
        $datos = ['tipo' => $tipo, 'descripcion' => $descripcion, 'importe' => $importe, 'updated_at' => now()];

        // Se pregunta con exists() y no con el resultado de update(): MySQL
        // informa 0 filas cambiadas si los valores eran iguales, y eso haría
        // insertar un duplicado.
        if ($tabla->clone()->where('venta_id', $venta->id_venta)->exists()) {
            $tabla->where('venta_id', $venta->id_venta)->update($datos);

            return;
        }

        // Ventas viejas que nunca tuvieron movimiento: se crea.
        $tabla->insert($datos + [
            'cliente_id' => $venta->id_cliente,
            'venta_id' => $venta->id_venta,
            'created_at' => now(),
        ]);
    }

    private function registrarHistorial(Venta $venta, string $accion, ?string $estadoAnterior, string $estadoNuevo, ?string $motivo = null, ?array $datos = null): void
    {
        VentaHistorial::create([
            'id_venta' => $venta->id_venta,
            'accion' => $accion,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'motivo' => $motivo,
            'datos' => $datos,
            // Usuario de prueba hasta que exista el login (mismo que usa VentaController@store).
            'id_usuario' => config('cuenta_corriente.id_usuario_prueba'),
        ]);
    }

    /**
     * Suma las cantidades por producto: [id_producto => cantidad total].
     * (Un mismo producto puede estar en dos líneas.)
     *
     * @param  iterable  $detalles  modelos DetalleVenta o arrays con id_producto y cantidad
     * @return array<int, int>
     */
    private function cantidadesPorProducto(iterable $detalles): array
    {
        $cantidades = [];
        foreach ($detalles as $detalle) {
            $producto = (int) $detalle['id_producto'];
            $cantidades[$producto] = ($cantidades[$producto] ?? 0) + (int) $detalle['cantidad'];
        }

        return $cantidades;
    }

    /**
     * Le avisa a Stock los cambios (ya con la venta guardada).
     * Si Stock falla, NO se deshace la venta: se anota en el log para revisarlo.
     */
    private function avisarStock(array $cambios, int $ventaId, string $motivo, ?VentaLogger $log): void
    {
        $cambios = array_filter($cambios, fn (int $cantidad) => $cantidad !== 0);

        $log?->paso('Avisar al módulo de Stock', 'Se informa cuánta mercadería vuelve (+) o sale (-) del depósito.');

        if ($cambios === []) {
            $log?->ok('No cambiaron las cantidades: no hay nada que avisar.');

            return;
        }

        try {
            $this->stock->ajustar($cambios, $ventaId, $motivo);
            $log?->ok('Stock avisado: '.json_encode($cambios));
        } catch (Throwable $e) {
            $log?->detalle('⚠ Stock no respondió ('.$e->getMessage().'). La venta quedó guardada igual; hay que ajustar el stock a mano: '.json_encode($cambios));
        }
    }
}
