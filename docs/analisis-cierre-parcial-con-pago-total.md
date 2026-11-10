# Análisis de Lógica de Negocio y Contable: Tratamiento del Pago en Exceso ante Cierre de Recepción Parcial en Compras a Proveedores

**Módulo:** G3 - Compras, Proveedores y Stock  
**Fecha:** 9 de noviembre de 2026  
**Destinatarios:** Cátedra / Docente a cargo, Equipo de Desarrollo ERP  
**Estado:** Documento de Consulta y Decisión de Diseño  

---

## 1. Planteamiento del Escenario Fáctico

En una distribuidora mayorista es habitual que el ciclo de abastecimiento involucre pagos anticipados, pagos contra entrega o cancelaciones completas contra la emisión de la factura del proveedor.

El problema analizado ocurre bajo la siguiente secuencia operativa:

1. **Carga y Facturación Inicial:**  
   Se registra una compra a un proveedor por **100 unidades** a **$1.000 c/u**, totalizando un importe facturado de **$100.000**.
2. **Pago Completo:**  
   Administración cancela la totalidad de la deuda mediante uno o varios métodos de pago (transferencia bancaria, cheque/echeq, efectivo).  
   * **Importe de la Compra:** $100.000  
   * **Total Abonado:** $100.000  
   * **Saldo a Pagar:** $0 (Estado de pago: *Pagada*).
3. **Recepción Física Parcial:**  
   El transporte del proveedor entrega en depósito únicamente **60 unidades** (valuadas en **$60.000**).
4. **Acuerdo de Cierre Definitivo:**  
   El proveedor notifica que no cuenta con stock ni reingreso proyectado para las **40 unidades restantes**, o ambas partes acuerdan cancelar el remanente.
5. **Cierre de Compra con lo Recibido (`cerrarParcial`):**  
   El operador presiona *"Dar por completada con lo recibido"*, lo que recalcula la compra ajustando las cantidades facturadas a 60 unidades y el nuevo importe total a **$60.000**.

### El Conflicto Financiero y Contable
* **Mercadería efectivamente ingresada a stock:** $60.000 (60 u.).
* **Dinero efectivamente transferido al proveedor:** $100.000.
* **Descalce / Pago en Exceso:** **$40.000 a favor de la distribuidora** que actualmente quedan huérfanos o sin imputación directa en la compra si esta solo reduce su total.

---

## 2. Impacto en las Tres Dimensiones del ERP

| Dimensión | Estado Actual tras el Cierre | Diagnóstico |
| :--- | :--- | :--- |
| **Física (Depósito / Kardex)** | 60 unidades ingresadas en stock. Sin pendientes físicos. | **Correcto.** No hay distorsión en el inventario real. |
| **Comercial / Abastecimiento** | Orden de compra y Compra finalizadas como `completada`. | **Correcto.** No se esperan nuevos remitos ni camiones. |
| **Financiera / Cuenta Corriente** | Los registros de `PAGO_PROVEEDOR` suman $100.000 mientras que la compra vale $60.000. | **Inconsistente.** Hay $40.000 entregados que no corresponden a mercadería recibida. |

---

## 3. Alternativas de Solución en la Práctica Empresarial

### Alternativa 1: Generación de Saldo a Favor / Nota de Crédito *(Estándar en Distribución Mayorista)*
* **Lógica Comercial:**  
  El proveedor no restituye el dinero inmediatamente, sino que emite una **Nota de Crédito** formal (AFIP/ARCA) por los $40.000 sobrantes.
* **Tratamiento en el ERP:**  
  * La compra actual se salda y se declara libre de deuda con un costo de $60.000.
  * Los $40.000 excedentes se convierten en un **Saldo a Favor del Comprador** (Crédito con el proveedor).
  * **Mecanismos de aplicación del crédito:**
    1. **Compensación automática o manual con facturas pendientes:** Si la distribuidora le adeuda $50.000 de otra compra anterior o posterior, se aplican los $40.000 reduciendo la deuda exigible a $10.000.
    2. **Anticipo para futuras compras:** Al emitir una nueva compra a este proveedor, el sistema ofrece utilizar el crédito acumulado como medio de pago ("Uso de Saldo a Favor").
