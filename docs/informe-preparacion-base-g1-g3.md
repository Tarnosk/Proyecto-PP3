# Preparación de la base de trabajo G1 + G3

> **Retirado el 27/10/2026 por instrucción del usuario.** G1 volvió a `sistema_gestion`; se respaldaron y eliminaron de nuestra copia las 25 tablas que había agregado el instalador. La herramienta se retiró del proyecto y `CATALOG_SERVICE_URL` quedó sin proveedor hasta recibir la API de G3. Este documento conserva el antecedente; no ejecutar sus pasos como instalación vigente. Ver [informe de retiro](informe-retiro-integracion-local-g3.md).

> **Actualización posterior del 26/10/2026:** se reemplazó el arranque con dos servidores PHP por Apache de XAMPP en un único puerto, 8000. La base preparada en esta intervención se conserva. El arranque vigente y la reversión exclusiva de Apache están en [informe de puerto único](informe-apache-puerto-unico-g1.md). Las instrucciones de dos terminales que siguen documentan el estado anterior a ese cambio.

**Fecha:** 25 y 26 de octubre de 2026. **Rama local:** `develop`. **Commit de referencia:** `d01b5ca690f95c0d255800c9bac89349fa08942d`.

## 1. Resultado y alcance

Se preparó y seleccionó una base nueva, `sistema_gestion_g1_g3_20261026`, para usar G1 y G3 con los archivos de esta misma rama. Las APIs de categorías, marcas y productos responden correctamente y permiten alimentar el Inventario y el Catálogo público de G1.

La base anterior, `sistema_gestion`, quedó intacta. No se vació, renombró ni reemplazó. La nueva base contiene una copia de sus 17 tablas y registros, más 25 tablas de G3. Esto permite regresar al entorno anterior mediante configuración, sin deshacer tablas en uso.

Por elección expresa del usuario, se cargaron **categorías y marcas oficiales, sin productos de demostración**. Se mantuvieron las cuatro cuentas existentes de G1 y sus contraseñas. No se creó un administrador con contraseña por defecto.

La carga de productos con y sin stock se comprobó en un clon descartable. En la base de trabajo se verificaron las APIs y el Catálogo mediante GET, sin insertar productos, cuentas ni movimientos de prueba.

Se aplicó la autorización específica para preparar G1 + G3. Los archivos originales de G3 no se modificaron; las adaptaciones de instalación se hicieron desde una herramienta perteneciente a G1. Tampoco se modificaron controladores, modelos, rutas, migraciones ni datos originales de G2 o G4.

La base anterior ya tenía tablas de ventas, detalles y cuenta corriente de una intervención previa. Se copiaron tal como estaban: no se realizó una nueva instalación de G4. No fue necesario instalar G2 para permitir el alta de productos. No se incorporaron fuentes de otras ramas, SQL históricos descargados ni versiones remotas.

## 2. Qué estaba fallando

### 2.1. El esquema de G3 no estaba instalado

La captura mostraba `SQLSTATE[42S02]`, error MySQL 1146: faltaba `PRODUCTO` en `sistema_gestion`. La validación del código único consultaba esa tabla antes de guardar el producto.

Existía `productos`, en plural, pero no es un alias de la tabla singular: tiene otro contrato. Renombrarla o cambiar el modelo para usarla habría confundido esquemas sin instalar las unidades, ubicaciones, precios, movimientos y auditoría que espera G3.

Las consultas de categorías y marcas también dependen de las tablas y relaciones del esquema de G3, incluyendo conteos de productos. Por eso no alcanzaba con agregar opciones manuales en los selectores del frontend.

### 2.2. Los tipos de una referencia a Ventas eran incompatibles

La definición base de G3 establece `movimiento_stock.id_venta INT(11)` con signo. La tabla ya instalada tiene `venta.id_venta BIGINT(20) UNSIGNED`. MySQL exige compatibilidad de tipo y signo para una FK.

Las pruebas anteriores de instalación conjunta habían encontrado error 1005/errno 150 por esta diferencia. Desactivar las comprobaciones no corrige tipos y puede trasladar el fallo a la creación posterior de la tabla referenciada.

### 2.3. Faltaba la tabla referenciada de Entregas

G3 también declara una FK desde `movimiento_stock.id_entrega`, opcional, hacia `entrega.id_entrega`. Esa tabla de G2 no existe en este entorno. Instalar todo G2 para satisfacer una referencia opcional no era necesario para cargar productos.

### 2.4. La semilla completa agregaba datos no solicitados

La migración de semilla de G3 incluye productos P001–P006, existencias de ejemplo y lógica de creación de una cuenta por defecto si no hay usuarios. Ejecutarla completa habría incumplido la elección de no cargar productos demo.

