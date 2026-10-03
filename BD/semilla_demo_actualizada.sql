-- ================================================================
-- SEMILLA DE DEMO - distribuidorapyb (esquema ACTUALIZADO)
-- Datos persistentes para poder operar/probar la interfaz.
-- Re-ejecutable: borra y recrea solo lo suyo (datos de prueba MVP).
-- ================================================================
SET NAMES utf8mb4;
USE distribuidorapyb;

-- Limpieza en orden de dependencia (hijas -> padres). Sin esto el re-ejecucion
-- choca con las FK: detalle_compra/detalle_orden_compra/detalle_recepcion referencian
-- producto, unidad_medida, compra y orden_compra.
DELETE FROM pago_proveedor;
DELETE FROM detalle_recepcion;
DELETE FROM recepcion;
DELETE FROM detalle_compra;
DELETE FROM compra;
DELETE FROM detalle_orden;
DELETE FROM orden_compra;
DELETE FROM detalle_venta;
DELETE FROM detalle_conteo;
DELETE FROM sesion_conteo;
DELETE FROM producto_ubicacion;
DELETE FROM ubicacion;
DELETE FROM producto_proveedor;
DELETE FROM historial_precio_proveedor;
DELETE FROM proveedor;
DELETE FROM movimiento_stock;
DELETE FROM historial_precio;
DELETE FROM lote;
DELETE FROM producto;
DELETE FROM unidad_medida;
DELETE FROM marca;
DELETE FROM categoria;
DELETE FROM zona;

-- USUARIO (esquema nuevo: usuario, password_hash, nombre, rol)
DELETE FROM cliente;
DELETE FROM usuario WHERE usuario='admin';
INSERT INTO usuario (usuario,password_hash,nombre,rol,estado) VALUES
 ('admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Administrador','administrativo','activo');

SET @idusu = (SELECT id_usuario FROM usuario WHERE usuario='admin');

INSERT INTO zona (nombre,descripcion) VALUES ('Zona Centro','Centro y surrounds');
SET @idzona = LAST_INSERT_ID();

INSERT INTO cliente (nombre,apellido_razon_social,dni_cuit,email,telefono,direccion,localidad,id_zona,tipo_cliente,condicion_iva,estado,saldo,creado_por) VALUES
 ('Distribuidora Norte','Distribuidora Norte S.A.','30-71234567-4','compras@norte.com','011-4555-1234','Av. Corrientes 1200','CABA',@idzona,'mayorista','responsable_inscripto','activo',0.00,@idusu);

INSERT INTO categoria (nombre,fecha_creacion) VALUES
 ('Bebidas',CURDATE()),('Lácteos',CURDATE()),('Almacén',CURDATE()),('Limpieza',CURDATE());
SET @cat_beb = (SELECT id_categoria FROM categoria WHERE nombre='Bebidas');
SET @cat_lac = (SELECT id_categoria FROM categoria WHERE nombre='Lácteos');
SET @cat_alm = (SELECT id_categoria FROM categoria WHERE nombre='Almacén');
SET @cat_lim = (SELECT id_categoria FROM categoria WHERE nombre='Limpieza');

INSERT INTO marca (nombre,fecha_creacion) VALUES
 ('Imperio',CURDATE()),('La Serenisima',CURDATE()),('Discentro',CURDATE()),('Dogo',CURDATE());
SET @m1 = (SELECT id_marca FROM marca WHERE nombre='Imperio');
SET @m2 = (SELECT id_marca FROM marca WHERE nombre='La Serenisima');
SET @m3 = (SELECT id_marca FROM marca WHERE nombre='Discentro');
SET @m4 = (SELECT id_marca FROM marca WHERE nombre='Dogo');

INSERT INTO proveedor (razon_social,CUIT,telefono,email,direccion,estado,plazo_entrega_dias,fecha_alta,id_usuario_carga) VALUES
 ('Bebidas del Sur SRL','30-99887766-5','011-4444-0001','ventas@bebidasdelsur.com','Ruta 8 Km 32','activo',7,CURDATE(),@idusu),
 ('Lácteos Patagonia SA','30-88776655-4','0294-422-1111','pedidos@lacteospatagonia.com','Ruta 40 Km 12','activo',5,CURDATE(),@idusu),
 ('Limpieza Total SA','30-77665544-3','011-4777-2222','info@limpiezatotal.com','Av. Mitre 450','activo',10,CURDATE(),@idusu);
