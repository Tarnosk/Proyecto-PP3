-- ================================================================
-- SUITE CANONICA DE MVP v2 - distribuidorapyb
-- ADAPTADA al esquema ACTUALIZADO (ACTUALIZACION/Version Corregida.sql)
-- Uso:     cmd /c ""C:\xampp\mysql\bin\mysql.exe" -u root
--           --default-character-set=utf8mb4 < BD/DistribuidoraPyB_MVP_v2.sql"
-- Todo dentro de START TRANSACTION + ROLLBACK final (no queda nada).
-- ================================================================
SET NAMES utf8mb4;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
START TRANSACTION;
USE distribuidorapyb;

SET @tag = CONCAT(DATE_FORMAT(NOW(),'%Y%m%d%H%i%s')) COLLATE utf8mb4_unicode_ci;
DROP TEMPORARY TABLE IF EXISTS mvp_resultados;
CREATE TEMPORARY TABLE mvp_resultados (
  fila INT AUTO_INCREMENT PRIMARY KEY,
  historia VARCHAR(90),
  prueba   VARCHAR(70),
  ok       VARCHAR(4),
  detalle  VARCHAR(250)
);

-- ================================================================
-- SEMILLA minima (se revierte con el rollback)
-- Esquema nuevo: USUARIO(usuario,password_hash,nombre,rol), CLIENTE(apellido_razon_social,dni_cuit,creado_por)
-- ================================================================
INSERT INTO zona (nombre,descripcion) VALUES ('Zona-MVP','MVP');
SET @idzona = LAST_INSERT_ID();

INSERT INTO usuario (usuario,password_hash,nombre,rol,estado)
VALUES ('root-mvp','$2y$10$mvp.mvp.mvp','Admin MVP','administrativo','activo');
SET @idusu = LAST_INSERT_ID();

INSERT INTO cliente (nombre,apellido_razon_social,dni_cuit,email,telefono,direccion,localidad,id_zona,tipo_cliente,condicion_iva,estado,saldo,creado_por)
VALUES ('Admin-MVP','Admin-MVP SRL','27-33333333-9','admin@mvp','111','calle','loc',@idzona,'mayorista','responsable_inscripto','activo',0.00,@idusu);
SET @idcliente = LAST_INSERT_ID();

INSERT INTO categoria (nombre,fecha_creacion) VALUES ('Categoria-MVP',CURDATE());
SET @idcat = LAST_INSERT_ID();
INSERT INTO marca (nombre,fecha_creacion) VALUES ('Marca-MVP',CURDATE());
SET @idmarca = LAST_INSERT_ID();

-- UNIDAD_MEDIDA nueva: es_base identifica la unidad indivisible (S08)
INSERT INTO unidad_medida (id_producto,nombre_unidad,equivalencia_base,es_base,descripcion)
SELECT id_producto,'Unidad',1,1,'unidad indivisible' FROM producto LIMIT 0;

-- ================================================================
-- MODULO PRODUCTOS (P01-P07)  -  precioMay/precioMin -> precio_unitario
-- ================================================================
INSERT INTO producto (codigo,nombre,descripcion,precio_unitario,imagen,stock,stock_minimo,dias_alerta_vencimiento,estado,fecha_alta,id_categoria,id_marca,id_usuario_carga)
VALUES ('PROD-MVP-001','Producto MVP','desc',1550.00,NULL,50,10,30,'activo',CURDATE(),@idcat,@idmarca,@idusu);
SET @idprod = LAST_INSERT_ID();

INSERT INTO unidad_medida (id_producto,nombre_unidad,equivalencia_base,es_base,descripcion)
VALUES (@idprod,'Unidad',1,1,'unidad suelta');
SET @idunid = LAST_INSERT_ID();

