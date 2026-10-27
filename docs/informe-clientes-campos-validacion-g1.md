# Clientes: campos, validaciones y retiro de la migración Sprint 3

Fecha: 27/10/2026. Alcance: módulo Clientes de G1, sus vistas, assets y pruebas.

## Qué se corrigió

El formulario no distinguía DNI de CUIT, guardaba el domicilio en un solo campo y no permitía asignar una zona desde el alta. La validación de estos datos tampoco era uniforme entre formulario y API. Además, la migración `2026_09_29_000002_add_sprint3_tables.php` mezclaba campos necesarios para G1 con tablas de otros dominios.

## Formulario de alta y edición

- Se agregó un selector de **DNI / CUIT** y un campo para el número. El DNI admite 7 u 8 dígitos y el CUIT, exactamente 11. Se ingresan sin puntos ni guiones. Cambiar de tipo no recorta silenciosamente el documento ya escrito: se informa la longitud incorrecta.
- **Localidad y Zona** aparecen una al lado de la otra en escritorio y se apilan en pantallas pequeñas.
- Zona permite elegir una existente mediante sugerencias o escribir una nueva. Al guardar, se reutiliza la zona existente sin distinguir mayúsculas y minúsculas, o se crea la nueva dentro de la misma transacción que guarda el cliente.
- El domicilio se separó en **Calle / nombre de dirección** y **Número / altura**. La altura admite de 1 a 6 dígitos.
- Se agregó Zona al listado de clientes y a su búsqueda.
- Se conservó la estética de G1 y se mostraron errores junto a cada campo, con foco en el primer dato incorrecto al enviar.

Las validaciones se ejecutan en JavaScript y nuevamente en Laravel. Desactivar JavaScript o enviar una petición manual no evita la validación del servidor.

| Campo | Validación |
| --- | --- |
| Nombre | Letras, acentos, espacios, puntos, apóstrofes y guiones; debe contener una letra. |
| Apellido / razón social | Letras y números, con puntuación comercial habitual, como `&`, puntos y paréntesis. |
| DNI / CUIT | Tipo seleccionado y longitud correspondiente; documento único. |
| Teléfono | Entre 6 y 15 dígitos; permite `+` inicial, espacios, paréntesis y guiones. |
| Email | Dirección válida con `@` y dominio completo, por ejemplo `gmail.com` o `empresa.com.ar`; email único. |
| Localidad | Texto válido que permite nombres como `25 de Mayo`. |
| Zona | Nombre o identificador, incluso numérico; sin etiquetas ni caracteres ajenos al campo. |
| Calle | Nombre textual o numérico, con puntuación habitual de direcciones. |
| Altura | De 1 a 6 dígitos, sin letras ni signos. |

La validación de email comprueba el formato, no la existencia del buzón. No se restringe a Gmail. La validación del documento comprueba tipo y longitud; no consulta organismos ni verifica el dígito de control del CUIT.

## Archivos agregados y modificados

Se agregaron dentro de `app/Modules/Clientes/`:

- `Rules/DocumentNumber.php`: validación de longitud y caracteres del documento.
- `Services/ClientFields.php`: reglas, mensajes y normalización compartidos por web y API.
- `Services/ClientWriter.php`: guardado transaccional de cliente y zona.

Se actualizaron el modelo `Cliente`, sus controladores web/API, los requests de alta, edición y escritura API, y `ClientResource`.

En presentación se modificaron `resources/views/clientes/{create,edit,form,index}.blade.php`; se agregaron `public/assets/css/client-form.css` y `public/assets/js/client-form.js`. En `public/assets/js/app.js` se evitó que la validación genérica anterior se aplique también al nuevo formulario.

Se agregaron pruebas en `tests/Feature/ClientFieldsTest.php` y `tests/Frontend/client-form.test.cjs`. Se adaptaron `ClientManagementTest`, `ExternalGatewaysTest`, `Sprint4ReportsTest` y `tests/TestCase.php` al retiro de la migración mezclada.

## Compatibilidad de los datos y la API

Se conserva el campo histórico `direccion`, compuesto con calle y altura. Así, las pantallas y consumidores que usan ese dato no tienen que cambiar de inmediato.

