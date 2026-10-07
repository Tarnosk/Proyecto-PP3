A partir de los registros reales del archivo **`Organizacion Grupo 3 (6 de octubre).xlsx`**, el trabajo de los **Sprints 2 y 3** se dividió en 3 sub-grupos de desarrollo en pareja, complementado por sesiones grupales para arquitectura y documentación de calidad.

A continuación tienes la **estructura de la presentación y el guion exacto de lo que debe presentar y decir cada uno de los 6 integrantes**, diseñado para una exposición fluida y profesional de aproximadamente **12 a 15 minutos en total** (~2 minutos por integrante).

---

## Estructura General de la Exposición

```
1. Dario Tarnoski   ──► Apertura, visión de Sprint 2 y núcleo de Movimientos de Stock
2. Franco Ortelli   ──► Disponibilidad para Ventas, Reglas de Bloqueo y Ajustes de Inventario
3. Tomás Giraudo    ──► Módulo Proveedores (CRUD, CUIT único) y Gestión de Lotes
4. Gino Martellini  ──► Asociación Producto-Proveedor, Alertas de Caducidad y Plazos
5. Santiago Gomis   ──► Unidades/Equivalencias complejas, Devoluciones y Consultas de Vencimiento
6. Francisco Calvo  ──► Alertas de Stock Mínimo, Filtros de Stock, Documentación y Cierre hacia Sprint 4
```

---

## Guion Detallado por Integrante

### 1. Darío Tarnoski (Apertura y Núcleo de Movimientos de Stock)

* **Rol en el equipo:** Coordinador / Sub-grupo con Franco Ortelli.
* **Issues a cargo que presenta:**
  * Contexto de la división del Sprint 2 y 3.
  * **Issue #82 (S03):** Registro de Ingreso de Mercadería (*2h 25m*).
  * **Issue #72 (S12):** Historial de Movimientos de Stock / Kardex (*2h 50m*).
