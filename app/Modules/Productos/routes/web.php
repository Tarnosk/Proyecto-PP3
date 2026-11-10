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
use App\Modules\Stock\Controllers\ConteoFisicoController;
use App\Modules\Stock\Controllers\IngresoMercaderiaController;
use App\Modules\Stock\Controllers\StockController;
use App\Modules\Stock\Controllers\UbicacionController;
use App\Modules\Stock\Controllers\UnidadController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Módulos Grupo 3 (Productos, Stock, Proveedores, Compras)
|--------------------------------------------------------------------------
| 1. Vistas Blade del módulo.
| 2. Rutas operativas internas para la interfaz de G3 (modifican directamente
|    la base de datos a través de los controladores y servicios propios de G3,
|    sin exponerlas como APIs externas para otros grupos).
*/

Route::middleware(['auth', 'session.current', IdentidadUsuario::class])->group(function (): void {
    Route::middleware('role:administrativo,repartidor')->group(function (): void {
        // Panel G3
        Route::view('/panel-g3', 'dashboard-g3')->name('g3.dashboard');

        // Módulo Proveedores (G3)
        Route::view('/proveedores', 'proveedores.index')->name('proveedores.index');

        // Módulo Inventario y Stock (G3)
        Route::view('/inventario', 'inventario.index')->name('inventario.index');
        Route::redirect('/productos', '/inventario')->name('productos.index');

        // Módulo Compras y Órdenes (G3)
        Route::view('/compras', 'compras.index')->name('compras.index');
        Route::redirect('/pedidos-compra', '/compras')->name('pedidos-compra.index');
        Route::redirect('/pedido-compra', '/compras');
        Route::redirect('/pedido_compra', '/compras');
        Route::redirect('/pedidos_compra', '/compras');
        Route::redirect('/ordenes-compra', '/compras');
        Route::redirect('/ordenes_compra', '/compras');
        Route::redirect('/ordenes', '/compras');

        // Submódulos Reposición y Vencimientos (G3)
        Route::redirect('/vencimientos', '/inventario')->name('vencimientos.index');
        Route::view('/sugerencias-reposicion', 'sugerencias-reposicion')->name('sugerencias-reposicion.index');
        Route::view('/dashboard-reposicion', 'dashboard-reposicion')->name('dashboard-reposicion.index');
        Route::redirect('/reposicion', '/sugerencias-reposicion');
    });
});

