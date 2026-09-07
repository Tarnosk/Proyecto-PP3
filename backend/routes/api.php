<?php
declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\Productos\Controllers\CategoriaController;
use App\Modules\Productos\Controllers\MarcaController;
use App\Modules\Productos\Controllers\ProductoController;
use App\Modules\Stock\Controllers\StockController;
use App\Modules\Stock\Controllers\IngresoMercaderiaController;
use App\Modules\Stock\Controllers\VentaStockController;
use App\Modules\Stock\Controllers\DevolucionController;
use App\Modules\Stock\Controllers\AjusteStockController;
use App\Modules\Stock\Controllers\AlertaStockController;
use App\Modules\Stock\Controllers\UnidadController;
use App\Modules\Proveedores\Controllers\ProveedorController;
use App\Modules\Compras\Controllers\CompraController;
use App\Modules\Compras\Controllers\RecepcionController;
use App\Modules\PedidosDeCompra\Controllers\OrdenCompraController;

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
            'GET /api/compras',
            'GET /api/compras/{id}',
            'POST /api/compras',
            'PATCH /api/compras/{id}',
            'PATCH /api/compras/{id}/cancelar',
            'POST /api/compras/{id}/recepciones',
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

// ── MÓDULO PROVEEDORES (PV01 a PV05) ───────────────────────────
// PV01/PV03 - Alta y edición (apiResource: index, store, show, update, destroy)
// PV04 - Desactivar/reactivar (borrado lógico)
Route::patch('proveedores/{proveedor}/desactivar', [ProveedorController::class, 'desactivar']);
Route::patch('proveedores/{proveedor}/activar', [ProveedorController::class, 'activar']);
// PV05 - Asociación de productos a proveedores
Route::post('proveedores/{proveedor}/productos', [ProveedorController::class, 'asociarProductos']);
Route::post('proveedores/productos/{id}/principal', [ProveedorController::class, 'definirPrincipal']);
Route::delete('proveedores/productos/{id}', [ProveedorController::class, 'desasociarProducto']);
Route::apiResource('proveedores', ProveedorController::class);

// ── MÓDULO COMPRAS (C01 a C06) ──────────────────────────────────
// C05 - Cancelar una compra pendiente
Route::patch('compras/{compra}/cancelar', [CompraController::class, 'cancelar']);
// C06 - Registrar recepciones parciales de una compra
Route::post('compras/{compra}/recepciones', [RecepcionController::class, 'store']);
// C01/C02/C03/C04 - Alta, listado, detalle y edición
Route::apiResource('compras', CompraController::class);

// ── MÓDULO ÓRDENES DE COMPRA (PC01 a PC06) ──────────────────────
// PC05 - Cancelar una orden pendiente
Route::patch('ordenes-compra/{orden}/cancelar', [OrdenCompraController::class, 'cancelar']);
// Complementaria: enviar una orden pendiente (habilita PC04/PC05/PC06)
Route::patch('ordenes-compra/{orden}/enviar', [OrdenCompraController::class, 'enviar']);
// PC01/PC02/PC03/PC04 - Generar, listar, detalle y editar
Route::apiResource('ordenes-compra', OrdenCompraController::class);

// Productos (P01, P04, P05, P06, P08)
Route::get('productos/{id}/historial', [ProductoController::class, 'historialPrecios']);
Route::patch('productos/{id}/desactivar', [ProductoController::class, 'desactivar']);
Route::patch('productos/{id}/activar', [ProductoController::class, 'activar']);
Route::apiResource('productos', ProductoController::class);

// ── MÓDULO STOCK (S01 a S08) ──────────────────────────────────

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

// S08 - Gestión de unidades y equivalencias
Route::get('unidades/convertir', [UnidadController::class, 'convertir']);
Route::apiResource('productos/{id}/unidades', UnidadController::class);
