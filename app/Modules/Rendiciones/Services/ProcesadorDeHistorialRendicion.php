<?php
namespace App\Modules\Rendiciones\Services;

class ProcesadorDeHistorialRendicion 
{
    public function consultarHistorial(array $filtros): array 
    {
        // 1. Simulamos un conjunto de datos histórico (Mock de base de datos)
        $historialMock = [
            [
                "id_rendicion" => 15,
                "fecha" => "2026-08-20",
                "id_repartidor" => 12,
                "repartidor" => "Carlos Gómez",
                "estado" => "Aprobada",
                "total_rendido" => 50000.00,
                "observaciones" => "Todo verificado correctamente contra caja."
            ],
            [
                "id_rendicion" => 16,
                "fecha" => "2026-08-21",
                "id_repartidor" => 8,
                "repartidor" => "Esteban Quito",
                "estado" => "Pendiente",
                "total_rendido" => 32400.50,
                "observaciones" => "Pendiente de revisión por faltante menor."
            ],
            [
                "id_rendicion" => 18,
                "fecha" => "2026-08-22",
                "id_repartidor" => 12,
                "repartidor" => "Carlos Gómez",
                "estado" => "Rechazada",
                "total_rendido" => 19000.00,
                "observaciones" => "Faltan comprobantes físicos de los gastos declarados."
            ]
        ];

        // 2. Aplicamos filtros opcionales si el usuario los mandó en la petición
        $resultadosFiltrados = $historialMock;

        if (!empty($filtros['fecha'])) {
            $resultadosFiltrados = array_filter($resultadosFiltrados, function($item) use ($filtros) {
                return $item['fecha'] === $filtros['fecha'];
            });
        }

        if (!empty($filtros['id_repartidor'])) {
            $resultadosFiltrados = array_filter($resultadosFiltrados, function($item) use ($filtros) {
                return $item['id_repartidor'] == $filtros['id_repartidor'];
            });
        }

        if (!empty($filtros['estado'])) {
            $estadoFiltro = strtolower($filtros['estado']);
            $resultadosFiltrados = array_filter($resultadosFiltrados, function($item) use ($estadoFiltro) {
                return strtolower($item['estado']) === $estadoFiltro;
            });
        }

        // Reindexamos el array para que devuelva una lista limpia en JSON
        $resultadosFiltrados = array_values($resultadosFiltrados);

        // 3. Retornamos el listado con información detallada e histórica
        return [
            "error" => false,
            "codigo" => 200,
            "mensaje" => "Historial de rendiciones consultado exitosamente.",
            "total_registros" => count($resultadosFiltrados),
            "data" => $resultadosFiltrados
        ];
    }
}