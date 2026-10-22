<?php
namespace App\Modules\Entregas\Repositories;

use PDO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Modules\Entregas\Models\Entrega;
use App\Modules\Ventas\Services\VentasApiClient;
use App\Modules\Shared\Services\UsuarioService;

class EntregaRepository
{
    private ?PDO $db;
    private VentasApiClient $ventasClient;

    public function __construct(?PDO $db = null, ?VentasApiClient $ventasClient = null)
    {
        try {
            $this->db = $db ?? DB::getPdo();
        } catch (\Throwable $e) {
            $this->db = null;
        }
        $this->ventasClient = $ventasClient ?? new VentasApiClient();
    }

    /**
     * HU #1: Obtener pedidos confirmados que no han sido asignados a ninguna entrega activa.
     * Desacoplado: Consume las ventas confirmadas desde la API de Ventas (G4) o su simulación JSON,
     * y las cruza contra la tabla local de asignaciones 'entrega_pedido' (G2).
     */
    public function obtenerPedidosConfirmadosPendientes(?int $idZona = null): array
    {
        // 1. Obtener IDs de ventas que ya están comprometidas en entregas activas (G2)
        $sqlOcupados = "
            SELECT DISTINCT ep.id_venta 
            FROM entrega_pedido ep
            INNER JOIN entrega e ON ep.id_entrega = e.id_entrega
            WHERE e.estado IN ('en_preparacion', 'en_camino')
        ";
        $idsOcupados = [];
        try {
            $stmtOcupados = $this->db->query($sqlOcupados);
            $idsOcupados = $stmtOcupados ? $stmtOcupados->fetchAll(PDO::FETCH_COLUMN) : [];
        } catch (\Throwable) {
            $idsOcupados = [];
        }

        // 2. Obtener ventas confirmadas mediante el cliente de API de Ventas
        $ventas = $this->ventasClient->obtenerVentasPendientesDespacho($idZona);

        // 3. Filtrar ventas que ya estén asignadas a una entrega activa
        $pendientes = array_filter($ventas, function ($v) use ($idsOcupados) {
            return !in_array((int)$v['id_venta'], array_map('intval', $idsOcupados));
        });

        return array_values($pendientes);
    }

    /**
     * Obtener detalle de productos de una venta específica mediante la API de Ventas.
     */
    public function obtenerDetalleVenta(int $idVenta): array
    {
        $venta = $this->ventasClient->obtenerVentaPorId($idVenta);
        if ($venta && !empty($venta['items'])) {
            return $venta['items'];
        }

        // Fallback si la tabla detalle_venta existiera localmente
        if (Schema::hasTable('detalle_venta') && Schema::hasTable('producto')) {
            try {
                $sql = "
                    SELECT 
                        dv.id_detalle_venta,
                        dv.id_producto,
                        p.codigo,
                        p.nombre AS producto_nombre,
                        p.descripcion AS producto_descripcion,
                        dv.cantidad,
                        dv.precio_unitario,
                        dv.subtotal,
                        dv.bultos,
                        dv.unidades_sueltas
                    FROM detalle_venta dv
                    INNER JOIN producto p ON dv.id_producto = p.id_producto
                    WHERE dv.id_venta = :id_venta
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':id_venta' => $idVenta]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable) {}
        }