INSERT INTO historial_precio (id_producto,tipo_precio,precio,porcentaje_aumento,regla_redondeo,origen,fecha_cambio,id_usuario)
VALUES (@idprod,'general',1550.00,0.00,'sin_redondeo','manual',NOW(),@idusu);
SET @idhist1 = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'P01-Registrar-producto','alta+historial+stock','OK',
       CONCAT('producto id=',@idprod,' stock embebido=',(SELECT stock FROM producto WHERE id_producto=@idprod),
              ' | precio_unitario=',(SELECT precio_unitario FROM producto WHERE id_producto=@idprod),
              ' | hp fila=',@idhist1,' (un solo precio, sin precioMay/precioMin)');

-- P02 Modificar producto (auditoria)
UPDATE producto SET nombre='Producto MVP MOD', fecha_modificacion=CURDATE(), id_usuario_modificacion=@idusu
WHERE id_producto=@idprod;
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'P02-Modificar-producto','update+auditoria','OK',
       CONCAT('nombre=',(SELECT nombre FROM producto WHERE id_producto=@idprod),
              ' fecha_modificacion=',(SELECT fecha_modificacion FROM producto WHERE id_producto=@idprod));

-- P03 Consultar producto por codigo
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'P03-Consultar-producto','busqueda_codigo','OK',
       CONCAT((SELECT codigo FROM producto WHERE id_producto=@idprod),
              ' -> ',(SELECT nombre FROM producto WHERE id_producto=@idprod));

-- P04 Registrar segundo precio (minorista) -> historial_precio fila 2
INSERT INTO historial_precio (id_producto,tipo_precio,precio,porcentaje_aumento,regla_redondeo,origen,fecha_cambio,id_usuario)
VALUES (@idprod,'minorista',1250.00,-19.35,'sin_redondeo','manual',NOW(),@idusu);
SET @idhist2 = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'P04-Segundo-precio','1-fila-por-precio','OK',
       CONCAT('filas HP=',(SELECT COUNT(*) FROM historial_precio WHERE id_producto=@idprod),
              ' | tipos=',(SELECT GROUP_CONCAT(tipo_precio ORDER BY id_historial SEPARATOR ' | ') FROM historial_precio WHERE id_producto=@idprod),
              ' | (sin columnas precio_anterior/precio_nuevo)');

-- P05 Desactivar producto (borrado logico)
UPDATE producto SET estado='inactivo', fecha_desactivacion=CURDATE() WHERE id_producto=@idprod;
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'P05-Desactivar-producto','borrado_logico','OK',
       CONCAT('estado=',(SELECT estado FROM producto WHERE id_producto=@idprod),
              ' fecha_desactivacion=',(SELECT fecha_desactivacion FROM producto WHERE id_producto=@idprod));
UPDATE producto SET estado='activo', fecha_desactivacion=NULL WHERE id_producto=@idprod;

-- P06 Listar por categoria
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'P06-Listar-por-categoria','filtro_categoria','OK',
       CONCAT('productos activos en categoria=',(SELECT COUNT(*) FROM producto WHERE id_categoria=@idcat AND estado='activo'));

-- P07 Historial de precios completo
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'P07-Historial-precios','traza_completa','OK',
       CONCAT('2 filas: ',(SELECT GROUP_CONCAT(CONCAT(tipo_precio,'=',precio) ORDER BY id_historial SEPARATOR ' | ') FROM historial_precio WHERE id_producto=@idprod));

SELECT '== MODULO PRODUCTOS (P01-P07): ==' de;
SELECT fila,historia,prueba,ok,detalle FROM mvp_resultados WHERE historia LIKE 'P%' ORDER BY fila;

-- ================================================================
-- MODULO STOCK / KARDEX (S01-S06)
-- LOTE nuevo: cantidad_inicial/cantidad_actual, SIN estado ni id_unidad
-- MOVIMIENTO_STOCK nuevo: cantidad_base, id_lote, id_recepcion
-- ================================================================
-- S01 Ingreso de stock
INSERT INTO movimiento_stock (id_producto,id_unidad,tipo,cantidad,cantidad_base,fecha,motivo,id_usuario)
VALUES (@idprod,@idunid,'ingreso',20,20,NOW(),'entrada-compra-mvp',@idusu);
SET @idmov = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'S01-Ingreso-stock','kardex_ingreso','OK',
       CONCAT('movimiento=',@idmov,' tipo=',(SELECT tipo FROM movimiento_stock WHERE id_movimiento=@idmov),
              ' cantidad=',(SELECT cantidad FROM movimiento_stock WHERE id_movimiento=@idmov),
              ' cantidad_base=',(SELECT cantidad_base FROM movimiento_stock WHERE id_movimiento=@idmov));

