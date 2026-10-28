# Guía de Solución de Problemas y Preguntas Frecuentes (Troubleshooting & FAQ)

Esta guía detalla las soluciones a los incidentes y errores más habituales al clonar, configurar o ejecutar el proyecto en entornos locales de desarrollo (XAMPP, Laragon o PHP CLI).

---

## 1. Problemas de Base de Datos y Conexión

### Error: `SQLSTATE[HY000] [2002] No se puede establecer una conexión ya que el equipo de destino denegó expresamente dicha conexión`
* **Causa:** El motor de base de datos MySQL / MariaDB no se encuentra en ejecución o está escuchando en un puerto distinto al configurado en `.env`.
* **Solución:**
  1. Abra el panel de control de **XAMPP** (o Laragon).
  2. Verifique que el servicio **MySQL** tenga el botón en verde y muestre el puerto `3306`.
  3. Si el puerto 3306 está ocupado por otro servicio, verifique en su archivo `.env` que `DB_PORT` coincida con el puerto real configurado.
  4. Pruebe la conectividad abriendo `http://localhost/phpmyadmin`.

### Error: `Base de datos 'distribuidorapyb' no existe` o Tablas Vacías
* **Causa:** La base de datos no fue creada o no se cargaron los scripts iniciales del esquema y la semilla.
* **Solución:**
  1. Ingrese a phpMyAdmin o ejecute en consola MySQL:
     ```sql
     CREATE DATABASE IF NOT EXISTS distribuidorapyb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     ```
  2. Importe los archivos SQL en este orden estricto:
     - 1°: [`database/esquema_base_DistribuidoraPyB.sql`](../database/esquema_base_DistribuidoraPyB.sql) (crea las 48 tablas y relaciones).
     - 2°: [`database/semilla_demo_actualizada.sql`](../database/semilla_demo_actualizada.sql) (carga usuarios, categorías, marcas, productos y proveedores).
  3. Ejecute las migraciones de Laravel para alinear la versión:
     ```powershell
     php artisan migrate
     ```

---

## 2. Configuración de PHP y Extensiones Faltantes

### Error: `Class 'PDO' not found` o `could not find driver`
* **Causa:** Las extensiones para MySQL de PHP no están habilitadas en el archivo `php.ini`.
* **Solución:**
  1. Localice su archivo `php.ini` activo (puede verlo ejecutando `php --ini`).
  2. Abra `php.ini` en un editor de texto y descomente (quite el punto y coma `;` inicial) de las siguientes líneas:
     ```ini
     extension=pdo_mysql
     extension=mysqli
     extension=bcmath
     extension=gd
     extension=zip
     extension=curl
     extension=mbstring
     ```
  3. Guarde el archivo y reinicie el servidor Apache / PHP.

### Error: `Call to undefined function bcmul()` o `bcadd()`
* **Causa:** Falta la extensión matemática de alta precisión `bcmath` requerida por Laravel y el motor de redondeos de precios.
* **Solución:** Habilite `extension=bcmath` en `php.ini` como se indicó arriba.

---

## 3. Caché, Vistas y Configuración Desactualizada

### Síntoma: Los cambios en `.env` no surten efecto o pantalla en blanco
* **Causa:** Laravel mantiene una caché compilada de la configuración anterior en `bootstrap/cache/config.php`.
* **Solución:** Limpie todos los archivos temporales ejecutando:
  ```powershell
  php artisan optimize:clear
  php artisan config:clear
  php artisan view:clear
  php artisan route:clear
  ```

### Error: `The stream or file ".../storage/logs/laravel.log" could not be opened in append mode: failed to open stream: Permission denied`
* **Causa:** Permisos insuficientes en las carpetas de almacenamiento y caché de Laravel.
* **Solución:**
  - En Windows: Asegúrese de que su usuario tenga permisos de escritura sobre la carpeta `storage` y `bootstrap/cache`.
  - En Linux / macOS:
    ```bash
    chmod -R 775 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
    ```

---

## 4. Problemas de Seguridad y Vistas Web

### Error: `419 Page Expired` al enviar formularios
* **Causa:** Expiró la sesión o falta el token de protección CSRF en la solicitud.
* **Solución:**
  1. Si es un formulario HTML Blade, verifique que incluya la directiva `@csrf` dentro de la etiqueta `<form>`.
  2. Si es una petición AJAX/Fetch en JavaScript, asegúrese de incluir la cabecera:
     ```javascript
     headers: {
         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
         'Content-Type': 'application/json'
     }
     ```

### Error: `401 Unauthorized` o `403 Forbidden` en llamadas API REST
* **Causa:** Las rutas bajo `/api/v1/` requieren identidad de usuario para registrar la auditoría.
* **Solución:** Envíe la cabecera de identidad en su cliente HTTP o Postman:
  ```http
  X-User-Id: 1
  ```
  O use las credenciales de prueba provistas en la semilla demo (`admin` / `Admin1234`).

---

## 5. Pruebas Automatizadas (PHPUnit)

### Síntoma: `php artisan test` reporta errores de base de datos
* **Causa:** Las pruebas intentan escribir en una base de datos de test que no existe o que está desconectada.
* **Solución:**
  1. Verifique que MySQL esté iniciado.
  2. El archivo `phpunit.xml` está configurado para utilizar una base aislada o la conexión default.
  3. Puede ejecutar una prueba específica sin correr toda la suite:
     ```powershell
     php artisan test --filter=UserStoriesFrontendTest
     php artisan test --filter=ConteoFisicoTest
     ```