### 2.5. El consumidor del Catálogo no tenía proveedor configurado

`CATALOG_SERVICE_URL` estaba vacío en el `.env` local. Instalar las tablas no configura automáticamente el consumo HTTP de G1. Además, en el servidor integrado de PHP para Windows conviene separar el proceso que atiende la página del que recibe sus consultas HTTP a la API.

## 3. Respaldos y procedimiento

### 3.1. Protección del origen

Con MySQL detenido se copió la carpeta de datos de XAMPP y se verificó cada archivo mediante SHA-256. Luego se inició MySQL y se generó un respaldo lógico SQL de `sistema_gestion`.

El SQL se restauró en una instancia descartable de MariaDB, puerto `33317`. La comparación de las 17 tablas, definiciones, cantidades y huellas de filas coincidió con el origen. No se verificó solamente que existiera un archivo: se comprobó que pudiera restaurarse.

Los respaldos y evidencias están fuera del repositorio Git del proyecto:

```text
C:\Users\juanf\OneDrive\Escritorio\Repositorio\Entregables\Integracion_G1_G3_2026-10-25\
```

La carpeta conserva la fecha del inicio de la intervención. Contiene datos privados y hashes de contraseñas: no debe publicarse junto con la documentación.

### 3.2. Fuentes exactas de la rama actual

- `app/Modules/Productos/database/migrations/2026_09_30_000000_create_g3_base_tables.php`: definiciones de las 25 tablas base.
- Las doce migraciones complementarias de esa carpeta, fechadas entre el 1 y el 16 de octubre: sugerencias, pagos y plazos de proveedores, stock, unidades, compras y presentación comercial.
- `2026_10_24_000001_seed_g3_catalog_for_integration.php`: exclusivamente las listas de categorías y marcas. **No se ejecutó su `up()` completo**.
- El respaldo de la base local anterior: fuente de los registros existentes, no de productos nuevos.

Se conservaron huellas SHA-256 de las catorce migraciones para identificar las versiones utilizadas.

### 3.3. Herramienta nueva de G1

Se agregó `app/Modules/Catalogo/Tools/preparar-base-g1-g3.php` y su manifiesto `fuentes-g3.json`. Es una herramienta de preparación de una copia, no un cambio de lógica comercial de G3.

Su comportamiento:

1. Exige un destino distinto de la base seleccionada en `.env`, con nombre y puerto admitidos y carpeta de evidencia existente.
2. Rechaza sobrescribir cualquiera de las 25 tablas que pretende crear. No sirve para actualizar indiscriminadamente bases de G3 ya pobladas.
3. Valida las huellas de las migraciones revisadas. Si cambian o aparecen otras, se detiene para exigir revisión.
4. Extrae las sentencias `CREATE TABLE` originales y genera un plan JSON y el SQL resultante. Sin `--apply`, no modifica la base.
5. Crea las tablas antes de agregar sus relaciones. Mantiene las comprobaciones de integridad activadas.
6. Ejecuta solamente los `up()` complementarios revisados de G3 sobre las tablas nuevas y registra su tratamiento en `migrations`. No ejecuta ningún `down()`.
7. Refleja las cuentas existentes de G1 en la tabla singular `usuario`, necesaria para las FKs de autoría de G3. Conserva identificador, usuario, hash, nombre, rol y estado; rechaza identidades incompatibles en lugar de cambiarles el rol. No modifica `usuarios` de G1.
8. Inserta solo las categorías y marcas extraídas de la semilla oficial; no crea productos ni cuentas por defecto.

El DDL de MySQL no se revierte como una transacción ordinaria. La protección principal es usar una copia y seleccionarla solo después de verificarla. La semilla y el reflejo de usuarios sí se agruparon en una transacción de datos.

### 3.4. Adaptaciones concretas

| Elemento | Tratamiento | Motivo |
| --- | --- | --- |
| `movimiento_stock.id_venta` | Se creó como `BIGINT(20) UNSIGNED`, leyendo el tipo real de `venta.id_venta`; se mantuvo su FK. | Evitar incompatibilidad sin modificar la tabla de G4. |
| FK `fk_mov_stock_entrega` | Se difirió su creación; `id_entrega` se conservó como columna opcional. | No existe `entrega` y no se necesita para el alta de productos. |
| Relaciones internas G3 | Se agregaron después de crear todas las tablas base. | Resolver el orden con integridad activa. |
| Cuentas G1 → `usuario` | Cuatro reflejos en una tabla nueva, sin cambiar las cuentas de origen. | Satisfacer autoría y auditoría. |
| Semilla | Tres categorías y seis marcas, sin productos ni stock demo. | Respetar la elección del usuario. |

