# Reporte de Pruebas y Criterios de Aceptación — Módulos Proveedores y Compras

**Alcance:** Historias de Usuario de Proveedores (`PV01` a `PV08`) y Compras (`C01` a `C09`).  
**Entorno de Ejecución:** Laravel 12.x / PHPUnit 11 / Base de datos MySQL `distribuidorapyb`.  
**Suites de Prueba:** `tests/Feature/UserStoriesFrontendTest.php` y `tests/Feature/HistorialPrecioProveedorTest.php`.  
**Dictamen General:** **100% Aprobado (0 fallos, 0 errores).**

---

## 1. Módulo Proveedores (PV01 – PV08)

### PV01 — Alta de Proveedor y Validación Fiscal
* **Criterios Verificados:**
  - Validación de CUIT argentino mediante algoritmo matemático Módulo 11 (rechaza números con dígito verificador inválido con error `400`).
  - Unicidad del CUIT en la base de datos.
  - Razón social obligatoria.
* **Método de Prueba:** `UserStoriesFrontendTest::test_pv01_alta_proveedor`
* **Resultado:** **PASS (201 Created / 400 Bad Request ante CUIT inválido)**

### PV02 — Edición de Proveedor
* **Criterios Verificados:**
  - Modificación de teléfono, email comercial y domicilio fiscal.
  - Registro de auditoría con valores anteriores y nuevos.
* **Método de Prueba:** `UserStoriesFrontendTest::test_pv02_edicion_proveedor`
* **Resultado:** **PASS (200 OK)**

### PV03 / PV04 — Desactivación Lógica y Reactivación
* **Criterios Verificados:**
  - Desactivación lógica preservando historial de compras previas (`estado = 'inactivo'`).
  - Un proveedor inactivo no puede recibir nuevas órdenes de compra.
  - Reactivación restaura la capacidad operativa (`estado = 'activo'`).
* **Métodos de Prueba:** `test_pv03_desactivacion` y `test_pv04_reactivacion`
* **Resultado:** **PASS (200 OK)**

### PV05 — Asociación de Catálogo de Productos
* **Criterios Verificados:**
  - Vinculación de productos al proveedor con precio de costo acordado.
  - Asignación de código de producto según catálogo del proveedor.
  - Determinación de proveedor principal (`es_principal = 1`).
* **Método de Prueba:** `UserStoriesFrontendTest::test_pv05_asociar_productos`
* **Resultado:** **PASS (200 OK / 201 Created)**

### PV06 — Historial de Precios de Compra por Proveedor
* **Criterios Verificados:**
  - Registro de variaciones de cotización al recepcionar una compra o por ajuste manual de lista.
  - Consulta de evolución histórica de precios pactados con proveedor.
  - Cálculo del porcentaje de aumento entre períodos.
* **Suite Específica:** `tests/Feature/HistorialPrecioProveedorTest.php`
  - `test_registra_historial_precio_al_modificar_costo`: PASS
  - `test_consulta_historial_por_proveedor_y_producto`: PASS
* **Resultado:** **PASS (100%)**

### PV07 / PV08 — Plazos de Entrega y Rendimiento
* **Criterios Verificados:**
  - Configuración de plazo estimado de entrega en días (`plazo_entrega_dias`).
  - Registro de historial de plazos y comparación con fecha real de recepción.
* **Métodos de Prueba:** `test_pv07_plazos_entrega` y `test_pv08_metricas_proveedor`
* **Resultado:** **PASS (200 OK)**

---

## 2. Módulo Compras (C01 – C09)

### C01 — Registro de Compra
* **Criterios Verificados:**
  - Creación de compra directa o asociada a una orden de compra previa.
  - Tipos de comprobante admitidos: Facturas A, B, C y Remito.
  - Formato estricto de número de comprobante (4 a 8 dígitos numéricos).
* **Método de Prueba:** `UserStoriesFrontendTest::test_c01_registro_compra`
* **Resultado:** **PASS (201 Created)**

### C02 — Recepción Total e Impacto en Inventario
* **Criterios Verificados:**
  - Al registrar la recepción de la compra, el stock físico se incrementa automáticamente.
  - Se genera un registro de tipo `recepcion` en el Kardex.
  - Si la compra provenía de una orden de compra, el estado de la orden avanza a `completada`.
* **Método de Prueba:** `UserStoriesFrontendTest::test_c02_recepcion_total`
* **Resultado:** **PASS (201 Created)**

### C03 — Recepción Parcial de Mercadería
* **Criterios Verificados:**
  - Permite recibir una cantidad inferior a la solicitada en la factura/orden.
  - El stock se incrementa únicamente por las unidades efectivamente recibidas.
  - La orden de compra queda en estado `recibida_parcialmente` y mantiene el remanente pendiente.
* **Método de Prueba:** `UserStoriesFrontendTest::test_c03_recepcion_parcial`
* **Resultado:** **PASS (201 Created)**

### C04 — Consulta de Comprobantes de Compra
* **Criterios Verificados:**
  - Listado con filtros por proveedor, tipo de comprobante y estado de pago.
  - Desglose de totales gravados, IVA e importe final.
* **Método de Prueba:** `UserStoriesFrontendTest::test_c04_comprobantes_compra`
* **Resultado:** **PASS (200 OK)**

### C05 — Impresión de Comprobante Comercial
* **Criterios Verificados:**
  - Renderizado de vista para impresión en hoja A4 en escala de grises.
  - Inclusión de letra fiscal (A, B, C), razón social, CUIT del proveedor y detalle de ítems.
* **Método de Prueba:** `UserStoriesFrontendTest::test_c05_impresion_comprobante`
* **Resultado:** **PASS (200 OK)**

### C06 — Asignación de Lote en la Recepción
* **Criterios Verificados:**
  - En el momento de la recepción física, se captura el número de lote y fecha de vencimiento del proveedor.
  - Se crea o actualiza la partida en la tabla `lote` y se vincula al movimiento de stock.
* **Método de Prueba:** `UserStoriesFrontendTest::test_c06_lote_en_recepcion`
* **Resultado:** **PASS (201 Created)**

### C07 / C08 — Pagos a Proveedores y Medios Diferidos
* **Criterios Verificados:**
  - Registro de pagos imputados a facturas de compra.
  - Soporte de medios de pago: efectivo, transferencia, cheque y e-cheq.
  - En medios diferidos (cheque/echeq), registro de número de cheque y fecha de cobro.
  - Actualización automática del estado de pago de la compra (`pendiente` -> `pagado_parcial` -> `pagado_total`).
* **Métodos de Prueba:** `test_c07_registro_pagos` y `test_c08_pagos_diferidos`
* **Resultado:** **PASS (201 Created)**

### C09 — Control de Cuentas Corrientes y Deudas con Proveedores
* **Criterios Verificados:**
  - Cálculo consolidado: Total Comprado - Total Pagado = Saldo Deudor.
  - Listado de facturas pendientes de cancelación agrupadas por proveedor.
* **Método de Prueba:** `UserStoriesFrontendTest::test_c09_cuentas_corrientes`
* **Resultado:** **PASS (200 OK)**
