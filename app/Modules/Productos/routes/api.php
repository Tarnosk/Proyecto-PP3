<?php

declare(strict_types=1);

use App\Http\Middleware\IdentidadUsuario;
use App\Modules\Compras\Controllers\CompraController;
use App\Modules\Compras\Controllers\PagoCompraController;
use App\Modules\Compras\Controllers\RecepcionController;
use App\Modules\PedidosDeCompra\Controllers\DashboardReposicionController;
use App\Modules\PedidosDeCompra\Controllers\OrdenCompraController;
use App\Modules\PedidosDeCompra\Controllers\SugerenciasReposicionController;
use App\Modules\Productos\Controllers\CategoriaController;
use App\Modules\Productos\Controllers\MarcaController;
use App\Modules\Productos\Controllers\ProductoController;
use App\Modules\Proveedores\Controllers\HistorialPrecioController;
use App\Modules\Proveedores\Controllers\PlazoEntregaController;
use App\Modules\Proveedores\Controllers\ProveedorController;
use App\Modules\Stock\Controllers\AjusteStockController;
use App\Modules\Stock\Controllers\AlertaStockController;
use App\Modules\Stock\Controllers\ConsultaVencimientosController;
use App\Modules\Stock\Controllers\ConteoFisicoController;
use App\Modules\Stock\Controllers\DevolucionController;
use App\Modules\Stock\Controllers\IngresoMercaderiaController;
use App\Modules\Stock\Controllers\LoteController;
use App\Modules\Stock\Controllers\StockController;
use App\Modules\Stock\Controllers\UbicacionController;
use App\Modules\Stock\Controllers\UnidadController;
use App\Modules\Stock\Controllers\VentaStockController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

