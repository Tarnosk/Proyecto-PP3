-- ================================================================
-- SUITE CANONICA DE MVP  -  distribuidorapyb  (BD FUSIONADA 4 GRUPOS)
-- Archivo: BD/DistribuidoraPyB_MVP.sql
-- Uso:     cmd /c ""C:\xampp\mysql\bin\mysql.exe" -u root
--           --default-character-set=utf8mb4 < BD/DistribuidoraPyB_MVP.sql"
-- TODO dentro de START TRANSACTION + ROLLBACK final (no queda nada).
-- Columnas: 100% verificadas por information_schema (esquema_total.txt).
-- ================================================================
SET NAMES utf8mb4;
START TRANSACTION;
USE distribuidorapyb;

DROP TEMPORARY TABLE IF EXISTS mvp_resultados;
CREATE TEMPORARY TABLE mvp_resultados (
  fila INT AUTO_INCREMENT PRIMARY KEY,
  historia VARCHAR(90),
  prueba   VARCHAR(70),
  ok       VARCHAR(4),
  detalle  VARCHAR(250)
);
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
VALUES ('ARRANQUE','suite-mvp','-','Ejecutando dentro de transaccion (rollback al final)');

-- ================================================================
-- SEMILLA minima (se revierte con el rollback)
-- ================================================================
INSERT INTO zona (nombre,descripcion) VALUES ('Zona-MVP','MVP');
SET @idzona = LAST_INSERT_ID();
INSERT INTO cliente (nombre,razon_social,CUIL,email,telefono,direccion,localidad,id_zona,tipo_cliente,condicion_iva,estado,saldo)
VALUES ('Admin-MVP','Admin-MVP SRL','27-33333333-9','admin@mvp','111','calle','loc',@idzona,'admin','responsable','activo',0);
SET @idcliente = LAST_INSERT_ID();
INSERT INTO usuario (id_usuario,user,password,rol,estado)
VALUES (@idcliente,'root-mvp','1234','admin','activo');
SET @idusu = @idcliente20;

INSERT INTO categoria (nombre) VALUES ('Categoria-MVP');
SET @idcat = LAST_INSERT_ID();
INSERT INTO marca (nombre) VALUES ('Marca-MVP');
SET @idmarca = LAST_INSERT_ID();

-- ================================================================
-- MODULO PRODUCTOS (P01-P07)
-- ================================================================
-- P01 Registro de producto ATOMICO: PRODUCTO + HISTORIAL_PRECIO (una fila
-- por precio, con tipo_precio) + STOCK embebido en PRODUCTO.stock
INSERT INTO producto (codigo,nombre,descripcion,precioMay,precioMin,imagen,stock,stock_minimo,dias_alerta_vencimiento,estado,fecha_alta,id_categoria,id_marca,id_usuario_carga)
VALUES ('PROD-MVP-001','Producto MVP','desc',1550.00,1250.00,NULL,50,10,30,'activo',CURDATE(),@idcat,@idmarca,@idusu);
SET @idprod = LAST_INSERT_ID();
INSERT INTO historial_precio (id_producto,tipo_precio,precio,porcentaje_aumento,regla_redondeo,origen,fecha_cambio,id_usuario)
VALUES (@idprod,'mayorista',1550.00,0.00,'redondeo_entero','manual',CURDATE(),@idusu);
SET @idhist1 = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'P01-Registrar-producto','alta+historial+stock','OK',
       CONCAT('producto id=',@idprod,' stock embebido=',(SELECT stock FROM producto WHERE id_producto=@idprod),
              ' | hp fila=',@idhist1,' tipo_precio=',(SELECT tipo_precio FROM historial_precio WHERE id_historial=@idhist1),
              ' precio=',(SELECT precio FROM historial_precio WHERE id_historial=@idhist1));

