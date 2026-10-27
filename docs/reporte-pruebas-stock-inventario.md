# Reporte de Pruebas y Criterios de Aceptación — Módulos Stock y Productos

**Alcance:** Historias de Usuario de Productos (`P01` a `P08`) y Stock e Inventario (`S01` a `S16`).  
**Entorno de Ejecución:** Laravel 12.x / PHPUnit 11 / Base de datos MySQL `distribuidorapyb`.  
**Suites de Prueba:** `tests/Feature/UserStoriesFrontendTest.php` y `tests/Feature/ConteoFisicoTest.php`.  
**Dictamen General:** **100% Aprobado (0 fallos, 0 errores).**

---

## 1. Módulo Productos (P01 – P08)

### P01 — Alta de Producto
* **Criterios Verificados:**
  - Código único obligatorio (rechaza duplicados con error `400 Bad Request`).
  - Descripción, categoría existente y marca existente obligatorias.
  - Precio unitario positivo (`min:0`).
  - Creación transaccional con stock inicial embebido y registro en `log_auditoria`.
* **Método de Prueba:** `UserStoriesFrontendTest::test_p01_creacion_producto`
* **Resultado:** **PASS (201 Created)**

### P02 — Modificación de Producto
* **Criterios Verificados:**
  - Modificación de descripción, precios mayoristas/minoristas y stock mínimo.
  - Registro de usuario que modificó y timestamp en `fecha_modificacion`.
  - Captura de snapshot en `log_auditoria` con valores anteriores y nuevos.
* **Método de Prueba:** `UserStoriesFrontendTest::test_p02_edicion_producto`
* **Resultado:** **PASS (200 OK)**

### P03 / P04 — Desactivación Lógica y Reactivación
* **Criterios Verificados:**
  - Desactivación lógica estableciendo `estado = 'inactivo'` sin borrar el registro físico.
  - Un producto inactivo no aparece en búsquedas por defecto ni puede ser vendido.
  - Reactivación restituye `estado = 'activo'` y `fecha_desactivacion = null`.
* **Métodos de Prueba:** `test_p03_desactivacion_producto` y `test_p04_reactivacion_producto`
* **Resultado:** **PASS (200 OK)**

### P05 — Búsqueda Flexible y Filtros Combinados
* **Criterios Verificados:**
  - Búsqueda por subcadena de código o nombre (case-insensitive).
  - Filtrado por `categoria_id`, `marca_id` y estado de producto.
  - Paginación y ordenamiento descendente por defecto.
* **Método de Prueba:** `UserStoriesFrontendTest::test_p05_busqueda_filtros`
* **Resultado:** **PASS (200 OK)**

### P07 — Aumento Masivo de Precios con Reglas de Redondeo
* **Criterios Verificados:**
  - Aplicación de porcentaje positivo a un universo filtrado por categoría o marca.
  - Validación de reglas de redondeo: `sin_redondeo`, `redondeo` entero, `ceil`, `floor`.
  - Inserción automática de registros en `historial_precio` por cada producto afectado.
* **Método de Prueba:** `UserStoriesFrontendTest::test_p07_aumento_masivo_precios`
* **Resultado:** **PASS (200 OK)**

### P08 — Historial de Cambios de Precio
* **Criterios Verificados:**
  - Consulta cronológica de precios anteriores y posteriores.
  - Exposición del usuario responsable y motivo del cambio.
* **Método de Prueba:** `UserStoriesFrontendTest::test_p08_historial_precios`
* **Resultado:** **PASS (200 OK)**

---

## 2. Módulo Stock e Inventario (S01 – S16)

### S01 / S02 — Consulta de Stock y Disponibilidad
* **Criterios Verificados:**
  - Consulta de stock disponible consolidado en tiempo real.
  - Cálculo de semáforo de alerta: `normal`, `bajo` (stock <= stock_minimo) y `sin_stock` (stock = 0).
* **Método de Prueba:** `test_s01_consulta_stock` y `test_s02_disponibilidad`
* **Resultado:** **PASS (200 OK)**

### S03 / S04 — Ingreso Manual y Salida por Venta
* **Criterios Verificados:**
  - El ingreso manual incrementa existencias y crea movimiento de tipo `ingreso` en Kardex.
  - La venta descuenta stock de forma atómica dentro de una transacción.
  - Rechazo de venta con error `400` si la cantidad solicitada supera el stock disponible.
