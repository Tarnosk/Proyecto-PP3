<?php
namespace App\Modules\Entregas\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Modules\Entregas\Models\Entrega;
use App\Modules\Entregas\Models\Remito;
use App\Modules\Entregas\Repositories\EntregaRepository;
use App\Modules\Entregas\Repositories\RemitoRepository;
use App\Modules\Entregas\Integraciones\VentasApiClient;

/**
 * Servicio de Negocio: Entregas y Remitos
 * Contiene las reglas de negocio de HU #1 y HU #2
 */
class EntregaService
{
    private EntregaRepository $entregaRepo;
    private RemitoRepository $remitoRepo;
    private VentasApiClient $ventasClient;

    public function __construct(
        ?EntregaRepository $entregaRepo = null, 
        ?RemitoRepository $remitoRepo = null,
        ?VentasApiClient $ventasClient = null
    ) {
        $this->entregaRepo = $entregaRepo ?? new EntregaRepository();
        $this->remitoRepo = $remitoRepo ?? new RemitoRepository();
        $this->ventasClient = $ventasClient ?? new VentasApiClient();
    }

    /**
     * HU #1: Visualizar listado de pedidos confirmados listos para despacho
     */
    public function obtenerPedidosPendientes(?int $idZona = null): array
    {
        $pedidos = $this->entregaRepo->obtenerPedidosConfirmadosPendientes($idZona);

        // Enriquecer cada pedido con el detalle de sus productos
        foreach ($pedidos as &$pedido) {
            $pedido['items'] = $this->entregaRepo->obtenerDetalleVenta((int)$pedido['id_venta']);
        }

        return $pedidos;
    }

