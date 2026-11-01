<?php

declare(strict_types=1);

use App\Http\Middleware\IdentidadUsuario;
use App\Modules\Productos\Controllers\ProductoController;
use App\Modules\Stock\Controllers\DevolucionController;
use App\Modules\Stock\Controllers\StockController;
use App\Modules\Stock\Controllers\VentaStockController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Integración Externa Grupo 3 (para Grupos 1, 2 y 4)
|--------------------------------------------------------------------------
| Expone únicamente los endpoints que los demás grupos necesitan consumir:
| - Grupo 1 (Catálogo Público y Reportes): consulta de productos.
| - Grupo 4 (Ventas y Promociones): catálogo de productos, consulta de
|   disponibilidad de stock y descuento/reposición de stock por ventas.
| - Grupo 2 (Entregas y Rendiciones): consulta de productos/bultos y
|   reingreso de stock por devoluciones en reparto.
|
| Las operaciones propias de los módulos internos de G3 (ABM de productos,
| proveedores, compras, órdenes de compra, conteos y reposición) modifican
| directamente la base de datos desde las rutas internas en routes/web.php.
*/

$registrarApisIntegracionG3 = function (): void {
    Route::get('/', function (Request $request) {
        $payload = [
            'status' => 'success',
            'message' => 'APIs de integración del Grupo 3 para los Grupos 1, 2 y 4.',
            'interfaz' => url('/dashboard'),
            'endpoints' => [
                'GET /api/productos',
                'GET /api/productos/{id}',
                'GET /api/productos/codigo/{codigo}',
                'GET /api/stock',
                'GET /api/stock/{id}',
                'POST /api/stock/movimientos',
                'POST /api/stock/ventas',
                'POST /api/stock/devoluciones',
            ],
        ];

        if ($request->expectsJson() || str_contains((string) $request->header('Accept'), 'application/json')) {
            return response()->json($payload);
        }

        return redirect('/dashboard');
    });

    // ========== PRODUCTOS (Consumo de G1 Catálogo/Reportes y G4 Ventas/Promociones) ==========
    Route::get('productos/codigo/{codigo}', [ProductoController::class, 'showByCodigo']);
    Route::get('productos', [ProductoController::class, 'index']);
    Route::get('productos/{id}', [ProductoController::class, 'show'])->whereNumber('id');

    // ========== STOCK (Consumo de G4 Ventas HttpStockApi y G2 Rendiciones/Reparto) ==========
    Route::get('stock', [StockController::class, 'consultaGeneral']);
    Route::get('stock/{id}', [StockController::class, 'show'])->whereNumber('id');
    Route::get('stock/productos/{id}', [StockController::class, 'show'])->whereNumber('id');
    Route::post('stock/movimientos', [StockController::class, 'registrarMovimientosLote']);
    Route::post('stock/ventas', [VentaStockController::class, 'store']);
    Route::post('stock/descuentos', [VentaStockController::class, 'store']);
    Route::patch('stock/{id}/descontar', function (Request $request, int $id, VentaStockController $controller) {
        $request->merge(['id_producto' => $id]);

        return $controller->store($request);
    })->whereNumber('id');
    Route::post('stock/devoluciones', [DevolucionController::class, 'store']);
};

$g3ApiMiddleware = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    IdentidadUsuario::class,
];

Route::middleware($g3ApiMiddleware)->group(function () use ($registrarApisIntegracionG3): void {
    Route::prefix('v1')->name('api.v1.')->group($registrarApisIntegracionG3);
    Route::name('api.')->group($registrarApisIntegracionG3);
});