SET @p1 = (SELECT id_proveedor FROM proveedor WHERE razon_social LIKE 'Bebidas del Sur%');
SET @p2 = (SELECT id_proveedor FROM proveedor WHERE razon_social LIKE 'Lácteos Patagonia%');
SET @p3 = (SELECT id_proveedor FROM proveedor WHERE razon_social LIKE 'Limpieza Total%');

-- PRODUCTO (esquema nuevo: precio_unitario unico, NOT NULL en cat/marca/usuario)
INSERT INTO producto (codigo,nombre,descripcion,precio_unitario,stock,stock_minimo,dias_alerta_vencimiento,estado,fecha_alta,id_categoria,id_marca,id_usuario_carga) VALUES
 ('B-3301','Gaseosa Cola 2.25L','Botella PET 2250ml',1450.00,120,30,45,'activo',CURDATE(),@cat_beb,@m1,@idusu),
 ('B-3302','Gaseosa Naranja 2.25L','Botella PET 2250ml',1450.00,85,30,45,'activo',CURDATE(),@cat_beb,@m1,@idusu),
 ('L-9A21','Leche Entera 1L','Tetrapak 1000ml',950.00,8,40,10,'activo',CURDATE(),@cat_lac,@m2,@idusu),
 ('L-9A22','Yogur Natural 1L','Tetrapak 1000ml',1250.00,60,25,14,'activo',CURDATE(),@cat_lac,@m2,@idusu),
 ('A-1001','Arroz Largo Fino 1kg','Bolsa 1000g',1890.00,45,20,120,'activo',CURDATE(),@cat_alm,@m3,@idusu),
 ('A-1002','Fideos Espagueti 500g','Bolsa 500g',890.00,200,50,180,'activo',CURDATE(),@cat_alm,@m3,@idusu),
 ('C-2200','Detergente Concentrado 3L','Bidón 3000ml',3200.00,3,15,300,'activo',CURDATE(),@cat_lim,@m4,@idusu),
 ('C-2201','Lavandina 1L','Botella 1000ml',750.00,95,30,300,'activo',CURDATE(),@cat_lim,@m4,@idusu);

-- UNIDAD_MEDIDA (esquema nuevo: es_base + equivalencia_base entera)
INSERT INTO unidad_medida (id_producto,nombre_unidad,equivalencia_base,es_base,descripcion)
SELECT id_producto,'Unidad suelta',1,1,'unidad indivisible' FROM producto;
INSERT INTO unidad_medida (id_producto,nombre_unidad,equivalencia_base,es_base,descripcion)
SELECT id_producto,'Caja x12',12,0,'bulto de 12 unidades' FROM producto
WHERE codigo IN ('B-3301','B-3302','A-1001','A-1002');

-- HISTORIAL_PRECIO (tipo_precio ENUM, origen ENUM)
INSERT INTO historial_precio (id_producto,tipo_precio,precio,porcentaje_aumento,regla_redondeo,origen,id_usuario)
SELECT id_producto,'general',precio_unitario,NULL,'sin_redondeo','manual',@idusu FROM producto;

-- LOTE (esquema nuevo: cantidad_inicial/cantidad_actual, SIN estado/id_unidad/id_movimiento)
INSERT INTO lote (id_producto,nro_lote,cantidad_inicial,cantidad_actual,fecha_vencimiento) VALUES
 ((SELECT id_producto FROM producto WHERE codigo='L-9A21'),'LOTE-2026-001',8,8,DATE_ADD(CURDATE(),INTERVAL 7 DAY)),
 ((SELECT id_producto FROM producto WHERE codigo='L-9A22'),'LOTE-2026-002',40,40,DATE_ADD(CURDATE(),INTERVAL 12 DAY)),
 ((SELECT id_producto FROM producto WHERE codigo='B-3301'),'LOTE-2026-003',120,120,DATE_ADD(CURDATE(),INTERVAL 25 DAY)),
 ((SELECT id_producto FROM producto WHERE codigo='A-1001'),'LOTE-2025-118',45,0,DATE_ADD(CURDATE(),INTERVAL -3 DAY));

