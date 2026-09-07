-- ════════════════════════════════════════════════════════════════
-- GRUPO 3 — Productos, Stock, Proveedores, Compras, Pedidos de Compra
-- Script de creación de base de datos (MySQL 8+ / MariaDB 10.5+)
-- ════════════════════════════════════════════════════════════════
--
-- NOTA IMPORTANTE:
-- Este script incluye ÚNICAMENTE las tablas del dominio del Grupo 3.
-- Varias tablas tienen columnas que en el DER completo referencian
-- entidades de otros grupos (LOGIN de Grupo 1, VENTA y ENTREGA de
-- Grupo 2/4). Esas columnas se conservan (para no romper la
-- integridad futura del sistema integrado) pero SIN restricción
-- FOREIGN KEY, ya que esas tablas no existen en este script.
-- Están marcadas con el comentario "-- FK externa (Grupo X)".
-- Al integrar los cuatro grupos, esas FKs deberían agregarse con
-- ALTER TABLE una vez que existan las tablas referenciadas.

CREATE DATABASE IF NOT EXISTS distribuidora_grupo3
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE distribuidora_grupo3;

-- ── PRODUCTOS ──────────────────────────────────────────────────

CREATE TABLE CATEGORIA (
  id_categoria       INT AUTO_INCREMENT PRIMARY KEY,
  nombre             VARCHAR(100) NOT NULL,
  fecha_creacion     DATE,
  fecha_modificacion DATE
);

CREATE TABLE MARCA (
  id_marca           INT AUTO_INCREMENT PRIMARY KEY,
  nombre             VARCHAR(100) NOT NULL,
  fecha_creacion     DATE,
  fecha_modificacion DATE
);

CREATE TABLE PRODUCTO (
  id_producto             INT AUTO_INCREMENT PRIMARY KEY,
  codigo                  VARCHAR(50) NOT NULL UNIQUE,
  descripcion             VARCHAR(255),
  precio_unitario         DECIMAL(12,2) NOT NULL,
  estado                  VARCHAR(20) CHECK (estado IN ('activo','inactivo')),
  fecha_alta              DATE,
  fecha_modificacion      DATE,
  fecha_desactivacion     DATE,
  id_categoria            INT,
  id_marca                INT,
  id_usuario_carga        INT, -- FK externa (Grupo 1: LOGIN)
  id_usuario_modificacion INT, -- FK externa (Grupo 1: LOGIN)
  CONSTRAINT fk_producto_categoria FOREIGN KEY (id_categoria) REFERENCES CATEGORIA(id_categoria),
  CONSTRAINT fk_producto_marca     FOREIGN KEY (id_marca)     REFERENCES MARCA(id_marca)
);

CREATE TABLE HISTORIAL_PRECIO (
  id_historial       INT AUTO_INCREMENT PRIMARY KEY,
  id_producto        INT NOT NULL,
  precio_anterior    DECIMAL(12,2),
  precio_nuevo       DECIMAL(12,2),
  porcentaje_aumento DECIMAL(6,2),
  regla_redondeo     VARCHAR(50),
  origen             VARCHAR(20) CHECK (origen IN ('manual','aumento_masivo')),
  fecha_cambio       DATE,
  id_usuario         INT, -- FK externa (Grupo 1: LOGIN)
  CONSTRAINT fk_histprecio_producto FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto)
);

-- ── STOCK ──────────────────────────────────────────────────────

CREATE TABLE UNIDAD_MEDIDA (
  id_unidad         INT AUTO_INCREMENT PRIMARY KEY,
  id_producto       INT NOT NULL,
  nombre_unidad     VARCHAR(50),
  equivalencia_base DECIMAL(12,4),
  descripcion       VARCHAR(255),
  CONSTRAINT fk_unidad_producto FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto)
);

CREATE TABLE STOCK (
  id_stock                INT AUTO_INCREMENT PRIMARY KEY,
  id_producto             INT NOT NULL UNIQUE, -- relación 1 a 1 con PRODUCTO
  stock_disponible        INT NOT NULL,
  stock_minimo            INT,
  estado_alerta           VARCHAR(20) CHECK (estado_alerta IN ('normal','bajo','critico')),
  dias_alerta_vencimiento INT,
  CONSTRAINT fk_stock_producto FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto)
);

