# PLAN DE FUSIÓN DE BASE DE DATOS: GRUPO 3 CON PROYECTO GENERAL

**Proyecto:** ERP Distribuidora — Integración Base de Datos General + Módulo Grupo 3  
**Fecha:** 10 de Septiembre de 2026  
**Objetivo:** Fusionar el esquema propio del Grupo 3 con el esquema del Proyecto General, resolviendo las observaciones del docente, unificando discrepancias y estableciendo las claves foráneas reales con los demás grupos.

---

## 1. MAPEO COMPARATIVO Y RESOLUCIÓN DE CONFLICTOS

| Entidad / Concepto | En Diagrama General | En Diagrama Grupo 3 | Decisión de Fusión Justificada |
| :--- | :--- | :--- | :--- |
| **`USUARIO` (Seguridad)** | Tabla `USUARIO (id_usuario, user, password, rol, estado)`. | Tenía comentarios apuntando a `LOGIN(id_usuario)`. | **Se adopta `USUARIO`** del general como la tabla canónica. Todas las FKs de auditoría (`id_usuario`) apuntarán a `USUARIO(id_usuario)`. |
| **`CATEGORIA` y `MARCA`** | Solo `id` y `nombre`. | `id`, `nombre`, `fecha_creacion`, `fecha_modificacion`. | **Se adopta la versión enriquecida de G3** (con fechas de auditoría requeridas por HU P02 y P03). |
| **`PRODUCTO`** | `precioMay`, `precioMin`, `stock`, `stock_minimo`, `imagen`, `estado`. | `precio_unitario`, `estado`, fechas y `id_usuario`. Stock estaba en tabla separada. | **Fusión definitiva:** Se incorporan `precio_mayorista` y `precio_minorista`, se absorbe `stock_disponible`, `stock_minimo` y `dias_alerta_vencimiento` (respondiendo a la Obs. 3 del docente) y se mantiene `codigo` UNIQUE y fechas de auditoría. |
| **`STOCK` (Tabla 1:1)** | No existe (está en PRODUCTO). | Tabla separada 1:1. | **Se elimina la tabla `STOCK`** y sus columnas se fusionan en `PRODUCTO`, satisfaciendo la devolución del profesor. |
| **`HISTORIAL_PRECIO`** | No existía en el general. | Tenía `precio_anterior` y `precio_nuevo`. | **Se incorpora al esquema general normalizada:** Un único campo `precio` (o precio mayorista/minorista), fecha, motivo, id_usuario. Responde a la Obs. 1 del docente. |
| **`MOVIMIENTO_STOCK`** | No existía en el general. | Tenía `id_venta`, `id_entrega` sueltos. | **Se incorpora al esquema general:** Se enlaza con FK real a `VENTA(id_venta)` y `ENTREGA(id_entrega)` del modelo general, y se defiende ante el docente (Obs. 2) por mermas, roturas y devoluciones. |
| **`LOTE`** | `id_lote, id_producto, nro_lote, cantidad, fecha_vencimiento, estado`. | Similar, pero enlazado a `MOVIMIENTO_STOCK` y `UNIDAD_MEDIDA`. | **Se adopta la versión completa de G3**, manteniendo la FK a `PRODUCTO`, `MOVIMIENTO_STOCK` y `UNIDAD_MEDIDA`. |
| **`UNIDAD_MEDIDA`** | `id_unidad, id_producto, nombre_unidad, equivalencia_base, descripcion`. | Idéntica. | **Se mantiene la tabla** vinculada a `PRODUCTO`. |
| **`UBICACION` y `SESION_CONTEO`**| No existían en el general. | Tablas de almacén físico (estanterías, pasillos, inventario físico). | **Se incorporan al esquema unificado** para respaldar las HUs de almacén (S13, S14). |
| **`PROVEEDOR` y `PRODUCTO_PROVEEDOR`**| Campos básicos en el general. | Versión completa con estados, plazos de entrega y fechas. | **Se adopta la versión completa de G3**. |
| **`ORDEN_COMPRA`** | No existía en el general. | `ORDEN_COMPRA` y `DETALLE_ORDEN`. | **Se incorporan al esquema general** (indispensables para reposición formal HU PC01-PC07). |
| **`COMPRA` y `DETALLE_COMPRA`**| Esquema muy básico sin enlace a orden ni saldo. | Enlazada a `ORDEN_COMPRA`, con `saldo_pendiente` y estados de recepción. | **Se adopta la versión completa de G3**, que además enlaza con `USUARIO`. |
| **`RECEPCION` y `PAGO_PROVEEDOR`**| No existían en el general. | Tablas para entregas parciales con lotes y pagos a proveedores. | **Se incorporan al esquema general**, completando el ciclo de compras de G3. |
| **`LOG_AUDITORIA`** | No existía. | No existía (auditoría dispersa). | **Se crea la tabla centralizada** de auditoría en respuesta a la Obs. 4 del docente. |

---

## 2. ESTRUCTURA COMPLETA DE TABLAS DEL SISTEMA FUSIONADO

### Módulos Externos Integrados (Grupos 1, 2 y 4):
1. `ZONA` (Grupo 1/2)
2. `CLIENTE` (Grupo 1)
3. `USUARIO` (Grupo 1 - Seguridad y Autenticación)
4. `VENTA` (Grupo 4)
5. `DETALLE_VENTA` (Grupo 4)
6. `PROMOCION` (Grupo 4)
7. `PROMOCION_PRODUCTO` (Grupo 4)
8. `ENTREGA` (Grupo 2 - Logística)
9. `ENTREGA_PEDIDO` (Grupo 2)

### Módulos del Grupo 3 (Catálogo, Stock, Proveedores, Compras):
10. `CATEGORIA`
11. `MARCA`
12. `PRODUCTO` (Con stock integrado y precios mayorista/minorista)
13. `HISTORIAL_PRECIO` (Normalizado a precio resultante + tipo)
14. `UNIDAD_MEDIDA`
15. `MOVIMIENTO_STOCK` (Con FKs reales a VENTA y ENTREGA, más soporte de mermas/devoluciones)
16. `LOTE`
17. `UBICACION`
18. `PRODUCTO_UBICACION`
19. `SESION_CONTEO`
20. `DETALLE_CONTEO`
21. `PROVEEDOR`
22. `PRODUCTO_PROVEEDOR`
23. `ORDEN_COMPRA`
24. `DETALLE_ORDEN`
25. `COMPRA`
26. `DETALLE_COMPRA`
27. `RECEPCION`
28. `DETALLE_RECEPCION`
29. `PAGO_PROVEEDOR`
30. `HISTORIAL_PRECIO_PROVEEDOR`
31. `LOG_AUDITORIA` (Tabla centralizada transversal)

---

## 3. PASOS DE EJECUCIÓN PROPUESTOS

1. **Generar el Script SQL Unificado:** Crear `BD/distribuidora_general_fusionada.sql` con sintaxis estándar MySQL 8 / InnoDB, tipos de datos compatibles (`INT`, `VARCHAR`, `DECIMAL(12,2)`, `DATE`, `DATETIME`), restricciones `CHECK` y todas las `FOREIGN KEY` debidamente referenciadas.
2. **Documentar la Defensa Técnica para el Docente:** Explicar cómo el nuevo script fusionado resuelve formalmente sus 4 observaciones manteniendo la coherencia de todo el ERP.
3. **Presentar al usuario para aprobación.**