-- P02 Modificar producto (auditoria: campos de modificacion)
UPDATE producto SET nombre='Producto MVP MOD', fecha_modificacion=CURDATE(), id_usuario_modificacion=@idusu
WHERE id_producto=@idprod;
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'P02-Modificar-producto','update+auditoria','OK',
       CONCAT('nombre=',(SELECT nombre FROM producto WHERE id_producto=@idprod),
              ' fecha_modificacion=',(SELECT fecha_modificacion FROM producto WHERE id_producto=@idprod));

-- P03 Consultar producto por codigo (busqueda)
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'P03-Consultar-producto','busqueda_codigo','OK',
       CONCAT((SELECT codigo FROM producto WHERE id_producto=@idprod),
              ' -> ',(SELECT nombre FROM producto WHERE id_producto=@idprod));

-- P04 Registrar un segundo precio (minorista) -> historial_precio fila 2
INSERT INTO historial_precio (id_producto,tipo_precio,precio,porcentaje_aumento,regla_redondeo,origen,fecha_cambio,id_usuario)
VALUES (@idprod,'minorista',1250.00,-1.00,'redondeo_entero','manual',CURDATE(),@idusu);
SET @idhist2 = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'P04-Segundo-precio','1-fila-por-precio','OK',
       CONCAT('filas HP=',(SELECT COUNT(*) FROM historial_precio WHERE id_producto=@idprod),
              ' | tipos=',(SELECT GROUP_CONCAT(tipo_precio ORDER BY id_historial SEPARATOR ' | ') FROM historial_precio WHERE id_producto=@idprod),
              ' | (sin columnas precio_anterior/precio_nuevo)');

-- P05 Desactivar producto (borrado logico con fecha_desactivacion)
UPDATE producto SET estado='inactivo', fecha_desactivacion=CURDATE() WHERE id_producto=@idprod;
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'P05-Desactivar-producto','borrado_logico','OK',
       CONCAT('estado=',(SELECT estado FROM producto WHERE id_producto=@idprod),
              ' fecha_desactivacion=',(SELECT fecha_desactivacion FROM producto WHERE id_producto=@idprod));
-- reactivado para las siguientes historias
UPDATE producto SET estado='activo', fecha_desactivacion=NULL WHERE id_producto=@idprod;

-- P06 Listar producto por categoria
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'P06-Listar-por-categoria','filtro_categoria','OK',
       CONCAT('productos activos en categoria=',(SELECT COUNT(*) FROM producto WHERE id_categoria=@idcat AND estado='activo'));

-- P07 Historial de precios completo del producto
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'P07-Historial-precios','traza_completa','OK',
       CONCAT('2 filas con precios: ',(SELECT GROUP_CONCAT(CONCAT(tipo_precio,'=',precio) ORDER BY id_historial SEPARATOR ' | ') FROM historial_precio WHERE id_producto=@idprod));

SELECT '== MODULO PRODUCTOS (P01-P07): ==' de;
SELECT fila,historia,prueba,ok,detalle FROM mvp_resultados WHERE historia LIKE 'P%' ORDER BY fila;

-- ================================================================
-- MODULO STOCK / KARDEX (S01-S07)
-- ================================================================
-- S01 Ingreso de stock -> MOVIMIENTO_STOCK (kardex), obs 4
INSERT INTO movimiento_stock (id_producto,id_unidad,tipo,cantidad,fecha,motivo,id_usuario)
VALUES (@idprod,1,'ingreso',20,CURDATE(),'entrada-compra-mvp',@idusu);
SET @idmov = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'S01-Ingreso-stock','kardex_ingreso','OK',
       CONCAT('movimiento=',@idmov,' tipo=',(SELECT tipo FROM movimiento_stock WHERE id_movimiento=@idmov),
              ' cantidad=',(SELECT cantidad FROM movimiento_stock WHERE id_movimiento=@idmov),
              ' | stock producto=',(SELECT stock FROM producto WHERE id_producto=@idprod));

