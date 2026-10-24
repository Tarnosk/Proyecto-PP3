# Informe de conexión de G2 con G1

Fecha: 22 de octubre de 2026.

## Resultado y alcance real

Se preparó la integración de código de **Caja, Conciliación Bancaria, Entregas y Rendiciones** con el login y la navegación de G1. Se registraron las rutas de cada módulo, se retiraron las identidades provisorias de sus controladores y se recuperó el frontend de Entregas/Rendiciones desde la referencia local `origin/develop-g2`.

**La operación real de G2 todavía no está habilitada en esta base.** Se comprobó que faltan sus 13 tablas. Además, hay contratos pendientes con Ventas y un origen de cobros ausente en Conciliación. Se dejaron avisos controlados HTTP 503 para no convertir esos faltantes en errores SQL o éxitos simulados.

La autorización de esta etapa cubre autenticación, rutas, frontend y código de integración de G2. No se instalaron esquemas, ejecutaron migraciones o seeders ni modificaron datos comerciales existentes. No se cambió código de backend de G3/G4. Las APIs de Ventas se consultaron como contrato de referencia, sin ejecutar operaciones contra ellas.

## Qué estaba desconectado

1. Las cuatro entradas del menú apuntaban a una vista provisoria; sus módulos no tenían rutas web registradas en este checkout.
2. Caja web usaba el usuario fijo `3` y un rol fijo; Conciliación usaba `1`. Caja JSON aceptaba identidades/roles del request. Entregas asumía el actor `1` y Rendiciones aceptaba el validador enviado por el navegador.
3. `UsuarioService` priorizaba nombres simulados y hasta fabricaba roles para IDs inexistentes; Caja referenciaba un modelo `Shared\Models\Usuario` que no existe aquí.
4. El helper JSON imprimía la respuesta y ejecutaba `exit`, evitando que Laravel terminara su ciclo HTTP normal.
5. Entregas/Rendiciones importaban `App\Modules\Ventas\Services\VentasApiClient`, ausente en este checkout. La versión original de G2 tenía respaldos con tablas ajenas y datos de demostración.
6. La apertura del índice de Conciliación podía crear un período. Una consulta GET no debería realizar esa escritura.

## Cambios de autenticación y permisos

- Se agregó `Shared/Auth/G2Identity`: toma el ID del usuario autenticado de G1, adapta `administrativo` a `admin` para las reglas existentes de G2 y mantiene `repartidor`/`contador`.
- `Shared/Services/UsuarioService` ahora consulta el directorio `usuarios` de G1 en modo de lectura, devuelve repartidores activos y no crea usuarios ni inventa identidades. Adapta el campo `id` de G1 al nombre `id_usuario` que espera G2.
- `G2Access` protege las rutas por rol. El contador conserva acceso a Conciliación; Caja, Entregas y Rendiciones quedan para administrativo/repartidor. Armar despachos y revisar rendiciones quedan para administración.
- `G2OwnsRecord` comprueba en tablas propias de G2 que entregas, remitos y rendiciones correspondan al repartidor antes de llamar al controlador o a servicios remotos. Un registro ajeno responde 404. Administración puede consultar los registros correspondientes a sus tareas.
- Caja usa identidad/rol de sesión al abrir, registrar movimientos, cerrar y cargar conteos; filtra el historial del repartidor y protege el detalle de cajas/cierres ajenos. Las validaciones de negocio siguen en `CajaService`.
- Entregas usa el actor de sesión para creación/confirmación, limita listados al repartidor y valida que el repartidor seleccionado esté activo.
- Rendiciones limita listados, fuerza el repartidor de sesión cuando corresponde y fija el validador administrativo en el servidor. Verifica la pertenencia de entrega/remitos al repartidor al registrar una rendición.
- Se retiraron los valores de respaldo `id_cliente=1`, `id_factura=1` y la selección implícita de “última rendición o 1”. Los identificadores requeridos deben llegar explícitamente y validarse.

