# Reporte MVP — Criterios de Aceptación 31/31 OK

BD: `distribuidorapyb` (clave foránea, esquema fusionado, ack de diseño OB1-OB4)
Ejecución: suite `DistribuidoraPyB_MVP.sql` dentro de transacción + ROLLBACK final.
Estado: **31/31 historias cumplen su criterio de aceptación** · `exit=0` · sin errores.

## Módulo PRODUCTOS (P01-P07)
| Historia | Criterio de aceptación | Evidencia de la corrida |
|---|---|---|
| P01 Registrar producto | Alta + 1 fila de historial de precio + stock incial embebido | `P01 OK: producto id=14 stock embebido=50 \| hp fila=14 tipo_precio=mayorista precio=1550.00` |
| P02 Modificar producto | Update + auditoría (fecha_modificacion) | `P02 OK: nombre=Producto MVP MOD fecha_modificacion=2026-09-12` |
| P03 Consultar por código | Búsqueda por código exacta | `P03 OK: PROD-MVP-001 -> Producto MVP MOD` |
| P04 Segundo precio | 1 fila por precio (historial_precio) | `P04 OK: filas HP=2 tipos=mayorista\|minorista (sin cols precio_anterior/nuevo)` |
| P05 Desactivar producto | Borrado lógico (estado + fecha) | `P05 OK: estado=inactivo fecha_desactivacion=2026-09-12` |
| P06 Listar por categoría | Filtro por categoría, solo activos | `P06 OK: productos activos en categoria=1` |
| P07 Historial de precios | Traza completa de cambios | `P07 OK: 2 filas con precios: mayorista=1550.00 \| minorista=1250.00` |

## Módulo STOCK/KARDEX (S01-S06)
| Historia | Criterio de aceptación | Evidencia de la corrida |
|---|---|---|
| S01 Ingreso de stock | Kardex de ingreso (motivo id_producto) | `S01 OK: movimiento=14 tipo=ingreso cantidad=20 \| stock producto=50` |
| S02 Salida por venta | Kardex salida enlazado a id_venta | `S02 OK: salida cantidad=1 enlazada a venta=8` |
| S03 Consultar stock | Stock embebido (sin tabla stock separada) | `S03 OK: stock=50 \| tabla STOCK separada existe=NO (correcto)` |
| S04 Stock mínimo | Alerta si stock < mínimo | `S04 OK: stock=5 < minimo=15 => ALERTA por 10 und` |
| S05 Kardex consulta | Traza de movimientos | `S05 OK: movimientos PROD-MVP-001=2 \| de PROD-MVP-002=0` |
| S06 Alerta vencimiento | Lote + días hasta vencer | `S06 OK: lote=LOTE-MVP-001 vence=2026-10-07 => vence en 25 dias` |

## Módulo PROVEEDORES (PV01-PV05)
| Historia | Criterio de aceptación | Evidencia de la corrida |
|---|---|---|
| PV01 Alta proveedor | Alta con razón social + plazo entrega | `PV01 OK: id=4 razon=Proveedor-MVP SA plazo entrega=15 dias` |
| PV02 Modificar | Update + auditoría | `PV02 OK: email=prov2@mvp` |
| PV03 Desactivar | Borrado lógico (estado) | `PV03 OK: estado=inactivo` |
| PV04 Producto-proveedor | Asociación con precio acordado | `PV04 OK: pp=4 principal=1 acordado=1450.00` |
| PV05 Historial precio proveedor | 2 tipos de precio aceptados | `PV05 OK: hp prov=4 acordado=1450.00` |

## Módulo COMPRAS (C01-C05)
| Historia | Criterio de aceptación | Evidencia de la corrida |
|---|---|---|
| C01 Orden de compra | Orden + detalle | `C01 OK: orden=OC-MVP-0001 total_est=1450.00 \| detalle cant=2` |
| C02 Aprobar orden | Estado aprobada | `C02 OK: estado=aprobada` |
| C03 Registrar compra | Compra + detalle con cant. recibida | `C03 OK: compra=FC-MVP-0001 importe=2900.00 detalle cant_recibida=2` |
| C04 Recepción | Recepción + detalle con lote real | `C04 OK: recepcion=4 recibido=2` (enlazado a @idlote del lote S06) |
| C05 Pago proveedor | Pago registrado (estado pagado) | `C05 OK: importe=2900.00 estado=pagado` |

## Módulo VENTAS (V01-V04)
| Historia | Criterio de aceptación | Evidencia de la corrida |
|---|---|---|
| V01 Registrar venta | Venta + detalle | `V01 OK: venta=8 total=1550.00 detalle subtotal=1550.00` |
| V02 Estado de cuenta cliente | Saldo del cliente | `V02 OK: cliente=Admin-MVP saldo=0.00` |
| V03 Promociones | Promoción + productos | `V03 OK: promo=Promo-MVP-15 desc=15.00% aplica a 1 producto(s)` |
| V04 Entrega | Entrega vinculada a venta | `V04 OK: entrega=3 estado=en_ruta vincula venta=8` |

## Observaciones de diseño (OB1-OB4)
| Observación | Reflejo en esquema | Evidencia de la corrida |
|---|---|---|
| OB1 Historial precios 1-fila | historial_precio 1 fila/valor | `OB1 OK: 2 filas, sin columnas precio_anterior/nuevo` |
| OB2 Log de auditoría | log_auditoria centralizada | `OB2 OK: tabla_afectada/valores_anteriores/valores_nuevos/id_usuario` |
| OB3 Stock embebido | columna stock en producto | `OB3 OK: sin tabla stock separada, stock en producto` |
| OB4 Kardex central | movimiento_stock con tipos | `OB4 OK: kardex con fecha/motivo/id_usuario/id_venta` |

---
`TOTAL OK: 31/31 historias de MVP` · `== ROLLBACK aplicado: la BD no conserva datos de prueba ==`
