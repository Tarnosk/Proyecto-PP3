<?php

namespace App\Modules\ConciliacionBancaria\Controllers;

use App\Modules\ConciliacionBancaria\Services\ChequeService;
use App\Modules\Shared\Http\Controllers\Controller;
use App\Modules\Shared\Http\Response;
use Illuminate\Http\Request;

/**
 * Controller de Cheques (dentro de Conciliación Bancaria).
 *
 * ALCANCE NO CONFIRMADO: no hay historia de usuario que respalde estos
 * endpoints — se propusieron a partir de los valores de `estado` en la
 * migración CHEQUE. Pendiente de formalizar y confirmar con el equipo.
 */
class ChequeController extends Controller
{
    public function __construct(private ChequeService $service)
    {
    }

    public function index(Request $request)
    {
        $estado = $request->query('estado');

        Response::json($this->service->listar($estado));
    }

    public function show(int $id)
    {
        $cheque = $this->service->buscar($id);

        if ($cheque === null) {
            Response::error('Cheque no encontrado', 404);
            return;
        }

        Response::json($cheque);
    }

    public function depositar(int $id)
    {
        $resultado = $this->service->depositar($id);

        if ($resultado === null) {
            Response::error('Cheque no encontrado', 404);
            return;
        }

        if ($resultado['error'] ?? false) {
            Response::error($resultado['mensaje'], 400);
            return;
        }

        Response::json($resultado);
    }

    public function rechazar(int $id)
    {
        $resultado = $this->service->rechazar($id);

        if ($resultado === null) {
            Response::error('Cheque no encontrado', 404);
            return;
        }

        if ($resultado['error'] ?? false) {
            Response::error($resultado['mensaje'], 400);
            return;
        }

        Response::json($resultado);
    }
}