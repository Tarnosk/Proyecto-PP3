# PLAN DE IMPLEMENTACIÓN TÉCNICA
## Historia de Usuario: S13 — Conteo Físico de Inventario

---

### 1. INFORMACIÓN GENERAL Y ALCANCE
* **Código de HU:** `S13`
* **Título:** Conteo Físico de Inventario
* **Rol / Actor:** Encargado de Depósito
* **Complejidad / Estimación:** 8 Story Points (SP)
* **Dependencias:** `S01` (Consulta y Disponibilidad de Stock)
* **Módulo:** `app/Modules/Stock` (Backend) / `resources/views/inventario/` (Blade) y `frontend/src/components/InventoryView.tsx` (React)
* **Descripción de la HU:**
  > *"Como Encargado de Depósito, quiero realizar un conteo físico de inventario y compararlo con el stock del sistema para detectar diferencias."*

---

### 2. CRITERIOS DE ACEPTACIÓN (CA)

| ID | Criterio de Aceptación | Detalle Técnico / Comportamiento Esperado |
| :--- | :--- | :--- |
| **CA-01** | **Registro de cantidad física** | Debe permitir ingresar y registrar la cantidad contada físicamente de un producto determinado dentro de una sesión de conteo. |
| **CA-02** | **Comparación automática** | El sistema debe comparar automáticamente el valor físico ingresado contra el stock registrado en el sistema (`PRODUCTO.stock` / `stock_disponible`). |
| **CA-03** | **Visualización de diferencias** | Debe calcular y mostrar de forma inmediata la discrepancia detectada: `diferencia = cantidad_fisica - stock_sistema`. Debe clasificar si es: Coincidente ($0$), Faltante ($<0$) o Sobrante ($>0$). |
| **CA-04** | **Resumen de inconsistencias** | Debe generar un balance general de la sesión indicando: total de artículos auditados, total de discrepancias detectadas, desglose de faltantes y sobrantes. |
| **CA-05** | **Registro de observaciones** | Debe permitir registrar notas u observaciones aclaratorias a nivel general de la sesión y a nivel de cada ítem contado (ej.: "caja deteriorada", "lote sin rotular"). |
| **CA-06** *(Opcional / Regularización)* | **Conciliación de inventario** | Al finalizar o consolidar la sesión de conteo, debe ofrecer la opción de ajustar automáticamente el stock del sistema (generando movimientos tipo `'ajuste'` en `MOVIMIENTO_STOCK` con referencia a `id_sesion_conteo`). |

---

### 3. MODELO DE DATOS Y ESTADO DE BASE DE DATOS

