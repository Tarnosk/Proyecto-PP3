# Estructura del proyecto

A continuación se describe la organización principal del repositorio.

## Árbol general

```text
.
├── README.md
├── app/
│   ├── Http/
│   ├── Modules/
│   ├── Providers/
│   ├── Services/
│   ├── Support/
│   └── Contracts/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── tests/
├── docs/
│   ├── README.md
│   ├── guia-instalacion.md
│   ├── arquitectura.md
│   ├── estructura-proyecto.md
│   ├── modulos.md
│   ├── flujo-negocio.md
│   ├── seguridad.md
│   ├── despliegue.md
│   ├── roadmap.md
│   └── guia-colaboradores.md
├── Documentacion/
├── artisan
├── composer.json
├── composer.lock
├── phpunit.xml
├── .env.example
├── .gitignore
└── MIGRATION.md
```

## Carpetas clave

### `app`

Contiene la lógica de la aplicación: controladores, servicios, modelos, políticas, requests y módulos funcionales.

### `app/Modules`

Carpeta central del diseño modular. Aquí viven las implementaciones por dominio funcional del ERP.

### `database`

Incluye:

- migraciones
- factories
- seeders
- SQL legacy o históricos

### `resources/views`

Contiene las vistas Blade y templates del frontend.

### `routes`

Define rutas web y API del sistema.

### `tests`

Incluye pruebas automatizadas con PHPUnit.

### `docs/`

Centraliza toda la documentación técnica del proyecto.

### `Documentacion/`

Reúne minutas, contratos inter-grupo, historias de usuario, planes de prueba de sprints y planillas de organización.

## Convención para nuevos módulos

Cuando se cree un nuevo módulo, se recomienda:

```text
app/Modules/NuevoModulo/
├── Controllers/
├── Models/
├── Services/
├── Requests/
├── Policies/
├── database/
├── routes/
├── README.md
└── ...
```

## Reglas de organización

- un modulo debe agrupar dominios coherentes
- no mezclar lógica de negocio de varios dominios en un solo servicio
- documentar cada módulo nuevo con su propio README
- mantener rutas, migraciones y modelos sincronizados
