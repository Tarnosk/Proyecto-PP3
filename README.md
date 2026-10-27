# ERP Juan23

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1)
![Status](https://img.shields.io/badge/Estado-Desarrollo%20activo-green)

Sistema web de gestión comercial en Laravel para una distribuidora o empresa con operaciones de venta, clientes, stock, compras, reportes, cobros y seguridad por roles.

## Descripción general

ERP Juan23 es una aplicación web orientada a la administración interna de una empresa comercial. El sistema integra la gestión de clientes, ventas, inventario, compras, proveedores, reportes y seguridad con una arquitectura modular pensada para mantener cada dominio funcional separado.

El repositorio está estructurado para facilitar la evolución del sistema, la documentación por módulo y la incorporación de nuevos procesos sin depender de una única capa monolítica.

## Objetivo del sistema

Permitir gestionar de manera centralizada los procesos clave del negocio, entre ellos:

- administración de clientes y zonas
- gestión de ventas y estados comerciales
- control de stock, ubicaciones y movimientos
- compras, proveedores y pagos
- reportes y análisis operativos
- reclamos, devoluciones y seguimiento de incidencias
- seguridad, permisos y sesiones por rol
- integración de módulos funcionales bajo una misma plataforma

## Stack tecnológico

- PHP 8.2+
- Laravel 12.x
- MySQL / MariaDB
- Blade + Bootstrap
- JavaScript
- PHPUnit

## Arquitectura

El proyecto sigue una estructura modular basada en Laravel, donde cada dominio funcional se encapsula en su propio módulo bajo `app/Modules`.

Esta organización permite separar claramente responsabilidades como:

- autenticación y seguridad
- clientes
- ventas
- stock
- compras
- proveedores
- reportes
- reclamos
- entregas y rendiciones
- cuenta corriente

## Estructura principal

```text
app/
  Http/
    Controllers/
  Modules/
    Auth/
    Clientes/
    Reclamos/
    Reportes/
    Productos/
    Ventas/
    Compras/
    Stock/
    Proveedores/
    Login/
  Contracts/
  Providers/
  Services/
    Gateways/
bootstrap/
config/
public/
resources/
  views/
routes/
database/
  migrations/
  seeders/
tests/
```

## Requisitos previos

- PHP 8.2 o superior
- Composer 2.x
- MySQL 8 o MariaDB 10.4 o superior
- Extensiones PHP: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer`, `xml` y `zip`

> En XAMPP o entornos locales, habilitar `extension=gd` y `extension=zip` en `php.ini` si corresponde.

## Instalación

1. Clonar el repositorio:

```bash
git clone https://github.com/rapiriz/ERP-Juan23.git
cd ERP-Juan23
```

2. Instalar dependencias:

```bash
composer install
```

3. Crear el archivo de entorno:

```bash
cp .env.example .env
```

4. Generar la clave de la aplicación:

```bash
php artisan key:generate
```

5. Configurar la base de datos en `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_gestion
DB_USERNAME=root
DB_PASSWORD=
```

6. Crear la base de datos y ejecutar migraciones + seeders:

```bash
php artisan migrate --seed
```

7. Ejecutar la aplicación:

```bash
php artisan serve
```

Abrir en el navegador:

```text
http://localhost:8000
```

## Base de datos

### Instalación nueva

Crear primero una base vacío llamada `sistema_gestion` con juego de caracteres `utf8mb4_unicode_ci`. Luego ejecutar:

```bash
php artisan migrate --seed
```

### Base existente

Hacer una copia de seguridad y apuntar `.env` a la base actual. Después correr:

```bash
php artisan migrate
```

La primera migration detecta las tablas existentes y las registra como línea base sin eliminar datos. Las migraciones históricas y los SQL previos se conservan como documentación del esquema.

## Módulos principales

La aplicación está organizada por dominios funcionales. Algunos de los módulos principales son:

- Auth / Login
- Clientes
- Ventas
- Stock
- Compras
- Proveedores
- Reportes
- Reclamos
- Entregas
- Rendiciones
- Cuenta corriente

Cada módulo mantiene una estructura consistente de controladores, modelos, servicios, requests, migraciones y rutas.

## Flujo principal del sistema

1. El usuario inicia sesión con su rol.
2. El sistema valida permisos y acceso.
3. Se gestionan operaciones de negocio según el módulo.
4. La lógica de negocio procesa los datos y persiste en la base.
5. Se genera una respuesta visible en la interfaz o en reportes.
6. Los distintos módulos interactúan entre sí para mantener la coherencia del ERP.

## Usuarios de prueba

Estas credenciales son solo para desarrollo local y se crean al ejecutar `php artisan db:seed`.

| Usuario | Contraseña | Rol |
| --- | --- | --- |
| admin | Admin1234 | administrativo |
| repartidor | Repartidor1234 | repartidor |
| contador | Contador1234 | contador |
| inactivo | Inactivo1234 | repartidor inactivo |

## Permisos

| Funcionalidad | Administrativo | Repartidor | Contador |
| --- | --- | --- | --- |
| Panel propio | Sí | Sí | Sí |
| Listar y buscar clientes | Sí | Sí | No |
| Crear y editar clientes | Sí | No | No |
| Crear y listar reclamos | Sí | Sí | No |
| Ver historial de clientes | Sí | Sí | No |
| Consultar cobros y saldos de otros módulos | Sí | Sí | No |
| Reportes de clientes y ranking comercial | Sí | No | Sí |
| Exportar historial a PDF y Excel | Sí | No | Sí |
| Secciones de ventas y stock | Sí | Sí | No |
| Configurar emails de usuarios | Sí | No | No |

## Seguridad de acceso

- Tres contraseñas incorrectas bloquean la cuenta durante 15 minutos.
- Cada cuenta admite una sola sesión activa.
- La sesión expira después de 120 minutos sin actividad.
- El logout utiliza POST con protección CSRF.
- Las contraseñas usan hashing seguro de Laravel.
- Los identificadores de sesión guardados en la base están hasheados.
- Los permisos se verifican en middleware, policies y form requests.
- La recuperación usa códigos numéricos hasheados que vencen en 15 minutos.

## Recuperación de contraseña por email

En desarrollo, `MAIL_MAILER=log` guarda el mensaje y el código en `storage/logs/laravel.log`, pero no envía correo real. Para usar SMTP, configurar en `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_SCHEME=null
MAIL_USERNAME=usuario_smtp
MAIL_PASSWORD=contrasena_smtp
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="ERP Juan23"
```

Luego limpiar la configuración:

```bash
php artisan config:clear
```

## Pruebas y comprobaciones

Ejecutar la suite automatizada:

```bash
php artisan test
```

También es útil verificar rutas y formato:

```bash
php artisan route:list --except-vendor
php artisan config:clear
php artisan cache:clear
```

## Documentación

La documentación técnica y operativa del proyecto está organizada en `docs/` y `Documentacion/`:

### Guías del Sistema (`docs/`)
- [Índice de documentación](docs/README.md)
- [Guía de instalación](docs/guia-instalacion.md)
- [Arquitectura del sistema](docs/arquitectura.md)
- [Estructura del proyecto](docs/estructura-proyecto.md)
- [Módulos funcionales](docs/modulos.md)
- [Flujo de negocio](docs/flujo-negocio.md)
- [Seguridad y accesos](docs/seguridad.md)
- [Despliegue](docs/despliegue.md)
- [Roadmap del proyecto](docs/roadmap.md)
- [Guía de colaboradores](docs/guia-colaboradores.md)
- [Guía de migración](MIGRATION.md)

### Especificaciones de API y Contratos
- [Especificación OpenAPI 3.0 (YAML)](docs/openapi.yaml)
- [Colección Postman REST API (JSON)](docs/contracts/ERP_Juan23_API_Collection.postman_collection.json)
- [Contrato API G1](docs/contrato-api-g1.md)
- [Contratos Sprint 4 G1](docs/contratos-sprint4-g1.md)
- [Guía de Integración Modular](docs/GUIA_INTEGRACION_MODULAR.md)
- [Integración de Microservicios](docs/integracion-microservicios.md)
- [Mapa de APIs Sprints 3 y 4](docs/mapa-apis-sprints-3-y-4.md)
- [Informe Técnico Integral](docs/informe-tecnico-integral-erp-juan23.md)

### Requisitos y Base de Datos
- [Matriz de Trazabilidad de Requisitos (RTM)](docs/matriz-trazabilidad-requisitos.md)
- [Diccionario de Datos de la Base de Datos](docs/diccionario-datos.md)

### Entregas Académicas y Sprints (`Documentacion/`)
- [Historias de Usuario (PDF)](Documentacion/Historias%20de%20Usuario.pdf)
- [Control de Versiones y Evolución de Sprints](Documentacion/CONTROL_DE_VERSIONES_EVOLUCION_SPRINTS.md)
- [Presentación Sprints 2 y 3](Documentacion/PRESENTACION_SPRINT_2_Y_3.md)
- [Plan de Pruebas de Validación y Verificación](Documentacion/plan%20de%20prueba%20de%20validacion%20y%20verificacion.md)
- [Criterios de Accesibilidad y Usabilidad](Documentacion/criterios%20de%20accesibilidad%20y%20usabilidad.md)
- [Contrato G1 Auditoría](Documentacion/CONTRATO_G1_AUDITORIA.md)
- [Pendientes del Proyecto](Documentacion/PENDIENTES_PROYECTO.md)

## Estado del proyecto

El proyecto se encuentra en desarrollo activo con una estructura modular consolidada. La aplicación ya cuenta con la base conceptual y técnica para una solución ERP de gestión comercial y la documentación acompaña el crecimiento del sistema por módulos.

## Contribución

1. Crear una rama nueva.
2. Trabajar sobre el módulo o corrección correspondiente.
3. Ejecutar pruebas relevantes.
4. Actualizar documentación si cambia el comportamiento o la arquitectura.
5. Abrir un pull request con una descripción clara.

## Recomendaciones de mantenimiento

- no versionar archivos `.env` con información real
- mantener actualizado el estado de cada módulo
- revisar permisos y reglas de negocio antes de mergear
- conservar la documentación sincronizada con los cambios de código

## Licencia

Si el proyecto no declara una licencia formal en el repositorio, se recomienda definirla antes de una publicación externa o uso institucional en producción.

## Contacto

Este repositorio forma parte del desarrollo del ERP Juan23 y su documentación puede ampliarse según lo requieran el equipo, la entrega académica o el despliegue final del sistema.
