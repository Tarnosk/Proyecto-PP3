<?php
declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\Productos\Controllers\CategoriaController;
use App\Modules\Productos\Controllers\MarcaController;
use App\Modules\Productos\Controllers\ProductoController;
use App\Modules\Stock\Controllers\StockController;
use App\Modules\Stock\Controllers\LoteController;
use App\Modules\Stock\Controllers\IngresoMercaderiaController;
use App\Modules\Stock\Controllers\VentaStockController;
use App\Modules\Stock\Controllers\DevolucionController;
use App\Modules\Stock\Controllers\AjusteStockController;
use App\Modules\Stock\Controllers\AlertaStockController;
use App\Modules\Stock\Controllers\UnidadController;
use App\Modules\Stock\Controllers\ConsultaVencimientosController;
use App\Modules\Proveedores\Controllers\ProveedorController;
use App\Modules\Proveedores\Controllers\PlazoEntregaController;
use App\Modules\Compras\Controllers\CompraController;
use App\Modules\Compras\Controllers\RecepcionController;
use App\Modules\Compras\Controllers\PagoCompraController;
use App\Modules\PedidosDeCompra\Controllers\OrdenCompraController;
use App\Modules\PedidosDeCompra\Controllers\SugerenciasReposicionController;
use App\Modules\PedidosDeCompra\Controllers\DashboardReposicionController;