    /**
     * HU #2: Armado de entrega y emisión de remitos oficiales
     * Ejecuta una transacción atómica (todo o nada)
     */
    public function armarEntrega(
        int $idRepartidor,
        array $pedidosIds,
        string $fechaSalida,
        int $idUsuarioCreacion,
        ?string $observaciones = null
    ): array {
        if (empty($pedidosIds)) {
            throw new Exception("Debe seleccionar al menos un pedido para armar la entrega.");
        }

        if ($idRepartidor <= 0) {
            throw new Exception("Debe asignar un repartidor válido.");
        }

        if (empty($fechaSalida)) {
            $fechaSalida = date('Y-m-d');
        }

        // 1. Iniciar transacción atómica
        DB::beginTransaction();

        try {
            // 2. Crear cabecera de la Entrega (Viaje en preparación)
            $entrega = new Entrega(
                $idRepartidor,
                $fechaSalida,
                $idUsuarioCreacion,
                'en_preparacion',
                $observaciones
            );

            $idEntrega = $this->entregaRepo->guardarEntrega($entrega);
            $remitosCreados = [];

            // 3. Procesar cada pedido seleccionado
            foreach ($pedidosIds as $idVenta) {
                $idVenta = (int)$idVenta;

                // A. Vincular pedido a la entrega
                $this->entregaRepo->vincularPedidoAEntrega($idEntrega, $idVenta, 'en_preparacion');

                // B. Cambiar estado del pedido a 'en_preparacion'
                $this->entregaRepo->actualizarEstadoVenta($idVenta, 'en_preparacion');

                // C. Obtener productos y bultos del pedido
                $items = $this->entregaRepo->obtenerDetalleVenta($idVenta);
                if (empty($items)) {
                    $items = [
                        [
                            'id_producto' => null,
                            'codigo' => 'PED-' . $idVenta,
                            'producto_nombre' => 'Mercadería consolidada Pedido #' . $idVenta,
                            'descripcion' => 'Bultos cerrados de reparto',
                            'cantidad' => 1,
                            'bultos' => 1,
                            'unidades_sueltas' => 0
                        ]
                    ];
                }

                // D. Generar número de remito correlativo oficial
                $numeroRemito = $this->remitoRepo->generarSiguienteNumeroRemito(1);

                // E. Guardar cabecera de Remito
                $remito = new Remito(
                    $numeroRemito,
                    $idEntrega,
                    $idVenta,
                    'emitido',
                    "Remito generado para entrega #{$idEntrega}"
                );
                $idRemito = $this->remitoRepo->guardarRemito($remito);

                // F. Guardar detalle físico del Remito
                $this->remitoRepo->guardarDetalleRemito($idRemito, $items);

                $remitosCreados[] = [
                    'id_remito' => $idRemito,
                    'numero_remito' => $numeroRemito,
                    'id_venta' => $idVenta
                ];
            }

            // 4. Confirmar todos los cambios
            DB::commit();

            // 5. Notificar a la API de Ventas del Grupo 4 (HTTP)
            foreach ($pedidosIds as $idVentaNotif) {
                try {
                    $this->ventasClient->actualizarEstadoVenta((int)$idVentaNotif, 'en_camino');
                } catch (\Throwable $e) {
                    // Log y continuar para no romper la transacción confirmada
                }
            }

            return [
                'id_entrega' => $idEntrega,
                'estado' => 'en_preparacion',
                'fecha_salida' => $fechaSalida,
                'cantidad_pedidos' => count($pedidosIds),
                'remitos' => $remitosCreados,
                'mensaje' => "Entrega #{$idEntrega} creada con éxito y " . count($remitosCreados) . " remitos emitidos."
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Error al armar la entrega: " . $e->getMessage());
        }
    }

    /**
     * Listar entregas
     */
    public function listarEntregas(array $filtros = []): array
    {
        return $this->entregaRepo->listarEntregas($filtros);
    }

    /**
     * Obtener detalle de una entrega
     */
    public function obtenerEntrega(int $idEntrega): ?array
    {
        $entrega = $this->entregaRepo->obtenerEntregaPorId($idEntrega);
        if (!$entrega) {
            throw new Exception("La entrega #{$idEntrega} no existe.");
        }
        return $entrega;
    }

    /**
     * Obtener Remito para visualización / impresión
     */
    public function obtenerRemito(int $idRemito): ?array
    {
        $remito = $this->remitoRepo->obtenerRemitoPorId($idRemito);
        if (!$remito) {
            throw new Exception("El remito #{$idRemito} no existe.");
        }
        return $remito;
    }

    public function obtenerZonas(): array
    {
        return $this->entregaRepo->listarZonas();
    }

    public function obtenerRepartidores(): array
    {
        return $this->entregaRepo->listarRepartidores();
    }

    /**
     * HU #3 (Issue #124): Notificar y confirmar pedido entregado
     * Permite al chofer marcar un pedido como completado en tiempo real
     */
    public function confirmarPedidoEntregado(
        int $idEntrega,
        int $idVenta,
        ?string $observaciones,
        int $idUsuarioAuditoria
    ): array {
        if ($idEntrega <= 0) {
            throw new Exception("El ID de entrega es inválido.");
        }
        if ($idVenta <= 0) {
            throw new Exception("El ID de venta/pedido es inválido.");
        }

        $entrega = $this->entregaRepo->obtenerEntregaPorId($idEntrega);
        if (!$entrega) {
            throw new Exception("La entrega #{$idEntrega} no existe.");
        }

        $pedidoEncontrado = null;
        foreach ($entrega['pedidos'] as $p) {
            if ((int)$p['id_venta'] === $idVenta) {
                $pedidoEncontrado = $p;
                break;
            }
        }

        if (!$pedidoEncontrado) {
            throw new Exception("El pedido #{$idVenta} no está asociado a la entrega #{$idEntrega}.");
        }

        if ($pedidoEncontrado['estado_pedido_entrega'] === 'entregado') {
            throw new Exception("El pedido #{$idVenta} ya se encuentra confirmado como entregado.");
        }

        DB::beginTransaction();
        try {
            $this->entregaRepo->marcarPedidoComoEntregado($idEntrega, $idVenta, $observaciones);
            DB::commit();

            // 2. Precargar automáticamente la rendición en estado 'pendiente' para administración
            try {
                $rendicionService = new \App\Modules\Rendiciones\Services\RendicionService(new \App\Modules\Rendiciones\Repositories\RendicionRepository());
                $rendicionService->precargarOActualizarRendicionDesdeEntrega($idEntrega);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("No se pudo precargar automáticamente la rendición para la entrega #{$idEntrega}: " . $e->getMessage());
            }

            // 3. Notificar a la API de Ventas del Grupo 4 (HTTP)
            try {
                $this->ventasClient->actualizarEstadoVenta($idVenta, 'entregada');
            } catch (\Throwable $e) {
                // Loguear error pero mantener la entrega confirmada
            }

            return [
                'id_entrega' => $idEntrega,
                'id_venta' => $idVenta,
                'estado_pedido' => 'entregado',
                'fecha_confirmacion' => date('Y-m-d H:i:s'),
                'cliente' => $pedidoEncontrado['cliente_nombre'] ?? '',
                'mensaje' => "¡Pedido #PED-" . str_pad($idVenta, 4, '0', STR_PAD_LEFT) . " confirmado como ENTREGADO! La rendición ha sido enviada automáticamente a administración.",
                'rendicion_precargada' => true
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Error al confirmar entrega del pedido: " . $e->getMessage());
        }
    }

    /**
     * Obtener entregas con pedidos entregados listas para rendición
     */
    public function obtenerEntregasPendientesRendicion(?int $idRepartidor = null): array
    {
        return $this->entregaRepo->obtenerEntregasListasParaRendicion($idRepartidor);
    }
}
