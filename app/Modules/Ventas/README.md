# Módulo Ventas (Grupo 2)

Este directorio está asignado al **Grupo 2: Ventas, Facturación, Cobros, Clientes y Promociones**.

## Estructura estándar
```text
app/Modules/Ventas/
├── Controllers/         # Controladores de ventas, cobros, facturación
├── Models/              # Modelos de venta, detalle de venta, cobro
├── Services/            # Lógica de cálculo de importes, promociones, etc.
├── routes/
│   ├── web.php          # Rutas web del módulo (cargadas automáticamente)
│   └── api.php          # Rutas API del módulo (cargadas automáticamente con prefijo /api)
└── database/
    └── migrations/      # Migraciones específicas del módulo
```

## Nota de Integración
Las rutas ubicadas en `routes/web.php` y `routes/api.php` son detectadas y cargadas automáticamente por el `ModuleServiceProvider`. No es necesario modificar los archivos de rutas globales del sistema.
