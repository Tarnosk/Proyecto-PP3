# Módulo Stock e Inventario (Grupo 3)

Este módulo es el núcleo de trazabilidad física y control de existencias de la Distribuidora. Administra el catálogo de existencias, el Kardex inmutable de movimientos, la gestión de lotes y vencimientos, ubicaciones en depósito y sesiones de conteo físico a ciegas.

---

## 1. Responsabilidades del Módulo
- **S01 / S02:** Consulta de inventario general y verificación de disponibilidad en tiempo real.
- **S03 / S04:** Registro de ingresos manuales y salidas automáticas por venta.
- **S05:** Gestión de devoluciones (cliente o proveedor) con imputación obligatoria de lote.
- **S06:** Ajustes manuales de inventario por merma, rotura o desvío.
- **S07:** Alertas tempranas de stock mínimo y punto de reposición.
- **S08:** Soporte de unidades de medida (cajas, bultos, packs) y conversión a unidad base.
- **S09 / S10:** Trazabilidad de partidas, lotes y semáforo de fechas de vencimiento.
- **S11:** Análisis de criticidad de vencimientos y generación de reportes.
- **S12:** Kardex inmutable de movimientos con auditoría de usuario y timestamp.
- **S13:** **Conteo Físico a Ciegas:** Módulo de toma de inventario por operario, comparación automática con stock en sistema y ajuste compensatorio por discrepancias.
- **S14:** **Ubicaciones en Depósito:** Mapeo de pasillos, estanterías y niveles asignados a productos.
- **S15 / S16:** Filtros avanzados por rango numérico y soporte de bulto cerrado.

---

## 2. Estructura del Módulo
```text
app/Modules/Stock/
├── Controllers/
│   ├── StockController.php                    # Consulta general, Kardex y disponibilidad
│   ├── IngresoMercaderiaController.php        # Altas manuales de mercadería
│   ├── VentaStockController.php               # Descuentos por ventas
│   ├── DevolucionController.php               # Devoluciones cliente/proveedor
│   ├── AjusteStockController.php              # Mermas y ajustes manuales
│   ├── AlertaStockController.php              # Semáforo de stock bajo
│   ├── LoteController.php                     # Gestión de partidas y vencimientos
│   ├── ConteoFisicoController.php             # Sesiones de inventario a ciegas
│   ├── UbicacionController.php                # Pasillos y estanterías
│   └── UnidadController.php                   # Factores de conversión
├── Models/
│   ├── Stock.php                              # Entidad de stock
│   ├── MovimientoStock.php                    # Kardex inmutable
│   ├── Lote.php                               # Partidas y caducidad
│   ├── Ubicacion.php                          # Ubicación en depósito
│   ├── SesionConteo.php                       # Sesión de conteo físico
│   ├── DetalleConteo.php                      # Ítems contados a ciegas
│   └── UnidadMedida.php                       # Equivalencias de empaque
├── Services/
│   ├── StockService.php                       # Motor de validación y cálculo de Kardex
│   ├── ConteoFisicoService.php                # Cálculo de discrepancias y compensación
│   └── ConsultaVencimientosService.php        # Semáforo y criticidad de lotes
└── Repositories/
    └── ConteoFisicoRepository.php             # Persistencia de tomas de inventario
```

---

## 3. Endpoints REST (`/api/v1/`)
- `GET /stock`: Inventario general.
- `GET /stock/{id}/disponibilidad`: Chequeo de existencias.
- `POST /stock/ingresos`: Ingreso manual.
- `POST /stock/ventas`: Salida por venta.
- `POST /stock/devoluciones`: Devolución con lote.
- `POST /stock/ajustes`: Ajuste manual por merma.
- `GET /stock/alertas`: Alertas de stock bajo.
- `GET /stock/lotes-por-vencer`: Semáforo de vencimientos.
- `POST /sesiones`: Abrir sesión de conteo físico a ciegas.
- `POST /sesiones/{id}/items`: Cargar cantidad relevada.
- `POST /sesiones/{id}/finalizar`: Cerrar sesión y ajustar stock.

---

## 4. Pruebas Automatizadas
- `tests/Feature/UserStoriesFrontendTest.php`: Validaciones end-to-end de `S01` a `S16`.
- `tests/Feature/ConteoFisicoTest.php`: Flujo integral de conteo físico a ciegas, cálculo de discrepancias y aplicación de ajustes.
