# Plan de Prueba de Verificación y Validación (V&V)

## 1. Introducción y Motivación
El presente documento define la estrategia de **Verificación y Validación (V&V)** para el sistema de gestión (ERP) de la distribuidora, focalizado en los Sprints 1 y 2 (Módulos de Productos, Stock y Proveedores). 
Siguiendo los principios de Ingeniería de Software, el objetivo es garantizar la calidad del sistema mediante dos preguntas fundamentales:
*   **Verificación:** *¿Construimos correctamente el producto?* (Asegurar que el código cumpla con las especificaciones técnicas y los criterios de aceptación documentados).
*   **Validación:** *¿Construimos el producto correcto?* (Comprobar que el software realmente satisface las necesidades de los vendedores, encargados de depósito y administradores).

### Conceptos Clave
*   **Defecto:** Error presente en el código fuente (ej. un error lógico en la fórmula de equivalencias de stock).
*   **Fallo:** Comportamiento incorrecto manifestado durante la ejecución del programa (ej. el sistema permite vender un producto sin stock).

---

## 2. Niveles y Estrategias de Prueba

Las pruebas se ejecutarán de forma escalonada atravesando los siguientes niveles:

1.  **Pruebas de Unidad:** Evaluación de componentes individuales. Se verificará la lógica de funciones críticas, como el cálculo de los saldos pendientes (C08) o el cálculo de los nuevos precios tras un aumento masivo (P07).
2.  **Pruebas de Integración:** Validación de la interacción entre módulos. Por ejemplo, comprobar que el "Registro de Ingreso de Mercadería" (S03) actualice correctamente la "Consulta de Stock Disponible" (S01).
3.  **Pruebas de Sistema:** Prueba del ERP completo simulando el flujo de trabajo real.
4.  **Pruebas de Aceptación:** Validación final enfocada en el usuario de la distribuidora para certificar la entrega del producto.

---

## 3. Técnicas de Prueba a Utilizar

*   **Técnicas de Caja Blanca (Estructurales):** 
    *   Centradas en las rutas lógicas y la cobertura de código. 
    *   *Aplicación:* Se usarán para probar procesos algorítmicos complejos del backend, como la actualización automática de stock tras una venta (S04) asegurando que no se rompan las reglas lógicas (ej. impedir stock negativo) y las conversiones de unidades y equivalencias (S08).
*   **Técnicas de Caja Negra (Funcionales):**
    *   Centradas en validar entradas y salidas según los requisitos, sin observar la estructura interna.
    *   *Aplicación:* Interfaces de usuario, validaciones de formularios (ej. Registrar Proveedor PV01, Registrar Producto P01) y correcto funcionamiento de filtros de búsqueda.

---

## 4. Automatización y Herramientas

Para lograr un ahorro de tiempo y garantizar la consistencia en pruebas repetitivas (pruebas de regresión), se propone el uso de herramientas de automatización:
*   **Backend (Lógica):** Uso de *JUnit* (o *PyTest* dependiendo del lenguaje del backend) para asegurar la persistencia de las reglas de negocio (ej. validación de CUIT, cálculos de precio).
*   **Frontend (UI):** Uso de scripts de *Selenium* para automatizar los flujos más largos, como el proceso de generación de una Orden de Compra completa (PC01).

---

## 5. Validación Alfa y Beta

*   **Validación Alfa (Interna):** Se realizará de forma iterativa por el equipo de desarrollo en un entorno de pruebas controlado. Se asignarán los roles correspondientes para estas sesiones: *Analista* (diseña el caso), *Ejecutor* (realiza la prueba), y *Registrador* (toma nota de los resultados y métricas).
*   **Validación Beta (Externa):** Se liberará una versión funcional (MVP) de los Sprints 1 y 2 en un entorno *staging* para que un grupo seleccionado de usuarios reales (empleados de la distribuidora) operen con el sistema antes del despliegue final.

---

## 6. Mini Plan de Pruebas (Diseño de Casos Guiados)

Atendiendo a la técnica de diseño de pruebas, se detallan a continuación **3 casos de prueba** sobre el **Módulo de Productos (Registrar y Aumentos Masivos)**:

| ID | Tipo de Prueba | Descripción | Datos de Entrada | Salida Esperada | Resultado |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **CP-01** | **Caja Negra** | Registrar un producto nuevo exitosamente. (P01) | - Código: `PROD-001`<br>- Desc: `Yerba 1Kg`<br>- Cat: `Almacén`<br>- Marca: `Playadito`<br>- Precio: `$2500` | El sistema guarda el producto, registra el usuario actual, la fecha de alta, y muestra un mensaje de éxito. El producto aparece activo. | *Pendiente de ejecución* |
| **CP-02** | **Caja Negra** | Intentar registrar un código duplicado. (P01) | - Código: `PROD-001` *(existente)*<br>- Resto de los datos válidos. | El sistema impide el registro. Se emite un mensaje de error claro: *"El código ingresado ya existe en el sistema"*. | *Pendiente de ejecución* |
| **CP-03** | **Caja Blanca** | Aplicar aumento masivo por Categoría. (P07) | - Categoría objetivo: `Almacén`<br>- Porcentaje de aumento: `10%` | El código recorre lógicamente solo los productos de "Almacén". Un producto de $1000 pasa a valer $1100. Se crea un registro en el "Historial de Precios" para cada ítem afectado. | *Pendiente de ejecución* |