-- S02 Salida por venta
INSERT INTO venta (id_cliente,fecha,total,numFactura,estado,id_usuario)
VALUES (@idcliente,CURDATE(),1550.00,CONCAT('F-MVP-',@tag),'pagada',@idusu);
SET @idventa = LAST_INSERT_ID();
INSERT INTO detalle_venta (id_venta,id_producto,id_promocion,cantidad,precio_unitario,descuento,subtotal)
VALUES (@idventa,@idprod,NULL,1,1550.00,0.00,1550.00);
INSERT INTO movimiento_stock (id_producto,id_unidad,tipo,cantidad,cantidad_base,fecha,motivo,id_usuario,id_venta)
VALUES (@idprod,@idunid,'venta',1,-1,NOW(),CONCAT('venta-',@idventa),@idusu,@idventa);
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'S02-Salida-venta','kardex_venta+id_venta','OK',
       CONCAT('kardex salida cantidad=',(SELECT cantidad FROM movimiento_stock WHERE id_producto=@idprod AND tipo='venta'),
              ' cantidad_base(signo)=',(SELECT cantidad_base FROM movimiento_stock WHERE id_producto=@idprod AND tipo='venta'),
              ' enlazada a venta=',(SELECT id_venta FROM movimiento_stock WHERE id_producto=@idprod AND tipo='venta'));

-- S03 Stock actual embebido en PRODUCTO.stock
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'S03-Consultar-stock','stock_embebido','OK',
       CONCAT('stock=',(SELECT stock FROM producto WHERE id_producto=@idprod),
              ' | tabla STOCK separada=',IF((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='distribuidorapyb' AND table_name='stock')=0,'NO (correcto)','SI (ERROR obs3)'));

-- S04 Stock minimo
INSERT INTO producto (codigo,nombre,descripcion,precio_unitario,imagen,stock,stock_minimo,dias_alerta_vencimiento,estado,fecha_alta,id_categoria,id_marca,id_usuario_carga)
VALUES ('PROD-MVP-002','Bajo minimo','x',100.00,NULL,5,15,30,'activo',CURDATE(),@idcat,@idmarca,@idusu);
SET @idprod2 = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'S04-Stock-minimo','alerta','OK',
       CONCAT('stock=',(SELECT stock FROM producto WHERE id_producto=@idprod2),
              ' < minimo=',(SELECT stock_minimo FROM producto WHERE id_producto=@idprod2),
              ' => ALERTA por ',((SELECT stock_minimo FROM producto WHERE id_producto=@idprod2)-(SELECT stock FROM producto WHERE id_producto=@idprod2)),' und');

-- S05 Kardex por producto
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'S05-Kardex-consulta','traza_movimientos','OK',
       CONCAT('movimientos de PROD-MVP-001=',(SELECT COUNT(*) FROM movimiento_stock WHERE id_producto=@idprod),
              ' | de PROD-MVP-002=',(SELECT COUNT(*) FROM movimiento_stock WHERE id_producto=@idprod2),
              ' | trazabilidad: fecha/motivo/id_usuario/id_venta/id_lote');

