# APIs funcionales de G1: consultas, escrituras y pruebas

Fecha: 28/10/2026. Alcance de la implementación de APIs: código y configuración de G1. No se modificaron tablas ni archivos de negocio de G2, G3 o G4 en esa implementación. Posteriormente, por pedido del usuario, se retiraron los nueve archivos nuevos de migración de la instalación local anterior, sin ejecutar rollback ni cambiar la base existente. Las migraciones previamente versionadas se conservaron.

## Resultado

Quedaron verificadas **16 operaciones funcionales**: nueve GET y siete POST/PUT/PATCH. Las dos consultas financieras delegadas de saldo/cobros quedan fuera de esta certificación, pendientes de proveedores compatibles de G4/G2.

Las consultas privadas aceptan sesión web vigente o Bearer. Las escrituras requieren Bearer: una cookie del frontend no habilita POST/PUT/PATCH. El Catálogo comercial sigue siendo público y ahora tiene una API JSON de datos, además de sus páginas HTML y el endpoint de actualización del frontend.

## Tabla de endpoints verificados

Base: `http://127.0.0.1:8000`. En la tabla, A = administrativo, R = repartidor, C = contador y S = token interno de servicio. Un token interno no identifica a un usuario y no permite escribir clientes ni cerrar una sesión de usuario.

| Método | Ruta | Acceso | Éxito y contenido |
| --- | --- | --- | --- |
| GET | `/api/v1/health` | A/R/C con sesión o Bearer; S | 200, `{"status":"ok"}` |
| GET | `/api/v1/clientes` | A/R con sesión o Bearer; S | 200, `data` y `meta` |
| GET | `/api/v1/clientes/{id}` | A/R con sesión o Bearer; S | 200, ficha en `data` |
| GET | `/api/v1/zonas` | A/R con sesión o Bearer; S | 200, zonas ordenadas por nombre |
| GET | `/api/v1/reclamos` | A/R con sesión o Bearer; S | 200, `data` y `meta` |
| GET | `/api/v1/reclamos/{id}` | A/R con sesión o Bearer; S | 200, detalle en `data` |
| GET | `/api/v1/usuarios/{id}` | A con sesión o Bearer; S | 200, id, nombre, rol y estado; sin credenciales |
| GET | `/api/v1/catalogo` | Público | 200, productos comerciales, metadatos y filtros |
| GET | `/api/v1/catalogo/{id}` | Público | 200, detalle comercial del producto activo |
| POST | `/api/v1/auth/login` | Público, credenciales válidas de usuario activo | 200, Bearer, vencimiento y usuario |
| POST | `/api/v1/auth/logout` | Bearer de usuario A/R/C | 200, revoca ese token |
| POST | `/api/v1/clientes` | Bearer A | 201, cliente creado y cabecera `Location` |
| PUT | `/api/v1/clientes/{id}` | Bearer A | 200, reemplazo de ficha editable completa |
| PATCH | `/api/v1/clientes/{id}` | Bearer A | 200, actualización de campos enviados |
| POST | `/api/v1/reclamos` | Bearer A/R; S con autor explícito | 201, reclamo abierto |
| PATCH | `/api/v1/reclamos/{id}/estado` | Bearer A/R; S | 200, reclamo con el estado nuevo |

No hay DELETE de Clientes ni Reclamos en esta API. `PUT` y `PATCH` son operaciones distintas: un PUT incompleto se rechaza. No se agregaron rutas que no tuvieran un caso de uso solicitado.

## Consultas desde el navegador

Con MySQL iniciado, ejecutar desde la raíz del proyecto:

```powershell
C:\xampp\php\php.exe -d extension=gd -d extension=zip artisan serve
```

Iniciar sesión en el frontend como administrativo. En el mismo navegador y host se pueden abrir, por ejemplo:

```text
http://127.0.0.1:8000/api/v1/health
http://127.0.0.1:8000/api/v1/clientes
http://127.0.0.1:8000/api/v1/clientes/1
http://127.0.0.1:8000/api/v1/zonas
http://127.0.0.1:8000/api/v1/reclamos
http://127.0.0.1:8000/api/v1/usuarios/1
http://127.0.0.1:8000/api/v1/catalogo
http://127.0.0.1:8000/api/v1/catalogo/1
```

