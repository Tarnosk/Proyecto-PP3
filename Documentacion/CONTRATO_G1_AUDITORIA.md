# Contrato G1 · Auditoría de usuario (Grupo 3 - backend)

> Estado: implementado el **nivel A (middleware de identidad)**. Este documento
> define el contrato para que G1 entregue la identidad del usuario y, como
> referencia, describe el nivel C (triggers de BD) que quedó disponible como
> alternativa sin activar.

## Objetivo

Registrar en `MOVIMIENTO_STOCK.id_usuario`, `PRODUCTO.id_usuario_carga /
id_usuario_modificacion`, `HISTORIAL_PRECIO.id_usuario`, etc. **quién** ejecutó
cada acción, y más adelante poblar `LOG_AUDITORIA`. El backend del Grupo 3
nunca confía en un `id_usuario` enviado en el body de la request (era
manipulable): la identidad sale de un único punto confiable.

## Arquitectura elegida (nivel A)

```
                    ┌─ login / token / sesión ──────────► Auth de Laravel (G1)
request ─► IdentidadUsuario ─► atributo 'usuario_autenticado_id'
(cliente)    (middleware)                       │
                                                ▼
                                    UsuarioActual::id($request)
                                                │
              controladores G3 (ver listado) ───┘  (jamas leen el body)
```

Archivos:

| Archivo | Rol |
|---|---|
| `backend/app/Http/Middleware/IdentidadUsuario.php` | Detecta sesión en el guard `proyecto` o el guard por defecto y expone la identidad en el atributo de request `usuario_autenticado_id`. Registrado en el grupo `api` (ver `backend/bootstrap/app.php`). |
| `backend/app/Support/UsuarioActual.php` | Único lector de identidad. Resuelve por prioridad: (1) atributo `usuario_autenticado_id`, (2) guard `proyecto` si está configurado, (3) `Auth::id()` por defecto, (4) **fallback admin id=1**. |

## Contrato para G1 (autenticación)

G1 es dueño de la tabla `USUARIO` (`id_usuario`, `user`, `password`, `rol`,
`estado`) y valida el login. Debe entregar la identidad de una de estas dos
formas (no hace falta implementar ambas):

1. **Guard de Laravel (recomendada):** configurar un guard (nombre sugerido
   `proyecto`) contra la tabla `USUARIO` (provider Eloquent/SQL). El middleware
   `IdentidadUsuario` lo detecta automáticamente y rellena el atributo. Se puede
   agregar el middleware a la ruta de login para "marcar" lógicamente cada
   request autenticada.
2. **Atributo de request:** si G1 valida por header/token propio, setear en el
   request el atributo `usuario_autenticado_id` (int) antes de que el
   controlador actúe (cualquier middleware previo sirve; el orden se define en
   `bootstrap/app.php`).

Garantías del lado G3:

- Ningún controlador lee `id_usuario` del body; el campo está fuera de las
  reglas de validación y simplemente se ignora.
- Si G1 aún no integra el login, todo se graba con el **fallback admin (id=1)**
  y queda visible en las columnas `id_usuario*` para depuración.
- G3 no crea tablas de usuarios ni sesiones propias; consume las de G1.

### Comportamiento esperado

| Situación | id_usuario registrado |
|---|---|
| G1 autenticó (guard `proyecto` o default) | El `id_usuario` real de `USUARIO` |
| Solo atributo `usuario_autenticado_id` seteado | Ese id (debe existir en `USUARIO`) |
| Sin identidad (contrato no integrado aún) | `1` (admin semilla) |

## Uso en controladores

Los 9 controladores que hoy participan usan `UsuarioActual::id($request)`:

- `Productos\ProductoController` (alta/modificación/estado)
- `Proveedores\ProveedorController` (alta/edición/asociaciones)
- `Stock\IngresoMercaderiaController`, `VentaStockController`,
  `DevolucionController`, `AjusteStockController`
- `Compras\CompraController`, `Compras\RecepcionController`
- `PedidosDeCompra\OrdenCompraController`

Cualquier módulo nuevo agrega: `use App\Support\UsuarioActual;` y, en la
acción, `$idUsuario = UsuarioActual::id($request);`.

## Nivel C (referencia): triggers de BD sobre LOG_AUDITORIA

Quedó **documentado pero NO activado** (se eligió consistencia de aplicación;
si se quiere a prueba de bypass se puede habilitar).

`LOG_AUDITORIA` existe (0 filas):
`id_auditoria, tabla_afectada, id_registro, accion, valores_anteriores,
valores_nuevos, id_usuario, fecha_hora`.

Esquema propuesto si se decide activar:

1. Los controladores ejecutan `DB::statement('SET @usuario_activo = ?', [UsuarioActual::id()])`
   al inicio de cada acción (o el middleware lo hace en el request).
2. Un trigger por cada tabla sensible (`PRODUCTO`, `PROVEEDOR`,
   `MOVIMIENTO_STOCK`, `COMPRA`, `ORDEN_COMPRA`, `RECEPCION`, ...):

```sql
DROP TRIGGER IF EXISTS trg_log_auditoria_producto;
DELIMITER $$
CREATE TRIGGER trg_log_auditoria_producto
AFTER UPDATE ON PRODUCTO
FOR EACH ROW
BEGIN
    INSERT INTO LOG_AUDITORIA
        (tabla_afectada, id_registro, accion,
         valores_anteriores, valores_nuevos, id_usuario, fecha_hora)
    VALUES
        ('PRODUCTO', NEW.id_producto, 'UPDATE',
         CONCAT('stock:', OLD.stock, '|min:', OLD.stock_minimo),
         CONCAT('stock:', NEW.stock, '|min:', NEW.stock_minimo),
         COALESCE(@usuario_activo, 1), NOW());
END$$
DELIMITER ;
```

Notas del nivel C:

- `@usuario_activo` es variable de sesión MySQL; si la request no la setea,
  el `COALESCE` cae a `1` (admin).
- Es un plan de contingencia al nivel A, o un complemento para operaciones
  fuera del framework (scripts directos a BD), que actualmente siguen sin
  auditoría.

## Verificación de referencia

Probar que la identidad se graba sin enviar `id_usuario` en el body:

```sql
SELECT id_usuario_carga, id_usuario_modificacion FROM PRODUCTO
WHERE codigo = '<tu codigo>';
```