# Módulo Auth (Grupo 1)

Este directorio está asignado al **Grupo 1: Autenticación, Roles, Sesiones y Seguridad**.

## Estructura estándar
```text
app/Modules/Auth/
├── Controllers/         # Controladores de login, registro, sesiones
├── Models/              # Modelos (User, Rol, Sesion, etc.)
├── Services/            # Lógica de tokens, autenticación y seguridad
├── routes/
│   ├── web.php          # Rutas web del módulo (cargadas automáticamente)
│   └── api.php          # Rutas API del módulo (cargadas automáticamente con prefijo /api)
└── database/
    └── migrations/      # Migraciones específicas del módulo
```

## Nota de Integración
Las rutas ubicadas en `routes/web.php` y `routes/api.php` son detectadas y cargadas automáticamente por el `ModuleServiceProvider`. No es necesario modificar los archivos de rutas globales del sistema.
