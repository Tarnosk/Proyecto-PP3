# Estructura modular del proyecto

La organización toma como referencia el patrón de clases funcionales de [`develop-g3`](https://github.com/rapiriz/ERP-Juan23/tree/2f486f04653e621ae6dbd327dc142873d3222de5/app/Modules), revisado el 20/10/2026. G1 aplica ese mismo patrón a su autenticación, agrupada en el módulo `Login`.

## Criterio común

Todos los módulos siguen estas reglas:

- Las clases PHP propias de una función se ubican en `app/Modules/{Modulo}/` con namespace `App\Modules\{Modulo}`.
- Se crean las subcarpetas que contienen implementación: `Controllers`, `Models`, `Requests`, `Services`, `Enums`, `Policies`, `Resources`, `Middleware` o `Mail`.
- Las rutas web y API permanecen en `routes/web.php` y `routes/api.php`, con imports de los controladores de cada módulo.
- Las vistas permanecen en `resources/views/`, con sus nombres existentes como `auth.login`, `clientes.index` o `reports.index`.
- Las migraciones, factories y seeders permanecen en `database/`. Las tres migraciones históricas conservan ubicación, nombre y esquema.
- Los assets se mantienen en `public/assets/` y las pruebas en `tests/Feature/`.

`Login` no tiene rutas, vistas, migraciones, factories ni seeders internos: utiliza las ubicaciones centrales igual que `Clientes`. La composición de las carpetas de clases varía según las funciones reales; Login requiere correo y middleware, mientras Clientes requiere políticas y un recurso JSON.

## Distribución de clases

| Módulo | Clases y responsabilidades |
| --- | --- |
| `Login` | Controladores web/API de autenticación y usuarios; modelos `User`, `LoginLog` y `PasswordResetCode`; servicios de autenticación y tokens; roles y estados de usuario; validaciones; middleware de sesiones y permisos; correo de recuperación. |
| `Clientes` | Controladores web/API de clientes y zonas; modelos `Cliente` y `Zona`; validaciones; política; recurso JSON; enums de tipo y estado. |
| `Reclamos` | Controladores web/API; modelo `Reclamo`; validación; política; enums de prioridad y estado. |
| `Reportes` | Controladores y validación de reportes; servicios de consultas, preparación de datos y exportación Excel. |
| `Productos` | Modelos históricos `Producto`, `Categoria` y `Marca`, y enum de estado. |
| `Ventas` | Modelos históricos `Venta`, `DetalleVenta` y `Cobro`, y enums de ventas y pagos. |

Los modelos históricos de Productos y Ventas conservan las relaciones y los datos utilizados por las pruebas. Las pantallas, los reportes y la API de G1 siguen consultando ventas, catálogo, cobros y saldos mediante las APIs externas existentes.

## Elementos compartidos

- `app/Http/Controllers/Controller.php` y `DashboardController.php` siguen como base y panel compartidos.
- `app/Services/Gateways/` y `app/Contracts/` contienen los adaptadores e interfaces HTTP usados por varios módulos.
- `AppServiceProvider` registra las políticas de Clientes y Reclamos y la implementación del contrato de ventas.
- `config/auth.php` referencia `App\Modules\Login\Models\User`.
- `bootstrap/app.php` registra los alias de middleware usando las clases de `Login\Middleware`.
- La configuración PSR-4 existente de Composer (`App\` hacia `app/`) carga las clases de todos los módulos. No hace falta agregar un proveedor para descubrir rutas o vistas internas, porque estas siguen centralizadas.
- Cada modelo con `HasFactory` declara explícitamente su factory central mediante `UseFactory`. Las factories apuntan al modelo del módulo correspondiente.

La reorganización conserva las URLs, nombres de rutas, middleware, tablas, columnas, permisos, vistas y contratos JSON existentes. La conexión a la base de datos sigue definida en `.env`.

## Verificación y ejecución

Desde la raíz del proyecto:

```powershell
C:\xampp\php\php.exe artisan test
C:\xampp\php\php.exe artisan route:list --except-vendor
```

Las pruebas utilizan SQLite en memoria. La configuración de PHPUnit incluye la suite Feature existente; se retiró la referencia a `tests/Unit`, carpeta ausente en esta copia.

Con MySQL encendido en XAMPP, el servidor se inicia con:

```powershell
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```
