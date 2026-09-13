# INFORME DE ANÁLISIS Y DEVOLUCIÓN DE DISEÑO DE BASE DE DATOS — GRUPO 3
**Proyecto:** ERP Distribuidora — Grupo 3 (Productos, Stock, Proveedores, Compras y Pedidos de Compra)  
**Fecha:** 10 de Septiembre de 2026  
**Documentos analizados:**
- `Revision/Anotaciones Base de Datos.txt` (Observaciones y preguntas del docente)
- `Revision/Diagrama Actual Grupo 3.sql` (Script de base de datos específico del Grupo 3)
- `Revision/Diagrama Actual del Proyecto General.sql` (Modelo consolidado entre los 4 grupos)
- `Revision/Diagrama Antiguo.sql` (Modelo histórico integral)
- `Revision/Endpoints pedidos.xlsx` (Contratos de interfaz y consumo entre módulos/grupos)
- `Revision/Historias de Usuario.pdf` (Especificación formal: P01-P08, S01-S16, PV01-PV08, C01-C09, PC01-PC07)
- `Revision/Entrevista al Cliente (Parcial).docx` (Relevamiento operativo real de la distribuidora)

---

## 1. INTRODUCCIÓN Y CONTEXTO DEL PROBLEMA

El Grupo 3 tiene a su cargo el núcleo transaccional y operativo de la distribuidora: la administración de artículos, la valorización comercial, el inventario físico, los proveedores, las compras y los pedidos de reposición.

Inicialmente se concibió un diagrama general compartido para todo el sistema, pero debido a la complejidad intrínseca de la gestión de depósito (fraccionamiento en unidades de medida, lotes con vencimientos, conteos físicos, recepciones parciales de compras, órdenes de reposición), el modelo general resultaba incompleto para este grupo. Por tal motivo, se elaboró un **diagrama específico y desacoplado para el Grupo 3**.

Frente a este modelo, el docente realizó cuatro observaciones puntuales de diseño centradas en normalización, redundancia y responsabilidades de las tablas.

A continuación se detalla la evaluación técnica de cada una de estas observaciones, el diagnóstico del desacoplamiento con respecto a los demás grupos y la propuesta de rediseño.

---

## 2. ANÁLISIS DETALLADO DE LAS OBSERVACIONES DEL DOCENTE

### 2.1. Tabla `HISTORIAL_PRECIO`: Atributos `precio_anterior` y `precio_nuevo`

> **Observación del Docente:**  
> *“¿Es necesario que exista `precio_anterior` y `precio_nuevo`? ¿No bastaría con un solo atributo `precio`? ¿No podemos tener inconsistencias manteniendo esos dos atributos?”*

#### Diagnóstico Técnico:
- **La crítica del docente es conceptualmente correcta:** En el diseño de bases de datos temporales e históricas, almacenar el estado previo y el posterior en la misma fila genera una **redundancia transitiva** y un riesgo severo de inconsistencia en la serie temporal:
  - Si el registro 1 indica que el precio cambió de \$100 a \$120, pero el registro 2 indica que el precio cambió de \$130 a \$150, la cadena de auditoría queda corrompida.
  - Exige validaciones adicionales por software o triggers para asegurar que `precio_anterior` en el instante $t$ sea idéntico a `precio_nuevo` en el instante $t-1$.
- **Origen del atributo en el modelo actual:** La Historia de Usuario **P08** (*Consultar Historial de Precios*) establece en sus criterios de aceptación: *"Debe mostrar el precio anterior y el nuevo precio"*. Además, **P07** (*Aumentos Masivos*) solicita registrar el porcentaje y regla de redondeo. Quien diseñó la tabla tradujo literalmente el requerimiento visual de la pantalla a columnas de la base de datos.
- **Resolución Arquitectónica:**
  - En la base de datos se debe persistir únicamente el **precio resultante** (`precio` o `precio_vigente`), la `fecha_cambio`, `id_usuario`, `origen` y `motivo`.
  - El precio anterior no debe guardarse físicamente: se deduce dinámicamente mediante consultas SQL estándar utilizando funciones de ventana:
    ```sql
    SELECT 
        id_producto,
        LAG(precio) OVER (PARTITION BY id_producto ORDER BY fecha_cambio, id_historial) AS precio_anterior,
        precio AS precio_nuevo,
        fecha_cambio,
        id_usuario
    FROM HISTORIAL_PRECIO;
    ```
  - Esto garantiza 100% de consistencia matemática, normalización estricta y cumplimiento total de la vista esperada en la API y frontend.