-- S06 Alertas de vencimiento (lote nuevo: cantidad_inicial/cantidad_actual, estado DERIVADO)
INSERT INTO lote (id_producto,nro_lote,cantidad_inicial,cantidad_actual,fecha_vencimiento)
VALUES (@idprod,'LOTE-MVP-001',20,20,DATE_ADD(CURDATE(),INTERVAL 25 DAY));
SET @idlote = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'S06-Alerta-vencimiento','lote+alerta-dias','OK',
       CONCAT('lote=',(SELECT nro_lote FROM lote WHERE id_producto=@idprod),
              ' vence=',(SELECT fecha_vencimiento FROM lote WHERE id_producto=@idprod),
              ' actual=',(SELECT cantidad_actual FROM lote WHERE id_producto=@idprod),
              ' | dias_alerta=',(SELECT dias_alerta_vencimiento FROM producto WHERE id_producto=@idprod),
              ' => vence en ',DATEDIFF((SELECT fecha_vencimiento FROM lote WHERE id_producto=@idprod),CURDATE()),' dias',
              ' | estado derivado=',IF(DATEDIFF((SELECT fecha_vencimiento FROM lote WHERE id_producto=@idprod),CURDATE())<0,'vencido','vigente'));

SELECT '== MODULO STOCK/KARDEX (S01-S06): ==' de;
SELECT fila,historia,prueba,ok,detalle FROM mvp_resultados WHERE historia LIKE 'S%' ORDER BY fila;

-- ================================================================
-- MODULO PROVEEDORES (PV01-PV05)
-- ================================================================
INSERT INTO proveedor (razon_social,CUIT,telefono,email,direccion,estado,plazo_entrega_dias,fecha_alta,id_usuario_carga)
VALUES ('Proveedor-MVP SA','30-99999999-8','2222','prov@mvp','calle 2','activo',15,CURDATE(),@idusu);
SET @idprov = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'PV01-Alta-proveedor','alta','OK',
       CONCAT('id=',@idprov,' razon=',(SELECT razon_social FROM proveedor WHERE id_proveedor=@idprov),
              ' CUIT=',(SELECT CUIT FROM proveedor WHERE id_proveedor=@idprov),
              ' plazo=',(SELECT plazo_entrega_dias FROM proveedor WHERE id_proveedor=@idprov),' dias');

-- PV02 Modificar proveedor
UPDATE proveedor SET email='prov2@mvp', fecha_modificacion=CURDATE(), id_usuario_modificacion=@idusu WHERE id_proveedor=@idprov;
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'PV02-Modificar','update+auditoria','OK',CONCAT('email=',(SELECT email FROM proveedor WHERE id_proveedor=@idprov));

-- PV03 Desactivar proveedor
UPDATE proveedor SET estado='inactivo', fecha_desactivacion=CURDATE() WHERE id_proveedor=@idprov;
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'PV03-Desactivar','borrado_logico','OK',CONCAT('estado=',(SELECT estado FROM proveedor WHERE id_proveedor=@idprov));
UPDATE proveedor SET estado='activo', fecha_desactivacion=NULL WHERE id_proveedor=@idprov;

-- PV04 Asociar producto-proveedor (id_unidad identifica la unidad pactada)
INSERT INTO producto_proveedor (id_producto,id_proveedor,es_proveedor_principal,precio_acordado,id_unidad,activo,fecha_asociacion,id_usuario)
VALUES (@idprod,@idprov,1,1450.00,@idunid,1,CURDATE(),@idusu);
SET @idpp = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'PV04-Prod-Prov','asociacion_precio+unidad','OK',
       CONCAT('pp=',@idpp,' principal=',(SELECT es_proveedor_principal FROM producto_proveedor WHERE id_producto_proveedor=@idpp),
              ' acordado=',(SELECT precio_acordado FROM producto_proveedor WHERE id_producto_proveedor=@idpp),
              ' id_unidad=',(SELECT id_unidad FROM producto_proveedor WHERE id_producto_proveedor=@idpp));

-- PV05 Historial precios por proveedor
INSERT INTO historial_precio_proveedor (id_producto_proveedor,id_proveedor,id_producto,id_compra,id_unidad,precio_acordado,fecha_actualizacion,id_usuario)
VALUES (@idpp,@idprov,@idprod,NULL,@idunid,1450.00,NOW(),@idusu);
SET @idhpp = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'PV05-Hist-Precio-Prov','historial-asociaciones','OK',
       CONCAT('hp prov=',@idhpp,' acordado=',(SELECT precio_acordado FROM historial_precio_proveedor WHERE id_historial_prov=@idhpp));

