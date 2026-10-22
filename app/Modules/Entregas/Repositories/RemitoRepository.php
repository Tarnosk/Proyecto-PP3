<?php
namespace App\Modules\Entregas\Repositories;

use PDO;
use Illuminate\Support\Facades\DB;
use App\Modules\Entregas\Models\Remito;

class RemitoRepository
{
    private ?PDO $db;

    public function __construct(?PDO $db = null)
    {
        try {
            $this->db = $db ?? DB::getPdo();
        } catch (\Throwable $e) {
            $this->db = null;
        }
    }

    /**
     * Generar el siguiente número correlativo de remito oficial (Formato: R-0001-00000001)
     */
    public function generarSiguienteNumeroRemito(int $puntoVenta = 1): string
    {
        $pvPrefijo = sprintf("R-%04d-", $puntoVenta);
        
        $sql = "
            SELECT numero_remito 
            FROM remito 
            WHERE numero_remito LIKE :prefijo 
            ORDER BY id_remito DESC 
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':prefijo' => $pvPrefijo . '%']);
        $ultimo = $stmt->fetchColumn();

        $siguienteNumero = 1;
        if ($ultimo) {
            $partes = explode('-', $ultimo);
            if (isset($partes[2])) {
                $siguienteNumero = ((int)$partes[2]) + 1;
            }
        }

        return sprintf("R-%04d-%08d", $puntoVenta, $siguienteNumero);
    }

    /**
     * Guardar cabecera de Remito
     */
    public function guardarRemito(Remito $remito): int
    {
        $sql = "
            INSERT INTO remito (
                numero_remito, 
                id_entrega, 
                id_venta, 
                estado, 
                observaciones
            ) VALUES (
                :numero_remito, 
                :id_entrega, 
                :id_venta, 
                :estado, 
                :observaciones
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':numero_remito' => $remito->numeroRemito,
            ':id_entrega' => $remito->idEntrega,
            ':id_venta' => $remito->idVenta,
            ':estado' => $remito->estado,
            ':observaciones' => $remito->observaciones
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Guardar detalle de artículos del Remito
     */
    public function guardarDetalleRemito(int $idRemito, array $items): void
    {
        $sql = "
            INSERT INTO detalle_remito (
                id_remito, 
                id_producto, 
                descripcion, 
                cantidad, 
                bultos
            ) VALUES (
                :id_remito, 
                :id_producto, 
                :descripcion, 
                :cantidad, 
                :bultos
            )
        ";
        $stmt = $this->db->prepare($sql);

        foreach ($items as $item) {
            $stmt->execute([
                ':id_remito' => $idRemito,
                ':id_producto' => $item['id_producto'],
                ':descripcion' => $item['producto_nombre'] ?? $item['descripcion'] ?? 'Producto',
                ':cantidad' => (int)$item['cantidad'],
                ':bultos' => (int)($item['bultos'] ?? 1)
            ]);
        }
    }

    /**
     * Obtener Remito completo por su ID (Desacoplado de tablas ajenas)
     */
    public function obtenerRemitoPorId(int $idRemito): ?array
    {
        $sql = "
            SELECT 
                r.*,
                e.id_repartidor,
                e.fecha_salida
            FROM remito r
            INNER JOIN entrega e ON r.id_entrega = e.id_entrega
            WHERE r.id_remito = :id_remito
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_remito' => $idRemito]);
        $remito = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$remito) {
            return null;
        }

        // 1. Obtener datos de la venta y cliente desde la API de Ventas (G4)
        $ventasClient = new \App\Modules\Ventas\Services\VentasApiClient();
        $venta = $ventasClient->obtenerVentaPorId((int)$remito['id_venta']);

        $remito['fecha_venta'] = $venta['fecha'] ?? date('Y-m-d H:i:s');
        $remito['total_venta'] = (float)($venta['total'] ?? $venta['monto_total'] ?? 0.0);
        $remito['total_bultos'] = (int)($venta['total_bultos'] ?? 1);
        $remito['total_sueltos'] = (int)($venta['total_sueltos'] ?? 0);
        $remito['cliente_nombre'] = $venta['cliente_nombre'] ?? $venta['cliente']['nombre'] ?? "Cliente Pedido #{$remito['id_venta']}";
        $remito['razon_social'] = $venta['razon_social'] ?? '';
        $remito['cuit'] = $venta['cuit'] ?? '';
        $remito['direccion'] = $venta['direccion'] ?? 'Domicilio comercial';
        $remito['localidad'] = $venta['localidad'] ?? 'Pigüé';
        $remito['telefono'] = $venta['telefono'] ?? '';
        $remito['zona_nombre'] = $venta['zona_nombre'] ?? 'Ruta Saavedra';
        $remito['repartidor_nombre'] = \App\Modules\Shared\Services\UsuarioService::obtenerNombre((int)$remito['id_repartidor']);

        // 2. Obtener líneas del remito (Tabla propia detalle_remito de G2)
        $sqlDetalle = "
            SELECT dr.*
            FROM detalle_remito dr
            WHERE dr.id_remito = :id_remito
        ";
        $stmtDet = $this->db->prepare($sqlDetalle);
        $stmtDet->execute([':id_remito' => $idRemito]);
        $items = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$it) {
            $it['codigo'] = 'ART-' . ($it['id_producto'] ?? $it['id_detalle_remito']);
        }
        $remito['items'] = $items;

        return $remito;
    }
}