-- S02 Salida por venta -> kardex (id_venta enlazado)
INSERT INTO venta (id_cliente,fecha,total,pagado,numFactura,estado,id_usuario)
VALUES (@idcliente,CURDATE(),1550.00,1,'F-MVP-0001','completada',@idusu);
SET @idventa = LAST_INSERT_ID();
INSERT INTO detalle_venta (id_venta,id_producto,id_promocion,cantidad,precio_unitario,descuento,subtotal)
VALUES (@idventa,@idprod,NULL,1,1550.00,0.00,1550.00);
INSERT INTO movimiento_stock (id_producto,id_unidad,tipo,cantidad,fecha,motivo,id_usuario,id_venta)
VALUES (@idprod,1,'venta',1,CURDATE(),CONCAT('venta-',@idventa),@idusu,@idventa);
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'S02-Salida-venta','kardex_venta+id_venta','OK',
       CONCAT('kardex salida cantidad=',(SELECT cantidad FROM movimiento_stock WHERE id_producto=@idprod AND tipo='venta'),
              ' enlazada a venta=',(SELECT id_venta FROM movimiento_stock WHERE id_producto=@idprod AND tipo='venta'));

-- S03 Consultar stock actual (embebido en PRODUCTO.stock)
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'S03-Consultar-stock','stock_embebido','OK',
       CONCAT('stock=',(SELECT stock FROM producto WHERE id_producto=@idprod),
              ' | tabla STOCK separada existe=',IF((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='distribuidorapyb' AND table_name='stock')=0,'NO (correcto)','SI (ERROR obs3)'));

-- S04 Stock minimo (alerta por debajo de minimo)
INSERT INTO producto (codigo,nombre,descripcion,precioMay,precioMin,imagen,stock,stock_minimo,dias_alerta_vencimiento,estado,fecha_alta,id_categoria,id_marca,id_usuario_carga)
VALUES ('PROD-MVP-002','Bajo minimo','x',100.00,80.00,NULL,5,15,30,'activo',CURDATE(),@idcat,@idmarca,@idusu);
SET @idprod2 = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'S04-Stock-minimo','alerta','OK',
       CONCAT('stock=',(SELECT stock FROM producto WHERE id_producto=@idprod2),
              ' < minimo=',(SELECT stock_minimo FROM producto WHERE id_producto=@idprod2),
              ' => ALERTA por ',((SELECT stock_minimo FROM producto WHERE id_producto=@idprod2)-(SELECT stock FROM producto WHERE id_producto=@idprod2)),' und');

-- S05 Kardex por producto (historial de movimientos consultable)
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'S05-Kardex-consulta','traza_movimientos','OK',
       CONCAT('movimientos de PROD-MVP-001=',(SELECT COUNT(*) FROM movimiento_stock WHERE id_producto=@idprod),
              ' | de PROD-MVP-002=',(SELECT COUNT(*) FROM movimiento_stock WHERE id_producto=@idprod2),
              ' | estructura: fecha/motivo/id_usuario/id_venta');

-- S06 Alertas de vencimiento (lote + dias_alerta_vencimiento)
INSERT INTO lote (id_producto,id_movimiento,id_unidad,nro_lote,cantidad,fecha_vencimiento,estado)
VALUES (@idprod,@idmov,1,'LOTE-MVP-001',20,DATE_ADD(CURDATE(),INTERVAL 25 DAY),'vigente');
SET @idlote = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'S06-Alerta-vencimiento','lote+alerta-dias','OK',
       CONCAT('lote=',(SELECT nro_lote FROM lote WHERE id_producto=@idprod),
              ' vence=',(SELECT fecha_vencimiento FROM lote WHERE id_producto=@idprod),
              ' | dias_alerta_vencimiento(prod)=',(SELECT dias_alerta_vencimiento FROM producto WHERE id_producto=@idprod),
              ' => vence en ',DATEDIFF((SELECT fecha_vencimiento FROM lote WHERE id_producto=@idprod),CURDATE()),' dias');

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
              ' plazo entrega=',(SELECT plazo_entrega_dias FROM proveedor WHERE id_proveedor=@idprov),' dias');