**Límite pendiente:** no existe integridad referencial con Entregas. Antes de conectar esa operación se debe validar el tipo real de `entrega.id_entrega`, compatibilizar la columna nueva si corresponde, revisar datos y agregar la relación. No se presenta Entregas como una integración completa.

### 3.5. Política de historial de migraciones

La entrada `2026_10_24_000001_seed_g3_catalog_for_integration` se registró como **conciliada mediante la semilla selectiva**, no como prueba de ejecución de su `up()` completo. El resultado JSON identifica esta política.

Esto evita que un `migrate` posterior considere pendiente esa semilla y agregue productos demo rechazados por el usuario. La diferencia es relevante para auditoría: la unidad fue atendida mediante una ejecución deliberadamente parcial y controlada.

No usar `migrate:rollback` general para deshacer esta instalación. Los `down()` originales no representan las adaptaciones de este procedimiento ni su política de semilla.

### 3.6. Base resultante

Se agregaron estas 25 tablas:

```text
usuario, categoria, marca, producto, proveedor,
unidad_medida, ubicacion, producto_ubicacion, lote,
movimiento_stock, sesion_conteo, detalle_conteo,
historial_precio, producto_proveedor, historial_precio_proveedor,
historial_plazo_proveedor, orden_compra, detalle_orden,
compra, detalle_compra, recepcion, detalle_recepcion,
pago_proveedor, sugerencia_compra, log_auditoria
```

Categorías: **Bebidas, Panadería, Almacén**.

Marcas: **Coca-Cola, Bimbo, Playadito, Natura, Gallo, Matarazzo**.

Al cierre hay 42 tablas en la base nueva y cero filas en `producto`. Se conservan las cuatro cuentas originales de G1, sus cuatro reflejos nuevos en `usuario` y los 17 registros anteriores de `login_logs`. No quedaron operaciones de prueba en la base de trabajo. Estas cantidades cambiarán cuando se use el sistema.

## 4. Archivos agregados y modificados

| Archivo | Cambio de esta intervención |
| --- | --- |
| `app/Modules/Catalogo/Tools/preparar-base-g1-g3.php` | Instalador controlado de la copia. |
| `app/Modules/Catalogo/Tools/fuentes-g3.json` | Huellas de las fuentes G3 revisadas. |
| `.env` local, ignorado por Git | `DB_DATABASE=sistema_gestion_g1_g3_20261026` y `CATALOG_SERVICE_URL=http://127.0.0.1:8001`. |
| `README.md` | Estado local, arranque manual y enlace a este informe. |
| `docs/informe-implementacion-catalogo-g1.md` | Nota posterior que remite al estado actual. |
| Este informe | Causas, procedimiento, verificaciones, límites y reversión. |

Fuera del repositorio se agregaron respaldos, planes, huellas, scripts de pruebas y logs. Los cambios del Catálogo que ya estaban pendientes antes de empezar se conservaron; no deben confundirse con cambios nuevos de esta instalación.

## 5. Verificaciones

### 5.1. En laboratorio, con escrituras permitidas

- Restauración del respaldo SQL y comparación exacta con el origen.
- Instalación de las 25 tablas y complementos, también con la versión final del instalador y su control de huellas.
- Preservación de filas y esquemas previos, salvo las entradas nuevas previstas en `migrations` de la copia.
- Login API de G1 con cuenta exclusiva del laboratorio; consulta de clientes, logout y rechazo posterior del token revocado.
- Categorías y marcas disponibles mediante API; listado de productos inicialmente vacío.
- Alta de producto sin stock inicial: HTTP 201.
- Rechazo de código duplicado: HTTP 400.
- Alta de producto con stock inicial: HTTP 201.
- Precio `3210,75`, stock `12`, dos historiales de precio y un movimiento de ingreso, correspondientes a los dos productos de prueba del clon.
- Catálogo público y datos: HTTP 200; producto visible, precio mostrado como `3.210,75`.
- Compatibilidad de la referencia a ventas y `FOREIGN_KEY_CHECKS=1`.

Las cuentas y productos de estas pruebas no se trasladaron a la base de trabajo.

### 5.2. En la base de trabajo, sin operaciones de prueba

Se comprobó HTTP 200 en:

```text
GET /api/v1/categorias
GET /api/v1/marcas
GET /api/v1/productos
GET /catalogo
GET /catalogo/datos
```

Se compararon nuevamente las huellas de tablas antes y después de las consultas: no generaron escrituras. La base original `sistema_gestion` también siguió coincidiendo con su estado anterior completo.