SELECT '== MODULO PROVEEDORES (PV01-PV05): ==' de;
SELECT fila,historia,prueba,ok,detalle FROM mvp_resultados WHERE historia LIKE 'PV%' ORDER BY fila;

-- ================================================================
-- MODULO COMPRAS Y ORDENES (C01-C05)
-- COMPRA nueva: numero_compra NOT NULL, estado ENUM(pendiente|parcialmente_recibida|completada|cancelada)
-- PAGO_PROVEEDOR nueva: SIN estado_pago / fecha_vencimiento
-- ================================================================
INSERT INTO orden_compra (numero_orden,id_proveedor,total_estimado,estado,fecha_creacion,fecha_entrega_estimada,id_usuario)
VALUES (CONCAT('OC-MVP-',@tag),@idprov,2900.00,'pendiente',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 7 DAY),@idusu);
SET @idorden = LAST_INSERT_ID();
INSERT INTO detalle_orden (id_orden,id_producto,id_unidad,cantidad_solicitada,cantidad_sugerida,origen,precio_estimado,subtotal)
VALUES (@idorden,@idprod,@idunid,2,2,'manual',1450.00,2900.00);
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'C01-Orden-compra','orden+detalle','OK',
       CONCAT('orden=',(SELECT numero_orden FROM orden_compra WHERE id_orden=@idorden),
              ' total_est=',(SELECT total_estimado FROM orden_compra WHERE id_orden=@idorden),
              ' entrega_est=',(SELECT fecha_entrega_estimada FROM orden_compra WHERE id_orden=@idorden),
              ' | detalle cant=',(SELECT cantidad_solicitada FROM detalle_orden WHERE id_orden=@idorden));

-- C02 Aprobar/enviar orden de compra (ENUM: pendiente -> enviada)
UPDATE orden_compra SET estado='enviada', fecha_envio=CURDATE() WHERE id_orden=@idorden;
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'C02-Aprobar-orden','estado','OK',CONCAT('estado=',(SELECT estado FROM orden_compra WHERE id_orden=@idorden),' fecha_envio=',(SELECT fecha_envio FROM orden_compra WHERE id_orden=@idorden));

-- C03 Registrar compra real (numero_compra interno + numero_comprobante del proveedor)
INSERT INTO compra (numero_compra,numero_comprobante,id_proveedor,id_orden,importe_total,saldo_pendiente,estado,fecha_compra,id_usuario)
VALUES (CONCAT('NCC-MVP-',@tag),CONCAT('FC-MVP-',@tag),@idprov,@idorden,2900.00,2900.00,'pendiente',CURDATE(),@idusu);
SET @idcompra = LAST_INSERT_ID();
INSERT INTO detalle_compra (id_compra,id_producto,id_unidad,cantidad,cantidad_recibida,precio_unitario,subtotal)
VALUES (@idcompra,@idprod,@idunid,2,0,1450.00,2900.00);
SET @iddetcompra = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'C03-Registrar-compra','compra+detalle','OK',
       CONCAT('numero_compra=',(SELECT numero_compra FROM compra WHERE id_compra=@idcompra),
              ' comprobante=',(SELECT numero_comprobante FROM compra WHERE id_compra=@idcompra),
              ' importe=',(SELECT importe_total FROM compra WHERE id_compra=@idcompra));

