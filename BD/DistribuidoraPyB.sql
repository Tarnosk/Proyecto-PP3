CREATE TABLE `ZONA` (
  `id_zona` integer PRIMARY KEY AUTO_INCREMENT,
  `nombre` string NOT NULL,
  `descripcion` string
);

CREATE TABLE `CLIENTE` (
  `id_cliente` integer PRIMARY KEY AUTO_INCREMENT,
  `nombre` string NOT NULL,
  `razon_social` string,
  `CUIL` string UNIQUE,
  `email` string,
  `telefono` string,
  `direccion` string,
  `localidad` string,
  `id_zona` integer,
  `tipo_cliente` string COMMENT 'mayorista | minorista | consumidor_final',
  `condicion_iva` string,
  `estado` string COMMENT 'activo | inactivo',
  `saldo` decimal
);

CREATE TABLE `USUARIO` (
  `id_usuario` integer PRIMARY KEY,
  `user` string UNIQUE NOT NULL,
  `password` string NOT NULL,
  `rol` string COMMENT 'admin | vendedor | repartidor',
  `estado` string COMMENT 'activo | bloqueado'
);

CREATE TABLE `CATEGORIA` (
  `id_categoria` integer PRIMARY KEY AUTO_INCREMENT,
  `nombre` string NOT NULL,
  `fecha_creacion` date,
  `fecha_modificacion` date
);

CREATE TABLE `MARCA` (
  `id_marca` integer PRIMARY KEY AUTO_INCREMENT,
  `nombre` string NOT NULL,
  `fecha_creacion` date,
  `fecha_modificacion` date
);

CREATE TABLE `PRODUCTO` (
  `id_producto` integer PRIMARY KEY AUTO_INCREMENT,
  `codigo` string UNIQUE NOT NULL,
  `nombre` string NOT NULL,
  `descripcion` string,
  `precioMay` decimal NOT NULL,
  `precioMin` decimal NOT NULL,
  `imagen` string,
  `stock` integer NOT NULL DEFAULT 0,
  `stock_minimo` integer DEFAULT 0,
  `dias_alerta_vencimiento` integer DEFAULT 30,
  `estado` string COMMENT 'activo | inactivo',
  `fecha_alta` date,
  `fecha_modificacion` date,
  `fecha_desactivacion` date,
  `id_categoria` integer,
  `id_marca` integer,
  `id_usuario_carga` integer,
  `id_usuario_modificacion` integer
);

CREATE TABLE `HISTORIAL_PRECIO` (
  `id_historial` integer PRIMARY KEY AUTO_INCREMENT,
  `id_producto` integer NOT NULL,
  `tipo_precio` string NOT NULL COMMENT 'mayorista | minorista',
  `precio` decimal NOT NULL,
  `porcentaje_aumento` decimal,
  `regla_redondeo` string,
  `origen` string COMMENT 'manual | aumento_masivo',
  `fecha_cambio` date,
  `id_usuario` integer
);

CREATE TABLE `UNIDAD_MEDIDA` (
  `id_unidad` integer PRIMARY KEY AUTO_INCREMENT,
  `id_producto` integer,
  `nombre_unidad` string,
  `equivalencia_base` decimal,
  `descripcion` string
);

CREATE TABLE `MOVIMIENTO_STOCK` (
  `id_movimiento` integer PRIMARY KEY AUTO_INCREMENT,
  `id_producto` integer NOT NULL,
  `id_unidad` integer,
  `tipo` string COMMENT 'ingreso | venta | devolucion | ajuste | merma',
  `cantidad` integer NOT NULL,
  `fecha` date,
  `motivo` string,
  `id_usuario` integer,
  `id_venta` integer,
  `id_entrega` integer
);

CREATE TABLE `LOTE` (
  `id_lote` integer PRIMARY KEY AUTO_INCREMENT,
  `id_producto` integer,
  `id_movimiento` integer,
  `id_unidad` integer,
  `nro_lote` string,
  `cantidad` integer,
  `fecha_vencimiento` date,
  `estado` string COMMENT 'vigente | vencido | consumido'
);

