# Guía de Integración Modular - ERP Juan XXIII (Rama `develop`)

> **Estado de integración G1 (18/10/2026):** Esta guía describe la arquitectura modular propuesta para `develop`, no el estado completo del código integrado. G1 conserva sus rutas y controladores globales y consulta ventas, catálogo, cobros y saldos mediante APIs de los grupos responsables, sin leer sus tablas locales para esos flujos. El equipo debe acordar si la integración final usará una base compartida o servicios HTTP antes de aplicar las reglas de base de datos unificada que siguen. Ver `docs/integracion-microservicios.md` y `docs/contratos-sprint4-g1.md` para el estado actual de G1.

Esta guía define las pautas de arquitectura, el esquema de datos y el flujo de integración en la rama troncal `develop` para todos los grupos de trabajo.

---

## 1. Principios Fundamentales del Proyecto

### A. Framework: Laravel 12
El proyecto está construido sobre el esqueleto oficial de **Laravel 12** (`"laravel/framework": "^12.0"`), con PHP 8.2+.

### B. Sin Vite (Frontend Tradicional)
Para simplificar el entorno de desarrollo y evitar dependencias de Node.js / npm bundlers:
* **No se utiliza Vite ni compiladores de frontend.**
* Los estilos CSS, librerías y scripts JavaScript deben residir directamente en la carpeta pública (`public/css/`, `public/js/`, `public/assets/`).
* En las vistas Blade se incluyen mediante la función nativa de Laravel:
  ```html
  <link rel="stylesheet" href="{{ asset('css/estilos.css') }}">
  <script src="{{ asset('js/script.js') }}"></script>
  ```

### C. Base de Datos Unificada (Mismo Esquema Relacional)
> **IMPORTANTE:** El ERP opera bajo una **ÚNICA BASE DE DATOS COMPARTIDA** (configurada en `.env` como `DB_DATABASE=distribuidorapyb` en MySQL).
> * **NO existen bases de datos por módulo ni microbases.**
> * Todos los módulos (`Auth`, `Ventas`, `Productos`, `Stock`, `Proveedores`, `Compras`, `Reparto`) conviven en las mismas tablas.
> * Las relaciones entre módulos son claves foráneas directas:
>   * `ventas.id_cliente` $\rightarrow$ `clientes.id_cliente`
>   * `ventas.id_usuario` $\rightarrow$ `usuarios.id` / `usuario.id_usuario`
>   * `detalle_venta.id_producto` $\rightarrow$ `producto.id_producto`
>   * `repartos.id_venta` $\rightarrow$ `venta.id_venta`
> * Las migraciones de cada módulo impactan en este único esquema central.

---

## 2. Arquitectura Modular (`app/Modules/`)

Para evitar conflictos en Git y mantener el código ordenado, la lógica de cada equipo reside exclusivamente dentro de su carpeta en `app/Modules/`:

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

## 3. Estructura Estándar de un Módulo

Cada módulo se estructura internamente de la siguiente forma:

```text
app/Modules/{NombreModulo}/
├── Controllers/         # Controladores HTTP del módulo
├── Models/              # Modelos Eloquent (apuntan a la BD unificada)
├── Services/            # Lógica de negocio y casos de uso
├── routes/              # Rutas propias del módulo
│   ├── web.php          # Rutas web (middleware 'web' aplicado automáticamente)
│   └── api.php          # Rutas API (middleware 'api' y prefijo '/api' aplicados)
├── database/
│   └── migrations/      # Migraciones que impactan en la BD unificada
└── resources/
    └── views/           # Vistas Blade del módulo
```

---

## 4. Autodescubrimiento con `ModuleServiceProvider`

El sistema cuenta con el cargador automático `App\Providers\ModuleServiceProvider` que detecta y registra dinámicamente los componentes de cada módulo:

1. **Rutas Web:** Si defines `app/Modules/{Modulo}/routes/web.php`, se registran automáticamente con middleware `web`.
2. **Rutas API:** Si defines `app/Modules/{Modulo}/routes/api.php`, se registran automáticamente bajo el prefijo `/api` con middleware `api`.
3. **Migraciones:** Si creas `app/Modules/{Modulo}/database/migrations/`, Laravel las carga automáticamente al ejecutar `php artisan migrate`.
4. **Vistas:** Si tienes `app/Modules/{Modulo}/resources/views/`, puedes renderizarlas mediante `view('{modulo}::nombre-vista')`.

> **Regla de Oro:** **NO MODIFICAR** los archivos de rutas globales (`routes/web.php` o `routes/api.php`). Coloca tus rutas dentro de la carpeta `routes/` de tu propio módulo.

---

## 5. Flujo de Trabajo e Integración (Git)

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
   * Verificar que el PR **únicamente** contenga archivos dentro de su carpeta en `app/Modules/` (y assets públicos en `public/` si corresponde).
5. **Aprobación:** Una vez revisado y aprobado el PR por el coordinador, se avisará a todos los grupos para que hagan `git pull origin develop` y mantengan sus ramas actualizadas.
