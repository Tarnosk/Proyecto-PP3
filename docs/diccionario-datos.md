# Diccionario de Datos — ERP Juan23 (Distribuidora PyB)

Este documento detalla la estructura relacional de la base de datos unificada del sistema ERP, describiendo las tablas principales, claves primarias (`PK`), foráneas (`FK`), restricciones y el propósito funcional de cada columna.

---

## 1. Módulo Productos y Catálogo

### Tabla: `producto`
Almacena el catálogo maestro de artículos comercializados.
* **`id_producto`** (INT, PK, AUTO_INCREMENT): Identificador único del producto.
* **`codigo`** (VARCHAR(50), UNIQUE, NOT NULL): Código alfanumérico o SKU del producto.
* **`nombre`** (VARCHAR(255), NULL): Nombre comercial del producto.
* **`descripcion`** (VARCHAR(255), NOT NULL): Descripción técnica y detallada.
* **`precio_unitario`** (DECIMAL(12,2), NOT NULL): Precio base o costo de lista.
* **`precio_mayorista`** (DECIMAL(12,2), NULL): Precio de venta por volumen / mayorista.
* **`precio_minorista`** (DECIMAL(12,2), NULL): Precio de venta a consumidor final.
* **`stock`** (INT, NOT NULL, DEFAULT 0): Stock físico consolidado en sistema.
* **`stock_minimo`** (INT, NOT NULL, DEFAULT 5): Umbral para disparo de alertas de reposición.
* **`id_categoria`** (INT, FK -> `categoria.id_categoria`, NOT NULL): Categoría asignada.
* **`id_marca`** (INT, FK -> `marca.id_marca`, NOT NULL): Marca comercial del artículo.
* **`estado`** (ENUM('activo', 'inactivo'), DEFAULT 'activo'): Estado de habilitación comercial.
* **`fecha_alta`** (DATETIME): Timestamp de creación.
* **`id_usuario_carga`** (INT, FK -> `usuario.id_usuario`): Usuario que registró el producto.

### Tabla: `categoria`
Clasificación taxonómica de productos (ej. Lácteos, Bebidas, Almacén).
* **`id_categoria`** (INT, PK, AUTO_INCREMENT): Identificador de la categoría.
* **`nombre`** (VARCHAR(100), NOT NULL, UNIQUE): Nombre de la categoría.
* **`descripcion`** (TEXT, NULL): Detalle o alcance de la familia de productos.

### Tabla: `marca`
Marcas comerciales de los fabricantes o distribuidores.
* **`id_marca`** (INT, PK, AUTO_INCREMENT): Identificador de la marca.
* **`nombre`** (VARCHAR(100), NOT NULL, UNIQUE): Nombre de la marca.

### Tabla: `historial_precio`
Trazabilidad de cambios de precios de venta (P08).
* **`id_historial`** (INT, PK, AUTO_INCREMENT): Identificador del registro.
* **`id_producto`** (INT, FK -> `producto.id_producto`): Producto afectado.
* **`precio_anterior`** (DECIMAL(12,2), NOT NULL): Precio previo al ajuste.
* **`precio_nuevo`** (DECIMAL(12,2), NOT NULL): Precio resultante del ajuste.
* **`motivo`** (VARCHAR(255), NULL): Motivo (ej. "Aumento masivo 15% Categoría Bebidas").
* **`fecha_cambio`** (DATETIME, NOT NULL): Fecha y hora de aplicación.
* **`id_usuario`** (INT, FK -> `usuario.id_usuario`): Usuario responsable.

### Tabla: `unidad_medida`
Unidades de despacho y factores de conversión (S08).
* **`id_unidad`** (INT, PK, AUTO_INCREMENT): Identificador de la unidad.
* **`id_producto`** (INT, FK -> `producto.id_producto`): Producto asociado.
* **`nombre_unidad`** (VARCHAR(50), NOT NULL): Nombre (ej. "Caja x 12", "Pack").
* **`equivalencia_base`** (DECIMAL(10,2), NOT NULL): Cantidad de unidades individuales que contiene.
* **`es_base`** (TINYINT(1), DEFAULT 0): 1 si es la unidad mínima individual, 0 si es múltiplo.

---

## 2. Módulo Stock e Inventario

### Tabla: `movimiento_stock` (Kardex)
Registro inmutable de toda variación en las existencias físicas.
* **`id_movimiento`** (INT, PK, AUTO_INCREMENT): Identificador del movimiento.
* **`id_producto`** (INT, FK -> `producto.id_producto`): Producto movido.
* **`tipo_movimiento`** (ENUM('ingreso', 'egreso', 'ajuste', 'devolucion', 'venta', 'recepcion')): Tipo de operación.
* **`cantidad`** (INT, NOT NULL): Magnitud del movimiento (positivo o negativo según tipo).
* **`stock_anterior`** (INT, NOT NULL): Stock previo en sistema.
* **`stock_posterior`** (INT, NOT NULL): Stock resultante tras la operación.
* **`motivo`** (VARCHAR(255), NOT NULL): Justificación técnica u operativa.
* **`id_lote`** (INT, FK -> `lote.id_lote`, NULL): Lote asignado si aplica.
* **`id_usuario`** (INT, FK -> `usuario.id_usuario`): Operador que generó la acción.
* **`fecha_hora`** (DATETIME, NOT NULL): Timestamp del movimiento.