CREATE TABLE `UBICACION` (
  `id_ubicacion` integer PRIMARY KEY AUTO_INCREMENT,
  `descripcion` string COMMENT 'estante / pasillo / gondola',
  `estado` string,
  `fecha_creacion` date
);

CREATE TABLE `PRODUCTO_UBICACION` (
  `id_producto` integer NOT NULL,
  `id_ubicacion` integer NOT NULL,
  `fecha_asignacion` date,
  `id_usuario` integer,
  PRIMARY KEY (`id_producto`, `id_ubicacion`)
);

CREATE TABLE `SESION_CONTEO` (
  `id_sesion` integer PRIMARY KEY AUTO_INCREMENT,
  `estado` string COMMENT 'en_proceso | finalizado',
  `fecha_conteo` date,
  `observaciones` string,
  `total_productos` integer,
  `total_diferencias` integer,
  `id_usuario` integer
);

CREATE TABLE `DETALLE_CONTEO` (
  `id_detalle_conteo` integer PRIMARY KEY AUTO_INCREMENT,
  `id_sesion` integer NOT NULL,
  `id_producto` integer NOT NULL,
  `cantidad_fisica` integer,
  `stock_sistema` integer,
  `diferencia` integer,
  `observaciones` string
);

CREATE TABLE `PROVEEDOR` (
  `id_proveedor` integer PRIMARY KEY AUTO_INCREMENT,
  `razon_social` string NOT NULL,
  `CUIT` string UNIQUE,
  `telefono` string,
  `email` string,
  `direccion` string,
  `estado` string COMMENT 'activo | inactivo',
  `plazo_entrega_dias` integer,
  `fecha_alta` date,
  `fecha_modificacion` date,
  `fecha_desactivacion` date,
  `id_usuario_carga` integer,
  `id_usuario_modificacion` integer
);

CREATE TABLE `PRODUCTO_PROVEEDOR` (
  `id_producto_proveedor` integer PRIMARY KEY AUTO_INCREMENT,
  `id_producto` integer,
  `id_proveedor` integer,
  `es_proveedor_principal` boolean,
  `precio_acordado` decimal,
  `activo` boolean,
  `fecha_asociacion` date,
  `fecha_desasociacion` date,
  `id_usuario` integer
);

CREATE TABLE `ORDEN_COMPRA` (
  `id_orden` integer PRIMARY KEY AUTO_INCREMENT,
  `numero_orden` string UNIQUE,
  `id_proveedor` integer NOT NULL,
  `total_estimado` decimal,
  `estado` string COMMENT 'pendiente | enviada | recibida_parcialmente | completada | cancelada',
  `fecha_creacion` date,
  `fecha_modificacion` date,
  `fecha_envio` date,
  `fecha_cancelacion` date,
  `id_usuario` integer
);

CREATE TABLE `DETALLE_ORDEN` (
  `id_detalle_orden` integer PRIMARY KEY AUTO_INCREMENT,
  `id_orden` integer NOT NULL,
  `id_producto` integer NOT NULL,
  `id_unidad` integer,
  `cantidad_solicitada` integer NOT NULL,
  `cantidad_sugerida` integer,
  `origen` string COMMENT 'manual | sugerencia',
  `precio_estimado` decimal,
  `subtotal` decimal
);

CREATE TABLE `COMPRA` (
  `id_compra` integer PRIMARY KEY AUTO_INCREMENT,
  `numero_comprobante` string UNIQUE,
  `id_proveedor` integer NOT NULL,
  `id_orden` integer,
  `importe_total` decimal,
  `saldo_pendiente` decimal,
  `estado` string COMMENT 'pendiente | parcialmente_recibida | completada | cancelada',
  `fecha_compra` date,
  `fecha_cancelacion` date,
  `id_usuario` integer
);

CREATE TABLE `DETALLE_COMPRA` (
  `id_detalle_compra` integer PRIMARY KEY AUTO_INCREMENT,
  `id_compra` integer NOT NULL,
  `id_producto` integer NOT NULL,
  `id_unidad` integer,
  `cantidad` integer,
  `cantidad_recibida` integer DEFAULT 0,
  `precio_unitario` decimal,
  `subtotal` decimal
);

