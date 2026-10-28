# Guía de instalación

Esta guía describe cómo preparar el entorno local para levantar el proyecto y dejarlo operativo en desarrollo.

## Requisitos previos

- PHP 8.2+
- Composer 2.x
- MySQL 8.0 o MariaDB 10.4+
- Git
- Editor de texto o IDE

## Instalación paso a paso

### 1. Clonar el repositorio

```bash
git clone https://github.com/rapiriz/ERP-Juan23.git
cd ERP-Juan23
```

### 2. Instalar dependencias

```bash
composer install
```

### 4. Crear archivo `.env`

```bash
cp .env.example .env
```

### 5. Configurar acceso a base de datos

Editar `.env` con valores locales:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proyecto_pp3
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Generar la clave de la aplicación

```bash
php artisan key:generate
```

### 7. Correr migraciones

```bash
php artisan migrate
```

Si el proyecto usa seeders para datos iniciales o usuarios de prueba:

```bash
php artisan migrate --seed
```

### 8. Levantar la aplicación

```bash
php artisan serve
```

Abrir en el navegador:

```text
http://localhost:8000
```

## Problemas comunes

### Error de conexión a MySQL

Verificar:

- que MySQL o MariaDB esté corriendo
- que la base de datos exista
- que los datos del `.env` sean correctos
- que el puerto esté habilitado

### Error de extensiones PHP

Asegurarse de tener habilitadas extensiones como:

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

### Error al ejecutar Artisan

Probar limpiar la caché:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## Recomendaciones

- no versionar archivos `.env` con credenciales reales
- usar una base local separada para cada rama o entorno
- documentar cambios de configuración dentro del PR
- mantener los seeders útiles para pruebas y validación