// Endpoints internos de sesión para el frontend de G3 (acceso directo a BD propia de G3).
$registrarRutasInternasG3 = function (): void {
    // Categorías (P02) y Marcas (P03)
    Route::apiResource('categorias', CategoriaController::class);
    Route::apiResource('marcas', MarcaController::class);

    // Gestión interna de Productos, Precios y Auditoría (P01, P04, P06, P07, P08)
    Route::get('productos/historial', [ProductoController::class, 'historialPreciosGlobal']);
    Route::get('productos/{id}/historial', [ProductoController::class, 'historialPrecios'])->whereNumber('id');
    Route::get('productos/{id}/historial-precios', [ProductoController::class, 'historialPrecios'])->whereNumber('id');
    Route::get('productos/auditoria', [ProductoController::class, 'auditoriaGlobal']);
    Route::get('productos/{id}/auditoria', [ProductoController::class, 'auditoria'])->whereNumber('id');
    Route::post('productos/aumento-masivo', [ProductoController::class, 'aumentoMasivo']);
    Route::patch('productos/{id}/desactivar', [ProductoController::class, 'desactivar'])->whereNumber('id');
    Route::patch('productos/{id}/activar', [ProductoController::class, 'activar'])->whereNumber('id');
    Route::post('productos', [ProductoController::class, 'store']);
    Route::put('productos/{producto}', [ProductoController::class, 'update'])->whereNumber('producto');
    Route::patch('productos/{producto}', [ProductoController::class, 'update'])->whereNumber('producto');
    Route::delete('productos/{producto}', [ProductoController::class, 'destroy'])->whereNumber('producto');

    // Gestión interna de Stock, Kardex, Conteos, Alertas, Unidades y Ubicaciones
    Route::get('stock/movimientos', [StockController::class, 'todosLosMovimientos']);
    Route::get('stock/{id}/historial-movimientos', [StockController::class, 'historial'])->whereNumber('id');
    Route::post('stock/ingresos', [IngresoMercaderiaController::class, 'store']);
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
    Route::patch('stock/alertas/{id}/minimo', [AlertaStockController::class, 'configurarMinimo'])->whereNumber('id');
    Route::post('stock/alertas/recalcular', [AlertaStockController::class, 'recalcular']);

    Route::get('unidades/convertir', [UnidadController::class, 'convertir']);
    Route::post('productos/{id}/unidades/{idUnidad}/seleccionar', [UnidadController::class, 'seleccionar'])
        ->whereNumber('id')->whereNumber('idUnidad');
    Route::apiResource('productos/{id}/unidades', UnidadController::class);

    Route::get('ubicaciones', [UbicacionController::class, 'index']);
    Route::post('ubicaciones', [UbicacionController::class, 'store']);
    Route::put('ubicaciones/{id}', [UbicacionController::class, 'update'])->whereNumber('id');
    Route::delete('ubicaciones/{id}', [UbicacionController::class, 'destroy'])->whereNumber('id');
    Route::post('productos/{id}/ubicacion', [UbicacionController::class, 'asignarProducto'])->whereNumber('id');
    Route::delete('productos/{id}/ubicacion', [UbicacionController::class, 'desasignarProducto'])->whereNumber('id');

    // Módulo Proveedores (PV01 a PV07)
    Route::get('proveedores/historial', [ProveedorController::class, 'historialGlobal']);
    Route::get('proveedores/auditoria', [ProveedorController::class, 'historialGlobal']);
    Route::get('proveedores/{proveedor}/historial', [ProveedorController::class, 'historial'])->whereNumber('proveedor');
    Route::get('proveedores/{proveedor}/auditoria', [ProveedorController::class, 'historial'])->whereNumber('proveedor');
    Route::patch('proveedores/{proveedor}/desactivar', [ProveedorController::class, 'desactivar'])->whereNumber('proveedor');
    Route::patch('proveedores/{proveedor}/activar', [ProveedorController::class, 'activar'])->whereNumber('proveedor');
    Route::get('proveedores/{proveedor}/productos', [ProveedorController::class, 'listarProductos'])->whereNumber('proveedor');
    Route::post('proveedores/{proveedor}/productos', [ProveedorController::class, 'asociarProductos'])->whereNumber('proveedor');
    Route::get('proveedores/productos/{id}', [ProveedorController::class, 'obtenerAsociacion'])->whereNumber('id');
    Route::put('proveedores/productos/{id}', [ProveedorController::class, 'actualizarAsociacion'])->whereNumber('id');
    Route::patch('proveedores/productos/{id}', [ProveedorController::class, 'actualizarAsociacion'])->whereNumber('id');
    Route::post('proveedores/productos/{id}/principal', [ProveedorController::class, 'definirPrincipal'])->whereNumber('id');
    Route::delete('proveedores/productos/{id}', [ProveedorController::class, 'desasociarProducto'])->whereNumber('id');
    Route::post('proveedores/sincronizar-asociaciones', [ProveedorController::class, 'sincronizarAsociaciones']);
    Route::post('proveedores/{proveedor}/plazo', [PlazoEntregaController::class, 'store'])->whereNumber('proveedor');
    Route::get('proveedores/{proveedor}/plazos', [PlazoEntregaController::class, 'index'])->whereNumber('proveedor');
    Route::get('proveedores/{id}/precios-historicos', [HistorialPrecioController::class, 'index'])->whereNumber('id');
    Route::get('proveedores/{id}/productos/{idProducto}/precios-historicos', [HistorialPrecioController::class, 'porProducto'])
        ->whereNumber('id')->whereNumber('idProducto');
    Route::get('proveedores/{id}/comparativa-aumentos', [HistorialPrecioController::class, 'comparativaAumentos'])->whereNumber('id');
    Route::post('proveedores/{id}/productos/{idProducto}/precio', [HistorialPrecioController::class, 'actualizarPrecio'])
        ->whereNumber('id')->whereNumber('idProducto');
    Route::apiResource('proveedores', ProveedorController::class)->except(['destroy']);

    // Módulo Compras, Recepciones y Pagos a Proveedores (C01 a C09)
    Route::patch('compras/{compra}/cancelar', [CompraController::class, 'cancelar'])->whereNumber('compra');
    Route::patch('compras/{compra}/cerrar-parcial', [CompraController::class, 'cerrarParcial'])->whereNumber('compra');
    Route::post('compras/{compra}/recepciones', [RecepcionController::class, 'store'])->whereNumber('compra');
    Route::get('compras/{compra}/estado', [CompraController::class, 'estado'])->whereNumber('compra');
    Route::post('compras/{compra}/pagos', [PagoCompraController::class, 'store'])->whereNumber('compra');
    Route::get('compras/{compra}/pagos', [PagoCompraController::class, 'index'])->whereNumber('compra');
    Route::delete('compras/{compra}/pagos/{pago}', [PagoCompraController::class, 'destroy'])->whereNumber('compra')->whereNumber('pago');
    Route::get('compras/{compra}/pagos/resumen', [PagoCompraController::class, 'resumen'])->whereNumber('compra');
    Route::get('compras/deudas', [CompraController::class, 'deudas']);
    Route::get('proveedores/deudas', [CompraController::class, 'deudas']);
    Route::apiResource('compras', CompraController::class)->except(['destroy']);

    // Módulo Órdenes de Compra, Sugerencias y Dashboard de Reposición (PC01 a PC07)
    Route::patch('ordenes-compra/{orden}/cancelar', [OrdenCompraController::class, 'cancelar'])->whereNumber('orden');
    Route::patch('ordenes-compra/{orden}/enviar', [OrdenCompraController::class, 'enviar'])->whereNumber('orden');
    Route::patch('ordenes-compra/{orden}/cambiar-estado', [OrdenCompraController::class, 'cambiarEstado'])->whereNumber('orden');
    Route::apiResource('ordenes-compra', OrdenCompraController::class)->except(['destroy']);
    Route::apiResource('pedidos-compra', OrdenCompraController::class)->except(['destroy']);

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

Route::middleware([IdentidadUsuario::class])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->prefix('api')
    ->group(function () use ($registrarRutasInternasG3): void {
        Route::prefix('v1')->name('g3.internal.v1.')->group($registrarRutasInternasG3);
        Route::name('g3.internal.')->group($registrarRutasInternasG3);
    });
