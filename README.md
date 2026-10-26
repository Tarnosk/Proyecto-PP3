# Proyecto PP3

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1)
![Status](https://img.shields.io/badge/Estado-Desarrollo%20activo-green)

Sistema ERP modular desarrollado en Laravel para la gestión comercial y operativa de una distribuidora.

## Descripción general

Proyecto PP3 es una aplicación web orientada a la administración interna de una empresa distribuidora. El sistema integra funciones de clientes, ventas, stock, compras, proveedores, reportes, reclamos, seguridad y control financiero bajo una arquitectura modular.

El repositorio está organizado para facilitar el mantenimiento, la escalabilidad y la colaboración entre módulos funcionales, manteniendo una separación clara por dominio.

## Objetivo del sistema

Permitir gestionar de manera centralizada los procesos clave del negocio, incluyendo:

- administración de clientes y zonas
- gestión de ventas y estados comerciales
- control de inventario y movimientos de stock
- compras, proveedores y pagos
- reportes y analítica interna
- reclamos, devoluciones y seguimiento operativos
- seguridad, permisos y sesiones por rol
- integración de módulos funcionales bajo una misma plataforma

## Stack tecnológico

- PHP 8.2+
- Laravel 12.x
- Composer 2.x
- MySQL / MariaDB
- Blade
- CSS
- JavaScript
- PHPUnit

## Arquitectura

El proyecto sigue una estructura modular basada en Laravel, donde cada dominio funcional se encapsula en su propio módulo bajo `backend/app/Modules`.

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

## Estructura del repositorio

```text
.
├── README.md
├── backend/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── resources/
│   ├── routes/
│   ├── tests/
│   ├── artisan
│   ├── composer.json
│   ├── composer.lock
│   ├── phpunit.xml
│   ├── .env.example
│   ├── .env.testing
│   └── README.md
├── docs/
│   ├── README.md
│   ├── guia-instalacion.md
│   ├── arquitectura.md
│   ├── estructura-proyecto.md
│   ├── modulos.md
│   ├── flujo-negocio.md
│   ├── seguridad.md
│   ├── despliegue.md
│   ├── roadmap.md
│   └── guia-colaboradores.md
├── Documentacion/
├── .gitignore
├── .env.example
└── .gitignore
```

## Requisitos previos

- PHP 8.2 o superior
- Composer 2.x
- MySQL 8.0 o MariaDB 10.4+
- Git
- Extensiones recomendadas:
  - bcmath
  - ctype
  - curl
  - dom
  - fileinfo
  - gd
  - mbstring
  - openssl
  - pdo
  - pdo_mysql
  - tokenizer
  - xml
  - zip

## Instalación rápida

1. Clonar el repositorio

```bash
git clone https://github.com/Tarnosk/Proyecto-PP3.git
cd Proyecto-PP3
```

2. Entrar al backend

```bash
cd backend
```

3. Instalar dependencias

```bash
composer install
```

4. Crear el archivo de entorno

```bash
cp .env.example .env
```

5. Configurar la base de datos

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proyecto_pp3
DB_USERNAME=root
DB_PASSWORD=
```

6. Generar la clave de Laravel

```bash
php artisan key:generate
```

7. Ejecutar migraciones y seeders

```bash
php artisan migrate --seed
```

8. Levantar la aplicación

```bash
php artisan serve
```

Abrir la app en el navegador:

```text
http://localhost:8000
```

## Variables de entorno recomendadas

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proyecto_pp3
DB_USERNAME=root
DB_PASSWORD=
```

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

Cada módulo mantiene una estructura consistente de controladores, modelos, servicios, requests, validaciones y rutas.

## Flujo principal del sistema

1. El usuario inicia sesión con su rol.
2. El sistema valida permisos y acceso.
3. Se gestionan operaciones de negocio según el módulo.
4. La lógica de negocio procesa los datos y persiste en la base de datos.
5. Se genera una respuesta visible en la interfaz o en reportes.
6. Los distintos módulos interactúan entre sí para mantener la coherencia del ERP.

## Pruebas

Ejecutar la suite de pruebas del backend:

```bash
php artisan test
```

Validaciones rápidas recomendadas:

```bash
php artisan route:list --except-vendor
php artisan config:clear
php artisan cache:clear
```

## Documentación

La documentación técnica y operativa está organizada en `docs/`:

- [docs/README.md](docs/README.md)
- [docs/guia-instalacion.md](docs/guia-instalacion.md)
- [docs/arquitectura.md](docs/arquitectura.md)
- [docs/estructura-proyecto.md](docs/estructura-proyecto.md)
- [docs/modulos.md](docs/modulos.md)
- [docs/flujo-negocio.md](docs/flujo-negocio.md)
- [docs/seguridad.md](docs/seguridad.md)
- [docs/despliegue.md](docs/despliegue.md)
- [docs/roadmap.md](docs/roadmap.md)
- [docs/guia-colaboradores.md](docs/guia-colaboradores.md)

También existe una guía específica del backend en:

- [backend/README.md](backend/README.md)

## Estado del proyecto

El proyecto se encuentra en desarrollo activo con una estructura modular consolidada. La aplicación ya cuenta con la base conceptual y técnica para una solución ERP de gestión comercial, y la documentación acompañada del código facilita la evolución del sistema por módulos.

## Contribución

1. Crear una rama nueva.
2. Trabajar sobre el módulo o corrección correspondiente.
3. Ejecutar pruebas relevantes.
4. Actualizar documentación si cambia el comportamiento o la arquitectura.
5. Abrir un pull request con una descripción clara.

## Recomendaciones de mantenimiento

- no versionar archivos `.env` con datos reales
- mantener actualizado el estado de cada módulo
- revisar permisos y reglas de negocio antes de mergear
- conservar la documentación sincronizada con los cambios de código

## Licencia

Si el proyecto no declara una licencia formal en el repositorio, se recomienda definirla antes de una publicación externa o uso institucional en producción.

## Contacto

Este repositorio forma parte del desarrollo del proyecto PP3 y su documentación puede ampliarse según lo requieran el equipo, la entrega académica o el despliegue final del sistema.
