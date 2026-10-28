# Matriz de Trazabilidad de Requisitos (RTM) — ERP Juan23 (Grupo 3)

Esta matriz vincula cada Historia de Usuario (HU) con su endpoint en la API REST, el controlador y servicio responsable, el test automatizado que valida sus criterios de aceptación y la vista Blade donde se materializa en la interfaz.

---

## 1. Módulo Productos (P01 – P08)

| ID | Historia de Usuario | Endpoint(s) API | Controlador / Servicio | Test Automatizado | Vista / UI |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **P01** | Alta de Producto | `POST /api/v1/productos` | `ProductoController@store` | `UserStoriesFrontendTest@test_p01_creacion_producto` | `inventario/index.blade.php` |
| **P02** | Modificación de Producto | `PUT /api/v1/productos/{id}` | `ProductoController@update` | `UserStoriesFrontendTest@test_p02_edicion_producto` | `inventario/index.blade.php` |
| **P03** | Desactivación de Producto | `PATCH /api/v1/productos/{id}/desactivar` | `ProductoController@desactivar` | `UserStoriesFrontendTest@test_p03_desactivacion_producto` | `inventario/index.blade.php` |
| **P04** | Reactivación de Producto | `PATCH /api/v1/productos/{id}/activar` | `ProductoController@activar` | `UserStoriesFrontendTest@test_p04_reactivacion_producto` | `inventario/index.blade.php` |
| **P05** | Búsqueda y Filtros de Productos | `GET /api/v1/productos?search=&categoria_id=` | `ProductoController@index` | `UserStoriesFrontendTest@test_p05_busqueda_filtros` | `inventario/index.blade.php` |
| **P06** | Detalle de Producto y Stock | `GET /api/v1/productos/{id}` | `ProductoController@show` | `UserStoriesFrontendTest@test_p06_detalle_producto` | `inventario/index.blade.php` |
| **P07** | Aumento Masivo de Precios | `POST /api/v1/productos/aumento-masivo` | `ProductoController@aumentoMasivo` | `UserStoriesFrontendTest@test_p07_aumento_masivo_precios` | `inventario/index.blade.php` |
| **P08** | Historial de Cambios de Precio | `GET /api/v1/productos/{id}/historial` | `ProductoController@historialPrecios` | `UserStoriesFrontendTest@test_p08_historial_precios` | `inventario/index.blade.php` |

---

## 2. Módulo Stock e Inventario (S01 – S16)