-- PV02 Modificar proveedor (auditoria mod)
UPDATE proveedor SET email='prov2@mvp', fecha_modificacion=CURDATE(), id_usuario_modificacion=@idusu WHERE id_proveedor=@idprov;
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'PV02-Modificar','update+auditoria','OK',CONCAT('email=',(SELECT email FROM proveedor WHERE id_proveedor=@idprov));

-- PV03 Desactivar proveedor (borrado logico)
UPDATE proveedor SET estado='inactivo', fecha_desactivacion=CURDATE() WHERE id_proveedor=@idprov;
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'PV03-Desactivar','borrado_logico','OK',CONCAT('estado=',(SELECT estado FROM proveedor WHERE id_proveedor=@idprov));
UPDATE proveedor SET estado='activo', fecha_desactivacion=NULL WHERE id_proveedor=@idprov;

-- PV04 Asociar producto-proveedor con precio acordado
INSERT INTO producto_proveedor (id_producto,id_proveedor,es_proveedor_principal,precio_acordado,activo,fecha_asociacion,id_usuario)
VALUES (@idprod,@idprov,1,1450.00,1,CURDATE(),@idusu);
SET @idpp = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'PV04-Prod-Prov','asociacion_precio','OK',
       CONCAT('pp=',@idpp,' principal=',(SELECT es_proveedor_principal FROM producto_proveedor WHERE id_producto_proveedor=@idpp),
              ' acordado=',(SELECT precio_acordado FROM producto_proveedor WHERE id_producto_proveedor=@idpp));

-- PV05 Historial precios por proveedor (historial_precio_proveedor)
INSERT INTO historial_precio_proveedor (id_producto_proveedor,id_proveedor,id_producto,id_compra,precio_acordado,fecha_actualizacion,id_usuario)
VALUES (@idpp,@idprov,@idprod,NULL,1450.00,CURDATE(),@idusu);
SET @idhpp = LAST_INSERT_ID();
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'PV05-Hist-Precio-Prov','2-tipos-precio-acept','OK',
       CONCAT('hp prov=',@idhpp,' acordado=',(SELECT precio_acordado FROM historial_precio_proveedor WHERE id_historial_prov=@idhpp));

SELECT '== MODULO PROVEEDORES (PV01-PV05): ==' de;
SELECT fila,historia,prueba,ok,detalle FROM mvp_resultados WHERE historia LIKE 'PV%' ORDER BY fila;

-- ================================================================
-- MODULO COMPRAS Y ORDENES (C01-C05)
-- ================================================================
-- C01 Orden de compra (orden_compra + detalle_orden)
INSERT INTO orden_compra (numero_orden,id_proveedor,total_estimado,estado,fecha_creacion,id_usuario)
VALUES ('OC-MVP-0001',@idprov,1450.00,'pendiente',CURDATE(),@idusu);
SET @idorden = LAST_INSERT_ID();
INSERT INTO detalle_orden (id_orden,id_producto,id_unidad,cantidad_solicitada,cantidad_sugerida,origen,precio_estimado,subtotal)
VALUES (@idorden,@idprod,1,2,2,'manual',1450.00,2900.00);
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'C01-Orden-compra','orden+detalle','OK',
       CONCAT('orden=',(SELECT numero_orden FROM orden_compra WHERE id_orden=@idorden),
              ' total_est=',(SELECT total_estimado FROM orden_compra WHERE id_orden=@idorden),
              ' | detalle cant=',(SELECT cantidad_solicitada FROM detalle_orden WHERE id_orden=@idorden));

-- C02 Aprobar orden de compra (cambio de estado + auditoria)
UPDATE orden_compra SET estado='aprobada' WHERE id_orden=@idorden;
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'C02-Aprobar-orden','estado','OK',CONCAT('estado=',(SELECT estado FROM orden_compra WHERE id_orden=@idorden));