La API agrega `tipo_documento`, `direccion_calle` y `direccion_numero` sin eliminar sus campos anteriores. Sigue aceptando el domicilio completo en `direccion`; también acepta el nuevo par de campos, que debe enviarse completo. Si se envían ambos formatos con valores contradictorios, se rechaza la petición.

Para consumidores anteriores que envían DNI/CUIT sin indicar tipo, se infiere por su longitud. El CUIT conserva en almacenamiento y respuesta API el formato con guiones que ya utilizaba el contrato. En el formulario se muestra solo con dígitos.

Los domicilios anteriores se separan automáticamente solo cuando tienen una estructura simple y reconocible: calle seguida de una altura numérica. Si no se pueden separar con seguridad, se conserva el domicilio original; los nuevos campos quedan pendientes de completar al editar.

## Base local de G1

Antes de modificar la base `sistema_gestion` se generó un respaldo SQL y un inventario de estructura y datos. Se agregaron únicamente tres columnas anulables a `clientes`:

| Columna | Tipo |
| --- | --- |
| `tipo_documento` | `VARCHAR(4) NULL` |
| `direccion_calle` | `VARCHAR(200) NULL` |
| `direccion_numero` | `VARCHAR(6) NULL` |

Se aprovechó la tabla `zonas` y la relación `clientes.zona_id`, que ya existían en la base de G1. No se agregaron zonas ni clientes de demostración a la base de trabajo.

Se completaron los nuevos campos del cliente existente cuando fue posible inferirlos. Se conservaron sus campos anteriores, incluido `updated_at`. La comprobación posterior confirmó:

- Las 17 tablas existentes siguen presentes; no se creó ni eliminó ninguna tabla.
- Se conservó el único cliente que existía antes de la modificación.
- La estructura y los datos de las otras 16 tablas permanecen iguales al respaldo previo.
- No se modificaron `.env`, las bases de otros equipos ni código de G2, G3 o G4.

## Migración eliminada y reemplazo exclusivo de G1

Se eliminó **solo el archivo** `database/migrations/2026_09_29_000002_add_sprint3_tables.php`, como se solicitó. Se conservó una copia externa para revertir la eliminación si fuera necesario.

**No se ejecutó su rollback, no se borraron las tablas históricas que hubiese creado y no se alteró la tabla de historial de migraciones.** Eliminar un archivo no desinstala sus cambios anteriores de la base.

Inicialmente se preparó la base local mediante los cambios puntuales autorizados en `clientes`, sin una migración nueva. A continuación, por pedido del usuario, se agregó **`database/migrations/2026_10_27_000001_complete_g1_clientes_schema.php`** para reproducir el esquema de G1:

- Requiere que exista `clientes`, creada por la migración base de G1. Si falta, se detiene antes de hacer cambios.
- Crea `zonas` solo si no existe.
- Agrega a `clientes` únicamente las columnas ausentes: `localidad`, `zona_id`, `condicion_iva`, `saldo`, `tipo_documento`, `direccion_calle` y `direccion_numero`. `saldo` conserva el campo histórico de la ficha de Clientes; no instala tablas ni operaciones contables de otros equipos.
- Al agregar `zona_id`, crea su clave foránea hacia `zonas`, con actualización en cascada y `SET NULL` al eliminar la zona.
- Conserva los campos, relaciones y registros que ya existen; no normaliza ni sobrescribe datos en la migración. Admite la base local que ya se había actualizado manualmente y ejecuciones repetidas sin duplicar columnas.
- No incluye tablas de productos, marcas, categorías, ventas, detalles de venta ni cobros.

Se retiró `tests/Support/ClientSchema.php` y su llamada global desde `tests/TestCase.php`: las pruebas funcionales ahora utilizan esta migración real. Las pruebas que solo renderizan presentación preparan una tabla temporal de zonas vacía, limitada a SQLite en memoria, sin instalar tablas de otros grupos.

En esta segunda etapa **se creó y probó el archivo; no se ejecutó sobre la base local ni se iniciaron servidores**. La base local ya tiene los campos. Para registrar esta migración allí, con MySQL iniciado y `.env` apuntando a la base propia de G1, se puede ejecutar únicamente este archivo desde la raíz del proyecto:

