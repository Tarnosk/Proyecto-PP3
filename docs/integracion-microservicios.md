# Integracion entre grupos

Contrato detallado de campos, respuestas JSON y estados HTTP de cada ruta publicada: [contrato-api-g1.md](contrato-api-g1.md).

El Grupo 1 publica JSON en `/api/v1`. Las rutas de `routes/web.php` son pantallas HTML y no forman parte de este contrato. Los IDs son enteros positivos. Fechas y horas se expresan en ISO 8601 con zona horaria; importes en ARS usan cadenas decimales con dos cifras, sin separadores de miles. Las respuestas de listas usan `data` y, cuando hay paginacion, `meta`.

## Autenticacion

La API acepta dos tipos de token en `Authorization: Bearer ...`:

- **Token interno de servicio** (`INTERNAL_API_TOKEN`): para consultas y reclamos entre servicios. No autoriza altas ni cambios de clientes.
- **Token de usuario**: lo entrega `POST /api/v1/auth/login`. Administrativo y repartidor pueden consultar clientes, zonas y reclamos; solo administrativo puede consultar usuarios o crear y actualizar clientes. `POST /api/v1/auth/logout` lo revoca. Es valido durante 120 minutos desde su emision, o el valor de `SESSION_LIFETIME` si se configura otro.

Cada llamada autenticada debe incluir:

```http
Authorization: Bearer <TOKEN_INTERNO_O_DE_USUARIO>
Accept: application/json
```

El token interno se configura en `INTERNAL_API_TOKEN` y se comparte solo entre servidores de confianza. Nunca se envia un token en la URL. Sin token valido, las rutas protegidas devuelven `401`; un token de servicio o un usuario sin permiso para modificar clientes recibe `403`. Los errores de validacion devuelven `422` con `message` y `errors`; un recurso inexistente devuelve `404`. El login admite cinco peticiones por minuto por origen y reutiliza el bloqueo de cuenta despues de tres contrasenas incorrectas. Las otras rutas se limitan a 60 peticiones por minuto.

Para probar localmente, generar un valor con `C:\xampp\php\php.exe -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`, asignarlo a `INTERNAL_API_TOKEN` en `.env` y ejecutar `C:\xampp\php\php.exe artisan config:clear`. Con el servidor levantado, por ejemplo:

```bash
curl -H "Authorization: Bearer TU_TOKEN" -H "Accept: application/json" http://localhost:8000/api/v1/clientes
```

## Endpoints publicados por G1

| Metodo | Ruta | Parametros / cuerpo | Respuesta |
| --- | --- | --- | --- |
| GET | `/api/v1/health` | Ninguno | `{ "status": "ok" }` |
| GET | `/api/v1/clientes` | `buscar`, `estado`, `tipo`, `zona_id`, `page`, `per_page` | Clientes y `meta` |
| POST | `/api/v1/clientes` | Ficha nueva completa; token de administrador | Cliente creado (`201`) y cabecera `Location` |
| GET | `/api/v1/clientes/{id}` | Ninguno | Ficha de cliente |
| PUT | `/api/v1/clientes/{id}` | Ficha editable completa; token de administrador | Cliente reemplazado (`200`) |
| PATCH | `/api/v1/clientes/{id}` | Uno o mas campos editables; token de administrador | Cliente modificado (`200`) |
| GET | `/api/v1/clientes/{id}/saldo` | Ninguno | Saldo de G4 y moneda; `503` sin integracion |
| GET | `/api/v1/clientes/{id}/cobros` | Ninguno | Cobros de G2; `503` sin integracion |
| GET | `/api/v1/zonas` | Ninguno | Zonas ordenadas por nombre |
| GET | `/api/v1/reclamos` | `cliente_id`, `estado`, `page`, `per_page` | Reclamos y `meta` |
| GET | `/api/v1/reclamos/{id}` | Ninguno | Reclamo |
| POST | `/api/v1/reclamos` | JSON de alta | Reclamo creado (`201`) |
| PATCH | `/api/v1/reclamos/{id}/estado` | `{ "estado": "en_proceso" }` | Reclamo actualizado |
| GET | `/api/v1/usuarios/{id}` | Ninguno | Solo ID, nombre, rol y estado |
| POST | `/api/v1/auth/login` | `{ "usuario": "admin", "password": "..." }`; sin token previo | Token de usuario y vencimiento (`200`) |
| POST | `/api/v1/auth/logout` | Token de usuario activo | Confirma cierre de sesion (`200`) |

### Login y logout JSON

```http
POST /api/v1/auth/login
Content-Type: application/json
Accept: application/json

{"usuario":"admin","password":"tu_contrasena"}
```

Respuesta de ejemplo:

```json
{
  "data": {
    "token_type": "Bearer",
    "access_token": "TOKEN_OPACO",
    "expires_at": "2026-10-06T16:00:00-03:00",
    "usuario": { "id": 1, "nombre": "Administrador", "rol": "administrativo" }
  }
}
```

Enviar `access_token` como Bearer en las siguientes llamadas. Un nuevo login de la misma cuenta invalida el token anterior y cualquier sesion web anterior. El login web tambien invalida el token API anterior. Para cerrar sesion:

```http
POST /api/v1/auth/logout
Authorization: Bearer TOKEN_OPACO
Accept: application/json
```

El token deja de funcionar inmediatamente despues del logout o al vencer. No se entrega un token interno de servicio mediante login.

### Alta y cambios de clientes

`POST /api/v1/clientes` requiere `nombre`, `apellido_razon_social`, `dni_cuit`, `telefono`, `email`, `direccion` y `tipo_cliente` (`minorista` o `mayorista`). `localidad`, `zona_id` y `condicion_iva` son opcionales. El cliente se crea `activo` y `creado_por` se toma del token administrativo.

`PUT /api/v1/clientes/{id}` requiere los siete campos anteriores, `estado` (`activo` o `inactivo`) y la presencia explicita de `localidad`, `zona_id` y `condicion_iva` (pueden valer `null`). `PATCH /api/v1/clientes/{id}` requiere al menos un campo editable y conserva todos los demas. DNI/CUIT y email deben ser unicos; `zona_id` debe existir cuando se especifica.

Ejemplo de alta:

```json
{
  "nombre": "Ana",
  "apellido_razon_social": "Comercial Norte",
  "dni_cuit": "20-12345678-9",
  "telefono": "11-4444-5555",
  "email": "ana@example.com",
  "direccion": "Calle Principal 123",
  "tipo_cliente": "mayorista",
  "localidad": "San Isidro",
  "zona_id": 3,
  "condicion_iva": "Responsable Inscripto"
}
```

Ejemplo de cambio parcial:

```http
PATCH /api/v1/clientes/12
Authorization: Bearer TOKEN_DE_ADMINISTRADOR
Content-Type: application/json

{"telefono":"11-5555-6666","localidad":null}
```

`saldo` y `creado_por` no son campos editables por estos endpoints. El saldo pertenece a G4 y el creador se fija en el alta. Si se envian esos campos, la API responde `422`.

Ejemplo de cliente:

```json
{
  "data": {
    "id": 12,
    "nombre": "Ana",
    "apellido_razon_social": "Comercial Norte",
    "dni_cuit": "20-12345678-9",
    "telefono": "11-4444-5555",
    "email": "ana@example.com",
    "direccion": "Calle Principal 123",
    "localidad": "San Isidro",
    "zona_id": 3,
    "condicion_iva": "Responsable Inscripto",
    "tipo_cliente": "mayorista",
    "estado": "activo"
  }
}
```

`GET /clientes/{id}/saldo` consulta a G4 y devuelve `data: {"cliente_id": 12, "saldo": "12500.00", "moneda": "ARS"}`. `GET /clientes/{id}/cobros` consulta a G2 y devuelve `data` con `id`, `cliente_id`, `fecha`, `monto_total`, `medio_pago`, `comprobante_nro` y `observaciones`, ordenados por fecha descendente en G1. G1 ya no registra cobros ni calcula el saldo. Sin URL/token responde `503`; ante error o JSON invalido del proveedor responde `502`.

Para crear un reclamo:

```json
{
  "cliente_id": 12,
  "usuario_id": 4,
  "asunto": "Mercaderia incompleta",
  "descripcion": "Faltaron dos productos del pedido",
  "prioridad": "alta"
}
```

El cliente debe estar activo y el usuario debe ser administrativo o repartidor activo. El reclamo nace en `abierto`. Prioridades: `baja`, `media`, `alta`. Estados: `abierto`, `en_proceso`, `cerrado`. El cliente y usuario que se envian quedan registrados como autor y asociado del reclamo; cada consumidor debe enviar IDs verificables.

## Dependencias que consume G1

| Grupo | Consulta desde G1 | Pantalla o ruta G1 | Estado del contrato |
| --- | --- | --- | --- |
| G2 | Cobros por `cliente_id` | Ficha del cliente y `GET /api/v1/clientes/{id}/cobros` | Pendiente de endpoint de listado |
| G2 | Facturas pendientes | Reporte de clientes deudores | Pendiente de endpoint |
| G3 | Catalogo de productos | Selector del reporte clientes por producto | `GET /api/v1/productos` visible en rama G3; falta configurar y probar conexion |
| G4 | Ventas por cliente | Ficha, historial y exportaciones | Pendiente de contrato JSON compatible |
| G4 | Clientes compradores por producto | Porcentaje de clientes por producto | Pendiente de endpoint |
| G4 | Ranking de productos | Reporte ranking comercial | Pendiente de endpoint |
| G4 | Saldo por cliente y lista de deudores | Ficha, API de saldo y reporte de deudores | Pendiente de endpoints |

