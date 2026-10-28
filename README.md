# ERP Juan23

## Entorno local integrado (27/10/2026)

Por autorización del usuario se instalaron los esquemas locales de G2, G3 y G4 en `sistema_gestion`, conservando los registros existentes. La base tiene 61 tablas, 11 clientes y cinco productos reales con stock, precios iniciales y movimientos de ingreso. El retiro anterior de G3 queda como antecedente histórico: esta instalación fue autorizada nuevamente.

Para trabajar con las APIs de G1 y el Catálogo, iniciar **MySQL** en XAMPP y ejecutar desde la raíz del proyecto:

```powershell
C:\xampp\php\php.exe -d extension=gd -d extension=zip artisan serve
```

El Catálogo usa `CATALOG_TRANSPORT=internal` en este entorno: consulta la ruta API de Productos dentro de Laravel, sin una llamada HTTP al mismo proceso. Apache es opcional para este circuito. No iniciar Apache y `artisan serve` al mismo tiempo en 8000.

Abrir `http://127.0.0.1:8000` para entrar al ERP. El Catálogo público está en `/catalogo`; su API JSON de G1 está en `/api/v1/catalogo` y `/api/v1/catalogo/{id}`. En modo interno consumen la API real de Productos registrada en la copia local. Para un proveedor remoto, usar `CATALOG_TRANSPORT=http` y configurar su URL.

La pantalla `/inventario` pertenece a G3 y ahora dispone de su esquema local. Los archivos originales de negocio de los otros equipos se conservaron. La instalación de tablas no certifica todos sus circuitos financieros y comerciales.

Para apagar `artisan serve`, pulsar **Ctrl+C** en su terminal. Si se elige Apache, detenerlo desde XAMPP. En esta última revisión se cerraron los servidores web de prueba; no quedó la web iniciada.

Configuración de XAMPP, pruebas y reversión: [informe de puerto único](docs/informe-apache-puerto-unico-g1.md).

APIs verificadas, permisos, pruebas POST/PUT/PATCH y ejemplos manuales: [APIs funcionales de G1](docs/apis-funcionales-g1.md). La adaptación interna cubre Productos para el Catálogo y consumidores de catálogo de G1. Otros consumidores HTTP —por ejemplo Ventas en reportes— siguen necesitando un proveedor que pueda atender la consulta; si apuntan al mismo proceso único de PHP, usar Apache o un proveedor separado para ese circuito.

No ejecutar migraciones globales, `migrate:fresh`, `migrate:refresh`, semillas globales ni rollback general como paso de arranque. Se aplicaron rutas seleccionadas y dependencias ordenadas; la semilla de seis productos de G3 se excluyó para cargar únicamente los cinco solicitados. Fuentes, compatibilidad, pruebas y reversión: [informe de instalación local G2/G3/G4](docs/informe-instalacion-local-g2-g3-g4.md).

