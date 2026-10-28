# Guía de colaboradores

Esta guía sirve para mantener un flujo de trabajo claro y ordenado dentro del equipo.

## Flujo recomendado

1. Crear una rama nueva desde la rama base.
2. Trabajar un solo área funcional o corrección por rama.
3. Mantener mensajes de commit claros.
4. Ejecutar pruebas relevantes antes de finalizar.
5. Actualizar documentación si cambia comportamiento o arquitectura.
6. Abrir pull request con descripción clara.

## Convenciones de commit

Ejemplos:

```bash
git commit -m "feat: agregar módulo de clientes"
git commit -m "fix: corregir validación de stock"
git commit -m "refactor: reorganizar servicios de ventas"
```

## Pull request

Un buen PR debe incluir:

- resumen del cambio
- archivos principales modificados
- pruebas ejecutadas
- impacto en otras áreas
- notas de documentación

## Reglas del equipo

- no subir `.env` ni secretos
- no mezclar tareas distintas en una misma rama
- documentar cambios funcionales
- revisar permisos y roles antes de mergear
- mantener coherencia entre código, migraciones y documentación

## Recomendación

Un proyecto ERP crece rápidamente. La clave para mantenerlo sano es la disciplina de documentación, pruebas y comunicación entre módulos.
