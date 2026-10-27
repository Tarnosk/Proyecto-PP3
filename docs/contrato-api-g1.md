# Contrato HTTP/JSON de G1

Este documento describe las rutas implementadas en `routes/api.php` bajo `/api/v1`. Los valores de los ejemplos son ilustrativos; IDs, fechas, tokens y datos dependen de la base. Las rutas de reportes `/reportes`, `/ventas` y `/stock` son pantallas web, no forman parte de esta API. G1 consume ventas y saldos de G4, cobros y facturas de G2; no consume alertas de stock. Los contratos de Sprint 4 aun requieren acuerdo con esos grupos.

## Convenciones

- Enviar `Accept: application/json` siempre. En `POST`, `PUT` y `PATCH`, enviar ademas `Content-Type: application/json`.
- Salvo `POST /auth/login` y `GET /clientes`, enviar `Authorization: Bearer <token>`. El token de usuario sale del login; el token interno de servicio se configura en `INTERNAL_API_TOKEN` y no se obtiene por HTTP.
- `administrativo`, `repartidor` y `contador` son los valores de rol. Un usuario inactivo o con token vencido/reemplazado no se autentica.
- El token interno puede leer clientes, zonas, reclamos y usuarios, y crear/cambiar reclamos. No puede crear/cambiar clientes ni hacer logout.
- Los importes (`saldo`, `monto_total`) son cadenas decimales de dos posiciones, no numeros JSON. Fechas y horas son cadenas ISO 8601. Los campos opcionales de una respuesta pueden valer `null`.
- En las listas paginadas, `page` selecciona la pagina (por defecto 1) y `per_page` admite enteros de 1 a 100 (por defecto 20). `page` lo procesa el paginador de Laravel; no tiene una regla de validacion propia. Una lista vacia devuelve `data: []` y `meta`, no `404`.
- `{id}` es un segmento numerico de ruta. Un recurso inexistente devuelve `404`.

## Estados y errores

| Estado | Cuando aplica | Cuerpo |
| --- | --- | --- |
| `200 OK` | Consulta, login, logout o modificacion exitosa | JSON especifico de la ruta |
| `201 Created` | Alta de cliente o reclamo | `{"data": {...}}`; el alta de cliente agrega cabecera `Location: .../api/v1/clientes/{id}` |
| `401 Unauthorized` | Falta el Bearer, es invalido o vencio; no aplica al login ni al listado publico de clientes | `{"message":"No autorizado."}` |
| `403 Forbidden` | Token valido pero rol no permitido; token interno en ruta reservada a usuario | `{"message":"No tiene permisos para esta operacion."}` |
| `404 Not Found` | ID inexistente o ruta no encontrada | Error JSON de Laravel, normalmente `{"message":"No query results for model ..."}` para un ID inexistente; el texto exacto no es parte estable del contrato |
| `422 Unprocessable Content` | Campos invalidos, faltantes, duplicados o credenciales incorrectas/bloqueadas | `{"message":"...","errors":{"campo":["..."]}}`; el texto depende de la validacion/traducciones |
| `429 Too Many Requests` | Limite de peticiones excedido: login 5/minuto por origen, demas rutas 60/minuto | Error JSON de Laravel; volver a intentar despues del limite |
| `500 Internal Server Error` | Fallo no controlado, por ejemplo una base de datos indisponible | Laravel lo genera, pero G1 **no define hoy un cuerpo 500 propio ni estable**. Con `APP_DEBUG=true` puede exponer detalles tecnicos; en produccion debe usarse `APP_DEBUG=false`. |

`400`, `409`, `502` y `503` no tienen manejadores/contratos especificos en estas rutas de G1. Un metodo HTTP no permitido puede producir `405` por Laravel. Los `502`/`503` mencionados en la integracion de Ventas pertenecen al consumo remoto en el historial de clientes, no son respuestas definidas para estos 16 endpoints. No se debe interpretar un `500` como una validacion de negocio.

Ejemplos de errores:

```json
{"message":"No autorizado."}
```

```json
{"message":"The nombre field is required.","errors":{"nombre":["The nombre field is required."]}}
```

El segundo ejemplo ilustra la estructura de `422`, no garantiza ese idioma ni ese texto literal. En login, un fallo de credenciales usa `errors.usuario`, tambien con estado `422`. Tres contrasenas incorrectas bloquean la cuenta durante 15 minutos; el bloqueo sigue respondiendo `422`. El limitador HTTP puede responder `429` antes de llegar a la validacion.

## Esquemas reutilizados