Los IDs son ejemplos: utilizar los que existan en la base. El detalle de un reclamo inexistente devuelve 404 correctamente. Si todavía no hay reclamos, el listado devuelve `data: []`; crear uno por POST antes de consultar su ID.

La sesión debe ser activa, no estar reemplazada y no superar el tiempo de inactividad. Sin credenciales válidas se obtiene 401 JSON; con un rol que no tiene permiso, 403. No se aceptan encabezados que solamente declaren ser administrador. Un Bearer explícito inválido no se sustituye por una cookie válida.

`localhost` y `127.0.0.1` no comparten automáticamente cookies: usar el host donde se inició sesión. Las lecturas privadas y públicas del Catálogo incluyen `Cache-Control: no-store`.

## Nueva API pública de Catálogo

`GET /api/v1/catalogo` devuelve JSON estructurado, sin fragmentos HTML. No requiere login porque ofrece la misma información comercial pública que `/catalogo`.

Cada producto incluye `id`, `codigo`, `nombre`, `descripcion`, `categoria`, `marca`, `precio`, `moneda`, `presentacion`, `disponibilidad` e `imagen`. El precio es una cadena decimal de dos posiciones o null; la moneda indicada es ARS. Los IDs de categorías/marcas son cadenas para admitir las opciones de agrupación como `otros`.

No expone los IDs de usuarios de carga/modificación, auditoría, costos, proveedores ni cantidades exactas internas de stock. La disponibilidad comercial puede ser `disponible`, `bajo`, `sin-stock` o `consultar`.

Filtros combinables: `q`, `categoria`, `marca`, `disponibilidad`, `orden` y `page`. `disponibilidad=disponible` incluye las últimas unidades. Orden admite `nombre`, `codigo`, `precio-asc` o `precio-desc`. Se reutiliza la paginación del Catálogo web: doce productos por página; una página posterior a la última se ajusta a la última disponible.

Ejemplos:

```text
/api/v1/catalogo?q=TEST-G3-001
/api/v1/catalogo?disponibilidad=disponible&orden=precio-asc
/api/v1/catalogo?categoria=1&marca=1
```

La respuesta del listado tiene `data`, `meta` y `filtros`. `meta` incluye página, última página, tamaño, total, si la búsqueda fue aproximada y la fecha de consulta. El detalle devuelve `data`; productos inactivos o inexistentes devuelven 404. Un filtro inválido produce 422 y una fuente no configurada produce 503; los errores de respuesta de la fuente producen 502.

`/catalogo` y `/catalogo/{id}` siguen siendo páginas HTML. `/catalogo/datos` conserva su JSON de actualización con `html`, categorías y marcas; no se cambió ese contrato ni el JavaScript de las tarjetas.

## Cómo funciona con artisan serve

En el `.env` local se agregó:

```dotenv
CATALOG_TRANSPORT=internal
```

`InternalCatalogApi`, dentro de G1, consume la ruta GET registrada de Productos sin realizar una conexión HTTP al mismo servidor. No es un mock ni consulta directamente la tabla de G3: ejecuta su ruta/controlador y sus middleware de API.

El transporte solo permite `/api/productos`, `/api/v1/productos` y sus detalles numéricos, y comprueba que la acción sea la del controlador de Productos esperado. No permite utilizarlo para consultar rutas de Clientes, otros módulos o URLs arbitrarias. Solo hace GET.

La subpetición no reenvía cookies, encabezados de identidad ni la sesión del visitante. Usa un contexto aislado de autenticación y sesión en memoria y lo restaura en `finally`, incluso si el proveedor falla. Se excluyen únicamente los componentes de cookies/inicio de sesión de esa llamada de servicio; no se quitan permisos ni autenticación de API. Si el proveedor necesita un token, se envía el configurado en `services.catalog.token`, nunca un token del visitante tomado implícitamente.

