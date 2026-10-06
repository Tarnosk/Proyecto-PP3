# Plan de Integración — Grupo 3 (Revisado)
## ERP Distribuidora PyB · Feature Branches → Proyecto Principal

**Principio rector:** El código importado se adapta a NUESTRA base de datos. Nunca modificamos nuestro esquema para acomodar el de otras ramas.

**Fecha:** 2026-10-02  

---

## 1. Nuestra BD como referencia canónica

Las tablas relevantes en `DistribuidoraPyB (Version Corregida).sql`:

### `LOTE` (líneas 195–205 del SQL)
```sql
CREATE TABLE `LOTE` (
  `id_lote`           INT AUTO_INCREMENT PRIMARY KEY,
  `id_producto`       INT NOT NULL,
  `nro_lote`          VARCHAR(50) NOT NULL,
  `cantidad_inicial`  INT NOT NULL,
  `cantidad_actual`   INT NOT NULL,
  `fecha_vencimiento` DATE NOT NULL,
  UNIQUE KEY `uq_lote_producto_nro` (`id_producto`, `nro_lote`)
);
```

### `PRODUCTO` (líneas 129–152 del SQL)
Columnas relevantes: `id_producto`, `codigo`, `nombre`, `descripcion`, `precio_unitario`, `stock`, `stock_minimo`, `dias_alerta_vencimiento`, `estado` ENUM('activo','inactivo').

### `PROVEEDOR` (líneas 254–269 del SQL)
Columnas relevantes: `id_proveedor`, `razon_social` (no `nombre`), `plazo_entrega_dias`, `estado`.

### `MOVIMIENTO_STOCK` (líneas 414–434 del SQL)
Columnas relevantes: `id_movimiento`, `id_producto`, `tipo` ENUM('ingreso','venta','devolucion','ajuste'), `cantidad`, `fecha`, `id_lote` (nullable).

### `PRODUCTO_PROVEEDOR` (líneas 271–284 del SQL)
Columnas relevantes: `id_producto_proveedor`, `id_producto`, `id_proveedor`, `precio_acordado`, `activo`, `es_proveedor_principal`.

---

## 2. Incompatibilidades detectadas (código externo vs. nuestra BD)

### 2.1 Incompatibilidades CRÍTICAS — `LOTE`

| Campo que usa el código externo | Nuestra columna | Acción |
|---|---|---|
| `cantidad` | `cantidad_actual` | Renombrar referencias en código importado |
| `id_movimiento` (FK a MOVIMIENTO_STOCK) | No existe | **No agregar**. Usar `MOVIMIENTO_STOCK.id_lote` (ya existe en nuestra BD) para la relación inversa |
| `id_unidad` (FK a UNIDAD_MEDIDA) | No existe | **No agregar**. La unidad vive en `MOVIMIENTO_STOCK.id_unidad` |
| `estado` ENUM('vigente','vencido','consumido') | No existe | **No agregar**. Derivar el estado dinámicamente (ver abajo) |
| `UNIQUE KEY (id_producto, nro_lote)` | ✅ Ya existe en nuestra BD | Sin cambios |

**Estrategia de adaptación:**
- `cantidad` → `cantidad_actual` en todo el código PHP importado.
- El estado (`vigente`/`vencido`) se deriva de `fecha_vencimiento` comparada con `NOW()`. No se almacena.
- `id_movimiento` en el código de Martellini se invierte: ya existe `MOVIMIENTO_STOCK.id_lote`. Para obtener el movimiento asociado a un lote, hacer `MovimientoStock::where('id_lote', $lote->id_lote)->first()`.
- `id_unidad` en el código de Martellini: ignorar/null. La unidad de un movimiento vive en `MOVIMIENTO_STOCK.id_unidad`.
- `cantidad_inicial` es la cantidad al momento del ingreso (se fija al crear). No existe en el código externo; al crear un `Lote`, asignar `cantidad_inicial = cantidad_actual = $cantidad`.

### 2.2 Incompatibilidades MEDIAS — `SUGERENCIA_COMPRA`

La tabla no existe en nuestra BD y debe crearse. El modelo de Gomis-Calvo tiene más columnas que el plan anterior. La estructura correcta a crear es:

