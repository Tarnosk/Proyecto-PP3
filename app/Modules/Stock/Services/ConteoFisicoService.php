<?php
declare(strict_types=1);

namespace App\Modules\Stock\Services;

use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Models\DetalleConteo;
use App\Modules\Stock\Models\SesionConteo;
use App\Modules\Stock\Repositories\ConteoFisicoRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConteoFisicoService
{
    public function __construct(
        private ?ConteoFisicoRepository $repository = null,
        private ?StockService $stockService = null
    ) {
        $this->repository = $repository ?? new ConteoFisicoRepository();
        $this->stockService = $stockService ?? new StockService();
    }

    public function iniciarSesion(array $datos, int $idUsuario): SesionConteo
    {
        $fechaConteo = $datos['fecha_conteo'] ?? Carbon::now()->toDateString();

        $sesion = SesionConteo::create([
            'estado' => 'en_proceso',
            'fecha_conteo' => $fechaConteo,
            'observaciones' => $datos['observaciones'] ?? null,
            'total_productos' => 0,
            'total_diferencias' => 0,
            'id_usuario' => $idUsuario,
        ]);

        return $sesion->fresh();
    }

    public function registrarItem(int $idSesion, int $idProducto, int $cantidadFisica, ?string $observaciones = null): DetalleConteo
    {
        $sesion = SesionConteo::find($idSesion);

        if (!$sesion) {
            throw new RuntimeException('La sesión de conteo no existe.');
        }

        if ($sesion->estado !== 'en_proceso') {
            throw new RuntimeException('No se puede modificar una sesión de conteo finalizada.');
        }

        $producto = Producto::find($idProducto);
        if (!$producto || $producto->estado !== 'activo') {
            throw new RuntimeException('El producto seleccionado no existe o no está activo.');
        }

        $stockSistema = (int) $producto->stock_disponible;
        $diferencia = $cantidadFisica - $stockSistema;

        $detalle = DetalleConteo::updateOrCreate(
            [
                'id_sesion' => $idSesion,
                'id_producto' => $idProducto,
            ],
            [
                'cantidad_fisica' => $cantidadFisica,
                'stock_sistema' => $stockSistema,
                'diferencia' => $diferencia,
                'observaciones' => $observaciones,
            ]
        );

        $this->actualizarTotalesSesion($idSesion);

        return $detalle->fresh(['producto']);
    }

    public function obtenerResumen(int $idSesion): array
    {
        return $this->repository->resumenPorSesion($idSesion);
    }

    public function finalizarSesion(int $idSesion, bool $aplicarAjustes, int $idUsuario): SesionConteo
    {
        return DB::transaction(function () use ($idSesion, $aplicarAjustes, $idUsuario) {
            $sesion = SesionConteo::with('detalles')->findOrFail($idSesion);
            if ($sesion->estado === 'finalizado') {
                return $sesion;
            }

            if ($aplicarAjustes) {
                foreach ($sesion->detalles as $detalle) {
                    if ((int) $detalle->diferencia === 0) {
                        continue;
                    }

                    $producto = Producto::find($detalle->id_producto);
                    if (!$producto || $producto->estado !== 'activo') {
                        continue;
                    }

                    $motivo = sprintf(
                        'Regularización por Conteo Físico #%d: dif %s u.',
                        $idSesion,
                        $detalle->diferencia > 0 ? '+' . $detalle->diferencia : (string) $detalle->diferencia
                    );

                    $this->stockService->registrarMovimiento(
                        $producto,
                        'ajuste',
                        abs((int) $detalle->diferencia),
                        $motivo,
                        $idUsuario,
                        null,
                        null,
                        null,
                        Carbon::now(),
                        (int) $detalle->diferencia > 0,
                        null,
                        $idSesion
                    );
                }
            }

            $sesion->estado = 'finalizado';
            $sesion->save();

            return $sesion->fresh(['detalles.producto', 'usuario']);
        });
    }

    public function eliminarItem(int $idSesion, int $idProducto): void
    {
        $detalle = DetalleConteo::where('id_sesion', $idSesion)->where('id_producto', $idProducto)->first();

        if (!$detalle) {
            throw new RuntimeException('El ítem no está registrado en la sesión de conteo.');
        }

        $detalle->delete();
        $this->actualizarTotalesSesion($idSesion);
    }

    private function actualizarTotalesSesion(int $idSesion): void
    {
        $sesion = SesionConteo::findOrFail($idSesion);
        $detalles = DetalleConteo::where('id_sesion', $idSesion)->get();

        $sesion->total_productos = $detalles->count();
        $sesion->total_diferencias = $detalles->filter(fn ($detalle) => (int) $detalle->diferencia !== 0)->count();
        $sesion->save();
    }
}