Un endpoint visible en una rama no implica integracion operativa. G1 no consulta ventas, productos, cobros ni saldos de sus tablas locales para estos flujos. La issue de transferencias #120 sigue fuera de alcance.

## JSON requerido de Ventas

El Grupo 1 consulta `GET /api/v1/ventas?cliente_id=12&desde=YYYY-MM-DD&hasta=YYYY-MM-DD` para el historial de un cliente. Los filtros `desde` y `hasta` son opcionales. La respuesta `{ "data": [...] }` debe contener todas las operaciones del filtro, ordenadas por fecha descendente.

Cada elemento debe tener `fecha` (ISO 8601), `numero_factura` (cadena o `null`), `total` (decimal), `estado` (`pendiente`, `confirmada`, `pagada`, `facturada` o `cancelada`), `cliente` con `nombre` y `apellido_razon_social`, y `detalles` como lista de `{ cantidad, subtotal, producto: { nombre } }`. Deben venir todos los estados.

Ejemplo de contrato (solo documentacion y pruebas, no datos cargados en ejecucion): [ventas.json](contracts/ventas.json).

G1 ya no consume alertas de stock ni genera reportes de ventas generales o inventario. Las secciones de Ventas y Stock permanecen visibles sin consultas desde G1. El historial de clientes solo usa la API de G4. El reporte de clientes por producto usa el catalogo JSON de G3 (`GET /api/v1/productos`, con `id_producto`, `codigo`, `nombre` o `descripcion`) y solicita a G4 `GET /api/v1/reportes/productos/{id}/compradores`, con `data: [{"cliente_id": 12}]`. G4 debe devolver una vez cada cliente con venta confirmada, pagada o facturada, excluyendo pendientes y canceladas. Esta ultima ruta es un contrato **propuesto**, no disponible actualmente en la rama publica de G4. El porcentaje se calcula sobre todos los clientes registrados en G1. Ninguna consulta toma ventas o productos de las tablas locales.

## Configuracion de servicios externos

```dotenv
INTERNAL_API_TOKEN=valor_aleatorio_largo
SALES_SERVICE_URL=http://ventas:8000
SALES_SERVICE_TOKEN=token_del_servicio_ventas
CATALOG_SERVICE_URL=http://productos:8000
CATALOG_SERVICE_TOKEN=
BILLING_SERVICE_URL=http://cobros:8000
BILLING_SERVICE_TOKEN=token_del_servicio_cobros
BALANCE_SERVICE_URL=http://saldos:8000
BALANCE_SERVICE_TOKEN=token_del_servicio_saldos
```

Las URL y tokens anteriores son valores ilustrativos: no se deben copiar como servicios reales. G3 puede consultarse sin token mientras su API siga publica; los otros consumidores requieren token. Tras cambiar `.env`, ejecutar `php artisan config:clear`. Sin servicio configurado, las pantallas muestran integracion pendiente y las rutas API/exportaciones devuelven `503`. Un servicio caido devuelve `503`, y un error o JSON invalido devuelve `502`. Nunca se sustituyen los datos externos por tablas locales ni por respuestas simuladas.

Cada ruta externa tambien se puede ajustar con las variables `SALES_HISTORY_PATH`, `SALES_BUYERS_PATH`, `SALES_RANKING_PATH`, `CATALOG_PRODUCTS_PATH`, `BILLING_PAYMENTS_PATH`, `BILLING_INVOICES_PATH`, `BALANCE_CLIENT_PATH` y `BALANCE_DEBTORS_PATH` de `.env.example`. En las rutas de comprador y saldo individual, `{id}` se reemplaza por el ID correspondiente. Cambiar la ruta no adapta automaticamente un JSON distinto: si el otro grupo entrega otra estructura, hay que acordar y actualizar el adaptador y sus pruebas.

Estado verificado en las ramas publicas: G3 publica `GET /api/v1/productos` con `data`; G2 publica alta de cobros y una consulta de saldo propia, pero **no** el historial ni las facturas pendientes aqui esperadas. G4 publica `GET /api/ventas`, que hoy no equivale al contrato JSON de historial, ranking, compradores y saldos requerido por G1. Estas rutas propuestas no deben marcarse como integradas hasta probarlas extremo a extremo.

G2 es dueno de cobros y facturas; G4, de saldos y ventas. Las tablas locales historicas `cobros` y `clientes.saldo` se conservan sin borrar datos, pero ya no se usan en pantallas, reportes ni escrituras. Los nuevos contratos propuestos para Sprint 4 estan en [contratos-sprint4-g1.md](contratos-sprint4-g1.md). No se considera integrada una API hasta que el otro grupo confirme su ruta y estructura.