```sql
CREATE TABLE `SUGERENCIA_COMPRA` (
  `id_sugerencia`          INT AUTO_INCREMENT PRIMARY KEY,
  `id_producto`            INT NOT NULL,
  `id_proveedor`           INT NULL,
  `cantidad_sugerida`      INT NOT NULL,
  `cantidad_minima`        INT NULL,
  `cantidad_maxima`        INT NULL,
  `precio_unitario`        DECIMAL(12,2) NULL,
  `costo_total`            DECIMAL(12,2) NULL,
  `velocidad_venta_diaria` DECIMAL(10,4) NULL,
  `plazo_entrega_dias`     INT NULL,
  `fecha_reorden`          DATE NULL,
  `estado`                 VARCHAR(20) NOT NULL DEFAULT 'pendiente'
                           COMMENT 'pendiente | procesada | rechazada',
  `motivo_generacion`      VARCHAR(50) NOT NULL
                           COMMENT 'bajo_stock | proximo_vencer | agotamiento_inmediato',
  `observaciones`          TEXT NULL,
  `fecha_generacion`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_resolucion`       DATETIME NULL,
  `id_usuario_resolucion`  INT NULL,
  CONSTRAINT `fk_sug_producto`
    FOREIGN KEY (`id_producto`) REFERENCES `PRODUCTO` (`id_producto`),
  CONSTRAINT `fk_sug_proveedor`
    FOREIGN KEY (`id_proveedor`) REFERENCES `PROVEEDOR` (`id_proveedor`),
  CONSTRAINT `fk_sug_usuario`
    FOREIGN KEY (`id_usuario_resolucion`) REFERENCES `USUARIO` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Sugerencias de reposición automáticas (PC07)';
```

> **Nota:** FKs con `INT` (no `UNSIGNED`) para coincidir con `PRODUCTO.id_producto` e `PROVEEDOR.id_proveedor` que son `INT` signed.

### 2.3 Incompatibilidades MENORES — nombres de columnas

| Código externo usa | Nuestra BD tiene | Corrección |
|---|---|---|
| `Producto::where('activo', true)` (Calvo-Gomis original) | `PRODUCTO.estado = 'activo'` | Ya corregido en versión Gomis-Calvo final |
| `Proveedor.nombre` (Calvo-Gomis original) | `PROVEEDOR.razon_social` | Ya corregido en versión Gomis-Calvo final |
| `Stock.stock_maximo` | No existe → `config('reposicion.stock_maximo_default')` | Ya corregido en versión Gomis-Calvo final |
| `producto->detalleVentas()` | `MOVIMIENTO_STOCK` con `tipo='venta'` | Ya corregido en versión Gomis-Calvo final |
| `LoteController` busca `producto->stock_disponible` | Nuestro `Producto` tiene el accessor `getStockDisponibleAttribute()` | ✅ Compatible |
| `LoteController` busca `producto->estado_alerta` | Nuestro `Producto` tiene `getEstadoAlertaAttribute()` | ✅ Compatible |

---

## 3. Cambios requeridos en el código importado antes de copiarlo

### 3.1 `Lote.php` — reescribir completamente

El `Lote.php` de ambas ramas debe ser reescrito para usar nuestra estructura. A continuación el modelo adaptado listo para usar en nuestro proyecto:

```php
<?php
declare(strict_types=1);

namespace App\Modules\Stock\Models;