### Tabla: `lote`
Trazabilidad de partidas y control de caducidad (S09 / S10).
* **`id_lote`** (INT, PK, AUTO_INCREMENT): Identificador del lote.
* **`id_producto`** (INT, FK -> `producto.id_producto`): Producto del lote.
* **`nro_lote`** (VARCHAR(50), NOT NULL): Código o número de partida del fabricante.
* **`cantidad_inicial`** (INT, NOT NULL): Cantidad que ingresó al crearse el lote.
* **`cantidad_actual`** (INT, NOT NULL): Existencia remanente en este lote.
* **`fecha_vencimiento`** (DATE, NULL): Fecha de caducidad del lote.
* **`estado`** (ENUM('activo', 'agotado', 'vencido'), DEFAULT 'activo').

### Tabla: `ubicacion`
Estructura física del almacén (S14).
* **`id_ubicacion`** (INT, PK, AUTO_INCREMENT): Identificador de la ubicación.
* **`codigo`** (VARCHAR(50), UNIQUE, NOT NULL): Código mnemotécnico (ej. "PAS-01-EST-03").
* **`pasillo`** (VARCHAR(20), NOT NULL): Identificador de pasillo.
* **`estanteria`** (VARCHAR(20), NOT NULL): Número o letra de estantería.
* **`nivel`** (VARCHAR(20), NOT NULL): Altura o balda.

### Tabla: `producto_ubicacion`
Relación muchos a muchos entre productos y ubicaciones físicas.
* **`id_producto`** (INT, FK -> `producto.id_producto`): Producto almacenado.
* **`id_ubicacion`** (INT, FK -> `ubicacion.id_ubicacion`): Ubicación designada.

### Tablas: `sesion_conteo` y `detalle_conteo`
Control de inventarios y conteos a ciegas (S13).
* **`sesion_conteo`**:
  * **`id_sesion`** (INT, PK, AUTO_INCREMENT): Identificador de la sesión de inventario.
  * **`fecha_inicio`** / **`fecha_cierre`** (DATETIME): Período de ejecución.
  * **`estado`** (ENUM('abierta', 'finalizada', 'cancelada')): Estado de la toma.
  * **`id_usuario_responsable`** (INT, FK -> `usuario.id_usuario`): Supervisor.
* **`detalle_conteo`**:
  * **`id_detalle`** (INT, PK, AUTO_INCREMENT): Ítem contado.
  * **`id_sesion`** (INT, FK -> `sesion_conteo.id_sesion`): Sesión contenedora.
  * **`id_producto`** (INT, FK -> `producto.id_producto`): Producto contado.
  * **`cantidad_sistema`** (INT): Stock teórico al momento del conteo.
  * **`cantidad_contada`** (INT): Cantidad relevada físicamente en depósito.
  * **`diferencia`** (INT): Discrepancia calculada (`cantidad_contada - cantidad_sistema`).

---

## 3. Módulo Proveedores

### Tabla: `proveedor`
Padrón de empresas proveedoras de mercadería (PV01 – PV04).
* **`id_proveedor`** (INT, PK, AUTO_INCREMENT): Identificador del proveedor.
* **`cuit`** (VARCHAR(15), UNIQUE, NOT NULL): Clave fiscal validada mediante algoritmo Módulo 11.
* **`razon_social`** (VARCHAR(150), NOT NULL): Nombre legal de la empresa.
* **`telefono`** (VARCHAR(50), NULL): Línea de contacto comercial.
* **`email`** (VARCHAR(100), NULL): Correo electrónico de pedidos.
* **`direccion`** (VARCHAR(255), NULL): Domicilio fiscal o planta.
* **`estado`** (ENUM('activo', 'inactivo'), DEFAULT 'activo'): Estado comercial.

### Tabla: `producto_proveedor`
Pactos de precios y códigos de artículos por proveedor (PV05).
* **`id_producto_proveedor`** (INT, PK, AUTO_INCREMENT): Vínculo comercial.
* **`id_proveedor`** (INT, FK -> `proveedor.id_proveedor`): Proveedor.
* **`id_producto`** (INT, FK -> `producto.id_producto`): Producto provisto.
* **`codigo_producto_proveedor`** (VARCHAR(50), NULL): Código interno del catálogo del proveedor.
* **`precio_costo`** (DECIMAL(12,2), NOT NULL): Precio pactado de compra.
* **`es_principal`** (TINYINT(1), DEFAULT 0): 1 si es el proveedor predilecto para reposición.

### Tabla: `historial_precio_proveedor`
Registro histórico de variaciones de cotizaciones de compra (PV06).
* **`id_historial`** (INT, PK, AUTO_INCREMENT): Registro histórico.
* **`id_proveedor`** (INT, FK -> `proveedor.id_proveedor`): Proveedor que varió el precio.
* **`id_producto`** (INT, FK -> `producto.id_producto`): Producto afectado.
* **`precio_anterior`** / **`precio_nuevo`** (DECIMAL(12,2)): Evolución del costo.
* **`fecha_cambio`** (DATETIME, NOT NULL): Fecha de entrada en vigencia.
* **`origen`** (ENUM('compra', 'manual')): Si provino de factura recepcionada o ajuste manual.