* **Métodos de Prueba:** `test_s03_ingreso_mercaderia` y `test_s04_descuento_venta`
* **Resultado:** **PASS (201 Created / 400 Bad Request validado)**

### S05 — Registro de Devoluciones con Lote Obligatorio
* **Criterios Verificados:**
  - Devolución de cliente: suma al stock de producto y al lote asignado.
  - Devolución a proveedor: resta stock y valida que el lote tenga existencias suficientes.
  - Rechazo con error `400` si no se especifica lote (garantía de trazabilidad).
* **Método de Prueba:** `UserStoriesFrontendTest::test_s05_registro_devoluciones`
* **Resultado:** **PASS (201 Created)**

### S06 — Ajustes de Stock (Mermas y Sobrantes)
* **Criterios Verificados:**
  - Registro de ajuste con motivo obligatorio (rotura, vencimiento, desvío).
  - Actualización directa de existencias y asentamiento en Kardex.
* **Método de Prueba:** `UserStoriesFrontendTest::test_s06_ajustes_stock`
* **Resultado:** **PASS (201 Created)**

### S07 — Alertas de Stock Mínimo
* **Criterios Verificados:**
  - Endpoint `GET /api/v1/stock/alertas` lista productos en estado crítico.
  - Configuración dinámica del umbral `stock_minimo`.
* **Método de Prueba:** `UserStoriesFrontendTest::test_s07_alertas_stock`
* **Resultado:** **PASS (200 OK)**

### S08 — Unidades de Medida y Factores de Conversión
* **Criterios Verificados:**
  - Creación de equivalencias (ej. 1 Caja = 12 Unidades).
  - Conversión automática a unidad base antes de impactar el Kardex.
* **Método de Prueba:** `UserStoriesFrontendTest::test_s08_unidades_equivalencias`
* **Resultado:** **PASS (200 OK)**

### S09 / S10 / S11 — Lotes, Vencimientos y Criticidad
* **Criterios Verificados:**
  - Creación de lotes con número de partida y fecha de vencimiento.
  - Consulta de lotes próximos a vencer clasificados por rangos de días (<15 días, <30 días, vencidos).
  - Descuento selectivo por lote para cumplimiento de norma FEFO.
* **Métodos de Prueba:** `test_s09_gestion_lotes`, `test_s10_alertas_vencimiento` y `test_s11_consulta_vencimientos`
* **Resultado:** **PASS (200 OK / 201 Created)**

### S12 — Kardex Histórico de Movimientos
* **Criterios Verificados:**
  - Consulta cronológica inmutable de movimientos por producto.
  - Exposición de stock anterior, cantidad, stock posterior, tipo, usuario y lote.
* **Método de Prueba:** `UserStoriesFrontendTest::test_s12_historial_kardex`
* **Resultado:** **PASS (200 OK)**

### S13 — Conteo Físico a Ciegas
* **Criterios Verificados:**
  - Apertura de sesión de conteo en estado `abierta`.
  - Carga de cantidades físicas contadas por ítem sin revelar stock de sistema.
  - Cierre de sesión con cálculo automático de discrepancias (`contado - sistema`).
  - Generación automática de ajustes compensatorios de inventario en Kardex.
* **Suite Específica:** `tests/Feature/ConteoFisicoTest.php`
  - `test_iniciar_sesion_conteo`: PASS
  - `test_registrar_items_en_sesion`: PASS
  - `test_finalizar_sesion_aplica_ajuste_stock`: PASS
* **Resultado:** **PASS (100%)**

### S14 — Gestión de Ubicaciones en Depósito
* **Criterios Verificados:**
  - Alta de ubicaciones físicas compuestas (pasillo, estantería, nivel).
  - Asignación y desasignación de productos a ubicaciones.
* **Método de Prueba:** `UserStoriesFrontendTest::test_s14_gestion_ubicaciones`
* **Resultado:** **PASS (200 OK / 201 Created)**

### S15 / S16 — Filtros por Rango y Bultos Cerrados
* **Criterios Verificados:**
  - Filtros `?stock_min=X&stock_max=Y` en endpoint general.
  - Campos de bulto y unidades por bulto opcionales en catálogo.
* **Métodos de Prueba:** `test_s15_filtros_rango_stock` y `test_s16_formato_bulto`
* **Resultado:** **PASS (200 OK)**