**Cliente:** `id`, `nombre`, `apellido_razon_social`, `dni_cuit`, `telefono`, `email`, `direccion`, `localidad` (nullable), `zona_id` (nullable), `condicion_iva` (nullable), `tipo_cliente` (`minorista`/`mayorista`) y `estado` (`activo`/`inactivo`). `saldo` ya no se expone en la ficha de G1: se consulta por su ruta especifica, delegada a G4. `saldo` y `creado_por` no son editables por esta API.

**Reclamo:** `id`, `cliente_id`, `usuario_id`, `asunto`, `descripcion`, `prioridad` (`baja`/`media`/`alta`), `estado` (`abierto`/`en_proceso`/`cerrado`), `created_at`, `updated_at`.

**Meta paginada:** `current_page`, `last_page`, `per_page`, `total` son enteros. Las listas no devuelven enlaces de paginacion: construir la siguiente llamada con `?page=N`.

### Campos de escritura de cliente

| Campo | Tipo/regla | POST | PUT | PATCH |
| --- | --- | --- | --- | --- |
| `nombre` | Cadena, max. 120 | Obligatorio | Obligatorio | Opcional |
| `apellido_razon_social` | Cadena, max. 160 | Obligatorio | Obligatorio | Opcional |
| `dni_cuit` | Cadena unica, 6-20 caracteres: digitos, puntos, guiones o espacios | Obligatorio | Obligatorio | Opcional |
| `telefono` | Cadena, 6-30 caracteres: digitos, espacios, `+`, `-`, `(`, `)` | Obligatorio | Obligatorio | Opcional |
| `email` | Email unico, max. 160 | Obligatorio | Obligatorio | Opcional |
| `direccion` | Cadena, max. 255 | Obligatorio | Obligatorio | Opcional |
| `tipo_cliente` | `minorista` o `mayorista` | Obligatorio | Obligatorio | Opcional |
| `estado` | `activo` o `inactivo` | Prohibido; se crea `activo` | Obligatorio | Opcional |
| `localidad` | Cadena max. 120 o `null` | Opcional | Debe estar presente; puede ser `null` | Opcional |
| `zona_id` | ID de zona existente o `null` | Opcional | Debe estar presente; puede ser `null` | Opcional |
| `condicion_iva` | Cadena max. 60 o `null` | Opcional | Debe estar presente; puede ser `null` | Opcional |
| `saldo`, `creado_por` | No editables | Prohibidos | Prohibidos | Prohibidos |

En `PATCH` debe enviarse al menos un campo. Los campos desconocidos en escrituras de clientes dan `422`. El servidor recorta espacios al inicio y fin de las cadenas. `PUT` exige los tres campos nullable para que la ficha editable quede completa.

## Autenticacion

### `POST /api/v1/auth/login`

- Acceso: publico; la cuenta debe estar activa. Sin Bearer previo.
- Body obligatorio: `usuario` (cadena, max. 50), `password` (cadena, max. 255). Opcionales: ninguno.
- Exito: `200`. El token dura `SESSION_LIFETIME` minutos (120 por defecto) desde su emision. Un nuevo login invalida la sesion/token anterior de la misma cuenta.

```json
{"data":{"token_type":"Bearer","access_token":"TOKEN_OPACO","expires_at":"2026-10-06T16:00:00-03:00","usuario":{"id":1,"nombre":"Administrador","rol":"administrativo"}}}
```

- Errores aplicables: `422` (validacion, credenciales o bloqueo), `429`, `500`.

### `POST /api/v1/auth/logout`

- Acceso: token de usuario valido de cualquier rol; token interno no permitido.
- Body, query y parametros de ruta: ninguno.
- Exito: `200`.

```json
{"message":"Sesion cerrada."}
```

- Errores aplicables: `401`, `403`, `429`, `500`. El token revocado pasa a dar `401`.

### `GET /api/v1/health`

- Acceso: token interno o usuario autenticado de cualquier rol. No es publico.
- Body, query y parametros de ruta: ninguno.
- Exito: `200`.

```json
{"status":"ok"}
```

- Errores aplicables: `401`, `429`, `500`. `ok` confirma que la aplicacion responde, **no** comprueba MySQL ni los servicios externos.

## Clientes

### `GET /api/v1/clientes`

- Acceso: publico, sin token Bearer.
- Query obligatoria: ninguna. Opcionales: `buscar` (cadena max. 160, busca nombre, razon social o DNI/CUIT), `estado` (`activo`/`inactivo`), `tipo` (`minorista`/`mayorista`), `zona_id` (entero >= 1), `page`, `per_page`.
- Exito: `200`, orden por ID ascendente.

