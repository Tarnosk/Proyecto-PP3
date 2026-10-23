# Integración del frontend

## Cambios

- El panel y las pantallas internas de G1, G2, G3 y G4 usan un único menú lateral fijo, incluidos los tamaños pequeños. El relevamiento de APIs de G1 quedó en el chat.

- El layout compartido admite `title/content` (G1 y G4), `titulo/contenido` (G2), estilos y scripts mediante secciones y stacks de Blade.
- Se reemplazaron enlaces a archivos `.html` ausentes por rutas nombradas y se comparte la navegación entre el panel, G1, G2, G3 y G4. El menú respeta los roles existentes.
- `/ventas` abre el controlador de presentación del POS de G4; se eliminó la vista provisoria de G1 que lo reemplazaba al cargar las rutas. `/stock` redirige a `/inventario`.
- Las rutas web de Ventas, Historial y Cuentas corrientes requieren autenticación, sesión vigente y el rol correspondiente. No se modificaron sus controladores ni APIs.
- `/promociones` carga la vista aportada por G4. Se recuperaron su JavaScript y sus estilos desde las referencias locales `origin/develop-g4` y `origin/feature/g4-nicole-schwab`; el JavaScript coincide entre ambas. Se sirve como archivo público, sin requerir un manifiesto de Vite inexistente. Conserva el contrato HTTP y el catálogo de ventas que usa el frontend original de G4.

## Conexión de G2

Se registraron sus rutas web y endpoints de sesión bajo `/g2/api`, se conectó su identidad al login de G1 y se recuperaron sus vistas de Entregas/Rendiciones. Caja y Conciliación conservan sus vistas con el mismo menú global. Las modificaciones, dependencias pendientes, pruebas y pasos para revertir esta etapa están en el [informe de integración de G2](informe-integracion-operativa-g2.md).

La base local todavía no contiene las tablas de G2: las cuatro pantallas devuelven un aviso controlado HTTP 503 con el menú visible. No se instalaron tablas ni se cargaron datos. La conexión con Ventas necesita URL/autenticación y acuerdo sobre estados/pagos; la conciliación automática necesita que G2 complete su origen de cobros. No se presenta esta etapa como una validación de operaciones reales.
## Verificación

```powershell
C:\xampp\php\php.exe artisan test tests/Feature/PresentationIntegrationTest.php
```

Esta suite verifica presentación, navegación, formularios, estilos, scripts y permisos. Usa usuarios en memoria y datos de vista de prueba; no ejecuta migraciones, seeders ni escrituras de operaciones comerciales. El adaptador de identidad de G3 se sustituye únicamente en estas pruebas para evitar que cree usuarios en su esquema.

Las pruebas no validan el registro real de ventas, pagos, promociones ni movimientos de stock. Esas operaciones dependen de los backends de cada grupo y no se ejecutan contra los datos compartidos como parte de este trabajo.
