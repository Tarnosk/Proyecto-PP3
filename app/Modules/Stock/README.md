# Módulo Stock e Inventario (Grupo 3)

Este directorio está asignado al **Grupo 3: Stock, Inventario, Ajustes y Movimientos**.

## Estructura estándar
```text
app/Modules/Stock/
├── Controllers/         # Controladores de stock, ajustes, conteos físicos
├── Models/              # Modelos (MovimientoStock, Ubicacion, UnidadMedida, etc.)
├── Services/            # Lógica de cálculo de stock, alertas
├── routes/
│   ├── web.php          # Rutas web del módulo
│   └── api.php          # Rutas API del módulo
└── database/
    └── migrations/      # Migraciones específicas del módulo
```