```json
{"data":[{"id":12,"nombre":"Ana","apellido_razon_social":"Comercial Norte","dni_cuit":"20-12345678-9","telefono":"11-4444-5555","email":"ana@example.com","direccion":"Calle Principal 123","localidad":"San Isidro","zona_id":3,"condicion_iva":"Responsable Inscripto","tipo_cliente":"mayorista","estado":"activo"}],"meta":{"current_page":1,"last_page":8,"per_page":20,"total":150}}
```

- Errores aplicables: `422` (filtros invalidos), `429`, `500`.

### `POST /api/v1/clientes`

- Acceso: solo usuario administrativo; token interno no permitido.
- Body obligatorio/opcional: ver tabla **Campos de escritura de cliente**. No lleva query ni ID.
- Exito: `201`, cabecera `Location` con la URL del cliente nuevo. `estado` nace en `activo` y `creado_por` es el administrador autenticado. G4 es dueno del saldo.

```json
{"data":{"id":12,"nombre":"Ana","apellido_razon_social":"Comercial Norte","dni_cuit":"20-12345678-9","telefono":"11-4444-5555","email":"ana@example.com","direccion":"Calle Principal 123","localidad":null,"zona_id":null,"condicion_iva":null,"tipo_cliente":"mayorista","estado":"activo"}}
```

- Errores aplicables: `401`, `403`, `422` (incluye duplicados de DNI/CUIT o email), `429`, `500`.

### `GET /api/v1/clientes/{id}`

- Acceso: token interno, administrativo o repartidor.
- Ruta obligatoria: `{id}` numerico de un cliente existente. Body y query: ninguno.
- Exito: `200`.

```json
{"data":{"id":12,"nombre":"Ana","apellido_razon_social":"Comercial Norte","dni_cuit":"20-12345678-9","telefono":"11-4444-5555","email":"ana@example.com","direccion":"Calle Principal 123","localidad":"San Isidro","zona_id":3,"condicion_iva":"Responsable Inscripto","tipo_cliente":"mayorista","estado":"activo"}}
```

- Errores aplicables: `401`, `403`, `404`, `429`, `500`.

### `PUT /api/v1/clientes/{id}`

- Acceso: solo usuario administrativo.
- Ruta obligatoria: `{id}` numerico existente. Body obligatorio/opcional: ver tabla **Campos de escritura de cliente**; todos los campos de ficha deben estar presentes, aunque `localidad`, `zona_id` y `condicion_iva` pueden ser `null`.
- Exito: `200`, ficha actualizada completa.

```json
{"data":{"id":12,"nombre":"Ana","apellido_razon_social":"Comercial Norte","dni_cuit":"20-12345678-9","telefono":"11-4444-5555","email":"ana@example.com","direccion":"Calle Principal 123","localidad":null,"zona_id":null,"condicion_iva":null,"tipo_cliente":"mayorista","estado":"inactivo"}}
```

- Errores aplicables: `401`, `403`, `404`, `422`, `429`, `500`.

### `PATCH /api/v1/clientes/{id}`

- Acceso: solo usuario administrativo.
- Ruta obligatoria: `{id}` numerico existente. Body: al menos un campo editable de la tabla; ninguno es obligatorio por separado. Los campos omitidos conservan su valor.
- Exito: `200`, ficha completa luego del cambio, aunque solo se haya enviado un campo.

```json
{"data":{"id":12,"nombre":"Ana","apellido_razon_social":"Comercial Norte","dni_cuit":"20-12345678-9","telefono":"11-5555-6666","email":"ana@example.com","direccion":"Calle Principal 123","localidad":"San Isidro","zona_id":3,"condicion_iva":"Responsable Inscripto","tipo_cliente":"mayorista","estado":"activo"}}
```

- Errores aplicables: `401`, `403`, `404`, `422` (tambien body vacio), `429`, `500`.

### `GET /api/v1/clientes/{id}/saldo`

- Acceso: token interno, administrativo o repartidor.
- Ruta obligatoria: `{id}` numerico existente. Body y query: ninguno.
- Exito: `200`.

```json
{"data":{"cliente_id":12,"saldo":"12500.00","moneda":"ARS"}}
```

La respuesta proviene de G4, no de `clientes.saldo` local. Sin integracion configurada o disponible, `503`; respuesta externa fallida o invalida, `502`.

- Errores aplicables: `401`, `403`, `404`, `429`, `502`, `503`, `500`.

### `GET /api/v1/clientes/{id}/cobros`

- Acceso: token interno, administrativo o repartidor.
- Ruta obligatoria: `{id}` numerico existente. Body y query: ninguno. G1 consulta G2 y devuelve sus cobros asociados al cliente.
- Exito: `200`, lista provista por G2, ordenada por fecha descendente en G1.

```json
{"data":[{"id":7,"cliente_id":12,"fecha":"2026-10-06T10:00:00-03:00","monto_total":"2500.00","medio_pago":"transferencia","comprobante_nro":"TR-123","observaciones":null}]}
```

