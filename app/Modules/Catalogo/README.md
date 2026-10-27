# Catálogo público - G1

Consulta pública sin autenticación. Primera etapa de las historias G1 #109 y #110; consume exclusivamente las APIs de Productos de G3 mediante HTTP GET.

## Organización

- `Controllers/CatalogoController`: listado, actualización parcial y detalle, sin dependencia de sesión.
- `Services/CatalogoGateway`: acceso al proveedor y validación del contrato.
- `Services/CatalogoService`: búsqueda, filtros, categorías/marcas con cantidades, ordenamiento y paginación.
- `Data/ProductoCatalogo`: proyección pública, precio, presentación y disponibilidad.

Se sigue el patrón de G1: clases en `Modules`, rutas centrales, vistas en `resources/views/catalogo`, assets en `public/assets` y pruebas en `tests`. No se crean carpetas vacías ni modelos de base de datos: G1 no administra ni duplica el maestro de productos de G3.

## Rutas

| Método | URL | Función |
| --- | --- | --- |
| GET | `/catalogo` | Listado público y filtros. |
| GET | `/catalogo/datos` | Fragmento HTML generado por G1 y opciones de filtros para actualización progresiva. |
| GET | `/catalogo/{producto}` | Detalle y alternativas disponibles de la misma categoría. |

La fuente se configura en `services.catalog`. Las fotografías permitidas y el intervalo de actualización, en `config/catalogo.php`. El formulario y el detalle funcionan también sin JavaScript.

No hay tablas, migraciones, carrito, pedidos confirmados, pagos, reservas de stock ni un modo de demostración publicado. Las fotos son opcionales: la API actual de listado de G3 no las entrega. La ausencia de stock informado se presenta como “Consultar disponibilidad”, sin inferir stock cero.

Ver [informe de implementación, pruebas y reversión](../../../docs/informe-implementacion-catalogo-g1.md).