El modo alternativo `CATALOG_TRANSPORT=http` sigue disponible para un proveedor de G3 separado, usando `CATALOG_SERVICE_URL` y las rutas configuradas. Es el valor predeterminado de `.env.example`. El modo interno necesita que la API de G3 y su esquema estén instalados en esta misma aplicación, como ocurre en la copia local actual.

Esta adaptación cubre Productos para el Catálogo y los consumidores de catálogo de G1. No cambia el transporte de Ventas ni las otras integraciones HTTP. Si un reporte apunta por HTTP a Ventas en el mismo proceso de `artisan serve`, ese circuito todavía requiere un proveedor separado o Apache; no se declara resuelto por esta intervención.

## Seguridad y reglas de escritura

- Todas las escrituras requieren `Authorization: Bearer ...` y JSON. No se habilitaron escrituras con cookies ni se desactivó CSRF del frontend.
- Clientes solo puede ser creado/editado por un usuario administrativo, no por el token interno de servicio.
- El servidor asigna `creado_por`. No admite editar `saldo` ni enviar campos desconocidos a las escrituras de Clientes.
- DNI tiene 7 u 8 dígitos y CUIT tiene 11. Email, documento y los demás campos usan las validaciones de G1; duplicados o campos inválidos devuelven 422.
- Calle y altura se envían juntas. Si también se envía `direccion`, debe coincidir con ambas. Puede conservarse el formato anterior enviando solo la dirección completa.
- PUT exige la ficha completa, incluido `estado`, y la presencia de `localidad`, `zona_id` y `condicion_iva`, aunque estos tres sean null. PATCH omite lo que no cambia y no admite un cuerpo vacío.
- Con Bearer de usuario, el autor del reclamo es ese usuario; se puede omitir `usuario_id`. Si se envía otro actor, se rechaza. Con token interno de servicio, `usuario_id` es obligatorio y debe identificar a un administrativo/repartidor activo. Esta corrección evita atribuir reclamos a otras personas desde un token de usuario.
- Login aplica las validaciones y el bloqueo por intentos fallidos existentes. Un nuevo login reemplaza la sesión/token anterior de la misma cuenta. Por eso no se debe alternar login web y login API de la misma cuenta durante la misma prueba esperando conservar ambos accesos.

## Pruebas POST/PUT/PATCH realizadas

Se probaron las siete operaciones con solicitudes reales sobre **MariaDB y un único artisan serve**, en una copia aislada del esquema de 61 tablas y cinco productos. Se creó una cuenta administrativa exclusiva de laboratorio, sin alterar cuentas ni datos de la base principal.

| Operación | Caso válido comprobado | Comprobación adicional |
| --- | --- | --- |
| POST login | Credenciales activas, 200, token obtenido | Pruebas automatizadas de credenciales inválidas, bloqueo, vencimiento y reemplazo |
| POST cliente | Alta con DNI, localidad, zona nueva y dirección separada; 201 | Se leyó el registro persistido; sin Bearer/cookie sola, 401; cuerpo inválido, 422 |
| PUT cliente | Ficha completa, cambio de nombre y conservación del identificador; 200 | PUT incompleto rechazado con 422 |
| PATCH cliente | Cambio de calle/altura y recomposición del domicilio; 200 | MySQL confirmó el domicilio nuevo; saldo prohibido y cuerpo vacío rechazados |
| POST reclamo | Alta sobre cliente activo, autor del token y estado abierto; 201 | Prueba de rechazo de autor ajeno; el token de servicio conserva actor explícito válido |
| PATCH estado de reclamo | Cambio a cerrado, 200 | MySQL confirmó el estado; estado inválido e ID inexistente se rechazan |
| POST logout | Cierre con token de usuario, 200 | La consulta siguiente con ese mismo token devolvió 401 |

También se verificaron los siete GET privados con cookie de un login web real de laboratorio, incluido CSRF de login, los dos GET JSON públicos de Catálogo, sus páginas HTML y el rechazo de visitantes. El Catálogo interno devolvió los cinco productos bajo un solo `artisan serve` sin timeout. La sesión del administrador siguió siendo válida después de consultar el Catálogo.

