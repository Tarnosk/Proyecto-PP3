# Backend del Proyecto PP3

Este directorio contiene la aplicación principal del sistema ERP. La lógica del negocio, rutas, migraciones, modelos, servicios, validaciones y vistas se encuentran aquí.

## Objetivo

La aplicación backend está construida sobre Laravel y está orientada a la gestión de una distribuidora. Se organiza por dominios funcionales con rutas, modelos y servicios claros para cada área del negocio.

## Estructura principal

```text
backend/
├── app/
│   ├── Http/
│   ├── Modules/
│   ├── Providers/
│   ├── Services/
│   ├── Support/
│   └── Contracts/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── tests/
├── artisan
├── composer.json
├── composer.lock
├── phpunit.xml
├── .env.example
├── .env.testing
└── README.md
```

## Tecnologías

- PHP 8.2+
- Laravel 12.x
- Composer 2.x
- MySQL / MariaDB
- Blade
- CSS / JavaScript
- PHPUnit

## Módulos funcionales

La lógica del sistema se agrupa en módulos bajo `app/Modules/`.

Algunos de los principales son:

- `Auth`
- `Login`
- `Clientes`
- `Ventas`
- `Stock`
- `Compras`
- `Proveedores`
- `Reportes`
- `Reclamos`
- `Rendiciones`
- `Entregas`
- `CuentaCorriente`

Cada módulo suele contener:

- `Controllers`
- `Models`
- `Services`
- `Requests`
- `Policies`
- `database/migrations`
- `routes/`
- `README.md` cuando aplica

## Requisitos

- PHP 8.2 o superior
- Composer 2.x
- MySQL 8 o MariaDB 10.4+
- extensiones recomendadas:
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

## Instalación local

1. Ingresar al directorio del backend

```bash
cd backend
```

2. Instalar dependencias

```bash
composer install
```

3. Crear el archivo `.env`

```bash
cp .env.example .env
```

4. Configurar la base de datos

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proyecto_pp3
DB_USERNAME=root
DB_PASSWORD=
```

5. Generar la clave de aplicación

```bash
php artisan key:generate
```

6. Ejecutar migraciones y seeders

```bash
php artisan migrate --seed
```

7. Levantar la aplicación

```bash
php artisan serve
```

Abrir:

```text
http://localhost:8000
```

## Validaciones útiles

```bash
php artisan test
php artisan route:list --except-vendor
php artisan config:clear
php artisan cache:clear
```

## Buenas prácticas

- no subir credenciales ni `.env` reales
- mantener descripciones claras por módulo
- documentar cambios funcionales junto al código
- usar validaciones y permisos por rol
- mantener migraciones y seeders controlados

## Documentación asociada

- `../docs/README.md`
- `../docs/guia-instalacion.md`
- `../docs/arquitectura.md`
- `../docs/estructura-proyecto.md`
- `../docs/modulos.md`
- `../docs/flujo-negocio.md`
- `../docs/seguridad.md`
- `../docs/despliegue.md`

## Recomendación final

El backend debe mantenerse alineado con la documentación de negocio y técnica. Cuando se agregue un módulo, cambie una regla de negocio o altere una integración, debe actualizarse la documentación correspondiente dentro del mismo PR.
