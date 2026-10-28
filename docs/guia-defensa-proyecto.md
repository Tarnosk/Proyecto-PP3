# Guía de Defensa y Presentación del Proyecto (Pitch Guide) — ERP Juan23

Esta guía está diseñada como un guion estratégico para el equipo de desarrollo durante la defensa oral y técnica del proyecto frente a los profesores o evaluadores.

---

## 1. Estructura de la Presentación (Tiempo sugerido: 12 a 15 minutos)

```text
[00:00 - 02:00] 1. Introducción y Problema de Negocio (Distribuidora PyB)
[02:00 - 05:00] 2. Arquitectura de Software y Decisiones Técnicas Clave
[05:00 - 11:00] 3. Demostración en Vivo (Live Demo - Happy Path)
[11:00 - 13:00] 4. Calidad, Testing Automatizado y Auditoría
[13:00 - 15:00] 5. Conclusiones y Preguntas del Tribunal
```

---

## 2. Puntos Clave a Exponer por Sección

### Sección 1: Introducción y Contexto (2 min)
- **El Negocio:** La Distribuidora PyB comercializa productos de consumo masivo con alta rotación.
- **La Problemática Original:** Desacople entre ventas e inventario, pérdida de trazabilidad en lotes vencidos, discrepancias entre el stock teórico y el físico, y demoras en el reabastecimiento a proveedores.
- **La Solución:** Un ERP web modular desarrollado en Laravel 12 con MySQL, que centraliza catálogo, stock con Kardex inmutable, auditoría de precios y circuito completo de compras.

### Sección 2: Decisiones Técnicas de Arquitectura (3 min)
- **Monolito Modular:** Se estructuró bajo `app/Modules/` (separando Productos, Stock, Proveedores, Compras, Pedidos de Compra) para permitir desarrollo desacoplado y escalable sin la sobrecarga de latencia y despliegue de microservicios distribuidos.
- **Kardex Inmutable:** Ningún movimiento de stock se actualiza ni se borra; toda entrada, salida, merma o venta genera un registro histórico inmutable con usuario, fecha, motivo y saldo resultante.
- **Integridad Transaccional:** Uso de `DB::transaction()` en todas las operaciones que tocan múltiples tablas (ej. recepción de compra que afecta inventario, lotes y comprobantes), garantizando atomicidad y previniendo inconsistencias por caídas o cortes de red.
- **Versionado y Contratos de API:** Exposición de endpoints bajo `/api/v1/` con colección Postman y OpenAPI 3.0 para consumo unificado entre grupos.

---

## 3. Demostración en Vivo sugerida (Happy Path)

Siga este orden exacto de pantallas para una demo fluida, coherente y sin riesgos:

```text
Paso 1: Catálogo e Inventario
  ➔ Abrir /inventario
  ➔ Mostrar la tabla con el semáforo de colores (Normal / Bajo / Sin Stock).
  ➔ Filtrar por categoría o por rango numérico.
  ➔ Demostrar el botón "Aumento Masivo": aplicar un 10% con regla de redondeo y mostrar cómo se actualiza el precio y se asienta en el historial.

Paso 2: Sugerencia Automática de Compra
  ➔ Ir a /pedidos-compra o Dashboard de Reposición.
  ➔ Mostrar cómo el sistema detecta qué productos cayeron por debajo del stock mínimo.
  ➔ Presionar "Generar Orden de Compra" desde la sugerencia (crea la orden en estado 'pendiente').

Paso 3: Máquina de Estados de la Orden de Compra
  ➔ Mostrar la línea de tiempo interactiva (stepper visual).
  ➔ Avanzar la orden a 'enviada'.
  ➔ Presionar "Recepcionar Mercadería": ingresar número de factura del proveedor, número de lote y fecha de vencimiento.
  ➔ Guardar: mostrar que la orden pasa a 'completada'.

Paso 4: Verificación del Impacto en Depósito
  ➔ Volver a /inventario: mostrar que el stock del producto subió automáticamente.
  ➔ Mostrar en la solapa de Kardex el registro de tipo 'recepcion' con fecha y usuario.
  ➔ Ir a /vencimientos: mostrar que la partida cargada aparece en el semáforo de lotes.

Paso 5: Auditoría y Conteo Físico a Ciegas (La funcionalidad estrella)
  ➔ Iniciar una sesión de conteo físico a ciegas.
  ➔ Relevar un producto con 2 unidades de diferencia respecto al sistema.
  ➔ Finalizar sesión: mostrar cómo el sistema calcula la discrepancia e imputa el ajuste compensatorio automáticamente.
```

---

## 4. Respuestas Preparadas para Preguntas Difíciles de Profesores

### P1: "¿Cómo evitan que dos operarios vendan o descuenten el mismo stock al mismo tiempo? (Race Conditions / Concurrencia)"
* **Respuesta:** *"Toda operación que afecta existencias se ejecuta dentro de un bloque `DB::transaction()` con bloqueo a nivel de fila (`select ... for update` o comprobación atómica del stock disponible). Además, si el stock resultante fuera negativo, el servicio dispara una excepción antes de confirmar la transacción, revirtiendo cualquier cambio."*

### P2: "¿Cómo garantizan la seguridad y la identidad si el login lo maneja otro grupo?"
* **Respuesta:** *"Implementamos el middleware [`IdentidadUsuario`](../app/Http/Middleware/IdentidadUsuario.php), que actúa como capa de abstracción. Inspecciona tokens Bearer de Grupo 1, tokens de sesión en base de datos o cabeceras seguras `X-User-Id`. Si el token es válido, inyecta la identidad en el ciclo de vida de la solicitud y alimenta la clase [`AuditoriaLogger`](../app/Support/AuditoriaLogger.php), de modo que cada acción queda grabada con el ID del operador real."*

### P3: "¿Por qué es obligatoria la asignación de lote en las devoluciones?"
* **Respuesta:** *"Por trazabilidad sanitaria y control de calidad. Si un cliente devuelve un producto con empaque dañado o vencimiento cercano, o si nosotros devolvemos mercadería fallada al proveedor, no podemos mezclarla con el stock general sin saber a qué partida pertenece. El sistema exige asociar el lote para asegurar el principio FEFO y evitar que mercadería defectuosa vuelva al circuito de venta."*

### P4: "¿Qué nivel de cobertura de pruebas tienen implementado?"
* **Respuesta:** *"Contamos con una suite canónica en PHPUnit con pruebas de Feature end-to-end (`UserStoriesFrontendTest`, `ConteoFisicoTest`, `HistorialPrecioProveedorTest`) que simulan peticiones HTTP reales contra la base de datos, validando no solo los casos exitosos (códigos 200 y 201), sino también los casos de borde y rechazos por datos inválidos (código 400)."*
