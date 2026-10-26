# Flujo de negocio

Este documento describe el funcionamiento esperado del ERP como sistema integrado para una distribuidora.

## 1. Gestión de clientes

El proceso comienza con el registro y mantenimiento de clientes. Se pueden almacenar datos básicos, estado comercial, zonas y contacto. Dependiendo del rol del usuario, se pueden crear, editar, consultar o bloquear clientes.

## 2. Gestión de productos y stock

Se registran productos y sus categorías, marcas, presentaciones, precios y stock. El sistema debe permitir consultar disponibilidad y ajustar movimientos reales de inventario cuando se registran ventas, compras o devoluciones.

## 3. Gestión de compras

Cuando se requiere abastecer stock, el sistema administra compras, proveedores y pagos. Se puede registrar una compra, asociarla a un proveedor y controlar el estado de la misma.

## 4. Gestión de ventas

La venta es el núcleo operativo del ERP. Un cliente compra productos, el sistema valida disponibilidad, calcula totales, registra la operación y actualiza el estado comercial asociado.

## 5. Gestión de entregas y repartos

Una vez confirmada la venta, el sistema puede derivar la entrega a reparto. Se controlan remitos, entregas y seguimiento del estado de envío.

## 6. Cobranza y cuenta corriente

Se registran movimientos relacionados con pagos, deudas, saldos y conciliación. Esto permite tener control financiero de clientes y proveedores sin depender de herramientas externas.

## 7. Reclamos y devoluciones

Cuando hay incidencia con una operación, se registran reclamos y se debe seguir su resolución. El sistema ayuda a controlar prioridad, estado y trazabilidad.

## 8. Reportes y analítica

El negocio necesita consultar indicadores clave, como ventas por periodo, ranking de productos, clientes con deuda, en función de la información cargada por los distintos módulos.

## 9. Seguridad y roles

El sistema restringe acciones según el tipo de usuario. Los permisos y políticas deben validarse en cada operación crítica para evitar accesos indebidos.

## 10. Integración interna

Los diferentes módulos interactúan entre sí mediante servicios, modelos, contratos y reglas de negocio. Esto permite que el sistema funcione como una única plataforma con lógica distribuida por dominio.
