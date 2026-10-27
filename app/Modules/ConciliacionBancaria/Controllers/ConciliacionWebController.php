<?php

namespace App\Modules\ConciliacionBancaria\Controllers;

use App\Modules\ConciliacionBancaria\Services\ConciliacionService;
use App\Modules\Shared\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Shared\Auth\G2Identity;

class ConciliacionWebController extends Controller
{


    public function __construct(private ConciliacionService $service)
    {
    }

    public function index(Request $request)
    {
        $periodos = $this->service->listarPeriodos($request->query('estado'));

        return view('conciliacion.index', compact('periodos'));
    }

    public function asegurarMes()
    {
        $this->service->asegurarPeriodoAutomaticoDelMes(G2Identity::id());

        return redirect()->route('conciliacion.index')->with('mensaje', 'Período del mes disponible.');
    }

    public function create()
    {
        return view('conciliacion.create');
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'fecha_desde' => ['required', 'date'],
            'fecha_hasta' => ['required', 'date', 'after_or_equal:fecha_desde'],
        ]);

        $datos['id_usuario'] = G2Identity::id();

        $resultado = $this->service->crearPeriodo($datos);

        if ($resultado['error'] ?? false) {
            return back()->withInput()->with('error', $resultado['mensaje']);
        }

        return redirect()
            ->route('conciliacion.show', $resultado['id_periodo'])
            ->with('mensaje', 'Periodo de conciliacion creado correctamente.');
    }

    public function show(int $id)
    {
        $periodo = $this->service->buscarPeriodo($id);

        if ($periodo === null) {
            abort(404, 'Periodo de conciliacion no encontrado');
        }

        return view('conciliacion.show', compact('periodo'));
    }

    public function conciliarAutomatico(int $id)
    {
        $resultado = $this->service->conciliarAutomatico($id);

        if ($resultado === null) {
            abort(404, 'Periodo de conciliacion no encontrado');
        }

        if ($resultado['error'] ?? false) {
            return redirect()
                ->route('conciliacion.show', $id)
                ->with('error', $resultado['mensaje']);
        }

        $periodo = $this->service->buscarPeriodo($id);

        return view('conciliacion.show', [
            'periodo' => $periodo,
            'resultadoConciliacion' => $resultado,
        ]);
    }

    public function conciliarManual(Request $request, int $id)
    {
        $datos = $request->validate([
            'tipo_origen' => ['required', 'in:cobro,caja,ajuste,cheque'],
            'id_origen' => ['required', 'integer'],
            'id_periodo_redirect' => ['required', 'integer'],
        ]);

        $mapaCampos = [
            'cobro' => 'id_cobro',
            'caja' => 'id_movimiento_caja',
            'ajuste' => 'id_ajuste',
            'cheque' => 'id_cheque',
        ];

        $payload = [
            $mapaCampos[$datos['tipo_origen']] => $datos['id_origen'],
            'id_usuario' => G2Identity::id(),
        ];

        $resultado = $this->service->conciliarManual($id, $payload);

        if ($resultado === null) {
            abort(404, 'Movimiento bancario no encontrado');
        }

        if ($resultado['error'] ?? false) {
            return redirect()
                ->route('conciliacion.show', $datos['id_periodo_redirect'])
                ->with('error', $resultado['mensaje']);
        }

        return redirect()
            ->route('conciliacion.show', $datos['id_periodo_redirect'])
            ->with('mensaje', 'Movimiento conciliado manualmente.');
    }

    public function cerrar(int $id)
    {
        $resultado = $this->service->cerrarPeriodo($id);

        if ($resultado === null) {
            abort(404, 'Periodo de conciliacion no encontrado');
        }

        if ($resultado['error'] ?? false) {
            return redirect()
                ->route('conciliacion.show', $id)
                ->with('error', $resultado['mensaje']);
        }

        return redirect()
            ->route('conciliacion.show', $id)
            ->with('mensaje', 'Periodo cerrado correctamente.');
    }
}