Las tablas ya están creadas y normalizadas en el esquema canónico ([`backend/database/esquema_base_DistribuidoraPyB.sql`](file:///c:/Users/dario/Desktop/Proyecto/backend/database/esquema_base_DistribuidoraPyB.sql)):

#### A. Tabla `SESION_CONTEO`
```sql
CREATE TABLE `SESION_CONTEO` (
  `id_sesion` INT AUTO_INCREMENT PRIMARY KEY,
  `estado` ENUM('en_proceso', 'finalizado') NOT NULL DEFAULT 'en_proceso',
  `fecha_conteo` DATE DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `total_productos` INT DEFAULT 0,
  `total_diferencias` INT DEFAULT 0,
  `id_usuario` INT NOT NULL,
  KEY `idx_sesion_conteo_usuario` (`id_usuario`),
  CONSTRAINT `fk_conteo_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE RESTRICT
);
```

#### B. Tabla `DETALLE_CONTEO`
```sql
CREATE TABLE `DETALLE_CONTEO` (
  `id_detalle_conteo` INT AUTO_INCREMENT PRIMARY KEY,
  `id_sesion` INT NOT NULL,
  `id_producto` INT NOT NULL,
  `cantidad_fisica` INT DEFAULT NULL,
  `stock_sistema` INT DEFAULT NULL,
  `diferencia` INT DEFAULT NULL,
  `observaciones` VARCHAR(255) DEFAULT NULL,
  UNIQUE KEY `uq_conteo_sesion_prod` (`id_sesion`, `id_producto`),
  KEY `idx_detalle_conteo_prod` (`id_producto`),
  CONSTRAINT `fk_det_conteo_sesion` FOREIGN KEY (`id_sesion`) REFERENCES `SESION_CONTEO` (`id_sesion`) ON DELETE CASCADE,
  CONSTRAINT `fk_det_conteo_producto` FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`) ON DELETE RESTRICT
);
```

#### C. Integración en `MOVIMIENTO_STOCK`
* Columna `id_sesion_conteo` (FK hacia `SESION_CONTEO.id_sesion`): vincula cada ajuste generado en el Kardex con la auditoría física correspondiente.

---

### 4. ARQUITECTURA TÉCNICA Y COMPONENTES A DESARROLLAR

```
backend/app/Modules/Stock/
├── Models/
│   ├── SesionConteo.php              <-- NUEVO: Modelo Eloquent para SESION_CONTEO
│   └── DetalleConteo.php             <-- NUEVO: Modelo Eloquent para DETALLE_CONTEO
├── Repositories/
│   └── ConteoFisicoRepository.php    <-- NUEVO: Consultas y agregaciones de sesiones
├── Services/
│   └── ConteoFisicoService.php       <-- NUEVO: Reglas de negocio, cálculo de diferencias y conciliación
├── Controllers/
│   └── ConteoFisicoController.php    <-- NUEVO: Controlador RESTful para API
└── Requests/
    ├── IniciarSesionConteoRequest.php <-- NUEVO: Validación de apertura de sesión
    └── RegistrarItemConteoRequest.php <-- NUEVO: Validación de ítem contado
```

---

### 5. ESPECIFICACIÓN DE ENDPOINTS API REST (`routes/api.php`)

Todas las rutas vivirán bajo el prefijo `/api/stock/conteos`:

| Método | Endpoint | Descripción | Body / Parámetros | Respuesta Exitosa (200 / 201) |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/api/stock/conteos/sesiones` | Inicia una nueva sesión de auditoría física | `{ "fecha_conteo": "2026-10-11", "observaciones": "Auditoría mensual pasillo A" }` | `{ "status": "success", "data": { "id_sesion": 1, "estado": "en_proceso", ... } }` |
| `GET` | `/api/stock/conteos/sesiones` | Lista sesiones de conteo con filtros | `?estado=en_proceso\|finalizado&fecha_desde=&fecha_hasta=` | `{ "status": "success", "data": [ ... ] }` |
| `GET` | `/api/stock/conteos/sesiones/{id}` | Obtiene el detalle completo y resumen de diferencias de una sesión | - | `{ "status": "success", "data": { "sesion": { ... }, "resumen": { "total_contados": 15, "inconsistencias": 3, "sobrantes": 1, "faltantes": 2 }, "detalles": [ ... ] } }` |
| `POST` | `/api/stock/conteos/sesiones/{id}/items` | Registra o actualiza el conteo físico de un producto | `{ "id_producto": 4, "cantidad_fisica": 25, "observaciones": "2 unidades con rotura menor" }` | `{ "status": "success", "data": { "id_producto": 4, "cantidad_fisica": 25, "stock_sistema": 30, "diferencia": -5, "diagnostico": "faltante" } }` |
| `DELETE` | `/api/stock/conteos/sesiones/{id}/items/{id_producto}` | Elimina un ítem auditado de la sesión en curso | - | `{ "status": "success", "message": "Ítem removido de la sesión" }` |
| `POST` | `/api/stock/conteos/sesiones/{id}/finalizar` | Cierra la sesión y opcionalmente aplica ajustes de regularización | `{ "aplicar_ajustes": true\|false }` | `{ "status": "success", "message": "Sesión finalizada exitosamente", "ajustes_aplicados": 3 }` |

---

### 6. DETALLE DE IMPLEMENTACIÓN POR TAREAS (ROADMAP)

#### Tarea 1: Modelos Eloquent (`Models/`)
* **`SesionConteo.php`**:
  * Tabla: `SESION_CONTEO`, PK: `id_sesion`.
  * `fillable`: `['estado', 'fecha_conteo', 'observaciones', 'total_productos', 'total_diferencias', 'id_usuario']`.
  * Relaciones:
    * `detalles()` -> `hasMany(DetalleConteo::class, 'id_sesion')`.
    * `usuario()` -> `belongsTo(Usuario::class, 'id_usuario')`.
    * `movimientos()` -> `hasMany(MovimientoStock::class, 'id_sesion_conteo')`.
* **`DetalleConteo.php`**:
  * Tabla: `DETALLE_CONTEO`, PK: `id_detalle_conteo`.
  * `fillable`: `['id_sesion', 'id_producto', 'cantidad_fisica', 'stock_sistema', 'diferencia', 'observaciones']`.
  * Relaciones:
    * `sesion()` -> `belongsTo(SesionConteo::class, 'id_sesion')`.
    * `producto()` -> `belongsTo(Producto::class, 'id_producto')`.

#### Tarea 2: Lógica de Negocio (`ConteoFisicoService.php`)
* **`iniciarSesion(array $datos, int $idUsuario): SesionConteo`**:
  * Verifica que no haya otra sesión abierta en curso si se desea política de sesión única, o permite múltiples por sector.
  * Inicializa contadores en 0 y asigna fecha actual si no se provee.
* **`registrarItem(int $idSesion, int $idProducto, int $cantidadFisica, ?string $obs): DetalleConteo`**:
  * Valida que la sesión exista y esté `'en_proceso'` (bloquea edición si está `'finalizado'`).
  * Valida que el producto exista y esté activo (`estado = 'activo'`).
  * Obtiene el stock actual del sistema (`PRODUCTO.stock`).
  * Calcula `diferencia = $cantidadFisica - $stockSistema`.
  * Realiza `updateOrCreate` sobre `DETALLE_CONTEO`.
  * Actualiza los totales en `SESION_CONTEO`:
    * `total_productos = count(detalles)`
    * `total_diferencias = count(detalles con diferencia != 0)`.
* **`obtenerResumen(int $idSesion): array`**:
  * Agrupa las estadísticas:
    * Total productos contados.
    * Coincidentes (`diferencia == 0`).
    * Faltantes (`diferencia < 0`) y suma total de unidades faltantes.
    * Sobrantes (`diferencia > 0`) y suma total de unidades sobrantes.
* **`finalizarSesion(int $idSesion, bool $aplicarAjustes, int $idUsuario): SesionConteo`**:
  * Transacción DB atómica.
  * Si `$aplicarAjustes === true`:
    * Para cada detalle con `diferencia != 0`:
      * Llama a `StockService::registrarMovimiento()` con tipo `'ajuste'`.
      * Motivo: `"Regularización por Conteo Físico #{$idSesion}: dif {$dif} u."`.
      * Se setea `id_sesion_conteo = $idSesion` en el movimiento.
  * Cambia el estado de la sesión a `'finalizado'`.

#### Tarea 3: Controlador y Rutas (`ConteoFisicoController.php`)
* Implementar los métodos:
  * `store(Request $request)`
  * `index(Request $request)`
  * `show(int $id)`
  * `registrarItem(Request $request, int $id)`
  * `eliminarItem(int $id, int $idProducto)`
  * `finalizar(Request $request, int $id)`
* Usar `UsuarioActual::id()` para obtener el auditor que opera.
* Respuestas en JSON estandarizado: `{ "status": "success"|"error", "data": ..., "message": ... }`.

#### Tarea 4: Interfaz de Usuario (Frontend)
1. **Pestaña "Conteo Físico" en Blade (`inventario/index.blade.php`):**
   * Panel superior: Selección o creación de sesión de conteo activa (con indicador de estado `En Proceso` / `Finalizada`).
   * Formulario de carga rápida:
     * Buscador / Selector de producto.
     * Muestra en tiempo real el stock registrado del sistema.
     * Input para cantidad física contada.
     * Comparador visual dinámico con feedback semafórico:
       * Verde si coincide ($0$).
       * Rojo si falta ($<0$).
       * Azul si sobra ($>0$).
     * Input para observaciones del ítem.
     * Botón "Registrar en Conteo".
   * Panel de Resumen de Inconsistencias (tarjetas de KPI):
     * Total Contados | Coincidencias | Faltantes | Sobrantes.
   * Tabla interactiva de la sesión:
     * Columnas: Producto | Código | Stock Sistema | Conteo Físico | Diferencia | Diagnóstico | Observaciones | Acciones.
   * Botón de cierre: "Finalizar Sesión" con modal de confirmación y checkbox "¿Desea aplicar regularización automática de stock?".
2. **Componente React (`frontend/src/components/InventoryView.tsx`):**
   * Incorporar la sub-vista o modal correspondiente invocando al nuevo servicio `conteoService.ts`.

---

### 7. PLAN DE PRUEBAS AUTOMATIZADAS (TESTS DE ACEPTACIÓN)

Crear el archivo de pruebas en [`backend/tests/Feature/ConteoFisicoTest.php`](file:///c:/Users/dario/Desktop/Proyecto/backend/tests/Feature/ConteoFisicoTest.php):

| Caso de Prueba | Escenario a Validar | Resultado Esperado |
| :--- | :--- | :--- |
| `test_iniciar_sesion_conteo` | Abrir una nueva sesión de auditoría física | HTTP 201, sesión creada con estado `'en_proceso'`, contadores en 0. |
| `test_registrar_item_conteo_coincidente` | Producto con stock 10, se cuenta 10 físicamente | HTTP 200, diferencia = 0, diagnóstico = coincidente. |
| `test_registrar_item_conteo_con_discrepancia` | Producto con stock 50, se cuentan 45 | HTTP 200, diferencia = -5, se actualiza `total_diferencias = 1` en la sesión. |
| `test_bloquear_edicion_en_sesion_finalizada` | Intentar agregar ítems a una sesión con estado `'finalizado'` | HTTP 422 / 400 con mensaje de error claro. |
| `test_resumen_inconsistencias` | Consultar el endpoint `GET /sesiones/{id}` con múltiples ítems | Devuelve desglose exacto de conteos coincidentes, faltantes y sobrantes. |
| `test_finalizar_sesion_con_regularizacion` | Finalizar sesión con `aplicar_ajustes = true` | El stock del producto se actualiza al valor físico y se registran los movimientos Kardex vinculados a `id_sesion_conteo`. |

---

### 8. DEFINITION OF DONE (DoD)
Para dar por completada la HU S13, se deben cumplir todas las siguientes condiciones:
1. [ ] Modelos `SesionConteo` y `DetalleConteo` creados con relaciones y casts correctos.
2. [ ] `ConteoFisicoService` implementado con manejo de transacciones atómicas.
3. [ ] Rutas API REST registradas y funcionales en `backend/routes/api.php`.
4. [ ] Pestaña en la vista web de inventario con feedback visual inmediato (comparación automática y semáforo de diferencias).
5. [ ] Resumen de inconsistencias generado y visible para el usuario.
6. [ ] Suite de pruebas `ConteoFisicoTest.php` creada y pasando al 100% en `artisan test`.
7. [ ] Código formateado según PSR-12, sin advertencias de linter y documentado.
