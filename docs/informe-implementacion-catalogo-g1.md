# Implementación del Catálogo público de G1

> **Estado del 27/10/2026:** se retiró la instalación de G3 en nuestra copia local y G1 volvió a su base anterior. El módulo Catálogo conserva su consumo exclusivo de APIs, pero no tiene proveedor configurado hasta que G3 publique el suyo. No reinstalar tablas ajenas desde G1. Ver [informe de retiro de la integración local](informe-retiro-integracion-local-g3.md).

> **Arranque vigente:** el ERP y sus APIs locales ahora se sirven con Apache de XAMPP en el puerto 8000. Ver [configuración de puerto único](informe-apache-puerto-unico-g1.md). Los ejemplos con dos procesos PHP de este informe corresponden al procedimiento inicial.

> **Actualización del 26/10/2026:** este informe describe la implementación inicial del día 25. Posteriormente se preparó una copia de trabajo con G1 + G3 y se cambiaron `DB_DATABASE` y `CATALOG_SERVICE_URL` del `.env` local. El estado vigente, las pruebas y su reversión están en [informe de preparación de la base G1 + G3](informe-preparacion-base-g1-g3.md). Las afirmaciones de este documento sobre no haber modificado `.env` o la base corresponden a la intervención inicial.

**Fecha:** 25/10/2026.  
**Alcance:** consulta pública de G1, sin modificaciones de código, esquemas o datos de G2, G3 o G4.  
**Base local de esta intervención:** `d01b5ca`, con árbol de trabajo limpio antes de comenzar.

## 1. Qué faltaba