* **Qué mostrar en pantalla:**
  * Pantalla de **Stock** ([stock.html](file:///c:/Users/dario/Desktop/Proyecto/backend/public/Interfaz/stock.html)), botón *"Nuevo Ingreso"* y el modal *"Historial de Movimientos"* de un producto.
* **Qué debe decir:**
  > *"Buenas tardes a todos. En los Sprints 2 y 3 el Grupo 3 nos organizamos en tres sub-equipos de trabajo en pareja para abordar dos grandes frentes: el control de stock en tiempo real y el ciclo completo de proveedores y compras.*
  > 
  > *Junto con Franco nos encargamos del motor transaccional de movimientos en el backend. En el Sprint 2 implementamos la **Issue #82 (Ingreso de Mercadería - S03)**, garantizando que cada entrada de stock impacte atómicamente en la base de datos dentro de una transacción ACID, validando proveedores activos y bloqueando fechas futuras.*
  > 
  > *Luego, en el Sprint 3, cerramos la **Issue #72 (S12)** construyendo el **Kardex o Historial de Movimientos**. Cada operación —sea ingreso, venta, ajuste o devolución— deja una huella inmutable con fecha, usuario responsable, motivo y cantidad convertida a la unidad base, permitiendo una trazabilidad completa del inventario."*

---

### 2. Franco Ortelli (Reglas de Disponibilidad, Ventas y Ajustes)

* **Rol en el equipo:** Sub-grupo con Darío Tarnoski.
* **Issues a cargo que presenta:**
  * **Issue #81 (S02):** Consulta de Disponibilidad para Ventas (*2h 00m*).
  * **Issue #83 (S04):** Actualización Automática por Venta (*0h 25m* - In Review/Integración G4).
  * **Issue #85 (S06):** Ajustes Manuales de Inventario (*2h 00m*).
  * **Issue #78 (PV07):** Registro y tracking de plazos de entrega de proveedores (*1h 40m*).
* **Qué mostrar en pantalla:**
  * Botón *"Ajuste Stock"* en [stock.html](file:///c:/Users/dario/Desktop/Proyecto/backend/public/Interfaz/stock.html) mostrando los tipos (rotura, sobrante, merma) con motivo obligatorio.
* **Qué debe decir:**
  > *"Continuando con la parte operativa de stock, trabajamos en la **Issue #81 (S02)** creando los endpoints que consumen los vendedores para consultar stock disponible en tiempo real, distinguiendo stock físico de compromisos.*
  > 
  > *Para la **Issue #83 (S04)**, diseñamos el mecanismo que descuenta stock inmediatamente cuando se confirma una venta. Una regla crítica que protegemos a nivel de base de datos y de servicio es la restricción `CHECK chk_producto_stock_no_negativo`: el sistema jamás permite que una venta o ajuste deje el stock en negativo, disparando una excepción transaccional si no hay disponibilidad.*
  > 
  > *Además, completamos la **Issue #85 (S06)** para Ajustes Manuales por mermas o roturas físicas exigiendo justificación obligatoria, y en el Sprint 3 la **Issue #78 (PV07)** para registrar y hacer seguimiento del plazo promedio de entrega prometido por cada proveedor."*

---

### 3. Tomás Giraudo (Módulo Proveedores y Trazabilidad de Lotes)

* **Rol en el equipo:** Sub-grupo con Gino Martellini.
* **Issues a cargo que presenta:**
  * **Issue #64 (PV01):** Registrar Proveedores (*2h 10m*).
  * **Issue #65 (PV02):** Consultar Información de Proveedores (*0h 40m*).
  * **Issue #66 (PV03):** Editar Información de Proveedores (*1h 45m*).
  * **Issue #67 (PV04):** Desactivar Proveedores (*0h 35m*).
  * **Issue #75 (S09):** Gestión de Lotes y Vencimientos (*2h 15m*).
* **Qué mostrar en pantalla:**
  * Pantalla de **Proveedores** ([proveedores.html](file:///c:/Users/dario/Desktop/Proyecto/backend/public/Interfaz/proveedores.html)): alta de proveedor con CUIT, edición, búsqueda y botón de desactivar/activar.
* **Qué debe decir:**
  > *"Con Gino asumimos la responsabilidad integral del módulo de Proveedores en el Sprint 2 y la trazabilidad de lotes en el Sprint 3.*
  > 
  > *En el Sprint 2 desarrollamos el ciclo completo de proveedores (**Issues #64 a #67**): alta, consulta, edición y baja lógica. Tuvimos especial cuidado en las restricciones de negocio: el CUIT está validado en formato y unicidad para evitar duplicados en el padrón, y la desactivación es lógica mediante un estado `inactivo`, impidiendo que se le emitan compras u órdenes a proveedores dados de baja pero preservando intacto todo su historial contable.*
  > 
  > *Ya en el Sprint 3 implementamos la **Issue #75 (S09)**, permitiendo que cada ingreso de mercadería pueda asociarse a un número de lote y fecha de vencimiento (`LOTE`), desacoplándolo de referencias circulares como nos había observado el docente."*

---

### 4. Gino Martellini (Catálogo Proveedor-Producto y Alertas de Vencimiento)

* **Rol en el equipo:** Sub-grupo con Tomás Giraudo.
* **Issues a cargo que presenta:**
  * **Issue #76 (PV05):** Asociar Productos a Proveedores (*2h 00m*).
  * **Issue #74 (S10):** Alerta de Productos Próximos a Vencer (*1h 45m*).
* **Qué mostrar en pantalla:**
  * Modal *"Asociar Productos al Proveedor"* en `proveedores.html` y la vista de **Vencimientos** (`/vencimientos`).
* **Qué debe decir:**
  > *"Completando el módulo de proveedores, en el Sprint 2 desarrollamos la **Issue #76 (PV05)**, que resuelve la relación muchos a muchos entre productos y proveedores a través de la tabla `PRODUCTO_PROVEEDOR`.*
  > 
  > *Esto permite registrar qué proveedor abastece cada producto, definir el proveedor principal y fijar precios pactados, lo que luego sirvió de base fundamental para el módulo de Pedidos de Compra.*
  > 
  > *En el Sprint 3 desarrollamos la lógica de la **Issue #74 (S10)**: un servicio que evalúa automáticamente la diferencia entre la fecha actual y la fecha de caducidad del lote según los días configurados en el producto (`dias_alerta_vencimiento`), catalogando los lotes en vigentes, próximos a vencer o vencidos para disparar alertas tempranas al encargado de depósito."*

---

### 5. Santiago Gomis (Unidades de Medida, Devoluciones y Auditoría)

* **Rol en el equipo:** Sub-grupo con Francisco Calvo.
* **Issues a cargo que presenta:**
  * **Issue #84 (S05):** Registro de Devoluciones (*1h 55m*).
  * **Issue #87 (S08):** Gestión de Unidades y Equivalencias (*3h 15m*).
  * **Issue #73 (S11):** Consultar Productos Próximos a Vencer (*1h 30m*).
* **Qué mostrar en pantalla:**
  * Modal *"Unidades"* en `stock.html` (mostrando unidad suelta y bultos como Caja x12 con factor de conversión) y el reporte paginado en `/vencimientos`.
* **Qué debe decir:**
  > *"Con Francisco nos enfocamos en requerimientos de alta complejidad algorítmica y reglas de fraccionamiento.*
  > 
  > *En el Sprint 2 abordamos la **Issue #87 (S08 - 8 Story Points)**, una de las más demandantes: la Distribuidora compra y vende tanto por unidad suelta como en cajas cerradas o packs. Desarrollamos la entidad `UNIDAD_MEDIDA` y el motor de conversión en `StockService`, asegurando que el stock siempre se persista en unidades base indivisibles, mientras que la interfaz permite operar en bultos con cálculo automático.*
  > 
  > *También cubrimos la **Issue #84 (S05)** para recepcionar devoluciones de clientes con reingreso al inventario, y en el Sprint 3 la **Issue #73 (S11)**, construyendo la pantalla dedicada de consulta y ordenamiento de lotes próximos a vencer con KPIs de criticidad."*

---

### 6. Francisco Calvo (Alertas de Stock, Filtros y Documentación de Calidad)

* **Rol en el equipo:** Sub-grupo con Santiago Gomis.
* **Issues a cargo que presenta:**
  * **Issue #86 (S07):** Alertas de Stock Mínimo (*1h 30m*).
  * **Issue #69 (S15):** Filtrado por Nivel de Stock (*1h 10m*).
  * **Issues #70 y #71 (S13 / S14):** Análisis de Conteo Físico y Ubicaciones (*In Review*).
  * **Daily Grupal:** Plan de pruebas de validación y criterios de accesibilidad (*1h 45m*).
* **Qué mostrar en pantalla:**
  * Filtros de stock por estado (normal, bajo, crítico) y etiquetas visuales de nivel en `stock.html`, más los documentos `.md` de calidad.
* **Qué debe decir:**
  > *"Para cerrar el circuito preventivo en el Sprint 2 desarrollamos la **Issue #86 (S07)**, permitiendo configurar un umbral de `stock_minimo` por producto que clasifica dinámicamente el estado en crítico, bajo o normal.*
  > 
  > *En el Sprint 3 trabajamos en la **Issue #69 (S15)** para filtrar la grilla principal según estos estados, y dejamos planteado el diseño de las tablas de conteo físico (**S13**) y ubicaciones en depósito (**S14**).*
  > 
  > *Por último, de forma transversal los 6 integrantes dedicamos una sesión completa a confeccionar el **Plan de Pruebas de Verificación y Validación** y la matriz de **Criterios de Usabilidad y Accesibilidad**, validando contrastes WCAG, navegación por teclado y diseño responsive, dejando el sistema listo, probado y sincronizado para el Sprint 4."*

---

## Consejos Clave para la Presentación

1. **Tener el servidor corriendo:** El backend debe estar activo en `http://127.0.0.1:8000` con la base de datos `distribuidorapyb` iniciada en MySQL/XAMPP.
2. **Pestañas del navegador abiertas de antemano:**
   - Pestaña 1: [http://127.0.0.1:8000/Interfaz/stock.html](http://127.0.0.1:8000/Interfaz/stock.html)
   - Pestaña 2: [http://127.0.0.1:8000/Interfaz/proveedores.html](http://127.0.0.1:8000/Interfaz/proveedores.html)
   - Pestaña 3: [http://127.0.0.1:8000/vencimientos](http://127.0.0.1:8000/vencimientos)
   - Pestaña 4: [http://127.0.0.1:8000/Interfaz/pedido_compra.html](http://127.0.0.1:8000/Interfaz/pedido_compra.html) (mostrando el timeline y estados de PC06 que dejamos listos).
3. **Resaltar el trabajo en equipo:** Destacar que se respetaron los principios del docente: la base de datos canónica no se corrompió, se respetó la integridad transaccional y se mantuvo la trazabilidad en cada commit.