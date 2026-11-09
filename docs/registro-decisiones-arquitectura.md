# Registro de Decisiones de Arquitectura (ADR) — ERP Juan23

Este documento recopila las **Decisiones de Arquitectura de Software** (*Architecture Decision Records - ADR*) tomadas a lo largo del ciclo de vida del proyecto. Cada registro describe el contexto del problema, las opciones analizadas, la decisión adoptada y sus consecuencias técnicas.

---

## ADR-001: Adopción de Arquitectura Monolito Modular en Laravel 12

### Estado
**Aceptado**

### Contexto
El sistema debía integrar las responsabilidades de cuatro grupos de desarrollo (G1: Auth/Clientes, G2: Caja/Finanzas, G3: Stock/Compras/Proveedores, G4: Ventas/Reparto). Existía la disyuntiva entre implementar una arquitectura de microservicios distribuidos independientes o un monolito unificado.

### Decisión
Se adoptó un **Monolito Modular** estructurado bajo `app/Modules/`, donde cada dominio funcional encapsula sus propios controladores, modelos, servicios, migraciones y rutas, compartiendo un mismo runtime de Laravel 12 y una base de datos común.

### Consecuencias
* **Positivas:**
  - Evita la sobrecarga de red, latencia HTTP inter-servicios y complejidad de despliegue de múltiples contenedores.
  - Permite transacciones ACID nativas entre módulos (`DB::transaction`) sin necesidad de patrones complejos como SAGA o commit en dos fases (2PC).
  - Facilita la ejecución de pruebas automatizadas continuas en un único entorno PHPUnit.
* **Negativas:**
  - Requiere estricta disciplina de equipo para respetar los límites de cada módulo y no acoplar llamadas directas indebidas.

---

## ADR-002: Kardex Inmutable para la Gestión de Movimientos de Stock

### Estado
**Aceptado**

### Contexto
Un sistema comercial no puede depender únicamente de actualizar un contador numérico en `producto.stock`, ya que se pierde el rastro de quién, cuándo y por qué cambió la existencia física, impidiendo auditorías contables o detección de mermas y robos.

### Decisión
Se implementó un **Kardex Inmutable** a través de la tabla `movimiento_stock` y la clase `StockService`. Ningún movimiento de stock puede ser editado o eliminado (`DELETE`/`UPDATE` prohibidos por diseño). Toda entrada, salida, ajuste, venta o devolución genera una fila nueva registrando: `stock_anterior`, `cantidad`, `stock_posterior`, `tipo_movimiento`, `id_usuario`, `id_lote` y timestamp exacto.

### Consecuencias
* **Positivas:**
  - Trazabilidad forense 100% auditable.
  - Facilidad para reconstruir el saldo histórico del inventario a cualquier fecha pasada.
* **Negativas:**
  - Crecimiento acelerado del volumen de la tabla `movimiento_stock` a lo largo del tiempo, requiriendo indexación adecuada por `id_producto` y `fecha_hora`.

---

## ADR-003: Desacople de Autenticación mediante Middleware de Identidad (`IdentidadUsuario`)

### Estado
**Aceptado**

### Contexto
El módulo de login y sesión fue asignado al Grupo 1, mientras que los módulos operativos (G2, G3, G4) requerían conocer qué usuario ejecutaba cada acción para la auditoría (`id_usuario_carga`, `log_auditoria`). Esperar a la integración final del login de G1 bloqueaba el desarrollo y las pruebas de los demás módulos.

### Decisión
Se creó el middleware [`App\Http\Middleware\IdentidadUsuario`](../app/Http/Middleware/IdentidadUsuario.php) y la clase utilitaria `UsuarioActual`. Este middleware intercepta la solicitud y resuelve la identidad a través de múltiples mecanismos en cascada:
1. Token Bearer de Grupo 1 (JWT o token cifrado).
2. Token de sesión activo en la tabla `usuario`.
3. Encabezados HTTP directos (`X-User-Id`, `X-User-Username`, `X-User-Role`).
4. Guards de autenticación nativos de Laravel (`Auth::guard('proyecto')`).
5. Fallback seguro al usuario administrativo de desarrollo (`id = 1`) en entornos locales.

### Consecuencias
* **Positivas:**
  - Desacopló el desarrollo: G3 pudo avanzar y probar sus APIs con auditoría completa sin depender de que G1 terminara su pantalla de login.
  - Al integrarse G1, el sistema reconoció los tokens Bearer de forma transparente sin cambiar una sola línea de lógica en los controladores.
* **Negativas:**
  - Requiere asegurar que en entornos de producción los encabezados `X-User-Id` no puedan ser inyectados por clientes no confiables sin token válido previo.

---

## ADR-004: Asignación Obligatoria de Lotes en Devoluciones y Regla FEFO

### Estado
**Aceptado**

### Contexto
En versiones tempranas, las devoluciones de mercadería permitían ingresar stock al inventario general sin indicar el número de lote. En una distribuidora de consumo masivo o alimentos, esto destruye la trazabilidad y permite reingresar productos con fechas de caducidad vencidas o partidas retiradas del mercado (*recall*).

### Decisión
Se estableció como **regla obligatoria de validación** (`400 Bad Request` si no se especifica) que toda devolución (sea de cliente o a proveedor) debe indicar el `id_lote` o crear un `nro_lote` con `fecha_vencimiento`. Asimismo, el sistema aplica la política **FEFO** (*First Expired, First Out*), despachando siempre los lotes cuya caducidad sea más próxima.

### Consecuencias
* **Positivas:**
  - Garantía de trazabilidad alimentaria, sanitaria y legal.
  - Coherencia entre el stock del producto y el stock desglosado por partida.
* **Negativas:**
  - Agrega un paso obligatorio al formulario de devoluciones en el frontend.

