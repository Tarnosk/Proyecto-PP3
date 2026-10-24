# Informe de corrección e integración local de G4

Fecha: 22 de octubre de 2026.

Actualización posterior de frontend: las pantallas internas comparten el menú lateral y el relevamiento de APIs de G1 quedó en el chat. La intervención posterior en G2 tiene su propio [informe y reversión](informe-integracion-operativa-g2.md). Esa intervención conserva el esquema y la configuración de G4 descritos aquí. Si se usan las copias antiguas para revertir el arranque, comparar primero el README y conservar las modificaciones posteriores del menú.

## Resultado actual

Las tres pantallas responden HTTP 200 con sus rutas, middleware y controladores reales y con la base local `sistema_gestion`:

| Pantalla | Resultado final |
| --- | --- |
| `/ventas` | Abre con los catálogos originales de prueba de G4. |
| `/historial-ventas` | Abre y consulta la tabla local de ventas, actualmente vacía. |
| `/saldo` | Abre y combina los movimientos de ejemplo del catálogo con la tabla local, actualmente vacía. |

La configuración sigue siendo `VENTAS_INTEGRACION=mock`. No se activaron las APIs reales ni se registraron ventas, pagos o movimientos comerciales en la base local durante las verificaciones.

Se crearon exclusivamente `venta`, `detalle_venta` y `cuenta_corriente_movimientos`. No se reemplazaron tablas existentes ni se modificaron tablas o datos comerciales de G1, G2 o G3.

## Alcance y autorizaciones

La primera autorización cubrió frontend y rutas de presentación; la siguiente permitió restaurar el registro de dependencias y la configuración de arranque de G4. Una autorización posterior amplió el alcance para revisar e instalar únicamente el esquema oficial de G4 necesario para estas pantallas, sin borrar datos existentes ni modificar tablas de otros grupos.

Los cambios de navegación y rutas que ya estaban presentes antes de la restauración de dependencias son de la intervención anterior. La reversión de este informe cubre el arranque de G4 y su instalación de esquema; conserva esa navegación previa.

## Problemas encontrados

### 1. Dependencias sin registrar

Laravel no podía resolver `ClienteGateway`. También faltaban los registros de `ProductoGateway` y `StockGateway`, la configuración `config/ventas.php` y tres clases originales de apoyo de G4. El fallo ocurría antes del renderizado.

La línea del middleware de roles resaltada en las capturas solo transfería el control; no era la causa del error.

### 2. Vistas con nombres anteriores

Los controladores pedían `ventas`, `historial-ventas` y `saldo`, mientras los archivos estaban dentro de subcarpetas. Se agregaron archivos de compatibilidad para resolver los nombres que usa G4 conservando las vistas organizadas y los controladores originales.

### 3. Base local sin las tablas de G4

En la primera comprobación MySQL no respondía. Las capturas posteriores confirmaron que, con MySQL activo, faltaban `sistema_gestion.venta` y `sistema_gestion.cuenta_corriente_movimientos` (`SQLSTATE[42S02]`, error 1146).

La revisión autorizada confirmó una tercera tabla ausente: `detalle_venta`, necesaria para mostrar los productos cuando el historial tenga ventas. Se verificó que las tres estaban ausentes antes de ejecutar cualquier DDL.

El modo `mock` simula catálogos y stock. No sustituye las consultas persistentes del historial y las cuentas corrientes.

## Archivos agregados o modificados

### Restauración del arranque