CREATE TABLE `RECEPCION` (
  `id_recepcion` integer PRIMARY KEY AUTO_INCREMENT,
  `id_compra` integer NOT NULL,
  `id_proveedor` integer NOT NULL,
  `fecha_recepcion` date,
  `id_usuario` integer
);

CREATE TABLE `DETALLE_RECEPCION` (
  `id_det_rec` integer PRIMARY KEY AUTO_INCREMENT,
  `id_recepcion` integer NOT NULL,
  `id_producto` integer NOT NULL,
  `id_lote` integer,
  `id_unidad` integer,
  `cantidad_recibida` integer NOT NULL
);

CREATE TABLE `PAGO_PROVEEDOR` (
  `id_pago` integer PRIMARY KEY AUTO_INCREMENT,
  `id_compra` integer NOT NULL,
  `metodo_pago` string COMMENT 'efectivo | transferencia | cheque | tarjeta',
  `importe` decimal,
  `estado_pago` string COMMENT 'pendiente | pagado | parcial | vencido',
  `fecha_pago` date,
  `fecha_vencimiento` date,
  `id_usuario` integer
);

CREATE TABLE `HISTORIAL_PRECIO_PROVEEDOR` (
  `id_historial_prov` integer PRIMARY KEY AUTO_INCREMENT,
  `id_producto_proveedor` integer NOT NULL,
  `id_proveedor` integer NOT NULL,
  `id_producto` integer NOT NULL,
  `id_compra` integer,
  `precio_acordado` decimal NOT NULL,
  `fecha_actualizacion` date,
  `id_usuario` integer
);

CREATE TABLE `LOG_AUDITORIA` (
  `id_auditoria` integer PRIMARY KEY AUTO_INCREMENT,
  `tabla_afectada` string NOT NULL,
  `id_registro` integer NOT NULL,
  `accion` string NOT NULL COMMENT 'INSERT | UPDATE | DELETE',
  `valores_anteriores` json,
  `valores_nuevos` json,
  `id_usuario` integer,
  `fecha_hora` timestamp
);

CREATE TABLE `PROMOCION` (
  `id_promocion` integer PRIMARY KEY AUTO_INCREMENT,
  `nombre` string,
  `tipo_descuento` string COMMENT 'porcentaje | monto_fijo',
  `valor` decimal,
  `vigencia_desde` date,
  `vigencia_hasta` date,
  `condiciones` string,
  `estado` string COMMENT 'activa | pausada | baja'
);

CREATE TABLE `VENTA` (
  `id_venta` integer PRIMARY KEY AUTO_INCREMENT,
  `id_cliente` integer,
  `fecha` date,
  `total` decimal,
  `pagado` boolean,
  `numFactura` string,
  `estado` string COMMENT 'pendiente | confirmada | facturada | cancelada',
  `observaciones` string,
  `id_usuario` integer
);

CREATE TABLE `DETALLE_VENTA` (
  `id_detalle_venta` integer PRIMARY KEY AUTO_INCREMENT,
  `id_venta` integer,
  `id_producto` integer,
  `id_promocion` integer,
  `cantidad` integer,
  `precio_unitario` decimal,
  `descuento` decimal,
  `subtotal` decimal
);

CREATE TABLE `PROMOCION_PRODUCTO` (
  `id_promocion` integer,
  `id_producto` integer,
  PRIMARY KEY (`id_promocion`, `id_producto`)
);

CREATE TABLE `ENTREGA` (
  `id_entrega` integer PRIMARY KEY AUTO_INCREMENT,
  `id_repartidor` integer,
  `fecha` date,
  `estado` string COMMENT 'pendiente | en_transito | entregada | no_entregada',
  `observaciones` string
);

CREATE TABLE `ENTREGA_PEDIDO` (
  `id_entrega` integer,
  `id_venta` integer,
  PRIMARY KEY (`id_entrega`, `id_venta`)
);

