# Pruebas de las APIs de G1 en Postman

Esta colección deja preparado el encabezado Bearer para las solicitudes privadas de G1. No contiene contraseñas ni tokens reales, no genera tokens permanentes y no cambia la autenticación del servidor.

## Por qué el login mostró 405

Escribir `/api/v1/auth/login` en la barra del navegador envía GET. El servidor admite únicamente POST en esa ruta, con un cuerpo JSON que contiene `usuario` y `password`. El 405 de la captura indica un método incorrecto; no demuestra que las credenciales o el servicio de login estén fallando. No se agregó un login por GET.

## Preparación inicial

1. Iniciar MySQL y el servidor habitual del ERP. Esta colección no los inicia.
2. En Postman, usar **Import** para importar estos dos archivos:
   - `G1.postman_collection.json`.
   - `G1.local.postman_environment.json`.
3. Seleccionar el entorno **G1 local** en el selector de entornos.
4. En ese entorno, completar `usuario` y `password` con una cuenta propia del ERP. Usar una cuenta administrativa para probar las 16 operaciones. Con otros roles se mantienen las restricciones del servidor.
5. Verificar `base_url`: inicialmente es `http://127.0.0.1:8000`, sin barra final. Usar el servidor propio o uno de confianza, porque el login enviará allí las credenciales. Guardar los valores solo para uso local, sin compartirlos ni exportarlos al repositorio una vez completados.
6. Abrir **01 - Obtener Bearer (POST login)** y pulsar **Send**. No es necesario editar su cuerpo: el script prepara el JSON con las credenciales del entorno, escapándolas correctamente.

Si el login devuelve 200, su script guarda `access_token`, `expires_at` y `usuario_id` en el entorno. Las solicitudes privadas heredan `Authorization: Bearer {{access_token}}` de la colección; no se copia el token manualmente en cada una. La contraseña solo se envía al login. El Catálogo público no envía Bearer.

Los tokens se generan al autenticar y vencen. Si falta el token o ya venció según su fecha, la colección detiene las solicitudes privadas y pide ejecutar login. Un token revocado antes de vencer devuelve 401 desde el servidor: ejecutar login otra vez. No hay renovación automática con contraseña en cada solicitud. Un nuevo login de la misma cuenta reemplaza su sesión/token anterior, incluido el acceso web.

## Consultas

Abrir la carpeta **02 - Consultas GET** y enviar cada solicitud. Health, listados y zonas no necesitan completar IDs. La consulta de usuario utiliza el ID devuelto por el login.

Para los detalles, completar `cliente_id`, `reclamo_id` y `producto_id` con identificadores existentes obtenidos de sus listados. La colección no presupone que exista el registro 1. Si falta un ID, detiene esa solicitud y pide completarlo. Los registros inexistentes devuelven 404. Las dos consultas del Catálogo se pueden ejecutar sin login, aunque los detalles requieren `producto_id`.

## Escrituras de prueba

La carpeta **03 - Escrituras REALES** guarda cambios en la base configurada al pulsar Send. Ejecutarla solo si se desea crear esos registros, con un usuario administrativo y en el orden indicado. No usar **Run collection** pensando que ejecuta solamente consultas: también enviaría las escrituras.

1. **POST cliente**: genera un DNI de ocho dígitos y email de prueba, y crea un cliente con una zona de prueba. Guarda el ID devuelto y prepara una ficha completa para PUT. Una improbable colisión de documento/email devuelve 422; reenviar el POST genera otros valores.
2. **PUT cliente**: modifica únicamente al cliente creado por ese POST, con la ficha completa preparada automáticamente.
3. **PATCH cliente**: cambia el teléfono del mismo cliente creado.
4. **POST reclamo**: crea un reclamo para ese cliente; el servidor toma al autor del usuario autenticado. Guarda el ID devuelto.
5. **PATCH reclamo**: cierra el reclamo recién creado.

Los IDs de escritura (`cliente_creado_id` y `reclamo_creado_id`) empiezan vacíos y se completan con las altas de esta colección. No se utilizan los IDs de consulta para modificar clientes o reclamos anteriores. Al crear un cliente se actualiza también `cliente_id`; al crear un reclamo se actualiza `reclamo_id`, para consultar luego su persistencia. No se eliminan automáticamente los registros creados.

Finalmente, enviar **04 - Cerrar sesión y limpiar Bearer**. Revoca el token y limpia del entorno los datos de autenticación cuando el servidor confirma el cierre, o si ya lo rechaza con 401.

## Alcance y verificación

La colección contiene las 16 operaciones verificadas de G1: nueve GET, cinco escrituras de Clientes/Reclamos y login/logout. No incluye saldo ni cobros, cuyos proveedores siguen pendientes. No se modificó código de otros grupos, rutas del servidor, migraciones ni bases para preparar estos archivos.

Se verificaron el JSON, las 16 rutas/métodos contra `routes/api.php`, la herencia de Bearer, la ausencia de secretos y la sintaxis/ejecución aislada de los scripts de login, captura de IDs, preparación de PUT y logout. No se efectuó un nuevo login real ni se escribieron datos de prueba adicionales para preparar la colección. Las pruebas HTTP anteriores del backend están detalladas en [APIs funcionales de G1](../apis-funcionales-g1.md).

Para retirar esta ayuda, borrar esta carpeta y quitar su referencia del informe. No requiere rollback ni cambios de base.
