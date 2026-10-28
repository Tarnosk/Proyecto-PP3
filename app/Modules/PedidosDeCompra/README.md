# Módulo Pedidos de Compra (Grupo 3)

Este módulo gestiona el ciclo de aprovisionamiento de mercadería, desde la detección de necesidades de stock hasta la generación, seguimiento y cancelación de órdenes de compra a proveedores.

---

## 1. Responsabilidades del Módulo
- **PC01:** Creación y costeo de órdenes de compra con cálculo automático de totales e impuestos.
- **PC02:** Listado de órdenes con filtros por estado, proveedor y rangos de fecha.
- **PC03:** Detalle de órdenes con renglones de productos, precios pactados y datos del proveedor.
- **PC04:** Edición de pedidos en estado preliminar (`pendiente`).
- **PC05:** Cancelación de pedidos con motivo obligatorio de auditoría.
- **PC06:** Máquina de estados y línea de tiempo (*timeline*) interactivo:
  `pendiente` ➔ `enviada` ➔ `recibida_parcialmente` ➔ `completada` / `cancelada`.
- **PC07:** Cálculo automatizado de sugerencias de reposición basadas en stock mínimo, punto de pedido y lote óptimo.

---

## 2. Estructura del Módulo
```text
app/Modules/PedidosDeCompra/
├── Controllers/
│   ├── OrdenCompraController.php              # CRUD, cancelación y máquina de estados
│   ├── SugerenciasReposicionController.php    # Algoritmo de sugerencias automáticas
│   └── DashboardReposicionController.php      # KPIs y métricas de abastecimiento
├── Models/
│   ├── OrdenCompra.php                        # Cabecera de la orden (tabla orden_compra)
│   ├── DetalleOrden.php                       # Ítems solicitados (tabla detalle_orden)
│   └── SugerenciaCompra.php                   # Sugerencias generadas (tabla sugerencia_compra)
├── Services/
│   └── ReposicionService.php                  # Lógica de cálculo de faltantes y proveedores óptimos
└── routes/
    ├── api.php                                # Endpoints REST /api/v1/ordenes-compra
    └── web.php                                # Rutas web /pedidos-compra
```

---

## 3. Endpoints Principales
- `GET /api/v1/ordenes-compra`: Listar órdenes con filtros.
- `POST /api/v1/ordenes-compra`: Crear una orden de compra.
- `GET /api/v1/ordenes-compra/{id}`: Detalle de la orden.
- `PUT /api/v1/ordenes-compra/{id}`: Modificar orden pendiente.
- `PATCH /api/v1/ordenes-compra/{id}/cancelar`: Cancelar con motivo.
- `PATCH /api/v1/ordenes-compra/{id}/cambiar-estado`: Avanzar estado en el timeline.
- `GET /api/v1/sugerencias-reposicion`: Listar productos por debajo de stock de seguridad.
- `POST /api/v1/sugerencias-reposicion/{id}/generar-orden`: Convertir sugerencia en orden de compra.

---

## 4. Pruebas y Validación
Los criterios de aceptación están documentados y validados en:
- `docs/reporte-pruebas-pc01-ordenes-compra.md` a `pc07`.
- Pruebas automatizadas en `tests/Feature/UserStoriesFrontendTest.php`.