CREATE TABLE MOVIMIENTO_STOCK (
  id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
  id_producto   INT NOT NULL,
  id_unidad     INT,
  tipo          VARCHAR(20) CHECK (tipo IN ('ingreso','venta','devolucion','ajuste')),
  cantidad      INT,
  fecha         DATE,
  motivo        VARCHAR(255),
  id_usuario    INT, -- FK externa (Grupo 1: LOGIN)
  id_venta      INT, -- FK externa (Grupo 4: VENTA)
  id_entrega    INT, -- FK externa (Grupo 2: ENTREGA)
  CONSTRAINT fk_movstock_producto FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto),
  CONSTRAINT fk_movstock_unidad   FOREIGN KEY (id_unidad)   REFERENCES UNIDAD_MEDIDA(id_unidad)
);

CREATE TABLE LOTE (
  id_lote           INT AUTO_INCREMENT PRIMARY KEY,
  id_producto       INT NOT NULL,
  id_movimiento     INT,
  id_unidad         INT,
  nro_lote          VARCHAR(50),
  fecha_vencimiento DATE,
  cantidad          INT,
  estado            VARCHAR(20) CHECK (estado IN ('vigente','vencido','consumido')),
  CONSTRAINT fk_lote_producto   FOREIGN KEY (id_producto)   REFERENCES PRODUCTO(id_producto),
  CONSTRAINT fk_lote_movimiento FOREIGN KEY (id_movimiento) REFERENCES MOVIMIENTO_STOCK(id_movimiento),
  CONSTRAINT fk_lote_unidad     FOREIGN KEY (id_unidad)     REFERENCES UNIDAD_MEDIDA(id_unidad)
);

CREATE TABLE UBICACION (
  id_ubicacion   INT AUTO_INCREMENT PRIMARY KEY,
  descripcion    VARCHAR(255), -- estante / pasillo / gondola
  estado         VARCHAR(20),
  fecha_creacion DATE
);

CREATE TABLE PRODUCTO_UBICACION (
  id_producto      INT NOT NULL,
  id_ubicacion     INT NOT NULL,
  fecha_asignacion DATE,
  id_usuario       INT, -- FK externa (Grupo 1: LOGIN)
  PRIMARY KEY (id_producto, id_ubicacion),
  CONSTRAINT fk_produbic_producto  FOREIGN KEY (id_producto)  REFERENCES PRODUCTO(id_producto),
  CONSTRAINT fk_produbic_ubicacion FOREIGN KEY (id_ubicacion) REFERENCES UBICACION(id_ubicacion)
);

CREATE TABLE SESION_CONTEO (
  id_sesion         INT AUTO_INCREMENT PRIMARY KEY,
  estado            VARCHAR(20) CHECK (estado IN ('en_proceso','finalizado')),
  fecha_conteo      DATE,
  observaciones     VARCHAR(255),
  total_productos   INT,
  total_diferencias INT,
  id_usuario        INT -- FK externa (Grupo 1: LOGIN)
);

CREATE TABLE DETALLE_CONTEO (
  id_detalle_conteo INT AUTO_INCREMENT PRIMARY KEY,
  id_sesion         INT NOT NULL,
  id_producto       INT NOT NULL,
  cantidad_fisica   INT,
  stock_sistema     INT,
  diferencia        INT,
  observaciones     VARCHAR(255),
  CONSTRAINT fk_detconteo_sesion   FOREIGN KEY (id_sesion)   REFERENCES SESION_CONTEO(id_sesion),
  CONSTRAINT fk_detconteo_producto FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto)
);

-- ── PROVEEDORES ────────────────────────────────────────────────

CREATE TABLE PROVEEDOR (
  id_proveedor            INT AUTO_INCREMENT PRIMARY KEY,
  razon_social            VARCHAR(150) NOT NULL,
  cuit                    VARCHAR(20) UNIQUE,
  telefono                VARCHAR(30),
  correo                  VARCHAR(100),
  estado                  VARCHAR(20) CHECK (estado IN ('activo','inactivo')),
  fecha_alta              DATE,
  fecha_modificacion      DATE,
  fecha_desactivacion     DATE,
  plazo_entrega_dias      INT,
  id_usuario_carga        INT, -- FK externa (Grupo 1: LOGIN)
  id_usuario_modificacion INT  -- FK externa (Grupo 1: LOGIN)
);

