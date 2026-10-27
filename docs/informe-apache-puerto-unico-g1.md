# ERP y APIs locales en un único puerto con Apache

> **Actualización del 27/10/2026:** Apache en 8000 se conserva, pero G1 volvió a `sistema_gestion` y el proveedor de Catálogo quedó vacío tras retirar las tablas G3 de la copia local. La conexión a Productos descrita en las pruebas del 26/10 es histórica y ya no está activa. No se altera el arranque de Apache ni el código de los otros grupos. Ver [informe de retiro](informe-retiro-integracion-local-g3.md).

**Fecha:** 26/10/2026. **Alcance:** infraestructura local de desarrollo y configuración de consumidores de G1. Se conserva la base `sistema_gestion_g1_g3_20261026` y el código de los demás grupos.

## Resultado y arranque

El ERP, el Catálogo público y las APIs locales quedaron disponibles bajo un único origen:

```text
http://127.0.0.1:8000
```

Para usarlos, abrir el panel de XAMPP e iniciar **MySQL** y **Apache**. No ejecutar `artisan serve` ni abrir un segundo servidor en 8001. Para detener la web, pulsar **Stop** en Apache.

Se inició Apache exclusivamente para las verificaciones y se detuvo al terminarlas. No se instaló un servicio ni se configuró el inicio automático. MySQL, que ya estaba funcionando antes de este cambio, se conservó en su estado anterior.

## Por qué se cambió

El Catálogo de G1 consulta la API de Productos mediante HTTP desde el servidor. Un único proceso del servidor integrado de PHP en Windows atiende una petición por vez: si la página espera una consulta HTTP dirigida al mismo proceso, esa consulta no puede atenderse hasta que termine la página que la está esperando. El resultado puede ser un timeout.

La solución anterior separaba interfaz y proveedor en 8000 y 8001. Apache utiliza múltiples hilos para atender solicitudes, por lo que puede responder a ambas en el mismo puerto. En este equipo ya estaban habilitados PHP, `mod_rewrite` y el módulo de Windows con `ThreadsPerChild=150`; no fue necesario cambiar sus módulos o límites.

