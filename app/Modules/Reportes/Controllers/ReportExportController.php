<?php

namespace App\Modules\Reportes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Reportes\Requests\ClientHistoryRequest;
use App\Modules\Reportes\Services\ReportService;
use App\Modules\Reportes\Services\SpreadsheetExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function clientHistory(
        ClientHistoryRequest $request,
        string $format,
        ReportService $reports,
        SpreadsheetExportService $spreadsheets
    ): Response|StreamedResponse {
        $data = $request->validated();
        abort_unless(isset($data['cliente_id']), 422, 'Debe seleccionar un cliente.');
        $client = Cliente::query()->findOrFail($data['cliente_id']);
        $sales = $reports->clientSales($client, $data['desde'] ?? null, $data['hasta'] ?? null);
        $rows = $sales->map(fn ($sale): array => [
            $sale->fecha->format('d/m/Y H:i'),
            $sale->numero_factura ?: 'Sin comprobante',
            $sale->detalles->map(fn ($detail): string => $detail->cantidad.' x '.$detail->producto->nombre)->join(', ') ?: 'Sin detalle',
            ucfirst($sale->estado->value),
            (float) $sale->total,
        ])->all();

        return $this->export(
            $format,
            'Historial - '.$client->apellido_razon_social.', '.$client->nombre,
            ['Fecha', 'Comprobante', 'Productos', 'Estado', 'Total'],
            $rows,
            'historial-cliente-'.$client->id,
            $spreadsheets
        );
    }

    private function export(
        string $format,
        string $title,
        array $headers,
        array $rows,
        string $filename,
        SpreadsheetExportService $spreadsheets
    ): Response|StreamedResponse {
        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);

        if ($format === 'xlsx') {
            return $spreadsheets->download($title, $headers, $rows, $filename.'.xlsx');
        }

        return Pdf::loadView('reports.pdf', compact('title', 'headers', 'rows'))
            ->setPaper('a4', 'landscape')
            ->download($filename.'.pdf');
    }
}