        return [
            [
                'id_producto' => 1,
                'codigo' => 'ART-' . $idVenta,
                'producto_nombre' => 'Mercadería consolidada Pedido #' . $idVenta,
                'cantidad' => 1,
                'precio_unitario' => (float)($venta['total'] ?? 10000.00),
                'subtotal' => (float)($venta['total'] ?? 10000.00),
                'bultos' => (int)($venta['total_bultos'] ?? 1)
            ]
        ];
    }

    /**
     * Insertar nueva Entrega (Tabla propia G2)
     */
    public function guardarEntrega(Entrega $entrega): int
    {
        $sql = "
            INSERT INTO entrega (
                id_repartidor, 
                fecha_salida, 
                estado, 
                observaciones, 
                id_usuario_creacion
            ) VALUES (
                :id_repartidor, 
                :fecha_salida, 
                :estado, 
                :observaciones, 
                :id_usuario_creacion
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_repartidor' => $entrega->idRepartidor,
            ':fecha_salida' => $entrega->fechaSalida,
            ':estado' => $entrega->estado,
            ':observaciones' => $entrega->observaciones,
            ':id_usuario_creacion' => $entrega->idUsuarioCreacion
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Asignar un pedido a una Entrega (Tabla propia G2)
     */
    public function vincularPedidoAEntrega(int $idEntrega, int $idVenta, string $estadoPedido = 'en_preparacion'): void
    {
        $sql = "
            INSERT INTO entrega_pedido (
                id_entrega, 
                id_venta, 
                estado_pedido_entrega
            ) VALUES (
                :id_entrega, 
                :id_venta, 
                :estado_pedido_entrega
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_entrega' => $idEntrega,
            ':id_venta' => $idVenta,
            ':estado_pedido_entrega' => $estadoPedido
        ]);
    }

    /**
     * Actualizar estado de una venta (Notifica vía HTTP al microservicio de Ventas de G4)
     */
    public function actualizarEstadoVenta(int $idVenta, string $nuevoEstado): void
    {
        $this->ventasClient->actualizarEstadoVenta($idVenta, $nuevoEstado);

        if (Schema::hasTable('venta')) {
            try {
                $sql = "UPDATE venta SET estado = :estado WHERE id_venta = :id_venta";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':estado' => $nuevoEstado,
                    ':id_venta' => $idVenta
                ]);
            } catch (\Throwable) {}
        }
    }

    /**
     * Listar entregas realizadas o en curso (Tablas propias: entrega, entrega_pedido)
     */
    public function listarEntregas(array $filtros = []): array
    {
        $sql = "
            SELECT 
                e.id_entrega,
                e.id_repartidor,
                e.fecha_creacion,
                e.fecha_salida,
                e.estado,
                e.observaciones,
                COUNT(DISTINCT ep.id_venta) AS cantidad_pedidos
            FROM entrega e
            LEFT JOIN entrega_pedido ep ON e.id_entrega = ep.id_entrega
            WHERE 1=1
        ";

        $params = [];
        if (!empty($filtros['repartidor_id'])) {
            $sql .= " AND e.id_repartidor = :repartidor_id ";
            $params[':repartidor_id'] = $filtros['repartidor_id'];
        }
        if (!empty($filtros['fecha'])) {
            $sql .= " AND e.fecha_salida = :fecha ";
            $params[':fecha'] = $filtros['fecha'];
        }
        if (!empty($filtros['estado'])) {
            $sql .= " AND e.estado = :estado ";
            $params[':estado'] = $filtros['estado'];
        }

        $sql .= " GROUP BY e.id_entrega ORDER BY e.id_entrega DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enriquecer con nombres de repartidores mediante el servicio desacoplado
        foreach ($entregas as &$ent) {
            $ent['repartidor_nombre'] = UsuarioService::obtenerNombre((int)$ent['id_repartidor']);
            $ent['total_bultos'] = (int)($ent['cantidad_pedidos'] ?? 1) * 2;
            $ent['total_dinero'] = 0.0;
        }

        return $entregas;
    }

    /**
     * Obtener detalle completo de una Entrega y sus pedidos
     */
    public function obtenerEntregaPorId(int $idEntrega): ?array
    {
        $sql = "SELECT e.* FROM entrega e WHERE e.id_entrega = :id_entrega";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_entrega' => $idEntrega]);
        $entrega = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$entrega) {
            return null;
        }

        $entrega['repartidor_nombre'] = UsuarioService::obtenerNombre((int)$entrega['id_repartidor']);
        $entrega['creador_nombre'] = UsuarioService::obtenerNombre((int)$entrega['id_usuario_creacion']);

        // Obtener pedidos incluidos desde entrega_pedido y remito (Tablas de G2)
        $sqlPedidos = "
            SELECT 
                ep.id_entrega_pedido,
                ep.id_entrega,
                ep.id_venta,
                ep.estado_pedido_entrega,
                ep.motivo_rechazo,
                r.id_remito,
                r.numero_remito,
                r.estado AS estado_remito
            FROM entrega_pedido ep
            LEFT JOIN remito r ON r.id_entrega = ep.id_entrega AND r.id_venta = ep.id_venta
            WHERE ep.id_entrega = :id_entrega
            ORDER BY ep.id_entrega_pedido ASC
        ";
        $stmtPedidos = $this->db->prepare($sqlPedidos);
        $stmtPedidos->execute([':id_entrega' => $idEntrega]);
        $pedidosRaw = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);

        // Enriquecer cada pedido consultando la API de Ventas (G4)
        $pedidos = [];
        foreach ($pedidosRaw as $p) {
            $venta = $this->ventasClient->obtenerVentaPorId((int)$p['id_venta']);
            $pedidos[] = [
                'id_venta' => (int)$p['id_venta'],
                'fecha_venta' => $venta['fecha'] ?? date('Y-m-d H:i:s'),
                'total' => (float)($venta['total'] ?? $venta['monto_total'] ?? 0.00),
                'total_bultos' => (int)($venta['total_bultos'] ?? 1),
                'total_sueltos' => (int)($venta['total_sueltos'] ?? 0),
                'observaciones_venta' => $venta['observaciones'] ?? '',
                'estado_pedido_entrega' => $p['estado_pedido_entrega'],
                'motivo_rechazo' => $p['motivo_rechazo'],
                'id_cliente' => (int)($venta['id_cliente'] ?? 1),
                'cliente_nombre' => $venta['cliente_nombre'] ?? $venta['cliente']['nombre'] ?? "Cliente Pedido #{$p['id_venta']}",
                'direccion' => $venta['direccion'] ?? 'Domicilio comercial',
                'localidad' => $venta['localidad'] ?? 'Pigüé',
                'telefono' => $venta['telefono'] ?? '',
                'zona_nombre' => $venta['zona_nombre'] ?? 'Ruta Saavedra',
                'id_remito' => $p['id_remito'],
                'numero_remito' => $p['numero_remito'],
                'estado_remito' => $p['estado_remito']
            ];
        }

        $entrega['pedidos'] = $pedidos;
        return $entrega;
    }

    /**
     * Listar Zonas (Tabla propia G2)
     */
    public function listarZonas(): array
    {
        $stmt = $this->db->query("SELECT * FROM zona ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * HU #3: Marcar un pedido dentro de una entrega como entregado
     */
    public function marcarPedidoComoEntregado(int $idEntrega, int $idVenta, ?string $observaciones = null): void
    {
        // 1. Actualizar entrega_pedido (G2)
        $sqlEp = "UPDATE entrega_pedido 
                  SET estado_pedido_entrega = 'entregado', 
                      fecha_actualizacion = NOW() 
                  WHERE id_entrega = :id_entrega AND id_venta = :id_venta";
        $stmtEp = $this->db->prepare($sqlEp);
        $stmtEp->execute([
            ':id_entrega' => $idEntrega,
            ':id_venta' => $idVenta
        ]);

        // 2. Actualizar estado del remito asociado a 'entregado_conforme' (G2)
        $sqlRemito = "UPDATE remito 
                      SET estado = 'entregado_conforme' 
                      WHERE id_entrega = :id_entrega AND id_venta = :id_venta";
        $stmtRemito = $this->db->prepare($sqlRemito);
        $stmtRemito->execute([
            ':id_entrega' => $idEntrega,
            ':id_venta' => $idVenta
        ]);

        // 3. Notificar a la API de Ventas de G4 (HTTP)
        $this->actualizarEstadoVenta($idVenta, 'entregada');

        // 4. Verificar si todos los pedidos de la entrega están entregados (G2)
        $sqlCheck = "SELECT COUNT(*) AS no_entregados 
                     FROM entrega_pedido 
                     WHERE id_entrega = :id_entrega 
                       AND estado_pedido_entrega != 'entregado'";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute([':id_entrega' => $idEntrega]);
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        $noEntregados = (int)($row['no_entregados'] ?? 0);
        $nuevoEstadoEntrega = ($noEntregados === 0) ? 'finalizada' : 'en_camino';

        $sqlEntrega = "UPDATE entrega SET estado = :estado WHERE id_entrega = :id_entrega";
        $stmtEntrega = $this->db->prepare($sqlEntrega);
        $stmtEntrega->execute([
            ':estado' => $nuevoEstadoEntrega,
            ':id_entrega' => $idEntrega
        ]);
    }

    /**
     * Listar Repartidores activos (Mediante UsuarioService desacoplado)
     */
    public function listarRepartidores(): array
    {
        return UsuarioService::obtenerRepartidores();
    }

    /**
     * Obtener entregas con pedidos entregados listas para iniciar rendición (Tablas G2: entrega, entrega_pedido)
     */
    public function obtenerEntregasListasParaRendicion(?int $idRepartidor = null): array
    {
        $sql = "
            SELECT 
                e.id_entrega,
                e.fecha_salida,
                e.estado AS estado_entrega,
                e.id_repartidor,
                COUNT(DISTINCT ep.id_venta) AS cantidad_pedidos,
                SUM(CASE WHEN ep.estado_pedido_entrega = 'entregado' THEN 1 ELSE 0 END) AS pedidos_entregados
            FROM entrega e
            INNER JOIN entrega_pedido ep ON e.id_entrega = ep.id_entrega
            WHERE e.estado IN ('en_camino', 'en_preparacion', 'finalizada')
        ";

        $params = [];
        if ($idRepartidor !== null && $idRepartidor > 0) {
            $sql .= " AND e.id_repartidor = :id_repartidor ";
            $params[':id_repartidor'] = $idRepartidor;
        }

        $sql .= " GROUP BY e.id_entrega HAVING pedidos_entregados > 0 OR e.estado = 'finalizada' ORDER BY e.id_entrega DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($entregas as &$ent) {
            $ent['repartidor_nombre'] = UsuarioService::obtenerNombre((int)$ent['id_repartidor']);
            // Obtener el total aproximado consultando los pedidos entregados
            $sqlVentas = "SELECT id_venta FROM entrega_pedido WHERE id_entrega = :id_entrega AND estado_pedido_entrega = 'entregado'";
            $stmtV = $this->db->prepare($sqlVentas);
            $stmtV->execute([':id_entrega' => $ent['id_entrega']]);
            $ids = $stmtV->fetchAll(PDO::FETCH_COLUMN);

            $totalDinero = 0.0;
            foreach ($ids as $idV) {
                $venta = $this->ventasClient->obtenerVentaPorId((int)$idV);
                $totalDinero += (float)($venta['total'] ?? $venta['monto_total'] ?? 0.0);
            }
            $ent['total_entregado_dinero'] = round($totalDinero, 2);
        }

        return $entregas;
    }
}