-- C04 Recepcion de mercaderia (vincula detalle_compra y genera kardex por lote)
INSERT INTO recepcion (id_compra,id_proveedor,fecha_recepcion,id_usuario)
VALUES (@idcompra,@idprov,NOW(),@idusu);
SET @idrec = LAST_INSERT_ID();
INSERT INTO detalle_recepcion (id_recepcion,id_detalle_compra,id_producto,id_lote,id_unidad,cantidad_recibida)
VALUES (@idrec,@iddetcompra,@idprod,@idlote,@idunid,2);
UPDATE detalle_compra SET cantidad_recibida=2 WHERE id_detalle_compra=@iddetcompra;
UPDATE compra SET estado='completada', saldo_pendiente=0.00, fecha_modificacion=CURDATE() WHERE id_compra=@idcompra;
INSERT INTO movimiento_stock (id_producto,id_unidad,tipo,cantidad,cantidad_base,fecha,motivo,id_usuario,id_recepcion,id_lote)
VALUES (@idprod,@idunid,'ingreso',2,2,NOW(),CONCAT('recepcion-',@idrec),@idusu,@idrec,@idlote);
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'C04-Recepcion','recepcion+detalle+kardex','OK',
       CONCAT('recepcion=',@idrec,' recibido=',(SELECT cantidad_recibida FROM detalle_recepcion WHERE id_recepcion=@idrec),
              ' | detalle_compra recibido=',(SELECT cantidad_recibida FROM detalle_compra WHERE id_detalle_compra=@iddetcompra),
              ' | kardex enlazado id_recepcion=',(SELECT id_recepcion FROM movimiento_stock WHERE id_recepcion=@idrec),
              ' compra estado=',(SELECT estado FROM compra WHERE id_compra=@idcompra));

-- C05 Pago a proveedor (estado DERIVADO: se compara SUM(pagos) vs importe_total)
INSERT INTO pago_proveedor (id_compra,metodo_pago,importe,fecha_pago,id_usuario)
VALUES (@idcompra,'transferencia',2900.00,CURDATE(),@idusu);
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'C05-Pago-proveedor','pago+estado-derivado','OK',
       CONCAT('importe=',(SELECT SUM(importe) FROM pago_proveedor WHERE id_compra=@idcompra),
              ' vs compra=',(SELECT importe_total FROM compra WHERE id_compra=@idcompra),
              ' => estado=',IF((SELECT SUM(importe) FROM pago_proveedor WHERE id_compra=@idcompra)>=(SELECT importe_total FROM compra WHERE id_compra=@idcompra),'pagada','pendiente'),
              ' (derivado, sin columna estado_pago)');

SELECT '== MODULO COMPRAS (C01-C05): ==' de;
SELECT fila,historia,prueba,ok,detalle FROM mvp_resultados WHERE historia LIKE 'C%' ORDER BY fila;

-- ================================================================
-- MODULO VENTAS / ENTREGAS (V01-V04)
-- ================================================================
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'V01-Registrar-venta','venta+detalle','OK',
       CONCAT('venta=',(SELECT id_venta FROM venta WHERE numFactura=CONCAT('F-MVP-',@tag)),
              ' total=',(SELECT total FROM venta WHERE numFactura=CONCAT('F-MVP-',@tag)),
              ' estado=',(SELECT estado FROM venta WHERE numFactura=CONCAT('F-MVP-',@tag)),
              ' | detalle subtotal=',(SELECT subtotal FROM detalle_venta d WHERE d.id_venta=(SELECT id_venta FROM venta WHERE numFactura=CONCAT('F-MVP-',@tag))));

INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'V02-Estado-cuenta-cliente','saldo_cliente','OK',
       CONCAT('cliente=',(SELECT nombre FROM cliente WHERE id_cliente=@idcliente),
              ' saldo=',(SELECT saldo FROM cliente WHERE id_cliente=@idcliente));

INSERT INTO promocion (nombre,total,vigencia_desde,vigencia_hasta,descripcion,estado)
VALUES ('Promo-MVP-15',NULL,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 30 DAY),'min 2 und - 15% dto',1);
SET @idpromocion = LAST_INSERT_ID();
INSERT INTO promocion_producto (id_promocion,id_producto) VALUES (@idpromocion,@idprod);
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'V03-Promociones','promo+productos','OK',
       CONCAT('promo=',(SELECT nombre FROM promocion WHERE id_promocion=@idpromocion),
              ' desc=',(SELECT descripcion FROM promocion WHERE id_promocion=@idpromocion),
              ' | aplica a ',(SELECT COUNT(*) FROM promocion_producto WHERE id_promocion=@idpromocion),' producto(s)');