```powershell
C:\xampp\php\php.exe artisan migrate --path=database/migrations/2026_10_27_000001_complete_g1_clientes_schema.php
```

No reemplazarlo por un `migrate` general: el proyecto descubre migraciones de otros equipos. En una instalación G1 vacía se deben aplicar primero las migraciones propias anteriores (base ERP y recuperación de contraseña), también seleccionadas por ruta; este archivo complementa esas migraciones y no las sustituye.

El método `down()` rechaza el rollback automático con un mensaje explícito. Esto es deliberado: los campos y zonas pueden existir desde antes de ejecutar esta migración, por lo que eliminarlos en un rollback podría borrar datos anteriores. Una reversión de estructura requiere revisar el respaldo y preparar una intervención específica de G1.

## Verificaciones realizadas

- Suite PHP segura, después de agregar la migración: **179 pruebas, 1226 aserciones**, aprobadas con base temporal en memoria y configuración aislada. No se ejecutaron las clases que fuerzan conexiones MySQL de otros módulos.
- Cuatro pruebas específicas de la migración: instalación sobre la base G1, conservación de clientes y zonas al repetirla, rechazo sin cambios si falta `clientes` y bloqueo de rollback destructivo.
- Pruebas JavaScript: **28 aprobadas**, incluidas las 4 del nuevo formulario.
- Prueba en MariaDB sobre una copia aislada del respaldo: acceso G1, alta con zona nueva, persistencia de zona, edición de domicilio, compatibilidad de la respuesta API y rechazo de email/documento inválidos. Todas aprobadas; sin escribir datos de prueba en `sistema_gestion`.
- Revisión visual de alta y edición en escritorio. Medición de la vista de alta a 430, 760 y 1440 píxeles, sin desbordamiento horizontal.
- Comprobación de alcance por hashes de archivos y de tablas respecto del estado anterior.

Los respaldos y evidencias se guardaron **fuera del proyecto**, en `Entregables/Clientes_G1_2026-10-27/`, junto a `ERP-Juan23/`. El SQL contiene información privada y no debe publicarse ni incluirse en un commit.

Al finalizar se cerraron los procesos temporales y el MySQL iniciado para esta tarea. Se verificó que no quedaran servicios escuchando en 3306, 8000, 8001, 33317 ni 33318, y se eliminó el archivo temporal de credenciales de conexión.

## Cómo revertir estos cambios

1. Conservar un respaldo nuevo si ya se cargaron clientes o zonas después de esta modificación. No restaurar el SQL anterior sobre datos nuevos sin revisarlos.
2. Reponer únicamente los archivos modificados por esta tarea desde `Entregables/Clientes_G1_2026-10-27/archivos-antes/`. Allí están sus versiones al comenzar el trabajo, incluidas las modificaciones previas que ya existían. No usar un reset general del repositorio.
3. Retirar los archivos nuevos enumerados en este informe —regla, servicios, CSS, JavaScript y pruebas— al restaurar el código anterior que no los utiliza. Revertir también las adaptaciones de las pruebas.
4. Si se decide recuperar el archivo Sprint 3, su copia está en `Entregables/Clientes_G1_2026-10-27/2026_09_29_000002_add_sprint3_tables.php.antes`. Reponerlo no implica ejecutarlo: contiene definiciones de otros dominios y no debe aplicarse automáticamente.
5. Para revertir el código, **no hace falta eliminar las tres columnas nuevas**: al ser anulables pueden quedar sin uso y así se conservan los datos ingresados. Su eliminación o una reversión de datos requiere una intervención posterior limitada a G1, con respaldo y autorización específicos. No ejecutar rollback general ni borrar tablas de otros grupos.

Si se quiere retirar solamente la nueva migración y todavía no fue aplicada, se puede eliminar su archivo sin cambiar la base. Si ya fue registrada en otro entorno, no borrar su historial ni forzar el rollback: su `down()` evita eliminar estructuras o datos anteriores. Mantener el archivo y preparar una reversión específica si se necesitara. Las pruebas nuevas de la migración están en `tests/Feature/G1ClientMigrationTest.php`.

El respaldo previo completo es `Entregables/Clientes_G1_2026-10-27/g1-antes-clientes.sql`. Se conserva como recuperación, no como una instrucción de restauración indiscriminada.
