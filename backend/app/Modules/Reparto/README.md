# Módulo Reparto (Grupo 4)

Este directorio está asignado al **Grupo 4: Logística, Despacho, Hojas de Ruta y Entregas**.

## Estructura estándar
```text
app/Modules/Reparto/
├── Controllers/         # Controladores de despachos, hojas de ruta, entregas
├── Models/              # Modelos de envíos, vehículos, rutas
├── Services/            # Lógica de asignación de pedidos a repartidores
├── routes/
│   ├── web.php          # Rutas web del módulo (cargadas automáticamente)
│   └── api.php          # Rutas API del módulo (cargadas automáticamente con prefijo /api)
└── database/
    └── migrations/      # Migraciones específicas del módulo
```

## Nota de Integración
Las rutas ubicadas en `routes/web.php` y `routes/api.php` son detectadas y cargadas automáticamente por el `ModuleServiceProvider`. No es necesario modificar los archivos de rutas globales del sistema.
