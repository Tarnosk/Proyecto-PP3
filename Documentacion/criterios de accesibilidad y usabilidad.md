# Criterios de Accesibilidad y Usabilidad

Este documento detalla los criterios de accesibilidad y usabilidad orientados a las funcionalidades correspondientes a los **Sprints 1 y 2** del sistema de gestión (ERP), basándose en las historias de usuario de los módulos de Productos, Stock y Proveedores.

---

## 1. Criterios Transversales (Generales)

Estos criterios aplican a todas las interfaces involucradas en las funcionalidades de los Sprints 1 y 2.

### 1.1 Accesibilidad (Basado en WCAG 2.1)
*   **Navegación por teclado:** Todas las acciones, formularios y vistas de consulta deben ser navegables e interactuables utilizando únicamente el teclado (teclas `Tab`, `Enter`, `Espacio`, flechas).
*   **Foco visible:** El elemento activo (botón, campo de texto, fila de tabla) debe tener un indicador visual de foco (focus ring) claramente visible en todo momento.
*   **Contraste de color:** Los textos, íconos y botones deben tener un ratio de contraste mínimo de 4.5:1 con respecto a su fondo, garantizando la legibilidad para usuarios con visión reducida.
*   **Lectores de pantalla (Screen Readers):** Los campos de formulario deben contar con etiquetas (`<label>`) y atributos ARIA correspondientes. Los mensajes de estado dinámicos (éxito al registrar, errores de validación) deben ser anunciados.
*   **Independencia del color:** No se debe usar el color como único medio visual para transmitir información, indicar una acción, o distinguir un elemento (ej. usar íconos de advertencia además del color rojo para alertas de stock o desactivaciones).

### 1.2 Usabilidad General
*   **Prevención de errores y validación en tiempo real:** Los formularios deben advertir errores antes de ser enviados (ej. "Formato de CUIT incorrecto") destacando el campo afectado.
*   **Feedback del sistema:** Toda acción de creación, modificación o eliminación (desactivación) debe devolver un mensaje claro informando el resultado de la operación ("Producto guardado con éxito").
*   **Identificación de campos obligatorios:** Los campos requeridos en todos los registros deben estar explícitamente marcados (por ejemplo, con un asterisco `*`).

---

## 2. Criterios Específicos por Módulo y Funcionalidad

### 2.1 Módulo Productos
*Funcionalidades: Administrar Categorías/Marcas, Registrar, Buscar, Modificar, Desactivar, Aplicar Aumentos Masivos, Consultar Historial.*

*   **Registrar / Modificar Productos (P01, P04) y Administrar Categorías/Marcas (P02, P03):**
    *   La validación de códigos duplicados (P01, P04) debe alertar de manera inmediata (al perder el foco del campo) sin esperar a enviar el formulario completo, reduciendo la frustración del usuario.
    *   Los selectores de categorías y marcas deben permitir búsqueda tipeada en caso de existir un volumen alto de opciones.
*   **Buscar Productos (P05):**
    *   El campo de búsqueda (por código/nombre) debe activarse al presionar `Enter`.
    *   Si es posible, incluir una función de autocompletado rápido dado que se usará ágilmente durante ventas.
*   **Desactivar Productos (P06):**
    *   Se debe incorporar una ventana modal de confirmación explícita (ej. *"¿Está seguro de que desea desactivar este producto? No podrá ser usado en nuevas operaciones"*).
*   **Aplicar Aumentos Masivos (P07):**
    *   **Claridad en la Vista Previa:** La pantalla de vista previa debe organizar la información en una tabla legible que compare claramente el "Precio Anterior" con el "Precio Nuevo", resaltando la variación. 
    *   Ofrecer botones claros para "Confirmar" o "Cancelar/Modificar" el porcentaje.
*   **Consultar Historial de Precios (P08):**
    *   La tabla de historial debe poder ordenarse fácilmente de forma cronológica (ascendente y descendente) haciendo clic en los encabezados de las columnas.

### 2.2 Módulo Stock
*Funcionalidades: Consulta (Disponible y Ventas), Ingreso de Mercadería, Actualización por Venta, Registro de Devoluciones, Ajustes Manuales, Alertas, Gestión de Unidades/Equivalencias.*

*   **Consultas de Stock (S01, S02):**
    *   La cantidad de stock disponible debe tener prominencia visual en la pantalla (mayor tamaño o peso de tipografía).
    *   En búsquedas de disponibilidad rápida, los resultados deben cargar sin demoras, priorizando la legibilidad.
*   **Registro de Movimientos (S03 Ingresos, S05 Devoluciones, S06 Ajustes):**
    *   Los campos de cantidad deben estar restringidos para evitar la introducción de valores alfabéticos o símbolos incorrectos.
    *   El campo "Motivo" en ajustes y devoluciones debe ser visible y claro sobre el tipo de información que requiere.
*   **Alertas de Stock Mínimo (S07):**
    *   Las alertas deben presentarse mediante indicadores visuales (como insignias/badges) que no interrumpan el flujo de trabajo (no usar pop-ups intrusivos, preferir banners o notificaciones en el panel de control).
*   **Gestión de Unidades y Equivalencias (S08):**
    *   Dado que es una funcionalidad matemáticamente abstracta, se debe incluir texto de ayuda o "tooltips" explicando cómo el sistema realiza la conversión de las equivalencias definidas.

### 2.3 Módulo Proveedores
*Funcionalidades: Registrar, Consultar, Editar, Desactivar, Asociar Productos.*

*   **Registrar y Editar Proveedores (PV01, PV03):**
    *   En campos con formato predefinido como el CUIT, correo electrónico y teléfono, se deben proveer máscaras de entrada (input masks) o `placeholders` claros (ej. "Ej: 20-12345678-9").
*   **Consultar Información de Proveedores (PV02):**
    *   Los datos de contacto deben ser accesibles y accionables: los teléfonos deben usar el enlace `tel:` (útil si se usa un cliente VoIP o celular) y los correos el enlace `mailto:` para abrir rápidamente la aplicación de correo.
*   **Desactivar Proveedores (PV04):**
    *   Al igual que en productos, requiere obligatoriamente una barrera de confirmación (Modal) para evitar desactivaciones accidentales, informando que el proveedor no estará disponible para nuevas compras.
*   **Asociar Productos a Proveedores (PV05):**
    *   La selección de productos múltiples debe realizarse mediante componentes de interfaz intuitivos, como listas duales (dual listbox), etiquetas removibles (tags) o casillas de verificación (checkboxes) con barra de búsqueda rápida incorporada. Se debe evitar la fatiga por desplazamiento (scroll infinito).