El 28/10/2026 se retiraron, por pedido del usuario, las nueve migraciones nuevas de esa instalación local (compatibilidad de stock, siete importadas de G2 y promociones de G4). Se conservaron las migraciones previamente versionadas y la base local existente, sin rollback. Esos archivos locales no se compartirán con el código de G1; esta copia no permite reproducir por sí sola el esquema integrado desde cero. Detalles y respaldo: [APIs funcionales de G1](docs/apis-funcionales-g1.md#qué-cambia-con-commitpush-y-cómo-revertir).

Sistema web de gestión comercial en Laravel. Incluye autenticación segura, recuperación de contraseña por email, permisos por rol, clientes, reclamos y reportes. Los cobros y facturas pertenecen a G2; los saldos y ventas, a G4.

## Catálogo público de G1

Consulta sin iniciar sesión en `/catalogo`, con búsqueda, filtros, disponibilidad y detalle. Consume las APIs de Productos de G3 sin agregar tablas ni modificar datos de otros grupos. Si el proveedor no está conectado, muestra su estado de indisponibilidad.

Configuración, alcance, pruebas y reversión: [informe de implementación del Catálogo](docs/informe-implementacion-catalogo-g1.md).

## Actualización de Clientes de G1 (27/10/2026)

El alta y la edición incluyen selector DNI/CUIT, localidad y zona (existente o nueva), calle y altura separadas, y validaciones en navegador y servidor. Se retiró el archivo de migración Sprint 3 sin ejecutar rollback ni borrar tablas. Su parte de Clientes/Zonas de G1 y los nuevos campos quedaron en `2026_10_27_000001_complete_g1_clientes_schema.php`: agrega solo lo que falte y conserva los datos existentes. Detalles, ejecución limitada a G1, pruebas y reversión: [informe de Clientes](docs/informe-clientes-campos-validacion-g1.md).

## Requisitos

- PHP 8.2 o superior.
- Laravel 12.x.
- Composer 2.x.
- MySQL 8 o MariaDB 10.4 o superior.
- Extensiones PHP: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer`, `xml` y `zip`.

El proyecto fue verificado con PHP 8.2.12, Laravel 12.69.2 y MariaDB 10.4.32 de XAMPP.

En XAMPP, habilitar `extension=gd` y `extension=zip` en `C:\xampp\php\php.ini`. Como alternativa, los comandos de este README que generan PDF o Excel incluyen ambas extensiones mediante `-d`.

## Instalación

1. Instalar las dependencias:

   ```bash
   composer install
   ```

   En este equipo también puede usarse el Composer local ya descargado:

   ```powershell
   C:\xampp\php\php.exe -d extension=gd -d extension=zip .tools\composer.phar install
   ```

2. Crear el archivo de entorno:

   ```powershell
   Copy-Item .env.example .env
   ```

3. Generar la clave de la aplicación:

   ```powershell
   C:\xampp\php\php.exe artisan key:generate
   ```

4. Configurar MySQL en `.env`:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sistema_gestion
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Iniciar MySQL desde XAMPP.

## Base de datos

### Instalación nueva

Preparar el esquema aprobado en una base propia, con respaldo y una selección explícita y ordenada de migraciones. No utilizar una migración o semilla global: el proveedor modular descubre también archivos de otros equipos. La instalación local actual de G2/G3/G4 y las dependencias de su secuencia se documentan en [el informe de instalación](docs/informe-instalacion-local-g2-g3-g4.md).

### Base existente del proyecto

La base `sistema_gestion` ya está preparada; no necesita migraciones o semillas como paso de arranque. Cualquier modificación posterior requiere alcance autorizado y una ruta explícita revisada, preservando datos existentes. No volver a importar SQL históricos completos ni reinstalar tablas ya existentes.

La migration de recuperación agrega un email opcional y único a `usuarios`. El administrador puede asignar los emails reales desde **Lista de Usuarios** en su panel, sin utilizar phpMyAdmin.

`database/database.sql` y las antiguas migraciones SQL se conservan como documentación del esquema previo.

## Ejecución

Las pantallas internas usan el menú lateral compartido. El relevamiento de APIs de G1 quedó en el chat. La conexión de G2, sus requisitos pendientes y la reversión se detallan en [informe de integración de G2](docs/informe-integracion-operativa-g2.md).

Para las APIs de G1 y el Catálogo, iniciar MySQL en XAMPP y ejecutar `artisan serve`, según las instrucciones iniciales. También puede usarse Apache, que sirve exclusivamente `ERP-Juan23/public`.

Abrir:

```text
http://127.0.0.1:8000
```

Laravel debe servirse desde `public/`. El Catálogo interno ya no depende de concurrencia HTTP. Mantener el modo HTTP contra el mismo proceso de `artisan serve` sí puede bloquear una consulta; esa configuración no se utiliza para el Catálogo local.

### Ventas, Historial y Cuentas corrientes: modo de integración

La configuración de G4 está restaurada en modo de prueba:

```dotenv
VENTAS_INTEGRACION=mock
```

Este modo usa los catálogos originales de clientes y productos de G4 y simula la integración de stock. **No convierte las ventas, el historial ni los pagos en operaciones ficticias**: Historial y Cuentas corrientes consultan MySQL y las acciones de guardar conservan las escrituras originales de G4. Para revisar las pantallas, iniciar MySQL desde XAMPP; no confirmar operaciones con datos de prueba sobre una base compartida.

Después de cambiar `VENTAS_INTEGRACION`, ejecutar `C:\xampp\php\php.exe artisan config:clear`. El modo `http` queda preparado en el registro de dependencias, pero requiere validar URLs, autenticación y contratos con cada grupo antes de activarlo.

Detalle de la corrección y verificaciones: [informe de integración de G4](docs/informe-correccion-integracion-g4.md).

La instalación de tablas y la administración de datos de G4 corresponden a su equipo; G1 consume sus APIs. Las tablas `venta`, `detalle_venta` y `cuenta_corriente_movimientos` que ya existían en este entorno se conservaron sin cambios durante el retiro de G3. Su instalación previa se mantiene documentada como antecedente en el informe; no se indica repetirla ni revertirla desde G1.

## Usuarios de prueba

Estas credenciales son solo para desarrollo local y se crean con `artisan db:seed`.

| Usuario | Contraseña | Rol |
| --- | --- | --- |
| admin | Admin1234 | administrativo |
| repartidor | Repartidor1234 | repartidor |
| contador | Contador1234 | contador |
| inactivo | Inactivo1234 | repartidor inactivo |

Los usuarios sembrados usan direcciones `example.com`. Deben reemplazarse por emails reales para probar el envío mediante SMTP.

## Permisos

Los reportes Sprint 4 de deudores y ranking estan preparados para consumir JSON de G2/G4. Hasta que esos grupos confirmen y publiquen las APIs, muestran integracion pendiente. Ver [contratos-sprint4-g1.md](docs/contratos-sprint4-g1.md) para rutas, campos y variables `BILLING_SERVICE_*`, `BALANCE_SERVICE_*` y `SALES_SERVICE_*`.

| Funcionalidad | Administrativo | Repartidor | Contador |
| --- | --- | --- | --- |
| Panel propio | Sí | Sí | Sí |
| Listar y buscar clientes | Sí | Sí | No |
| Crear y editar clientes | Sí | No | No |
| Crear y listar reclamos | Sí | Sí | No |
| Ver historial de clientes | Sí | Sí | No |
| Consultar cobros y saldos de otros módulos | Sí | Sí | No |
| Reportes de clientes y ranking comercial | Sí | No | Sí |
| Exportar historial de clientes a PDF y Excel | Sí | No | Sí |
| Secciones de ventas y stock sin consultas de G1 | Sí | Sí | No |
| Configurar emails de usuarios | Sí | No | No |

## Seguridad de acceso

- Tres contraseñas incorrectas bloquean la cuenta durante 15 minutos.
- Cada cuenta admite una sola sesión activa.
- La sesión expira después de 120 minutos sin actividad.
- El logout utiliza POST con protección CSRF.
- Las contraseñas usan hashing seguro de Laravel.
- Los identificadores de sesión guardados en la base están hasheados.
- Los permisos se verifican en middleware, Policies y Form Requests.
- La recuperación usa códigos numéricos hasheados que vencen en 15 minutos y admiten hasta 5 intentos.

## Recuperación de contraseña por email

En desarrollo, `MAIL_MAILER=log` guarda el mensaje y su código en `storage/logs/laravel.log`, pero no envía un correo real. Para usar un servidor SMTP, configure en `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_SCHEME=null
MAIL_USERNAME=usuario_smtp
MAIL_PASSWORD=contrasena_smtp
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="Varela ERP"
```

Después de modificar `.env`, limpiar la configuración:

```powershell
C:\xampp\php\php.exe artisan config:clear
```

## Pruebas y comprobaciones

Ejecutar la suite automatizada:

```powershell
C:\xampp\php\php.exe -d extension=gd -d extension=zip artisan test
```

Comprobar una instalación limpia con SQLite en memoria, sin tocar MySQL:

```powershell
C:\xampp\php\php.exe artisan migrate:fresh --seed --env=testing --force
```

Revisar rutas y formato:

```powershell
C:\xampp\php\php.exe artisan route:list --except-vendor
C:\xampp\php\php.exe vendor\bin\pint --test
```

La suite incluye pruebas de seguridad, clientes, reclamos, integración de cobros y saldos, reportes y exportaciones.

## Estructura principal

```text
app/
  Http/
    Controllers/  Controlador base y panel principal compartido
  Modules/
    Login/        Controllers, Models, Requests, Services, Enums, Middleware y Mail
    Clientes/     Controllers, Models, Requests, Enums, Policies y Resources
    Reclamos/     Controllers, Models, Requests, Enums y Policies
    Reportes/     Controllers, Requests y Services
    Productos/    Models y Enums del esquema histórico
    Ventas/       Models y Enums del esquema histórico
  Contracts/      Interfaces compartidas entre módulos
  Providers/      Registro de servicios y políticas
  Services/
    Gateways/     Clientes HTTP compartidos para APIs de otros grupos
database/
  factories/      Datos para pruebas
  migrations/     Esquema de base de datos
  seeders/        Usuarios de desarrollo
public/assets/    CSS y JavaScript conservados
resources/views/ Vistas Blade
routes/web.php    Rutas web con nombre
routes/api.php    Rutas JSON de integración
tests/Feature/    Pruebas automatizadas
```

Todos los módulos, incluido `Login`, siguen el mismo criterio: las clases PHP se agrupan en `app/Modules/{Modulo}/`, y las rutas, vistas, migraciones, factories, seeders y assets permanecen en sus carpetas generales. Cada módulo contiene las subcarpetas necesarias para su funcionalidad. Ver [estructura-modular.md](docs/estructura-modular.md) para la distribución y el alcance.

## Configuración de producción

Configurar credenciales reales en `.env`, utilizar `APP_DEBUG=false`, habilitar HTTPS y definir `SESSION_SECURE_COOKIE=true`. Nunca subir `.env` al repositorio.

El detalle completo de la migración está en [MIGRATION.md](MIGRATION.md).

## Integración entre grupos

La API JSON de G1 y los contratos HTTP esperados de G2, G3 y G4 se documentan en [docs/integracion-microservicios.md](docs/integracion-microservicios.md). No existe un modo de datos locales para ventas o productos externos. El login API es publico; las demas rutas aceptan un token de usuario o, segun sus permisos, `INTERNAL_API_TOKEN`. Ningun token debe incluirse en el repositorio.