| Archivo | Cambio y motivo |
| --- | --- |
| `app/Providers/AppServiceProvider.php` | Registros `scoped` para los tres gateways de G4, manteniendo el registro de ventas externas y las políticas de G1. Laravel puede resolver las interfaces y reutilizar sus instancias dentro de una petición. |
| `config/ventas.php` | Archivo original recuperado: modo de integración, URLs, timeout y valores que espera G4. |
| `app/Services/MockClienteApi.php` | Clase original recuperada para clientes y movimientos iniciales de demostración. |
| `app/Services/MockProductoApi.php` | Clase original recuperada para productos, precios y stock de ejemplo. |
| `app/Services/MockUsuarioApi.php` | Clase original recuperada para los nombres de usuarios del historial. |
| `.env` local | Se agregó `VENTAS_INTEGRACION=mock`. No se cambiaron credenciales. |
| `.env.example` | Se documentaron las variables de integración de G4. |
| `resources/views/ventas.blade.php` | Incluye `ventas.ventas` para conservar el nombre esperado por el controlador. |
| `resources/views/historial-ventas.blade.php` | Incluye `historial-ventas.historial-ventas`. |
| `resources/views/saldo.blade.php` | Incluye `saldos.saldo`. |
| `tests/Feature/G4DependencyRegistrationTest.php` | Cinco pruebas de registro, selección de adaptadores y ejecución real de controladores, inicialmente con respuestas de lectura de prueba. |

Los cuatro archivos recuperados coinciden exactamente con la referencia local `origin/develop-g4`, commit `022a72026c6ba68040918b1a6e12a92ecc6f72de`. Se mantuvo `app/Services` porque coincide con los namespaces utilizados por las clases originales de G4.

`MockStockApi` y los adaptadores HTTP ya existían y no se modificaron.

### Instalación de tablas autorizada después

| Archivo | Cambio y motivo |
| --- | --- |
| `app/Modules/Ventas/database/migrations/2026_10_22_000000_create_g4_sales_base_tables.php` | Migración nueva y acotada que crea únicamente `venta` y `detalle_venta` con las definiciones oficiales de G4. Incluye controles para no reemplazar tablas y para no revertir una instalación con operaciones guardadas. |
| `app/Modules/CuentaCorriente/database/migrations/2026_10_07_000000_create_cuenta_corriente_movimientos_table.php` | Ya existía. Se ejecutó sin modificar su código. |
| `tests/Feature/G4SalesSchemaTest.php` | Cuatro pruebas adicionales con las tablas reales en SQLite en memoria: apertura de pantallas, reversión vacía, rechazo de reversión con datos y rechazo de reemplazo de tablas existentes. |
| `README.md` | Se documentaron modo `mock`, alcance de las escrituras originales, instalación acotada y referencia a este informe. |
| `docs/informe-correccion-integracion-g4.md` | Se actualizaron causas, cambios, resultados y procedimientos de reversión. |

No se modificaron controladores, servicios de negocio, modelos ni contratos de G4. Tampoco se importó el SQL completo de la rama ni se ejecutó el conjunto general de migraciones, que contiene artefactos de otros grupos.

## Origen y contenido del esquema

Las definiciones de `venta` y `detalle_venta` se extrajeron exclusivamente de sus bloques en `database/migrations/2026_10_15_000000_create_erp_tables.php` de la referencia de G4 indicada arriba. No se ejecutó ese archivo completo.

| Tabla | Estructura instalada |
| --- | --- |
| `venta` | `id_venta` bigint unsigned autoincremental; `id_cliente` e `id_usuario` unsigned integer; fecha; total y descuento global decimal(12,2); `numFactura` varchar(50) único; estado varchar(30); observaciones varchar(150). El descuento global conserva el valor predeterminado 0 del original. |
| `detalle_venta` | `id_detalle` bigint unsigned autoincremental; `id_venta` bigint unsigned; identificadores unsigned integer de producto y promoción, esta última nullable; cantidad unsigned integer; precio, descuento y subtotal decimal(12,2). Clave foránea únicamente hacia `venta.id_venta`, con borrado en cascada, como en G4. |
| `cuenta_corriente_movimientos` | Definición original existente: id, cliente, venta nullable y única, tipo, descripción, importe decimal(12,2), referencia externa nullable y timestamps; índice por cliente/fecha de creación. |

No se agregaron claves foráneas hacia tablas de otros grupos. No se instalaron las migraciones de auditoría o cambio de estados: no eran necesarias para abrir estas pantallas y excedían esta corrección puntual.

