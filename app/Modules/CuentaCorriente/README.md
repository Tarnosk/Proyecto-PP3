# Módulo Cuenta Corriente (G4)

```text
app/Modules/CuentaCorriente/
├── Controllers/            CuentaCorrienteController (saldos, pagos y notas de crédito)
├── routes/
│   └── web.php             /saldo, /saldo/{cliente}/movimientos
└── database/
    └── migrations/         cuenta_corriente_movimientos
```

Las rutas y la migración las carga solo `App\Providers\ModuleServiceProvider`.

Quedan afuera del módulo:
- La vista `resources/views/saldo.blade.php`, porque usa el layout compartido.
- `config/cuenta_corriente.php`, porque Laravel solo lee la configuración desde `config/`. También la usa Ventas.
- Los clientes salen de `App\Services\MockClienteApi` (mock del módulo Clientes) hasta que se integre.

El módulo Ventas escribe en `cuenta_corriente_movimientos` al crear, modificar, anular y restaurar ventas (ver `docs/VENTAS-BACKEND.md`).
