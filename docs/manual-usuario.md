# Manual de Usuario y Guía Operativa — ERP Juan23 (Distribuidora PyB)

Este documento es una guía funcional paso a paso orientada al usuario final (administrativo, encargado de depósito, comprador o tesorero), describiendo la operación cotidiana del sistema en sus circuitos comerciales y logísticos.

---

## 1. Acceso al Sistema y Roles de Usuario

El sistema cuenta con control de acceso basado en roles (**RBAC**). Cada perfil tiene permisos específicos sobre las opciones del menú lateral:

| Rol | Perfil de Usuario | Alcance y Permisos Principales |
| :--- | :--- | :--- |
| **Administrativo / Admin** | Supervisor general | Acceso irrestricto: gestión de catálogo, compras, stock, proveedores, parámetros y auditoría. |
| **Comprador** | Gestión de abastecimiento | Generación de órdenes de compra, análisis de reposición, relación con proveedores y precios pactados. |
| **Encargado de Depósito** | Operador de stock | Recepción física de mercadería, asignación de lotes, conteos físicos a ciegas, mermas y devoluciones. |
| **Contador / Tesorero** | Gestión financiera | Cuentas corrientes de proveedores, registro de pagos, comprobantes fiscales (Facturas A, B, C) y reportes. |

---

## 2. Circuito 1: Catálogo de Productos y Precios

### 2.1. Alta de un Nuevo Producto (P01)
1. Ingrese al módulo **Inventario & Stock** desde la barra lateral.
2. Haga clic en el botón **`+ Nuevo Producto`**.
3. Complete el formulario modal:
   - **Código / SKU:** Debe ser único (ej. `BEB-AGUA-600`).
   - **Nombre y Descripción:** Detalle comercial del artículo.
   - **Categoría y Marca:** Seleccione de las listas desplegables.
   - **Precios:** Ingrese el *Precio Unitario Base* y opcionalmente precios diferenciados (*Mayorista* y *Minorista*).
   - **Parámetros de Stock:** Defina el *Stock Inicial* y el *Stock Mínimo* (umbral que dispara alertas amarillas/rojas).
4. Presione **`Guardar Producto`**. El sistema validará la unicidad del código y creará el registro con auditoría de fecha y usuario.

### 2.2. Aumentos Masivos de Precios con Redondeo (P07)
Permite actualizar rápidamente listas de precios ante variaciones inflacionarias o cambios de cotización:
1. En la vista de Inventario, presione **`Aumento Masivo`**.
2. Indique el **Porcentaje de Aumento** (ej. `12.5%`).
3. Filtre el alcance (puede aplicarse a *Todos*, a una *Categoría específica* o a una *Marca*).
4. Seleccione la **Regla de Redondeo**:
   - `Sin redondeo`: Mantiene decimales exactos.
   - `Redondeo comercial estándar`: Al entero más próximo.
   - `Hacia arriba (Ceil)`: Redondea siempre a favor del negocio.
   - `Decenas / Centenas`: Útil para facilitar el cobro en efectivo.
5. Confirme la operación. El sistema recalculará los precios y guardará una entrada en el **Historial de Precios** (`historial_precio`).

---

## 3. Circuito 2: Gestión de Stock, Lotes y Trazabilidad

### 3.1. Consulta de Inventario y Semáforo de Stock (S01 / S07)
La tabla principal clasifica los productos visualmente:
- 🟢 **Normal:** Stock actual superior al stock mínimo.
- 🟡 **Stock Bajo:** Stock actual igual o menor al mínimo (requiere atención).
- 🔴 **Sin Stock:** Stock en 0 unidades (crítico).
Use los filtros por categoría, estado o rango numérico (`Desde` / `Hasta`) para acotar el listado.

### 3.2. Devoluciones de Clientes o a Proveedores (S05)
1. Presione el botón **`Devolución`** en la barra superior o en la fila del producto.
2. Seleccione el **Tipo de Devolución**:
   - **Devolución de Cliente:** La mercadería reingresa al depósito. Incrementa el stock general y el stock del lote asignado.
   - **Devolución a Proveedor:** Se devuelve mercadería defectuosa o no solicitada. Descuenta del stock disponible y del lote seleccionado.
