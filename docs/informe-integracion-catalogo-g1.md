# Informe Técnico de Integración: Catálogo y Stock (G3 para G1 y G4)

**Fecha:** 24 de octubre de 2026  
**Autor:** Grupo 3 (Inventario, Productos, Proveedores y Compras)  
**Destinatarios:** Grupo 1 (Autenticación, Clientes, Reportes y Catálogo) y Grupo 4 (Ventas y POS)  
**Rama:** `develop`  

---

## 1. Propósito

Este informe técnico documenta la resolución integral de las dudas planteadas por el líder de Grupo 1 para el consumo de datos de productos y existencias de stock, necesarios para la pantalla de **Catálogo** y los cálculos analíticos de **Reportes** (como el ranking comercial y alcance de clientes por producto), así como la estandarización arquitectónica del modelo de Login y la eliminación de la carpeta histórica `app/Models/`.

---

## 2. Esquema de Base de Datos y Resolución de Inconsistencias

### 2.1. Tablas Singulares canónicas vs. Tabla Plural histórica
En las instancias locales de base de datos (`sistema_gestion` / `distribuidorapyb`):
- **Esquema oficial vigente:** Los nombres de tabla canónicos del ERP son en **singular**:  
  `producto`, `categoria`, `marca`, `unidad_medida`, `ubicacion`, `movimiento_stock`.
- **Tabla plural `productos`:** Es un residuo histórico de sprints iniciales que se encuentra en desuso y vacía. No debe ser tomada como fuente de datos.

### 2.2. Migración y Siembra Automática No Destructiva
Para garantizar que cualquier miembro del equipo cuente con el catálogo base sin ejecutar scripts manuales, en `develop` se encuentra incorporada la migración:  
`app/Modules/Productos/database/migrations/2026_10_24_000001_seed_g3_catalog_for_integration.php`

**Comportamiento de la migración:**
1. **Preservación de datos:** Si la base local ya cuenta con productos cargados previamente, los respeta íntegramente.
2. **Siembra del catálogo compartido:** Si no existen los productos de prueba de integración (`P001` a `P006`), los inserta asegurando las claves primarias numéricas del 1 al 6.
3. **Resolución dinámica de claves foráneas:** Mapea las categorías (`Bebidas`, `Panadería`, `Almacén`) y marcas (`Coca-Cola`, `Bimbo`, `Playadito`, `Natura`, `Gallo`, `Matarazzo`) buscando por nombre, evitando errores por IDs desalineados o violaciones de integridad referencial.
4. **Sincronización de Identidad:** Sincroniza la tabla de usuarios de G1 (`usuarios`) con la tabla referencial histórica de auditoría de G3 (`usuario`).

**Comando de ejecución:**
```bash
php artisan migrate
```

---

## 3. Rama e Instancia Vigente

- **Rama oficial:** `develop` (repositorio principal `rapiriz/ERP-Juan23`).
- **Instancia / URL base:** Entorno local unificado `http://127.0.0.1:8000` (o `localhost:8000`).

---

## 4. Endpoints y Autenticación

Los servicios expuestos por G3 son **APIs internas de consulta directa** para interoperabilidad modular.

> **Autenticación requerida:** **Ninguna.**  
> Los endpoints de lectura del catálogo y existencias de stock no exigen tokens `Bearer` ni cabeceras de autorización. Pueden ser consumidos tanto desde controladores PHP de Laravel (`Http::get()`) como directamente desde JavaScript en el navegador (`fetch()`).

| Método | Ruta | Propósito |
| :--- | :--- | :--- |
| `GET` | `/api/v1/productos` | Listado completo de productos con filtros opcionales. |
| `GET` | `/api/v1/productos/{id}` | Ficha técnica y detalle de un producto específico. |
| `GET` | `/api/v1/stock` | Monitoreo de existencias, disponible y alertas de reposición. |

---

## 5. Diccionario de Datos y Mapeo de Atributos

### 5.1. Identificadores: Exposición dual (`id` e `id_producto`)
Para eliminar cualquier inconsistencia de nomenclatura entre grupos:
- El JSON retornado por la API expone **ambas propiedades**:
  ```json
  "id": 1,
  "id_producto": 1
  ```
- Ambos valores son enteros idénticos. Los consumidores de G1 y G4 pueden leer `$item['id']` o `$item['id_producto']` indistintamente.

### 5.2. Catálogo Compartido con Ventas (G4)
Los productos sembrados coinciden 1 a 1 con los mocks utilizados por el Punto de Venta (POS) de Grupo 4:

| `id` / `id_producto` | `codigo` | Nombre | Presentación | Stock Inicial |
| :---: | :---: | :--- | :--- | :---: |
| **1** | `P001` | Coca-Cola 1.5L | Pack x 6 u. | 48 |
| **2** | `P002` | Pan lactal grande | Bolsa x 1 u. | 30 |
| **3** | `P003` | Yerba Mate 1kg (Playadito) | Fardo x 10 u. | 25 |
| **4** | `P004` | Aceite girasol 900ml (Natura) | Caja x 12 u. | 60 |
| **5** | `P005` | Arroz 1kg (Gallo) | Fardo x 10 u. | 80 |
| **6** | `P006` | Fideos 500g (Matarazzo) | Caja x 20 u. | 100 |

