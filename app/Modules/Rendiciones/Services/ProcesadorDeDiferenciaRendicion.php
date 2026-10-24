<?php
namespace App\Modules\Rendiciones\Services;

class ProcesadorDeDiferenciaRendicion 
{
    public function registrarDiferencia(array $datos): array 
    {
        // 1. Valido que vengan los campos obligatorios para calcular la diferencia
        if (!isset($datos['id_rendicion']) || !isset($datos['total_esperado']) || !isset($datos['total_rendido'])) {
            return [
                "error" => true,
                "codigo" => 400,
                "mensaje" => "Faltan datos obligatorios (id_rendicion, total_esperado o total_rendido)."
            ];
        }

        $totalEsperado = (float)$datos['total_esperado'];
        $totalRendido = (float)$datos['total_rendido'];
        
        // 2. Calculo la diferencia automáticamente (Rendido - Esperado)
        // Si da negativo hay un faltante, si da positivo hay un sobrante, si da 0 está exacto.
        $diferencia = $totalRendido - $totalEsperado;
        
        $tipoDiferencia = "Exacto";
        if ($diferencia < 0) {
            $tipoDiferencia = "Faltante";
        } elseif ($diferencia > 0) {
            $tipoDiferencia = "Sobrante";
        }

        // 3. Estructuro el resultado de la auditoría de diferencias
        return [
            "error" => false,
            "codigo" => 201,
            "mensaje" => "Diferencia de rendición registrada y calculada exitosamente.",
            "data" => [
                "id_rendicion" => $datos['id_rendicion'],
                "total_esperado" => $totalEsperado,
                "total_rendido" => $totalRendido,
                "diferencia" => round($diferencia, 2),
                "tipo_diferencia" => $tipoDiferencia,
                "motivo" => $datos['motivo'] ?? 'No especificado',
                "observaciones" => $datos['observaciones'] ?? 'Sin observaciones',
                "diferencia_pendiente" => ($diferencia !== 0.0),
                "fecha_auditoria" => date('Y-m-d H:i:s')
            ]
        ];
    }
}