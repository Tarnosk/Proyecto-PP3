<?php
namespace App\Modules\Rendiciones\Services;

class ProcesadorDeDevolucionRendicion 
{
    public function registrarDevolucion(array $datos): array 
    {
        // 1. Valido que vengan los campos obligatorios según los criterios
        if (!isset($datos['id_rendicion']) || !isset($datos['id_pedido']) || !isset($datos['motivo'])) {
            return [
                "error" => true,
                "codigo" => 400,
                "mensaje" => "Faltan datos obligatorios (id_rendicion, id_pedido o motivo)."
            ];
        }

        // 2. Estructuro el registro de la devolución asociada al recorrido
        return [
            "error" => false,
            "codigo" => 201,
            "mensaje" => "Devolución registrada y asociada a la rendición exitosamente.",
            "data" => [
                "id_devolucion" => rand(1000, 9999),
                "id_rendicion" => $datos['id_rendicion'],
                "id_pedido" => $datos['id_pedido'],
                "cliente" => $datos['cliente'] ?? 'Cliente general',
                "motivo" => $datos['motivo'],
                "observaciones" => $datos['observaciones'] ?? 'Sin observaciones',
                "estado_entrega" => "no_entregada",
                "fecha_registro" => date('Y-m-d H:i:s')
            ]
        ];
    }
}