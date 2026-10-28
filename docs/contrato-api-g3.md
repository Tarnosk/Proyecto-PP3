# Contrato HTTP/JSON y Documentación de APIs — Grupo 3 (Inventario, Productos, Stock, Proveedores y Compras)

Este documento constituye la referencia técnica oficial y detallada de todas las APIs REST publicadas por el **Grupo 3 (G3)** en [`app/Modules/Productos/routes/api.php`](../app/Modules/Productos/routes/api.php).

Está diseñado tanto para que los **Grupos 1, 2 y 4** integren sus módulos sin realizar consultas SQL directas a las tablas de G3, como para documentar el funcionamiento interno de los submódulos de **Productos, Stock, Proveedores, Compras y Pedidos de Compra**.

---

## Índice

1. [Convenciones Generales de la API de G3](#1-convenciones-generales-de-la-api-de-g3)
2. [Criterio de Búsqueda: `id` / `id_producto` vs. `codigo`](#2-criterio-de-búsqueda-id--id_producto-vs-codigo)
3. [APIs Principales para Consumo de Otros Grupos (G1, G2 y G4)](#3-apis-principales-para-consumo-de-otros-grupos-g1-g2-y-g4)
   - [3.1. `GET /api/productos` — Listar y buscar productos del catálogo](#31-get-apiproductos--listar-y-buscar-productos-del-catálogo)
   - [3.2. `GET /api/productos/{id}` — Detalle de un producto por ID](#32-get-apiproductosid--detalle-de-un-producto-por-id)
   - [3.3. `GET /api/productos/codigo/{codigo}` — Detalle de un producto por Código SKU](#33-get-apiproductoscodigocodigo--detalle-de-un-producto-por-código-sku)
   - [3.4. `GET /api/stock/{id}` — Consultar stock actual de un producto](#34-get-apistockid--consultar-stock-actual-de-un-producto)
   - [3.5. `POST /api/stock/movimientos` — Descontar o reponer stock en lote (Ventas / Devoluciones)](#35-post-apistockmovimientos--descontar-o-reponer-stock-en-lote-ventas--devoluciones)
   - [3.6. `POST /api/stock/ventas` — Descuento puntual de stock por venta](#36-post-apistockventas--descuento-puntual-de-stock-por-venta)
   - [3.7. `POST /api/stock/devoluciones` — Registro de devolución de cliente o a proveedor](#37-post-apistockdevoluciones--registro-de-devolución-de-cliente-o-a-proveedor)
   - [3.8. `GET /api/categorias` y `GET /api/marcas` — Maestros de Categorías y Marcas](#38-get-apicategorias-y-get-apimarcas--maestros-de-categorías-y-marcas)
   - [3.9. `GET /api/stock` — Listado general de stock y estados de alerta](#39-get-apistock--listado-general-de-stock-y-estados-de-alerta)
4. [Guía de Integración Específica por Grupo](#4-guía-de-integración-específica-por-grupo)
   - [4.1. Integración con Grupo 1 (Clientes, Catálogo Público y Reportes)](#41-integración-con-grupo-1-clientes-catálogo-público-y-reportes)
   - [4.2. Integración con Grupo 4 (Ventas, Promociones y Cuenta Corriente)](#42-integración-con-grupo-4-ventas-promociones-y-cuenta-corriente)
   - [4.3. Integración con Grupo 2 (Entregas, Remitos, Rendiciones y Caja)](#43-integración-con-grupo-2-entregas-remitos-rendiciones-y-caja)
5. [Referencia de APIs Operativas Internas de G3](#5-referencia-de-apis-operativas-internas-de-g3)
   - [5.1. Módulo Productos, Precios y Auditoría](#51-módulo-productos-precios-y-auditoría)
   - [5.2. Módulo Stock, Kardex, Alertas, Unidades, Ubicaciones y Conteos](#52-módulo-stock-kardex-alertas-unidades-ubicaciones-y-conteos)
   - [5.3. Módulo Proveedores, Asociaciones, Plazos e Historial de Costos](#53-módulo-proveedores-asociaciones-plazos-e-historial-de-costos)
   - [5.4. Módulo Compras, Recepciones y Pagos a Proveedores](#54-módulo-compras-recepciones-y-pagos-a-proveedores)
   - [5.5. Módulo Órdenes de Compra, Sugerencias y Dashboard de Reposición](#55-módulo-órdenes-de-compra-sugerencias-y-dashboard-de-reposición)
6. [Ejemplos Prácticos de Prueba (PowerShell y cURL)](#6-ejemplos-prácticos-de-prueba-powershell-y-curl)

---

## 1. Convenciones Generales de la API de G3

### 1.1. Doble prefijo soportado (`/api` y `/api/v1`)
Todas las rutas de G3 están registradas simultáneamente bajo **dos prefijos**:
- `/api/<ruta>` (ej. `GET /api/productos`, `POST /api/stock/movimientos`)
- `/api/v1/<ruta>` (ej. `GET /api/v1/productos`, `POST /api/v1/stock/movimientos`)

Esto garantiza compatibilidad inmediata tanto con **Grupo 1** (que utiliza `/api/v1/productos` por convención en `config/services.php`) como con **Grupo 4** (que utiliza `/api/productos` y `/api/stock/...` en `HttpProductoApi` y `HttpStockApi`).

### 1.2. Cabeceras HTTP recomendadas
- **`Accept: application/json`** (obligatoria para recibir respuestas de error en formato JSON).
- **`Content-Type: application/json`** (en peticiones `POST`, `PUT`, `PATCH` con cuerpo JSON).
- **`X-User-Id: <id_usuario>`** *(opcional)*: Si el módulo invocador envía el ID del usuario que ejecuta la acción (o si existe sesión activa), G3 lo registra automáticamente en el Kardex (`movimiento_stock.id_usuario`) y en el log de auditoría (`log_auditoria.id_usuario`). Si se omite en llamadas servidor a servidor, G3 utiliza un usuario de sistema por defecto sin bloquear la operación.

### 1.3. Estructura estándar de respuesta y Códigos HTTP
Las respuestas exitosas de G3 siguen la envoltura estándar:
```json
{
  "status": "success",
  "message": "Descripción opcional de la operación realizada.",
  "data": { ... }
}
```
> **Nota de compatibilidad:** En endpoints donde otro grupo espera propiedades en la raíz del JSON (como `GET /api/stock/{id}` donde G4 lee `$respuesta->json('disponible')`), G3 incluye el valor **tanto en la raíz del JSON como dentro de `data`**.

| Código HTTP | Significado en G3 | Estructura de respuesta |
| :--- | :--- | :--- |
| `200 OK` | Consulta exitosa, actualización o procesamiento de lote de movimientos | `{"status": "success", "data": ...}` |
| `201 Created` | Creación exitosa de un recurso (producto, movimiento individual, compra, orden, etc.) | `{"status": "success", "message": "...", "data": ...}` |
| `400 Bad Request` | Error de validación de datos o regla de negocio (ej. stock insuficiente, producto inactivo) | `{"status": "error", "message": "Detalle legible del error"}` |
| `404 Not Found` | El producto, compra, orden o proveedor solicitado no existe | `{"status": "error", "message": "Producto no encontrado."}` |
| `500 Internal Server Error` | Error inesperado de infraestructura o base de datos | `{"status": "error", "message": "..."}` |

---

## 2. Criterio de Búsqueda: `id` / `id_producto` vs. `codigo`

En el modelo de dominio de G3 existen dos identificadores para un producto:

1. **`id_producto` (expuesto también como `id` en todos los JSON):**
   - Es la clave primaria numérica (`INT UNSIGNED AUTO_INCREMENT`) de la tabla `producto`.
   - **Cuándo usarlo:** Siempre que un módulo guarde una referencia en su propia base de datos (`detalle_venta.id_producto`, `promocion_producto.id_producto`, `detalle_remito.id_producto`) o invoque endpoints de recurso puntual (`GET /api/productos/{id}`, `GET /api/stock/{id}`, `POST /api/stock/movimientos`).
   - **Garantía de G3:** En todas las respuestas de productos y stock, G3 devuelve **ambas claves** (`"id"` e `"id_producto"`) con el mismo valor entero positivo.

2. **`codigo` (ej. `"P001"`, `"YER-PLAY-1KG"`):**
   - Es el código comercial / SKU legible por humanos (`VARCHAR(50) UNIQUE`).
   - **Cuándo usarlo:** En buscadores de interfaz de usuario (Punto de Venta, Catálogo, filtros de reportes, lectores de código de barras).
   - **Cómo buscar por `codigo` en la API de G3:**
     - **Búsqueda exacta directa:** `GET /api/productos/codigo/{codigo}` (ej. `GET /api/productos/codigo/P001`).
     - **Filtro exacto en listado:** `GET /api/productos?codigo=P001`.
     - **Búsqueda parcial (código, nombre o descripción):** `GET /api/productos?search=P001` (también acepta `?buscar=` o `?q=`).

---

## 3. APIs Principales para Consumo de Otros Grupos (G1, G2 y G4)

### 3.1. `GET /api/productos` — Listar y buscar productos del catálogo

- **Rutas equivalentes:** `GET /api/productos` | `GET /api/v1/productos`
- **Controlador:** [`App\Modules\Productos\Controllers\ProductoController@index`](../app/Modules/Productos/Controllers/ProductoController.php)
- **Consumidores directos:**
  - **Grupo 1:** `CatalogoGateway::products()` e `InternalCatalogApi::get('/api/v1/productos', ['estado' => 'activo'])`.
  - **Grupo 4:** `HttpProductoApi::todos()` (`GET /api/productos`) para el POS, cálculo de ventas (`CalculadoraVenta`) y validación de promociones (`PromocionController`).

#### Parámetros Query (todos opcionales)

| Parámetro | Tipo | Descripción | Ejemplo |
| :--- | :--- | :--- | :--- |
| `estado` | `string` | Filtra por estado del producto: `activo`, `inactivo` o `todos`. Si se omite, devuelve todos. **Nota:** Si el consumidor solo debe mostrar productos habilitados para la venta, se recomienda enviar `?estado=activo`. | `?estado=activo` |
| `search` *(alias: `buscar`, `q`)* | `string` | Búsqueda parcial (`LIKE %...%`) por `codigo`, `nombre` o `descripcion`. | `?search=Coca` o `?q=P001` |
| `codigo` | `string` | Filtra por coincidencia exacta del campo `codigo`. | `?codigo=P001` |
| `categoria_id` | `int` | Filtra productos pertenecientes a una categoría (`id_categoria`). | `?categoria_id=1` |
| `marca_id` | `int` | Filtra productos pertenecientes a una marca (`id_marca`). | `?marca_id=2` |

#### Campos de cada producto en `data[]`

| Campo | Tipo JSON | Descripción y propósito de integración |
| :--- | :--- | :--- |
| `id` | `int` | Identificador único del producto (igual a `id_producto`). Usado por G1 y G4. |
| `id_producto` | `int` | Clave primaria del producto en la tabla `producto`. |
| `codigo` | `string` | Código SKU único del producto (ej. `"P001"`). |
| `nombre` | `string` | Nombre comercial del producto (ej. `"Coca-Cola 1.5L"`). |
| `descripcion` | `string` | Descripción detallada del producto. |
| `imagen` | `string\|null` | Ruta relativa (`/assets/catalogo/...`, `/storage/...`) o URL externa de la imagen. |
| `imagen_url` | `string\|null` | URL normalizada de la imagen lista para renderizar en `<img>` (validada por `ProductoCatalogo` de G1). |
| `precio_unitario` | `float` | Precio unitario vigente en ARS. Usado por G1 (`ProductoCatalogo`) y G4. |
| `precio_mayorista` | `float` | Precio para lista mayorista (compatible con `HttpProductoApi` de G4). |
| `precio_minorista` | `float` | Precio para lista minorista (compatible con `HttpProductoApi` de G4). |
| `precioMay` | `float` | Alias camelCase de `precio_mayorista` (compatible directo con JS de POS/Promociones de G4). |
| `precioMin` | `float` | Alias camelCase de `precio_minorista` (compatible directo con JS de POS/Promociones de G4). |
| `estado` | `string` | `"activo"` o `"inactivo"`. |
| `stock` | `int` | Cantidad física actual en depósito (en unidades base). Usado por G4. |
| `stock_disponible` | `int` | Igual a `stock`. Mantenido por compatibilidad con el validador de `ProductoCatalogo::fromApi()` de G1. |
| `stock_minimo` | `int` | Umbral mínimo configurado para alertas de reposición. |
| `estado_alerta` | `string` | Semáforo calculado en tiempo real: `"normal"` (`stock > stock_minimo`), `"bajo"` (`0 < stock <= stock_minimo`) o `"critico"` (`stock <= 0`). |
| `id_categoria` | `int` | ID de la categoría asociada. |
| `categoria_nombre` | `string\|null` | Nombre de la categoría (ej. `"Bebidas"`). |
| `id_marca` | `int` | ID de la marca asociada. |
| `marca_nombre` | `string\|null` | Nombre de la marca (ej. `"Coca-Cola"`). |
| `unidad_medida` | `string` | Nombre de la presentación/unidad activa (ej. `"Pack x 6"` o `"u."`). |
| `equivalencia_unidad` | `float` | Factor de conversión de la unidad activa a unidades individuales. |
| `id_unidad_activa` | `int\|null` | ID de la unidad de medida activa en `unidad_medida` (si tiene). |
| `formato_bulto` | `string` | Tipo de empaque comercial (ej. `"Pack"`, `"Caja"`, `"Fardo"`, `"Unidad"`). |
| `unidades_por_bulto` | `int` | Cantidad de unidades individuales que trae cada bulto (mínimo `1`). |
| `presentacion_texto` | `string` | Texto legible de presentación (ej. `"Pack x 6 u."` o `"Unidad individual (u.)"`). |
| `id_ubicacion` | `int\|null` | ID de la ubicación física asignada en depósito. |
| `ubicacion_nombre` | `string` | Código/descripción de la ubicación física o `"Sin asignar"`. |
| `fecha_alta` | `string\|null` | Fecha y hora de creación (`YYYY-MM-DD HH:mm:ss`). |
| `fecha_modificacion` | `string\|null` | Fecha y hora de última modificación. |
| `fecha_desactivacion` | `string\|null` | Fecha y hora de baja lógica (`null` si está activo). |
| `id_usuario_carga` | `int\|null` | ID del usuario que dio de alta el producto. |
| `usuario_carga_nombre` | `string\|null` | Nombre del usuario que dio de alta el producto. |
| `id_usuario_modificacion` | `int\|null` | ID del último usuario que modificó el producto. |
| `usuario_modificacion_nombre` | `string\|null` | Nombre del último usuario que modificó el producto. |

#### Ejemplo de Respuesta (`200 OK`)
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "id_producto": 1,
      "codigo": "P001",
      "descripcion": "Gaseosa Coca-Cola botella 1.5L",
      "nombre": "Coca-Cola 1.5L",
      "imagen": "/assets/catalogo/p001.webp",
      "imagen_url": "/assets/catalogo/p001.webp",
      "precio_unitario": 2500,
      "precio_mayorista": 2500,
      "precio_minorista": 2500,
      "precioMay": 2500,
      "precioMin": 2500,
      "estado": "activo",
      "fecha_alta": "2026-09-01 10:00:00",
      "fecha_modificacion": "2026-10-24 12:00:00",
      "fecha_desactivacion": null,
      "id_usuario_carga": 1,
      "usuario_carga_nombre": "Administrador",
      "id_usuario_modificacion": 1,
      "usuario_modificacion_nombre": "Administrador",
      "id_categoria": 1,
      "categoria_nombre": "Bebidas",
      "id_marca": 1,
      "marca_nombre": "Coca-Cola",
      "id_ubicacion": 1,
      "ubicacion_nombre": "PASILLO-A1",
      "stock": 48,
      "stock_disponible": 48,
      "stock_minimo": 10,
      "estado_alerta": "normal",
      "unidad_medida": "Pack x 6",
      "equivalencia_unidad": 6,
      "id_unidad_activa": null,
      "formato_bulto": "Pack",
      "unidades_por_bulto": 6,
      "presentacion_texto": "Pack x 6 u."
    }
  ]
}
```

---

### 3.2. `GET /api/productos/{id}` — Detalle de un producto por ID

- **Rutas equivalentes:** `GET /api/productos/{id}` | `GET /api/v1/productos/{id}`
- **Controlador:** [`App\Modules\Productos\Controllers\ProductoController@show`](../app/Modules/Productos/Controllers/ProductoController.php)
- **Consumidores directos:**
  - **Grupo 1:** `CatalogoGateway::find(int $id)` para la ficha pública `/catalogo/{id}`.
  - **Grupo 4:** `HttpProductoApi::buscar(int $id)` usado por `VentaResource` (para armar los detalles de cada venta en `GET /api/ventas` y `GET /api/ventas/{id}`) y por `PromocionController`.
  - **Grupo 2:** De forma indirecta cuando `VentasApiClient::obtenerVentaPorId()` consulta una venta en G4 para armar entregas y remitos.

#### Parámetros de Ruta
- `{id}` *(int, obligatorio)*: `id_producto` a consultar.

#### Respuesta Exitosa (`200 OK`)
Devuelve un objeto JSON con `"status": "success"` y `"data": { ... }` conteniendo **exactamente los mismos campos que `GET /api/productos`**, más el arreglo `"historial_precios"` con las variaciones históricas del producto.

#### Respuesta cuando no existe (`404 Not Found`)
```json
{
  "status": "error",
  "message": "Producto no encontrado."
}
```

---

### 3.3. `GET /api/productos/codigo/{codigo}` — Detalle de un producto por Código SKU

- **Rutas equivalentes:** `GET /api/productos/codigo/{codigo}` | `GET /api/v1/productos/codigo/{codigo}`
- **Controlador:** [`App\Modules\Productos\Controllers\ProductoController@showByCodigo`](../app/Modules/Productos/Controllers/ProductoController.php)
- **Propósito:** Permite a cualquier módulo resolver la ficha completa de un producto conociendo únicamente su código comercial (`P001`, `YER-PLAY-1KG`, etc.) sin necesidad de recorrer todo el listado.
- **Respuestas:**
  - `200 OK`: Idéntica a `GET /api/productos/{id}`.
  - `404 Not Found`:
    ```json
    {
      "status": "error",
      "message": "Producto con código 'P999' no encontrado."
    }
    ```

---

### 3.4. `GET /api/stock/{id}` — Consultar stock actual de un producto

- **Rutas equivalentes:**
  - `GET /api/stock/{id}` | `GET /api/v1/stock/{id}`
  - `GET /api/stock/productos/{id}` | `GET /api/v1/stock/productos/{id}`
- **Controlador:** [`App\Modules\Stock\Controllers\StockController@show`](../app/Modules/Stock/Controllers/StockController.php)
- **Consumidores directos:**
  - **Grupo 4:** [`HttpStockApi::disponible(int $idProducto)`](../app/Modules/Ventas/Integraciones/HttpStockApi.php), que invoca `GET /api/stock/{id_producto}` y lee `(int) $respuesta->json('disponible')`.

#### Ejemplo de Respuesta (`200 OK`)
Incluye `stock` y `disponible` tanto en el nivel superior del JSON (requerido por `HttpStockApi` de G4) como dentro del objeto `data`:
```json
{
  "status": "success",
  "stock": 48,
  "disponible": 48,
  "data": {
    "id": 1,
    "id_producto": 1,
    "codigo": "P001",
    "nombre": "Coca-Cola 1.5L",
    "descripcion": "Gaseosa Coca-Cola botella 1.5L",
    "precio_unitario": 2500,
    "precio_mayorista": 2500,
    "precio_minorista": 2500,
    "stock": 48,
    "stock_disponible": 48,
    "disponible": 48,
    "stock_minimo": 10,
    "estado_alerta": "normal",
    "unidades": [
      {
        "id_unidad": 1,
        "nombre_unidad": "Pack x 6",
        "equivalencia_base": 6,
        "descripcion": "Pack cerrado de 6 botellas",
        "disponible_en_unidad": 8
      }
    ]
  }
}
```

#### Errores posibles
- `404 Not Found`: `{"status": "error", "message": "Producto no encontrado."}`
- `400 Bad Request` (si el producto está dado de baja): `{"status": "error", "message": "El producto no se encuentra activo."}`

---

### 3.5. `POST /api/stock/movimientos` — Descontar o reponer stock en lote (Ventas / Devoluciones)

- **Rutas equivalentes:** `POST /api/stock/movimientos` | `POST /api/v1/stock/movimientos`
- **Controlador:** [`App\Modules\Stock\Controllers\StockController@registrarMovimientosLote`](../app/Modules/Stock/Controllers/StockController.php)
- **Consumidores directos:**
  - **Grupo 4 (Ventas):** [`HttpStockApi::ajustar(array $cambios, int $ventaId, string $motivo)`](../app/Modules/Ventas/Integraciones/HttpStockApi.php), invocado desde [`VentaService`](../app/Modules/Ventas/Services/VentaService.php) al crear, modificar, anular o restaurar una venta.
  - **Grupo 2 (Entregas / Rendiciones):** Puede invocarlo cuando un repartidor registra una devolución de mercadería no entregada en ruta para reintegrar los artículos al depósito.

#### Regla de Negocio y Convención de Signos (`cantidad`)
Toda la lista `items` se ejecuta dentro de una **única transacción atómica (`DB::transaction`)** con bloqueo de fila (`lockForUpdate`):
- **Si `cantidad < 0` (negativa):** Representa una **salida de mercadería** (ej. alta de venta, agregado de unidades en una modificación de venta, o restauración de una venta anulada).
  - Verifica que el producto exista, esté `activo` y tenga `stock >= abs(cantidad)`. Si algún ítem no tiene stock suficiente, **hace rollback de todo el lote** y devuelve `400 Bad Request`.
  - Descuenta `abs(cantidad)` de `producto.stock` y crea una fila en `movimiento_stock` con `tipo = 'venta'`.
- **Si `cantidad > 0` (positiva):** Representa un **reingreso de mercadería al depósito** (ej. anulación de venta, quita de unidades en una modificación de venta, o devolución en reparto).
  - Suma `cantidad` a `producto.stock` y crea una fila en `movimiento_stock` con `tipo = 'devolucion'` (si `origen = 'ventas'`) o `'ajuste'`.

#### Cuerpo de la Petición (Request Body JSON)

| Campo | Tipo | Obligatorio | Descripción |
| :--- | :--- | :--- | :--- |
| `origen` | `string` | Opcional (default `"ventas"`) | Módulo que origina el movimiento (`"ventas"`, `"entregas"`, `"rendiciones"`). |
| `venta_id` *(alias: `id_venta`)* | `int\|null` | Opcional | ID lógico de la venta asociada. Se guarda en `movimiento_stock.id_venta` para trazabilidad sin exigir FK física contra la tabla `venta`. |
| `motivo` | `string` | Opcional | Motivo de la operación (`"venta"`, `"modificacion"`, `"anulacion"`, `"restauracion"`, etc.). |
| `items` | `array` | **Obligatorio** | Lista de al menos 1 producto a impactar. |
| `items[].id_producto` | `int` | **Obligatorio** | ID del producto (`> 0`). |
| `items[].cantidad` | `int` | **Obligatorio** | Cantidad con signo (`!= 0`): negativa descuenta stock, positiva suma stock. |
| `items[].id_unidad` | `int\|null` | Opcional | Si se envía un `id_unidad` configurado en `unidad_medida`, convierte automáticamente a unidades base multiplicando por `equivalencia_base`. |

#### Ejemplo de Request JSON (tal como lo envía `HttpStockApi` de G4)
```json
{
  "origen": "ventas",
  "venta_id": 5,
  "motivo": "modificacion",
  "items": [
    { "id_producto": 1, "cantidad": -3 },
    { "id_producto": 2, "cantidad": 1 }
  ]
}
```

#### Ejemplo de Respuesta Exitosa (`200 OK`)
```json
{
  "status": "success",
  "message": "Movimientos de stock registrados correctamente.",
  "data": [
    {
      "id_movimiento": 42,
      "id_producto": 1,
      "codigo": "P001",
      "tipo": "venta",
      "cantidad": 3,
      "stock": 45,
      "stock_disponible": 45,
      "disponible": 45
    },
    {
      "id_movimiento": 43,
      "id_producto": 2,
      "codigo": "P002",
      "tipo": "devolucion",
      "cantidad": 1,
      "stock": 31,
      "stock_disponible": 31,
      "disponible": 31
    }
  ]
}
```

#### Ejemplo de Respuesta por Stock Insuficiente (`400 Bad Request`)
```json
{
  "status": "error",
  "message": "Stock insuficiente para el producto 'Coca-Cola 1.5L'. Disponible: 2, solicitado: 10."
}
```

---

### 3.6. `POST /api/stock/ventas` — Descuento puntual de stock por venta

- **Rutas equivalentes:** `POST /api/stock/ventas` | `POST /api/stock/descuentos` | `PATCH /api/stock/{id}/descontar`
- **Controlador:** [`App\Modules\Stock\Controllers\VentaStockController@store`](../app/Modules/Stock/Controllers/VentaStockController.php)
- **Propósito:** Alternativa unitaria a `POST /api/stock/movimientos` cuando se desea descontar un único producto indicando siempre cantidad positiva.
- **Request Body JSON:**
  ```json
  {
    "id_producto": 1,
    "cantidad": 2,
    "id_venta": 15,
    "motivo": "Venta mostrador #15"
  }
  ```
- **Respuesta (`200 OK`):**
  ```json
  {
    "status": "success",
    "message": "Stock actualizado por la venta. Se descontó 2 unidad(es).",
    "data": {
      "id_producto": 1,
      "codigo": "P001",
      "descripcion": "Gaseosa Coca-Cola botella 1.5L",
      "cantidad_vendida": 2,
      "cantidad_descontada": 2,
      "stock_disponible": 46,
      "estado_alerta": "normal"
    }
  }
  ```

---

### 3.7. `POST /api/stock/devoluciones` — Registro de devolución de cliente o a proveedor

- **Rutas equivalentes:** `POST /api/stock/devoluciones` | `POST /api/v1/stock/devoluciones`
- **Controlador:** [`App\Modules\Stock\Controllers\DevolucionController@store`](../app/Modules/Stock/Controllers/DevolucionController.php)
- **Propósito:** Registra devoluciones unitarias especificando si provienen de un **cliente** (suma al stock) o si son devoluciones hacia un **proveedor** por mercadería defectuosa (resta del stock).
- **Request Body JSON:**
  ```json
  {
    "id_producto": 1,
    "cantidad": 4,
    "tipo_devolucion": "cliente",
    "motivo": "Pedido rechazado en entrega #8"
  }
  ```
- **Respuesta (`201 Created`):**
  ```json
  {
    "status": "success",
    "message": "Devolución de cliente registrada exitosamente (+4 u. sumadas al stock).",
    "data": {
      "id_producto": 1,
      "codigo": "P001",
      "descripcion": "Gaseosa Coca-Cola botella 1.5L",
      "tipo_devolucion": "cliente",
      "impacto_stock": "+4",
      "cantidad_devuelta": 4,
      "cantidad_base": 4,
      "motivo": "[Devolución Cliente] Pedido rechazado en entrega #8",
      "stock_disponible": 52,
      "estado_alerta": "normal"
    }
  }
  ```

---

### 3.8. `GET /api/categorias` y `GET /api/marcas` — Maestros de Categorías y Marcas

- **Rutas equivalentes:**
  - `GET /api/categorias` | `GET /api/v1/categorias` ([`CategoriaController@index`](../app/Modules/Productos/Controllers/CategoriaController.php))
  - `GET /api/marcas` | `GET /api/v1/marcas` ([`MarcaController@index`](../app/Modules/Productos/Controllers/MarcaController.php))
- **Parámetros Query opcionales:** `?search=` (filtra por nombre).
- **Respuesta `GET /api/categorias` (`200 OK`):**
  ```json
  {
    "status": "success",
    "data": [
      {
        "id_categoria": 1,
        "nombre": "Bebidas",
        "fecha_creacion": "2026-09-01 00:00:00",
        "fecha_modificacion": null,
        "total_productos": 1
      }
    ]
  }
  ```
- **Respuesta `GET /api/marcas` (`200 OK`):**
  ```json
  {
    "status": "success",
    "data": [
      {
        "id_marca": 1,
        "nombre": "Coca-Cola",
        "fecha_creacion": "2026-09-01 00:00:00",
        "fecha_modificacion": null,
        "total_productos": 1
      }
    ]
  }
  ```

---

### 3.9. `GET /api/stock` — Listado general de stock y estados de alerta

- **Rutas equivalentes:** `GET /api/stock` | `GET /api/v1/stock`
- **Controlador:** [`App\Modules\Stock\Controllers\StockController@consultaGeneral`](../app/Modules/Stock/Controllers/StockController.php)
- **Parámetros Query opcionales:**
  - `search`: texto a buscar en `codigo`, `nombre` o `descripcion`.
  - `estado`: filtra por estado del semáforo de stock (`normal`, `bajo`, `critico`).
  - `stock_min` / `stock_max`: filtra por rango numérico de stock.
- **Respuesta (`200 OK`):**
  ```json
  {
    "status": "success",
    "data": [
      {
        "id_producto": 1,
        "codigo": "P001",
        "descripcion": "Gaseosa Coca-Cola botella 1.5L",
        "id_categoria": 1,
        "categoria_nombre": "Bebidas",
        "id_marca": 1,
        "marca_nombre": "Coca-Cola",
        "stock": 48,
        "stock_disponible": 48,
        "stock_minimo": 10,
        "estado_alerta": "normal",
        "precio_unitario": 2500,
        "precio_mayorista": 2500,
        "precio_minorista": 2500
      }
    ]
  }
  ```

---

## 4. Guía de Integración Específica por Grupo

### 4.1. Integración con Grupo 1 (Clientes, Catálogo Público y Reportes)
1. **Cómo se comunica hoy G1 con G3:**
   - G1 utiliza [`CatalogoGateway`](../app/Modules/Catalogo/Services/CatalogoGateway.php) y [`InternalCatalogApi`](../app/Modules/Catalogo/Services/InternalCatalogApi.php).
   - En modo interno (`CATALOG_TRANSPORT=internal`), G1 despacha directamente las rutas registradas `GET /api/v1/productos` (`ProductoController@index`) y `GET /api/v1/productos/{id}` (`ProductoController@show`) sin hacer loopback HTTP.
   - En modo HTTP (`CATALOG_TRANSPORT=http`), G1 consulta `CATALOG_SERVICE_URL` + `CATALOG_PRODUCTS_PATH` (`/api/v1/productos`).
2. **Validación de imágenes (`imagen` / `imagen_url`):**
   - `ProductoCatalogo::fromApi()` de G1 exige que las imágenes locales comiencen con `/assets/catalogo/` o `/storage/` y terminen en `.png`, `.jpg`, `.jpeg` o `.webp`.
   - `ProductoController` guarda todos los archivos subidos desde el formulario de Inventario en `public/assets/catalogo/<codigo>-<timestamp>.<ext>` y expone la ruta `/assets/catalogo/...` en `imagen` e `imagen_url`, cumpliendo 100% con el validador de G1.

### 4.2. Integración con Grupo 4 (Ventas, Promociones y Cuenta Corriente)
1. **Activación por configuración (`.env`):**
   En [`config/ventas.php`](../config/ventas.php) y [`AppServiceProvider.php`](../app/Providers/AppServiceProvider.php), G4 conmuta entre mocks y las APIs reales de G3 definiendo en `.env`:
   ```env
   VENTAS_INTEGRACION=http
   VENTAS_PRODUCTOS_URL=http://127.0.0.1:8000
   VENTAS_STOCK_URL=http://127.0.0.1:8000
   ```
   *(Nota: En desarrollo local con `php artisan serve` en un solo puerto, si G4 hace llamadas HTTP a sí mismo, debe correr un worker adicional o usar el puerto de Apache/XAMPP `http://localhost/Proyecto/public`).*
2. **Contratos cubiertos:**
   - [`HttpProductoApi::todos()`](../app/Modules/Ventas/Integraciones/HttpProductoApi.php) → `GET /api/productos` (mapea `id`, `codigo`, `nombre`, `precioMin`, `precioMay`, `stock`).
   - [`HttpProductoApi::buscar($id)`](../app/Modules/Ventas/Integraciones/HttpProductoApi.php) → `GET /api/productos/{id}`.
   - [`HttpStockApi::disponible($idProducto)`](../app/Modules/Ventas/Integraciones/HttpStockApi.php) → `GET /api/stock/{id_producto}` (lee `disponible` de la raíz del JSON).
   - [`HttpStockApi::ajustar($cambios, $ventaId, $motivo)`](../app/Modules/Ventas/Integraciones/HttpStockApi.php) → `POST /api/stock/movimientos`.
3. **Recomendación para G4 en `VentaController.php`:**
   - En [`VentaService`](../app/Modules/Ventas/Services/VentaService.php), [`VentaResource`](../app/Modules/Ventas/Resources/VentaResource.php) y [`PromocionController`](../app/Modules/Promociones/Controllers/PromocionController.php), G4 ya inyecta las interfaces `ProductoGateway` y `StockGateway`.
   - En [`VentaController`](../app/Modules/Ventas/Controllers/VentaController.php) (`index`, `obtenerProductosSimulados`, `store`, `historial`), G4 aún inyecta la clase concreta `MockProductoApi` en vez de la interfaz `ProductoGateway`. Cambiando ese type-hint a `ProductoGateway` (y llamando a `$stock->ajustar(...)` al final de `VentaController@store`), todo el flujo de Ventas usará automáticamente los productos y el stock real de G3.

### 4.3. Integración con Grupo 2 (Entregas, Remitos, Rendiciones y Caja)
1. **Armado de entregas y emisión de remitos:**
   - G2 obtiene las ventas confirmadas y sus ítems desde la API de G4 (`GET /api/ventas` y `GET /api/ventas/{id}`), la cual a su vez toma el `codigo` y `nombre` del producto desde el `ProductoGateway` conectado a G3.
   - Si G2 necesita consultar en qué formato de bulto viene un producto (`formato_bulto`, `unidades_por_bulto`, `presentacion_texto`) para calcular bultos cerrados vs. unidades sueltas en el remito, puede consultar `GET /api/productos/{id}` o `GET /api/productos`.
2. **Devoluciones en Rendición de Reparto:**
   - Cuando en una rendición de reparto un pedido no pudo ser entregado y la mercadería regresa físicamente al depósito, G2 puede impactar el reingreso llamando a `POST /api/stock/movimientos` (con `origen: "rendiciones"` y `cantidad` positiva) o a `POST /api/stock/devoluciones` (`tipo_devolucion: "cliente"`).

---

## 5. Referencia de APIs Operativas Internas de G3

Además de las APIs inter-grupo detalladas en la Sección 3, el backend de G3 expone los siguientes endpoints para la operatoria completa de administración de inventario, compras y proveedores.

### 5.1. Módulo Productos, Precios y Auditoría

| Método | Ruta | Controlador | Descripción y Payload |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/productos` | `ProductoController@store` | **Alta de producto (P01).** Acepta JSON o `multipart/form-data`. Campos: `codigo` (req, único), `nombre`, `descripcion` (req), `precio_unitario` (req), `id_categoria` (req), `id_marca` (req), `stock_disponible` (stock inicial), `stock_minimo`, `formato_bulto`, `unidades_por_bulto`, `id_ubicacion`, `imagen` (ruta/URL) o `imagen_archivo` (`jpg/png/webp` máx 4 MB). |
| `PUT` / `PATCH` | `/api/productos/{id}` | `ProductoController@update` | **Edición de producto (P04).** Si varía `precio_unitario`, genera automáticamente un registro en `historial_precio`. Soporta actualización de `imagen` / `imagen_archivo`. |
| `PATCH` / `DELETE` | `/api/productos/{id}/desactivar` *(o `DELETE /api/productos/{id}`)* | `ProductoController@desactivar` | **Baja lógica (P06).** Cambia `estado` a `"inactivo"` y registra `fecha_desactivacion`. |
| `PATCH` | `/api/productos/{id}/activar` | `ProductoController@activar` | **Reactivación.** Cambia `estado` a `"activo"` y limpia `fecha_desactivacion`. |
| `POST` | `/api/productos/aumento-masivo` | `ProductoController@aumentoMasivo` | **Aumento masivo de precios (P07).** Body: `{ "porcentaje": 15, "tipo_precio": "ambos" \| "mayorista" \| "minorista", "id_categoria": 1, "id_marca": 2, "ids": [1,2], "regla_redondeo": "sin_redondeo" \| "peso_mas_cercano" \| "peso_superior" \| "terminar_90" \| "terminar_99" }`. |
| `GET` | `/api/productos/historial` | `ProductoController@historialPreciosGlobal` | **Historial global de precios (P08).** Filtros: `?id_producto=`, `?origen=manual\|masivo`. |
| `GET` | `/api/productos/{id}/historial` | `ProductoController@historialPrecios` | **Historial cronológico de precios de un producto (P08).** |
| `GET` | `/api/productos/auditoria` | `ProductoController@auditoriaGlobal` | **Log de auditoría global de productos.** Filtro opcional: `?id_producto=`. |
| `GET` | `/api/productos/{id}/auditoria` | `ProductoController@auditoria` | **Log de auditoría de un producto específico.** |
| `POST` / `PUT` / `DELETE` | `/api/categorias`, `/api/categorias/{id}` | `CategoriaController` | ABM de categorías de productos (P02). |
| `POST` / `PUT` / `DELETE` | `/api/marcas`, `/api/marcas/{id}` | `MarcaController` | ABM de marcas de productos (P03). |

---

### 5.2. Módulo Stock, Kardex, Alertas, Unidades, Ubicaciones y Conteos

| Método | Ruta | Controlador | Descripción y Payload |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/stock/ingresos` | `IngresoMercaderiaController@store` | **Ingreso manual de mercadería (S03).** Body: `{ "id_proveedor": 1, "id_producto": 1, "cantidad": 10, "id_unidad": null, "motivo": "Ingreso remito proveedor" }` (o con arreglo `items: [...]`; `id_proveedor` es opcional). Incrementa `producto.stock`. |
| `POST` | `/api/stock/ajustes` | `AjusteStockController@store` | **Ajuste manual positivo o negativo (S06).** Body: `{ "id_producto": 1, "tipo": "aumentar" \| "disminuir" \| "fijar", "cantidad": 3, "motivo": "Rotura en depósito" }` *(también acepta alias `tipo_ajuste`: `"incremento"` / `"decremento"`)*. |
| `GET` | `/api/stock/movimientos` | `StockController@todosLosMovimientos` | **Kardex global de movimientos (S12).** Filtros: `?id_producto=`, `?tipo=ingreso\|venta\|devolucion\|ajuste`, `?limit=150`. |
| `GET` | `/api/stock/{id}/historial-movimientos` | `StockController@historial` | **Kardex histórico de un producto específico (S12).** |
| `GET` | `/api/stock/alertas` *(alias: `/api/stock/bajo`)* | `AlertaStockController@index` | **Alertas de stock mínimo y crítico (S07/S15/S16).** Filtro: `?nivel=bajo\|critico`. Devuelve listado de productos activos en estado de alerta (`bajo` o `critico`). |
| `PATCH` | `/api/stock/alertas/{id}/minimo` | `AlertaStockController@configurarMinimo` | **Configurar stock mínimo de un producto (S07).** Body: `{ "stock_minimo": 15 }`. |
| `GET` / `POST` / `PUT` / `DELETE` | `/api/productos/{id}/unidades` | `UnidadController` | **ABM de unidades de medida y equivalencias de un producto (S08).** Body: `{ "nombre_unidad": "Caja x 12", "equivalencia_base": 12, "descripcion": "..." }`. |
| `POST` | `/api/productos/{id}/unidades/{idUnidad}/seleccionar` | `UnidadController@seleccionar` | Define qué unidad de medida queda marcada como activa (`es_base = 1`) para el producto. |
| `GET` | `/api/unidades/convertir` | `UnidadController@convertir` | Calculadora de equivalencias (`?id_unidad_origen=2&id_unidad_destino=1&cantidad=3`). |
| `GET` / `POST` / `PUT` / `DELETE` | `/api/ubicaciones` | `UbicacionController` | **ABM de ubicaciones físicas del depósito (S14).** Body: `{ "descripcion": "Pasillo A - Estantería 1 - Nivel 2" }`. |
| `POST` / `DELETE` | `/api/productos/{id}/ubicacion` | `UbicacionController` | Asigna (`POST` con `{ "id_ubicacion": 1 }`) o desasigna (`DELETE`) la ubicación de un producto. |
| `GET` / `POST` | `/api/stock/conteos/sesiones` | `ConteoFisicoController` | **Sesiones de Conteo Físico de Inventario (S13):** iniciar sesión (`POST /sesiones`), listar (`GET /sesiones`), ver detalle (`GET /sesiones/{id}`), cargar ítem contado (`POST /sesiones/{id}/items` con `{ "id_producto": 1, "cantidad_fisica": 48 }`), eliminar ítem (`DELETE /sesiones/{id}/items/{idProducto}`) y finalizar aplicando ajustes automáticos (`POST /sesiones/{id}/finalizar` con `{ "aplicar_ajustes": true }`). |

---

### 5.3. Módulo Proveedores, Asociaciones, Plazos e Historial de Costos

| Método | Ruta | Controlador | Descripción y Payload |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/proveedores` | `ProveedorController@index` | **Listar/buscar proveedores (PV03).** Filtros: `?search=`, `?estado=activo\|inactivo`. |
| `GET` | `/api/proveedores/{id}` | `ProveedorController@show` | **Detalle de proveedor (PV02)** con sus productos asociados, plazos e historial. |
| `POST` | `/api/proveedores` | `ProveedorController@store` | **Alta de proveedor (PV01).** Body: `{ "razon_social": "...", "cuit": "30-...", "contacto": "...", "telefono": "...", "email": "...", "direccion": "...", "plazo_entrega_dias": 3 }`. |
| `PUT` / `PATCH` | `/api/proveedores/{id}` | `ProveedorController@update` | **Modificar proveedor (PV02).** |
| `PATCH` | `/api/proveedores/{id}/desactivar` y `/activar` | `ProveedorController` | **Baja lógica y reactivación de proveedor (PV04).** |
| `GET` / `POST` | `/api/proveedores/{id}/productos` | `ProveedorController` | **Listar o asociar productos que comercializa un proveedor (PV05).** Body `POST`: `{ "id_producto": 1, "precio_acordado": 1800, "es_proveedor_principal": true }` *(también acepta `id_productos: [1, 2]` o `productos: [...]`)*. |
| `PUT` / `PATCH` / `DELETE` | `/api/proveedores/productos/{idAsociacion}` | `ProveedorController` | Modificar precio/plazo o eliminar una asociación producto-proveedor. |
| `POST` | `/api/proveedores/productos/{idAsociacion}/principal` | `ProveedorController@definirPrincipal` | Marca ese proveedor como el proveedor principal del producto. |
| `POST` | `/api/proveedores/sincronizar-asociaciones` | `ProveedorController@sincronizarAsociaciones` | Sincroniza asociaciones producto-proveedor a partir de las compras y órdenes históricas. |
| `GET` / `POST` | `/api/proveedores/{id}/plazos` y `/plazo` | `PlazoEntregaController` | **Consulta y registro de plazos de entrega del proveedor (PV06/PV07).** |
| `GET` / `POST` | `/api/proveedores/{id}/precios-historicos` | `HistorialPrecioController` | **Historial de precios de compra por proveedor** y comparativa de aumentos (`/comparativa-aumentos`). |
| `GET` | `/api/proveedores/historial` | `ProveedorController@historialGlobal` | Auditoría global de altas, bajas y modificaciones de proveedores. |

---

### 5.4. Módulo Compras, Recepciones y Pagos a Proveedores

| Método | Ruta | Controlador | Descripción y Payload |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/compras` | `CompraController@index` | **Listado de compras a proveedores (C02).** Filtros: `?id_proveedor=`, `?estado=pendiente\|parcial\|recibida\|cancelada`, `?estado_pago=pendiente\|parcial\|pagada`, `?desde=`, `?hasta=`. |
| `GET` | `/api/compras/{id}` | `CompraController@show` | **Detalle completo de una compra (C03)** con sus renglones, recepciones de mercadería y pagos registrados. |
| `POST` | `/api/compras` | `CompraController@store` | **Registrar compra (C01).** Body: `{ "id_proveedor": 1, "fecha_compra": "2026-10-28", "numero_comprobante": "FC-A-0001-00001234", "id_orden": null, "items": [{ "id_producto": 1, "cantidad": 20, "precio_unitario": 1800 }] }` *(también acepta alias `fecha`, `numero_factura` y `detalles`)*. |
| `PUT` / `PATCH` | `/api/compras/{id}` | `CompraController@update` | **Modificar compra pendiente (C04).** |
| `PATCH` | `/api/compras/{id}/cancelar` | `CompraController@cancelar` | **Cancelar compra pendiente (C05).** |
| `POST` | `/api/compras/{id}/recepciones` | `RecepcionController@store` | **Registrar recepción parcial o total de mercadería (C06/C07).** Body: `{ "fecha_recepcion": "2026-10-28", "items": [{ "id_producto": 1, "cantidad": 20 }] }` *(también acepta `detalles` y `cantidad_recibida`)*. **Impacta automáticamente el ingreso en `producto.stock` y en el Kardex (`movimiento_stock`).** |
| `GET` | `/api/compras/{id}/estado` | `CompraController@estado` | Consulta el avance de recepción (cantidad pedida vs. recibida por ítem) y estado de la compra. |
| `GET` / `POST` | `/api/compras/{id}/pagos` | `PagoCompraController` | **Registrar y listar pagos a proveedores (C08/C09)** soportando múltiples medios de pago (`efectivo`, `transferencia`, `cheque`, `tarjeta`). Body `POST`: `{ "metodo_pago": "transferencia", "importe": 18000, "fecha_pago": "2026-10-28" }` *(también acepta `monto` o arreglo `pagos`)*. |
| `GET` | `/api/compras/{id}/pagos/resumen` | `PagoCompraController@resumen` | Resumen de total comprado, total abonado y saldo pendiente de la compra. |
| `GET` | `/api/compras/deudas` *(alias: `/api/proveedores/deudas`)* | `CompraController@deudas` | Estado de deuda consolidado por proveedor. |

---

### 5.5. Módulo Órdenes de Compra, Sugerencias y Dashboard de Reposición

| Método | Ruta | Controlador | Descripción y Payload |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/ordenes-compra` | `OrdenCompraController@index` | **Listar órdenes de compra (PC02).** Filtros: `?id_proveedor=`, `?estado=borrador\|emitida\|enviada\|recibida_parcial\|completada\|cancelada`. |
| `GET` | `/api/ordenes-compra/{id}` | `OrdenCompraController@show` | **Detalle de orden de compra (PC03).** |
| `POST` | `/api/ordenes-compra` | `OrdenCompraController@store` | **Emitir orden de compra (PC01).** Body: `{ "id_proveedor": 1, "fecha_creacion": "2026-10-28", "items": [{ "id_producto": 1, "cantidad_solicitada": 30, "precio_estimado": 1800 }] }` *(también acepta `detalles` y `cantidad_pedida` / `cantidad`)*. |
| `PUT` / `PATCH` | `/api/ordenes-compra/{id}` | `OrdenCompraController@update` | **Editar orden de compra en borrador/emitida (PC04).** |
| `PATCH` | `/api/ordenes-compra/{id}/enviar` | `OrdenCompraController@enviar` | Marca la orden como `"enviada"` al proveedor (PC06). |
| `PATCH` | `/api/ordenes-compra/{id}/cancelar` | `OrdenCompraController@cancelar` | Cancela una orden de compra con motivo (PC05). |
| `GET` / `POST` | `/api/sugerencias-reposicion/*` | `SugerenciasReposicionController` | **Sugerencias automáticas de reposición (PC07):** `/generar` detecta productos con `stock <= stock_minimo`, `/` lista sugerencias, `/{id}/generar-orden` convierte la sugerencia en una Orden de Compra formal. |
| `GET` | `/api/dashboard-reposicion/*` | `DashboardReposicionController` | KPIs y series para gráficos de reposición (`/resumen`, `/grafico-motivos`, `/grafico-tendencia`, `/grafico-criticidad`). |

---

## 6. Ejemplos Prácticos de Prueba (PowerShell y cURL)

Podés ejecutar cualquiera de estos comandos en PowerShell teniendo el servidor levantado (`C:\xampp\php\php.exe artisan serve`):

### 6.1. Listar todos los productos activos (como lo pide G1 o G4)
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/productos?estado=activo" -Method GET -Headers @{ Accept = "application/json" } | ConvertTo-Json -Depth 5
```

### 6.2. Buscar un producto por su código SKU (`P001`)
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/productos/codigo/P001" -Method GET -Headers @{ Accept = "application/json" } | ConvertTo-Json -Depth 5
```

### 6.3. Consultar stock actual de un producto (como lo pide `HttpStockApi::disponible` de G4)
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/stock/1" -Method GET -Headers @{ Accept = "application/json" } | ConvertTo-Json -Depth 5
```

### 6.4. Descontar stock por una Venta (como lo envía `HttpStockApi::ajustar` de G4)
```powershell
$body = @{
    origen   = "ventas"
    venta_id = 101
    motivo   = "venta"
    items    = @(
        @{ id_producto = 1; cantidad = -2 }
    )
} | ConvertTo-Json -Depth 4

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/stock/movimientos" -Method POST -Headers @{ Accept = "application/json"; "Content-Type" = "application/json" } -Body $body | ConvertTo-Json -Depth 5
```

### 6.5. Reponer stock por Anulación de Venta o Devolución en Reparto
```powershell
$body = @{
    origen   = "ventas"
    venta_id = 101
    motivo   = "anulacion"
    items    = @(
        @{ id_producto = 1; cantidad = 2 }
    )
} | ConvertTo-Json -Depth 4

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/stock/movimientos" -Method POST -Headers @{ Accept = "application/json"; "Content-Type" = "application/json" } -Body $body | ConvertTo-Json -Depth 5
```