---

## 4. Módulo Compras y Órdenes de Compra

### Tabla: `orden_compra`
Órdenes y pedidos de adquisición a proveedores (PC01 – PC06).
* **`id_orden`** (INT, PK, AUTO_INCREMENT): Número de orden.
* **`id_proveedor`** (INT, FK -> `proveedor.id_proveedor`): Proveedor destinatario.
* **`fecha_emision`** (DATE, NOT NULL): Fecha de creación del pedido.
* **`fecha_entrega_esperada`** (DATE, NULL): Plazo estimado de arribo.
* **`estado`** (ENUM('pendiente', 'enviada', 'recibida_parcialmente', 'completada', 'cancelada')).
* **`total`** (DECIMAL(12,2), NOT NULL): Importe consolidado de la orden.
* **`observaciones`** (TEXT, NULL): Especificaciones adicionales.

### Tabla: `detalle_orden`
Renglones o artículos incluidos en la orden de compra.
* **`id_detalle_orden`** (INT, PK, AUTO_INCREMENT): Renglón de orden.
* **`id_orden`** (INT, FK -> `orden_compra.id_orden`): Orden madre.
* **`id_producto`** (INT, FK -> `producto.id_producto`): Producto solicitado.
* **`cantidad`** (INT, NOT NULL): Cantidad solicitada.
* **`precio_unitario`** (DECIMAL(12,2), NOT NULL): Precio pactado por unidad.
* **`subtotal`** (DECIMAL(12,2), NOT NULL): Importe del ítem.

### Tabla: `compra`
Registro formal y fiscal de compra mercadería ingresada (C01 – C05).
* **`id_compra`** (INT, PK, AUTO_INCREMENT): Identificador de la compra.
* **`id_orden`** (INT, FK -> `orden_compra.id_orden`, NULL): Orden de origen si aplica.
* **`id_proveedor`** (INT, FK -> `proveedor.id_proveedor`): Proveedor facturante.
* **`tipo_comprobante`** (ENUM('Factura A', 'Factura B', 'Factura C', 'Remito')): Documento fiscal.
* **`nro_comprobante`** (VARCHAR(20), NOT NULL): Número estricto del comprobante.
* **`total`** (DECIMAL(12,2), NOT NULL): Monto total liquidado.
* **`estado_pago`** (ENUM('pendiente', 'pagado_parcial', 'pagado_total')): Estado financiero.

### Tabla: `pago_proveedor`
Liquidación financiera de deudas con proveedores (C07 / C08).
* **`id_pago`** (INT, PK, AUTO_INCREMENT): Identificador de pago.
* **`id_compra`** (INT, FK -> `compra.id_compra`): Compra imputada.
* **`monto`** (DECIMAL(12,2), NOT NULL): Importe cancelado.
* **`metodo_pago`** (ENUM('efectivo', 'transferencia', 'cheque', 'echeq')): Vía de pago.
* **`nro_comprobante`** (VARCHAR(50), NULL): Número de transacción bancaria o cheque.
* **`fecha_pago`** (DATE, NOT NULL): Fecha de cancelación o cobro diferido.

---

## 5. Módulo Seguridad y Auditoría

### Tabla: `usuario`
Credenciales y roles de operadores del ERP.
* **`id_usuario`** (INT, PK, AUTO_INCREMENT): Identificador de usuario.
* **`user`** / **`usuario`** (VARCHAR(50), UNIQUE, NOT NULL): Nombre de usuario para login.
* **`password`** / **`password_hash`** (VARCHAR(255), NOT NULL): Contraseña encriptada en Bcrypt costo 12.
* **`nombre`** (VARCHAR(100), NOT NULL): Nombre real del operador.
* **`rol`** (ENUM('administrativo', 'repartidor', 'contador', 'admin')): Perfil de permisos.
* **`estado`** (ENUM('activo', 'inactivo'), DEFAULT 'activo'): Control de acceso.

### Tabla: `log_auditoria`
Pista de auditoría forense de operaciones críticas.
* **`id_log`** (INT, PK, AUTO_INCREMENT): Evento de auditoría.
* **`tabla_afectada`** (VARCHAR(50), NOT NULL): Nombre de la tabla modificada.
* **`id_registro`** (INT, NOT NULL): Clave primaria del registro alterado.
* **`accion`** (ENUM('INSERT', 'UPDATE', 'DELETE')): Tipo de sentencia.
* **`valores_anteriores`** (JSON, NULL): Snapshot antes de la modificación.
* **`valores_nuevos`** (JSON, NULL): Snapshot posterior a la modificación.
* **`id_usuario`** (INT, FK -> `usuario.id_usuario`, NULL): Usuario que ejecutó la acción.
* **`fecha_hora`** (DATETIME, NOT NULL): Timestamp de la operación.