---

### 2.2. Tabla `MOVIMIENTO_STOCK`: Justificación ante Compras y Ventas

> **Observación del Docente:**  
> *“¿Los cambios vienen de la venta de productos y compras de productos? ¿O hay algún movimiento más? Si los movimientos de stock fueran dados exclusivamente de las compras y las ventas, la información la tenés en sus respectivas tablas, no tendría sentido duplicarlas acá.”*

#### Justificación y Defensa Técnica (Por qué la tabla es indispensable):
La pregunta del profesor plantea una hipótesis condicional: *si únicamente hubiera compras y ventas, la tabla duplicaría información*. Sin embargo, en el negocio real de la distribuidora y en las especificaciones del proyecto **existen múltiples movimientos adicionales que no provienen de compras ni ventas:**

1. **Devoluciones de Reparto y de Clientes (HU S05 y Entrevista Operativa):**
   - En la entrevista con Diego (propietario) se describe: *"Hay veces que el negocio ha estado cerrado, y simplemente le digo que siga el reparto. Y cuando se termina ese reparto, vuelve la mercadería al depósito y yo le hago una nota de crédito... ese menos hace que el stock vuelva nuevamente al depósito"*. 
   - Estas devoluciones físicas requieren asentar el reingreso al inventario independientemente del comprobante de facturación.
2. **Ajustes Manuales de Inventario por Roturas, Mermas o Descartes (HU S06):**
   - En una distribuidora de bebidas y alimentos ocurren periódicamente roturas de botellas en depósito, mercadería vencida que debe darse de baja y pérdidas. No son ventas ni compras; son egresos por ajuste de stock.
3. **Ajustes por Conteo Físico / Toma de Inventario (HU S13):**
   - Las sesiones periódicas de conteo en pasillos y estanterías detectan diferencias (sobrantes o faltantes) respecto a los valores teóricos del sistema. La corrección se efectúa mediante un movimiento de ajuste.
4. **Principio Contable de Kárdex / Ledger:**
   - Calcular el stock disponible en tiempo real sumando todas las compras históricas y restando todas las ventas históricas es inviable operacionalmente: la complejidad temporal de la consulta crece a $O(N)$ con cada línea de factura o remito emitida, degradando el rendimiento del sistema en poco tiempo.
   - Todo software de gestión empresarial (ERP) utiliza un libro diario de movimientos de almacén (`MOVIMIENTO_STOCK`) que funciona como kárdex de auditoría inmutable.
- **Corrección requerida en la tabla:** En el diagrama actual se incluyeron columnas como `id_venta` e `id_entrega`. Si el Grupo 3 está desacoplado de las ventas de otros grupos, estas columnas rígidas deben reemplazarse por atributos genéricos de trazabilidad: `tipo_comprobante` y `numero_comprobante` (o `referencia_origen`).

---

### 2.3. Tabla `STOCK` vs. Atributos en la Tabla `PRODUCTO`

> **Observación del Docente:**  
> *“¿Los datos no podrían estar dentro de la propia tabla `PRODUCTO`?”*

#### Diagnóstico Técnico:
- En `Diagrama Actual Grupo 3.sql`, la tabla `STOCK` tiene:
  - `id_stock` (PK)
  - `id_producto` (FK, `UNIQUE` → relación estrictamente 1 a 1)
  - `stock_disponible`
  - `stock_minimo`
  - `estado_alerta` (CHECK: 'normal', 'bajo', 'critico')
  - `dias_alerta_vencimiento`
