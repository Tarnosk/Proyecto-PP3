# Estado del Proyecto y Cumplimiento de Historias de Usuario — ERP Juan23 (Distribuidora PyB)

**Fecha de actualización:** 26/10/2026  
**Alcance:** Módulos del Grupo 3 (Productos, Stock, Proveedores, Compras, Pedidos de Compra) e Integración General  
**Referencia:** Criterios de Aceptación de Historias de Usuario vs. Implementación en Backend (`app/Modules`) y Frontend Blade (`resources/views`)

---

## 1. Resumen Ejecutivo de Cumplimiento

| Módulo | Historias Cumplidas | Criterios de Aceptación | Tests Automatizados | Estado General |
| :--- | :---: | :---: | :---: | :---: |
| **Productos (P01 – P08)** | 8 / 8 (100%) | 100% Verificados | `UserStoriesFrontendTest` | **Completado (100%)** |
| **Pedidos de Compra (PC01 – PC07)** | 7 / 7 (100%) | 100% Verificados | `reporte-pruebas-pc01..07` | **Completado (100%)** |
| **Compras (C01 – C09)** | 9 / 9 (100%) | 100% Verificados | `UserStoriesFrontendTest` | **Completado (100%)** |
| **Stock e Inventario (S01 – S16)** | 16 / 16 (100%) | 100% Verificados | `ConteoFisicoTest`, `UserStoriesFrontendTest` | **Completado (100%)** |
| **Proveedores (PV01 – PV08)** | 8 / 8 (100%) | 100% Verificados | `HistorialPrecioProveedorTest` | **Completado (100%)** |

> **Nota de Infraestructura:** La base de datos unificada (`distribuidorapyb` / `sistema_gestion`) cuenta con sus 48 tablas activas, claves foráneas alineadas con `usuarios` y suite canónica de pruebas 100% operativa.

---

## 2. Detalle de Historias de Usuario Finalizadas

### Módulo Productos (P01 – P08)
- **P01:** Alta de producto con validaciones de código único, descripción, categoría, marca y precios.
- **P02:** Edición de producto y trazabilidad de modificaciones con auditoría de usuario.
- **P03:** Desactivación lógica de productos con restricción si existen operaciones en curso.
- **P04:** Reactivación de productos inactivos.
- **P05:** Búsqueda flexible por código, nombre y filtros combinados (categoría, marca, estado).
- **P06:** Consulta detallada con stock disponible, stock mínimo y ubicaciones asociadas.
- **P07:** Aumentos masivos de precios por categoría/marca con reglas de redondeo configurables (`sin_redondeo`, `redondeo`, `ceil`, `floor`).
- **P08:** Registro y consulta de historial de cambios de precios (precio anterior, nuevo, fecha y usuario).

### Módulo Pedidos de Compra (PC01 – PC07)
- **PC01:** Creación de órdenes de compra con cálculo automático de subtotales, IVA y total.
- **PC02:** Listado con filtros por proveedor, estado, rango de fechas y paginación.
- **PC03:** Visualización del detalle con ítems, cantidades solicitadas, precios pactados y datos del proveedor.
- **PC04:** Edición de órdenes en estado `pendiente`.
- **PC05:** Cancelación de órdenes con motivo obligatorio y liberación de compromisos.
- **PC06:** Seguimiento de estados mediante línea de tiempo interactiva (`pendiente` -> `enviada` -> `recibida_parcialmente` -> `completada` / `cancelada`).
- **PC07:** Generación automática de sugerencias de reposición basadas en stock mínimo y punto de pedido.

### Módulo Compras (C01 – C09)
- **C01:** Registro de compras directas y vinculadas a órdenes de compra.
- **C02:** Recepción de mercadería con incremento inmediato en Kardex de stock.
- **C03:** Recepción parcial de mercadería con control de pendientes.
- **C04:** Consulta de comprobantes de compra (Facturas A, B, C con numeración estricta de 4 a 8 dígitos).
- **C05:** Impresión de comprobante comercial en formato estándar A4 en escala de grises.
- **C06:** Vinculación de recepción con asignación de lote y fecha de vencimiento.
- **C07:** Registro de pagos a proveedores (`efectivo`, `transferencia`, `cheque`, `e-cheq`).
- **C08:** Gestión de pagos diferidos con número de cheque/e-cheq y fecha de cobro.
- **C09:** Visualización y control de cuentas corrientes y deudas con proveedores.