use App\Modules\Productos\Models\Producto;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lote extends Model
{
    protected $table = 'LOTE';
    protected $primaryKey = 'id_lote';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'nro_lote',
        'cantidad_inicial',
        'cantidad_actual',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'id_lote'           => 'integer',
        'id_producto'       => 'integer',
        'cantidad_inicial'  => 'integer',
        'cantidad_actual'   => 'integer',
        'fecha_vencimiento' => 'date:Y-m-d',
    ];

    protected $appends = [
        'dias_para_vencer',
        'estado',
    ];

    // ──────────────────────────────────────────────
    // RELACIONES
    // ──────────────────────────────────────────────

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    /**
     * El movimiento de ingreso se obtiene desde MOVIMIENTO_STOCK.id_lote
     * (relación inversa: MOVIMIENTO_STOCK tiene la FK, no LOTE).
     */
    public function movimiento(): HasOne
    {
        return $this->hasOne(MovimientoStock::class, 'id_lote', 'id_lote')
                    ->where('tipo', 'ingreso');
    }

    // ──────────────────────────────────────────────
    // ATRIBUTOS CALCULADOS
    // ──────────────────────────────────────────────

    /** Días hasta vencimiento (negativo si ya venció). */
    public function getDiasParaVencerAttribute(): ?int
    {
        if (!$this->fecha_vencimiento) {
            return null;
        }
        return (int) Carbon::today()->diffInDays($this->fecha_vencimiento, false);
    }

    /**
     * Estado derivado del vencimiento y stock actual.
     * 'vigente' | 'vencido' | 'consumido'
     * No se almacena en BD; se calcula en tiempo real.
     */
    public function getEstadoAttribute(): string
    {
        if ((int) $this->cantidad_actual <= 0) {
            return 'consumido';
        }
        $dias = $this->getDiasParaVencerAttribute();
        return ($dias !== null && $dias < 0) ? 'vencido' : 'vigente';
    }

    // ──────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────

    public function scopePorProducto(Builder $query, int $idProducto): Builder
    {
        return $query->where('id_producto', $idProducto);
    }

    /** Lotes no consumidos (cantidad > 0) y no vencidos. */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('cantidad_actual', '>', 0)
                     ->where('fecha_vencimiento', '>=', Carbon::today()->toDateString());
    }

    /** Lotes vigentes que vencen en los próximos N días. */
    public function scopePorVencer(Builder $query, int $dias = 30): Builder
    {
        $hoy    = Carbon::today()->toDateString();
        $limite = Carbon::today()->addDays($dias)->toDateString();
        return $query->where('cantidad_actual', '>', 0)
                     ->whereBetween('fecha_vencimiento', [$hoy, $limite]);
    }

    /** Búsqueda por nro_lote o código/descripción de producto. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term && trim($term) !== '') {
            $term = trim($term);
            return $query->where(function (Builder $q) use ($term) {
                $q->where('nro_lote', 'LIKE', "%{$term}%")
                  ->orWhereHas('producto', function (Builder $qp) use ($term) {
                      $qp->where('codigo', 'LIKE', "%{$term}%")
                         ->orWhere('descripcion', 'LIKE', "%{$term}%");
                  });
            });
        }
        return $query;
    }
}
```

### 3.2 `LoteController.php` — cambios específicos

| Línea original (Martellini) | Cambio requerido |
|---|---|
| `'cantidad' => $cantidad` al crear | `'cantidad_inicial' => $cantidad, 'cantidad_actual' => $cantidad` |
| `$l->cantidad` en `formatearLote()` | `$l->cantidad_actual` |
| `Lote::create([..., 'id_movimiento' => $idMovimiento, 'id_unidad' => $idUnidad, 'estado' => $estado, ...])` | Eliminar `id_movimiento`, `id_unidad`, `estado`. Agregar `cantidad_inicial`. Actualizar `MOVIMIENTO_STOCK.id_lote` en su lugar. |
| `$l->unidad ? $l->unidad->nombre_unidad : null` | `null` (sin `id_unidad` en LOTE; si se necesita, obtener de `$l->movimiento?->unidad?->nombre_unidad`) |
| Validator: `'id_unidad' => 'nullable|integer|exists:UNIDAD_MEDIDA,id_unidad'` | Mantener (se pasa al movimiento, no al lote) |
| `->where('estado', '!=', 'consumido')` en Repository | `->where('cantidad_actual', '>', 0)` |
| `->where('estado', 'vigente')` en scopes | `->where('cantidad_actual', '>', 0)->where('fecha_vencimiento', '>=', today())` |
| `sum('cantidad')` en `cantidadEnRiesgoPorProducto()` | `sum('cantidad_actual')` |

### 3.3 `LoteVencimientoRepository.php` — cambios específicos

| Línea | Cambio |
|---|---|
| `->where('cantidad', '>', 0)` | `->where('cantidad_actual', '>', 0)` |
| `->where('estado', '!=', 'consumido')` | `->where('cantidad_actual', '>', 0)` (el estado es derivado) |
| `->where('estado', 'vigente')` | Reemplazar con `->where('cantidad_actual', '>', 0)->where('fecha_vencimiento', '>=', Carbon::today()->toDateString())` |
| `->sum('cantidad')` | `->sum('cantidad_actual')` |

### 3.4 `IngresoMercaderiaController.php` — lógica de creación de lotes

Al crear el lote desde el ingreso de mercadería, el patrón cambia:
1. Crear el `MovimientoStock` (como ya se hace).
2. Si vienen `nro_lote` y `fecha_vencimiento`, crear `Lote` con `cantidad_inicial = cantidad_actual = $cantidad`.
3. Actualizar `MOVIMIENTO_STOCK.id_lote = $lote->id_lote` con `$movimiento->update(['id_lote' => $lote->id_lote])`.

### 3.5 `GeneradorSugerenciasReposicionService.php` — sin cambios

El servicio de Gomis-Calvo ya usa correctamente:
- `Producto.stock` / `Producto.stock_minimo` ✅
- `MOVIMIENTO_STOCK` con `tipo='venta'` ✅
- `PROVEEDOR.razon_social` ✅
- `PRODUCTO_PROVEEDOR.precio_acordado` ✅
- `LoteVencimientoRepository::cantidadEnRiesgoPorProducto()` → llama a `sum('cantidad')` → cambiar a `sum('cantidad_actual')` (ver 3.3)

---

## 4. Pasos de integración (con adaptaciones)

### PASO 1 — Integrar Martellini-Giraudo (S09 + S10)

#### 1.1 Crear `Lote.php` en el proyecto destino
No copiar el original. Usar el modelo reescrito de la sección 3.1 directamente.
**Destino:** `backend/app/Modules/Stock/Models/Lote.php`

#### 1.2 Copiar y adaptar `LoteController.php`
Copiar desde Martellini-Giraudo y aplicar los cambios de la sección 3.2.
**Destino:** `backend/app/Modules/Stock/Controllers/LoteController.php`

#### 1.3 Modificar `StockService.php`
- `registrarMovimiento()` debe retornar la instancia `MovimientoStock` persistida.
- **No modificar el esquema de parámetros** del método.

#### 1.4 Modificar `IngresoMercaderiaController.php`
Aplicar la lógica de creación de lotes descrita en la sección 3.4.

#### 1.5 Agregar relación `lotes()` en `Producto.php`
```php
public function lotes(): HasMany
{
    return $this->hasMany(Lote::class, 'id_producto', 'id_producto');
}
```

#### 1.6 **No agregar** relación `lotes()` en `MovimientoStock.php`
La FK está en `MOVIMIENTO_STOCK.id_lote`, no al revés. La relación ya disponible es:
```php
// En MovimientoStock.php — si se necesita
public function lote(): BelongsTo
{
    return $this->belongsTo(Lote::class, 'id_lote', 'id_lote');
}
```

#### 1.7 Rutas en `backend/routes/api.php`
```php
// ─── LOTES Y VENCIMIENTOS (S09, S10) ─────────────────────────────────────────
use App\Modules\Stock\Controllers\LoteController;

Route::get('stock/lotes',                [LoteController::class, 'index']);
Route::post('stock/lotes',               [LoteController::class, 'store']);
Route::get('stock/lotes/{id}',           [LoteController::class, 'show']);
Route::get('stock/productos/{id}/lotes', [LoteController::class, 'lotesPorProducto']);
// Reemplaza la ruta muerta (tablas en minúscula de StockController):
Route::get('stock/lotes-por-vencer',     [LoteController::class, 'porVencer']);
```

#### 1.8 Integrar cambios en `stock.html`
- Botón "📦 Lotes y Vencimientos" en la botonera.
- Botón "📦 Lotes" por fila de producto.
- Modal `#modalLotes` con tabla semáforo (rojo ≤15d, amarillo 16–30d, verde >30d).
- Campos `N° Lote` y `Vence` en el modal de ingreso.

---

### PASO 2 — Integrar Gomis-Calvo (S11 + PC07)

#### 2.1 Copiar y adaptar `LoteVencimientoRepository.php`
Copiar y aplicar los cambios de la sección 3.3.
**Destino:** `backend/app/Modules/Stock/Repositories/LoteVencimientoRepository.php`

#### 2.2 Copiar sin cambios (código ya adaptado a nuestra BD)
| Archivo | Destino |
|---|---|
| `app/Modules/Stock/Services/ConsultaVencimientosService.php` | igual |
| `app/Modules/Stock/Controllers/ConsultaVencimientosController.php` | igual |
| `app/Modules/PedidosDeCompra/Models/SugerenciaCompra.php` | igual |
| `app/Modules/PedidosDeCompra/Repositories/SugerenciaCompraRepository.php` | igual |
| `app/Modules/PedidosDeCompra/Services/GeneradorSugerenciasReposicionService.php` | igual (sólo cambiar `sum('cantidad')` por `sum('cantidad_actual')` en Repository) |
| `app/Modules/PedidosDeCompra/Services/DashboardReposicionService.php` | igual |
| `app/Modules/PedidosDeCompra/Controllers/SugerenciasReposicionController.php` | igual |
| `app/Modules/PedidosDeCompra/Controllers/DashboardReposicionController.php` | igual |
| `config/reposicion.php` | igual |

#### 2.3 Crear migración para `SUGERENCIA_COMPRA`
Copiar la migración de Gomis-Calvo y verificar que use `INT` (no `UNSIGNED`) para las FKs hacia `PRODUCTO`, `PROVEEDOR` y `USUARIO`.

#### 2.4 Copiar vistas, CSS y JS
| Archivo | Destino |
|---|---|
| `resources/views/layouts/app.blade.php` | igual |
| `resources/views/vencimientos.blade.php` | igual |
| `resources/views/sugerencias-reposicion.blade.php` | igual |
| `resources/views/dashboard-reposicion.blade.php` | igual |
| `public/css/claymorfismo.css` | igual |
| `public/js/vencimientos.js` | igual |
| `public/js/sugerencias-reposicion.js` | igual |
| `public/js/dashboard-reposicion.js` | igual |

#### 2.5 Rutas en `api.php` y `web.php`

**`api.php`:**
```php
// ─── S11: CONSULTA DE VENCIMIENTOS ────────────────────────────────────────────
use App\Modules\Stock\Controllers\ConsultaVencimientosController;

Route::prefix('vencimientos')->group(function () {
    Route::get('proximos',       [ConsultaVencimientosController::class, 'proximos']);
    Route::get('por-criticidad', [ConsultaVencimientosController::class, 'porCriticidad']);
    Route::get('alertas',        [ConsultaVencimientosController::class, 'alertas']);
    Route::get('reporte',        [ConsultaVencimientosController::class, 'reporte']);
});

// ─── PC07: SUGERENCIAS + DASHBOARD ────────────────────────────────────────────
use App\Modules\PedidosDeCompra\Controllers\SugerenciasReposicionController;
use App\Modules\PedidosDeCompra\Controllers\DashboardReposicionController;

Route::prefix('sugerencias-reposicion')->group(function () {
    Route::get('/',              [SugerenciasReposicionController::class, 'index']);
    Route::post('/generar',      [SugerenciasReposicionController::class, 'generar']);
    Route::get('/resumen',       [SugerenciasReposicionController::class, 'resumen']);
    Route::post('/procesar-lote',[SugerenciasReposicionController::class, 'procesarLote']);
    Route::post('/{id}/procesar',[SugerenciasReposicionController::class, 'procesar']);
    Route::post('/{id}/rechazar',[SugerenciasReposicionController::class, 'rechazar']);
});

Route::prefix('dashboard-reposicion')->group(function () {
    Route::get('/resumen',           [DashboardReposicionController::class, 'resumen']);
    Route::get('/grafico-motivos',   [DashboardReposicionController::class, 'graficoMotivos']);
    Route::get('/grafico-tendencia', [DashboardReposicionController::class, 'graficoTendencia']);
    Route::get('/grafico-criticidad',[DashboardReposicionController::class, 'graficoCriticidad']);
});
```

**`web.php`:**
```php
Route::view('/vencimientos',           'vencimientos');
Route::view('/sugerencias-reposicion', 'sugerencias-reposicion');
Route::view('/dashboard-reposicion',   'dashboard-reposicion');
```

#### 2.6 Ejecutar migración
```bash
php artisan migrate
# Crea SUGERENCIA_COMPRA. Requiere PRODUCTO y PROVEEDOR existentes.
```

#### 2.7 Sidebar HTML
En `public/Interfaz/index.html`, agregar:
```html
<a href="/vencimientos">📅 Vencimientos</a>
<a href="/sugerencias-reposicion">📋 Sugerencias de Reposición</a>
<a href="/dashboard-reposicion">📊 Dashboard Reposición</a>
```

---

## 5. Resumen de incompatibilidades y resoluciones

| Incompatibilidad | Gravedad | Resolución |
|---|---|---|
| `LOTE.cantidad` vs `cantidad_actual` | Crítica | Renombrar en todo el código PHP importado |
| `LOTE.estado` (no existe en nuestra BD) | Crítica | Derivar dinámicamente: `cantidad_actual > 0 AND fecha_vencimiento >= today` = vigente |
| `LOTE.id_movimiento` (no existe en nuestra BD) | Crítica | Invertir relación: usar `MOVIMIENTO_STOCK.id_lote` (ya existe) |
| `LOTE.id_unidad` (no existe en nuestra BD) | Media | No se copia; unidad se obtiene del movimiento |
| `cantidad_inicial` no existe en código externo | Media | Al crear: `cantidad_inicial = cantidad_actual = $cantidad` |
| `SUGERENCIA_COMPRA` no existe en nuestra BD | Media | Crear con migración (DDL en sección 2.2) |
| FK types `UNSIGNED` vs `INT` en migración | Media | Verificar que la migración use `integer()` + `foreign()` |
| `producto_lotes` / `ProductoLote` (Gomis-Calvo original) | Crítica | Descartar. Usar LOTE / Lote |
| `StockController::lotesPorVencer` (código muerto) | Media | Reemplazar ruta con `LoteController::porVencer` |

---

## 6. Archivos que NO se integran

| Archivo | Motivo |
|---|---|
| Migración `producto_lotes` + `ProductoLote.php` | Tabla descartada, usamos `LOTE` |
| `ProductoLoteController.php` + vistas `lotes/*.blade.php` | Duplica `LoteController` |
| `StockController::lotesPorVencer` (destino) | Código muerto con tablas en minúscula |
| Providers para `config/app.php` | Laravel 12 usa `bootstrap/providers.php` |
| Middleware `auth:sanctum` / `auth` web | Sanctum no está instalado; proyecto sin auth |

---

## 7. Checklist de integración

### Paso 1 (Martellini — S09/S10)
- [x] Crear `Lote.php` con el modelo reescrito (sección 3.1)
- [x] Copiar y adaptar `LoteController.php` (cambios sección 3.2)
- [x] `StockService::registrarMovimiento` retorna instancia `MovimientoStock`
- [x] `IngresoMercaderiaController` crea `Lote` y actualiza `MOVIMIENTO_STOCK.id_lote`
- [x] Relación `lotes(): HasMany` en `Producto.php`
- [x] Relación `lote(): BelongsTo` en `MovimientoStock.php` (opcional, solo si se necesita)
- [x] Rutas S09/S10 en `api.php` (ruta muerta reemplazada)
- [x] `stock.html` actualizado (modal lotes)

### Paso 2 (Gomis-Calvo — S11/PC07)
- [x] Copiar y adaptar `LoteVencimientoRepository.php` (cambios sección 3.3)
- [x] Copiar 7 clases PHP sin cambios (verificando `sum('cantidad_actual')`)
- [x] Copiar `config/reposicion.php`
- [x] Copiar y verificar migración `create_sugerencia_compra_table.php` (FKs con `INT`)
- [x] Copiar 3 vistas Blade + layout
- [x] Copiar CSS y JS (4 archivos)
- [x] Rutas S11/PC07 en `api.php`
- [x] Rutas web en `web.php`
- [x] `php artisan migrate`
- [x] Sidebar HTML actualizado

### Verificación final
- [x] `GET /api/stock/lotes` → 200
- [x] `POST /api/stock/lotes` → 201; `LOTE.cantidad_inicial = cantidad_actual = N`; `MOVIMIENTO_STOCK.id_lote` actualizado
- [x] `GET /api/stock/lotes-por-vencer` → lista usando `cantidad_actual > 0`
- [x] `GET /api/vencimientos/proximos` → `{exito, datos}` correcto
- [x] `POST /api/sugerencias-reposicion/generar` → crea sugerencias; no duplica
- [x] `POST /api/sugerencias-reposicion/{id}/procesar` × 2 → segunda retorna 400
- [x] Páginas `/vencimientos`, `/sugerencias-reposicion`, `/dashboard-reposicion` cargan en browser
