# Guía de Testeo en Postman: APIs de Integración G3 (Consumo de G1, G2 y G4)

Esta guía explica paso a paso cómo probar en **Postman** todas las **APIs que el Grupo 3 expone para los demás módulos del ERP (Grupo 1, Grupo 2 y Grupo 4)** definidas en [`app/Modules/Productos/routes/api.php`](../../app/Modules/Productos/routes/api.php).

---

## 1. Preparación del Entorno Local

1. **Iniciar MySQL** desde el panel de XAMPP (puerto `3306`, base `distribuidorapyb`).
2. **Asegurar las migraciones y el seeder de catálogo:**
   ```powershell
   C:\xampp\php\php.exe artisan migrate
   C:\xampp\php\php.exe artisan db:seed --class="Database\Seeders\ProductoSeeder"
   ```
3. **Levantar el servidor de Laravel:**
   ```powershell
   C:\xampp\php\php.exe artisan serve
   ```
   *(El servidor quedará escuchando en `http://127.0.0.1:8000`).*

---

## 2. Importar la Colección en Postman

1. Abrí **Postman** y hacé clic en **Import** (arriba a la izquierda).
2. Seleccioná el archivo:
   - [`docs/postman/G3.postman_collection.json`](G3.postman_collection.json)
   - *(Si ya la habías importado antes, elegí **Replace**).*
3. Verificá en la pestaña **Variables** de la colección que `base_url` sea `http://127.0.0.1:8000`.
4. **No necesitás configurar tokens ni iniciar sesión manualmente:** las rutas de integración de G3 utilizan el middleware `IdentidadUsuario`, que resuelve un usuario administrativo por defecto para llamadas servidor a servidor entre módulos.

---

## 3. Estructura de la Colección (2 Carpetas, 16 Peticiones con Tests Automáticos)

La colección abarca **exclusivamente los endpoints que consumen G1, G2 y G4**, sin incluir rutas internas del ABM de G3.

### Carpeta 01 — APIs de Productos (Consumo de G1, G2 y G4)

| # | Petición en Postman | Módulo Consumidor | Qué valida automáticamente |
| :--- | :--- | :--- | :--- |
| **01** | `GET /api` | Todos | Índice de endpoints de integración de G3 (`200 OK`). |
| **02** | `GET /api/productos?estado=activo` | **G4** (`HttpProductoApi::todos`) y **G1** | 1. Todos los campos comerciales requeridos por G1, G2 y G4 (`id`, `id_producto`, `codigo`, `nombre`, `descripcion`, `precio_unitario`, `precioMay`, `precioMin`, `stock`, `stock_disponible`, `formato_bulto`, `unidades_por_bulto`, `presentacion_texto`, `estado`).<br>2. Precios netos unificados (`precio_unitario == precioMay == precioMin`).<br>3. **Ocultamiento de datos confidenciales de G3** (`costo`, `recargo`, `margen_ganancia`, `id_usuario_carga`, `id_ubicacion`, `historial_precios`).<br>4. Guarda dinámicamente un producto con stock suficiente en las variables de la colección. |
| **03** | `GET /api/v1/productos?estado=activo` | **G1** (`CatalogoGateway::products` y `ExternalCatalogGateway::products`) | Verifica la ruta `/api/v1/productos` que consumen el Catálogo Público y los Reportes de G1. |
| **04** | `GET /api/productos?estado=activo&buscar=...&id_categoria=...&id_marca=...` | **G1** y **G4** | Filtrado combinado por texto (`codigo`/`nombre`), categoría y marca. |
| **05** | `GET /api/productos/{{id_producto}}` | **G1** (`CatalogoGateway::find`), **G2** (Bultos/Remitos) y **G4** (`HttpProductoApi::buscar`) | Detalle comercial completo del producto por ID sin exponer costos ni historial interno. |
| **06** | `GET /api/productos/999999` | **G1** y **G4** | Verifica que un ID inexistente responda `404 Not Found` (requerido por `CatalogoGateway::find` y `HttpProductoApi::buscar`). |
| **07** | `GET /api/productos/codigo/{{codigo_producto}}` | **G4** (Buscador SKU en POS) | Búsqueda exacta por código comercial (`P001`) y ocultamiento de costos internos. |

---

### Carpeta 02 — APIs de Stock (Consumo de G2 y G4)

*Nota: Las pruebas de movimientos descuentan y reponen exactamente la misma cantidad (`-2 + 2 - 1 + 1 = 0`), dejando el stock del producto intacto al finalizar.*

| # | Petición en Postman | Módulo Consumidor | Qué valida automáticamente |
| :--- | :--- | :--- | :--- |
| **08** | `GET /api/stock` | **G4** | Listado general de existencias, `estado_alerta` (`normal`, `bajo`, `critico`) y `precio_unitario` sin costos internos. |
| **09** | `GET /api/stock/{{id_producto}}` | **G4** (`HttpStockApi::disponible`) | Verifica que `disponible` se exponga en la raíz del JSON y en `data.stock_disponible`. |
| **10** | `GET /api/stock/productos/{{id_producto}}` | **G4** | Verifica el alias de consulta de stock por producto. |
| **11** | `GET /api/stock/999999` | **G4** (`HttpStockApi::disponible`) | Verifica que un producto inexistente devuelva `404 Not Found`. |
| **12** | `POST /api/stock/movimientos` *(cantidad `-2`)* | **G4** (`HttpStockApi::ajustar` al confirmar venta) | Descuento atómico en lote de 2 unidades (`tipo = "venta"`). |
| **13** | `POST /api/stock/movimientos` *(cantidad `+2`)* | **G4** (`HttpStockApi::ajustar` al anular venta) y **G2** | Reposición atómica en lote de las 2 unidades (`tipo = "devolucion"`), restaurando el stock inicial. |
| **14** | `POST /api/stock/movimientos` *(cantidad `-999999`)* | **G4** | Verifica que ante stock insuficiente devuelva `400 Bad Request` y no deje el stock en negativo. |
| **15** | `POST /api/stock/ventas` *(cantidad `1`)* | **G4** (Facturación unitaria) | Descuento unitario de 1 unidad por venta (`stock_inicial - 1`). |
| **16** | `POST /api/stock/devoluciones` *(cantidad `1`, `cliente`)* | **G2** (Rendiciones / Devolución en reparto) | Reingreso unitario (`201 Created`) de 1 unidad devuelta en reparto, dejando el stock nuevamente en `stock_inicial`. |

---

## 4. Cómo Ejecutar Todas las Pruebas de una Sola Vez (Collection Runner)

1. En Postman, hacé clic en los tres puntos `...` junto a la colección **ERP Juan 23 - APIs de Integración G3 (Consumo de G1, G2 y G4)** y elegí **Run collection**.
2. Asegurate de que las peticiones **01 a 16** estén tildadas en orden.
3. Hacé clic en el botón azul **Run ERP Juan 23...**.
4. Verás las **16 peticiones** con todas sus validaciones en **Pass (verde)**.