CREATE TABLE HISTORIAL_PLAZO_ENTREGA (
  id_historial_plazo INT AUTO_INCREMENT PRIMARY KEY,
  id_proveedor       INT NOT NULL,
  plazo_anterior      INT,
  plazo_nuevo         INT,
  fecha_cambio        DATE,
  id_usuario          INT, -- FK externa (Grupo 1: LOGIN)
  CONSTRAINT fk_histplazo_proveedor FOREIGN KEY (id_proveedor) REFERENCES PROVEEDOR(id_proveedor)
);

CREATE TABLE PRODUCTO_PROVEEDOR (
  id_producto_proveedor  INT AUTO_INCREMENT PRIMARY KEY,
  id_producto            INT NOT NULL,
  id_proveedor           INT NOT NULL,
  es_proveedor_principal BOOLEAN,
  precio_acordado        DECIMAL(12,2),
  activo                 BOOLEAN,
  fecha_asociacion       DATE,
  fecha_desasociacion    DATE,
  id_usuario             INT, -- FK externa (Grupo 1: LOGIN)
  UNIQUE (id_producto, id_proveedor),
  CONSTRAINT fk_prodprov_producto  FOREIGN KEY (id_producto)  REFERENCES PRODUCTO(id_producto),
  CONSTRAINT fk_prodprov_proveedor FOREIGN KEY (id_proveedor) REFERENCES PROVEEDOR(id_proveedor)
);

-- ── PEDIDOS DE COMPRA ──────────────────────────────────────────

CREATE TABLE ORDEN_COMPRA (
  id_orden           INT AUTO_INCREMENT PRIMARY KEY,
  numero_orden       VARCHAR(50),
  id_proveedor       INT NOT NULL,
  total_estimado     DECIMAL(12,2),
  estado             VARCHAR(30) CHECK (estado IN ('pendiente','enviada','recibida_parcialmente','completada','cancelada')),
  fecha_creacion     DATE,
  fecha_modificacion DATE,
  fecha_envio        DATE,
  fecha_cancelacion  DATE,
  id_usuario         INT, -- FK externa (Grupo 1: LOGIN)
  CONSTRAINT fk_orden_proveedor FOREIGN KEY (id_proveedor) REFERENCES PROVEEDOR(id_proveedor)
);

CREATE TABLE DETALLE_ORDEN (
  id_detalle_orden    INT AUTO_INCREMENT PRIMARY KEY,
  id_orden            INT NOT NULL,
  id_producto         INT NOT NULL,
  id_unidad           INT,
  cantidad_solicitada INT,
  cantidad_sugerida   INT,
  origen              VARCHAR(20) CHECK (origen IN ('manual','sugerencia')),
  precio_estimado     DECIMAL(12,2),
  subtotal            DECIMAL(12,2),
  CONSTRAINT fk_detorden_orden    FOREIGN KEY (id_orden)    REFERENCES ORDEN_COMPRA(id_orden),
  CONSTRAINT fk_detorden_producto FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto),
  CONSTRAINT fk_detorden_unidad   FOREIGN KEY (id_unidad)   REFERENCES UNIDAD_MEDIDA(id_unidad)
);

-- ── COMPRAS ────────────────────────────────────────────────────

CREATE TABLE COMPRA (
  id_compra          INT AUTO_INCREMENT PRIMARY KEY,
  numero_comprobante VARCHAR(50),
  id_proveedor       INT NOT NULL,
  id_orden           INT,
  importe_total      DECIMAL(12,2),
  saldo_pendiente    DECIMAL(12,2),
  estado             VARCHAR(30) CHECK (estado IN ('pendiente','parcialmente_recibida','completada','cancelada')),
  fecha_compra       DATE,
  fecha_cancelacion  DATE,
  id_usuario         INT, -- FK externa (Grupo 1: LOGIN)
  CONSTRAINT fk_compra_proveedor FOREIGN KEY (id_proveedor) REFERENCES PROVEEDOR(id_proveedor),
  CONSTRAINT fk_compra_orden     FOREIGN KEY (id_orden)     REFERENCES ORDEN_COMPRA(id_orden)
);