CREATE UNIQUE INDEX `PRODUCTO_PROVEEDOR_index_0` ON `PRODUCTO_PROVEEDOR` (`id_producto`, `id_proveedor`);

ALTER TABLE `CLIENTE` ADD FOREIGN KEY (`id_zona`) REFERENCES `ZONA` (`id_zona`);

ALTER TABLE `USUARIO` ADD FOREIGN KEY (`id_usuario`) REFERENCES `CLIENTE` (`id_cliente`);

ALTER TABLE `PRODUCTO` ADD FOREIGN KEY (`id_categoria`) REFERENCES `CATEGORIA` (`id_categoria`);

ALTER TABLE `PRODUCTO` ADD FOREIGN KEY (`id_marca`) REFERENCES `MARCA` (`id_marca`);

ALTER TABLE `PRODUCTO` ADD FOREIGN KEY (`id_usuario_carga`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `PRODUCTO` ADD FOREIGN KEY (`id_usuario_modificacion`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `HISTORIAL_PRECIO` ADD FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`);

ALTER TABLE `HISTORIAL_PRECIO` ADD FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `UNIDAD_MEDIDA` ADD FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`);

ALTER TABLE `MOVIMIENTO_STOCK` ADD FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`);

ALTER TABLE `MOVIMIENTO_STOCK` ADD FOREIGN KEY (`id_unidad`) REFERENCES `UNIDAD_MEDIDA` (`id_unidad`);

ALTER TABLE `MOVIMIENTO_STOCK` ADD FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `MOVIMIENTO_STOCK` ADD FOREIGN KEY (`id_venta`) REFERENCES `VENTA` (`id_venta`);

ALTER TABLE `MOVIMIENTO_STOCK` ADD FOREIGN KEY (`id_entrega`) REFERENCES `ENTREGA` (`id_entrega`);

ALTER TABLE `LOTE` ADD FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`);

ALTER TABLE `LOTE` ADD FOREIGN KEY (`id_movimiento`) REFERENCES `MOVIMIENTO_STOCK` (`id_movimiento`);

ALTER TABLE `LOTE` ADD FOREIGN KEY (`id_unidad`) REFERENCES `UNIDAD_MEDIDA` (`id_unidad`);

ALTER TABLE `PRODUCTO_UBICACION` ADD FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`);

ALTER TABLE `PRODUCTO_UBICACION` ADD FOREIGN KEY (`id_ubicacion`) REFERENCES `UBICACION` (`id_ubicacion`);

ALTER TABLE `PRODUCTO_UBICACION` ADD FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `SESION_CONTEO` ADD FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `DETALLE_CONTEO` ADD FOREIGN KEY (`id_sesion`) REFERENCES `SESION_CONTEO` (`id_sesion`);

ALTER TABLE `DETALLE_CONTEO` ADD FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`);

