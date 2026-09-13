-- ============================================================================
-- Migración de datos REALES: distribuidora_grupo3 -> distribuidorapyb
-- Proyecto ERP (Grupo 3): unificación en BD única (distribuidorapyb, 31 tablas).
--
-- Ejecutar con:  mysql -uroot < migrar_grupo3_a_pyb.sql
--
-- Mapeos aplicados (diferencias de esquema entre la BD vieja y la fusionada):
--   PRODUCTO    : precio_unitario -> precioMay/precioMin (mismo valor),
--                 nombre = descripcion (NOT NULL), stock embebido desde STOCK
--                 (OB3), dias_alerta_vencimiento = 30.
--   HISTORIAL_PRECIO: precio_nuevo -> tipo_precio 'mayorista' + precio (OB1).
--   PROVEEDOR   : cuit -> CUIT, correo -> email.
--   MOVIMIENTO_STOCK: id_venta=100 no existe en pyb (sin ventas) -> NULL.
--   USUARIO     : la vieja BD no tenía tabla USUARIO; se siembra admin id=1
--                 (la FK usuario.id_usuario -> cliente.id_cliente obliga a
--                  insertar primero el cliente).
-- ============================================================================

USE distribuidorapyb;

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- 1. Semilla operativa mínima (usuario admin id=1 usado por el backend).
-- ---------------------------------------------------------------------------
INSERT INTO zona (id_zona, nombre, descripcion)
VALUES (1, 'Zona Principal', 'Zona principal (migración)');

INSERT INTO cliente (id_cliente, nombre, razon_social, CUIL, email, telefono, direccion, localidad, id_zona, tipo_cliente, condicion_iva, estado, saldo)
VALUES (1, 'Admin General', 'Administración', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 'activo', 0.00);

INSERT INTO usuario (id_usuario, `user`, password, rol, estado)
VALUES (1, 'admin', 'admin123', 'admin', 'activo');

-- ---------------------------------------------------------------------------
-- 2. Categorías y marcas.
-- ---------------------------------------------------------------------------
INSERT INTO categoria (id_categoria, nombre, fecha_creacion, fecha_modificacion)
SELECT id_categoria, nombre, fecha_creacion, fecha_modificacion
FROM distribuidora_grupo3.categoria;

INSERT INTO marca (id_marca, nombre, fecha_creacion, fecha_modificacion)
SELECT id_marca, nombre, fecha_creacion, fecha_modificacion
FROM distribuidora_grupo3.marca;

-- ---------------------------------------------------------------------------
-- 3. Productos con stock embebido (OB3) y precios mayorista/minorista (OB1).
-- ---------------------------------------------------------------------------
INSERT INTO producto (id_producto, codigo, nombre, descripcion, precioMay, precioMin, imagen, stock, stock_minimo, dias_alerta_vencimiento, estado, fecha_alta, fecha_modificacion, fecha_desactivacion, id_categoria, id_marca, id_usuario_carga, id_usuario_modificacion)
SELECT p.id_producto,
       p.codigo,
       COALESCE(NULLIF(TRIM(p.descripcion), ''), p.codigo) AS nombre,
       p.descripcion,
       p.precio_unitario AS precioMay,
       p.precio_unitario AS precioMin,
       NULL,
       COALESCE(s.stock_disponible, 0) AS stock,
       COALESCE(s.stock_minimo, 0) AS stock_minimo,
       30,
       p.estado,
       p.fecha_alta,
       p.fecha_modificacion,
       p.fecha_desactivacion,
       p.id_categoria,
       p.id_marca,
       p.id_usuario_carga,
       p.id_usuario_modificacion
FROM distribuidora_grupo3.producto p
LEFT JOIN distribuidora_grupo3.stock s ON s.id_producto = p.id_producto;

-- ---------------------------------------------------------------------------
-- 4. Historial de precios (1 fila por valor, OB1).
-- ---------------------------------------------------------------------------
INSERT INTO historial_precio (id_historial, id_producto, tipo_precio, precio, porcentaje_aumento, regla_redondeo, origen, fecha_cambio, id_usuario)
SELECT id_historial, id_producto, 'mayorista', precio_nuevo, porcentaje_aumento, regla_redondeo, origen, fecha_cambio, id_usuario
FROM distribuidora_grupo3.historial_precio;

-- ---------------------------------------------------------------------------
-- 5. Unidades de medida, proveedores y asociaciones producto-proveedor.
-- ---------------------------------------------------------------------------
INSERT INTO unidad_medida (id_unidad, id_producto, nombre_unidad, equivalencia_base, descripcion)
SELECT id_unidad, id_producto, nombre_unidad, equivalencia_base, descripcion
FROM distribuidora_grupo3.unidad_medida;

INSERT INTO proveedor (id_proveedor, razon_social, CUIT, telefono, email, direccion, estado, plazo_entrega_dias, fecha_alta, fecha_modificacion, fecha_desactivacion, id_usuario_carga, id_usuario_modificacion)
SELECT id_proveedor, razon_social, cuit, telefono, correo, NULL, estado, plazo_entrega_dias, fecha_alta, fecha_modificacion, fecha_desactivacion, id_usuario_carga, id_usuario_modificacion
FROM distribuidora_grupo3.proveedor;

INSERT INTO producto_proveedor (id_producto_proveedor, id_producto, id_proveedor, es_proveedor_principal, precio_acordado, activo, fecha_asociacion, fecha_desasociacion, id_usuario)
SELECT id_producto_proveedor, id_producto, id_proveedor, es_proveedor_principal, precio_acordado, activo, fecha_asociacion, fecha_desasociacion, id_usuario
FROM distribuidora_grupo3.producto_proveedor;