### 5.3. Regresión de G1

Pasaron **54 pruebas PHP y 303 aserciones**: Catálogo, autenticación, escritura API de clientes, clientes, reclamos, sesiones y recuperación de contraseña. Se ejecutaron con SQLite en memoria y almacenamiento aislado, sin usar MySQL de trabajo.

Pasaron **7 pruebas JavaScript del Catálogo**. El instalador pasó la comprobación de sintaxis PHP.

La revisión final verificó los 175 archivos del respaldo físico y confirmó que, entre los archivos existentes al comenzar, solo cambiaron `.env`, `README.md` y la nota del informe previo del Catálogo. Las fuentes de los otros grupos conservaron sus huellas. Los puertos PHP de prueba quedaron cerrados y MySQL siguió disponible en 3306. El instalador también pasó la comprobación de formato con Pint y la revisión de espacios de Git no encontró errores.

Estas pruebas cubren la preparación y recorridos indicados. No certifican todos los procesos de compras, ventas, rendiciones, conciliación o entregas: aquí no se hicieron pruebas funcionales completas de esos dominios.

## 6. Cómo levantarlo manualmente

Iniciar **MySQL desde XAMPP**. Abrir dos terminales ubicadas en `ERP-Juan23`.

Terminal de API:

```powershell
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8001
```

Terminal de interfaz:

```powershell
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

- ERP: `http://127.0.0.1:8000`.
- Alta de productos: `http://127.0.0.1:8000/inventario`.
- Catálogo público sin login: `http://127.0.0.1:8000/catalogo`.

Ambos procesos son necesarios para el consumidor HTTP del Catálogo, configurado hacia 8001. Si solo funciona 8000, el Inventario puede consultar sus APIs en el mismo origen, pero el Catálogo no alcanzará el proveedor indicado. No apuntar el consumidor al mismo proceso PHP que está atendiendo la página.

No hace falta ejecutar migraciones ni semillas para usar esta base. No ejecutar `migrate:fresh`, `migrate:refresh`, semillas globales o un rollback general como paso de arranque.

Los servidores PHP de prueba se detuvieron al terminar. MySQL quedó iniciado. La interfaz queda para arranque manual, conforme a la preferencia del usuario.

## 7. Errores y consecuencias que se evitaron

| Situación sin corregir | Consecuencia observada o posible | Medida aplicada |
| --- | --- | --- |
| Falta `producto` | Error 1146 al validar/listar; el alta no se guarda. Observado en la captura. | Instalación del esquema correcto. |
| Faltan relaciones de categorías y marcas | Selectores vacíos o consultas fallidas pese a existir tablas plurales. | Tablas, relaciones y datos oficiales mínimos. |
| Cambiar solo el nombre de una tabla plural | Columnas, relaciones y reglas incompatibles; consultas o escrituras incorrectas. | Se conservaron ambos esquemas. |
| `INT` con signo contra `BIGINT UNSIGNED` | Error FK 1005/errno 150, instalación parcial o fallo posterior al crear la tabla referenciada. Observado en pruebas previas. | Tipo compatible solamente en la nueva tabla G3. |
| Desactivar integridad sin revisar referencias | Huérfanos o errores aplazados hasta integrar otro grupo. | Integridad activa; referencia a Entregas diferida explícitamente. |
| Ejecutar todas las migraciones de todos los grupos | Cambios fuera del alcance o instalación parcial con DDL ya confirmado. | Lista revisada de G3 sobre una copia. |
| Importar SQL históricos indiscriminadamente | Duplicados, sintaxis incompatible, recreaciones o pérdida de datos según el archivo. | Solo fuentes actuales revisadas y restauraciones en bases nuevas. |
| Ejecutar la semilla demo | Productos y stock ficticios, cuentas por defecto o identificadores en conflicto. | Semilla selectiva y productos en cero. |
| Reasignar identidades por coincidencias incompletas | Autoría errónea, FK inválida o elevación involuntaria de rol. | Reflejo exacto en tabla nueva; rechazo de incompatibilidades. |
| Página y proveedor en el mismo proceso PHP | Esperas y timeout por llamada a sí mismo en el servidor Windows. | Dos procesos y proveedor 8001. |
| Altas de prueba en la base del usuario | Productos, usuarios y movimientos ficticios mezclados con datos reales. | Escrituras de prueba solo en clones. |
| Suponer que producto nuevo implica stock | Disponibilidad falsa o ventas de existencias inexistentes. | Pruebas separadas con/sin stock; cantidad cero cuando no se informa. |
| Volver al origen después de trabajar en la copia | Los datos nuevos no aparecen allí, aunque permanezcan en la base nueva. | Respaldar la nueva y revertir por configuración, sin eliminarla. |