ALTER TABLE `PROVEEDOR` ADD FOREIGN KEY (`id_usuario_carga`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `PROVEEDOR` ADD FOREIGN KEY (`id_usuario_modificacion`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `PRODUCTO_PROVEEDOR` ADD FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`);

ALTER TABLE `PRODUCTO_PROVEEDOR` ADD FOREIGN KEY (`id_proveedor`) REFERENCES `PROVEEDOR` (`id_proveedor`);

ALTER TABLE `PRODUCTO_PROVEEDOR` ADD FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `ORDEN_COMPRA` ADD FOREIGN KEY (`id_proveedor`) REFERENCES `PROVEEDOR` (`id_proveedor`);

ALTER TABLE `ORDEN_COMPRA` ADD FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `DETALLE_ORDEN` ADD FOREIGN KEY (`id_orden`) REFERENCES `ORDEN_COMPRA` (`id_orden`);

ALTER TABLE `DETALLE_ORDEN` ADD FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`);

ALTER TABLE `DETALLE_ORDEN` ADD FOREIGN KEY (`id_unidad`) REFERENCES `UNIDAD_MEDIDA` (`id_unidad`);

ALTER TABLE `COMPRA` ADD FOREIGN KEY (`id_proveedor`) REFERENCES `PROVEEDOR` (`id_proveedor`);

ALTER TABLE `COMPRA` ADD FOREIGN KEY (`id_orden`) REFERENCES `ORDEN_COMPRA` (`id_orden`);

ALTER TABLE `COMPRA` ADD FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `DETALLE_COMPRA` ADD FOREIGN KEY (`id_compra`) REFERENCES `COMPRA` (`id_compra`);

ALTER TABLE `DETALLE_COMPRA` ADD FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`);

ALTER TABLE `DETALLE_COMPRA` ADD FOREIGN KEY (`id_unidad`) REFERENCES `UNIDAD_MEDIDA` (`id_unidad`);

ALTER TABLE `RECEPCION` ADD FOREIGN KEY (`id_compra`) REFERENCES `COMPRA` (`id_compra`);

ALTER TABLE `RECEPCION` ADD FOREIGN KEY (`id_proveedor`) REFERENCES `PROVEEDOR` (`id_proveedor`);

ALTER TABLE `RECEPCION` ADD FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `DETALLE_RECEPCION` ADD FOREIGN KEY (`id_recepcion`) REFERENCES `RECEPCION` (`id_recepcion`);

ALTER TABLE `DETALLE_RECEPCION` ADD FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`);

ALTER TABLE `DETALLE_RECEPCION` ADD FOREIGN KEY (`id_lote`) REFERENCES `LOTE` (`id_lote`);

ALTER TABLE `DETALLE_RECEPCION` ADD FOREIGN KEY (`id_unidad`) REFERENCES `UNIDAD_MEDIDA` (`id_unidad`);

ALTER TABLE `PAGO_PROVEEDOR` ADD FOREIGN KEY (`id_compra`) REFERENCES `COMPRA` (`id_compra`);

ALTER TABLE `PAGO_PROVEEDOR` ADD FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `HISTORIAL_PRECIO_PROVEEDOR` ADD FOREIGN KEY (`id_producto_proveedor`) REFERENCES `PRODUCTO_PROVEEDOR` (`id_producto_proveedor`);

ALTER TABLE `HISTORIAL_PRECIO_PROVEEDOR` ADD FOREIGN KEY (`id_proveedor`) REFERENCES `PROVEEDOR` (`id_proveedor`);

ALTER TABLE `HISTORIAL_PRECIO_PROVEEDOR` ADD FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`);

ALTER TABLE `HISTORIAL_PRECIO_PROVEEDOR` ADD FOREIGN KEY (`id_compra`) REFERENCES `COMPRA` (`id_compra`);

ALTER TABLE `HISTORIAL_PRECIO_PROVEEDOR` ADD FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `LOG_AUDITORIA` ADD FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `VENTA` ADD FOREIGN KEY (`id_cliente`) REFERENCES `CLIENTE` (`id_cliente`);

ALTER TABLE `VENTA` ADD FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `DETALLE_VENTA` ADD FOREIGN KEY (`id_venta`) REFERENCES `VENTA` (`id_venta`);

ALTER TABLE `DETALLE_VENTA` ADD FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`);

ALTER TABLE `DETALLE_VENTA` ADD FOREIGN KEY (`id_promocion`) REFERENCES `PROMOCION` (`id_promocion`);

ALTER TABLE `PROMOCION_PRODUCTO` ADD FOREIGN KEY (`id_promocion`) REFERENCES `PROMOCION` (`id_promocion`);

ALTER TABLE `PROMOCION_PRODUCTO` ADD FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`);

ALTER TABLE `ENTREGA` ADD FOREIGN KEY (`id_repartidor`) REFERENCES `USUARIO` (`id_usuario`);

ALTER TABLE `ENTREGA_PEDIDO` ADD FOREIGN KEY (`id_entrega`) REFERENCES `ENTREGA` (`id_entrega`);

ALTER TABLE `ENTREGA_PEDIDO` ADD FOREIGN KEY (`id_venta`) REFERENCES `VENTA` (`id_venta`);