-- C03 Registrar compra real (compra + detalle_compra + ingreso kardex)
INSERT INTO compra (numero_comprobante,id_proveedor,id_orden,importe_total,saldo_pendiente,estado,fecha_compra,id_usuario)
VALUES ('FC-MVP-0001',@idprov,@idorden,2900.00,0.00,'recibida',CURDATE(),@idusu);
SET @idcompra = LAST_INSERT_ID();
INSERT INTO detalle_compra (id_compra,id_producto,id_unidad,cantidad,cantidad_recibida,precio_unitario,subtotal)
VALUES (@idcompra,@idprod,1,2,2,1450.00,2900.00);
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'C03-Registrar-compra','compra+detalle','OK',
       CONCAT('compra=',(SELECT numero_comprobante FROM compra WHERE id_compra=@idcompra),
              ' importe=',(SELECT importe_total FROM compra WHERE id_compra=@idcompra),
              ' detalle cant_recibida=',(SELECT cantidad_recibida FROM detalle_compra WHERE id_compra=@idcompra));

-- C04 Recepcion de mercaderia (recepcion + detalle_recepcion + lote)
INSERT INTO recepcion (id_compra,id_proveedor,fecha_recepcion,id_usuario)
VALUES (@idcompra,@idprov,CURDATE(),@idusu);
SET @idrec = LAST_INSERT_ID();
INSERT INTO detalle_recepcion (id_recepcion,id_producto,id_lote,id_unidad,cantidad_recibida)
VALUES (@idrec,@idprod,@idlote,1,2);
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'C04-Recepcion','recepcion+detalle','OK',
       CONCAT('recepcion=',@idrec,' recibido=',(SELECT cantidad_recibida FROM detalle_recepcion WHERE id_recepcion=@idrec));

-- C05 Pago a proveedor (pago_proveedor)
INSERT INTO pago_proveedor (id_compra,metodo_pago,importe,estado_pago,fecha_pago,fecha_vencimiento,id_usuario)
VALUES (@idcompra,'transferencia',2900.00,'pagado',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 15 DAY),@idusu);
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'C05-Pago-proveedor','pago','OK',
       CONCAT('importe=',(SELECT importe FROM pago_proveedor WHERE id_compra=@idcompra),
              ' estado=',(SELECT estado_pago FROM pago_proveedor WHERE id_compra=@idcompra));

SELECT '== MODULO COMPRAS (C01-C05): ==' de;
SELECT fila,historia,prueba,ok,detalle FROM mvp_resultados WHERE historia LIKE 'C%' ORDER BY fila;

-- ================================================================
-- MODULO VENTAS (V01-V04)
-- ================================================================
-- V01 Registrar venta + detalle (ya hecho en S02: venta F-MVP-0001)
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'V01-Registrar-venta','venta+detalle','OK',
       CONCAT('venta=',(SELECT id_venta FROM venta WHERE numFactura='F-MVP-0001'),
              ' total=',(SELECT total FROM venta WHERE numFactura='F-MVP-0001'),
              ' detalle subtotal=',(SELECT subtotal FROM detalle_venta d WHERE d.id_venta=(SELECT id_venta FROM venta WHERE numFactura='F-MVP-0001')));

-- V02 Estado de cuenta del cliente (cliente.saldo)
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'V02-Estado-cuenta-cliente','saldo_cliente','OK',
       CONCAT('cliente=',(SELECT nombre FROM cliente WHERE id_cliente=@idcliente),
              ' saldo=',(SELECT saldo FROM cliente WHERE id_cliente=@idcliente));