La regresión segura aprobó **253 pruebas, 1461 aserciones**. Incluye pruebas aisladas de roles, contratos HTTP, filtros, paginación, errores, proyección pública, conservación de sesión y mantenimiento de un middleware de origen que deniega acceso. Se excluyeron las dos clases de G4 con expectativas antiguas de vistas ya documentadas y las pruebas que fuerzan bases externas; no se afirma que toda la suite del ERP esté aprobada.

Las escrituras de laboratorio quedaron solo en la base descartable. No se agregó un reclamo real ni un cliente adicional a los once de la base principal durante esta etapa. Los servidores de prueba se cerraron al finalizar. Evidencias y respaldo de código previo: `Entregables/Apis_G1_2026-10-28/`, fuera del proyecto Git y sin publicar tokens.

## Prueba manual de las siete escrituras con PowerShell

Estos comandos **sí escriben en la base que tengas configurada** cuando los ejecutes. Crean un cliente nuevo de prueba y utilizan su ID; no modifican uno de los clientes anteriores. Ejecutarlos con el servidor levantado y una cuenta administrativa del ERP.

```powershell
$base = 'http://127.0.0.1:8000'
$cred = Get-Credential -Message 'Usuario y contraseña del ERP'
$loginBody = @{ usuario = $cred.UserName; password = $cred.GetNetworkCredential().Password }
$login = Invoke-RestMethod "$base/api/v1/auth/login" -Method Post `
    -ContentType 'application/json; charset=utf-8' -Body ($loginBody | ConvertTo-Json)
$h = @{ Accept = 'application/json'; Authorization = "Bearer $($login.data.access_token)" }

# POST: crear un cliente de prueba con documento/email nuevos.
$documento = (Get-Random -Minimum 70000000 -Maximum 79999999).ToString()
$datos = @{
    nombre = 'Prueba'; apellido_razon_social = 'API G1'; tipo_documento = 'dni'
    dni_cuit = $documento; telefono = '2923123456'; email = "prueba.$documento@erp.example"
    tipo_cliente = 'minorista'; localidad = 'Pigue'; zona = 'Pruebas API'
    direccion_calle = 'San Martin'; direccion_numero = '150'
}
$cliente = Invoke-RestMethod "$base/api/v1/clientes" -Method Post -Headers $h `
    -ContentType 'application/json; charset=utf-8' -Body ($datos | ConvertTo-Json)
$id = $cliente.data.id

# PUT: reemplazo completo de la ficha editable.
$completo = $datos.Clone()
$completo.Remove('zona')
$completo.nombre = 'Prueba editada'
$completo.estado = 'activo'
$completo.zona_id = $cliente.data.zona_id
$completo.condicion_iva = $null
Invoke-RestMethod "$base/api/v1/clientes/$id" -Method Put -Headers $h `
    -ContentType 'application/json; charset=utf-8' -Body ($completo | ConvertTo-Json)

# PATCH: cambia únicamente teléfono.
Invoke-RestMethod "$base/api/v1/clientes/$id" -Method Patch -Headers $h `
    -ContentType 'application/json; charset=utf-8' -Body (@{ telefono = '2923445566' } | ConvertTo-Json)

# POST: crear un reclamo. El usuario autor se toma del token.
$reclamo = Invoke-RestMethod "$base/api/v1/reclamos" -Method Post -Headers $h `
    -ContentType 'application/json; charset=utf-8' `
    -Body (@{ cliente_id = $id; asunto = 'Prueba API'; descripcion = 'Control de entrega'; prioridad = 'media' } | ConvertTo-Json)
$reclamoId = $reclamo.data.id

# PATCH: cerrar el reclamo recién creado.
Invoke-RestMethod "$base/api/v1/reclamos/$reclamoId/estado" -Method Patch -Headers $h `
    -ContentType 'application/json; charset=utf-8' -Body (@{ estado = 'cerrado' } | ConvertTo-Json)

