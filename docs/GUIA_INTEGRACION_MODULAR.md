# Guía de Integración Modular - ERP Juan XXIII

Esta guía define las pautas de arquitectura y el flujo de integración en la rama troncal `develop` para todos los grupos de trabajo.

---

## 1. Arquitectura Modular (`app/Modules/`)

Para evitar conflictos en Git y mantener el código ordenado, la lógica de cada equipo reside exclusivamente dentro de su módulo en `app/Modules/`:

| Módulo | Grupo Responsable | Alcance Funcional |
|---|---|---|
| `app/Modules/Auth/` | **Grupo 1** | Autenticación, Roles, Sesión Única, Auditoría de Acceso, Usuarios. |
| `app/Modules/Ventas/` | **Grupo 2** | Ventas, Facturación, Cobros, Clientes, Promociones. |
| `app/Modules/Productos/` | **Grupo 3** | Catálogo de Productos, Categorías, Marcas, Precios. |
| `app/Modules/Proveedores/` | **Grupo 3** | Proveedores, Plazos de Entrega, Contactos. |
| `app/Modules/Stock/` | **Grupo 3** | Inventario, Movimientos de Stock, Ajustes, Devoluciones. |
| `app/Modules/Compras/` | **Grupo 3** | Compras, Órdenes de Compra, Recepción de Mercadería. |
| `app/Modules/Reparto/` | **Grupo 4** | Logística, Despacho, Hojas de Ruta, Entregas. |

---

## 2. Estructura Estándar de un Módulo

Cada módulo se estructura internamente de la siguiente forma:

```text
app/Modules/{NombreModulo}/
├── Controllers/         # Controladores HTTP del módulo
├── Models/              # Modelos Eloquent
├── Services/            # Lógica de negocio y casos de uso
├── routes/              # Rutas propias del módulo
│   ├── web.php          # Rutas web (middleware 'web' aplicado)
│   └── api.php          # Rutas API (middleware 'api' y prefijo '/api' aplicados)
├── database/
│   └── migrations/      # Migraciones específicas del módulo
└── resources/
    └── views/           # Vistas Blade del módulo
```

---

## 3. Autodescubrimiento de Rutas y Componentes (`ModuleServiceProvider`)

El sistema cuenta con un cargador automático (`App\Providers\ModuleServiceProvider`) que detecta y registra dinámicamente los componentes de cada módulo:

1. **Rutas Web:** Si defines `app/Modules/{Modulo}/routes/web.php`, se registran automáticamente con middleware `web`.
2. **Rutas API:** Si defines `app/Modules/{Modulo}/routes/api.php`, se registran automáticamente bajo el prefijo `/api` con middleware `api`.
3. **Migraciones:** Si creas `app/Modules/{Modulo}/database/migrations/`, Laravel las carga automáticamente al ejecutar `php artisan migrate`.
4. **Vistas:** Si tienes `app/Modules/{Modulo}/resources/views/`, puedes renderizarlas mediante la notación `view('{modulo}::nombre-vista')`.

> **Regla de Oro:** **NO MODIFICAR** los archivos de rutas globales (`routes/web.php` o `routes/api.php`) salvo acuerdo explícito de coordinación. Coloca tus rutas dentro de la carpeta `routes/` de tu propio módulo.

---

## 4. Flujo de Trabajo e Integración (Git)

1. **Trabajar en su rama:** Cada grupo desarrolla sus funcionalidades en su rama (`develop-g1`, `develop-g2`, `develop-g3`, `develop-g4`).
2. **Sincronizarse con develop antes de enviar:**
   ```bash
   git fetch origin
   git pull origin develop
   ```
3. **Verificar que las pruebas pasen:**
   ```bash
   php artisan test
   ```
4. **Abrir Pull Request:**
   * Origen: `develop-gX`
   * Destino: `develop`
   * Verificar que el PR **únicamente** contenga archivos de su carpeta en `app/Modules/` (y migraciones o vistas si aplican).
5. **Aprobación:** Una vez revisado y aprobado el PR por el coordinador, se avisará a todos los grupos para que hagan `git pull origin develop` y mantengan sus ramas actualizadas.
