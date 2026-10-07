# Control de Versiones y Evolución del Proyecto ERP Distribuidora (Grupo 3)

**Proyecto:** ERP Distribuidora - Grupo 3 (Productos, Stock, Proveedores, Compras, Pedidos de Compra)  
**Cátedra:** Práctica Profesional Supervisada / PP3  
**Repositorio Oficial:** [https://github.com/Tarnosk/Proyecto-PP3](https://github.com/Tarnosk/Proyecto-PP3)  
**Fecha de Emisión:** 6 de Octubre de 2026  
**Estándar de Versionado:** Versionado Semántico (SemVer - *MAJOR.MINOR.PATCH*) adaptado a Sprints y Entregas Académicas.

---

## 1. Resumen Ejecutivo de la Evolución del Proyecto

A lo largo del ciclo de vida del proyecto, el desarrollo del Grupo 3 evolucionó de forma iterativa e incremental a través de cuatro etapas principales (Sprints 1, 2, 3 y la fase de integración y estabilización actual). Cada iteración amplió las capacidades funcionales del sistema, refinó la arquitectura técnica e incrementó el nivel de cumplimiento normativo y de calidad:

```
[Sprint 1: v0.1.0-alpha] ──► [Sprint 2: v0.2.0-beta] ──► [Fusión BD: v0.2.5] ──► [Sprint 3: v0.3.0-rc1] ──► [Sprint 4: v0.4.0]
  Catálogo Base y MVP          Stock Operativo y            Armonización con G1/G2/G4     Lotes, Caducidades,         Timeline Órdenes PC06,
  Productos (PHP 8.5)          Proveedores (ACID)           y Auditoría de Identidad      Kardex e Informes V&V       Brechas y Estabilización
```

---

## 2. Tabla Maestra de Versiones e Hitos (Changelog Global)

| Versión | Sprint / Hito | Período / Fecha | Hito Principal y Alcance Técnico | Commits Relevantes | Estado de Entrega |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **`v0.1.0-alpha`** | **Sprint 1** | 18/08/2026 – 31/08/2026 | **MVP de Catálogo y Productos:** Creación de BD inicial (23 tablas), arquitectura backend PHP 8.5 (API REST), CRUD completo de Categorías, Marcas, Productos, Búsqueda con debounce, Aumentos Masivos e Historial de Precios. Conexión de `productos.html`. | `b551508`<br>`13b75c9`<br>`baa9385` | **Aprobado / Done** (28 SP / 16.3h) |
| **`v0.2.0-beta`** | **Sprint 2** | 01/09/2026 – 19/09/2026 | **Núcleo de Stock, Proveedores y Reglas Transaccionales:** Movimientos atómicos de stock (Ingresos S03, Ventas con bloqueo negativo S04, Devoluciones S05, Ajustes con justificación S06), Alertas de Stock Mínimo (S07), Unidades y Equivalencias (S08), CRUD de Proveedores (CUIT único y baja lógica PV01-PV04) y relación N:M Producto-Proveedor (PV05). | `13b75c9` | **Aprobado / Done** (56 SP / 22.6h) |
| **`v0.2.5`** | **Hito de Fusión Inter-Grupal** | 10/09/2026 – 13/09/2026 | **Re-arquitectura Canónica de Base de Datos y Auditoría:** Fusión de esquema con G1, G2 y G4 (`distribuidorapyb`), absorción de tabla `STOCK` en `PRODUCTO`, normalización de `HISTORIAL_PRECIO`, FKs reales en `MOVIMIENTO_STOCK`, y middleware de Identidad Nivel A (`CONTRATO_G1_AUDITORIA.md`). Suite canónica con 31/31 pruebas automáticas pasando. | `8b6efe6` | **Aprobado / Validado** (Suite 31/31 OK) |
| **`v0.3.0-rc1`** | **Sprint 3** | 20/09/2026 – 05/10/2026 | **Trazabilidad de Lotes, Vencimientos, Kardex y Calidad:** Entidad `LOTE`, registro de vencimientos (S09), consultas y alertas de caducidad con vistas dedicadas Blade/JS (`/vencimientos`, S10-S11), Historial inmutable Kardex (S12), Plazos de entrega de proveedores (PV07), filtro dinámico por nivel de stock (S15), módulos de sugerencia y reposición automática. Documentación formal de V&V y Accesibilidad WCAG 2.1. | `cc48c37`<br>`66ecbe6`<br>`0a815df` | **Aprobado / Done** (40 SP / 16.5h) |
| **`v0.4.0`** | **Sprint 4 (En Curso)** | 06/10/2026 | **Timeline de Compras PC06, Resolución de Brechas y Estabilización:** Implementación completa del ciclo y máquina de estados de órdenes de compra con timeline interactivo (PC06). Resolución de brechas de búsqueda por `nombre`, filtro `activo` por defecto, saneamiento de base de datos canónica y matriz de pendientes. | `aa4857f` | **En Progreso / Estable** |

---

## 3. Detalle de la Evolución por Sprint y Versión

### 3.1 Versión 0.1.0-alpha — Sprint 1 (18 de Agosto al 31 de Agosto de 2026)
* **Objetivo:** Establecer la base de datos de trabajo, el andamiaje del backend bajo PHP 8.5 y resolver el MVP completo del módulo de Productos y Catálogo.
* **Esfuerzo del Sprint:** 9 sesiones registradas (G3-001 a G3-009), 28 Story Points acumulados, 16.33 horas de trabajo real.
* **Modalidad:** Sesiones grupales (6 integrantes) para la arquitectura base y subdivisión en parejas para requerimientos puntuales.

#### Componentes de Software Creados
* **Base de Datos Inicial:** Script `BD/grupo3_create_database.sql` con 23 tablas, restricciones de unicidad (`UNIQUE`), comprobaciones (`CHECK`) e integridad referencial en `distribuidora_grupo3`.
* **Backend:** Arquitectura orientada a objetos en PHP 8.5 / Laravel bajo el patrón REST JSON (`ProductoController`, `CategoriaController`, `MarcaController`).
* **Frontend:** Prototipo conectado `Interfaz/productos.html` con consumo dinámico vía `fetch()`.

#### Historias de Usuario e Issues Resueltas
1. **#35 (P02) Administrar Categorías (3 SP - 1h 00m):** Creación y modificación con auditoría temporal (`fecha_creacion`, `fecha_modificacion`), unicidad de nombre y bloqueo de borrado si contiene productos asociados.
2. **#36 (P03) Administrar Marcas (3 SP - 1h 00m):** Creación y modificación con auditoría, unicidad de nombre y borrado condicional protegido.
3. **#34 (P01) Registrar Productos (8 SP - 2h 25m):** Alta de productos con validación estricta de código único, existencia de categoría/marca activa, registro del usuario creador e inicialización atómica de stock y precio base.
4. **#38 (P05) Buscar Productos (3 SP - 0h 50m):** Búsqueda en tiempo real con debounce por código/descripción y filtros cruzados por categoría y marca.
5. **#37 (P04) Modificar Productos (5 SP - 2h 00m):** Actualización de datos comerciales con auditoría inmutable de precios (`HISTORIAL_PRECIO`) si varía el valor unitario.
6. **#39 (P06) Desactivar Productos (1 SP - 0h 30m):** Borrado lógico preservando la integridad contable y marcando `estado = inactivo` y `fecha_baja`.
7. **#79 (S01) Consulta de Stock Disponible (3 SP - 1h 00m):** Endpoint de lectura de existencias en unidad base sin caché para garantizar datos en tiempo real.
8. **#40 (P07) Aplicar Aumentos Masivos (5 SP - 2h 40m):** Aplicación de porcentajes sobre categorías completas con vista previa y reglas de redondeo.
9. **#41 (P08) Consultar Historial de Precios (5 SP - 2h 55m):** Trazabilidad temporal inmutable de variaciones de precios por producto.

---

### 3.2 Versión 0.2.0-beta — Sprint 2 (1 de Septiembre al 19 de Septiembre de 2026)
* **Objetivo:** Desarrollar el motor transaccional de movimientos de stock en tiempo real y el ciclo completo de administración de proveedores.
* **Esfuerzo del Sprint:** 12 sesiones registradas (G3-010 a G3-021), 56 Story Points, 22.58 horas de desarrollo real.
* **Modalidad:** Trabajo en parejas (Darío/Franco en Stock, Tomás/Gino en Proveedores, Santiago/Francisco en Equivalencias y Alertas).

#### Componentes de Software Creados
* **Servicio Transaccional de Stock:** `StockService.php` coordinando movimientos ACID con bloqueo pesimista y actualización de existencias.
* **Controladores Especializados:** `IngresoMercaderiaController`, `VentaStockController`, `DevolucionController`, `AjusteStockController`, `AlertaStockController`, `UnidadController`, `ProveedorController`.
* **Frontend:** Vistas interactivas `stock.html` y `proveedores.html` con modales de ingreso, ajuste, detalle y unidades.

#### Historias de Usuario e Issues Resueltas
1. **#81 (S02) Disponibilidad para Ventas (3 SP - 2h 00m):** Endpoint para consulta de stock libre de compromisos por parte de vendedores.
2. **#64 (PV01) Registrar Proveedores (5 SP - 2h 10m):** Alta de proveedores con validación algorítmica de CUIT único, razón social y datos fiscales.
3. **#82 (S03) Registro de Ingreso de Mercadería (5 SP - 2h 25m):** Entrada de mercadería con validación de proveedor activo y bloqueo de fechas futuras.
4. **#83 (S04) Actualización Automática por Venta (5 SP - 0h 25m):** Descuento transaccional inmediato con restricción de integridad `CHECK chk_producto_stock_no_negativo` (bloqueo de stock negativo).
5. **#84 (S05) Registro de Devoluciones (5 SP - 1h 55m):** Reingreso justificado de mercadería al inventario con generación de comprobante y movimiento.
6. **#85 (S06) Ajustes Manuales de Inventario (5 SP - 2h 00m):** Correcciones por roturas, mermas o sobrantes exigiendo motivo obligatorio y auditoría de usuario.
7. **#86 (S07) Alertas de Stock Mínimo (5 SP - 1h 30m):** Configuración de umbral `stock_minimo` por producto con clasificación dinámica (crítico, bajo, normal).
8. **#87 (S08) Gestión de Unidades y Equivalencias (8 SP - 3h 15m):** Definición de bultos (cajas, packs) y factor de conversión automático hacia la unidad base indivisible.
9. **#65 (PV02) Consultar Información de Proveedores (1 SP - 0h 40m):** Búsqueda y visualización de ficha técnica de proveedores.
10. **#66 (PV03) Editar Información de Proveedores (3 SP - 1h 45m):** Modificación de contactos y condiciones comerciales con preservación de CUIT.
11. **#67 (PV04) Desactivar Proveedores (1 SP - 0h 35m):** Baja lógica (`estado = inactivo`) que inhabilita compras futuras pero preserva comprobantes históricos.
12. **#76 (PV05) Asociar Productos a Proveedores (5 SP - 2h 00m):** Desarrollo de la tabla intermedia `PRODUCTO_PROVEEDOR`, designación de proveedor principal y precios pactados.

---

### 3.3 Versión 0.2.5 — Hito de Fusión Canónica y Auditoría Inter-Grupal (10 al 13 de Septiembre de 2026)
* **Objetivo:** Atender las devoluciones del docente (Observaciones OB1 a OB4), unificar la base de datos canónica `distribuidorapyb` con los Grupos 1, 2 y 4, y blindar la auditoría de seguridad.
* **Archivos Clave:** `Plan_Fusion_Base_Datos_General.md`, `CONTRATO_G1_AUDITORIA.md`, `BD/reporte_criterios_aceptacion.md`, `BD/DistribuidoraPyB_MVP.sql`.

#### Resoluciones de Arquitectura y Base de Datos
* **OB1 (Historial de Precios Normalizado):** Se reemplazó el esquema con dos columnas (`precio_anterior` y `precio_nuevo`) por un modelo normalizado de una fila por cambio de precio (`tipo_precio`, `precio`, `fecha_desde`, `id_usuario`).
* **OB2 (Log Centralizado de Auditoría):** Creación de la tabla `LOG_AUDITORIA` para centralizar trazas de operaciones críticas.
* **OB3 (Eliminación de la tabla redundante STOCK):** Se absorbió la relación 1:1 eliminando la tabla `STOCK` y embebiendo la columna `stock` directamente en `PRODUCTO`.
* **OB4 (Kardex Centralizado):** Consolidación de `MOVIMIENTO_STOCK` con claves foráneas reales hacia `VENTA` (Grupo 4) y `ENTREGA` (Grupo 2).
* **Auditoría de Identidad Nivel A:** Creación del middleware `IdentidadUsuario.php` y el helper `UsuarioActual.php`. El backend del Grupo 3 dejó de confiar en el `id_usuario` recibido en el cuerpo JSON, resolviéndolo exclusivamente desde la sesión/guard autenticado.
* **Validación Canónica:** Ejecución de la suite `DistribuidoraPyB_MVP.sql` dentro de una transacción con `ROLLBACK`, certificando el cumplimiento de **31/31 historias de usuario con código de salida 0**.

---

### 3.4 Versión 0.3.0-rc1 — Sprint 3 (20 de Septiembre al 5 de Octubre de 2026)
* **Objetivo:** Implementar trazabilidad avanzada por lote y fecha de vencimiento, consolidar el Kardex histórico, habilitar módulos de reposición y documentar los planes de calidad y accesibilidad.
* **Esfuerzo del Sprint:** 10 sesiones registradas (G3-022 a G3-030 + Daily de Calidad), 40 Story Points, 16.50 horas de trabajo real.
* **Modalidad:** Parejas de desarrollo + sesión grupal transversal de aseguramiento de calidad (27/09/2026).

#### Componentes de Software Creados
* **Trazabilidad de Caducidad:** Entidad `LOTE`, `LoteController`, y repositorio especializado `LoteVencimientoRepository`.
* **Módulo de Reposición y Sugerencias:** `DashboardReposicionController`, `SugerenciasReposicionController`, tabla y modelo `SUGERENCIA_COMPRA`, servicio `GeneradorSugerenciasReposicionService` y transacciones con bloqueo pesimista `SELECT ... FOR UPDATE`.
* **Vistas de Producción:** Interfaz dedicada de vencimientos (`/vencimientos` en Blade + `public/js/vencimientos.js`) con diseño visual consistente (`claymorfismo.css`).
* **Documentación de Calidad:**
  * `Documentacion/plan de prueba de validacion y verificacion.md`: Definición de estrategia V&V, pruebas de caja blanca (rutas lógicas y conversiones) y caja negra (validaciones de formularios), pruebas alfa internas y pruebas beta con usuarios de la distribuidora.
  * `Documentacion/criterios de accesibilidad y usabilidad.md`: Criterios WCAG 2.1 (contraste 4.5:1, navegación por teclado, focus ring visible, screen readers) e independencia del color.

#### Historias de Usuario e Issues Resueltas
1. **#75 (S09) Gestión de Lotes y Vencimientos (8 SP - 2h 15m):** Asociación de número de lote y caducidad por cada partida recibida.
2. **#78 (PV07) Plazos de Entrega de Proveedores (3 SP - 1h 40m):** Creación de `HISTORIAL_PLAZO_PROVEEDOR` y cálculo del plazo promedio real de entrega.
3. **#73 (S11) Consultar Productos Próximos a Vencer (3 SP - 1h 30m):** Pantalla paginada con semáforo de criticidad y ordenamiento seguro por fecha.
4. **#74 (S10) Alerta de Productos Próximos a Vencer (5 SP - 1h 45m):** Lógica de categorización automática entre lotes vigentes, próximos a vencer y vencidos.
5. **#72 (S12) Historial de Movimientos de Stock / Kardex (8 SP - 2h 50m):** Trazabilidad completa inmutable de cada transacción física sobre el catálogo.
6. **#69 (S15) Filtrado por Nivel de Stock (3 SP - 1h 10m):** Ampliación del motor de consultas de stock con filtros rápidos por estado de alerta.
7. **#71 (S13) Conteo Físico de Inventario (8 SP - 1h 30m):** Análisis de requerimientos y diseño del modelo de datos para sesiones de recuento físico (`SESION_CONTEO`, `DETALLE_CONTEO`).
8. **#70 (S14) Gestión de Ubicaciones de Almacenamiento (5 SP - 1h 00m):** Modelado relacional de pasillos y estantes para optimización del depósito (`UBICACION`, `PRODUCTO_UBICACION`).
9. **Daily Grupal de Calidad (1h 45m):** Elaboración y validación de los manuales de prueba V&V y directrices de accesibilidad.

---

### 3.5 Versión 0.4.0 — Sprint 4 y Cierre de Brechas (6 de Octubre de 2026)
* **Objetivo:** Resolver el flujo formal de compras con timeline interactivo (PC06), corregir brechas detectadas en la auditoría del código (`consideraciones.txt`) y sincronizar el repositorio para la presentación grupal.
* **Commits Relevantes:** `aa4857f` en rama `main`.

#### Mejoras e Implementaciones Incorporadas
1. **Implementación de PC06 (Timeline de Estados de Órdenes de Compra):**
   * Definición de la máquina de estados: `borrador` ➔ `enviada` ➔ `confirmada` ➔ `en_transito` ➔ `recibida_parcial` ➔ `recibida_completa` (con estado terminal `cancelada`).
   * Validación backend de transiciones permitidas impidiendo saltos de estado inválidos.
   * Componente visual de stepper/timeline interactivo en `backend/public/Interfaz/pedido_compra.html` con retroalimentación inmediata.
2. **Resolución de Brechas de Búsqueda y Filtrado:**
   * Inclusión explícita de la columna `nombre` en las consultas de `Producto` y `StockController` (`scopeSearch`).
   * Aplicación del filtro por defecto `estado = activo` al consultar el catálogo.
3. **Consolidación Documental y Soporte de Exposición:**
   * `Documentacion/PENDIENTES_PROYECTO.md`: Hoja de ruta para el Sprint 4 con estimación de esfuerzo y prioridades.
   * `Documentacion/PRESENTACION_SPRINT_2_Y_3.md`: Guion de exposición individual y demo técnica para los 6 integrantes.
   * `Documentacion/CONTROL_DE_VERSIONES_EVOLUCION_SPRINTS.md`: Este documento de trazabilidad histórica.

---

## 4. Evolución de la Base de Datos a través de los Sprints

El esquema de base de datos atravesó tres grandes transformaciones a lo largo del proyecto:

| Aspecto | Versión Inicial (Sprint 1) | Versión Fusionada (Sprint 2 / Fusión) | Versión Definitiva (Sprint 3 y 4) |
| :--- | :--- | :--- | :--- |
| **Nombre de la BD** | `distribuidora_grupo3` | `distribuidorapyb` | `distribuidorapyb` |
| **Total de Tablas** | 23 tablas | 34 tablas | 43 tablas (con migraciones) |
| **Manejo de Stock** | Tabla `STOCK` separada 1:1 con `PRODUCTO` | Embebido directamente en `PRODUCTO` | Embebido en `PRODUCTO` con restricción CHECK |
| **Historial de Precios** | 2 columnas (`precio_anterior`, `precio_nuevo`) | Normalizado a 1 valor por fila | Normalizado por tipo de precio (mayorista/minorista) |
| **Trazabilidad Lotes** | Inexistente en capa funcional | Tabla `LOTE` diseñada en el esquema | Implementado con vencimientos y alertas (`LOTE`) |
| **Auditoría de Usuario** | Comentarios hacia tabla externa | `id_usuario` tipado con FK a `USUARIO` | Middleware Nivel A + `LOG_AUDITORIA` |
| **Plazos y Reposición** | No contemplados | Columna en proveedor | `HISTORIAL_PLAZO_PROVEEDOR` y `SUGERENCIA_COMPRA` |

---

## 5. Resumen de Métricas de Desarrollo por Sprint

Los registros extraídos del archivo canónico `Organizacion Grupo 3 (6 de octubre).xlsx` arrojan los siguientes totales consolidados de esfuerzo:

```
┌──────────────┬───────────────┬────────────────┬────────────────┬───────────────────────────┐
│ Sprint       │ Cant. Issues  │ Story Points   │ Horas Reales   │ Desviación Plan vs Real   │
├──────────────┼───────────────┼────────────────┼────────────────┼───────────────────────────┤
│ Sprint 1     │ 9 issues      │ 28 SP          │ 16.33 h        │ +0.83 h (Dentro del rango)│
│ Sprint 2     │ 12 issues     │ 56 SP          │ 22.58 h        │ +0.98 h (Óptimo)          │
│ Sprint 3     │ 10 sesiones   │ 40 SP          │ 16.50 h        │ +0.25 h (Muy preciso)     │
│ Sprint 4 / C │ 1 issue + doc │ 8 SP           │ ~3.00 h        │ En curso                  │
├──────────────┼───────────────┼────────────────┼────────────────┼───────────────────────────┤
│ TOTAL        │ 32 registros  │ 132 SP         │ 58.41 h        │ 100% Trazable en Git      │
└──────────────┴───────────────┴────────────────┴────────────────┴───────────────────────────┘
```

---

## 6. Matriz de Trazabilidad: Versión – Sprint – Issue – Responsables

| Versión | Sprint | ID Issue | Título de la Historia / Tarea | Sub-Grupo / Responsables | Archivo / Artefacto Principal |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `v0.1.0` | Sprint 1 | #35 (P02) | Administrar Categorías | Equipo Completo (6 integrantes) | `CategoriaController.php` |
| `v0.1.0` | Sprint 1 | #36 (P03) | Administrar Marcas | Equipo Completo (6 integrantes) | `MarcaController.php` |
| `v0.1.0` | Sprint 1 | #34 (P01) | Registrar Productos | Equipo Completo (6 integrantes) | `ProductoController.php` |
| `v0.1.0` | Sprint 1 | #38 (P05) | Buscar Productos | Equipo Completo (6 integrantes) | `productos.html` / `Producto.php` |
| `v0.1.0` | Sprint 1 | #37 (P04) | Modificar Productos | Equipo Completo (6 integrantes) | `ProductoController.php` |
| `v0.1.0` | Sprint 1 | #39 (P06) | Desactivar Productos | Equipo Completo (6 integrantes) | `Producto.php` |
| `v0.1.0` | Sprint 1 | #79 (S01) | Consulta de Stock Disponible | Tomás Giraudo / Gino Martellini | `StockController.php` |
| `v0.1.0` | Sprint 1 | #40 (P07) | Aplicar Aumentos Masivos | Darío Tarnoski / Franco Ortelli | `ProductoController.php` |
| `v0.1.0` | Sprint 1 | #41 (P08) | Consultar Historial de Precios | Santiago Gomis / Francisco Calvo | `HistorialPrecio.php` |
| `v0.2.0` | Sprint 2 | #81 (S02) | Consulta de Disponibilidad Ventas | Darío Tarnoski / Franco Ortelli | `StockController.php` |
| `v0.2.0` | Sprint 2 | #64 (PV01) | Registrar Proveedores | Tomás Giraudo / Gino Martellini | `ProveedorController.php` |
| `v0.2.0` | Sprint 2 | #82 (S03) | Registro de Ingreso de Mercadería | Darío Tarnoski / Franco Ortelli | `IngresoMercaderiaController.php`|
| `v0.2.0` | Sprint 2 | #83 (S04) | Actualización Automática por Venta| Darío Tarnoski / Franco Ortelli | `VentaStockController.php` |
| `v0.2.0` | Sprint 2 | #84 (S05) | Registro de Devoluciones | Santiago Gomis / Francisco Calvo | `DevolucionController.php` |
| `v0.2.0` | Sprint 2 | #85 (S06) | Ajustes Manuales de Inventario | Darío Tarnoski / Franco Ortelli | `AjusteStockController.php` |
| `v0.2.0` | Sprint 2 | #86 (S07) | Alertas de Stock Mínimo | Santiago Gomis / Francisco Calvo | `AlertaStockController.php` |
| `v0.2.0` | Sprint 2 | #87 (S08) | Gestión de Unidades y Equivalencias| Santiago Gomis / Francisco Calvo| `UnidadController.php` |
| `v0.2.0` | Sprint 2 | #65 (PV02) | Consultar Información Proveedores| Tomás Giraudo / Gino Martellini | `proveedores.html` |
| `v0.2.0` | Sprint 2 | #66 (PV03) | Editar Información Proveedores | Tomás Giraudo / Gino Martellini | `ProveedorController.php` |
| `v0.2.0` | Sprint 2 | #67 (PV04) | Desactivar Proveedores | Tomás Giraudo / Gino Martellini | `Proveedor.php` |
| `v0.2.0` | Sprint 2 | #76 (PV05) | Asociar Productos a Proveedores | Tomás Giraudo / Gino Martellini | `PRODUCTO_PROVEEDOR` |
| `v0.2.5` | Fusión BD | - | Re-diseño de BD y Auditoría Nivel A| Equipo Completo (6 integrantes) | `CONTRATO_G1_AUDITORIA.md` |
| `v0.3.0` | Sprint 3 | #75 (S09) | Gestión de Lotes y Vencimientos | Tomás Giraudo / Gino Martellini | `LoteController.php` / `LOTE` |
| `v0.3.0` | Sprint 3 | #78 (PV07) | Registrar Plazos de Entrega | Darío Tarnoski / Franco Ortelli | `PlazoEntregaController.php` |
| `v0.3.0` | Sprint 3 | #73 (S11) | Consultar Productos Próx. a Vencer| Santiago Gomis / Francisco Calvo| `/vencimientos` |
| `v0.3.0` | Sprint 3 | #74 (S10) | Alerta de Productos Próx. a Vencer| Tomás Giraudo / Gino Martellini | `ConsultaVencimientosService.php`|
| `v0.3.0` | Sprint 3 | Daily Cal | Documentos V&V y Accesibilidad | Equipo Completo (6 integrantes) | `plan de prueba de...md` |
| `v0.3.0` | Sprint 3 | #72 (S12) | Historial de Movimientos (Kardex)| Darío Tarnoski / Franco Ortelli | `StockController@historial` |
| `v0.3.0` | Sprint 3 | #69 (S15) | Filtrado por Nivel de Stock | Santiago Gomis / Francisco Calvo| `StockController.php` |
| `v0.3.0` | Sprint 3 | #71 (S13) | Conteo Físico (Análisis y Tablas) | Santiago Gomis / Francisco Calvo| `SESION_CONTEO` |
| `v0.3.0` | Sprint 3 | #70 (S14) | Ubicaciones Almacén (Análisis BD) | Santiago Gomis / Francisco Calvo| `UBICACION` |
| `v0.4.0` | Sprint 4 | PC06 | Timeline y Estados de Orden de Compra| Darío Tarnoski / Equipo | `pedido_compra.html` |
