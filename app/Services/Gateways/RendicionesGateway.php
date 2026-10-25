<?php

namespace App\Services\Gateways;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** G1: lectura de cobros incluidos en rendiciones; no certifica pagos ni saldos. */
class RendicionesGateway
{
    public function __construct(private ExternalJsonClient $http) {}

    public function isConfigured(): bool
    {
        return $this->http->isConfigured('rendiciones');
    }

    public function clientCollections(int $clientId): array
    {
        $rows = $this->http->getList('rendiciones', config('services.rendiciones.path'));
        $parents = $children = $collections = [];
        foreach ($rows as $row) {
            abort_unless(is_array($row) && ! Validator::make($row, [
                'id_rendicion' => ['required', 'integer', 'min:1'],
                'estado' => ['required', 'in:pendiente,aprobada,rechazada'],
                'fecha' => ['required', 'date'],
                'cobros' => ['present', 'array'],
                'cobros.*' => ['array'],
                'cobros.*.id_rendicion_cobro' => ['required', 'integer', 'min:1'],
                'cobros.*.id_rendicion' => ['required', 'integer', 'min:1'],
                'cobros.*.id_cliente' => ['required', 'integer', 'min:1'],
                'cobros.*.id_factura' => ['required', 'integer', 'min:1'],
                'cobros.*.monto' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
                'cobros.*.medio_pago' => ['required', 'in:efectivo,transferencia,cheque'],
                'cobros.*.fecha_registro' => ['required', 'date'],
            ])->fails(), 502, 'G2 devolvió una rendición incompleta o inválida.');
            abort_unless(array_is_list($row['cobros']), 502, 'G2 devolvió una lista de cobros inválida.');
            $parent = (int) $row['id_rendicion'];
            abort_if(isset($parents[$parent]), 502, 'G2 devolvió rendiciones repetidas.');
            $parents[$parent] = true;
            foreach ($row['cobros'] as $collection) {
                $id = (int) $collection['id_rendicion_cobro'];
                abort_if(isset($children[$id]) || (int) $collection['id_rendicion'] !== $parent,
                    502, 'G2 devolvió cobros repetidos o asociados a otra rendición.');
                $children[$id] = true;
                if ((int) $collection['id_cliente'] !== $clientId) {
                    continue;
                }
                $collections[] = [
                    'id' => $id,
                    'rendicion_id' => $parent,
                    'factura_id' => (int) $collection['id_factura'],
                    'fecha' => $collection['fecha_registro'],
                    'monto' => $collection['monto'],
                    'medio_pago' => $collection['medio_pago'],
                    'estado_rendicion' => $row['estado'],
                    // El estado de la rendición no confirma cada cobro individual.
                    'confirmacion_individual' => null,
                ];
            }
        }

        return collect($collections)->sortByDesc('fecha')->values()->all();
    }

    /** Una integración opcional fallida no impide consultar la ficha de G1. */
    public function clientSnapshot(int $clientId): array
    {
        if (! $this->isConfigured()) {
            return ['ready' => false, 'rows' => [], 'message' => 'Sin datos disponibles: la conexión de G1 a Rendiciones de G2 no está configurada.'];
        }
        try {
            return ['ready' => true, 'rows' => $this->clientCollections($clientId), 'message' => null];
        } catch (HttpException $exception) {
            if (! in_array($exception->getStatusCode(), [502, 503], true)) {
                throw $exception;
            }

            return ['ready' => false, 'rows' => [], 'message' => 'Sin datos disponibles: G2 no respondió con un listado válido de rendiciones.'];
        }
    }
}
