-- =============================================================================
-- ERP Distribuidora PyB - Base de Datos Consolidada (MySQL 8.0+)
-- Version Corregida y Normalizada para Produccion
-- =============================================================================
-- Modulos Grupo 3: Productos, Stock, Proveedores, Compras, Pedidos de Compra
-- Modulos Integrados: Usuarios/Login (G1), Reparto/Entregas (G2), Ventas/Caja (G4)
-- =============================================================================
-- Este dump es el ESQUEMA BASE: 43 tablas. No trae SUGERENCIA_COMPRA ni
-- HISTORIAL_PLAZO_PROVEEDOR, que se crean por migracion.
--
-- ORDEN DE INSTALACION (las tres cosas, en este orden):
--   1) Este esquema base:
--        mysql.exe -u root distribuidorapyb < BD/esquema_base_DistribuidoraPyB.sql
--   2) Las 5 migraciones del G3 (crean SUGERENCIA_COMPRA, motivo_rechazo,
--      PAGO_PROVEEDOR con FK, HISTORIAL_PLAZO_PROVEEDOR e id_orden_compra):
--        php artisan migrate
--      Las cinco tienen guarda hasTable/hasColumn, asi que es seguro correrlas
--      sobre este esquema.
--   3) Los datos de demo (opcional):
--        mysql.exe -u root distribuidorapyb < BD/semilla_demo_actualizada.sql
--      Re-ejecutable: borra y recrea solo lo suyo.
--
-- Origen: movido desde ACTUALIZACION/DistribuidoraPyB (Version Corregida).sql,
-- que era byte a byte identico a "DistribuidoraPyB (Ultima Version).sql".
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- TABLAS DEL GRUPO 1: ZONA, USUARIO, CLIENTE, LOGIN_LOG, RECLAMO, AUDITORIA
-- -----------------------------------------------------------------------------

DROP TABLE IF EXISTS `LOGIN_LOG`;
DROP TABLE IF EXISTS `RECLAMO`;
DROP TABLE IF EXISTS `CLIENTE`;
DROP TABLE IF EXISTS `ZONA`;
DROP TABLE IF EXISTS `USUARIO`;
DROP TABLE IF EXISTS `LOG_AUDITORIA`;

