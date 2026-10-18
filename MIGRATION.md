# Informe de migración a Laravel

## Alcance

El sistema procedural original fue migrado a Laravel 12 conservando sus módulos, reglas de negocio, diseño y estructura de datos. La implementación anterior se mantiene únicamente como referencia en `legacy/`; la aplicación activa se sirve desde `public/index.php`.

## Módulos migrados

- Autenticación por usuario y contraseña.
- Bloqueo temporal después de tres intentos fallidos.
- Registro de accesos exitosos en `login_logs`.
- Sesión única por cuenta y expiración por inactividad a las dos horas.
- Panel diferenciado para administrativo, repartidor y contador.
- Alta de clientes por administrativo.
- Listado y búsqueda de clientes por administrativo y repartidor.
- Edición y cambio de estado de clientes por administrativo.
- Alta y listado de reclamos por administrativo y repartidor.
- Pantallas preparadas de ventas, pedidos, inventario y stock.

## Correspondencia de archivos

| Implementación anterior | Implementación Laravel |
| --- | --- |
| `auth/authenticate.php` | `AuthenticatedSessionController`, `AuthenticationService`, `LoginRequest` |
| `auth/require_auth.php` | middleware `auth`, `EnsureCurrentSession`, `EnsureRole` y Policies |
| `clientes/*.php` | `ClienteController`, Form Requests, `ClientePolicy` y vistas Blade |
| `reclamos/*.php` | `ReclamoController`, `StoreReclamoRequest`, `ReclamoPolicy` y vistas Blade |
| `dashboard.php` | `DashboardController` y `dashboard.blade.php` |
| `config/database.php` | `.env` y `config/database.php` de Laravel |
| `database/database.sql` | migration `0001_01_01_000000_create_erp_tables.php` |
| `assets/*` | `public/assets/*` |

## Base de datos y relaciones

Se conservaron las tablas `usuarios`, `clientes`, `login_logs` y `reclamos`, sus columnas, tipos, índices, claves únicas y claves foráneas. Los modelos Eloquent incluyen estas relaciones:

- Usuario tiene clientes creados, reclamos y registros de login.
- Cliente pertenece a su creador y tiene reclamos.
- Reclamo pertenece a un cliente y a un usuario.
- LoginLog pertenece a un usuario.

La migration reconoce tablas existentes para incorporarlas como línea base sin borrar sus datos. En una base vacía crea el esquema completo.

## Correcciones y seguridad

- Consultas PDO manuales sustituidas por Eloquent y Query Builder parametrizado.
- Validación centralizada en Form Requests y mantenida también en el frontend.
- Protección CSRF nativa en todos los formularios que modifican datos.
- Logout cambiado de GET a POST.
- Identificador de sesión persistido como hash SHA-256 en lugar del ID de sesión sin protección.
- Regeneración de sesión después del login e invalidación completa al cerrar sesión.
- Cookies HTTP-only y SameSite configuradas explícitamente.
- Mass Assignment limitado mediante `$fillable` y asignación explícita de propietario, estado y usuario.
- Autorización por middleware de rol y Policies.
- Mensaje genérico para usuario inexistente o inactivo.
- Codificación UTF-8 y escape automático de Blade contra XSS.

## Validaciones incorporadas

- Campos requeridos, tipos, máximos y formatos.
- Email RFC y unicidad de email.
- Formato y unicidad de DNI/CUIT.
- Formato de teléfono.
- Valores permitidos para rol, estado, tipo de cliente y prioridad.
- Existencia y estado activo del cliente asociado a un reclamo.
- Exclusión del propio registro en validaciones únicas durante una edición.

## Pruebas

Se crearon 19 pruebas Feature con 106 verificaciones para login, bloqueo, usuarios inactivos, logout, sesión única, expiración, permisos por rol, búsqueda, alta y edición de clientes, duplicados, registros inexistentes, alta/listado de reclamos y datos inválidos.

Resultado verificado:

```text
Tests: 19 passed (106 assertions)
```

También se verificaron `artisan route:list`, `migrate:fresh --seed --env=testing` y Laravel Pint.