## Procedimiento aplicado en MySQL

1. Se inició MySQL de XAMPP, que no estaba respondiendo en la comprobación de este turno.
2. Se comprobó conexión con `sistema_gestion` y ausencia de las tres tablas y de los dos registros de migración correspondientes.
3. Se guardaron copias de archivos y un manifiesto del estado previo.
4. Se ejecutaron las pruebas aisladas y se revisó el SQL con `--pretend`.
5. Se ejecutaron únicamente estas rutas:

```powershell
C:\xampp\php\php.exe artisan migrate --path=app/Modules/CuentaCorriente/database/migrations/2026_10_07_000000_create_cuenta_corriente_movimientos_table.php --path=app/Modules/Ventas/database/migrations/2026_10_22_000000_create_g4_sales_base_tables.php --no-interaction
```

La ejecución real se hizo desde un auxiliar local que invoca ese mismo comando de Artisan y agrega un filtro SQL previo. Solo permitió `CREATE/ALTER` sobre las tres tablas autorizadas e inserciones de los dos nombres exactos en el registro de migraciones. Cualquier otra escritura SQL se habría rechazado antes de enviarse.

Se registraron nueve escrituras autorizadas: siete sentencias de creación/índices/clave foránea sobre las tablas nuevas y dos registros en `migrations`. No hubo escrituras comerciales ni cambios sobre tablas existentes de otros grupos.

Ambas migraciones quedaron en el **lote 2**:

- `2026_10_07_000000_create_cuenta_corriente_movimientos_table`
- `2026_10_22_000000_create_g4_sales_base_tables`

Estado posterior y después de los GET de verificación:

| Tabla | Filas |
| --- | ---: |
| `venta` | 0 |
| `detalle_venta` | 0 |
| `cuenta_corriente_movimientos` | 0 |

Los movimientos que muestra el catálogo de demostración de Cuentas corrientes continúan en memoria, en `MockClienteApi`; no se insertaron en MySQL.

## Respaldo y evidencias locales

Carpeta creada antes de instalar el esquema:

```text
.tools/backups/g4-schema-20261022-203308/
```

Su contenido permite revisar y revertir estas intervenciones:

- `before-schema/`: copias de los archivos existentes inmediatamente antes de esta instalación, incluido el informe, el README y la configuración local.
- `before-g4-startup/`: versiones de `AppServiceProvider.php`, `.env.example` y `README.md` anteriores a la restauración del arranque, obtenidas de HEAD. Verificar cambios posteriores antes de restaurarlas completas.
- `manifest-before.json`: base objetivo, tablas ausentes y referencia de origen.
- `manifest-after.json`: tablas creadas, cantidades de filas, lote y SQL de escritura permitido durante la instalación.
- `verification-readonly.json`: respuestas de las tres pantallas y cantidades de filas finales.

También quedaron auxiliares locales en `.tools/`: `g4-schema-preflight.php`, `g4-schema-install.php`, `g4-verify-readonly.php` y `g4-last-backup-path.txt`. Los dos primeros comprueban e instalan este estado concreto; el instalador rechaza repetirse sobre tablas existentes. La verificación permite únicamente SQL de lectura.

`.tools` está excluida de Git. Las copias locales incluyen `.env`; no compartirlas ni agregarlas al repositorio, porque pueden contener credenciales. No había filas o tablas de G4 previas que exportar: el manifiesto registra su ausencia. El respaldo no es un volcado de datos de otros grupos.

## Verificaciones finales

```powershell
C:\xampp\php\php.exe artisan test tests/Feature/G4SalesSchemaTest.php tests/Feature/G4DependencyRegistrationTest.php tests/Feature/PresentationIntegrationTest.php --compact
```

Resultado: **19 pruebas aprobadas y 203 comprobaciones**.