-- MOVIMIENTO_STOCK (cantidad con signo, cantidad_base, id_lote trazable)
INSERT INTO movimiento_stock (id_producto,id_unidad,tipo,cantidad,cantidad_base,fecha,motivo,id_usuario,id_lote) VALUES
 ((SELECT id_producto FROM producto WHERE codigo='L-9A21'),(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Unidad suelta' LIMIT 1),'ingreso',8,8,NOW(),'Carga inicial',@idusu,(SELECT id_lote FROM lote WHERE nro_lote='LOTE-2026-001')),
 ((SELECT id_producto FROM producto WHERE codigo='L-9A22'),(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Unidad suelta' LIMIT 1),'ingreso',40,40,NOW(),'Carga inicial',@idusu,(SELECT id_lote FROM lote WHERE nro_lote='LOTE-2026-002')),
 ((SELECT id_producto FROM producto WHERE codigo='B-3301'),(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Unidad suelta' LIMIT 1),'ingreso',120,120,NOW(),'Carga inicial',@idusu,(SELECT id_lote FROM lote WHERE nro_lote='LOTE-2026-003')),
 ((SELECT id_producto FROM producto WHERE codigo='A-1001'),(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Unidad suelta' LIMIT 1),'ingreso',45,45,NOW(),'Carga inicial',@idusu,(SELECT id_lote FROM lote WHERE nro_lote='LOTE-2025-118')),
 ((SELECT id_producto FROM producto WHERE codigo='A-1001'),(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Unidad suelta' LIMIT 1),'ajuste',-2,-2,NOW(),'Merma por humedad',@idusu,NULL),
 ((SELECT id_producto FROM producto WHERE codigo='C-2200'),(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Unidad suelta' LIMIT 1),'ingreso',3,3,NOW(),'Carga inicial',@idusu,NULL);

-- PRODUCTO_PROVEEDOR + HISTORIAL_PRECIO_PROVEEDOR
INSERT INTO producto_proveedor (id_producto,id_proveedor,es_proveedor_principal,precio_acordado,id_unidad,activo,fecha_asociacion,id_usuario) VALUES
 ((SELECT id_producto FROM producto WHERE codigo='B-3301'),@p1,1,1180.00,(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Caja x12' LIMIT 1),1,CURDATE(),@idusu),
 ((SELECT id_producto FROM producto WHERE codigo='B-3302'),@p1,1,1180.00,(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Caja x12' LIMIT 1),1,CURDATE(),@idusu),
 ((SELECT id_producto FROM producto WHERE codigo='L-9A21'),@p2,1,760.00,(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Unidad suelta' LIMIT 1),1,CURDATE(),@idusu),
 ((SELECT id_producto FROM producto WHERE codigo='L-9A22'),@p2,1,1020.00,(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Unidad suelta' LIMIT 1),1,CURDATE(),@idusu),
 ((SELECT id_producto FROM producto WHERE codigo='C-2200'),@p3,1,2650.00,(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Unidad suelta' LIMIT 1),1,CURDATE(),@idusu);

INSERT INTO historial_precio_proveedor (id_producto_proveedor,id_proveedor,id_producto,id_unidad,precio_acordado,id_usuario)
SELECT pp.id_producto_proveedor,pp.id_proveedor,pp.id_producto,pp.id_unidad,pp.precio_acordado,@idusu
FROM producto_proveedor pp;

-- ORDEN_COMPRA / DETALLE_ORDEN (fecha_entrega_estimada nueva, estado ENUM)
INSERT INTO orden_compra (numero_orden,id_proveedor,total_estimado,estado,fecha_creacion,fecha_entrega_estimada,id_usuario) VALUES
 ('OC-2026-000001',@p1,35400.00,'enviada',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 5 DAY),@idusu),
 ('OC-2026-000002',@p2,7600.00,'pendiente',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 3 DAY),@idusu),
 ('OC-2026-000003',@p3,2650.00,'pendiente',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 8 DAY),@idusu);
SET @o1 = (SELECT id_orden FROM orden_compra WHERE numero_orden='OC-2026-000001');
SET @o2 = (SELECT id_orden FROM orden_compra WHERE numero_orden='OC-2026-000002');
INSERT INTO detalle_orden (id_orden,id_producto,id_unidad,cantidad_solicitada,cantidad_sugerida,origen,precio_estimado,subtotal) VALUES
 (@o1,(SELECT id_producto FROM producto WHERE codigo='B-3301'),(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Caja x12' LIMIT 1),30,30,'manual',1180.00,35400.00),
 (@o2,(SELECT id_producto FROM producto WHERE codigo='L-9A21'),(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Unidad suelta' LIMIT 1),10,10,'sugerencia',760.00,7600.00);

-- COMPRA / DETALLE_COMPRA (numero_compra NOT NULL, estado ENUM, fecha_vencimiento nueva)
INSERT INTO compra (numero_compra,numero_comprobante,id_proveedor,id_orden,importe_total,saldo_pendiente,estado,fecha_compra,fecha_vencimiento,id_usuario) VALUES
 ('NCC-2026-000001','0001-00000045',@p1,@o1,35400.00,35400.00,'pendiente',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 30 DAY),@idusu),
 ('NCC-2026-000002','0001-00000046',@p2,@o2,7600.00,3800.00,'parcialmente_recibida',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 15 DAY),@idusu);
SET @c1 = (SELECT id_compra FROM compra WHERE numero_compra='NCC-2026-000001');
SET @c2 = (SELECT id_compra FROM compra WHERE numero_compra='NCC-2026-000002');
INSERT INTO detalle_compra (id_compra,id_producto,id_unidad,cantidad,cantidad_recibida,precio_unitario,subtotal) VALUES
 (@c1,(SELECT id_producto FROM producto WHERE codigo='B-3301'),(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Caja x12' LIMIT 1),30,0,1180.00,35400.00),
 (@c2,(SELECT id_producto FROM producto WHERE codigo='L-9A21'),(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Unidad suelta' LIMIT 1),10,5,760.00,7600.00);

-- RECEPCION / DETALLE_RECEPCION (id_compra NULLABLE = ingreso directo S03; id_detalle_compra nuevo)
INSERT INTO recepcion (id_compra,id_proveedor,fecha_recepcion,id_usuario) VALUES
 (@c2,@p2,NOW(),@idusu),
 (NULL,@p1,NOW(),@idusu);
SET @r1 = (SELECT id_recepcion FROM recepcion WHERE id_compra=@c2 LIMIT 1);
SET @r2 = (SELECT id_recepcion FROM recepcion WHERE id_compra IS NULL LIMIT 1);
INSERT INTO detalle_recepcion (id_recepcion,id_detalle_compra,id_producto,id_lote,id_unidad,cantidad_recibida) VALUES
 (@r1,(SELECT id_detalle_compra FROM detalle_compra WHERE id_compra=@c2),(SELECT id_producto FROM producto WHERE codigo='L-9A21'),NULL,(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Unidad suelta' LIMIT 1),5),
 (@r2,NULL,(SELECT id_producto FROM producto WHERE codigo='A-1002'),NULL,(SELECT id_unidad FROM unidad_medida WHERE nombre_unidad='Unidad suelta' LIMIT 1),25);

-- PAGO_PROVEEDOR (sin estado_pago / fecha_vencimiento)
INSERT INTO pago_proveedor (id_compra,metodo_pago,importe,fecha_pago,id_usuario) VALUES
 (@c2,'transferencia',3800.00,CURDATE(),@idusu);

-- UBICACION / PRODUCTO_UBICACION / SESION_CONTEO / DETALLE_CONTEO
INSERT INTO ubicacion (descripcion,estado,fecha_creacion) VALUES
 ('Estante A - Bebidas','activo',CURDATE()),('Estante B - Lacteos','activo',CURDATE()),('Deposito Central','activo',CURDATE());
INSERT INTO producto_ubicacion (id_producto,id_ubicacion,fecha_asignacion,id_usuario) VALUES
 ((SELECT id_producto FROM producto WHERE codigo='B-3301'),(SELECT id_ubicacion FROM ubicacion WHERE descripcion LIKE 'Estante A%'),CURDATE(),@idusu),
 ((SELECT id_producto FROM producto WHERE codigo='L-9A21'),(SELECT id_ubicacion FROM ubicacion WHERE descripcion LIKE 'Estante B%'),CURDATE(),@idusu);
INSERT INTO sesion_conteo (estado,fecha_conteo,observaciones,total_productos,total_diferencias,id_usuario) VALUES
 ('en_proceso',CURDATE(),'Caneo mensual',0,0,@idusu);
SET @ses = LAST_INSERT_ID();
INSERT INTO detalle_conteo (id_sesion,id_producto,cantidad_fisica,stock_sistema,diferencia,observaciones) VALUES
 (@ses,(SELECT id_producto FROM producto WHERE codigo='B-3301'),118,120,-2,'Faltantes en góndola');

SELECT '== SEMILLA APLICADA ==' AS ok;
SELECT (SELECT COUNT(*) FROM producto) AS productos,
       (SELECT COUNT(*) FROM unidad_medida) AS unidades,
       (SELECT COUNT(*) FROM lote) AS lotes,
       (SELECT COUNT(*) FROM movimiento_stock) AS movimientos,
       (SELECT COUNT(*) FROM proveedor) AS proveedores,
       (SELECT COUNT(*) FROM orden_compra) AS ordenes,
       (SELECT COUNT(*) FROM compra) AS compras,
       (SELECT COUNT(*) FROM recepcion) AS recepciones;