### Módulo Stock e Inventario (S01 – S16)
- **S01:** Visualización del inventario general con estados de alerta visual.
- **S02:** Consulta de disponibilidad en tiempo real por producto.
- **S03:** Ingreso manual de mercadería con motivo y auditoría.
- **S04:** Registro de salidas por venta con descuento automático de stock.
- **S05:** Devoluciones de clientes (incremento) y devoluciones a proveedores (descuento) con asignación de lote obligatoria.
- **S06:** Ajustes manuales de inventario por rotura, merma o sobrante.
- **S07:** Alertas automáticas de stock bajo y punto de reposición.
- **S08:** Soporte de unidades de medida y cálculo automático de equivalencias base.
- **S09:** Gestión completa de lotes por producto (alta, cantidad inicial, cantidad actual).
- **S10:** Control y semáforo de fechas de vencimiento (vencidos, por vencer, en regla).
- **S11:** Consulta analítica y reportes de criticidad de vencimientos.
- **S12:** Kardex histórico de movimientos con trazabilidad completa (usuario, fecha, tipo, lote).
- **S13:** **Conteo Físico a Ciegas:** Registro de toma de inventario, detección de discrepancias y ajuste automático compensatorio (`ConteoFisicoController` + `ConteoFisicoService`).
- **S14:** **Gestión de Ubicaciones en Almacén:** Registro de pasillos, estanterías, niveles y asignación a productos (`UbicacionController`).
- **S15:** Filtros avanzados por rangos de stock (`stock_min`, `stock_max`) en backend y frontend.
- **S16:** Formato de venta por bulto cerrado y unidades por bulto opcional.

### Módulo Proveedores (PV01 – PV08)
- **PV01:** Alta de proveedores con validación estricta de CUIT argentino (algoritmo Módulo 11) y razón social.
- **PV02:** Edición de datos fiscales, comerciales y de contacto del proveedor.
- **PV03:** Desactivación lógica de proveedores sin pérdida de histórico.
- **PV04:** Reactivación de proveedores inactivos.
- **PV05:** Asociación de catálogo de productos por proveedor con código propio y precio pactado.
- **PV06:** **Historial de Precios de Compra:** Registro automático por compra o ajuste manual y comparativa de evolución de aumentos (`HistorialPrecioController`).
- **PV07:** Configuración de plazos de entrega estimados en días por proveedor.
- **PV08:** Registro y métricas de cumplimiento de plazos de entrega en recepciones.

---

## 3. Estado de la Integración con Otros Grupos

1. **Grupo 1 (Autenticación, Clientes y Reportes):**
   - Integrado el middleware de resolución de identidad (`IdentidadUsuario`) con soporte para tokens Bearer, sesiones de base de datos y headers `X-User-Id`.
   - Vistas Blade y componentes de navegación unificados en `resources/views/partials/navigation-controls.blade.php`.
   - Compatibilidad completa en rutas versionadas `/api/v1/` y fallback `/api/`.

2. **Grupo 2 (Caja, Finanzas y Rendiciones):**
   - Vistas y controladores de Caja, Rendiciones y Conciliación Bancaria incorporados bajo `app/Modules/` y `resources/views/`.
   - Mapeo de contratos y gateways en `app/Services/Gateways/`.

3. **Grupo 4 (Ventas, Facturación y Reparto):**
   - Módulos de Ventas, Promociones y Reparto sincronizados.
   - Esquemas de bases de datos y semillas adaptadas para convivencia de catálogos compartidos.

---

## 4. Tareas de Mantenimiento y Evolución Continua
- Mantener ejecutadas las suites de regresión periódica (`php artisan test`).
- Monitorear logs de auditoría en `log_auditoria` para control de cambios de precios y stock.
- Revisar periódicamente las tablas de sesión para depuración de tokens caducados.
