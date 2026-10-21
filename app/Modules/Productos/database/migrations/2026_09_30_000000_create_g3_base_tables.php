<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        if (! Schema::hasTable('usuario')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `rol` enum('administrativo','repartidor','contador','vendedor','deposito') NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `intentos_fallidos` tinyint(4) NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `ultimo_intento_fallido` datetime DEFAULT NULL,
  `current_session_id` varchar(128) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios internos del ERP. Incluye roles de ventas y deposito'
SQL);
        }

        if (! Schema::hasTable('categoria')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `categoria` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `fecha_creacion` date NOT NULL,
  `fecha_modificacion` date DEFAULT NULL,
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! Schema::hasTable('marca')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `marca` (
  `id_marca` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `fecha_creacion` date NOT NULL,
  `fecha_modificacion` date DEFAULT NULL,
  PRIMARY KEY (`id_marca`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! Schema::hasTable('producto')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `producto` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `precio_unitario` decimal(12,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) NOT NULL DEFAULT 0,
  `dias_alerta_vencimiento` int(11) NOT NULL DEFAULT 30,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `fecha_alta` date NOT NULL,
  `fecha_modificacion` date DEFAULT NULL,
  `fecha_desactivacion` date DEFAULT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_marca` int(11) NOT NULL,
  `id_usuario_carga` int(11) NOT NULL,
  `id_usuario_modificacion` int(11) DEFAULT NULL,
  `formato_bulto` varchar(50) DEFAULT 'Unidad',
  `unidades_por_bulto` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `idx_producto_categoria` (`id_categoria`),
  KEY `idx_producto_marca` (`id_marca`),
  KEY `idx_producto_nombre` (`nombre`),
  KEY `fk_producto_usuario_carga` (`id_usuario_carga`),
  KEY `fk_producto_usuario_mod` (`id_usuario_modificacion`),
  CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`) ON DELETE NO ACTION,
  CONSTRAINT `fk_producto_marca` FOREIGN KEY (`id_marca`) REFERENCES `marca` (`id_marca`) ON DELETE NO ACTION,
  CONSTRAINT `fk_producto_usuario_carga` FOREIGN KEY (`id_usuario_carga`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION,
  CONSTRAINT `fk_producto_usuario_mod` FOREIGN KEY (`id_usuario_modificacion`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL,
  CONSTRAINT `chk_producto_stock_no_negativo` CHECK (`stock` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catalogo maestro de articulos. Precio unitario de lista base y stock embebido'
SQL);
        }

        if (! Schema::hasTable('proveedor')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `proveedor` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `razon_social` varchar(160) NOT NULL,
  `contacto` varchar(160) DEFAULT NULL,
  `CUIT` varchar(20) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(160) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `plazo_entrega_dias` int(11) DEFAULT NULL,
  `fecha_alta` date NOT NULL,
  `fecha_modificacion` date DEFAULT NULL,
  `fecha_desactivacion` date DEFAULT NULL,
  `id_usuario_carga` int(11) NOT NULL,
  `id_usuario_modificacion` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_proveedor`),
  UNIQUE KEY `CUIT` (`CUIT`),
  KEY `idx_proveedor_razon` (`razon_social`),
  KEY `fk_proveedor_usuario_carga` (`id_usuario_carga`),
  KEY `fk_proveedor_usuario_mod` (`id_usuario_modificacion`),
  CONSTRAINT `fk_proveedor_usuario_carga` FOREIGN KEY (`id_usuario_carga`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION,
  CONSTRAINT `fk_proveedor_usuario_mod` FOREIGN KEY (`id_usuario_modificacion`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! Schema::hasTable('unidad_medida')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `unidad_medida` (
  `id_unidad` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `nombre_unidad` varchar(50) NOT NULL,
  `equivalencia_base` int(11) NOT NULL DEFAULT 1,
  `es_base` tinyint(1) NOT NULL DEFAULT 0,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_unidad`),
  UNIQUE KEY `uq_unidad_producto_nombre` (`id_producto`,`nombre_unidad`),
  KEY `idx_unidad_producto` (`id_producto`),
  CONSTRAINT `fk_unidad_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unidades y equivalencias relativas a la unidad base (S08)'
SQL);
        }

        if (! Schema::hasTable('ubicacion')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `ubicacion` (
  `id_ubicacion` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `fecha_creacion` date DEFAULT NULL,
  PRIMARY KEY (`id_ubicacion`),
  UNIQUE KEY `descripcion` (`descripcion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sectores/pasillos del deposito fisico (S14)'
SQL);
        }

        if (! Schema::hasTable('producto_ubicacion')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `producto_ubicacion` (
  `id_producto` int(11) NOT NULL,
  `id_ubicacion` int(11) NOT NULL,
  `fecha_asignacion` date DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_producto`,`id_ubicacion`),
  KEY `idx_prod_ubic_ubicacion` (`id_ubicacion`),
  KEY `fk_prod_ubic_usuario` (`id_usuario`),
  CONSTRAINT `fk_prod_ubic_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE,
  CONSTRAINT `fk_prod_ubic_ubicacion` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`) ON DELETE CASCADE,
  CONSTRAINT `fk_prod_ubic_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! Schema::hasTable('lote')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `lote` (
  `id_lote` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `nro_lote` varchar(50) NOT NULL,
  `cantidad_inicial` int(11) NOT NULL,
  `cantidad_actual` int(11) NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  PRIMARY KEY (`id_lote`),
  UNIQUE KEY `uq_lote_producto_nro` (`id_producto`,`nro_lote`),
  KEY `idx_lote_producto_vencimiento` (`id_producto`,`fecha_vencimiento`),
  CONSTRAINT `fk_lote_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lotes con vencimiento en unidad base. Rompe referencia circular (S09)'
SQL);
        }

        if (! Schema::hasTable('movimiento_stock')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `movimiento_stock` (
  `id_movimiento` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `tipo` enum('ingreso','venta','devolucion','ajuste') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `cantidad_base` int(11) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `motivo` varchar(255) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_venta` int(11) DEFAULT NULL,
  `id_entrega` int(11) DEFAULT NULL,
  `id_recepcion` int(11) DEFAULT NULL,
  `id_sesion_conteo` int(11) DEFAULT NULL,
  `id_lote` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_movimiento`),
  KEY `idx_mov_stock_producto_fecha` (`id_producto`,`fecha`),
  KEY `idx_mov_stock_tipo` (`tipo`),
  KEY `idx_mov_stock_lote` (`id_lote`),
  KEY `idx_mov_stock_recepcion` (`id_recepcion`),
  KEY `fk_mov_stock_unidad` (`id_unidad`),
  KEY `fk_mov_stock_usuario` (`id_usuario`),
  KEY `fk_mov_stock_venta` (`id_venta`),
  KEY `fk_mov_stock_entrega` (`id_entrega`),
  KEY `fk_mov_stock_conteo` (`id_sesion_conteo`),
  CONSTRAINT `fk_mov_stock_conteo` FOREIGN KEY (`id_sesion_conteo`) REFERENCES `sesion_conteo` (`id_sesion`) ON DELETE SET NULL,
  CONSTRAINT `fk_mov_stock_entrega` FOREIGN KEY (`id_entrega`) REFERENCES `entrega` (`id_entrega`) ON DELETE SET NULL,
  CONSTRAINT `fk_mov_stock_lote` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`) ON DELETE SET NULL,
  CONSTRAINT `fk_mov_stock_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE NO ACTION,
  CONSTRAINT `fk_mov_stock_recepcion` FOREIGN KEY (`id_recepcion`) REFERENCES `recepcion` (`id_recepcion`) ON DELETE SET NULL,
  CONSTRAINT `fk_mov_stock_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidad_medida` (`id_unidad`) ON DELETE SET NULL,
  CONSTRAINT `fk_mov_stock_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION,
  CONSTRAINT `fk_mov_stock_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Libro kardex de auditoria de inventario (S12)'
SQL);
        }

        if (! Schema::hasTable('sesion_conteo')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `sesion_conteo` (
  `id_sesion` int(11) NOT NULL AUTO_INCREMENT,
  `estado` enum('en_proceso','finalizado') NOT NULL DEFAULT 'en_proceso',
  `fecha_conteo` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `total_productos` int(11) DEFAULT 0,
  `total_diferencias` int(11) DEFAULT 0,
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id_sesion`),
  KEY `idx_sesion_conteo_usuario` (`id_usuario`),
  CONSTRAINT `fk_conteo_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! Schema::hasTable('detalle_conteo')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `detalle_conteo` (
  `id_detalle_conteo` int(11) NOT NULL AUTO_INCREMENT,
  `id_sesion` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad_fisica` int(11) DEFAULT NULL,
  `stock_sistema` int(11) DEFAULT NULL,
  `diferencia` int(11) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_detalle_conteo`),
  UNIQUE KEY `uq_conteo_sesion_prod` (`id_sesion`,`id_producto`),
  KEY `idx_detalle_conteo_prod` (`id_producto`),
  CONSTRAINT `fk_det_conteo_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE NO ACTION,
  CONSTRAINT `fk_det_conteo_sesion` FOREIGN KEY (`id_sesion`) REFERENCES `sesion_conteo` (`id_sesion`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! Schema::hasTable('historial_precio')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `historial_precio` (
  `id_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `tipo_precio` enum('general','mayorista','minorista') NOT NULL DEFAULT 'general',
  `precio` decimal(12,2) NOT NULL,
  `porcentaje_aumento` decimal(6,2) DEFAULT NULL,
  `regla_redondeo` varchar(50) DEFAULT 'sin_redondeo',
  `origen` enum('manual','aumento_masivo') NOT NULL DEFAULT 'manual',
  `fecha_cambio` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id_historial`),
  KEY `idx_historial_prod_fecha` (`id_producto`,`fecha_cambio`),
  KEY `idx_historial_usuario` (`id_usuario`),
  CONSTRAINT `fk_hist_precio_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE,
  CONSTRAINT `fk_hist_precio_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Trazabilidad de precios. Guarda unicamente precio resultante (OB1)'
SQL);
        }

        if (! Schema::hasTable('producto_proveedor')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `producto_proveedor` (
  `id_producto_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `es_proveedor_principal` tinyint(1) NOT NULL DEFAULT 0,
  `precio_acordado` decimal(12,2) DEFAULT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_asociacion` date DEFAULT NULL,
  `fecha_desasociacion` date DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_producto_proveedor`),
  KEY `idx_prod_prov_producto` (`id_producto`),
  KEY `idx_prod_prov_proveedor` (`id_proveedor`),
  KEY `fk_prod_prov_unidad` (`id_unidad`),
  KEY `fk_prod_prov_usuario` (`id_usuario`),
  CONSTRAINT `fk_prod_prov_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE,
  CONSTRAINT `fk_prod_prov_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`) ON DELETE CASCADE,
  CONSTRAINT `fk_prod_prov_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidad_medida` (`id_unidad`) ON DELETE SET NULL,
  CONSTRAINT `fk_prod_prov_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! Schema::hasTable('historial_precio_proveedor')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `historial_precio_proveedor` (
  `id_historial_prov` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto_proveedor` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_compra` int(11) DEFAULT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `precio_acordado` decimal(12,2) NOT NULL,
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp(),
  `observaciones` varchar(255) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_historial_prov`),
  KEY `idx_hist_prov_prod_prov` (`id_producto_proveedor`),
  KEY `idx_hist_prov_fecha` (`fecha_actualizacion`),
  KEY `fk_hist_prov_proveedor` (`id_proveedor`),
  KEY `fk_hist_prov_producto` (`id_producto`),
  KEY `fk_hist_prov_compra` (`id_compra`),
  KEY `fk_hist_prov_unidad` (`id_unidad`),
  KEY `fk_hist_prov_usuario` (`id_usuario`),
  CONSTRAINT `fk_hist_prov_compra` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON DELETE SET NULL,
  CONSTRAINT `fk_hist_prov_prod_prov` FOREIGN KEY (`id_producto_proveedor`) REFERENCES `producto_proveedor` (`id_producto_proveedor`) ON DELETE CASCADE,
  CONSTRAINT `fk_hist_prov_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE NO ACTION,
  CONSTRAINT `fk_hist_prov_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`) ON DELETE NO ACTION,
  CONSTRAINT `fk_hist_prov_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidad_medida` (`id_unidad`) ON DELETE SET NULL,
  CONSTRAINT `fk_hist_prov_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! Schema::hasTable('historial_plazo_proveedor')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `historial_plazo_proveedor` (
  `id_historial_plazo` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_proveedor` int(11) NOT NULL,
  `plazo_anterior_dias` int(11) DEFAULT NULL,
  `plazo_nuevo_dias` int(11) NOT NULL,
  `fecha_cambio` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id_historial_plazo`),
  KEY `idx_historial_plazo_proveedor_fecha` (`id_proveedor`,`fecha_cambio`),
  KEY `historial_plazo_proveedor_id_usuario_foreign` (`id_usuario`),
  CONSTRAINT `historial_plazo_proveedor_id_proveedor_foreign` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`) ON DELETE CASCADE,
  CONSTRAINT `historial_plazo_proveedor_id_usuario_foreign` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! Schema::hasTable('orden_compra')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `orden_compra` (
  `id_orden` int(11) NOT NULL AUTO_INCREMENT,
  `numero_orden` varchar(30) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `total_estimado` decimal(12,2) DEFAULT NULL,
  `estado` enum('pendiente','enviada','recibida_parcialmente','completada','cancelada') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` date NOT NULL,
  `fecha_modificacion` date DEFAULT NULL,
  `fecha_envio` date DEFAULT NULL,
  `fecha_entrega_estimada` date DEFAULT NULL,
  `fecha_cancelacion` date DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id_orden`),
  UNIQUE KEY `numero_orden` (`numero_orden`),
  KEY `idx_orden_proveedor` (`id_proveedor`),
  KEY `idx_orden_estado` (`estado`),
  KEY `fk_orden_usuario` (`id_usuario`),
  CONSTRAINT `fk_orden_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`) ON DELETE NO ACTION,
  CONSTRAINT `fk_orden_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! Schema::hasTable('detalle_orden')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `detalle_orden` (
  `id_detalle_orden` int(11) NOT NULL AUTO_INCREMENT,
  `id_orden` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `cantidad_solicitada` int(11) NOT NULL,
  `cantidad_sugerida` int(11) DEFAULT NULL,
  `origen` enum('manual','sugerencia') NOT NULL DEFAULT 'manual',
  `precio_estimado` decimal(12,2) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id_detalle_orden`),
  UNIQUE KEY `uq_detalle_orden_prod_unidad` (`id_orden`,`id_producto`,`id_unidad`),
  KEY `idx_det_orden_producto` (`id_producto`),
  KEY `fk_det_orden_unidad` (`id_unidad`),
  CONSTRAINT `fk_det_orden_orden` FOREIGN KEY (`id_orden`) REFERENCES `orden_compra` (`id_orden`) ON DELETE CASCADE,
  CONSTRAINT `fk_det_orden_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE NO ACTION,
  CONSTRAINT `fk_det_orden_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidad_medida` (`id_unidad`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! Schema::hasTable('compra')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `compra` (
  `id_compra` int(11) NOT NULL AUTO_INCREMENT,
  `numero_compra` varchar(30) NOT NULL,
  `numero_comprobante` varchar(40) DEFAULT NULL,
  `id_proveedor` int(11) NOT NULL,
  `id_orden` int(11) DEFAULT NULL,
  `importe_total` decimal(12,2) DEFAULT NULL,
  `saldo_pendiente` decimal(12,2) DEFAULT NULL,
  `estado` enum('pendiente','parcialmente_recibida','completada','cancelada') NOT NULL DEFAULT 'pendiente',
  `fecha_compra` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `fecha_cancelacion` date DEFAULT NULL,
  `fecha_modificacion` date DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_usuario_modificacion` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_compra`),
  UNIQUE KEY `numero_compra` (`numero_compra`),
  UNIQUE KEY `uq_compra_prov_comprobante` (`id_proveedor`,`numero_comprobante`),
  KEY `idx_compra_proveedor_fecha` (`id_proveedor`,`fecha_compra`),
  KEY `idx_compra_orden` (`id_orden`),
  KEY `fk_compra_usuario` (`id_usuario`),
  KEY `fk_compra_usuario_mod` (`id_usuario_modificacion`),
  CONSTRAINT `fk_compra_orden` FOREIGN KEY (`id_orden`) REFERENCES `orden_compra` (`id_orden`) ON DELETE SET NULL,
  CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`) ON DELETE NO ACTION,
  CONSTRAINT `fk_compra_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION,
  CONSTRAINT `fk_compra_usuario_mod` FOREIGN KEY (`id_usuario_modificacion`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Compras a proveedores. numero_compra es correlativo interno; numero_comprobante es del proveedor'
SQL);
        }

        if (! Schema::hasTable('detalle_compra')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `detalle_compra` (
  `id_detalle_compra` int(11) NOT NULL AUTO_INCREMENT,
  `id_compra` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `cantidad_recibida` int(11) NOT NULL DEFAULT 0,
  `precio_unitario` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id_detalle_compra`),
  UNIQUE KEY `uq_det_compra_prod_unidad` (`id_compra`,`id_producto`,`id_unidad`),
  KEY `idx_det_compra_producto` (`id_producto`),
  KEY `fk_det_compra_unidad` (`id_unidad`),
  CONSTRAINT `fk_det_compra_compra` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON DELETE CASCADE,
  CONSTRAINT `fk_det_compra_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE NO ACTION,
  CONSTRAINT `fk_det_compra_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidad_medida` (`id_unidad`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! Schema::hasTable('recepcion')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `recepcion` (
  `id_recepcion` int(11) NOT NULL AUTO_INCREMENT,
  `id_compra` int(11) DEFAULT NULL,
  `id_proveedor` int(11) NOT NULL,
  `fecha_recepcion` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id_recepcion`),
  KEY `idx_recepcion_compra` (`id_compra`),
  KEY `idx_recepcion_proveedor` (`id_proveedor`),
  KEY `fk_recepcion_usuario` (`id_usuario`),
  CONSTRAINT `fk_recepcion_compra` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON DELETE SET NULL,
  CONSTRAINT `fk_recepcion_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`) ON DELETE NO ACTION,
  CONSTRAINT `fk_recepcion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Recepcion de mercaderia. id_compra nullable permite ingreso directo sin compra previa (S03)'
SQL);
        }

        if (! Schema::hasTable('detalle_recepcion')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `detalle_recepcion` (
  `id_det_rec` int(11) NOT NULL AUTO_INCREMENT,
  `id_recepcion` int(11) NOT NULL,
  `id_detalle_compra` int(11) DEFAULT NULL,
  `id_producto` int(11) NOT NULL,
  `id_lote` int(11) DEFAULT NULL,
  `id_unidad` int(11) NOT NULL,
  `cantidad_recibida` int(11) NOT NULL,
  PRIMARY KEY (`id_det_rec`),
  KEY `idx_det_rec_recepcion` (`id_recepcion`),
  KEY `idx_det_rec_det_compra` (`id_detalle_compra`),
  KEY `idx_det_rec_producto` (`id_producto`),
  KEY `idx_det_rec_lote` (`id_lote`),
  KEY `fk_det_rec_unidad` (`id_unidad`),
  CONSTRAINT `fk_det_rec_det_compra` FOREIGN KEY (`id_detalle_compra`) REFERENCES `detalle_compra` (`id_detalle_compra`) ON DELETE SET NULL,
  CONSTRAINT `fk_det_rec_lote` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`) ON DELETE SET NULL,
  CONSTRAINT `fk_det_rec_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE NO ACTION,
  CONSTRAINT `fk_det_rec_recepcion` FOREIGN KEY (`id_recepcion`) REFERENCES `recepcion` (`id_recepcion`) ON DELETE CASCADE,
  CONSTRAINT `fk_det_rec_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidad_medida` (`id_unidad`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! Schema::hasTable('pago_proveedor')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `pago_proveedor` (
  `id_pago` int(11) NOT NULL AUTO_INCREMENT,
  `id_compra` int(11) NOT NULL,
  `metodo_pago` enum('efectivo','transferencia','cheque','echeq','tarjeta') NOT NULL,
  `importe` decimal(12,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id_pago`),
  KEY `idx_pago_compra` (`id_compra`),
  KEY `idx_pago_usuario` (`id_usuario`),
  CONSTRAINT `fk_pago_compra` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON DELETE CASCADE,
  CONSTRAINT `fk_pago_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! Schema::hasTable('sugerencia_compra')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `sugerencia_compra` (
  `id_sugerencia` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `cantidad_sugerida` int(11) NOT NULL,
  `cantidad_minima` int(11) DEFAULT NULL,
  `cantidad_maxima` int(11) DEFAULT NULL,
  `precio_unitario` decimal(12,2) DEFAULT NULL,
  `costo_total` decimal(12,2) DEFAULT NULL,
  `velocidad_venta_diaria` decimal(10,4) DEFAULT NULL,
  `plazo_entrega_dias` int(11) DEFAULT NULL,
  `fecha_reorden` date DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `id_orden_compra` int(11) DEFAULT NULL COMMENT 'Orden de compra generada a partir de esta sugerencia (PC07)',
  `motivo_generacion` varchar(50) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `motivo_rechazo` text DEFAULT NULL,
  `fecha_generacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_resolucion` datetime DEFAULT NULL,
  `id_usuario_resolucion` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_sugerencia`),
  KEY `sugerencia_compra_estado_index` (`estado`),
  KEY `sugerencia_compra_fecha_reorden_index` (`fecha_reorden`),
  KEY `idx_sugerencia_producto_motivo_estado` (`id_producto`,`motivo_generacion`,`estado`),
  KEY `sugerencia_compra_id_proveedor_foreign` (`id_proveedor`),
  KEY `sugerencia_compra_id_usuario_resolucion_foreign` (`id_usuario_resolucion`),
  KEY `sugerencia_compra_id_orden_compra_index` (`id_orden_compra`),
  CONSTRAINT `sugerencia_compra_id_orden_compra_foreign` FOREIGN KEY (`id_orden_compra`) REFERENCES `orden_compra` (`id_orden`) ON DELETE SET NULL,
  CONSTRAINT `sugerencia_compra_id_producto_foreign` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`),
  CONSTRAINT `sugerencia_compra_id_proveedor_foreign` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`),
  CONSTRAINT `sugerencia_compra_id_usuario_resolucion_foreign` FOREIGN KEY (`id_usuario_resolucion`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        if (! Schema::hasTable('log_auditoria')) {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `log_auditoria` (
  `id_auditoria` int(11) NOT NULL AUTO_INCREMENT,
  `tabla_afectada` varchar(80) NOT NULL,
  `id_registro` int(11) NOT NULL,
  `accion` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `valores_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valores_anteriores`)),
  `valores_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valores_nuevos`)),
  `id_usuario` int(11) DEFAULT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_auditoria_tabla_reg` (`tabla_afectada`,`id_registro`),
  KEY `idx_auditoria_usuario` (`id_usuario`),
  CONSTRAINT `fk_log_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auditoria centralizada transaccional'
SQL);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // No destructivo en base de datos compartida
    }
};
