# Glosario de Términos del Dominio — ERP Juan23 (Distribuidora PyB)

Este glosario define los conceptos comerciales, logísticos, técnicos y fiscales utilizados en el diseño, desarrollo y operación del sistema ERP.

---

## 1. Conceptos de Stock, Depósito y Trazabilidad

### Kardex
Libro de registro cronológico e inmutable donde se asientan todos los movimientos que modifican las existencias físicas de un producto. Cada fila registra la fecha, cantidad, tipo de operación (ingreso, venta, merma, devolución), usuario responsable y el saldo resultante.

### Lote (Partida)
Conjunto de unidades de un producto elaboradas o recibidas bajo condiciones homogéneas de fabricación. Cada lote posee un identificador único asignado por el proveedor y una fecha de caducidad.

### FEFO (*First Expired, First Out*)
Principio logístico y sanitario que establece que la mercadería cuya fecha de vencimiento es más próxima debe ser la primera en despacharse al cliente, minimizando las pérdidas por mercadería caducada.

### FIFO (*First In, First Out*)
Método de valuación y rotación donde los artículos más antiguos en ingresar son los primeros en salir. En el sistema coexiste con FEFO, priorizando siempre la fecha de vencimiento cuando está disponible.

### Stock Mínimo (Stock de Seguridad)
Nivel de existencias por debajo del cual no debería caer un producto para evitar quiebres de inventario ante demoras imprevistas del proveedor o picos de demanda.

### Punto de Pedido (Punto de Reorden)
Nivel cuantitativo de stock que, al ser alcanzado, dispara automáticamente una sugerencia de compra para reaprovisionar el depósito antes de agotar el stock de seguridad.

### Conteo Físico a Ciegas
Metodología de auditoría de inventario donde el operario releva y anota las unidades físicas presentes en la estantería sin conocer el stock teórico que figura en el sistema, evitando sesgos cognitivos o falsificaciones de conteo.

### Discrepancia de Inventario
Diferencia matemática entre la cantidad física contada y el stock registrado en el sistema. Puede ser un **Faltante** (merma, rotura o extravío) o un **Sobrante** (error de registro en recepciones previas).

### Ubicación Compuesta
Direccionamiento físico dentro del depósito identificado jerárquicamente por **Pasillo**, **Estantería** y **Nivel** (balda), permitiendo localizar rápidamente cualquier artículo.

---

## 2. Conceptos de Productos y Precios

### SKU (*Stock Keeping Unit*) / Código de Producto
Identificador alfanumérico único asignado a cada artículo para su control informático y comercial.

### Unidad Base
La menor unidad indivisible de comercialización (ej. una botella o un paquete individual). Todos los movimientos de inventario se calculan internamente en unidades base para mantener la coherencia.

### Bulto / Factor de Equivalencia
Formato de empaque secundario que agrupa múltiples unidades base (ej. una caja x 12 unidades o un pack x 6). El factor de equivalencia es el multiplicador numérico que traduce el bulto a unidades base.

### Regla de Redondeo
Algoritmo aplicado durante los aumentos masivos de precios para normalizar el importe final resultante:
- *Sin redondeo:* Conserva decimales exactos.
- *Redondeo estándar:* Al entero más próximo (0.50 redondea hacia arriba).
- *Ceil (Hacia arriba):* Fuerza el redondeo al entero superior.
- *Decenas / Centenas:* Redondea a múltiplos de 10 o 100 para simplificar el cobro físico en mostrador.

---

## 3. Conceptos de Compras y Proveedores

### Orden de Compra (OC)
Documento formal emitido por la distribuidora que solicita a un proveedor la entrega de determinados productos a un precio pactado y en un plazo acordado. Transita por una máquina de estados: `pendiente` ➔ `enviada` ➔ `recibida_parcialmente` ➔ `completada` / `cancelada`.

### Recepción Parcial
Situación en la cual el proveedor entrega una cantidad inferior a la solicitada en la orden de compra. El sistema incrementa el stock únicamente por lo recibido y mantiene abierta la orden para el remanente.

### Plazo de Entrega (*Lead Time*)
Tiempo transcurrido, medido en días, desde que la orden de compra es enviada al proveedor hasta que la mercadería es recepcionada físicamente en el depósito.

### CUIT (*Clave Única de Identificación Tributaria*)
Identificador fiscal de 11 dígitos utilizado en Argentina para personas jurídicas y autónomos.

### Algoritmo Módulo 11
Fórmula matemática ponderada utilizada oficialmente por AFIP/ARCA para validar que el último dígito del CUIT corresponda estrictamente con los primeros 10 dígitos, impidiendo el alta de números fiscales ficticios o mal tipeados.

### Tipos de Comprobante Fiscal
- **Factura A:** Emitida entre Responsables Inscriptos, con discriminación explícita del 21% o 10.5% de IVA.
- **Factura B:** Emitida a consumidores finales o exentos, con IVA incluido en el total.
- **Factura C:** Emitida por contribuyentes inscriptos en el régimen de Monotributo.
- **Remito:** Documento de traslado de mercadería sin validez de factura contable pero válido para recepción física en depósito.

---

## 4. Conceptos de Arquitectura y Seguridad

### RBAC (*Role-Based Access Control*)
Modelo de control de acceso donde los permisos para ver o modificar secciones del sistema se asignan a roles abstractos (Administrativo, Repositor, Contador) y no directamente a usuarios individuales.

### Token Bearer
Cadena criptográfica transmitida en la cabecera `Authorization: Bearer <token>` que acredita la identidad y sesión activa del cliente HTTP ante el servidor sin requerir reingresar credenciales en cada petición.

### Pista de Auditoría (*Audit Trail*)
Registro histórico que captura el usuario responsable, timestamp, tabla afectada y el estado anterior y posterior de los datos en formato JSON ante cada modificación en el sistema (`log_auditoria`).

### Desactivación Lógica (*Soft Delete*)
Técnica de persistencia donde un registro (como un producto o proveedor) no se borra físicamente con `DELETE` de la base de datos, sino que se marca con `estado = 'inactivo'`, preservando la integridad referencial de todas las ventas y compras históricas en las que participó.