No se alteraron importes, fórmulas de cierre, plazos de Caja, reglas de aprobación/rechazo ni estados comerciales para hacer coincidir artificialmente los módulos.

## Rutas y frontend

Cada módulo tiene su archivo `routes/web.php`, cargado por el proveedor modular existente. Se reemplazaron las cuatro rutas provisorias del archivo central por las rutas reales de G2.

| Pantalla | Ruta | Comportamiento con la base local actual |
|---|---|---|
| Caja y Tesorería | `/caja` | Aviso de instalación pendiente, HTTP 503, menú común visible |
| Conciliación Bancaria | `/conciliacion` | Igual aviso; GET no crea períodos |
| Entregas y Despacho | `/entregas` | Igual aviso; vista original recuperada queda preparada |
| Rendiciones y Cobros | `/rendiciones` | Igual aviso; vista original recuperada queda preparada |

Se registraron **46 endpoints de sesión bajo `/g2/api`** para los controladores existentes. Se usa un prefijo propio para evitar colisiones, especialmente con `/api/v1/zonas` de G1. Estos endpoints usan middleware `web`, autenticación, vigencia de sesión, permisos y CSRF: **no se presentan como una API pública para otros equipos ni como autenticación por token entre servicios**.

Se conservaron nombres de rutas usados por los formularios de Caja/Conciliación y se agregó la ruta faltante de carga de conteo. Se recuperaron scripts/estilos de `origin/develop-g2` en archivos `g2-*`, sin cambiar de rama, hacer merge ni traer sus módulos de Ventas.

- Entregas y Rendiciones ahora extienden `layouts.app`; se eliminaron su sidebar/usuario fijo y el JavaScript del menú anterior.
- Caja y Conciliación conservan sus vistas; sus componentes tienen estilos limitados a `.g2-page`.
- Los estilos recuperados están prefijados para no redefinir el menú común ni las pantallas de otros equipos. Se conservaron los diseños internos de G2.
- Los scripts consumen `/g2/api`, no envían actores fijos y reciben el token CSRF de la sesión. `g2-http.js` restringe sus llamadas al mismo origen/prefijo, conserva JSON y convierte errores de validación HTTP en errores reconocibles para el frontend.
- Se retiraron repartidores/clientes/IDs de demostración de los formularios recuperados. Los repartidores salen del directorio real. El selector manual de cliente/factura queda sin opciones inventadas: necesita un catálogo real para operar sin una entrega seleccionada.
- La creación del período mensual se pasó a un botón POST con CSRF: **“Crear período del mes si falta”**. Invoca el mismo servicio y la misma regla de período mensual.

El aviso de instalación usa el layout común: aun ante el faltante de tablas se conserva la navegación. No se modificó la pantalla pública de login.

## Respuestas HTTP e integración con Ventas

`Shared/Http/Response` devuelve `JsonResponse` de Laravel manteniendo la estructura `{error,codigo,mensaje,data}` o `{error,codigo,mensaje,detalles}`. Se actualizaron sus cinco controladores consumidores para retornar estas respuestas, incluidos los métodos que delegan en otros métodos. Se retiró `echo/exit` del helper.

El nuevo cliente está en `Entregas/Integraciones/VentasApiClient.php`, dentro de G2. Sus consumidores de Entregas y Rendiciones apuntan a esa clase.

- Sólo hace llamadas HTTP. Se eliminaron de la integración las consultas de respaldo a `detalle_venta/producto`, la escritura directa `UPDATE venta` y los artículos inventados cuando faltaba el detalle.
- Consume listado y detalle de ventas, además del endpoint de cambio de estado que ya esperaba el código de G2. No publica ni cambia endpoints de G4.
- Adapta los campos documentados por `VentaResource`: `id`, `cliente.id`, `estado.valor` y `detalles`. Conserva identificadores e importes reales y recorre las páginas del listado. No sigue URLs externas de paginación ni devuelve un listado parcial si excede su límite de 100 páginas.
- Si se solicita filtro por zona y la respuesta no informa `id_zona`, comunica la incompatibilidad; no interpreta los datos ausentes como ausencia de pedidos.
- No supone que una factura esté impaga cuando falta el indicador booleano `pagado`. Rendiciones requiere ese dato para la validación de cobro que ya existía.
- Zonas se lee del catálogo `zonas` de G1 mediante un adaptador de lectura; no se crea otra tabla `zona` ni se sobrescribe la API de G1.