$registrarRutasG3 = function (): void {
    Route::get('/', function (Request $request) {
        $payload = [
            'status' => 'success',
            'message' => 'API REST del ERP Grupo 3. La interfaz web está en /dashboard',
            'interfaz' => url('/dashboard'),
            'endpoints' => [
                'GET /api/categorias',
                'GET /api/marcas',
                'GET /api/productos',
                'GET /api/productos/{id}',
                'GET /api/productos/{id}/historial',
                'POST /api/productos/aumento-masivo',
                'GET /api/stock',
                'GET /api/stock/{id}/disponibilidad',
                'POST /api/stock/ingresos',
                'POST /api/stock/ventas',
                'POST /api/stock/devoluciones',
                'POST /api/stock/ajustes',
                'GET /api/stock/alertas',
                'GET /api/stock/{id}/historial-movimientos',
                'GET /api/proveedores',
                'GET /api/proveedores/{id}',
                'POST /api/proveedores',
                'PATCH /api/proveedores/{id}',
                'PATCH /api/proveedores/{id}/desactivar',
                'PATCH /api/proveedores/{id}/activar',
                'POST /api/proveedores/{id}/productos',
                'POST /api/proveedores/productos/{id}/principal',
                'DELETE /api/proveedores/productos/{id}',
                'POST /api/proveedores/{id}/plazo',
                'GET /api/proveedores/{id}/plazos',
                'GET /api/compras',
                'GET /api/compras/{id}',
                'GET /api/compras/{id}/estado',
                'POST /api/compras',
                'PATCH /api/compras/{id}',
                'PATCH /api/compras/{id}/cancelar',
                'POST /api/compras/{id}/recepciones',
                'POST /api/compras/{id}/pagos',
                'GET /api/compras/{id}/pagos',
                'GET /api/compras/{id}/pagos/resumen',
                'GET /api/ordenes-compra',
                'GET /api/ordenes-compra/{id}',
                'POST /api/ordenes-compra',
                'PATCH /api/ordenes-compra/{id}',
                'PATCH /api/ordenes-compra/{id}/cancelar',
                'PATCH /api/ordenes-compra/{id}/enviar',
            ],
        ];

        if ($request->expectsJson() || str_contains((string) $request->header('Accept'), 'application/json')) {
            return response()->json($payload);
        }

        return redirect('/dashboard');
    });

    // Categorías (P02)
    Route::apiResource('categorias', CategoriaController::class);

    // Marcas (P03)
    Route::apiResource('marcas', MarcaController::class);

    // ========== MÓDULO PROVEEDORES (PV01 a PV07) ==========
    Route::get('proveedores/historial', [ProveedorController::class, 'historialGlobal']);
    Route::get('proveedores/auditoria', [ProveedorController::class, 'historialGlobal']);
    Route::get('proveedores/{proveedor}/historial', [ProveedorController::class, 'historial']);
    Route::get('proveedores/{proveedor}/auditoria', [ProveedorController::class, 'historial']);
    Route::patch('proveedores/{proveedor}/desactivar', [ProveedorController::class, 'desactivar']);
    Route::patch('proveedores/{proveedor}/activar', [ProveedorController::class, 'activar']);
    Route::get('proveedores/{proveedor}/productos', [ProveedorController::class, 'listarProductos']);
    Route::post('proveedores/{proveedor}/productos', [ProveedorController::class, 'asociarProductos']);
    Route::get('proveedores/productos/{id}', [ProveedorController::class, 'obtenerAsociacion']);
    Route::put('proveedores/productos/{id}', [ProveedorController::class, 'actualizarAsociacion']);
    Route::patch('proveedores/productos/{id}', [ProveedorController::class, 'actualizarAsociacion']);
    Route::post('proveedores/productos/{id}/principal', [ProveedorController::class, 'definirPrincipal']);
    Route::delete('proveedores/productos/{id}', [ProveedorController::class, 'desasociarProducto']);
    Route::post('proveedores/sincronizar-asociaciones', [ProveedorController::class, 'sincronizarAsociaciones']);
    Route::post('proveedores/{proveedor}/plazo', [PlazoEntregaController::class, 'store']);
    Route::get('proveedores/{proveedor}/plazos', [PlazoEntregaController::class, 'index']);
    Route::get('proveedores/{id}/precios-historicos', [HistorialPrecioController::class, 'index']);
    Route::get('proveedores/{id}/productos/{idProducto}/precios-historicos', [HistorialPrecioController::class, 'porProducto']);
    Route::get('proveedores/{id}/comparativa-aumentos', [HistorialPrecioController::class, 'comparativaAumentos']);
    Route::post('proveedores/{id}/productos/{idProducto}/precio', [HistorialPrecioController::class, 'actualizarPrecio']);
    Route::apiResource('proveedores', ProveedorController::class)->except(['destroy']);

    // ========== MÓDULO COMPRAS (C01 a C08) ==========
    Route::patch('compras/{compra}/cancelar', [CompraController::class, 'cancelar']);
    Route::post('compras/{compra}/recepciones', [RecepcionController::class, 'store']);
    Route::get('compras/{compra}/estado', [CompraController::class, 'estado']);
    Route::post('compras/{compra}/pagos', [PagoCompraController::class, 'store']);
    Route::get('compras/{compra}/pagos', [PagoCompraController::class, 'index']);
    Route::get('compras/{compra}/pagos/resumen', [PagoCompraController::class, 'resumen']);
    Route::get('compras/deudas', [CompraController::class, 'deudas']);
    Route::get('proveedores/deudas', [CompraController::class, 'deudas']);
    Route::apiResource('compras', CompraController::class)->except(['destroy']);

    // ========== MÓDULO ÓRDENES DE COMPRA (PC01 a PC06) ==========
    Route::patch('ordenes-compra/{orden}/cancelar', [OrdenCompraController::class, 'cancelar']);
    Route::patch('ordenes-compra/{orden}/enviar', [OrdenCompraController::class, 'enviar']);
    Route::patch('ordenes-compra/{orden}/cambiar-estado', [OrdenCompraController::class, 'cambiarEstado']);
    Route::apiResource('ordenes-compra', OrdenCompraController::class)->except(['destroy']);
    Route::apiResource('pedidos-compra', OrdenCompraController::class)->except(['destroy']);

    // Productos (P01, P04, P05, P06, P07, P08)
    Route::get('productos/historial', [ProductoController::class, 'historialPreciosGlobal']);
    Route::get('productos/{id}/historial', [ProductoController::class, 'historialPrecios']);
    Route::get('productos/{id}/historial-precios', [ProductoController::class, 'historialPrecios']);
    Route::get('productos/auditoria', [ProductoController::class, 'auditoriaGlobal']);
    Route::get('productos/{id}/auditoria', [ProductoController::class, 'auditoria']);
    Route::post('productos/aumento-masivo', [ProductoController::class, 'aumentoMasivo']);
    Route::patch('productos/{id}/desactivar', [ProductoController::class, 'desactivar']);
    Route::patch('productos/{id}/activar', [ProductoController::class, 'activar']);
    Route::apiResource('productos', ProductoController::class);

    // ========== MÓDULO STOCK (S01 a S08) ==========
    Route::get('stock', [StockController::class, 'consultaGeneral']);
    Route::get('stock/{id}/disponibilidad', [StockController::class, 'disponibilidadParaVenta']);
    Route::get('stock/{id}', [StockController::class, 'disponibilidadParaVenta'])->whereNumber('id');
    Route::get('stock/productos/{id}', [StockController::class, 'disponibilidadParaVenta'])->whereNumber('id');
    Route::get('stock/movimientos', [StockController::class, 'todosLosMovimientos']);
    Route::get('stock/{id}/historial-movimientos', [StockController::class, 'historial']);
    Route::post('stock/ingresos', [IngresoMercaderiaController::class, 'store']);
    Route::post('stock/ventas', [VentaStockController::class, 'store']);
    Route::post('stock/descuentos', [VentaStockController::class, 'store']);
    Route::patch('stock/{id}/descontar', function (Request $request, int $id, VentaStockController $controller) {
        $request->merge(['id_producto' => $id]);

        return $controller->store($request);
    })->whereNumber('id');
    Route::post('stock/devoluciones', [DevolucionController::class, 'store']);
    Route::post('stock/ajustes', [AjusteStockController::class, 'store']);

    Route::prefix('stock/conteos')->group(function () {
        Route::post('sesiones', [ConteoFisicoController::class, 'store']);
        Route::get('sesiones', [ConteoFisicoController::class, 'index']);
        Route::get('sesiones/{id}', [ConteoFisicoController::class, 'show'])->whereNumber('id');
        Route::post('sesiones/{id}/items', [ConteoFisicoController::class, 'registrarItem'])->whereNumber('id');
        Route::delete('sesiones/{id}/items/{idProducto}', [ConteoFisicoController::class, 'eliminarItem'])
            ->whereNumber('id')->whereNumber('idProducto');
        Route::post('sesiones/{id}/finalizar', [ConteoFisicoController::class, 'finalizar'])->whereNumber('id');
    });

    Route::get('stock/alertas', [AlertaStockController::class, 'index']);
    Route::get('stock/bajo', [AlertaStockController::class, 'index']);
    Route::patch('stock/alertas/{id}/minimo', [AlertaStockController::class, 'configurarMinimo']);
    Route::post('stock/alertas/recalcular', [AlertaStockController::class, 'recalcular']);

    Route::get('stock/lotes', [LoteController::class, 'index']);
    Route::post('stock/lotes', [LoteController::class, 'store']);
    Route::get('stock/lotes/{id}', [LoteController::class, 'show']);
    Route::get('stock/productos/{id}/lotes', [LoteController::class, 'lotesPorProducto']);
    Route::get('stock/lotes-por-vencer', [LoteController::class, 'porVencer']);

    Route::get('unidades/convertir', [UnidadController::class, 'convertir']);
    Route::post('productos/{id}/unidades/{idUnidad}/seleccionar', [UnidadController::class, 'seleccionar']);
    Route::apiResource('productos/{id}/unidades', UnidadController::class);

    Route::get('ubicaciones', [UbicacionController::class, 'index']);
    Route::post('ubicaciones', [UbicacionController::class, 'store']);
    Route::put('ubicaciones/{id}', [UbicacionController::class, 'update']);
    Route::delete('ubicaciones/{id}', [UbicacionController::class, 'destroy']);
    Route::post('productos/{id}/ubicacion', [UbicacionController::class, 'asignarProducto']);
    Route::delete('productos/{id}/ubicacion', [UbicacionController::class, 'desasignarProducto']);

    Route::prefix('vencimientos')->group(function () {
        Route::get('proximos', [ConsultaVencimientosController::class, 'proximosAVencer']);
        Route::get('por-criticidad', [ConsultaVencimientosController::class, 'porCriticidad']);
        Route::get('alertas', [ConsultaVencimientosController::class, 'alertas']);
        Route::get('reporte', [ConsultaVencimientosController::class, 'reporte']);
    });

    Route::prefix('sugerencias-reposicion')->group(function () {
        Route::get('/', [SugerenciasReposicionController::class, 'listar']);
        Route::post('/generar', [SugerenciasReposicionController::class, 'generar']);
        Route::get('/resumen', [SugerenciasReposicionController::class, 'resumen']);
        Route::post('/procesar-lote', [SugerenciasReposicionController::class, 'procesarLote']);
        Route::post('/{id}/procesar', [SugerenciasReposicionController::class, 'procesar'])->whereNumber('id');
        Route::post('/{id}/rechazar', [SugerenciasReposicionController::class, 'rechazar'])->whereNumber('id');
        Route::post('/{id}/generar-orden', [SugerenciasReposicionController::class, 'generarOrden'])->whereNumber('id');
    });

    Route::prefix('dashboard-reposicion')->group(function () {
        Route::get('/resumen', [DashboardReposicionController::class, 'resumen']);
        Route::get('/grafico-motivos', [DashboardReposicionController::class, 'graficoMotivos']);
        Route::get('/grafico-tendencia', [DashboardReposicionController::class, 'graficoTendencia']);
        Route::get('/grafico-criticidad', [DashboardReposicionController::class, 'graficoCriticidad']);
    });
};

$g3ApiMiddleware = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    IdentidadUsuario::class,
];

Route::middleware($g3ApiMiddleware)->group(function () use ($registrarRutasG3): void {
    Route::prefix('v1')->name('api.v1.')->group($registrarRutasG3);
    Route::name('api.')->group($registrarRutasG3);
});