Existían las historias [#109, Catálogo actualizado](https://github.com/rapiriz/ERP-Juan23/issues/109) y [#110, Web de disponibilidad](https://github.com/rapiriz/ERP-Juan23/issues/110), pero no un módulo propio de Catálogo de G1. El consumidor existente de Reportes proyectaba identificador, código y nombre: no era una pantalla comercial ni conservaba precio, categoría, presentación o disponibilidad.

El usuario definió expresamente acceso sin iniciar sesión. Se tomó como referencia conceptual la organización por categorías y las tarjetas de `productos.pybdistribuciones.com.ar`, junto con las capturas proporcionadas. El sitio no pudo inspeccionarse desde las herramientas disponibles. No se copiaron código, productos ni fotografías de ese sitio; el diseño se adaptó a los azules y fondos claros del ERP.

Esta etapa permite explorar, buscar, filtrar y consultar detalles. No incluye compra online ni edición de artículos, excluidas del alcance de esas dos historias.

## 2. Qué se agregó y por qué

| Componente de G1 | Implementación y motivo |
| --- | --- |
| `app/Modules/Catalogo/Controllers/CatalogoController.php` | Listado, actualización y detalle públicos. Maneja indisponibilidad sin mostrar excepciones internas. |
| `app/Modules/Catalogo/Services/CatalogoGateway.php` | Consumidor HTTP GET de G3. Valida respuestas; no consulta tablas ajenas directamente. |
| `app/Modules/Catalogo/Services/CatalogoService.php` | Búsqueda, filtros, facetas, ordenamiento y paginación de la consulta pública. |
| `app/Modules/Catalogo/Data/ProductoCatalogo.php` | Proyección comercial explícita. Evita reenviar usuarios, auditoría y cantidades exactas de inventario. |
| `app/Modules/Catalogo/README.md` | Responsabilidades y organización. |
| `resources/views/catalogo/` | Layout público, listado, detalle, errores, tarjetas, iconos e imágenes opcionales. |
| `public/assets/css/catalogo.css` | Estilos exclusivos del Catálogo, con escritorio, móvil y movimiento reducido. |
| `public/assets/js/catalogo.js` | Menú, búsquedas sin recargar, renovación del listado y recuperación de errores. |
| `config/catalogo.php` | Intervalo de renovación y hosts de imágenes aprobados. |
| `tests/Feature/CatalogoPublicoTest.php` | Pruebas del contrato y acceso público sin base comercial. |
| `tests/Frontend/catalogo.test.cjs` | Pruebas de actualización, fallos, carreras entre consultas, filtros y menú. |

La organización conserva el criterio de Clientes y Login: clases funcionales dentro de `Modules`, vistas y rutas centrales. No se agregaron modelos ni migraciones porque esta función no posee tablas y no debe duplicar el maestro de G3. Tampoco se refactorizó el consumidor histórico de Reportes: sus reglas quedaron separadas de esta implementación pública.

## 3. Archivos existentes modificados

- `routes/web.php`: agrega las tres rutas GET públicas y el import del controlador. El inicio del ERP y las rutas protegidas conservan sus destinos y permisos.
- `resources/views/partials/sidebar-links.blade.php`: agrega “Catálogo de productos” en el menú interno de G1.
- `resources/views/auth/login.blade.php`: agrega el acceso al catálogo sin iniciar sesión.
- `.env.example`: documenta `CATALOG_IMAGE_HOSTS`, opcional y vacío por defecto.
- `README.md`: incorpora el acceso al módulo y este informe.

La configuración real `.env` no fue alterada. No se ejecutaron migraciones, seeders ni importaciones SQL.

## 4. Correcciones y decisiones de presentación

- Cabecera pública con marca, inicio del catálogo y acceso interno separado del acceso de clientes.
- Portada breve, colores del ERP y consulta sin registro.
- Categorías con cantidades; no se inventan categorías vacías ni jerarquías ausentes del proveedor.
- Menú móvil plegable con estado accesible, foco al abrir/cerrar, Escape y navegación por teclado.
- Tarjetas con marca, código, nombre, presentación, categoría, precio de referencia y disponibilidad.
- Fotografías cuando exista una URL permitida; imagen neutra cuando falten o no carguen.
- Detalle con descripción, presentación, marca, categoría y código.
- Alternativas disponibles de la misma categoría. No son recomendaciones personalizadas ni equivalencias comerciales garantizadas.
- Mensajes diferenciados para catálogo vacío, búsqueda sin resultados, conexión fallida y producto no publicado.

El menú público contiene categorías y filtros, no datos de sesión ni opciones administrativas ajenas. El menú interno conserva su composición y agrega un acceso al Catálogo. Se corrigió dentro del CSS nuevo la herencia de color de los `span` de etiquetas: el estilo general de formularios los coloreaba de rojo y no correspondía a las categorías.

## 5. Búsqueda, filtros y disponibilidad

Se busca por nombre, descripción, código, marca, categoría y presentación. Se normalizan mayúsculas, acentos y algunos formatos de litros/separadores decimales. Ante cero coincidencias exactas se admiten diferencias pequeñas de escritura en palabras alfabéticas y se avisa que los resultados son aproximados. No es un motor semántico ni un diccionario completo de equivalencias.

Categoría, marca y disponibilidad son combinables. Las opciones se calculan a partir de los productos publicados y de los otros filtros. Se ordena por nombre, código o precio; los precios desconocidos quedan al final. Se muestran doce artículos por página y se conservan los filtros al navegar.

| Dato recibido | Estado público |
| --- | --- |
| Stock disponible positivo, mayor que el mínimo o sin mínimo informado | Disponible. |
| Stock disponible positivo y menor o igual al mínimo informado | Últimas unidades. |
| Stock disponible cero o negativo | Sin stock. |
| Stock disponible ausente o nulo | Consultar disponibilidad. |

No se confirma disponibilidad desde el stock físico total. Un fallo de conexión tampoco equivale a stock cero. Se admiten descripciones nulas si existe un nombre válido. Si falta precio se muestra “Consultar precio”; si falta presentación no se inventa un empaque. El precio de lista conserva dos decimales: no se deducen IVA, descuentos ni condiciones de un cliente.

## 6. API consumida y proyección pública

Se utiliza la configuración existente `services.catalog` de G1:

```dotenv
CATALOG_SERVICE_URL=http://127.0.0.1:8001
CATALOG_PRODUCTS_PATH=/api/v1/productos
CATALOG_SERVICE_TOKEN=
CATALOG_IMAGE_HOSTS=
```

El puerto es un ejemplo para dos procesos de desarrollo; no se activó ese servicio ni se modificó el entorno real.

El consumidor pide `GET /api/v1/productos` con `estado=activo` y vuelve a verificar el estado de cada producto. Lee la paginación compatible con el cliente de G1 sin seguir enlaces externos. Rechaza identificadores repetidos/inconsistentes y respuestas inválidas. El detalle consulta `GET /api/v1/productos/{id}`; un artículo inactivo o inexistente se presenta como no publicado.

Los filtros públicos se resuelven en G1 sobre la lectura completa. Se evita depender de la discrepancia entre los parámetros documentados por G3 y los efectivos de su controlador. Para volúmenes grandes habrá que acordar filtrado/paginación de origen o una proyección persistente exclusivamente de G1.

No se envían credenciales del visitante ni cabeceras de identidad declarada. Un token de servicio configurado permanece en el servidor. Sólo se publica la proyección comercial; los textos externos se escapan en Blade y los filtros dinámicos se construyen con `textContent`. Las imágenes sólo admiten rutas locales limitadas o HTTPS en hosts previamente aprobados mediante `CATALOG_IMAGE_HOSTS`.

## 7. Acceso público sin base de sesiones

Las rutas GET excluyen únicamente para su grupo el inicio de sesión de Laravel, el intercambio de errores mediante sesión y el middleware CSRF asociado. No hay formularios de escritura ni rutas POST, PUT o DELETE del módulo. Las protecciones internas existentes no cambian.

Se construyen respuestas HTTP/JSON directamente: la fábrica general de respuestas de esta versión de Laravel resuelve el servicio de redirecciones y puede abrir una sesión de base aunque no haya login. La prueba específica exige cero conexiones incluso configurando sesiones `database`. La cabecera pública no llama a `auth()` ni consulta usuarios.

Así puede mostrarse un mensaje de indisponibilidad aunque la base comercial no esté accesible, en lugar de romper la pantalla antes de renderizarla.

## 8. Actualización y fallos

Cada consulta obtiene datos por HTTP. No se creó una copia persistente ni se devuelve una instantánea antigua como información nueva. El listado puede renovarse cada 60 segundos si la pestaña es visible, no hay otra petición y el foco está fuera del formulario. También se consulta al buscar, filtrar, navegar páginas o pulsar “Actualizar”. El detalle se renueva al abrirlo o pulsar “Actualizar consulta”.

Las respuestas llevan `Cache-Control: no-store`. Las peticiones anteriores se cancelan y una respuesta vieja no puede reemplazar a la más reciente. Ante un fallo se retiran las tarjetas anteriores, se informa que no hay actualización disponible y se ofrece reintento. No se conserva una disponibilidad confirmada sobre datos cuya renovación falló.

Esto es consulta y sondeo; no son eventos, webhooks, sincronización transaccional ni tiempo real garantizado.

## 9. Pruebas y revisión

- 22 pruebas PHP específicas de Catálogo: acceso sin sesión/base, fuente vacía, errores, stock desconocido, precios, filtros, búsqueda, inactivos, salida escapada, imágenes permitidas, paginación, detalle y ausencia de métodos de escritura.
- Regresión conjunta de G1: **127 pruebas PHP aprobadas, 897 aserciones** en el corte de esta implementación.
- Siete pruebas JavaScript específicas de Catálogo y las 17 existentes de navegación/presentación de rendiciones.
- Validación sintáctica de PHP y JavaScript.
- Renderizado de listado, detalle e indisponibilidad con HTTP simulado; revisión visual a 1440 y 390 píxeles. Sin desbordamiento horizontal en la captura móvil.

Los renderizados con productos se generaron fuera del proyecto con fixtures descartables. No son una carga de G3 ni un modo de demostración publicado. La base MySQL y el contrato real completo del proveedor no quedaron certificados: no se afirma una prueba de extremo a extremo con datos comerciales.

Para revisar la ejecución normal se dejó iniciado el servidor de interfaz en `127.0.0.1:8000`. La petición real a `/catalogo` renderizó la interfaz pública con HTTP 503, el mensaje de indisponibilidad esperado y `Cache-Control: no-store`, sin pantalla de excepción. Esto confirma el manejo del proveedor no configurado, no una conexión a productos reales. No se inició un servidor de G3.

## 10. Uso y conexión real

Con el servidor de G1 levantado, entrar en `http://127.0.0.1:8000/catalogo`. También hay accesos desde el login y el menú interno.

Si `CATALOG_SERVICE_URL` está vacío o el servicio falla, se muestra la interfaz pública con indisponibilidad y reintento, nunca una lista ficticia. Para ver datos reales, G3 debe disponer de una API operativa con esquema y datos preparados por su equipo; G1 sólo configura la URL y consume GET. No ejecutar una migración general como parte de este procedimiento.

El servidor integrado de PHP en Windows atiende una petición por proceso. No configurar la interfaz y su proveedor HTTP en el mismo proceso/puerto, porque una llamada a sí mismo puede producir timeouts. Usar Apache con concurrencia, un proveedor externo o dos procesos:

```powershell
# Terminal 1: interfaz de G1
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000

# Terminal 2: proveedor, sólo con su base ya preparada
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8001
```

Si ambos procesos ejecutan esta copia integrada, configurar `CATALOG_SERVICE_URL=http://127.0.0.1:8001`. Si existía configuración cacheada, limpiar únicamente esa caché con `php artisan config:clear`. Sólo se inició la interfaz en 8000 para esta revisión; el proveedor y su configuración quedan a cargo de la conexión real acordada con G3.

```powershell
C:\xampp\php\php.exe artisan test --filter=CatalogoPublicoTest
node --test tests/Frontend/catalogo.test.cjs
```

## 11. Pendientes

1. Conectar y comprobar la API real con MySQL accesible y datos de origen consistentes.
2. Acordar campo de fotografía, alojamiento y hosts con G3. No se incorporan imágenes ni filas a su base desde G1.
3. Acordar subcategorías si se requieren: el contrato actual sólo garantiza categoría simple.
4. Confirmar política de precio público, impuestos y condiciones comerciales. Esta versión presenta el precio base recibido.
5. Integrar pedidos cuando haya contratos de autenticación de clientes y pedidos autorizados. El login actual pertenece a usuarios internos.
6. Definir administración comercial de G1 —destacados, visibilidad, imágenes editoriales— antes de añadir persistencia y permisos.
7. Evaluar límites de tráfico, caché, índice o sincronización persistente de G1 según volumen y despliegue. No hay infraestructura disponible para prometer eventos instantáneos.
8. Ampliar recomendaciones con reglas acordadas. Las alternativas actuales sólo comparten categoría y disponibilidad.

No se modificó la sincronización de usuarios de G3 ni se reabrieron hallazgos de otros equipos. El informe integral anterior conserva su corte histórico; este documento registra la nueva intervención.

## 12. Reversión

No hay tablas, datos comerciales ni migraciones que deshacer.

Si los cambios siguen sin confirmar y estos archivos no recibieron trabajo posterior, restaurar **sólo** los archivos existentes modificados contra la base inicial:

```powershell
git restore --source=d01b5ca -- .env.example README.md routes/web.php resources/views/auth/login.blade.php resources/views/partials/sidebar-links.blade.php
```

Retirar luego los elementos nuevos de esta intervención, preservando cualquier trabajo posterior sobre ellos:

- `app/Modules/Catalogo/`
- `config/catalogo.php`
- `resources/views/catalogo/`
- `public/assets/css/catalogo.css`
- `public/assets/js/catalogo.js`
- `tests/Feature/CatalogoPublicoTest.php`
- `tests/Frontend/catalogo.test.cjs`
- `docs/informe-implementacion-catalogo-g1.md`.

Si existe un commit exclusivo del Catálogo, preferir `git revert <commit-del-catalogo>` para conservar historial. Si incluye otros trabajos, revertir selectivamente; no restaurar ni limpiar todo el repositorio.

El `.env` real no se modificó. Si después se configuraron URL/hosts exclusivamente para esta prueba, retirar sólo esos valores y limpiar la caché de configuración si corresponde. No ejecutar rollback de migraciones de G2, G3 o G4.