- **La crítica del docente es totalmente acertada para el alcance actual:**
  - Una tabla separada con cardinalidad 1:1 solo tiene justificación si se contempla **stock multidepósito** (por ejemplo, Depósito Central, Sucursal Norte, Camión 1, Camión 2), en cuyo caso la clave primaria sería compuesta `(id_producto, id_deposito)`.
  - Al gestionar un único depósito físico centralizado, tener una tabla separada obliga a realizar un `INNER JOIN` innecesario en cada búsqueda de catálogo, listado de productos y control de precios.
  - Además, `estado_alerta` es un dato calculado derivado (`stock_disponible <= stock_minimo`), por lo que almacenarlo físicamente en una columna introduce una redundancia pasible de desincronización.
- **Resolución Arquitectónica:**
  - Trasladar `stock_disponible`, `stock_minimo` y `dias_alerta_vencimiento` directamente a la tabla `PRODUCTO`.
  - Eliminar la tabla satélite `STOCK`.
  - Calcular el estado de alerta mediante vistas o lógica de aplicación.

---

### 2.4. Tabla Centralizada de Auditoría

> **Observación del Docente:**  
> *“¿No habría que hacer una tabla aparte para registrar los cambios hechos por usuario en todas las tablas?”*

#### Diagnóstico Técnico:
- En el diagrama actual existen campos de auditoría dispersos en casi todas las tablas:
  - En `PRODUCTO`: `id_usuario_carga`, `id_usuario_modificacion`, `fecha_alta`, `fecha_modificacion`, `fecha_desactivacion`.
  - En `PROVEEDOR`: `id_usuario_carga`, `id_usuario_modificacion`, `fecha_alta`, etc.
  - Tablas dedicadas ad-hoc para historiales puntuales: `HISTORIAL_PLAZO_ENTREGA`.
- **Resolución Arquitectónica:**
  - Se debe distinguir entre:
    1. **Trazabilidad operativa básica:** Mantener en cada tabla principal `fecha_creacion`, `fecha_modificacion` y `id_usuario`.
    2. **Log de Auditoría Transaccional Unificado:** Implementar una tabla centralizada `LOG_AUDITORIA` para registrar cualquier mutación de datos sensible:
       ```sql
       CREATE TABLE LOG_AUDITORIA (
         id_auditoria      INT AUTO_INCREMENT PRIMARY KEY,
         nombre_tabla      VARCHAR(50) NOT NULL,
         id_registro       INT NOT NULL,
         accion            ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
         valores_anteriores JSON NULL,
         valores_nuevos    JSON NULL,
         id_usuario        INT NOT NULL,
         fecha_hora        DATETIME DEFAULT CURRENT_TIMESTAMP
       );
       ```
  - Con esta tabla se elimina la proliferación de tablas satélite como `HISTORIAL_PLAZO_ENTREGA`, se centraliza el rastreo de cambios y se satisface la solicitud del docente bajo un estándar profesional.

---

## 3. EVALUACIÓN DE LA DESCONEXIÓN DEL GRUPO 3 RESPECTO A LOS DEMÁS GRUPOS

El Grupo 3 optó por trabajar con un esquema propio desconectado. Tras contrastar el esquema de G3 con el esquema del Proyecto General y con la matriz de interfaces en `Endpoints pedidos.xlsx`, se concluye lo siguiente:

### 3.1. ¿Es correcto que el diagrama esté desconectado?
- **A nivel de Base de Datos (Persistencia): SÍ, ES CORRECTO.**  
  En una arquitectura orientada a servicios o desarrollo modular en equipo, el Grupo 3 representa el servicio de Catálogo, Inventario y Abastecimiento. No debe tener restricciones de clave foránea física (`FOREIGN KEY`) que apunten a tablas de Ventas (Grupo 4), Entregas (Grupo 2) o Clientes/Usuarios (Grupo 1), ya que ello crearía un acoplamiento monolítico que impediría desarrollar, probar y desplegar de manera independiente.