`config/g2.php` admite `G2_VENTAS_API_URL`, `G2_VENTAS_API_TOKEN` y un timeout de cinco segundos. **No se modificó `.env` ni se activó una URL o credencial supuesta.** La URL debe incluir el prefijo API acordado, por ejemplo el `/api` del servicio de G4.

No apuntar ese cliente al mismo proceso único de `artisan serve`: una petición HTTP del servidor hacia sí mismo puede agotar el tiempo de espera. La dirección y la autenticación del servicio de Ventas deben resolverse antes de activar esta conexión.

## Dependencias pendientes para habilitar operación real

### 1. Instalación del esquema oficial de G2

La comprobación de metadatos encontró ausentes:

| Área | Tablas requeridas |
|---|---|
| Caja | `CAJA`, `CAJA_MOVIMIENTO` |
| Conciliación | `PERIODO_CONCILIACION`, `MOVIMIENTO_BANCARIO`, `CONCILIACION_DETALLE`, `AJUSTE_BANCARIO`, `CHEQUE` |
| Entregas | `entrega`, `entrega_pedido`, `remito`, `detalle_remito` |
| Rendiciones | `RENDICION`, `RENDICION_COBRO` |

Hay dependencias internas entre estas áreas: Conciliación consulta movimientos de Caja y la confirmación de entregas precarga Rendiciones. El middleware verifica las tablas que necesita cada área, no sólo su cabecera.

No se crearon migraciones alternativas ni se ejecutó el SQL general del repositorio, que podría afectar a otros grupos. Para instalar G2 hay que revisar su esquema oficial, sus columnas y las referencias de usuario. Los IDs de G1 (`usuarios.id`) no pueden suponerse equivalentes a antiguos usuarios de demostración de G2. **No se hizo una migración de identidades ni se cambiaron claves foráneas.** La comprobación actual de readiness verifica existencia de tablas, no compatibilidad completa de columnas/FK.

### 2. Contrato de G2 con Ventas/G4

- El código original de despacho envía estados `en_preparacion` y `en_camino`; el catálogo actual de G4 publica `preparada`, entre otros, y no esos dos valores. No se decidió que fueran equivalentes. Antes de habilitar despachos hay que acordar el significado y las transiciones; no basta con configurar una URL.
- El recurso actual de G4 entrega cliente, estado, importes y detalle de productos. No publica en ese recurso todos los datos logísticos que G2 espera (zona, domicilio, bultos/unidades de empaque) ni el booleano `pagado` utilizado por Rendiciones. Se necesita confirmar cómo obtenerlos y qué fuente es autoritativa.
- No se conectaron cobros de G2 a Cuenta Corriente ni se cambió el estado financiero de ventas automáticamente. Registrar una rendición no prueba que el saldo de G4 se haya actualizado.
- No se probó un despacho real, confirmación real de entrega ni cobro/aprobación real contra servicios externos.

### 3. Cobros y conciliación automática de G2

`ConciliacionRepository` referencia `Cobro` sin que exista esa clase en su namespace/imports; `Cheque` tampoco tiene definida la relación `cobro` usada al buscar candidatos. El esquema y sus claves deben confirmarse con G2. No se reemplazó ese origen por `RendicionCobro` ni por un modelo de G4 porque sus IDs y campos no son equivalentes automáticamente.

La ruta de conciliación automática devuelve un aviso 503 si sigue faltando esa dependencia, aun si luego se instalan las tablas. No se cambiaron sus reglas de búsqueda de candidatos, tolerancias ni conciliación financiera.