---

## ADR-005: Control de Concurrencia e Integridad Atómica con `DB::transaction()`

### Estado
**Aceptado**

### Contexto
Operaciones críticas como recepcionar una compra (crear registro de compra + actualizar estado de orden + sumar stock de producto + sumar stock de lote + crear movimiento Kardex) o finalizar un conteo físico requieren tocar múltiples tablas simultáneamente. Si una sentencia falla por un corte de conexión o error de validación, la base de datos queda corrupta con registros huérfanos.

### Decisión
Se encapsularon todas las operaciones multi-tabla dentro de cierres **`DB::transaction(function () { ... })`**. Si ocurre cualquier excepción dentro del bloque, el motor de base de datos ejecuta un *Rollback* automático integral. Además, las comprobaciones de stock disponible se validan dentro de la misma transacción para evitar sobreventas por condiciones de carrera (*race conditions*).

### Consecuencias
* **Positivas:**
  - Cumplimiento de las propiedades ACID (Atomicidad, Consistencia, Aislamiento y Durabilidad).
  - Cero inconsistencias en saldos de inventario o comprobantes fiscales.
* **Negativas:**
  - Breve bloqueo a nivel de fila (*row-level lock*) durante la ejecución de la transacción en bases de datos con concurrencia masiva.

---

## ADR-006: Versionado de Rutas en `/api/v1/` con Soporte Retrocompatible

### Estado
**Aceptado**

### Contexto
Durante el Sprint 4 se acordó con todos los grupos unificar las rutas de API bajo el prefijo `/api/v1/` para permitir una convivencia estandarizada. Sin embargo, existían componentes frontend y scripts de prueba que ya consumían las rutas en `/api/`.

### Decisión
En `routes/api.php`, se definió el conjunto de rutas en una clausura reutilizable `$registrarRutasG3`, y se registraron simultáneamente bajo:
1. `Route::prefix('v1')->group(...)` ➔ Estándar oficial de integración.
2. `Route::group(...)` ➔ Alias retrocompatible directo sin prefijo.

### Consecuencias
* **Positivas:**
  - Transición sin fricciones: ningún cliente legacy ni prueba existente se rompió, mientras se cumplió al 100% el contrato de versionado.
* **Negativas:**
  - Mantenimiento duplicado de dos puntos de entrada para la misma lógica (resuelto al compartir la misma clausura de código).

---

## ADR-007: Modelo de Costo de Reposición, Recargo (Markup) y Precios Netos sin IVA con Encapsulamiento de Confidencialidad Comercial

### Estado
**Aceptado**

### Contexto
En una distribuidora mayorista de consumo masivo, la lista de precios de venta no puede mantenerse estática ni desacoplada de la inflación o de las variaciones de precios de los proveedores. Asimismo:
1. Se requería distinguir conceptual y matemáticamente entre el **costo de reposición** (precio neto facturado o pactado con el proveedor principal), el **recargo comercial o markup** (`%` sobre el costo: `(precio - costo) / costo * 100`) y el **margen de ganancia bruto** (`%` sobre el precio de venta: `(precio - costo) / precio * 100`).
2. Se requería automatizar la actualización de precios: al registrarse una compra o una actualización de cotización del proveedor principal, el sistema debía actualizar el costo de reposición y recalcular automáticamente el precio de venta según el markup fijado.
3. Se requería proteger la **confidencialidad comercial**: mientras los módulos internos de G3 (administración de inventario, compras y proveedores) gestionan costos y márgenes, los módulos consumidores externos (Catálogo público y Reportes de G1, Caja y Facturación de G2, y POS de Ventas de G4) deben acceder únicamente a los precios netos de venta y stock disponible, sin exponer jamás los costos de compra ni los márgenes brutos de la empresa.

### Decisión
1. **Esquema de Base de Datos:** Se agregaron las columnas `costo` (`DECIMAL(12,2) NULL`) y `recargo` (`DECIMAL(8,2) NULL`) a la tabla `producto` mediante la migración `2026_11_03_000001_add_costo_y_recargo_to_producto_table.php`, y se amplió la columna `origen` de `historial_precio` a `VARCHAR(50)` para soportar el disparador `'costo_reposicion'`.
2. **Sincronización Automática:** En `CompraController@store`, `HistorialPrecioController@actualizarPrecio` y `ProveedorController@asociarProducto`, si el proveedor involucrado es el `es_proveedor_principal` activo, se invoca `Producto::sincronizarCostoReposicion($idUsuario)` para ajustar el costo y actualizar el `precio_unitario` según su recargo, registrando la variación en `historial_precio`.
3. **Precios Netos sin IVA:** Todos los precios expuestos (`precio_unitario`, `precioMayorista`, `precioMinorista`) son netos sin IVA. La discriminación tributaria la realiza Facturación (G2) y la política de descuentos y promociones la calcula Ventas (G4).
4. **Encapsulamiento en APIs de Integración:** En `ProductoController@formatearProducto`, los endpoints de integración inter-grupos (`/api/productos`, `/api/v1/productos`) configuran `$incluirInternos = false`, ocultando deliberadamente `costo`, `recargo`, `margen_ganancia`, `id_usuario_carga` y el historial interno de auditoría.

### Consecuencias
* **Positivas:**
  - Sincronización transparente y en tiempo real de los márgenes comerciales ante compras o subas de insumos.
  - Protección estricta de la confidencialidad de los costos de la distribuidora ante consultas públicas o módulos de ventas en mostrador.
  - Compatibilidad total y desacople garantizado con los contratos de API previamente suscritos con G1, G2 y G4.
* **Negativas:**
  - Requiere mantener al menos un proveedor marcado como `es_proveedor_principal` por producto para habilitar la sincronización automática de costos.
