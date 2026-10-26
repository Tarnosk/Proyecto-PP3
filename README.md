# Proyecto PP3

Sistema ERP modular desarrollado con Laravel para la gestión comercial de una distribuidora.

## Descripción general

Proyecto PP3 es una aplicación web orientada a la administración integral de una distribuidora, con foco en clientes, ventas, productos, stock, compras, reportes y procesos de negocio internos. La solución está diseñada como una aplicación modular en Laravel, con separaciones por dominio y una base de datos central para los procesos operativos.

## Objetivo del sistema

Facilitar la gestión diaria de una organización comercial con capacidades para:

- gestionar clientes, zonas y contactos
- controlar ventas y estados comerciales
- administrar stock, ubicaciones y movimientos
- gestionar compras, proveedores y pagos
- planificar reposición y sugerencias de compra
- registrar reclamos y devoluciones
- consultar reportes, saldos y analítica
- controlar seguridad, roles y sesiones
- integrar módulos interdependientes bajo una misma plataforma

## Stack tecnológico

- PHP 8.2+
- Laravel 12.x
- Composer 2.x
- MySQL / MariaDB
- Blade
- CSS
- JavaScript
- PHPUnit

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
└── .env.example
```

## Requisitos

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

2. Ingresar al backend

```bash
cd backend
```

3. Instalar dependencias

```bash
composer install
```

4. Crear archivo de entorno

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

Abrir en el navegador:

```text
http://localhost:8000
```

## Documentación central

La documentación completa está organizada en `docs/` y cubre instalación, flujo, módulos, arquitectura, seguridad y despliegue:

- `docs/README.md`
- `docs/guia-instalacion.md`
- `docs/arquitectura.md`
- `docs/estructura-proyecto.md`
- `docs/modulos.md`
- `docs/flujo-negocio.md`
- `docs/seguridad.md`
- `docs/despliegue.md`
- `docs/roadmap.md`
- `docs/guia-colaboradores.md`

## Módulos principales

El proyecto está organizado por dominios funcionales, por ejemplo:

- Autenticación y usuarios
- Clientes y zonas
- Ventas
- Compras y proveedores
- Stock e inventario
- Reparto y entregas
- Reclamos
- Reportes y analítica
- Cuenta corriente
- Rendiciones

## Reglas de trabajo

- cada cambio funcional debe documentarse
- cada módulo debe mantener estructura clara y consistente
- no se debe subir `.env` ni credenciales reales
- las pruebas deben ejecutarse antes de mergear cambios

## Contribución

1. Crear una rama nueva.
2. Trabajar sobre el área correspondiente.
3. Ejecutar pruebas relevantes.
4. Actualizar documentación si cambió el comportamiento.
5. Abrir un pull request con descripción clara.

## Licencia

Si el proyecto no declara licencia explícita, se recomienda definir una antes de uso en producción o publicación institucional.