Los riesgos posibles no significan que se hayan perdido datos. La comparación del origen confirmó que esta intervención no lo modificó.

No se ejecutó la migración histórica de G2 que eliminaba tablas ajenas, identificada en el relevamiento previo: no forma parte de este procedimiento.

## 8. Límites pendientes

- Entregas de G2 todavía no tiene su FK ni integración operativa con estos movimientos.
- No se completaron APIs de cobros/saldos de G2 ni se cambiaron los mocks de G4 por integraciones reales.
- No se modificaron permisos, autenticación ni resolución de usuario del backend G3. Corregir su instalación local no certifica que su API sea adecuada para publicación en Internet.
- El Catálogo inicialmente vacío es correcto. Se poblará al cargar productos reales desde Inventario.
- No se inventaron ubicaciones, imágenes ni movimientos. Sin ubicaciones se puede guardar sin asignación física, conforme al flujo existente.
- Las migraciones futuras requieren revisar las huellas, dependencias y política de semilla antes de reutilizar el instalador.

## 9. Evidencias conservadas

En la carpeta externa indicada están:

- `respaldo-fisico-previo/` y `respaldo-fisico-manifiesto.json`: copia en frío verificada.
- `sistema_gestion-antes.sql`: respaldo original restaurado y comprobado.
- `.env.antes-integracion`: configuración completa anterior; contiene secretos y no se publica.
- `sistema_gestion_g1_g3-preparada.sql`: respaldo adicional de la base preparada.
- `estado-antes.json`, `origen-despues.json`, `trabajo-preparado.json`, `trabajo-post-get.json`: estructuras, cantidades y huellas.
- `plan-sistema_gestion_g1_g3_20261026.json`, `esquema-sistema_gestion_g1_g3_20261026.sql`, `resultado-sistema_gestion_g1_g3_20261026.json`: plan, DDL adaptado y resultado.
- `pruebas-integracion.json` y `verificacion-aplicacion.json`: resultados de laboratorio y aplicación.
- `fuentes-antes.json`: huellas del código antes de esta intervención.

Los scripts externos de prueba no son comandos de arranque ni deben apuntarse indiscriminadamente a una base poblada.

## 10. Cómo revertir

### 10.1. Volver al entorno anterior sin eliminar datos

1. Detener ambos servidores PHP con `Ctrl+C`.
2. Respaldar la base nueva si ya se cargaron productos u operaciones después de esta intervención. Volver al origen no fusiona esos registros.
3. Editar únicamente estas claves del `.env`:

```dotenv
DB_DATABASE=sistema_gestion
CATALOG_SERVICE_URL=
```

Estos eran sus valores inmediatamente anteriores. Si no hubo cambios posteriores de configuración, también se conserva `.env.antes-integracion` en el respaldo externo. Si hubo otros cambios, recuperar solamente los valores necesarios.

4. Si después se generó caché de configuración, ejecutar:

```powershell
C:\xampp\php\php.exe artisan config:clear
```

5. Volver a iniciar los servidores necesarios. Regresará el entorno anterior, incluyendo su limitación: allí no están instaladas las tablas singulares de G3, por lo que el alta de productos volverá a carecer de ese esquema.

La base nueva se puede conservar para recuperar registros. **No hace falta borrarla para revertir**. No usar rollback de migraciones, `migrate:fresh`, borrados de tablas ni reset general del repositorio.

### 10.2. Retirar archivos de esta intervención

Si también se desea retirar la herramienta y la documentación, quitar únicamente los dos archivos nuevos de `app/Modules/Catalogo/Tools/`, este informe y las notas añadidas al README y al informe anterior del Catálogo. No eliminar el módulo Catálogo completo: ya era trabajo pendiente antes de esta preparación.

Si se crea un commit exclusivo, puede revertirse para retirar sus archivos. Eso no revierte automáticamente `.env` ni bases MySQL: requiere el cambio de configuración anterior.

### 10.3. Restaurar desde un respaldo

Si hiciera falta recuperar desde SQL, restaurar `sistema_gestion-antes.sql` en **otra base nueva y vacía**, comprobarla y apuntar `.env` hacia ella. El volcado recrea tablas: no importarlo sobre datos existentes sin otro respaldo y un procedimiento específico.

La copia física no debe pegarse sobre el directorio de datos con MySQL en ejecución. Es un respaldo adicional del servidor, no el mecanismo rutinario para deshacer esta integración. Para volver al estado previo alcanza con seleccionar la base original intacta.
