# Arquitectura del sistema

El proyecto sigue una arquitectura modular basada en Laravel, con separación por dominio funcional y capacidad de crecimiento incremental.

## Visión general

La aplicación se organiza siguiendo un patrón MVC ampliado por módulos, donde cada dominio funcional encapsula su lógica de negocio, modelos, rutas y vistas.

## Capas de la aplicación

### 1. Capa de presentación

Responsable de la interfaz de usuario.

Ubicación principal:

- `backend/resources/views`
- `backend/public/css`
- `backend/public/js`
- `backend/public/assets`

### 2. Capa de lógica de negocio

Responsable de reglas y procesos de negocio.

Ubicación principal:

- `backend/app/Http/Controllers`
- `backend/app/Modules/*`
- `backend/app/Services`
- `backend/app/Support`

### 3. Capa de persistencia

Responsable del almacenamiento y acceso a datos.

Ubicación principal:

- `backend/database/migrations`
- `backend/database/factories`
- `backend/database/seeders`
- `backend/app/Modules/*/Models`

### 4. Capa de integración

Se encarga de conectarse con servicios y proveedores externos, si corresponde.

Ubicación principal:

- `backend/app/Services/Gateways`
- `backend/app/Modules/*/Integraciones`
- `backend/app/Modules/*/Services`

## Patrón de módulos

Cada módulo encapsula funcionalidad con una estructura clara y uniforme.

Ejemplo de estructura:

```text
backend/app/Modules/Clientes/
├── Controllers/
├── Models/
├── Requests/
├── Policies/
├── Services/
├── database/
├── routes/
└── README.md
```

## Módulos del sistema

Los dominios principales del ERP suelen ser:

- `Auth` / `Login`
- `Clientes`
- `Ventas`
- `Stock`
- `Compras`
- `Proveedores`
- `Reportes`
- `Reclamos`
- `Entregas`
- `Rendiciones`
- `CuentaCorriente`

## Flujos de arquitectura

Un caso típico funciona de la siguiente manera:

1. El usuario inicia sesión.
2. El sistema valida credenciales y rol.
3. El controlador recibe la petición.
4. Se ejecuta la lógica del servicio o módulo.
5. Se persiste o consulta información en la base de datos.
6. La vista Blade renderiza la respuesta.

## Principios de diseño

- separación de responsabilidades
- módulos por dominio
- validación centralizada
- control de acceso por roles
- reutilización de servicios
- mantenibilidad por dominio

## Consideraciones

- la documentación debe mantenerse alineada con el código
- cada cambio de negocio debe evaluarse en términos de impacto en módulos relacionados
- los contratos de integración deben documentarse para evitar errores entre dominios