CREATE TABLE DETALLE_COMPRA (
  id_detalle        INT AUTO_INCREMENT PRIMARY KEY,
  id_compra         INT NOT NULL,
  id_producto       INT NOT NULL,
  id_unidad         INT,
  cantidad          INT,
  cantidad_recibida INT,
  precio_unitario   DECIMAL(12,2),
  subtotal          DECIMAL(12,2),
  CONSTRAINT fk_detcompra_compra   FOREIGN KEY (id_compra)   REFERENCES COMPRA(id_compra),
  CONSTRAINT fk_detcompra_producto FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto),
  CONSTRAINT fk_detcompra_unidad   FOREIGN KEY (id_unidad)   REFERENCES UNIDAD_MEDIDA(id_unidad)
);

CREATE TABLE RECEPCION (
  id_recepcion    INT AUTO_INCREMENT PRIMARY KEY,
  id_compra       INT NOT NULL,
  id_proveedor    INT NOT NULL,
  fecha_recepcion DATE,
  id_usuario      INT, -- FK externa (Grupo 1: LOGIN)
  CONSTRAINT fk_recepcion_compra    FOREIGN KEY (id_compra)    REFERENCES COMPRA(id_compra),
  CONSTRAINT fk_recepcion_proveedor FOREIGN KEY (id_proveedor) REFERENCES PROVEEDOR(id_proveedor)
);

CREATE TABLE DETALLE_RECEPCION (
  id_det_rec        INT AUTO_INCREMENT PRIMARY KEY,
  id_recepcion      INT NOT NULL,
  id_producto       INT NOT NULL,
  id_lote           INT,
  id_unidad         INT,
  cantidad_recibida INT,
  CONSTRAINT fk_detrecep_recepcion FOREIGN KEY (id_recepcion) REFERENCES RECEPCION(id_recepcion),
  CONSTRAINT fk_detrecep_producto  FOREIGN KEY (id_producto)  REFERENCES PRODUCTO(id_producto),
  CONSTRAINT fk_detrecep_lote      FOREIGN KEY (id_lote)      REFERENCES LOTE(id_lote),
  CONSTRAINT fk_detrecep_unidad    FOREIGN KEY (id_unidad)    REFERENCES UNIDAD_MEDIDA(id_unidad)
);

CREATE TABLE PAGO_PROVEEDOR (
  id_pago           INT AUTO_INCREMENT PRIMARY KEY,
  id_compra         INT NOT NULL,
  metodo_pago       VARCHAR(20) CHECK (metodo_pago IN ('efectivo','transferencia','cheque','tarjeta')),
  importe           DECIMAL(12,2),
  estado_pago       VARCHAR(20) CHECK (estado_pago IN ('pendiente','pagado','parcial','vencido')),
  fecha_pago        DATE,
  fecha_vencimiento DATE,
  id_usuario        INT, -- FK externa (Grupo 1: LOGIN)
  CONSTRAINT fk_pagoprov_compra FOREIGN KEY (id_compra) REFERENCES COMPRA(id_compra)
);

CREATE TABLE HISTORIAL_PRECIO_PROVEEDOR (
  id_historial_prov     INT AUTO_INCREMENT PRIMARY KEY,
  id_producto_proveedor INT NOT NULL,
  id_proveedor          INT NOT NULL,
  id_producto            INT NOT NULL,
  id_compra              INT,
  precio_anterior         DECIMAL(12,2),
  precio_nuevo            DECIMAL(12,2),
  fecha_actualizacion     DATE,
  id_usuario              INT, -- FK externa (Grupo 1: LOGIN)
  CONSTRAINT fk_histprecioprov_prodprov FOREIGN KEY (id_producto_proveedor) REFERENCES PRODUCTO_PROVEEDOR(id_producto_proveedor),
  CONSTRAINT fk_histprecioprov_proveedor FOREIGN KEY (id_proveedor) REFERENCES PROVEEDOR(id_proveedor),
  CONSTRAINT fk_histprecioprov_producto  FOREIGN KEY (id_producto)  REFERENCES PRODUCTO(id_producto),
  CONSTRAINT fk_histprecioprov_compra    FOREIGN KEY (id_compra)    REFERENCES COMPRA(id_compra)
);
