# Módulo Promociones (G4)

```text
app/Modules/Promociones/
├── Controllers/   PromocionController (ABM de promociones y cambio de estado)
├── Models/        Promocion (tabla "promocion")
└── routes/
    └── api.php    /api/promociones/...
```

Las rutas las carga solo `App\Providers\ModuleServiceProvider`.

Quedan afuera del módulo:
- La pantalla `/promociones`, que está en `routes/web.php` y la atiende `HomeController`.
- La vista `resources/views/promociones.blade.php` y el JS `resources/js/promociones.js`, porque usan el layout y Vite compartidos.

⚠ Bug conocido (ya existía antes de reorganizar): `PromocionController` llama a `VentaController::productosSimulados()`, que ya no existe. Fallan `show` y la validación de productos en `store` y `update`. Habría que usar `MockProductoApi`, o el contrato `ProductoGateway` del módulo Ventas.