-- ---------------------------------------------------------------------------
-- 6. Movimientos de stock (necesarios antes que lote por su FK).
--    id_venta: la tabla vieja referencia la venta 100 que no existe en pyb
--    (módulo de ventas del Grupo 4), por lo que se neutraliza a NULL.
-- ---------------------------------------------------------------------------
INSERT INTO movimiento_stock (id_movimiento, id_producto, id_unidad, tipo, cantidad, fecha, motivo, id_usuario, id_venta, id_entrega)
SELECT m.id_movimiento,
       m.id_producto,
       m.id_unidad,
       m.tipo,
       m.cantidad,
       m.fecha,
       m.motivo,
       m.id_usuario,
       CASE WHEN m.id_venta IS NOT NULL
              AND EXISTS (SELECT 1 FROM venta v WHERE v.id_venta = m.id_venta)
            THEN m.id_venta
            ELSE NULL END AS id_venta,
       NULL
FROM distribuidora_grupo3.movimiento_stock m;

-- ---------------------------------------------------------------------------
-- 7. Lotes (después de movimiento_stock).
-- ---------------------------------------------------------------------------
INSERT INTO lote (id_lote, id_producto, id_movimiento, id_unidad, nro_lote, cantidad, fecha_vencimiento, estado)
SELECT id_lote, id_producto, id_movimiento, id_unidad, nro_lote, cantidad, fecha_vencimiento, estado
FROM distribuidora_grupo3.lote;

-- ---------------------------------------------------------------------------
-- 8. Órdenes de compra y su detalle.
-- ---------------------------------------------------------------------------
INSERT INTO orden_compra (id_orden, numero_orden, id_proveedor, total_estimado, estado, fecha_creacion, fecha_modificacion, fecha_envio, fecha_cancelacion, id_usuario)
SELECT id_orden, numero_orden, id_proveedor, total_estimado, estado, fecha_creacion, fecha_modificacion, fecha_envio, fecha_cancelacion, id_usuario
FROM distribuidora_grupo3.orden_compra;

INSERT INTO detalle_orden (id_detalle_orden, id_orden, id_producto, id_unidad, cantidad_solicitada, cantidad_sugerida, origen, precio_estimado, subtotal)
SELECT id_detalle_orden, id_orden, id_producto, id_unidad, cantidad_solicitada, cantidad_sugerida, origen, precio_estimado, subtotal
FROM distribuidora_grupo3.detalle_orden;

-- ---------------------------------------------------------------------------
-- 9. Compras, detalle, recepciones (orden de FKs: compra -> detalle_compra
--    -> recepcion -> detalle_recepcion).
-- ---------------------------------------------------------------------------
INSERT INTO compra (id_compra, numero_comprobante, id_proveedor, id_orden, importe_total, saldo_pendiente, estado, fecha_compra, fecha_cancelacion, id_usuario)
SELECT id_compra, numero_comprobante, id_proveedor, id_orden, importe_total, saldo_pendiente, estado, fecha_compra, fecha_cancelacion, id_usuario
FROM distribuidora_grupo3.compra;

INSERT INTO detalle_compra (id_detalle_compra, id_compra, id_producto, id_unidad, cantidad, cantidad_recibida, precio_unitario, subtotal)
SELECT id_detalle, id_compra, id_producto, id_unidad, cantidad, cantidad_recibida, precio_unitario, subtotal
FROM distribuidora_grupo3.detalle_compra;

INSERT INTO recepcion (id_recepcion, id_compra, id_proveedor, fecha_recepcion, id_usuario)
SELECT id_recepcion, id_compra, id_proveedor, fecha_recepcion, id_usuario
FROM distribuidora_grupo3.recepcion;

INSERT INTO detalle_recepcion (id_det_rec, id_recepcion, id_producto, id_lote, id_unidad, cantidad_recibida)
SELECT id_det_rec, id_recepcion, id_producto, id_lote, id_unidad, cantidad_recibida
FROM distribuidora_grupo3.detalle_recepcion;

COMMIT;

-- ---------------------------------------------------------------------------
-- Verificación: conteos por tabla migrada.
-- ---------------------------------------------------------------------------
SELECT 'usuario' tabla, COUNT(*) filas FROM usuario
UNION ALL SELECT 'zona', COUNT(*) FROM zona
UNION ALL SELECT 'cliente', COUNT(*) FROM cliente
UNION ALL SELECT 'categoria', COUNT(*) FROM categoria
UNION ALL SELECT 'marca', COUNT(*) FROM marca
UNION ALL SELECT 'producto', COUNT(*) FROM producto
UNION ALL SELECT 'historial_precio', COUNT(*) FROM historial_precio
UNION ALL SELECT 'unidad_medida', COUNT(*) FROM unidad_medida
UNION ALL SELECT 'proveedor', COUNT(*) FROM proveedor
UNION ALL SELECT 'producto_proveedor', COUNT(*) FROM producto_proveedor
UNION ALL SELECT 'movimiento_stock', COUNT(*) FROM movimiento_stock
UNION ALL SELECT 'lote', COUNT(*) FROM lote
UNION ALL SELECT 'orden_compra', COUNT(*) FROM orden_compra
UNION ALL SELECT 'detalle_orden', COUNT(*) FROM detalle_orden
UNION ALL SELECT 'compra', COUNT(*) FROM compra
UNION ALL SELECT 'detalle_compra', COUNT(*) FROM detalle_compra
UNION ALL SELECT 'recepcion', COUNT(*) FROM recepcion
UNION ALL SELECT 'detalle_recepcion', COUNT(*) FROM detalle_recepcion;