- **A nivel de Integración del Sistema: LA CONEXIÓN SE DA POR API REST.**  
  La comunicación entre módulos no debe ser por base de datos compartida, sino a través de los contratos formalizados en `Endpoints pedidos.xlsx`:
  - **G4 (Ventas) solicita a G3:** `GET /productos/{id}`, `GET /stock/{producto_id}`, `PATCH /stock/{producto_id}/descontar`.
  - **G1 (Administración) solicita a G3:** `GET /productos`, `GET /stock/bajo`, `GET /categorias`, `GET /marcas`.
  - **G3 solicita a G1 (Seguridad):** `POST /auth/login` y `GET /usuarios/{id}/permisos`.

### 3.2. Discrepancias Críticas detectadas entre el Diagrama de G3 y el General

1. **Esquema de Precios en `PRODUCTO`:**
   - En el `Diagrama Actual del Proyecto General.sql`, la tabla `PRODUCTO` cuenta con dos precios: `precioMay` (mayorista) y `precioMin` (minorista).
   - En la entrevista al cliente se corrobora que la distribuidora vende a ambos canales (supermercados/cooperativas mayoristas y comercios minoristas).
   - En el `Diagrama Actual Grupo 3.sql` solo se definió una columna `precio_unitario`. **Es necesario incorporar `precio_mayorista` y `precio_minorista` en G3 para estar alineados con el negocio.**
2. **Nombres de Entidades Externas:**
   - G3 comentaba referencias a `LOGIN(id_usuario)`. En el general la tabla se denomina `USUARIO(id_usuario)`.
3. **Flujo de Compras:**
   - En el diagrama general, el módulo de compras está extremadamente simplificado (`COMPRA` y `DETALLE_COMPRA`).
   - El diseño del Grupo 3 (`ORDEN_COMPRA` → `COMPRA` → `RECEPCION` con lotes → `PAGO_PROVEEDOR`) es **sustancialmente superior y más fiel a la realidad operativa**, cumpliendo con las historias de usuario formales (C01 a C09 y PC01 a PC07). Este flujo ampliado de G3 debe conservarse.

---

## 4. CONCLUSIÓN Y DICTAMEN FINAL

¿El diagrama planteado para el Grupo 3 es correcto?

> **Dictamen:**  
> **El diagrama del Grupo 3 es funcionalmente muy completo en el dominio de compras, lotes e inventario, pero requiere correcciones formales de diseño de base de datos para responder a las observaciones del profesor y sincronizarse con la arquitectura general del proyecto.**

### Resumen de Cambios a Implementar:
1. **Unificar `STOCK` dentro de `PRODUCTO`:** Mover `stock_disponible`, `stock_minimo` y `dias_alerta_vencimiento` a `PRODUCTO`. Eliminar la tabla `STOCK`.
2. **Normalizar `HISTORIAL_PRECIO`:** Reemplazar `precio_anterior` y `precio_nuevo` por un único campo de precio resultante, calculando el precio previo por software o función de ventana.
3. **Mantener y fundamentar `MOVIMIENTO_STOCK`:** Conservar la tabla como libro kárdex justificando los movimientos no provenientes de compra/venta (devoluciones de reparto S05, mermas/roturas S06, ajustes de conteo S13). Reemplazar `id_venta` e `id_entrega` por columnas genéricas de referencia de comprobante.
4. **Implementar `LOG_AUDITORIA`:** Crear una tabla centralizada de auditoría que absorba historiales menores (como `HISTORIAL_PLAZO_ENTREGA`) y registre cambios de usuarios.
5. **Incorporar doble lista de precios (`precio_mayorista`, `precio_minorista`) en `PRODUCTO`:** Para cumplir con el modelo de negocio mayorista/minorista de la distribuidora.