Otros comportamientos del backend original de G2 —por ejemplo procesadores que simulan validaciones, devoluciones/diferencias o efectos contables, y notificaciones posteriores al commit— requieren validación funcional del equipo. Esta conexión de identidad/rutas no convierte esas simulaciones en persistencia real ni garantiza transacciones distribuidas con G4.

## Verificaciones realizadas

Se ejecutaron las cuatro suites de esta integración y su regresión:

```powershell
C:\xampp\php\php.exe artisan test tests/Feature/G2IntegrationTest.php tests/Feature/PresentationIntegrationTest.php tests/Feature/G4DependencyRegistrationTest.php tests/Feature/G4SalesSchemaTest.php
```

Resultado: **34 pruebas aprobadas, 350 aserciones**. G2 aporta 14 pruebas nuevas sobre identidad real, payloads con actor/rol falsificado, permisos y pertenencia, vistas/scripts, respuestas JSON, paginación de Ventas, ausencia de respaldos simulados y dependencias faltantes. Los servicios comerciales se sustituyen por dobles de prueba donde se comprueba el límite HTTP. Los fixtures se crean sólo en SQLite `:memory:`; estas suites no instalan G2 en MySQL ni registran operaciones reales.

Se comprobó adicionalmente el JavaScript con `node --check` y el helper HTTP mediante `.tools/g2-http-test.cjs`: CSRF, credenciales de sesión, aislamiento de origen/prefijo y manejo de validaciones 422. `git diff --check` no encontró errores de espacios en los cambios.

La comprobación local `.tools/g2-live-readonly.php` usa una identidad/sesión exclusivamente en memoria, consulta la base configurada sólo en lectura y no inicia sesión con una cuenta real:

- `/caja`, `/conciliacion`, `/entregas`, `/rendiciones`: HTTP 503 esperado por las tablas ausentes.
- Cada respuesta contiene exactamente un menú compartido.
- 20 consultas de lectura; ninguna operación comercial ni usuario persistido.

No se hizo verificación visual interactiva en navegador ni pruebas reales de escritura. Los tests anteriores de G4 siguen aprobados. No se ejecutó toda la batería de historias comerciales de los otros grupos. No se crearon commits, merges o publicaciones.

## Archivos agregados y modificados

La siguiente lista corresponde sólo a esta etapa. No atribuye a G2 las modificaciones anteriores del menú/G4 que ya estaban presentes al comenzar. Los motivos se detallaron en las secciones anteriores.

### Agregados
- `app/Modules/Caja/routes/web.php`
- `app/Modules/ConciliacionBancaria/routes/web.php`
- `app/Modules/Entregas/Integraciones/VentasApiClient.php`
- `app/Modules/Entregas/routes/web.php`
- `app/Modules/Rendiciones/routes/web.php`
- `app/Modules/Shared/Auth/G2Identity.php`
- `app/Modules/Shared/Http/Middleware/G2Access.php`
- `app/Modules/Shared/Http/Middleware/G2OwnsRecord.php`
- `app/Modules/Shared/Http/Middleware/G2SchemaReady.php`
- `config/g2.php`
- `public/css/g2.css`
- `public/css/g2-finanzas.css`
- `public/js/g2-http.js`
- `public/js/g2-entregas.js`
- `public/js/g2-rendiciones.js`
- `resources/views/entregas/index.blade.php`
- `resources/views/rendiciones/index.blade.php`
- `resources/views/modules/g2-unavailable.blade.php`
- `tests/Feature/G2IntegrationTest.php`
- `docs/informe-integracion-operativa-g2.md`

### Modificados