# GET: verificar la persistencia y las otras consultas.
Invoke-RestMethod "$base/api/v1/clientes/$id" -Headers $h
Invoke-RestMethod "$base/api/v1/reclamos/$reclamoId" -Headers $h
Invoke-RestMethod "$base/api/v1/zonas" -Headers $h
Invoke-RestMethod "$base/api/v1/usuarios/$($login.data.usuario.id)" -Headers $h
Invoke-RestMethod "$base/api/v1/catalogo"

# POST: cerrar la sesión API. Reutilizar este token después debe fallar con 401.
Invoke-RestMethod "$base/api/v1/auth/logout" -Method Post -Headers $h
```

En Postman se usan los mismos métodos, URLs y cuerpos JSON, configurando Authorization > Bearer Token con `data.access_token`. Para PUT usar el cuerpo completo; para PATCH enviar solo los cambios. La barra de direcciones del navegador realiza GET: no sirve para ejecutar un POST/PUT/PATCH.

### Colección con Bearer automático

Se agregó una [colección importable y un entorno vacío de Postman](postman/README.md) para las 16 operaciones. Tras completar las credenciales locales y enviar POST login, el token se guarda y se reutiliza en todas las solicitudes privadas. No se incluyeron tokens ni contraseñas reales. Las solicitudes de escritura se identifican como cambios reales y usan los IDs de los registros creados por la colección. Esta ayuda no cambia rutas, permisos, migraciones ni bases. Un GET al login desde la barra del navegador sigue devolviendo 405 porque el método admitido es POST.

## Dependencias que siguen pendientes

`GET /api/v1/clientes/{id}/saldo` y `/cobros` continúan delegando en G4 y G2. No se simularon saldos ni se usaron tablas históricas como reemplazo. Esas dos rutas mantienen Bearer y pueden devolver 503 hasta disponer de los proveedores acordados.

## Qué cambia con commit/push y cómo revertir

La implementación de APIs cambia únicamente G1: transporte del Catálogo, su API JSON, agrupación de lecturas, validación/autoría de reclamos, configuración, pruebas y documentación. No creó migraciones. Después de esa implementación se eliminaron los nueve archivos de migración nuevos y todavía no versionados de la instalación anterior: la compatibilidad de stock de la carpeta principal, los siete archivos de `g2_importadas/` y el archivo de promociones de `integracion_local/`. Ya no forman parte de los cambios a subir.

Git no envía las tablas, filas o servicios de MySQL por tenerlos instalados localmente. `.env` está ignorado y los respaldos SQL están fuera del proyecto. Retirar esos archivos no desinstala las tablas ni elimina sus filas o sus registros de ejecución en la tabla `migrations`. Tampoco deja reproducible esa instalación local desde cero: una instalación nueva necesita que cada grupo provea su esquema compatible. No ejecutar `migrate:fresh`, `migrate:refresh` ni rollback global para intentar reconstruir esta copia.

Para revertir solo la implementación de APIs, comparar y restaurar selectivamente los archivos existentes desde `Entregables/Apis_G1_2026-10-28/archivos-antes/`, retirar los dos archivos nuevos de Catálogo, las dos pruebas nuevas y este informe cuando ya no tengan referencias. Restaurar también el modo previo del `.env`, sin reemplazar otras credenciales. No usar un reset general. El retiro posterior de las nueve migraciones es una acción independiente autorizada por el usuario.

Se conservó una copia de esos nueve archivos fuera del repositorio Git, en `Entregables/Retiro_Migraciones_Locales_2026-10-28_010424/`, verificando que cada copia coincidiera con su original antes de borrarlo. Para recuperar los archivos se pueden copiar a sus rutas originales desde ese respaldo; recuperarlos no requiere volver a ejecutarlos sobre una base donde ya fueron aplicados. Se verificó además que las 41 migraciones previamente versionadas, tanto centrales como de módulos, conservaran sus hashes. No se ejecutó ningún comando de base de datos durante el retiro.

No hay cambios de base que revertir en esta etapa. Para volver al consumo remoto, configurar `CATALOG_TRANSPORT=http` y una URL de proveedor que pueda atender solicitudes. Con HTTP al mismo proceso de PHP volvería el bloqueo que motivó esta adaptación.
