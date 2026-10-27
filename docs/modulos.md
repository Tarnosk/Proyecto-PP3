# Módulos funcionales

El ERP está pensado para separar el negocio por dominios claros. Esto facilita la escalabilidad, el mantenimiento y la evolución del sistema.

## Módulo de autenticación y seguridad

Responsable de:

- login
- cierre de sesión
- recuperación de contraseña
- autorización por rol
- middleware de seguridad
- control de sesiones

## Módulo de clientes

Responsable de:

- alta, edición y consulta de clientes
- control de zonas y clasificación
- historial de relación con clientes
- datos comerciales y contacto

## Módulo de ventas

Responsable de:

- creación de ventas
- validación de stock y precios
- cambios de estado
- historial de ventas
- cobranzas y saldos

## Módulo de stock

Responsable de:

- ingresos y egresos de mercadería
- ubicaciones y lotes
- conteos físicos
- alertas y vencimientos
- movimientos y ajustes

## Módulo de compras y proveedores

Responsable de:

- compras
- ordenes de compra
- pago a proveedores
- historial de precios
- plazos de entrega

## Módulo de reportes

Responsable de:

- reportes operativos
- ranking de productos
- saldos y deudores
- exportación de reportes
- métricas relevantes para la gestión

## Módulo de reclamos

Responsable de:

- registro de reclamos
- seguimiento de estado
- priorización y resolución

## Módulo de reparto y entregas

Responsable de:

- coordinación de entregas
- remitos y control de logística
- integración con ventas y stock

## Módulo de rendiciones y cuenta corriente

Responsable de:

- cierre de entregas y cobros
- conciliaciones
- flujo financiero del negocio
- seguimiento de saldos

## Módulo de integración

Encargado de conectar la lógica interna con servicios externos o módulos dependientes, gestionando:

- APIs internas
- mocks o gateways
- contratos de integración
- validación de dependencias

## Recomendación

Cada módulo debe tener un README específico y una clara definición de responsabilidad para evitar duplicación lógica y mantener la arquitectura limpia.