3. Ingrese la **Cantidad** y seleccione la **Unidad de Medida** (si devuelve cajas, el sistema calculará automáticamente las unidades individuales).
4. **Asignación Obligatoria de Lote:** Seleccione el lote del cual proviene o al cual se imputa la mercadería. Esto garantiza la trazabilidad alimentaria/sanitaria.
5. Ingrese el **Motivo** detallado y confirme. El movimiento quedará grabado en el Kardex.

### 3.3. Sesión de Conteo Físico a Ciegas (S13)
Herramienta para auditar el depósito sin sesgos del personal:
1. Haga clic en **`Iniciar Conteo Físico`**.
2. El sistema abrirá una nueva sesión en estado `abierta`.
3. El operario recorre el almacén e ingresa producto por producto la **Cantidad Contada** real que observa en la estantería (la pantalla no revela el stock teórico del sistema para evitar que el operario copie el número).
4. Al terminar el relevamiento, presione **`Finalizar Conteo`**.
5. El sistema compara automáticamente:
   - Si `Cantidad Contada` > `Stock Sistema` ➔ Genera un ajuste por **Sobrante**.
   - Si `Cantidad Contada` < `Stock Sistema` ➔ Genera un ajuste por **Faltante / Merma**.
6. El inventario se actualiza y la sesión queda archivada con el balance de discrepancias.

### 3.4. Control de Vencimientos y Partidas (S10 / S11)
Desde el módulo **Vencimientos**:
- Consulte el semáforo de partidas: lotes vencidos, lotes que caducan en los próximos 15/30 días y lotes en regla.
- Facilita la aplicación del principio **FEFO** (*First Expired, First Out* - lo primero que vence es lo primero que se despacha).

---

## 4. Circuito 3: Proveedores y Precios de Compra

### 4.1. Alta de Proveedores con Validación Fiscal (PV01)
1. Acceda al módulo **Proveedores**.
2. Presione **`Nuevo Proveedor`**.
3. Ingrese el **CUIT** (el sistema valida matemáticamente el dígito verificador mediante el algoritmo Módulo 11 de AFIP/ARCA).
4. Complete Razón Social, Teléfono, Email y Domicilio.
5. Presione **`Guardar`**.

### 4.2. Catálogo de Artículos por Proveedor y Cotizaciones (PV05 / PV06)
1. En la ficha del proveedor, presione **`Ver Catálogo`**.
2. Asocie los productos que esta empresa nos provee, indicando el **Precio de Costo Pactado** y el código interno del proveedor.
3. Puede consultar la pestaña **`Historial de Precios`** para ver el gráfico y tabla de cómo ha aumentado el costo de cada producto a lo largo del tiempo.

---

## 5. Circuito 4: Abastecimiento y Órdenes de Compra

### 5.1. Sugerencias Automáticas de Reposición (PC07)
1. Ingrese al módulo **Dashboard de Reposición** o presione **`Ver Sugerencias`**.
2. El motor algorítmico lista los productos cuyo stock proyectado no cubre la demanda mínima.
3. Presione **`Generar Orden de Compra`** sobre la sugerencia: el sistema precargará una orden con el proveedor principal y la cantidad óptima de compra calculada.

### 5.2. Ciclo de Vida de una Orden de Compra (PC01 – PC06)
1. **Creación:** Cree la orden indicando ítems, cantidades y fecha de entrega pactada. Nace en estado `pendiente`.
2. **Envío al Proveedor:** Presione **`Marcar como Enviada`**. El estado avanza a `enviada` en la línea de tiempo.
3. **Recepción en Depósito:** Al llegar el camión, presione **`Recepcionar Mercadería`**:
   - Puede recepcionarse el total o marcar una recepción parcial.
   - El sistema solicita el número de factura/remito del proveedor y el lote/vencimiento.
   - Al guardar, el stock físico se incrementa de inmediato en el Kardex y la orden pasa a `recibida_parcialmente` o `completada`.
4. **Cancelación:** Si el proveedor no dispone de stock, la orden puede cancelarse ingresando el motivo de auditoría correspondiente.
