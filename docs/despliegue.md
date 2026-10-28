# Despliegue

Este documento resume la estrategia recomendada para desplegar la aplicación en un entorno real.

## Requisitos de producción

- PHP 8.2+
- Composer 2.x
- MySQL 8.0 o MariaDB 10.4+
- Nginx o Apache
- certificado SSL
- acceso controlado a base de datos

## Variables de entorno

Usar un archivo `.env` de producción sin versionar al repositorio.

Ejemplo:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_KEY=...
DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

## Recomendaciones de producción

- activar HTTPS
- deshabilitar depuración de errores
- usar caché configurada
- limitar acceso a la base de datos por IP o red interna
- mantener copias de seguridad automáticas
- revisar logs regularmente

## Comandos recomendados

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan migrate
php artisan optimize
```

## Backups

Se recomienda automatizar backups de la base de datos con una frecuencia acorde al volumen del negocio.

## Monitoreo

- revisar logs de Laravel
- monitorear errores 500
- validar sesiones y accesos
- revisar consumo de base de datos y memoria

## Checklist final

1. Validar que la aplicación responde.
2. Confirmar conexión a la base de datos.
3. Ejecutar migraciones.
4. Verificar permisos por rol.
5. Confirmar HTTPS.
6. Revisar logs y errores.
7. Ejecutar pruebas finales.