| ID | Historia de Usuario | Endpoint(s) API | Controlador / Servicio | Test Automatizado | Vista / UI |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **S01** | Visualización de Stock General | `GET /api/v1/stock` | `StockController@index` | `UserStoriesFrontendTest@test_s01_consulta_stock` | `inventario/index.blade.php` |
| **S02** | Disponibilidad en Tiempo Real | `GET /api/v1/stock/{id}/disponibilidad` | `StockController@disponibilidad` | `UserStoriesFrontendTest@test_s02_disponibilidad` | `inventario/index.blade.php` |
| **S03** | Ingreso Manual de Mercadería | `POST /api/v1/stock/ingresos` | `IngresoMercaderiaController@store` | `UserStoriesFrontendTest@test_s03_ingreso_mercaderia` | `inventario/index.blade.php` |
| **S04** | Salida de Stock por Venta | `POST /api/v1/stock/ventas` | `VentaStockController@store` | `UserStoriesFrontendTest@test_s04_descuento_venta` | `inventario/index.blade.php` |
| **S05** | Registro de Devoluciones | `POST /api/v1/stock/devoluciones` | `DevolucionController@store` | `UserStoriesFrontendTest@test_s05_registro_devoluciones` | `inventario/index.blade.php` |
| **S06** | Ajustes Manuales de Inventario | `POST /api/v1/stock/ajustes` | `AjusteStockController@store` | `UserStoriesFrontendTest@test_s06_ajustes_stock` | `inventario/index.blade.php` |
| **S07** | Alertas de Stock Mínimo | `GET /api/v1/stock/alertas` | `AlertaStockController@index` | `UserStoriesFrontendTest@test_s07_alertas_stock` | `inventario/index.blade.php` |
| **S08** | Unidades y Equivalencias | `GET /api/v1/unidades/convertir` | `UnidadController@convertir` | `UserStoriesFrontendTest@test_s08_unidades_equivalencias` | `inventario/index.blade.php` |
| **S09** | Gestión y Registro de Lotes | `GET /api/v1/stock/lotes`<br>`POST /api/v1/stock/lotes` | `LoteController@store` | `UserStoriesFrontendTest@test_s09_gestion_lotes` | `inventario/index.blade.php` |
| **S10** | Semáforo de Vencimientos | `GET /api/v1/stock/lotes-por-vencer` | `LoteController@porVencer` | `UserStoriesFrontendTest@test_s10_alertas_vencimiento` | `vencimientos.blade.php` |
| **S11** | Consulta Analítica de Vencimientos | `GET /api/v1/vencimientos/proximos` | `ConsultaVencimientosController` | `UserStoriesFrontendTest@test_s11_consulta_vencimientos` | `vencimientos.blade.php` |
| **S12** | Historial Kardex de Movimientos | `GET /api/v1/stock/{id}/historial-movimientos` | `StockController@historial` | `UserStoriesFrontendTest@test_s12_historial_kardex` | `inventario/index.blade.php` |
| **S13** | Conteo Físico a Ciegas | `POST /api/v1/sesiones`<br>`POST /api/v1/sesiones/{id}/finalizar` | `ConteoFisicoController`<br>`ConteoFisicoService` | `ConteoFisicoTest@test_flujo_completo_conteo_fisico` | `inventario/index.blade.php` |
| **S14** | Ubicaciones en Almacén | `GET /api/v1/ubicaciones`<br>`POST /api/v1/productos/{id}/ubicacion` | `UbicacionController` | `UserStoriesFrontendTest@test_s14_gestion_ubicaciones` | `inventario/index.blade.php` |
| **S15** | Filtros Avanzados por Rango | `GET /api/v1/stock?stock_min=&stock_max=` | `StockController@index` | `UserStoriesFrontendTest@test_s15_filtros_rango_stock` | `inventario/index.blade.php` |
| **S16** | Venta en Bulto y Subunidades | `POST /api/v1/productos` (campos bulto) | `ProductoController`<br>`StockService` | `UserStoriesFrontendTest@test_s16_formato_bulto` | `inventario/index.blade.php` |

---

## 3. Módulo Proveedores (PV01 – PV08)

| ID | Historia de Usuario | Endpoint(s) API | Controlador / Servicio | Test Automatizado | Vista / UI |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **PV01** | Alta de Proveedor (CUIT validado) | `POST /api/v1/proveedores` | `ProveedorController@store` | `UserStoriesFrontendTest@test_pv01_alta_proveedor` | `proveedores/index.blade.php` |
| **PV02** | Edición de Proveedor | `PATCH /api/v1/proveedores/{id}` | `ProveedorController@update` | `UserStoriesFrontendTest@test_pv02_edicion_proveedor` | `proveedores/index.blade.php` |
| **PV03** | Desactivación de Proveedor | `PATCH /api/v1/proveedores/{id}/desactivar` | `ProveedorController@desactivar` | `UserStoriesFrontendTest@test_pv03_desactivacion` | `proveedores/index.blade.php` |
| **PV04** | Reactivación de Proveedor | `PATCH /api/v1/proveedores/{id}/activar` | `ProveedorController@activar` | `UserStoriesFrontendTest@test_pv04_reactivacion` | `proveedores/index.blade.php` |
| **PV05** | Asociación Catálogo / Precios | `POST /api/v1/proveedores/{id}/productos` | `ProveedorController@asociarProducto` | `UserStoriesFrontendTest@test_pv05_asociar_productos` | `proveedores/index.blade.php` |
| **PV06** | Historial de Precios de Compra | `GET /api/v1/proveedores/{id}/productos/{idP}/historial-precios` | `HistorialPrecioController`<br>`HistorialPrecioService` | `HistorialPrecioProveedorTest` | `proveedores/index.blade.php` |
| **PV07** | Configuración de Plazo Entrega | `POST /api/v1/proveedores/{id}/plazo` | `PlazoEntregaController@store` | `UserStoriesFrontendTest@test_pv07_plazos_entrega` | `proveedores/index.blade.php` |
| **PV08** | Auditoría y Métricas Proveedor | `GET /api/v1/proveedores/{id}/rendimiento` | `ProveedorController@rendimiento` | `UserStoriesFrontendTest@test_pv08_metricas_proveedor` | `proveedores/index.blade.php` |