CREATE TABLE `ZONA` (
  `id_zona` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(120) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Zonas comerciales y de reparto';

CREATE TABLE `USUARIO` (
  `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `nombre` VARCHAR(120) NOT NULL,
  `rol` ENUM('administrativo', 'repartidor', 'contador', 'vendedor', 'deposito') NOT NULL,
  `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `intentos_fallidos` TINYINT NOT NULL DEFAULT 0,
  `bloqueado_hasta` DATETIME DEFAULT NULL,
  `ultimo_intento_fallido` DATETIME DEFAULT NULL,
  `current_session_id` VARCHAR(128) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Usuarios internos del ERP. Incluye roles de ventas y deposito';

CREATE TABLE `CLIENTE` (
  `id_cliente` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(120) NOT NULL,
  `apellido_razon_social` VARCHAR(160) NOT NULL,
  `dni_cuit` VARCHAR(20) NOT NULL UNIQUE,
  `telefono` VARCHAR(30) NOT NULL,
  `email` VARCHAR(160) NOT NULL UNIQUE,
  `direccion` VARCHAR(255) NOT NULL,
  `localidad` VARCHAR(120) DEFAULT NULL,
  `id_zona` INT DEFAULT NULL,
  `tipo_cliente` ENUM('minorista', 'mayorista') NOT NULL,
  `condicion_iva` VARCHAR(80) DEFAULT NULL,
  `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `saldo` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `creado_por` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_cliente_creado_por` (`creado_por`),
  KEY `idx_cliente_id_zona` (`id_zona`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `LOGIN_LOG` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `fecha_hora` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` VARCHAR(45) DEFAULT NULL,
  KEY `idx_login_log_usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `RECLAMO` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `asunto` VARCHAR(160) NOT NULL,
  `descripcion` TEXT NOT NULL,
  `prioridad` ENUM('baja', 'media', 'alta') NOT NULL DEFAULT 'media',
  `estado` ENUM('abierto', 'en_proceso', 'cerrado') NOT NULL DEFAULT 'abierto',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_reclamo_cliente_id` (`cliente_id`),
  KEY `idx_reclamo_usuario_id` (`usuario_id`),
  KEY `idx_reclamo_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `LOG_AUDITORIA` (
  `id_auditoria` INT AUTO_INCREMENT PRIMARY KEY,
  `tabla_afectada` VARCHAR(80) NOT NULL,
  `id_registro` INT NOT NULL,
  `accion` ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
  `valores_anteriores` JSON DEFAULT NULL,
  `valores_nuevos` JSON DEFAULT NULL,
  `id_usuario` INT DEFAULT NULL,
  `fecha_hora` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_auditoria_tabla_reg` (`tabla_afectada`, `id_registro`),
  KEY `idx_auditoria_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Auditoria centralizada transaccional';


-- -----------------------------------------------------------------------------
-- TABLAS DEL GRUPO 3: PRODUCTOS Y PRECIOS
-- -----------------------------------------------------------------------------

DROP TABLE IF EXISTS `HISTORIAL_PRECIO`;
DROP TABLE IF EXISTS `PRODUCTO_UBICACION`;
DROP TABLE IF EXISTS `PRODUCTO_PROVEEDOR`;
DROP TABLE IF EXISTS `HISTORIAL_PRECIO_PROVEEDOR`;
DROP TABLE IF EXISTS `PRODUCTO`;
DROP TABLE IF EXISTS `CATEGORIA`;
DROP TABLE IF EXISTS `MARCA`;

CREATE TABLE `CATEGORIA` (
  `id_categoria` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL UNIQUE,
  `fecha_creacion` DATE NOT NULL,
  `fecha_modificacion` DATE DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `MARCA` (
  `id_marca` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL UNIQUE,
  `fecha_creacion` DATE NOT NULL,
  `fecha_modificacion` DATE DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `PRODUCTO` (
  `id_producto` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(50) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `precio_unitario` DECIMAL(12,2) NOT NULL,
  `imagen` VARCHAR(255) DEFAULT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `stock_minimo` INT NOT NULL DEFAULT 0,
  `dias_alerta_vencimiento` INT NOT NULL DEFAULT 30,
  `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `fecha_alta` DATE NOT NULL,
  `fecha_modificacion` DATE DEFAULT NULL,
  `fecha_desactivacion` DATE DEFAULT NULL,
  `id_categoria` INT NOT NULL,
  `id_marca` INT NOT NULL,
  `id_usuario_carga` INT NOT NULL,
  `id_usuario_modificacion` INT DEFAULT NULL,
  KEY `idx_producto_categoria` (`id_categoria`),
  KEY `idx_producto_marca` (`id_marca`),
  KEY `idx_producto_nombre` (`nombre`),
  CONSTRAINT `chk_producto_stock_no_negativo` CHECK (`stock` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Catalogo maestro de articulos. Precio unitario de lista base y stock embebido';

CREATE TABLE `HISTORIAL_PRECIO` (
  `id_historial` INT AUTO_INCREMENT PRIMARY KEY,
  `id_producto` INT NOT NULL,
  `tipo_precio` ENUM('general', 'mayorista', 'minorista') NOT NULL DEFAULT 'general',
  `precio` DECIMAL(12,2) NOT NULL,
  `porcentaje_aumento` DECIMAL(6,2) DEFAULT NULL,
  `regla_redondeo` VARCHAR(50) DEFAULT 'sin_redondeo',
  `origen` ENUM('manual', 'aumento_masivo') NOT NULL DEFAULT 'manual',
  `fecha_cambio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario` INT NOT NULL,
  KEY `idx_historial_prod_fecha` (`id_producto`, `fecha_cambio`),
  KEY `idx_historial_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Trazabilidad de precios. Guarda unicamente precio resultante (OB1)';


-- -----------------------------------------------------------------------------
-- TABLAS DEL GRUPO 3: STOCK, UNIDADES, LOTES, CONTEOS Y UBICACIONES
-- -----------------------------------------------------------------------------

DROP TABLE IF EXISTS `DETALLE_RECEPCION`;
DROP TABLE IF EXISTS `RECEPCION`;
DROP TABLE IF EXISTS `MOVIMIENTO_STOCK`;
DROP TABLE IF EXISTS `LOTE`;
DROP TABLE IF EXISTS `UNIDAD_MEDIDA`;
DROP TABLE IF EXISTS `DETALLE_CONTEO`;
DROP TABLE IF EXISTS `SESION_CONTEO`;
DROP TABLE IF EXISTS `UBICACION`;

CREATE TABLE `UNIDAD_MEDIDA` (
  `id_unidad` INT AUTO_INCREMENT PRIMARY KEY,
  `id_producto` INT NOT NULL,
  `nombre_unidad` VARCHAR(50) NOT NULL,
  `equivalencia_base` INT NOT NULL DEFAULT 1,
  `es_base` TINYINT(1) NOT NULL DEFAULT 0,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  UNIQUE KEY `uq_unidad_producto_nombre` (`id_producto`, `nombre_unidad`),
  KEY `idx_unidad_producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Unidades y equivalencias relativas a la unidad base (S08)';

CREATE TABLE `LOTE` (
  `id_lote` INT AUTO_INCREMENT PRIMARY KEY,
  `id_producto` INT NOT NULL,
  `nro_lote` VARCHAR(50) NOT NULL,
  `cantidad_inicial` INT NOT NULL,
  `cantidad_actual` INT NOT NULL,
  `fecha_vencimiento` DATE NOT NULL,
  UNIQUE KEY `uq_lote_producto_nro` (`id_producto`, `nro_lote`),
  KEY `idx_lote_producto_vencimiento` (`id_producto`, `fecha_vencimiento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Lotes con vencimiento en unidad base. Rompe referencia circular (S09)';

CREATE TABLE `UBICACION` (
  `id_ubicacion` INT AUTO_INCREMENT PRIMARY KEY,
  `descripcion` VARCHAR(100) NOT NULL UNIQUE,
  `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `fecha_creacion` DATE DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Sectores/pasillos del deposito fisico (S14)';

CREATE TABLE `PRODUCTO_UBICACION` (
  `id_producto` INT NOT NULL,
  `id_ubicacion` INT NOT NULL,
  `fecha_asignacion` DATE DEFAULT NULL,
  `id_usuario` INT DEFAULT NULL,
  PRIMARY KEY (`id_producto`, `id_ubicacion`),
  KEY `idx_prod_ubic_ubicacion` (`id_ubicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `SESION_CONTEO` (
  `id_sesion` INT AUTO_INCREMENT PRIMARY KEY,
  `estado` ENUM('en_proceso', 'finalizado') NOT NULL DEFAULT 'en_proceso',
  `fecha_conteo` DATE DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `total_productos` INT DEFAULT 0,
  `total_diferencias` INT DEFAULT 0,
  `id_usuario` INT NOT NULL,
  KEY `idx_sesion_conteo_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `DETALLE_CONTEO` (
  `id_detalle_conteo` INT AUTO_INCREMENT PRIMARY KEY,
  `id_sesion` INT NOT NULL,
  `id_producto` INT NOT NULL,
  `cantidad_fisica` INT DEFAULT NULL,
  `stock_sistema` INT DEFAULT NULL,
  `diferencia` INT DEFAULT NULL,
  `observaciones` VARCHAR(255) DEFAULT NULL,
  UNIQUE KEY `uq_conteo_sesion_prod` (`id_sesion`, `id_producto`),
  KEY `idx_detalle_conteo_prod` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- TABLAS DEL GRUPO 3: PROVEEDORES
-- -----------------------------------------------------------------------------

DROP TABLE IF EXISTS `PROVEEDOR`;

CREATE TABLE `PROVEEDOR` (
  `id_proveedor` INT AUTO_INCREMENT PRIMARY KEY,
  `razon_social` VARCHAR(160) NOT NULL,
  `CUIT` VARCHAR(20) NOT NULL UNIQUE,
  `telefono` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(160) DEFAULT NULL,
  `direccion` VARCHAR(255) DEFAULT NULL,
  `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `plazo_entrega_dias` INT DEFAULT NULL,
  `fecha_alta` DATE NOT NULL,
  `fecha_modificacion` DATE DEFAULT NULL,
  `fecha_desactivacion` DATE DEFAULT NULL,
  `id_usuario_carga` INT NOT NULL,
  `id_usuario_modificacion` INT DEFAULT NULL,
  KEY `idx_proveedor_razon` (`razon_social`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `PRODUCTO_PROVEEDOR` (
  `id_producto_proveedor` INT AUTO_INCREMENT PRIMARY KEY,
  `id_producto` INT NOT NULL,
  `id_proveedor` INT NOT NULL,
  `es_proveedor_principal` TINYINT(1) NOT NULL DEFAULT 0,
  `precio_acordado` DECIMAL(12,2) DEFAULT NULL,
  `id_unidad` INT DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_asociacion` DATE DEFAULT NULL,
  `fecha_desasociacion` DATE DEFAULT NULL,
  `id_usuario` INT DEFAULT NULL,
  KEY `idx_prod_prov_producto` (`id_producto`),
  KEY `idx_prod_prov_proveedor` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `HISTORIAL_PRECIO_PROVEEDOR` (
  `id_historial_prov` INT AUTO_INCREMENT PRIMARY KEY,
  `id_producto_proveedor` INT NOT NULL,
  `id_proveedor` INT NOT NULL,
  `id_producto` INT NOT NULL,
  `id_compra` INT DEFAULT NULL,
  `id_unidad` INT DEFAULT NULL,
  `precio_acordado` DECIMAL(12,2) NOT NULL,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario` INT DEFAULT NULL,
  KEY `idx_hist_prov_prod_prov` (`id_producto_proveedor`),
  KEY `idx_hist_prov_fecha` (`fecha_actualizacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- TABLAS DEL GRUPO 3: PEDIDOS DE COMPRA, COMPRAS Y RECEPCIONES
-- -----------------------------------------------------------------------------

DROP TABLE IF EXISTS `PAGO_PROVEEDOR`;
DROP TABLE IF EXISTS `DETALLE_COMPRA`;
DROP TABLE IF EXISTS `COMPRA`;
DROP TABLE IF EXISTS `DETALLE_ORDEN`;
DROP TABLE IF EXISTS `ORDEN_COMPRA`;

CREATE TABLE `ORDEN_COMPRA` (
  `id_orden` INT AUTO_INCREMENT PRIMARY KEY,
  `numero_orden` VARCHAR(30) NOT NULL UNIQUE,
  `id_proveedor` INT NOT NULL,
  `total_estimado` DECIMAL(12,2) DEFAULT NULL,
  `estado` ENUM('pendiente', 'enviada', 'recibida_parcialmente', 'completada', 'cancelada') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` DATE NOT NULL,
  `fecha_modificacion` DATE DEFAULT NULL,
  `fecha_envio` DATE DEFAULT NULL,
  `fecha_entrega_estimada` DATE DEFAULT NULL,
  `fecha_cancelacion` DATE DEFAULT NULL,
  `id_usuario` INT NOT NULL,
  KEY `idx_orden_proveedor` (`id_proveedor`),
  KEY `idx_orden_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `DETALLE_ORDEN` (
  `id_detalle_orden` INT AUTO_INCREMENT PRIMARY KEY,
  `id_orden` INT NOT NULL,
  `id_producto` INT NOT NULL,
  `id_unidad` INT NOT NULL,
  `cantidad_solicitada` INT NOT NULL,
  `cantidad_sugerida` INT DEFAULT NULL,
  `origen` ENUM('manual', 'sugerencia') NOT NULL DEFAULT 'manual',
  `precio_estimado` DECIMAL(12,2) DEFAULT NULL,
  `subtotal` DECIMAL(12,2) DEFAULT NULL,
  UNIQUE KEY `uq_detalle_orden_prod_unidad` (`id_orden`, `id_producto`, `id_unidad`),
  KEY `idx_det_orden_producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `COMPRA` (
  `id_compra` INT AUTO_INCREMENT PRIMARY KEY,
  `numero_compra` VARCHAR(30) NOT NULL UNIQUE,
  `numero_comprobante` VARCHAR(40) DEFAULT NULL,
  `id_proveedor` INT NOT NULL,
  `id_orden` INT DEFAULT NULL,
  `importe_total` DECIMAL(12,2) DEFAULT NULL,
  `saldo_pendiente` DECIMAL(12,2) DEFAULT NULL,
  `estado` ENUM('pendiente', 'parcialmente_recibida', 'completada', 'cancelada') NOT NULL DEFAULT 'pendiente',
  `fecha_compra` DATE NOT NULL,
  `fecha_vencimiento` DATE DEFAULT NULL,
  `fecha_cancelacion` DATE DEFAULT NULL,
  `fecha_modificacion` DATE DEFAULT NULL,
  `id_usuario` INT NOT NULL,
  `id_usuario_modificacion` INT DEFAULT NULL,
  UNIQUE KEY `uq_compra_prov_comprobante` (`id_proveedor`, `numero_comprobante`),
  KEY `idx_compra_proveedor_fecha` (`id_proveedor`, `fecha_compra`),
  KEY `idx_compra_orden` (`id_orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Compras a proveedores. numero_compra es correlativo interno; numero_comprobante es del proveedor';

CREATE TABLE `DETALLE_COMPRA` (
  `id_detalle_compra` INT AUTO_INCREMENT PRIMARY KEY,
  `id_compra` INT NOT NULL,
  `id_producto` INT NOT NULL,
  `id_unidad` INT NOT NULL,
  `cantidad` INT NOT NULL,
  `cantidad_recibida` INT NOT NULL DEFAULT 0,
  `precio_unitario` DECIMAL(12,2) NOT NULL,
  `subtotal` DECIMAL(12,2) DEFAULT NULL,
  UNIQUE KEY `uq_det_compra_prod_unidad` (`id_compra`, `id_producto`, `id_unidad`),
  KEY `idx_det_compra_producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `RECEPCION` (
  `id_recepcion` INT AUTO_INCREMENT PRIMARY KEY,
  `id_compra` INT DEFAULT NULL,
  `id_proveedor` INT NOT NULL,
  `fecha_recepcion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario` INT NOT NULL,
  KEY `idx_recepcion_compra` (`id_compra`),
  KEY `idx_recepcion_proveedor` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Recepcion de mercaderia. id_compra nullable permite ingreso directo sin compra previa (S03)';

CREATE TABLE `DETALLE_RECEPCION` (
  `id_det_rec` INT AUTO_INCREMENT PRIMARY KEY,
  `id_recepcion` INT NOT NULL,
  `id_detalle_compra` INT DEFAULT NULL,
  `id_producto` INT NOT NULL,
  `id_lote` INT DEFAULT NULL,
  `id_unidad` INT NOT NULL,
  `cantidad_recibida` INT NOT NULL,
  KEY `idx_det_rec_recepcion` (`id_recepcion`),
  KEY `idx_det_rec_det_compra` (`id_detalle_compra`),
  KEY `idx_det_rec_producto` (`id_producto`),
  KEY `idx_det_rec_lote` (`id_lote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `PAGO_PROVEEDOR` (
  `id_pago` INT AUTO_INCREMENT PRIMARY KEY,
  `id_compra` INT NOT NULL,
  `metodo_pago` ENUM('efectivo', 'transferencia', 'cheque', 'echeq', 'tarjeta') NOT NULL,
  `importe` DECIMAL(12,2) NOT NULL,
  `fecha_pago` DATE NOT NULL,
  `id_usuario` INT NOT NULL,
  KEY `idx_pago_compra` (`id_compra`),
  KEY `idx_pago_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- MOVIMIENTO_STOCK (Kardex de Inventario)
-- -----------------------------------------------------------------------------
CREATE TABLE `MOVIMIENTO_STOCK` (
  `id_movimiento` INT AUTO_INCREMENT PRIMARY KEY,
  `id_producto` INT NOT NULL,
  `id_unidad` INT DEFAULT NULL,
  `tipo` ENUM('ingreso', 'venta', 'devolucion', 'ajuste') NOT NULL,
  `cantidad` INT NOT NULL,
  `cantidad_base` INT DEFAULT NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `motivo` VARCHAR(255) DEFAULT NULL,
  `id_usuario` INT NOT NULL,
  `id_venta` INT DEFAULT NULL,
  `id_entrega` INT DEFAULT NULL,
  `id_recepcion` INT DEFAULT NULL,
  `id_sesion_conteo` INT DEFAULT NULL,
  `id_lote` INT DEFAULT NULL,
  KEY `idx_mov_stock_producto_fecha` (`id_producto`, `fecha`),
  KEY `idx_mov_stock_tipo` (`tipo`),
  KEY `idx_mov_stock_lote` (`id_lote`),
  KEY `idx_mov_stock_recepcion` (`id_recepcion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Libro kardex de auditoria de inventario (S12)';


-- -----------------------------------------------------------------------------
-- TABLAS DEL GRUPO 4: VENTAS, PROMOCIONES Y COBROS
-- -----------------------------------------------------------------------------

DROP TABLE IF EXISTS `PROMOCION_PRODUCTO`;
DROP TABLE IF EXISTS `DETALLE_VENTA`;
DROP TABLE IF EXISTS `COBRO_VENTA`;
DROP TABLE IF EXISTS `CAJA_MOVIMIENTO`;
DROP TABLE IF EXISTS `COBRO`;
DROP TABLE IF EXISTS `VENTA`;
DROP TABLE IF EXISTS `PROMOCION`;

CREATE TABLE `PROMOCION` (
  `id_promocion` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(120) NOT NULL,
  `total` DECIMAL(12,2) DEFAULT NULL,
  `vigencia_desde` DATE DEFAULT NULL,
  `vigencia_hasta` DATE DEFAULT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `estado` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `VENTA` (
  `id_venta` INT AUTO_INCREMENT PRIMARY KEY,
  `id_cliente` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `numFactura` VARCHAR(50) DEFAULT NULL,
  `estado` ENUM('pendiente', 'confirmada', 'pagada', 'facturada', 'cancelada') NOT NULL DEFAULT 'pendiente',
  `observaciones` VARCHAR(255) DEFAULT NULL,
  `id_usuario` INT NOT NULL,
  KEY `idx_venta_cliente` (`id_cliente`),
  KEY `idx_venta_fecha` (`fecha`),
  KEY `idx_venta_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `DETALLE_VENTA` (
  `id_detalle_venta` INT AUTO_INCREMENT PRIMARY KEY,
  `id_venta` INT NOT NULL,
  `id_producto` INT NOT NULL,
  `id_promocion` INT DEFAULT NULL,
  `cantidad` INT NOT NULL,
  `precio_unitario` DECIMAL(12,2) NOT NULL,
  `descuento` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(12,2) NOT NULL,
  KEY `idx_det_venta_venta` (`id_venta`),
  KEY `idx_det_venta_producto` (`id_producto`),
  KEY `idx_det_venta_promocion` (`id_promocion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `PROMOCION_PRODUCTO` (
  `id_promocion` INT NOT NULL,
  `id_producto` INT NOT NULL,
  PRIMARY KEY (`id_promocion`, `id_producto`),
  KEY `idx_promo_prod_producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `COBRO` (
  `id_cobro` INT AUTO_INCREMENT PRIMARY KEY,
  `id_cliente` INT NOT NULL,
  `id_usuario` INT NOT NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `monto_total` DECIMAL(12,2) NOT NULL,
  `medio_pago` ENUM('efectivo', 'transferencia', 'cheque', 'echeq', 'tarjeta') NOT NULL,
  `comprobante_nro` VARCHAR(80) DEFAULT NULL,
  `estado` ENUM('pendiente', 'aprobado', 'anulado') NOT NULL DEFAULT 'aprobado',
  `observaciones` TEXT DEFAULT NULL,
  `motivo_anulacion` TEXT DEFAULT NULL,
  `fecha_anulacion` DATETIME DEFAULT NULL,
  `id_usuario_anulacion` INT DEFAULT NULL,
  KEY `idx_cobro_cliente` (`id_cliente`),
  KEY `idx_cobro_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `COBRO_VENTA` (
  `id_cobro` INT NOT NULL,
  `id_venta` INT NOT NULL,
  `monto_aplicado` DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`id_cobro`, `id_venta`),
  KEY `idx_cobro_venta_venta` (`id_venta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `CAJA_MOVIMIENTO` (
  `id_movimiento_caja` INT AUTO_INCREMENT PRIMARY KEY,
  `fecha` DATE NOT NULL,
  `monto` DECIMAL(12,2) NOT NULL,
  `tipo` ENUM('ingreso', 'egreso') NOT NULL,
  `concepto` VARCHAR(255) NOT NULL,
  `id_usuario` INT NOT NULL,
  `id_cobro` INT DEFAULT NULL,
  KEY `idx_caja_fecha` (`fecha`),
  KEY `idx_caja_cobro` (`id_cobro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- TABLAS DEL GRUPO 2: REPARTOS, ENTREGAS, REMITOS Y RENDICIONES
-- -----------------------------------------------------------------------------

DROP TABLE IF EXISTS `RENDICION_DEVOLUCION`;
DROP TABLE IF EXISTS `RENDICION_DIFERENCIA`;
DROP TABLE IF EXISTS `RENDICION_REMITO`;
DROP TABLE IF EXISTS `RENDICION_COBRO`;
DROP TABLE IF EXISTS `RENDICION`;
DROP TABLE IF EXISTS `DETALLE_REMITO`;
DROP TABLE IF EXISTS `REMITO`;
DROP TABLE IF EXISTS `ENTREGA_PEDIDO`;
DROP TABLE IF EXISTS `ENTREGA`;

CREATE TABLE `ENTREGA` (
  `id_entrega` INT AUTO_INCREMENT PRIMARY KEY,
  `id_repartidor` INT NOT NULL,
  `fecha_creacion` DATE NOT NULL,
  `fecha_salida` DATE DEFAULT NULL,
  `estado` ENUM('pendiente', 'en_transito', 'entregada', 'no_entregada') NOT NULL DEFAULT 'pendiente',
  `observaciones` VARCHAR(255) DEFAULT NULL,
  `id_usuario_creacion` INT NOT NULL,
  KEY `idx_entrega_repartidor` (`id_repartidor`),
  KEY `idx_entrega_fecha` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENTREGA_PEDIDO` (
  `id_entrega` INT NOT NULL,
  `id_venta` INT NOT NULL,
  `estado_pedido_entega` ENUM('pendiente', 'en_transito', 'entregada', 'no_entregada') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`id_entrega`, `id_venta`),
  KEY `idx_entrega_ped_venta` (`id_venta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `REMITO` (
  `id_remito` INT AUTO_INCREMENT PRIMARY KEY,
  `numero_remito` VARCHAR(50) NOT NULL UNIQUE,
  `id_entrega` INT DEFAULT NULL,
  `id_venta` INT DEFAULT NULL,
  `fecha_emision` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` ENUM('emitido', 'en_viaje', 'entregado', 'anulado') NOT NULL DEFAULT 'emitido',
  `observaciones` TEXT DEFAULT NULL,
  KEY `idx_remito_entrega` (`id_entrega`),
  KEY `idx_remito_venta` (`id_venta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `DETALLE_REMITO` (
  `id_detalle_remito` INT AUTO_INCREMENT PRIMARY KEY,
  `id_remito` INT NOT NULL,
  `id_producto` INT NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `cantidad` INT NOT NULL,
  `bultos` INT DEFAULT NULL,
  KEY `idx_det_remito_remito` (`id_remito`),
  KEY `idx_det_remito_producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `RENDICION` (
  `id_rendicion` INT AUTO_INCREMENT PRIMARY KEY,
  `id_reparto` INT DEFAULT NULL,
  `id_repartidor` INT NOT NULL,
  `fecha_rendicion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_rendido` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `estado` ENUM('pendiente', 'aprobada', 'rechazada') NOT NULL DEFAULT 'pendiente',
  `id_usuario_validador` INT DEFAULT NULL,
  `fecha_validacion` DATETIME DEFAULT NULL,
  `motivo_rechazo` TEXT DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  KEY `idx_rendicion_repartidor` (`id_repartidor`),
  KEY `idx_rendicion_fecha` (`fecha_rendicion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `RENDICION_COBRO` (
  `id_rendicion_cobro` INT AUTO_INCREMENT PRIMARY KEY,
  `id_rendicion` INT NOT NULL,
  `id_cliente` INT NOT NULL,
  `id_factura` INT DEFAULT NULL,
  `monto` DECIMAL(12,2) NOT NULL,
  `medio_pago` ENUM('efectivo', 'transferencia', 'cheque', 'echeq', 'tarjeta') NOT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_rend_cobro_rendicion` (`id_rendicion`),
  KEY `idx_rend_cobro_cliente` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `RENDICION_REMITO` (
  `id_rendicion` INT NOT NULL,
  `id_remito` INT NOT NULL,
  PRIMARY KEY (`id_rendicion`, `id_remito`),
  KEY `idx_rend_remito_remito` (`id_remito`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `RENDICION_DIFERENCIA` (
  `id_diferencia` INT AUTO_INCREMENT PRIMARY KEY,
  `id_rendicion` INT NOT NULL,
  `total_esperado` DECIMAL(12,2) NOT NULL,
  `total_rendido` DECIMAL(12,2) NOT NULL,
  `diferencia` DECIMAL(12,2) NOT NULL,
  `tipo_diferencia` ENUM('sobrante', 'faltante', 'cuadrado') NOT NULL,
  `motivo` TEXT DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `diferencia_pendiente` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_auditoria` DATETIME DEFAULT NULL,
  KEY `idx_rend_dif_rendicion` (`id_rendicion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `RENDICION_DEVOLUCION` (
  `id_devolucion` INT AUTO_INCREMENT PRIMARY KEY,
  `id_rendicion` INT NOT NULL,
  `id_pedido` INT DEFAULT NULL,
  `motivo` VARCHAR(255) DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `estado_entrega` VARCHAR(50) DEFAULT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_rend_dev_rendicion` (`id_rendicion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- RELACIONES Y CLAVES FORANEAS (ALTER TABLE)
-- -----------------------------------------------------------------------------

-- Grupo 1 (CLIENTE, RECLAMO, LOGIN_LOG)
ALTER TABLE `CLIENTE` ADD CONSTRAINT `fk_cliente_zona` FOREIGN KEY (`id_zona`) REFERENCES `ZONA` (`id_zona`) ON DELETE SET NULL;
ALTER TABLE `CLIENTE` ADD CONSTRAINT `fk_cliente_usuario` FOREIGN KEY (`creado_por`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;
ALTER TABLE `LOGIN_LOG` ADD CONSTRAINT `fk_login_log_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE CASCADE;
ALTER TABLE `RECLAMO` ADD CONSTRAINT `fk_reclamo_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `CLIENTE` (`id_cliente`) ON DELETE RESTRICT;
ALTER TABLE `RECLAMO` ADD CONSTRAINT `fk_reclamo_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;
ALTER TABLE `LOG_AUDITORIA` ADD CONSTRAINT `fk_log_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE SET NULL;

-- Grupo 3 (PRODUCTO, HISTORIAL_PRECIO, UNIDAD_MEDIDA, LOTES, CONTEOS, UBICACIONES)
ALTER TABLE `PRODUCTO` ADD CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `CATEGORIA` (`id_categoria`) ON DELETE RESTRICT;
ALTER TABLE `PRODUCTO` ADD CONSTRAINT `fk_producto_marca` FOREIGN KEY (`id_marca`) REFERENCES `MARCA` (`id_marca`) ON DELETE RESTRICT;
ALTER TABLE `PRODUCTO` ADD CONSTRAINT `fk_producto_usuario_carga` FOREIGN KEY (`id_usuario_carga`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;
ALTER TABLE `PRODUCTO` ADD CONSTRAINT `fk_producto_usuario_mod` FOREIGN KEY (`id_usuario_modificacion`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE SET NULL;

ALTER TABLE `HISTORIAL_PRECIO` ADD CONSTRAINT `fk_hist_precio_producto` FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`) ON DELETE CASCADE;
ALTER TABLE `HISTORIAL_PRECIO` ADD CONSTRAINT `fk_hist_precio_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;

ALTER TABLE `UNIDAD_MEDIDA` ADD CONSTRAINT `fk_unidad_producto` FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`) ON DELETE CASCADE;

ALTER TABLE `LOTE` ADD CONSTRAINT `fk_lote_producto` FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`) ON DELETE RESTRICT;

ALTER TABLE `PRODUCTO_UBICACION` ADD CONSTRAINT `fk_prod_ubic_producto` FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`) ON DELETE CASCADE;
ALTER TABLE `PRODUCTO_UBICACION` ADD CONSTRAINT `fk_prod_ubic_ubicacion` FOREIGN KEY (`id_ubicacion`) REFERENCES `UBICACION` (`id_ubicacion`) ON DELETE CASCADE;
ALTER TABLE `PRODUCTO_UBICACION` ADD CONSTRAINT `fk_prod_ubic_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE SET NULL;

ALTER TABLE `SESION_CONTEO` ADD CONSTRAINT `fk_conteo_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;
ALTER TABLE `DETALLE_CONTEO` ADD CONSTRAINT `fk_det_conteo_sesion` FOREIGN KEY (`id_sesion`) REFERENCES `SESION_CONTEO` (`id_sesion`) ON DELETE CASCADE;
ALTER TABLE `DETALLE_CONTEO` ADD CONSTRAINT `fk_det_conteo_producto` FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`) ON DELETE RESTRICT;

-- Grupo 3 (PROVEEDORES Y RELACIONES)
ALTER TABLE `PROVEEDOR` ADD CONSTRAINT `fk_proveedor_usuario_carga` FOREIGN KEY (`id_usuario_carga`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;
ALTER TABLE `PROVEEDOR` ADD CONSTRAINT `fk_proveedor_usuario_mod` FOREIGN KEY (`id_usuario_modificacion`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE SET NULL;

ALTER TABLE `PRODUCTO_PROVEEDOR` ADD CONSTRAINT `fk_prod_prov_producto` FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`) ON DELETE CASCADE;
ALTER TABLE `PRODUCTO_PROVEEDOR` ADD CONSTRAINT `fk_prod_prov_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `PROVEEDOR` (`id_proveedor`) ON DELETE CASCADE;
ALTER TABLE `PRODUCTO_PROVEEDOR` ADD CONSTRAINT `fk_prod_prov_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `UNIDAD_MEDIDA` (`id_unidad`) ON DELETE SET NULL;
ALTER TABLE `PRODUCTO_PROVEEDOR` ADD CONSTRAINT `fk_prod_prov_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE SET NULL;

ALTER TABLE `HISTORIAL_PRECIO_PROVEEDOR` ADD CONSTRAINT `fk_hist_prov_prod_prov` FOREIGN KEY (`id_producto_proveedor`) REFERENCES `PRODUCTO_PROVEEDOR` (`id_producto_proveedor`) ON DELETE CASCADE;
ALTER TABLE `HISTORIAL_PRECIO_PROVEEDOR` ADD CONSTRAINT `fk_hist_prov_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `PROVEEDOR` (`id_proveedor`) ON DELETE RESTRICT;
ALTER TABLE `HISTORIAL_PRECIO_PROVEEDOR` ADD CONSTRAINT `fk_hist_prov_producto` FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`) ON DELETE RESTRICT;
ALTER TABLE `HISTORIAL_PRECIO_PROVEEDOR` ADD CONSTRAINT `fk_hist_prov_compra` FOREIGN KEY (`id_compra`) REFERENCES `COMPRA` (`id_compra`) ON DELETE SET NULL;
ALTER TABLE `HISTORIAL_PRECIO_PROVEEDOR` ADD CONSTRAINT `fk_hist_prov_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `UNIDAD_MEDIDA` (`id_unidad`) ON DELETE SET NULL;
ALTER TABLE `HISTORIAL_PRECIO_PROVEEDOR` ADD CONSTRAINT `fk_hist_prov_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE SET NULL;

-- Grupo 3 (ORDEN_COMPRA, COMPRA, RECEPCION, PAGO)
ALTER TABLE `ORDEN_COMPRA` ADD CONSTRAINT `fk_orden_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `PROVEEDOR` (`id_proveedor`) ON DELETE RESTRICT;
ALTER TABLE `ORDEN_COMPRA` ADD CONSTRAINT `fk_orden_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;

ALTER TABLE `DETALLE_ORDEN` ADD CONSTRAINT `fk_det_orden_orden` FOREIGN KEY (`id_orden`) REFERENCES `ORDEN_COMPRA` (`id_orden`) ON DELETE CASCADE;
ALTER TABLE `DETALLE_ORDEN` ADD CONSTRAINT `fk_det_orden_producto` FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`) ON DELETE RESTRICT;
ALTER TABLE `DETALLE_ORDEN` ADD CONSTRAINT `fk_det_orden_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `UNIDAD_MEDIDA` (`id_unidad`) ON DELETE RESTRICT;

ALTER TABLE `COMPRA` ADD CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `PROVEEDOR` (`id_proveedor`) ON DELETE RESTRICT;
ALTER TABLE `COMPRA` ADD CONSTRAINT `fk_compra_orden` FOREIGN KEY (`id_orden`) REFERENCES `ORDEN_COMPRA` (`id_orden`) ON DELETE SET NULL;
ALTER TABLE `COMPRA` ADD CONSTRAINT `fk_compra_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;
ALTER TABLE `COMPRA` ADD CONSTRAINT `fk_compra_usuario_mod` FOREIGN KEY (`id_usuario_modificacion`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE SET NULL;

ALTER TABLE `DETALLE_COMPRA` ADD CONSTRAINT `fk_det_compra_compra` FOREIGN KEY (`id_compra`) REFERENCES `COMPRA` (`id_compra`) ON DELETE CASCADE;
ALTER TABLE `DETALLE_COMPRA` ADD CONSTRAINT `fk_det_compra_producto` FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`) ON DELETE RESTRICT;
ALTER TABLE `DETALLE_COMPRA` ADD CONSTRAINT `fk_det_compra_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `UNIDAD_MEDIDA` (`id_unidad`) ON DELETE RESTRICT;

ALTER TABLE `RECEPCION` ADD CONSTRAINT `fk_recepcion_compra` FOREIGN KEY (`id_compra`) REFERENCES `COMPRA` (`id_compra`) ON DELETE SET NULL;
ALTER TABLE `RECEPCION` ADD CONSTRAINT `fk_recepcion_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `PROVEEDOR` (`id_proveedor`) ON DELETE RESTRICT;
ALTER TABLE `RECEPCION` ADD CONSTRAINT `fk_recepcion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;

ALTER TABLE `DETALLE_RECEPCION` ADD CONSTRAINT `fk_det_rec_recepcion` FOREIGN KEY (`id_recepcion`) REFERENCES `RECEPCION` (`id_recepcion`) ON DELETE CASCADE;
ALTER TABLE `DETALLE_RECEPCION` ADD CONSTRAINT `fk_det_rec_det_compra` FOREIGN KEY (`id_detalle_compra`) REFERENCES `DETALLE_COMPRA` (`id_detalle_compra`) ON DELETE SET NULL;
ALTER TABLE `DETALLE_RECEPCION` ADD CONSTRAINT `fk_det_rec_producto` FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`) ON DELETE RESTRICT;
ALTER TABLE `DETALLE_RECEPCION` ADD CONSTRAINT `fk_det_rec_lote` FOREIGN KEY (`id_lote`) REFERENCES `LOTE` (`id_lote`) ON DELETE SET NULL;
ALTER TABLE `DETALLE_RECEPCION` ADD CONSTRAINT `fk_det_rec_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `UNIDAD_MEDIDA` (`id_unidad`) ON DELETE RESTRICT;

ALTER TABLE `PAGO_PROVEEDOR` ADD CONSTRAINT `fk_pago_compra` FOREIGN KEY (`id_compra`) REFERENCES `COMPRA` (`id_compra`) ON DELETE CASCADE;
ALTER TABLE `PAGO_PROVEEDOR` ADD CONSTRAINT `fk_pago_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;

-- Grupo 3: MOVIMIENTO_STOCK (Kardex integral)
ALTER TABLE `MOVIMIENTO_STOCK` ADD CONSTRAINT `fk_mov_stock_producto` FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`) ON DELETE RESTRICT;
ALTER TABLE `MOVIMIENTO_STOCK` ADD CONSTRAINT `fk_mov_stock_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `UNIDAD_MEDIDA` (`id_unidad`) ON DELETE SET NULL;
ALTER TABLE `MOVIMIENTO_STOCK` ADD CONSTRAINT `fk_mov_stock_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;
ALTER TABLE `MOVIMIENTO_STOCK` ADD CONSTRAINT `fk_mov_stock_venta` FOREIGN KEY (`id_venta`) REFERENCES `VENTA` (`id_venta`) ON DELETE SET NULL;
ALTER TABLE `MOVIMIENTO_STOCK` ADD CONSTRAINT `fk_mov_stock_entrega` FOREIGN KEY (`id_entrega`) REFERENCES `ENTREGA` (`id_entrega`) ON DELETE SET NULL;
ALTER TABLE `MOVIMIENTO_STOCK` ADD CONSTRAINT `fk_mov_stock_recepcion` FOREIGN KEY (`id_recepcion`) REFERENCES `RECEPCION` (`id_recepcion`) ON DELETE SET NULL;
ALTER TABLE `MOVIMIENTO_STOCK` ADD CONSTRAINT `fk_mov_stock_conteo` FOREIGN KEY (`id_sesion_conteo`) REFERENCES `SESION_CONTEO` (`id_sesion`) ON DELETE SET NULL;
ALTER TABLE `MOVIMIENTO_STOCK` ADD CONSTRAINT `fk_mov_stock_lote` FOREIGN KEY (`id_lote`) REFERENCES `LOTE` (`id_lote`) ON DELETE SET NULL;

-- Grupo 4 (VENTA, DETALLE_VENTA, PROMOCION, COBRO)
ALTER TABLE `VENTA` ADD CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `CLIENTE` (`id_cliente`) ON DELETE RESTRICT;
ALTER TABLE `VENTA` ADD CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;

ALTER TABLE `DETALLE_VENTA` ADD CONSTRAINT `fk_det_venta_venta` FOREIGN KEY (`id_venta`) REFERENCES `VENTA` (`id_venta`) ON DELETE CASCADE;
ALTER TABLE `DETALLE_VENTA` ADD CONSTRAINT `fk_det_venta_producto` FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`) ON DELETE RESTRICT;
ALTER TABLE `DETALLE_VENTA` ADD CONSTRAINT `fk_det_venta_promo` FOREIGN KEY (`id_promocion`) REFERENCES `PROMOCION` (`id_promocion`) ON DELETE SET NULL;

ALTER TABLE `PROMOCION_PRODUCTO` ADD CONSTRAINT `fk_promo_prod_promo` FOREIGN KEY (`id_promocion`) REFERENCES `PROMOCION` (`id_promocion`) ON DELETE CASCADE;
ALTER TABLE `PROMOCION_PRODUCTO` ADD CONSTRAINT `fk_promo_prod_producto` FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`) ON DELETE CASCADE;

ALTER TABLE `COBRO` ADD CONSTRAINT `fk_cobro_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `CLIENTE` (`id_cliente`) ON DELETE RESTRICT;
ALTER TABLE `COBRO` ADD CONSTRAINT `fk_cobro_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;
ALTER TABLE `COBRO` ADD CONSTRAINT `fk_cobro_usuario_anul` FOREIGN KEY (`id_usuario_anulacion`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE SET NULL;

ALTER TABLE `COBRO_VENTA` ADD CONSTRAINT `fk_cobro_venta_cobro` FOREIGN KEY (`id_cobro`) REFERENCES `COBRO` (`id_cobro`) ON DELETE CASCADE;
ALTER TABLE `COBRO_VENTA` ADD CONSTRAINT `fk_cobro_venta_venta` FOREIGN KEY (`id_venta`) REFERENCES `VENTA` (`id_venta`) ON DELETE RESTRICT;

ALTER TABLE `CAJA_MOVIMIENTO` ADD CONSTRAINT `fk_caja_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;
ALTER TABLE `CAJA_MOVIMIENTO` ADD CONSTRAINT `fk_caja_cobro` FOREIGN KEY (`id_cobro`) REFERENCES `COBRO` (`id_cobro`) ON DELETE SET NULL;

-- Grupo 2 (ENTREGA, REMITO, RENDICION)
ALTER TABLE `ENTREGA` ADD CONSTRAINT `fk_entrega_repartidor` FOREIGN KEY (`id_repartidor`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;
ALTER TABLE `ENTREGA` ADD CONSTRAINT `fk_entrega_usuario_crea` FOREIGN KEY (`id_usuario_creacion`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;

ALTER TABLE `ENTREGA_PEDIDO` ADD CONSTRAINT `fk_ent_ped_entrega` FOREIGN KEY (`id_entrega`) REFERENCES `ENTREGA` (`id_entrega`) ON DELETE CASCADE;
ALTER TABLE `ENTREGA_PEDIDO` ADD CONSTRAINT `fk_ent_ped_venta` FOREIGN KEY (`id_venta`) REFERENCES `VENTA` (`id_venta`) ON DELETE CASCADE;

ALTER TABLE `REMITO` ADD CONSTRAINT `fk_remito_entrega` FOREIGN KEY (`id_entrega`) REFERENCES `ENTREGA` (`id_entrega`) ON DELETE SET NULL;
ALTER TABLE `REMITO` ADD CONSTRAINT `fk_remito_venta` FOREIGN KEY (`id_venta`) REFERENCES `VENTA` (`id_venta`) ON DELETE SET NULL;

ALTER TABLE `DETALLE_REMITO` ADD CONSTRAINT `fk_det_remito_remito` FOREIGN KEY (`id_remito`) REFERENCES `REMITO` (`id_remito`) ON DELETE CASCADE;
ALTER TABLE `DETALLE_REMITO` ADD CONSTRAINT `fk_det_remito_producto` FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`) ON DELETE RESTRICT;

ALTER TABLE `RENDICION` ADD CONSTRAINT `fk_rendicion_repartidor` FOREIGN KEY (`id_repartidor`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT;
ALTER TABLE `RENDICION` ADD CONSTRAINT `fk_rendicion_validador` FOREIGN KEY (`id_usuario_validador`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE SET NULL;

ALTER TABLE `RENDICION_COBRO` ADD CONSTRAINT `fk_rend_cobro_rendicion` FOREIGN KEY (`id_rendicion`) REFERENCES `RENDICION` (`id_rendicion`) ON DELETE CASCADE;
ALTER TABLE `RENDICION_COBRO` ADD CONSTRAINT `fk_rend_cobro_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `CLIENTE` (`id_cliente`) ON DELETE RESTRICT;

ALTER TABLE `RENDICION_REMITO` ADD CONSTRAINT `fk_rend_remito_rendicion` FOREIGN KEY (`id_rendicion`) REFERENCES `RENDICION` (`id_rendicion`) ON DELETE CASCADE;
ALTER TABLE `RENDICION_REMITO` ADD CONSTRAINT `fk_rend_remito_remito` FOREIGN KEY (`id_remito`) REFERENCES `REMITO` (`id_remito`) ON DELETE CASCADE;

ALTER TABLE `RENDICION_DIFERENCIA` ADD CONSTRAINT `fk_rend_dif_rendicion` FOREIGN KEY (`id_rendicion`) REFERENCES `RENDICION` (`id_rendicion`) ON DELETE CASCADE;

ALTER TABLE `RENDICION_DEVOLUCION` ADD CONSTRAINT `fk_rend_dev_rendicion` FOREIGN KEY (`id_rendicion`) REFERENCES `RENDICION` (`id_rendicion`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;\n