- `README.md`
- `app/Modules/Caja/Controllers/CajaController.php`
- `app/Modules/Caja/Controllers/CajaWebController.php`
- `app/Modules/Caja/Repositories/CajaRepository.php`
- `app/Modules/ConciliacionBancaria/Controllers/ChequeController.php`
- `app/Modules/ConciliacionBancaria/Controllers/ConciliacionController.php`
- `app/Modules/ConciliacionBancaria/Controllers/ConciliacionWebController.php`
- `app/Modules/Entregas/Controllers/EntregaController.php`
- `app/Modules/Entregas/Repositories/EntregaRepository.php`
- `app/Modules/Entregas/Repositories/RemitoRepository.php`
- `app/Modules/Entregas/Services/EntregaService.php`
- `app/Modules/Rendiciones/Controllers/RendicionController.php`
- `app/Modules/Rendiciones/Repositories/RendicionRepository.php`
- `app/Modules/Rendiciones/Services/RendicionService.php`
- `app/Modules/Shared/Http/Response.php`
- `app/Modules/Shared/Services/UsuarioService.php`
- `docs/informe-correccion-integracion-g4.md`
- `docs/integracion-frontend.md`
- `resources/views/caja/abrir.blade.php`
- `resources/views/caja/cierre-detalle.blade.php`
- `resources/views/caja/cierres.blade.php`
- `resources/views/caja/dia.blade.php`
- `resources/views/conciliacion/create.blade.php`
- `resources/views/conciliacion/index.blade.php`
- `resources/views/conciliacion/show.blade.php`
- `routes/web.php`
- `tests/Feature/PresentationIntegrationTest.php`

También quedaron herramientas locales en `.tools` para comprobación y reversión. Esa carpeta está ignorada por Git. Las herramientas de edición usadas durante esta tarea no son comandos de arranque ni deben volver a ejecutarse.

Se quitaron las referencias a `menu-global-y-apis-g1.md` del README y los informes que lo enlazaban. El archivo no se recreó ni se trasladó su inventario de APIs a otro documento. Su eliminación queda a cargo del usuario, como indicó.

## Cómo revertir todos los cambios de esta etapa

El respaldo está en:

```text
.tools/backups/g2-integration-20261022-212235/
```

Contiene las versiones anteriores de los archivos modificados y un `manifest.json` con la lista y los hashes anteriores/posteriores. Las vistas de Caja/Conciliación estaban limpias en Git al iniciar esta etapa: su respaldo se obtuvo de `HEAD`, sin traer cambios de otras ramas. El resto se copió antes de editar.

Se incluyó `revert.php` dentro del respaldo para usar el PHP ya disponible en XAMPP. **Por defecto sólo revisa y muestra el plan; no cambia archivos.** Ejecutar desde la raíz del proyecto:

```powershell
C:\xampp\php\php.exe .tools/backups/g2-integration-20261022-212235/revert.php
```

Para aplicar exactamente ese plan:

```powershell
C:\xampp\php\php.exe .tools/backups/g2-integration-20261022-212235/revert.php --apply
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan config:clear
```

La reversión restaura los archivos modificados desde su copia previa y elimina exclusivamente los archivos nuevos enumerados. Valida que las rutas estén dentro del proyecto, que las copias tengan el hash esperado y que los archivos actuales coincidan con la versión entregada. Si detecta modificaciones posteriores, se detiene antes de escribir: hay que comparar/respaldar esas modificaciones y resolverlas manualmente. No usa `git reset --hard`, no borra carpetas recursivamente y no restaura ni borra archivos ajenos a este manifiesto.

No hay rollback de base de datos para esta etapa: no se ejecutaron instalaciones ni escrituras comerciales. **No ejecutar el rollback de G4 para deshacer G2**: corresponde a otro trabajo. Si después se instalan tablas o registran operaciones de G2, esa instalación/datos necesitarán su propio procedimiento, independiente de esta reversión de archivos.

El respaldo incluye las versiones previas del README/informes. Restaurarlas puede reintroducir enlaces al documento de APIs que el usuario eliminó; pueden quitarse esos enlaces nuevamente sin recrear el documento. El script no recrea `menu-global-y-apis-g1.md`.

Conservar una copia de este informe y de la carpeta del respaldo fuera del checkout antes de aplicar la reversión: el informe de G2 es uno de los archivos nuevos que se elimina al deshacer esta etapa. `.tools` no se incluye en commits; para usar este mecanismo en otra computadora hay que trasladar el respaldo completo y revisar el directorio raíz registrado en el manifiesto.