Route::get('/', function (Request $request) {
    $payload = [
        'status' => 'success',
        'message' => 'API REST del ERP Grupo 3. La interfaz web no vive en /api; está en /Interfaz/index.html',
        'interfaz' => url('/Interfaz/index.html'),
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

    return redirect('/Interfaz/index.html');
});

// Categorías (P02)
Route::apiResource('categorias', CategoriaController::class);

// Marcas (P03)
Route::apiResource('marcas', MarcaController::class);

// ========== MÓDULO PROVEEDORES (PV01 a PV07) ==========
// PV01/PV03 - Alta y edición
// PV04 - Desactivar/reactivar (borrado lógico)
Route::patch('proveedores/{proveedor}/desactivar', [ProveedorController::class, 'desactivar']);
Route::patch('proveedores/{proveedor}/activar', [ProveedorController::class, 'activar']);
// PV05 - Asociación de productos a proveedores
Route::post('proveedores/{proveedor}/productos', [ProveedorController::class, 'asociarProductos']);
Route::post('proveedores/productos/{id}/principal', [ProveedorController::class, 'definirPrincipal']);
Route::delete('proveedores/productos/{id}', [ProveedorController::class, 'desasociarProducto']);
// PV07 - Registrar plazos de entrega y consultar su historial
Route::post('proveedores/{proveedor}/plazo', [PlazoEntregaController::class, 'store']);
Route::get('proveedores/{proveedor}/plazos', [PlazoEntregaController::class, 'index']);
// destroy queda fuera: el proveedor se desactiva (PV04), no se borra.
Route::apiResource('proveedores', ProveedorController::class)->except(['destroy']);

// ========== MÓDULO COMPRAS (C01 a C08) ==========
// C05 - Cancelar una compra pendiente
Route::patch('compras/{compra}/cancelar', [CompraController::class, 'cancelar']);
// C06 - Registrar recepciones parciales de una compra
Route::post('compras/{compra}/recepciones', [RecepcionController::class, 'store']);
// C07 - Visualizar el estado de una compra
Route::get('compras/{compra}/estado', [CompraController::class, 'estado']);
// C08 - Registrar múltiples métodos de pago
Route::post('compras/{compra}/pagos', [PagoCompraController::class, 'store']);
Route::get('compras/{compra}/pagos', [PagoCompraController::class, 'index']);
Route::get('compras/{compra}/pagos/resumen', [PagoCompraController::class, 'resumen']);
// destroy queda fuera: la compra se cancela (C05), no se borra.
Route::apiResource('compras', CompraController::class)->except(['destroy']);

// ========== MÓDULO ÓRDENES DE COMPRA (PC01 a PC06) ==========
// PC05 - Cancelar una orden pendiente
Route::patch('ordenes-compra/{orden}/cancelar', [OrdenCompraController::class, 'cancelar']);
// Complementaria: enviar una orden pendiente (habilita PC04/PC05/PC06)
Route::patch('ordenes-compra/{orden}/enviar', [OrdenCompraController::class, 'enviar']);
// destroy queda fuera: la orden se cancela (PC05), no se borra.
Route::apiResource('ordenes-compra', OrdenCompraController::class)->except(['destroy']);

// Productos (P01, P04, P05, P06, P07, P08)
Route::get('productos/{id}/historial', [ProductoController::class, 'historialPrecios']);
// P07 - Aplicar aumento porcentual masivo de precios
Route::post('productos/aumento-masivo', [ProductoController::class, 'aumentoMasivo']);
Route::patch('productos/{id}/desactivar', [ProductoController::class, 'desactivar']);
Route::patch('productos/{id}/activar', [ProductoController::class, 'activar']);
Route::apiResource('productos', ProductoController::class);

// ========== MÓDULO STOCK (S01 a S08) ==========

// S01 - Consulta de Stock Disponible
Route::get('stock', [StockController::class, 'consultaGeneral']);

// S02 - Disponibilidad para Venta (por producto)
Route::get('stock/{id}/disponibilidad', [StockController::class, 'disponibilidadParaVenta']);

// S12 - Historial de movimientos de stock de un producto
Route::get('stock/{id}/historial-movimientos', [StockController::class, 'historial']);

// S03 - Registro de Ingreso de Mercadería
Route::post('stock/ingresos', [IngresoMercaderiaController::class, 'store']);

// S04 - Actualización automática por venta (endpoint interno simple)
Route::post('stock/ventas', [VentaStockController::class, 'store']);

// S05 - Registro de Devoluciones
Route::post('stock/devoluciones', [DevolucionController::class, 'store']);

// S06 - Ajustes manuales de inventario
Route::post('stock/ajustes', [AjusteStockController::class, 'store']);

// S07 - Alertas de stock mínimo
Route::get('stock/alertas', [AlertaStockController::class, 'index']);
Route::patch('stock/alertas/{id}/minimo', [AlertaStockController::class, 'configurarMinimo']);
Route::post('stock/alertas/recalcular', [AlertaStockController::class, 'recalcular']);

// S09/S10 - Lotes y vencimientos (reemplaza la ruta muerta de StockController)
Route::get('stock/lotes',                [LoteController::class, 'index']);
Route::post('stock/lotes',               [LoteController::class, 'store']);
Route::get('stock/lotes/{id}',           [LoteController::class, 'show']);
Route::get('stock/productos/{id}/lotes', [LoteController::class, 'lotesPorProducto']);
Route::get('stock/lotes-por-vencer',     [LoteController::class, 'porVencer']);

// S08 - Gestión de unidades y equivalencias
Route::get('unidades/convertir', [UnidadController::class, 'convertir']);
Route::apiResource('productos/{id}/unidades', UnidadController::class);

// ─── S11: CONSULTA DE VENCIMIENTOS (sobre la tabla LOTE) ────────────────────────
Route::prefix('vencimientos')->group(function () {
    Route::get('proximos',       [ConsultaVencimientosController::class, 'proximosAVencer']);
    Route::get('por-criticidad', [ConsultaVencimientosController::class, 'porCriticidad']);
    Route::get('alertas',        [ConsultaVencimientosController::class, 'alertas']);
    Route::get('reporte',        [ConsultaVencimientosController::class, 'reporte']);
});

// ─── PC07: SUGERENCIAS DE REPOSICIÓN ───────────────────────────────────────────
Route::prefix('sugerencias-reposicion')->group(function () {
    Route::get('/',                [SugerenciasReposicionController::class, 'listar']);
    Route::post('/generar',        [SugerenciasReposicionController::class, 'generar']);
    Route::get('/resumen',         [SugerenciasReposicionController::class, 'resumen']);
    Route::post('/procesar-lote',  [SugerenciasReposicionController::class, 'procesarLote']);
Route::post('/{id}/procesar',  [SugerenciasReposicionController::class, 'procesar'])->whereNumber('id');
            Route::post('/{id}/rechazar',  [SugerenciasReposicionController::class, 'rechazar'])->whereNumber('id');
            Route::post('/{id}/generar-orden', [SugerenciasReposicionController::class, 'generarOrden'])->whereNumber('id');
});

Route::prefix('dashboard-reposicion')->group(function () {
    Route::get('/resumen',           [DashboardReposicionController::class, 'resumen']);
    Route::get('/grafico-motivos',   [DashboardReposicionController::class, 'graficoMotivos']);
    Route::get('/grafico-tendencia', [DashboardReposicionController::class, 'graficoTendencia']);
    Route::get('/grafico-criticidad',[DashboardReposicionController::class, 'graficoCriticidad']);
});