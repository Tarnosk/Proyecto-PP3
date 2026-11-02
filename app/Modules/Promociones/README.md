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

`promocion.total` guarda el precio final de los productos incluidos, con el descuento aplicado. `tipo_descuento` y `valor_descuento` conservan si se eligió porcentaje o monto fijo y su valor exacto, para mostrarlo y editarlo sin inferencias.
