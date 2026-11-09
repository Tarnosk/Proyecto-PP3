# Colecciones de Postman — ERP Juan23 (Distribuidora PyB)

Esta carpeta reúne las colecciones oficiales de **Postman**, variables de entorno y guías de ejecución automatizada para probar tanto los módulos individuales como la integración integral del ERP.

---

## Índice de Colecciones Disponibles

| Archivo / Guía | Ámbito / Módulo | Descripción | Requiere Auth / Token |
| :--- | :--- | :--- | :--- |
| **[`G3.postman_collection.json`](./G3.postman_collection.json)** | **Grupo 3 (Productos & Stock)** | Colección oficial de las **16 peticiones de integración** que consumen G1, G2 y G4. Incluye tests automáticos, verificación de precios netos sin IVA, encapsulamiento de costos y pruebas de stock sin modificar existencias netas. | No (middleware `IdentidadUsuario`) |
| **[`GUIA_TESTEO_RECARGOS_Y_APIS_G3.md`](./GUIA_TESTEO_RECARGOS_Y_APIS_G3.md)** | **Guía de Testeo G3** | Instructivo detallado paso a paso para correr `G3.postman_collection.json` con el *Collection Runner*. | — |
| **[`ERP-Juan23-APIs.postman_collection.json`](./ERP-Juan23-APIs.postman_collection.json)** | **Suite Integral ERP (G1, G2, G3, G4)** | Colección unificada completa que abarca todos los módulos del sistema (Autenticación, Clientes, Catálogo, Caja, Conciliación, Rendiciones, Stock, Proveedores, Compras, Ventas, Promociones y Cuenta Corriente). | Sí (según módulo) |
| **[`CuentaCorriente-Cobros.postman_collection.json`](./CuentaCorriente-Cobros.postman_collection.json)** | **Cta. Cte. & Cobranzas (G2 / G4)** | Pruebas de aplicación de cobros de rendiciones sobre saldos de cuentas corrientes. | Opcional |
| **[`G1.postman_collection.json`](./G1.postman_collection.json)** | **Grupo 1 (Clientes & Auth)** | Colección de 16 operaciones verificadas de G1 (login Bearer, consultas GET y escrituras de Clientes/Reclamos). | Sí (token Bearer automático) |
| **[`G1.local.postman_environment.json`](./G1.local.postman_environment.json)** | **Entorno Local G1** | Variables de entorno para ejecutar `G1.postman_collection.json`. | — |

---

## 1. Guía Rápida: Colección Oficial Grupo 3 (`G3.postman_collection.json`)

Para verificar las APIs que el Grupo 3 expone a los demás módulos:
1. Asegurarse de tener la base de datos levantada y el servidor Laravel corriendo (`php artisan serve`).
2. En Postman, hacer clic en **Import** y seleccionar [`G3.postman_collection.json`](./G3.postman_collection.json).
3. La variable `base_url` por defecto es `http://127.0.0.1:8000`.
4. No requiere login previo: las rutas usan `IdentidadUsuario` que asigna usuario de sistema si no se envían credenciales.
5. Ejecutar la colección completa con el **Collection Runner** (tres puntos `...` > *Run collection*). Todas las 16 pruebas deben dar **Pass (verde)**.
6. Para más detalles, consultar la [Guía de Testeo G3](./GUIA_TESTEO_RECARGOS_Y_APIS_G3.md).

---

## 2. Colección Integral del ERP (`ERP-Juan23-APIs.postman_collection.json`)

Contiene la totalidad de los endpoints expuestos en el backend Laravel 12:
- **Autenticación y Roles (Login/Logout, tokens Sanctum y sesiones web)**.
- **Clientes y Reclamos (G1)**.
- **Caja Diaria, Cierres, Conciliación Bancaria y Rendiciones (G2)**.
- **Catálogo, Stock, Kardex, Lotes, Conteos, Proveedores y Compras (G3)**.
- **Ventas, Preventa, Promociones y Cuenta Corriente (G4)**.

---

## 3. Pruebas de las APIs de G1 en Postman (`G1.postman_collection.json`)

Esta colección deja preparado el encabezado Bearer para las solicitudes privadas de G1. No contiene contraseñas ni tokens reales, no genera tokens permanentes y no cambia la autenticación del servidor.

### Preparación inicial
1. En Postman, importar:
   - `G1.postman_collection.json`.
   - `G1.local.postman_environment.json`.
2. Seleccionar el entorno **G1 local** en el selector de entornos.
3. En ese entorno, completar `usuario` y `password` con una cuenta propia del ERP (usar cuenta administrativa para probar las 16 operaciones).
4. Abrir **01 - Obtener Bearer (POST login)** y pulsar **Send**. El script guardará el token en el entorno y lo propagará automáticamente a las solicitudes privadas.
5. Ejecutar las carpetas **02 - Consultas GET** y **03 - Escrituras REALES** según sea necesario.
6. Al finalizar, ejecutar **04 - Cerrar sesión y limpiar Bearer** para revocar el token.
