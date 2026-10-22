<?php
namespace App\Modules\Rendiciones\Services;

class ProcesadorDeRendicionReparto 
{
    public function registrarRendicionReparto(array $datos): array 
    {
        if (!isset($datos['id_reparto']) || !isset($datos['id_repartidor'])) {
            return [
                "error" => true,
                "codigo" => 400,
                "mensaje" => "Faltan datos obligatorios del reparto o del repartidor."
            ];
        }

        $cobros = $datos['cobros'] ?? [];
        $remitos = $datos['remitos'] ?? [];

        if (empty($cobros) && empty($remitos)) {
            return [
                "error" => true,
                "codigo" => 400,
                "mensaje" => "La rendición debe incluir al menos un cobro o un remito entregado."
            ];
        }

        $totalRendido = 0;
        foreach ($cobros as $cobro) {
            if (!isset($cobro['monto']) || $cobro['monto'] < 0) {
                return [
                    "error" => true,
                    "codigo" => 400,
                    "mensaje" => "Se detectó un monto inválido dentro del listado de cobros de la rendición."
                ];
            }
            $totalRendido += $cobro['monto'];
        }

        return [
            "error" => false,
            "codigo" => 201,
            "mensaje" => "Rendición de reparto registrada y totales calculados exitosamente.",
            "data" => [
                "id_rendicion" => rand(100, 999),
                "id_reparto" => $datos['id_reparto'],
                "id_repartidor" => $datos['id_repartidor'],
                "fecha_rendicion" => date('Y-m-d H:i:s'),
                "total_rendido" => $totalRendido,
                "cantidad_cobros_asociados" => count($cobros),
                "cantidad_remitos_asociados" => count($remitos),
                "observaciones" => $datos['observaciones'] ?? 'Sin observaciones',
                "estado" => "Pendiente"
            ]
        ];
    }
}