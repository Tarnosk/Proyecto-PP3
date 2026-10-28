# Módulo Productos (Grupo 3)

Este directorio está asignado al **Grupo 3: Catálogo de Productos, Categorías, Marcas y Precios**.

## Estructura estándar
```text
app/Modules/Productos/
├── Controllers/         # Controladores de productos, marcas, categorías
├── Models/              # Modelos (Producto, Categoria, Marca, HistorialPrecio)
├── Services/            # Lógica de aumentos masivos, listas de precios
├── routes/
│   ├── web.php          # Rutas web del módulo (cargadas automáticamente)
│   └── api.php          # Rutas API del módulo (cargadas automáticamente con prefijo /api)
└── database/
    └── migrations/      # Migraciones específicas del módulo
```

## Nota de Integración y Contrato de APIs
Las rutas ubicadas en `routes/web.php` y `routes/api.php` son detectadas y cargadas automáticamente por el `ModuleServiceProvider`. No es necesario modificar los archivos de rutas globales del sistema.

👉 **Documentación completa de APIs para los demás grupos (G1, G2 y G4):** Ver [`docs/contrato-api-g3.md`](../../../docs/contrato-api-g3.md).
