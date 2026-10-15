# Contratos pendientes para Sprint 4 de G1

G1 implementa las pantallas de [#113](https://github.com/rapiriz/ERP-Juan23/issues/113) y [#114](https://github.com/rapiriz/ERP-Juan23/issues/114). La issue #120 queda pendiente, sin implementacion. No hay datos de ejemplo ni fallback local en tiempo de ejecucion para estos reportes.

Las rutas de abajo son **contratos propuestos**, no APIs confirmadas ni publicadas por los otros grupos. G1 envia `Authorization: Bearer <token>` y `Accept: application/json`. Cada respuesta exitosa debe contener `data`. Importes son cadenas decimales en ARS, fechas `YYYY-MM-DD` o ISO 8601. Los IDs de clientes deben coincidir con `clientes.id` de G1. Se deben acordar rutas, autenticacion, filtros, paginacion y semantica de estados antes de conectar entornos productivos.

| Dueno | Metodo y ruta esperada | Uso en G1 | Campos requeridos en `data` |
| --- | --- | --- | --- |
| G4 | `GET /api/v1/reportes/ranking-productos?desde=YYYY-MM-DD&hasta=YYYY-MM-DD` | Ranking #113 | Lista de `{producto_id, producto_nombre, cantidad_vendida, monto_facturado}` |
| G4 | `GET /api/v1/clientes/deudores` | Saldos vigentes para #114 | Lista de `{cliente_id, saldo}` con `saldo > 0` y un cliente por fila |
| G4 | `GET /api/v1/clientes/{id}/saldo` | Perfil de cliente y API G1 existente | Objeto `{cliente_id, saldo, moneda: "ARS"}` |
| G2 | `GET /api/v1/facturas/pendientes` | Detalle de deuda #114 | Lista de `{id, cliente_id, numero, vencimiento, importe_pendiente}` |
| G2 | `GET /api/v1/cobros?cliente_id={id}` | Historial del cliente y API G1 existente | Lista de `{id, cliente_id, fecha, monto_total, medio_pago, comprobante_nro?, observaciones?}` |

El ranking debe contabilizar solo ventas validas para facturacion, excluir canceladas y devolver `[]` cuando el periodo no tenga movimientos. G4 debe definir si `desde` y `hasta` son inclusivos; G1 los envia tal cual. El reporte de deudores toma el **saldo total de G4**, no suma las facturas de G2 para calcularlo. Facturas y saldos pueden tener momentos de actualizacion diferentes; G2 y G4 deben acordar consistencia e identificadores.

Configurar `SALES_SERVICE_URL` y `SALES_SERVICE_TOKEN` para G4 (ranking), `BALANCE_SERVICE_URL` y `BALANCE_SERVICE_TOKEN` para G4 (saldos), y `BILLING_SERVICE_URL` y `BILLING_SERVICE_TOKEN` para G2. Las URL son bases, por ejemplo `https://servicio.example`; G1 agrega las rutas anteriores. No guardar tokens reales en el repositorio. Luego ejecutar `php artisan config:clear`.

Cuando falta URL o token, la pantalla explica la dependencia pendiente y la API de G1 responde `503`. Si el servicio no responde, `503`; si responde con error o estructura invalida, `502`. Una respuesta `data: []` exitosa representa ausencia real de registros, no un fallo de integracion. No hay mocks en el codigo de produccion; las pruebas usan `Http::fake()` solo para verificar el contrato.

El alta local de cobros de G1 quedo deshabilitada. Sus tablas historicas permanecen para no destruir datos preexistentes, pero G1 no las usa como fuente de verdad. Para registrar cobros en la aplicacion sera necesario acordar con G2 un endpoint de escritura, permisos, validaciones e idempotencia; esa operacion no se habilita sin contrato confirmado.
