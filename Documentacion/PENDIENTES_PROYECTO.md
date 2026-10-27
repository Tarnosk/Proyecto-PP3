# Estado del Proyecto y Tareas Pendientes — ERP Distribuidora PyB

**Fecha de actualización:** 06/10/2026  
**Alcance:** Módulos del Grupo 3 (Productos, Stock, Proveedores, Compras, Pedidos de Compra)  
**Referencia:** Criterios de Aceptación de Historias de Usuario vs. Implementación en Backend (`app/Modules`) y Frontend (`backend/public/Interfaz`)

---

## 1. Resumen Ejecutivo de Avance

| Módulo | Historias Cumplidas | Historias Parciales | Historias Pendientes | Estado General |
| :--- | :---: | :---: | :---: | :---: |
| **Productos (P01 – P08)** | 8 / 8 (100%) | 0 | 0 | **Completado (100%)** |
| **Pedidos de Compra (PC01 – PC07)** | 7 / 7 (100%) | 0 | 0 | **Completado (100%)** |
| **Compras (C01 – C09)** | 7 / 9 (78%) | 1 (C08) | 1 (C09) | **Avanzado (~90%)** |
| **Stock (S01 – S16)** | 13 / 16 (81%) | 1 (S15 UI) | 2 (S13, S14) | **Avanzado (~85%)** |
| **Proveedores (PV01 – PV08)** | 6 / 8 (75%) | 0 | 2 (PV06, PV08) | **Avanzado (~75%)** |

> **Nota:** La base de datos (`distribuidorapyb` con 48 tablas) y la semilla demo actualizada (`semilla_demo_actualizada.sql`) se encuentran 100% operativas en MySQL/MariaDB (puerto 3306).

---

## 2. Últimos Avances Realizados en esta Sesión

1. **PC06 — Visualizar y Gestionar el Estado de una Orden de Compra (3 SP):**
   - **Backend:** 
     - Se añadió soporte en `CompraController@store` para asociar `id_orden`.
     - En `RecepcionController@store`, al recibir mercadería de una compra vinculada a una orden, el estado de `ORDEN_COMPRA` se actualiza automáticamente a `recibida_parcialmente` o `completada`.
     - Se creó el endpoint `PATCH /api/ordenes-compra/{id}/cambiar-estado` en `OrdenCompraController` para avanzar órdenes enviadas a `recibida_parcialmente` o `completada`.
     - Se incorporó la relación `compras()` en el modelo `OrdenCompra`.
   - **Frontend (`pedido_compra.html`):**
     - Se agregaron las 6 tarjetas KPI diferenciadas: *Total*, *Pendientes*, *Enviadas*, *Recibidas parcial*, *Completadas* y *Canceladas*, con filtro al hacer clic.
     - Se diseñó e implementó la **Línea de Tiempo / Stepper visual interactivo** en el modal de detalle de la orden.
     - Se incorporaron los botones de acción para avanzar el estado (*Recibir Parcial* y *Marcar Completada*) desde el modal de detalle.
     - Se añadieron estilos de etiquetas visuales (`.tag.pendiente`, `.tag.enviada`, `.tag.recibida_parcialmente`, `.tag.completada`, `.tag.cancelada`).

2. **Resolución de Brechas Críticas en Módulo Productos (P01–P08):**
   - **P05.2:** Búsqueda flexible por nombre agregada a `scopeSearch` en `Producto.php`.
   - **P05.5:** Filtrado de productos activos por defecto en `ProductoController@index`.
   - **P07.7:** Parámetro y cálculo de `regla_redondeo` (`sin_redondeo`, `redondeo`, `ceil`, `floor`, etc.) en aumentos masivos.
   - **P08.2:** Exposición explícita de `precio_anterior` y `precio_nuevo` en el historial de precios.
   - **UI:** Modal y funcionalidad de Aumento Masivo integrado en `productos.html`.

3. **Mejoras en Módulo Stock:**
   - **S15:** Filtros por rango de stock (`stock_min` y `stock_max`) en el backend `StockController@consultaGeneral`.
   - **S08:** Validación de unicidad de equivalencias por producto en `UnidadController`.
   - **S03, S05, S06:** Soporte para registro de fechas personalizadas de movimientos en `StockService`, `AjusteStockController`, `DevolucionController` e `IngresoMercaderiaController`.

---

## 3. Lo que Queda por Hacer (Detalle de Tareas Pendientes)

### Prioridad Alta (MVP y Consistencia Operativa)

#### 1. S15 — Controles de Filtro de Stock en Frontend (Stock)
- **Complejidad:** 1 SP.
- **Situación actual:** El backend ya soporta `?stock_min=X&stock_max=Y`, pero en `stock.html` no existen los campos numéricos de entrada en la barra de filtros.
- **Tareas:**
  1. Agregar inputs numéricos `#filterStockMin` y `#filterStockMax` en la barra de filtros de `stock.html`.
  2. Conectar los campos en la función `loadStock()` enviando los parámetros a la API.