`medio_pago` puede ser `efectivo`, `transferencia`, `tarjeta`, `cheque` u `otro`; `comprobante_nro` y `observaciones` pueden ser `null`.

- Errores aplicables: `401`, `403`, `404`, `429`, `502`, `503`, `500`.

## Zonas

### `GET /api/v1/zonas`

- Acceso: token interno, administrativo o repartidor.
- Body, query y parametros de ruta: ninguno.
- Exito: `200`, orden por nombre; sin paginacion.

```json
{"data":[{"id":3,"nombre":"Norte","descripcion":"Reparto zona norte"}]}
```

`descripcion` puede ser `null`.

- Errores aplicables: `401`, `403`, `429`, `500`.

## Reclamos

### `GET /api/v1/reclamos`

- Acceso: token interno, administrativo o repartidor.
- Query obligatoria: ninguna. Opcionales: `cliente_id` (entero >= 1), `estado` (`abierto`, `en_proceso`, `cerrado`), `page`, `per_page` (1-100). `cliente_id` filtra, no se valida su existencia para esta consulta.
- Exito: `200`, reclamos mas recientes primero.

```json
{"data":[{"id":9,"cliente_id":12,"usuario_id":4,"asunto":"Mercaderia incompleta","descripcion":"Faltaron dos unidades","prioridad":"alta","estado":"abierto","created_at":"2026-10-06T10:00:00-03:00","updated_at":"2026-10-06T10:00:00-03:00"}],"meta":{"current_page":1,"last_page":1,"per_page":20,"total":1}}
```

- Errores aplicables: `401`, `403`, `422` (filtros invalidos), `429`, `500`.

### `GET /api/v1/reclamos/{id}`

- Acceso: token interno, administrativo o repartidor.
- Ruta obligatoria: `{id}` numerico existente. Body y query: ninguno.
- Exito: `200`.

```json
{"data":{"id":9,"cliente_id":12,"usuario_id":4,"asunto":"Mercaderia incompleta","descripcion":"Faltaron dos unidades","prioridad":"alta","estado":"abierto","created_at":"2026-10-06T10:00:00-03:00","updated_at":"2026-10-06T10:00:00-03:00"}}
```

- Errores aplicables: `401`, `403`, `404`, `429`, `500`.

### `POST /api/v1/reclamos`

- Acceso: token interno, administrativo o repartidor.
- Body obligatorio: `cliente_id` (entero de cliente **activo** existente), `usuario_id` (entero de usuario **activo** con rol administrativo/repartidor), `asunto` (cadena max. 160), `descripcion` (cadena), `prioridad` (`baja`, `media`, `alta`). Opcionales: ninguno. Los demas campos enviados no se usan para crear el reclamo.
- Exito: `201`; nace con estado `abierto`. `usuario_id` se toma del body, **no** del token del llamador; el servicio consumidor debe enviar el autor correcto.

```json
{"data":{"id":9,"cliente_id":12,"usuario_id":4,"asunto":"Mercaderia incompleta","descripcion":"Faltaron dos unidades","prioridad":"alta","estado":"abierto","created_at":"2026-10-06T10:00:00-03:00","updated_at":"2026-10-06T10:00:00-03:00"}}
```

- Errores aplicables: `401`, `403`, `422` (incluye IDs inexistentes/inactivos), `429`, `500`.

### `PATCH /api/v1/reclamos/{id}/estado`

- Acceso: token interno, administrativo o repartidor.
- Ruta obligatoria: `{id}` numerico existente. Body obligatorio: `estado` (`abierto`, `en_proceso`, `cerrado`). Opcionales: ninguno; otros campos enviados no se usan.
- Exito: `200`, devuelve el reclamo completo actualizado.

```json
{"data":{"id":9,"cliente_id":12,"usuario_id":4,"asunto":"Mercaderia incompleta","descripcion":"Faltaron dos unidades","prioridad":"alta","estado":"en_proceso","created_at":"2026-10-06T10:00:00-03:00","updated_at":"2026-10-06T11:30:00-03:00"}}
```

- Errores aplicables: `401`, `403`, `404`, `422`, `429`, `500`.

## Usuarios

### `GET /api/v1/usuarios/{id}`

- Acceso: token interno o administrativo. Repartidor y contador reciben `403`.
- Ruta obligatoria: `{id}` numerico existente. Body y query: ninguno.
- Exito: `200`. No expone email, contrasena ni datos de sesion.

```json
{"data":{"id":4,"nombre":"Operador","rol":"repartidor","estado":"activo"}}
```

- Errores aplicables: `401`, `403`, `404`, `429`, `500`.