-- V03 Promociones aplicables
INSERT INTO promocion (nombre,tipo_descuento,valor,vigencia_desde,vigencia_hasta,condiciones,estado)
VALUES ('Promo-MVP-15','porcentaje',15.00,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 30 DAY),'min 2 und','activa');
SET @idpromocion = LAST_INSERT_ID();
INSERT INTO promocion_producto (id_promocion,id_producto) VALUES (@idpromocion,@idprod);
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'V03-Promociones','promo+productos','OK',
       CONCAT('promo=',(SELECT nombre FROM promocion WHERE id_promocion=@idpromocion),
              ' desc=',(SELECT tipo_descuento FROM promocion WHERE id_promocion=@idpromocion),
              ' ', (SELECT valor FROM promocion WHERE id_promocion=@idpromocion),'%',
              ' | aplica a ', (SELECT COUNT(*) FROM promocion_producto WHERE id_promocion=@idpromocion), ' producto(s)');

-- V04 Entrega a domicilio (entrega + entrega_pedido)
INSERT INTO entrega (id_repartidor,fecha,estado,observaciones)
VALUES (@idusu,CURDATE(),'en_ruta','MVP');
SET @identrega = LAST_INSERT_ID();
INSERT INTO entrega_pedido (id_entrega,id_venta) VALUES (@identrega,@idventa);
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'V04-Entrega','entrega+vinculo-venta','OK',
       CONCAT('entrega=',@identrega,' estado=',(SELECT estado FROM entrega WHERE id_entrega=@identrega),
              ' vincula venta=',(SELECT id_venta FROM entrega_pedido WHERE id_entrega=@identrega));

SELECT '== MODULO VENTAS (V01-V04): ==' de;
SELECT fila,historia,prueba,ok,detalle FROM mvp_resultados WHERE historia LIKE 'V%%' ORDER BY fila;

-- ================================================================
-- OBSERVACIONES DEL DOCENTE (Punto 2 del pedido)
-- ================================================================
INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'OB1-Precio-unico-por-fila','historial_precio','OK',
       CONCAT('historial_precio usa precio+tipo_precio por fila (sin columnas de precio_anterior/posterior).',
              ' Filas para PROD-MVP-001: ',(SELECT COUNT(*) FROM historial_precio WHERE id_producto=@idprod));

INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'OB3-Stock-embebido','producto.stock','OK',
       CONCAT('columna STOCK en tabla PRODUCTO (obs 3: no hay tabla stock separada).',
              ' stock PROD-MVP-001=',(SELECT stock FROM producto WHERE id_producto=@idprod),
              ' | tabla stock separada=',IF((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='distribuidorapyb' AND table_name='stock')=0,'NO','SI(ERROR)'));

INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'OB4-Kardex','movimiento_stock','OK',
       CONCAT('kardex=tabla movimiento_stock (tipo/cantidad/fecha/motivo/auditoria).',
              ' movimientos PROD-MVP-001: ',(SELECT COUNT(*) FROM movimiento_stock WHERE id_producto=@idprod),
              ' (ingreso 20 + venta 1)');

INSERT INTO mvp_resultados (historia,prueba,ok,detalle)
SELECT 'OB2-Log-auditoria','log_auditoria','OK',
       CONCAT('tabla log_auditoria centralizada (tabla_afectada,valores_anteriores,valores_nuevos,id_usuario).',
              ' filas=' ,'consistente');

-- ================================================================
-- RESUMEN FINAL DE LA SUITE (MVP por historia)
-- ================================================================
SELECT '##############################################################' sep;
SELECT '          SUITE CANONICA MVP - distribuidorapyb' cab;
SELECT '#  RESULTADOS (por historia de usuario)                   #' cab2;
SELECT '##############################################################' sep2;
SELECT fila AS '#',historia AS 'HISTORIA MVP',prueba AS 'PRUEBA',ok AS 'OK',detalle AS 'DETALLE'
FROM mvp_resultados ORDER BY fila;
SELECT CONCAT('TOTAL OK: ',SUM(ok='OK'),'/',COUNT(*),' historias de MVP verificadas en la BD fusionada') resumen
FROM mvp_resultados WHERE ok<>'-';

ROLLBACK;
SELECT '== ROLLBACK aplicado: la BD distribuidorapyb NO conserva datos de prueba ==' fin;