### 5.3. Política de Precios: Unitario, Mayorista y Minorista
- **En Base de Datos:** Como distribuidora mayorista, la tabla física `producto` contiene una única columna monetaria de lista: `precio_unitario DECIMAL(12,2)` en pesos argentinos (ARS).
- **En la API:** El punto de venta de G4 contempla selector de listas ("mayorista" y "minorista"). Para brindar compatibilidad total sin obligar a G4 ni a G1 a implementar fallbacks manuales, la API de G3 expone tres campos numéricos:
  - `"precio_unitario"`: Precio base del catálogo.
  - `"precio_mayorista"`: Mapeado al precio unitario.
  - `"precio_minorista"`: Mapeado al precio unitario.

### 5.4. Unidades de Medida y Formato Bulto
G3 soporta tanto venta suelta como bultos cerrados de logística:
- `"formato_bulto"`: Tipo de empaque (`"Pack"`, `"Bolsa"`, `"Fardo"`, `"Caja"`, `"Unidad"`).
- `"unidades_por_bulto"`: Factor entero multiplicador (ej. `6` para botellas por pack).
- `"unidad_medida"`: Etiqueta descriptiva (ej. `"Pack x 6"`).
- `"presentacion_texto"`: Formato legible para interfaz (ej. `"Pack x 6 u."`).

### 5.5. Disponibilidad y Estados Operativos
- `"estado"`: `"activo"` o `"inactivo"` (borrado lógico).
- `"stock"`: Existencia física total en depósito.
- `"stock_disponible"`: Stock neto apto para venta.
- `"stock_minimo"`: Umbral de reposición.
- `"estado_alerta"`: Semáforo visual de stock:
  - `"normal"`: Stock disponible mayor al stock mínimo.
  - `"bajo"`: Stock disponible menor o igual al mínimo, pero mayor a cero.
  - `"critico"`: Stock disponible en cero o negativo.

---

## 6. Paginación y Filtros

- **Listado completo por defecto:** `GET /api/v1/productos` devuelve todos los registros dentro de `"data": [...]`. Esto garantiza que los selectores desplegables de clientes por producto y los reportes analíticos de G1 puedan operar sin lidiar con páginas cortadas o URLs externas.
- **Filtros opcionales por Query String:**
  - `?estado=activo|inactivo` (por defecto lista activos).
  - `?buscar={texto}` (búsqueda parcial por código o nombre/descripción).
  - `?id_categoria={id}` (filtro por ID de categoría).
  - `?id_marca={id}` (filtro por ID de marca).

---

## 7. Ejemplos de Payload JSON

### 7.1. Detalle de Producto (`GET /api/v1/productos/1`)
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "id_producto": 1,
    "codigo": "P001",
    "nombre": "Coca-Cola 1.5L",
    "descripcion": "Gaseosa Coca-Cola botella 1.5L",
    "precio_unitario": 2500,
    "precio_mayorista": 2500,
    "precio_minorista": 2500,
    "stock": 48,
    "stock_disponible": 48,
    "stock_minimo": 10,
    "estado": "activo",
    "estado_alerta": "normal",
    "id_categoria": 1,
    "categoria_nombre": "Bebidas",
    "id_marca": 1,
    "marca_nombre": "Coca-Cola",
    "id_ubicacion": null,
    "ubicacion_nombre": "Sin asignar",
    "unidad_medida": "Pack x 6",
    "equivalencia_unidad": 6,
    "formato_bulto": "Pack",
    "unidades_por_bulto": 6,
    "presentacion_texto": "Pack x 6 u.",
    "fecha_alta": "2026-09-01 00:00:00",
    "fecha_modificacion": null,
    "fecha_desactivacion": null,
    "id_usuario_carga": 1,
    "usuario_carga_nombre": "Administrador"
  }
}
```

### 7.2. Consulta de Stock (`GET /api/v1/stock`)
```json
{
  "status": "success",
  "data": [
    {
      "id_producto": 1,
      "codigo": "P001",
      "nombre": "Coca-Cola 1.5L",
      "stock_disponible": 48,
      "stock_minimo": 10,
      "estado_alerta": "normal",
      "formato_bulto": "Pack",
      "unidades_por_bulto": 6
    },
    {
      "id_producto": 2,
      "codigo": "P002",
      "nombre": "Pan lactal grande",
      "stock_disponible": 30,
      "stock_minimo": 8,
      "estado_alerta": "normal",
      "formato_bulto": "Bolsa",
      "unidades_por_bulto": 1
    }
  ]
}
```

---

## 8. Estandarización de Login y Modelos (`app/Models/`)

En cumplimiento de la solicitud de unificación modular:
1. **Eliminación de `app/Models/`:** La carpeta fue eliminada en su totalidad del proyecto.
2. **Modelo de Login oficial:** Todos los controladores y servicios de G3 consumen ahora:
   ```php
   use App\Modules\Login\Models\User;
   ```
3. **Auditoría modular:** El modelo `LogAuditoria` fue trasladado a su módulo natural:
   ```php
   use App\Modules\Productos\Models\LogAuditoria;
   ```
4. **Aislamiento:** Cero modificaciones sobre el código fuente de G1, G2 o G4.