#### 2. C09 — Visualización de Deudas con Proveedores (Compras)
- **Complejidad:** 5 SP.
- **Situación actual:** Se registran compras y pagos (`PAGO_PROVEEDOR`), pero no existe una vista que calcule el estado de cuenta corriente de cada proveedor.
- **Tareas:**
  1. Crear un endpoint `GET /api/proveedores/{id}/cuenta-corriente` o `GET /api/compras/deudas` que calcule:
     - Total comprado a crédito.
     - Total pagado.
     - Saldo pendiente total por proveedor y por factura de compra.
  2. Agregar una pestaña o modal "Cuentas Corrientes / Deudas" en `compras.html` o `proveedores.html`.

#### 3. C08 (Refinamiento) — Detalle de Medios de Pago Diferidos (Compras)
- **Complejidad:** 2 SP.
- **Situación actual:** El pago registra método (efectivo, transferencia, cheque, etc.), pero no tiene campos para registrar el número de cheque/e-cheq ni la fecha de vencimiento/cobro diferido.
- **Tareas:**
  1. Si se selecciona medio de pago "cheque" o "echeq", solicitar número de comprobante/cheque y fecha de pago diferida.

---

### Prioridad Media (Historias de Usuario del Backlog)

#### 4. S13 — Conteo Físico de Inventario (Stock)
- **Complejidad:** 8 SP.
- **Situación actual:** Las tablas `sesion_conteo` y `detalle_conteo` existen en la base de datos, pero no hay lógica ni vistas creadas.
- **Criterios a cumplir:**
  - 13.1 Registrar cantidad física contada a ciegas por producto.
  - 13.2 Comparar automáticamente el conteo físico con el stock en sistema (`PRODUCTO.stock`).
  - 13.3 Mostrar discrepancias (faltantes / sobrantes).
  - 13.4 Generar resumen de inconsistencias.
  - 13.5 Aplicar ajuste de inventario automático a partir de las diferencias confirmadas.
- **Tareas:**
  1. Crear `ConteoInventarioController.php` y rutas asociadas (`/api/stock/conteos`).
  2. Implementar servicio de consolidación y ajuste.
  3. Crear pantalla o modal para la toma de inventario en `stock.html`.

#### 5. S14 — Gestión de Ubicaciones en Almacén (Stock)
- **Complejidad:** 5 SP.
- **Situación actual:** Las tablas `ubicacion` y `producto_ubicacion` existen en la base de datos sin controladores.
- **Criterios a cumplir:**
  - 14.1 Registrar ubicaciones físicas (pasillo, estantería, nivel).
  - 14.2 Asociar una ubicación física a cada producto.
  - 14.3 Modificar o reasignar ubicaciones.
  - 14.4 Mostrar la ubicación en la consulta de stock y detalle del producto.
- **Tareas:**
  1. Crear `UbicacionController.php` con CRUD básico.
  2. Exponer la ubicación en `ProductoController@show` y en la tabla de stock.

#### 6. PV06 — Historial de Precios de Compra por Proveedor (Proveedores)
- **Complejidad:** 8 SP.
- **Situación actual:** La tabla `historial_precio_proveedor` existe en la base de datos, pero no tiene endpoints de consulta.
- **Criterios a cumplir:**
  - 6.1 Registrar cambios de precio acordado con cada proveedor.
  - 6.2 Consultar la evolución histórica de cotizaciones de un proveedor para un producto.
  - 6.3 Comparar precios entre proveedores para el mismo producto.
- **Tareas:**
  1. En `ProveedorController` o `ProductoProveedorController`, agregar endpoint `GET /api/proveedores/{id}/productos/{idProducto}/historial-precios`.
  2. Agregar botón y modal en `proveedores.html` para consultar el historial de precios pactados.

---

### Prioridad Baja / Futuro

#### 7. PV08 — Estadísticas y Métricas de Proveedores
- **Complejidad:** 8 SP (Marcada como "Futuro" en las HUs).
- **Tareas:**
  - Métricas de cumplimiento de plazos de entrega (`plazo_entrega_dias` vs. fecha real de recepción).
  - Volumen histórico de compras por proveedor.

---

### Frontend, Calidad y Mantenimiento

#### 8. Sincronización y Limpieza de Carpetas de Interfaz
- **Situación actual:** Existen dos carpetas:
  - `backend/public/Interfaz/` (la versión activa que sirve el servidor).
  - `Interfaz/` (en la raíz del repositorio).
- **Tareas:**
  - Mantener sincronizadas ambas carpetas o definir `backend/public/Interfaz/` como la única fuente de verdad mediante scripts o enlaces relativos.

#### 9. Unificación de Tema Oscuro y Responsive (según `TODO.md`)
- **Tareas:**
  - Verificar que el interruptor de tema oscuro almacene la misma clave en `localStorage` (`theme` o `erp-theme`) en todas las pantallas.
  - Revisar el comportamiento del menú colapsable en celulares y tablets.

#### 10. Suite de Tests Automatizados (PHPUnit)
- **Tareas:**
  - Crear pruebas funcionales para los endpoints principales:
    - `ProductosTest.php` (CRUD, búsqueda, aumentos masivos).
    - `StockTest.php` (movimientos, alertas, lotes).
    - `OrdenesCompraTest.php` (ciclo completo de vida de órdenes: creación, envío, recepción y cancelación).
    - `ComprasTest.php` (recepciones parciales y totales).