La nueva suite crea exclusivamente las tres tablas de G4 en SQLite en memoria. No usa `RefreshDatabase`, no ejecuta todas las migraciones del proyecto y no crea tablas de otros grupos. Sus GET usan los controladores, las consultas y las vistas reales, sin sustituir las consultas por mocks. Comprueba que abrir las pantallas no crea operaciones.

Además se verificaron las tres peticiones con el kernel HTTP y el middleware real de la aplicación sobre MySQL local. Se usó una identidad administrativa en memoria y una sesión de prueba, sin guardar ni modificar un usuario real. El filtro SQL bloqueó cualquier escritura durante esos GET:

| Ruta | Resultado |
| --- | --- |
| `/ventas` | HTTP 200 |
| `/historial-ventas` | HTTP 200 |
| `/saldo` | HTTP 200 |

Se comprobó el plan de reversión del lote 2 con `--pretend`; no se revirtió la instalación local. La reversión efectiva se probó únicamente en la base aislada de pruebas.

Las validaciones de sintaxis, formato y diferencias también se completaron. No se realizaron commits, merges ni publicaciones.

## Límites de esta corrección

- El modo `mock` conserva clientes, productos y usuarios de ejemplo; no es integración con los datos reales de G1/G3.
- Las acciones originales que guardan ventas, pagos o notas de crédito siguen siendo operaciones persistentes. No se ejecutaron ni se certificaron esos flujos, las modificaciones de ventas, sus anulaciones/restauraciones o los cambios de stock.
- La comprobación final certifica la apertura y lectura de las tres pantallas con la base local y su esquema mínimo, no la instalación completa de todos los módulos de G4.
- El modo `http` requiere validar URLs, autenticación y contratos con los equipos antes de activarlo.

## Cómo revertir todos estos cambios

La reversión no se ejecutó. Hay dos niveles: quitar solo el esquema de esta última intervención, o quitar también la restauración previa del arranque de G4. La navegación y los cambios de frontend anteriores a esas intervenciones se conservan.

### 1. Preparación común

Detener las operaciones de la aplicación durante la reversión para evitar escrituras concurrentes. Conservar el respaldo local y este informe. Ejecutar los comandos desde la raíz de `ERP-Juan23`.

Comprobar el estado actual:

```powershell
C:\xampp\php\php.exe .tools/g4-schema-preflight.php
```

Las tres tablas deben estar vacías para usar la reversión descrita. La nueva migración comprueba también los movimientos de cuentas corrientes y **se detiene antes de borrar tablas si encuentra filas en cualquiera de las tres**.

Si ya hay operaciones, no forzar el rollback ni eliminar ese control: primero exportar las tres tablas de G4 y coordinar la conservación de esos datos con su equipo. Un respaldo de archivos PHP no conserva ventas o pagos posteriores. No utilizar `migrate:fresh`, `migrate:reset` ni un rollback general sobre esta base compartida.

### 2. Revertir únicamente las tablas nuevas

Mantener los archivos de migración hasta completar el rollback. Primero revisar el SQL:

```powershell
C:\xampp\php\php.exe artisan migrate:rollback --pretend --batch=2 --path=app/Modules/CuentaCorriente/database/migrations/2026_10_07_000000_create_cuenta_corriente_movimientos_table.php --path=app/Modules/Ventas/database/migrations/2026_10_22_000000_create_g4_sales_base_tables.php --no-interaction
```

Si las tablas siguen vacías y el lote corresponde a los dos nombres indicados, ejecutar la reversión real:

```powershell
C:\xampp\php\php.exe artisan migrate:rollback --batch=2 --path=app/Modules/CuentaCorriente/database/migrations/2026_10_07_000000_create_cuenta_corriente_movimientos_table.php --path=app/Modules/Ventas/database/migrations/2026_10_22_000000_create_g4_sales_base_tables.php --no-interaction
```

Se elimina primero `detalle_venta`, luego `venta` y después `cuenta_corriente_movimientos`. Laravel retira solamente los dos registros correspondientes de `migrations`. No se toca el lote 1 ni se desactivan claves foráneas para forzar eliminaciones.

