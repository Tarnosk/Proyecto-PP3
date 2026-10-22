# Módulo Ventas (G4)

Todo lo funcional de **Ventas** vive en esta carpeta. Guía completa (estados, endpoints, errores y cómo probar): [`docs/VENTAS-BACKEND.md`](../../../docs/VENTAS-BACKEND.md).

## Estructura

```text
app/Modules/Ventas/
├── Controllers/        VentaController (POS, alta, historial) · VentaApiController (API: listar, ver, modificar, anular, restaurar, estados)
├── Models/             Venta · DetalleVenta · VentaHistorial
├── Services/           VentaService (reglas de negocio) · CalculadoraVenta (cuenta del total)
├── Requests/           Validaciones de cada pedido (Modificar, Anular, CambiarEstado, Listar)
├── Resources/          VentaResource (formato JSON de una venta)
├── Enums/              EstadoVenta (los 7 estados y sus reglas)
├── Exceptions/         Errores de negocio (404 / 409 / 503)
├── Contracts/          ProductoGateway · ClienteGateway · StockGateway (contratos con otros módulos)
├── Integraciones/      MockStockApi (activo) · Http*Api (APIs reales, apagadas)
├── Logging/            VentaLogger (paso a paso en storage/logs/ventas/)
├── routes/
│   ├── web.php         /ventas, /historial-ventas
│   └── api.php         /api/ventas/...
└── database/
    └── migrations/     venta_historial · estados preparada/entregada
```

## Cómo se carga

`App\Providers\ModuleServiceProvider` (registrado en `bootstrap/providers.php`) carga **solo**:
- `routes/web.php` con el middleware `web`.
- `routes/api.php` con el prefijo `/api` y el middleware `api`.
- Las migraciones de `database/migrations/`.

No hace falta tocar `routes/web.php` ni `routes/api.php` globales.

## Lo que queda afuera del módulo (y por qué)

| Qué | Dónde | Por qué |
|---|---|---|
| Vistas `ventas.blade.php` e `historial-ventas.blade.php` | `resources/views/` | Usan el layout compartido; moverlas obligaría a cambiar el código de los controllers |
| `config/ventas.php` | `config/` | Laravel solo lee la configuración desde `config/` |
| Mocks de clientes, productos y usuarios | `app/Services/` | Representan a OTROS módulos y también los usan Promociones y Cuenta Corriente |
| Interruptor mock/http de los contratos | `app/Providers/AppServiceProvider.php` | Es configuración global de la app |
| Tests | `tests/Unit/Ventas`, `tests/Unit/Integraciones`, `tests/Feature/Ventas`, `tests/Feature/VentasApiTest.php` | PHPUnit los busca en `tests/` |