Referencias: [servidor integrado de PHP](https://www.php.net/manual/en/features.commandline.webserver.php) y [Apache MPM para Windows](https://httpd.apache.org/docs/2.4/mod/mpm_winnt.html).

## Cambios concretos

| Archivo | Cambio |
| --- | --- |
| `C:\xampp\apache\conf\extra\httpd-erp-g1.conf` | Archivo nuevo: escucha local en `127.0.0.1:8000` y VirtualHost del ERP. |
| `C:\xampp\apache\conf\extra\httpd-vhosts.conf` | Se agregó una inclusión de ese archivo. Se conservaron sus entradas anteriores. |
| `.env` local del proyecto | `APP_URL`, `CATALOG_SERVICE_URL` y `SALES_SERVICE_URL` ahora usan `http://127.0.0.1:8000`. |
| `README.md` | Se reemplazó el arranque de dos terminales por Apache y MySQL desde XAMPP. |
| Informes de implementación del Catálogo y preparación de la base | Se agregaron notas que remiten al procedimiento vigente. |
| Este informe | Explica instalación, pruebas, límites y reversión. |

Valores anteriores de las claves modificadas:

```dotenv
APP_URL=http://localhost:8000
CATALOG_SERVICE_URL=http://127.0.0.1:8001
SALES_SERVICE_URL=http://127.0.0.1:8010
```

Se ajustó `SALES_SERVICE_URL` porque es un consumidor de G1 y su API ya existe en esta misma aplicación, en `/api/ventas`. La consulta respondió correctamente desde 8000. No se cambiaron rutas, permisos, mocks ni configuración propia de integración de G4. Los proveedores que estaban sin configurar permanecen sin configurar; este cambio no completa servicios pendientes de otros grupos.

La configuración de Apache apunta **exclusivamente** a:

```text
C:/Users/juanf/OneDrive/Escritorio/Repositorio/ERP-Juan23/public
```

Mantiene las reglas existentes de `public/.htaccess`, permite resolver rutas Laravel y servir sus assets. Se limita a acceso local y rechaza archivos cuyo nombre comienza con punto. No se sirve la raíz del repositorio como directorio público.

Los accesos predeterminados de XAMPP en 80/443 se conservaron; el ERP usa 8000. Apache abre sus puertos habituales cuando se inicia, pero para este proyecto no se necesitan otros servidores ni puertos de API.

## Qué se comprobó

Apache validó la configuración con `httpd.exe -t` y el mapa de VirtualHosts con `httpd.exe -S`. Las pruebas HTTP se realizaron sobre Apache real, con la configuración definitiva del proyecto:

| Prueba | Resultado |
| --- | --- |
| `GET /login` | 200. |
| `GET /catalogo` | 200. |
| `GET /api/v1/productos` | 200, lista vacía al momento de comprobar. |
| `GET /api/v1/categorias` | 200, tres categorías. |
| `GET /api/v1/marcas` | 200, seis marcas. |
| `GET /api/ventas` | 200, lista vacía al momento de comprobar. |
| `GET /catalogo/datos` | 200 y contenido actualizado: G1 consultó la API en el mismo puerto. |
| Seis consultas simultáneas a `/catalogo/datos` | Todas respondieron 200, sin timeout; la más lenta tardó menos de un segundo en esta prueba. |
| `GET /dashboard` sin sesión | 302 hacia login; se conserva la protección del panel. |
| Asset CSS del Catálogo | 200. |
| `GET /.env` | 403. |
| `GET /composer.json`, `/artisan`, `/vendor/autoload.php` | 404. |
| Finalización | Apache detenido; puertos 80, 443, 8000 y 8001 libres. |

Las pruebas usaron consultas GET y no incluyeron altas de productos, ventas, cambios de usuarios o movimientos. Se comparó el estado de la base antes y después para confirmar que se mantuvieran sus tablas y datos.

El código de aplicación de G1, G2, G3 y G4 no necesitó cambios para esta configuración. No se instaló ninguna migración o semilla adicional.

## Respaldos y evidencia

Se conservaron fuera del repositorio Git, en:

```text
C:\Users\juanf\OneDrive\Escritorio\Repositorio\Entregables\Apache_G1_2026-10-26\
```

Allí están `httpd.conf.antes`, `httpd-vhosts.conf.antes`, `.env.antes-apache`, la configuración aplicada, los resultados HTTP y las verificaciones de base y código. El respaldo `.env` contiene secretos; no publicarlo.

Los logs específicos del VirtualHost están en `C:\xampp\apache\logs\erp-g1-error.log` y `erp-g1-access.log`.

La configuración de XAMPP es específica de este equipo y queda fuera de Git. En otra computadora habrá que crear su VirtualHost con la ruta local correspondiente. Si se mueve el repositorio, se debe actualizar `DocumentRoot` y su bloque `Directory` antes de reiniciar Apache.

## Reversión de este cambio

1. Detener Apache desde XAMPP.
2. Retirar de `httpd-vhosts.conf` la línea `Include conf/extra/httpd-erp-g1.conf` y su comentario. Si no hubo cambios posteriores, también puede restaurarse `httpd-vhosts.conf.antes` desde la carpeta de respaldo.
3. Retirar el archivo nuevo `C:\xampp\apache\conf\extra\httpd-erp-g1.conf` si ya no se utilizará.
4. Recuperar únicamente los tres valores anteriores de `.env` indicados arriba. Si no hubo cambios posteriores de entorno, se conserva el respaldo completo `.env.antes-apache`.
5. Si posteriormente se creó caché de configuración, limpiarla con `C:\xampp\php\php.exe artisan config:clear`.
6. Verificar Apache con `C:\xampp\apache\bin\httpd.exe -t`.

Esto revierte exclusivamente la configuración de puerto único. La base preparada G1 + G3 sigue disponible y no se revierte ni elimina.

Para volver al esquema anterior de desarrollo, con Apache detenido, iniciar `artisan serve --host=127.0.0.1 --port=8000` para la interfaz y otro proceso en 8001 para Productos. La URL de Ventas volvería a requerir su proveedor previo en 8010 o una configuración de G1 revisada para el entorno elegido.

No hace falta ejecutar rollback de migraciones ni restaurar bases para revertir Apache. Los demás trabajos pendientes del Catálogo se conservan.