El número 2 es el de esta instalación concreta. En otra base, verificar el lote real antes de copiar el comando.

Después del rollback, se pueden quitar los dos archivos nuevos de esta fase:

```powershell
Remove-Item -LiteralPath 'app/Modules/Ventas/database/migrations/2026_10_22_000000_create_g4_sales_base_tables.php', 'tests/Feature/G4SalesSchemaTest.php'
```

Para recuperar el README inmediatamente anterior a esta fase, siempre que no tenga modificaciones posteriores que quieras conservar:

```powershell
Copy-Item -LiteralPath '.tools/backups/g4-schema-20261022-203308/before-schema/README.md' -Destination 'README.md'
```

Conservar la migración original de Cuentas corrientes, que ya existía antes de este trabajo. Mantener el informe como registro, o consultar su copia anterior en `before-schema/docs/` si se necesita comparar.

Resultado de este nivel: se mantiene la reparación de dependencias, pero Historial y Cuentas corrientes volverán a fallar por falta de tablas. Es el estado previo a la instalación del esquema.

### 3. Revertir también la restauración del arranque de G4

Hacer primero el paso anterior. Después revisar los archivos a restaurar: si recibieron cambios posteriores, comparar las copias y retirar únicamente los bloques introducidos por esta intervención, para conservar el trabajo de otros equipos.

Las copias anteriores al arranque permiten restaurar los tres archivos modificados en esa etapa:

```powershell
Copy-Item -LiteralPath '.tools/backups/g4-schema-20261022-203308/before-g4-startup/app/Providers/AppServiceProvider.php' -Destination 'app/Providers/AppServiceProvider.php'
Copy-Item -LiteralPath '.tools/backups/g4-schema-20261022-203308/before-g4-startup/.env.example' -Destination '.env.example'
Copy-Item -LiteralPath '.tools/backups/g4-schema-20261022-203308/before-g4-startup/README.md' -Destination 'README.md'
```

En `.env`, retirar únicamente `VENTAS_INTEGRACION=mock` y el comentario de integración que se agregó junto a esa línea. Antes de la restauración no existía esa variable. Conservar las credenciales y cualquier cambio posterior del entorno; no reemplazar todo `.env` para retirar una sola variable.

Retirar los archivos que se agregaron para esa restauración, si no fueron modificados o adoptados posteriormente por otros integrantes:

```powershell
Remove-Item -LiteralPath 'config/ventas.php', 'app/Services/MockClienteApi.php', 'app/Services/MockProductoApi.php', 'app/Services/MockUsuarioApi.php', 'resources/views/ventas.blade.php', 'resources/views/historial-ventas.blade.php', 'resources/views/saldo.blade.php', 'tests/Feature/G4DependencyRegistrationTest.php'
```

No eliminar `MockStockApi`, los adaptadores HTTP, los controladores ni las vistas dentro de sus subcarpetas: ya existían y no fueron agregados en esa etapa. La suite `PresentationIntegrationTest.php` pertenece al frontend anterior y también se conserva.

Limpiar las cachés después de los cambios de archivos:

```powershell
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan view:clear
```

Resultado de la reversión completa: las tablas nuevas desaparecen y el arranque de G4 vuelve al estado anterior a la reparación, incluido el error de dependencias que motivó estas intervenciones. No se revierte código ajeno con `git reset` ni se restauran masivamente directorios del proyecto.

Los auxiliares `.tools/g4-*` pueden conservarse como evidencia. No ejecutar nuevamente `g4-schema-install.php` después de una reversión, salvo que se quiera reinstalar el mismo esquema. Conservar la carpeta del respaldo mientras se necesite poder comparar o recuperar archivos.

MySQL de XAMPP se inició para esta comprobación. Su apagado es independiente del rollback de archivos y tablas; puede detenerse desde XAMPP cuando ningún equipo lo esté usando. El rollback no lo detiene automáticamente.
