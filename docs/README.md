# Documentación del proyecto

Esta carpeta reúne la documentación técnica y operativa del sistema ERP.

## Índice

### Guías de Operación y Usuario
- [Manual de Usuario y Guía Operativa](./manual-usuario.md)
- [Guía de Solución de Problemas y FAQ](./troubleshooting.md)
- [Guía de Defensa y Presentación del Proyecto (Pitch Guide)](./guia-defensa-proyecto.md)

### Guías Generales de Desarrollo
- [Guía de instalación](./guia-instalacion.md)
- [Arquitectura del sistema](./arquitectura.md)
- [Registro de Decisiones de Arquitectura (ADR)](./registro-decisiones-arquitectura.md)
- [Glosario de Términos del Dominio](./glosario-terminos.md)
- [Diagramas de Flujo, Estados y Secuencia (Mermaid)](./diagramas-flujo.md)
- [Estructura del proyecto](./estructura-proyecto.md)
- [Módulos funcionales](./modulos.md)
- [Flujo de negocio](./flujo-negocio.md)
- [Seguridad](./seguridad.md)
- [Despliegue](./despliegue.md)
- [Roadmap](./roadmap.md)
- [Guía de colaboradores](./guia-colaboradores.md)

### Requisitos, Datos y Evidencias de Prueba
- [Matriz de Trazabilidad de Requisitos (RTM)](./matriz-trazabilidad-requisitos.md)
- [Diccionario de Datos](./diccionario-datos.md)
- [Reporte de Pruebas: Stock y Productos (S01-S16 / P01-P08)](./reporte-pruebas-stock-inventario.md)
- [Reporte de Pruebas: Proveedores y Compras (PV01-PV08 / C01-C09)](./reporte-pruebas-proveedores-compras.md)
- [Reportes de Pruebas: Órdenes de Compra (PC01 a PC07)](./reporte-pruebas-pc01-ordenes-compra.md)

### Especificaciones de API y Contratos
- [Especificación OpenAPI 3.0 (YAML)](./openapi.yaml)
- [Colección Postman REST API](./contracts/ERP_Juan23_API_Collection.postman_collection.json)
- [Contrato API G1](./contrato-api-g1.md)
- [Contratos Sprint 4 G1](./contratos-sprint4-g1.md)
- [Guía de Integración Modular](./GUIA_INTEGRACION_MODULAR.md)
- [Informe Técnico Integral](./informe-tecnico-integral-erp-juan23.md)

## Propósito

La documentación tiene como objetivo permitir que el proyecto pueda ser entendido, levantado, mantenido y extendido por nuevos integrantes del equipo sin depender únicamente del código fuente.

## Organización

- `guia-instalacion.md`: entorno local, requisitos y pasos para levantar la app.
- `arquitectura.md`: capas, módulos y patrones de diseño.
- `estructura-proyecto.md`: organización de carpetas y archivos relevantes.
- `modulos.md`: detalle funcional por dominio.
- `flujo-negocio.md`: descripción del funcionamiento del ERP.
- `seguridad.md`: autenticación, permisos y buenas prácticas.
- `despliegue.md`: recomendaciones para entorno productivo.
- `roadmap.md`: estado del proyecto y evolución esperada.
- `guia-colaboradores.md`: flujo de trabajo del equipo.

## Convención general

Toda modificación funcional importante debe ir acompañada de una actualización en los documentos relevantes.

## Enlace principal

El README principal del repositorio se encuentra en:

- [README.md](../README.md)
