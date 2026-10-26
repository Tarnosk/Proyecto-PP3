# Seguridad

La seguridad es un aspecto crítico del sistema ERP. Este documento resume las recomendaciones de implementación y control de acceso.

## Autenticación

- los usuarios deben autenticarse mediante credenciales válidas
- debe existir control de sesiones activas y expiración
- la contraseña debe guardarse con hash seguro
- cada sesión debe ser tratada de forma aislada y verificable

## Autorización

El sistema debe restringir acciones según el rol del usuario:

- administrador
- repartidor
- contador
- usuario inactivo u otros roles definidos

## Recomendaciones

- validar permisos en middleware, policies y form requests
- usar CSRF en formularios con estado de sesión
- limitar intentos fallidos de inicio de sesión
- cerrar sesión de forma segura con POST
- evitar exponer información sensible en logs y respuestas

## Sesiones

- controlar expiración por inactividad
- permitir una sola sesión activa por usuario si se requiere
- confirmar identidad al cambiar credenciales o recuperar contraseña

## Recuperación de contraseña

Se recomienda:

- usar códigos temporales y de corta duración
- limitar cantidad de intentos
- hashear valores temporales antes de almacenarlos
- validar expiración y uso único

## Protección de la base de datos

- no versionar `.env` ni credenciales de producción
- separar entornos (dev, testing, prod)
- usar permisos mínimos en base de datos
- activar HTTPS en producción

## Checklist de seguridad

- `APP_DEBUG=false` en producción
- SSL habilitado
- sesiones configuradas correctamente
- permisos por rol implementados
- logs y errores controlados
- credenciales protegidas