* **Ventajas:** Refleja la práctica real del 95% del comercio mayorista en Argentina, donde rara vez se pide devolución de dinero en transferencias interempresa habituales.
* **Desventajas:** Requiere que el sistema mantenga un balance de cuenta corriente de saldos a favor por proveedor.

---

### Alternativa 2: Reintegro de Dinero / Devolución Bancaria *(Cashback)*
* **Lógica Comercial:**  
  La relación comercial con el proveedor es esporádica o no se proyectan compras a corto plazo, por lo que el proveedor devuelve los $40.000 vía transferencia bancaria o en efectivo.
* **Tratamiento en el ERP:**  
  * Se registra un movimiento de **devolución de pago** (egreso negativo o contra-asiento de $40.000) vinculado a la compra.
  * El total pagado de la compra se reduce de $100.000 a $60.000, cerrando la ecuación:  
    $$\text{Importe Total} (\$60.000) = \text{Total Pagado Neto} (\$60.000)$$
* **Ventajas:** Cierra la compra sin dejar saldos pendientes de compensar en el ERP.
* **Desventajas:** Depende de que el proveedor efectivamente deposite el dinero y requiere registrar el comprobante de devolución.

---

### Alternativa 3: Bloqueo de Cierre Parcial si hay Pagos Excedentes *(Enfoque Rígido)*
* **Lógica:** El sistema prohíbe dar por completada la compra si el importe recalculado resultara menor a lo ya pagado, forzando a que primero se anulen o ajusten los pagos existentes antes de cerrar la compra.
* **Desventajas:** Bloquea la operativa de depósito. El encargado de depósito no puede cerrar la recepción física simplemente porque la administración ya pagó la factura en el banco. Separa incorrectamente la realidad física de la financiera.

---

## 4. Preguntas Clave para Definición con la Cátedra

Para definir la implementación definitiva en el proyecto final, solicitamos orientación en los siguientes puntos de criterio de evaluación:

1. **¿Alcance de la Cuenta Corriente de Proveedores en G3?**  
   * ¿El alcance de la materia exige administrar una cuenta corriente formal con balance deudores/acreedores por proveedor, o es suficiente con reflejar el **Saldo a Favor ($)** en la ficha de la compra y deducirlo del reporte global de deudas del proveedor?
2. **¿Compensación Automática vs. Compensación Explícita?**  
   * Si un proveedor tiene $40.000 a favor por una compra cerrada y a la vez tiene otra factura por $50.000 pendiente de pago:  
     * ¿El sistema debe compensar automáticamente la deuda mostrando un saldo neto de $10.000?  
     * ¿O debe requerir un botón explícito de *"Imputar saldo a favor"* para no alterar las fechas de vencimiento de las facturas individuales?
3. **¿Tratamiento de Comprobantes Imprimibles y Auditoría Fiscal?**  
   * Si la compra original tenía una Factura del proveedor por $100.000:  
     * ¿Debe el comprobante interno imprimir una advertencia exigiendo la **Nota de Crédito** por $40.000 para respaldo fiscal en el Libro de IVA Compras?

---

## 5. Propuesta de Implementación Recomendada para G3

Mientras se consensúa el criterio con el profesor, la propuesta técnica de menor acoplamiento y máxima transparencia es:

1. **Cálculo Explícito de Saldo a Favor:**  
   $$\text{saldo\_a\_favor} = \max(0.0, \; \text{total\_pagado} - \text{importe\_total})$$
2. **Badge y Estado Informativo en UI:**  
   * Si $\text{saldo\_a\_favor} > 0$, la compra no figura como "pagada común", sino con la etiqueta:  
     `PAGADA CON SALDO A FAVOR ($40.000)` en color azul o ámbar.
3. **Pie del Comprobante Impreso:**  
   Aclarar en el documento impreso:  
   * *Total Factura Recalculado:* $60.000  
   * *Total Abonado Original:* $100.000  
   * *Crédito / Saldo a Favor a Reclamar o Compensar:* $40.000  
   * *Leyenda:* *"Pendiente de compensación en futuras compras o emisión de Nota de Crédito por parte del proveedor."*
4. **Reporte de Deudas a Proveedores:**  
   En la pantalla de *"Deudas a Proveedores"*, mostrar la columna *"Saldo a Favor / Créditos"* para que el encargado de pagos visualice inmediatamente si un proveedor al que se le va a pagar tiene saldos a favor pendientes de aplicar.