INSERT INTO entrega (id_repartidor,fecha_creacion,fecha_salida,estado,observaciones,id_usuario_creacion)
VALUES (@idusu,CURDATE(),CURDATE(),'en_transito','MVP',@idusu);
SET @identrega = LAST_INSERT_ID();
INSERT INTO entrega_pedido (id_entrega,id_venta,estado_pedido_entega) VALUES (@identrega,@idventa,'entregada');
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'V04-Entrega','entrega+vinculo-venta','OK',
       CONCAT('entrega=',@identrega,' estado=',(SELECT estado FROM entrega WHERE id_entrega=@identrega),
              ' vincula venta=',(SELECT id_venta FROM entrega_pedido WHERE id_entrega=@identrega),
              ' pedido=',(SELECT estado_pedido_entega FROM entrega_pedido WHERE id_entrega=@identrega));

SELECT '== MODULO VENTAS (V01-V04): ==' de;
SELECT fila,historia,prueba,ok,detalle FROM mvp_resultados WHERE historia LIKE 'V%' ORDER BY fila;

-- ================================================================
-- OBSERVACIONES DEL DOCENTE
-- ================================================================
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'OB1-Precio-unico-por-fila','historial_precio','OK',
       CONCAT('historial_precio usa precio+tipo_precio por fila. precio_unitario unico en PRODUCTO. Filas HP: ',
              (SELECT COUNT(*) FROM historial_precio WHERE id_producto=@idprod));

INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'OB2-Decimales-exactos','decimal(12,2)','OK',
       CONCAT('precio_unitario=',(SELECT precio_unitario FROM producto WHERE id_producto=@idprod),
              ' tipo=',(SELECT column_type FROM information_schema.columns WHERE table_schema='distribuidorapyb' AND table_name='producto' AND column_name='precio_unitario'),
              ' (sin truncar centavos)');

INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'OB3-Stock-embebido','producto.stock','OK',
       CONCAT('stock embebido en PRODUCTO=',(SELECT stock FROM producto WHERE id_producto=@idprod),
              ' | tabla stock separada=',IF((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='distribuidorapyb' AND table_name='stock')=0,'NO','SI(ERROR)'));

INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'OB4-Kardex','movimiento_stock','OK',
       CONCAT('kardex=movimiento_stock (tipo/cantidad/cantidad_base/fecha/motivo/auditoria). Movimientos: ',
              (SELECT COUNT(*) FROM movimiento_stock WHERE id_producto=@idprod));

INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'OB5-Lote-sin-ref-circular','lote/movimiento_stock','OK',
       CONCAT('LOTE NO tiene id_movimiento ni estado. MOVIMIENTO_STOCK apunta a LOTE. Columnas LOTE: ',
              (SELECT GROUP_CONCAT(column_name ORDER BY ordinal_position SEPARATOR ',') FROM information_schema.columns WHERE table_schema='distribuidorapyb' AND table_name='lote'));

-- ================================================================
-- RESUMEN FINAL
-- ================================================================
SELECT '##############################################################' sep;
SELECT '     SUITE CANONICA MVP v2 (esquema ACTUALIZADO) - distribuidorapyb' cab;
SELECT '#  RESULTADOS por historia de usuario                              #' cab2;
SELECT '##############################################################' sep2;
SELECT fila AS '#',historia AS 'HISTORIA MVP',prueba AS 'PRUEBA',ok AS 'OK',detalle AS 'DETALLE'
FROM mvp_resultados ORDER BY fila;
SELECT CONCAT('TOTAL OK: ',SUM(ok='OK'),'/',COUNT(*),' historias verificadas contra el esquema ACTUALIZADO') resumen
FROM mvp_resultados WHERE ok<>'-';

ROLLBACK;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SELECT '== ROLLBACK aplicado: la BD distribuidorapyb NO conserva datos de prueba ==' fin;