---

## 4. Módulo Compras (C01 – C09)

| ID | Historia de Usuario | Endpoint(s) API | Controlador / Servicio | Test Automatizado | Vista / UI |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **C01** | Registro de Compra Directa | `POST /api/v1/compras` | `CompraController@store` | `UserStoriesFrontendTest@test_c01_registro_compra` | `compras/index.blade.php` |
| **C02** | Recepción Total e Impacto Stock | `POST /api/v1/compras/{id}/recepciones` | `RecepcionController@store` | `UserStoriesFrontendTest@test_c02_recepcion_total` | `compras/index.blade.php` |
| **C03** | Recepción Parcial de Compra | `POST /api/v1/compras/{id}/recepciones` | `RecepcionController@store` | `UserStoriesFrontendTest@test_c03_recepcion_parcial` | `compras/index.blade.php` |
| **C04** | Facturas de Compra (A, B, C) | `GET /api/v1/compras` | `CompraController@index` | `UserStoriesFrontendTest@test_c04_comprobantes_compra` | `compras/index.blade.php` |
| **C05** | Impresión Comprobante A4 | `GET /compras/{id}/imprimir` | `CompraController@imprimir` | `UserStoriesFrontendTest@test_c05_impresion_comprobante` | `compras/index.blade.php` |
| **C06** | Asignación de Lote en Recepción | `POST /api/v1/compras/{id}/recepciones` | `RecepcionController@store` | `UserStoriesFrontendTest@test_c06_lote_en_recepcion` | `compras/index.blade.php` |
| **C07** | Pago a Proveedor | `POST /api/v1/compras/{id}/pagos` | `PagoCompraController@store` | `UserStoriesFrontendTest@test_c07_registro_pagos` | `compras/index.blade.php` |
| **C08** | Pagos Diferidos (Cheques / E-cheq)| `POST /api/v1/compras/{id}/pagos` | `PagoCompraController@store` | `UserStoriesFrontendTest@test_c08_pagos_diferidos` | `compras/index.blade.php` |
| **C09** | Estado de Cuentas y Deudas | `GET /api/v1/compras/deudas` | `CompraController@deudas` | `UserStoriesFrontendTest@test_c09_cuentas_corrientes` | `compras/index.blade.php` |

---

## 5. Módulo Pedidos de Compra (PC01 – PC07)

| ID | Historia de Usuario | Endpoint(s) API | Controlador / Servicio | Test Automatizado | Vista / UI |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **PC01** | Creación de Orden de Compra | `POST /api/v1/ordenes-compra` | `OrdenCompraController@store` | `reporte-pruebas-pc01-ordenes-compra.md` | `compras/index.blade.php` |
| **PC02** | Listado y Filtros de Órdenes | `GET /api/v1/ordenes-compra` | `OrdenCompraController@index` | `reporte-pruebas-pc02-visualizar-ordenes-compra.md` | `compras/index.blade.php` |
| **PC03** | Detalle de Orden de Compra | `GET /api/v1/ordenes-compra/{id}` | `OrdenCompraController@show` | `reporte-pruebas-pc03-detalle-orden-compra.md` | `compras/index.blade.php` |
| **PC04** | Edición de Orden de Compra | `PUT /api/v1/ordenes-compra/{id}` | `OrdenCompraController@update` | `reporte-pruebas-pc04-editar-orden-compra.md` | `compras/index.blade.php` |
| **PC05** | Cancelación de Orden de Compra | `PATCH /api/v1/ordenes-compra/{id}/cancelar` | `OrdenCompraController@cancelar` | `reporte-pruebas-pc05-cancelar-orden-compra.md` | `compras/index.blade.php` |
| **PC06** | Timeline y Estados de la Orden | `PATCH /api/v1/ordenes-compra/{id}/cambiar-estado` | `OrdenCompraController@cambiarEstado` | `reporte-pruebas-pc06-visualizar-estado-orden-compra.md` | `compras/index.blade.php` |
| **PC07** | Sugerencias de Reposición | `GET /api/v1/sugerencias-reposicion`<br>`POST /.../generar-orden` | `SugerenciasReposicionController` | `reporte-pruebas-pc07-sugerencias-ordenes-compra.md` | `dashboard-reposicion.blade.php` |
