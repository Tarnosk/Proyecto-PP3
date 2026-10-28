# Módulo Stock e Inventario (Grupo 3)

Este módulo es el núcleo de trazabilidad física y control de existencias de la Distribuidora. Administra el catálogo de existencias, el Kardex inmutable de movimientos, ubicaciones en depósito y sesiones de conteo físico a ciegas.

---

## 1. Responsabilidades del Módulo
- **S01 / S02:** Consulta de inventario general y verificación de disponibilidad en tiempo real.
- **S03 / S04:** Registro de ingresos manuales y salidas automáticas por venta.
- **S05:** Gestión de devoluciones (cliente o proveedor).
- **S06:** Ajustes manuales de inventario por merma, rotura o desvío.
- **S07:** Alertas tempranas de stock mínimo y punto de reposición.
- **S08:** Soporte de unidades de medida (cajas, bultos, packs) y conversión a unidad base.
- **S12:** Kardex inmutable de movimientos con auditoría de usuario y timestamp.
- **S13:** **Conteo Físico a Ciegas:** Módulo de toma de inventario por operario, comparación automática con stock en sistema y ajuste compensatorio por discrepancias.
- **S14:** **Ubicaciones en Depósito:** Mapeo de pasillos, estanterías y niveles asignados a productos.
- **S15 / S16:** Filtros avanzados por rango numérico y soporte de bulto cerrado.

---

## 2. Estructura del Módulo
```text
app/Modules/Stock/
├── Controllers/
│   ├── StockController.php                    # Consulta general, Kardex, disponibilidad y lote de movimientos
│   ├── IngresoMercaderiaController.php        # Altas manuales de mercadería
│   ├── VentaStockController.php               # Descuentos por ventas
│   ├── DevolucionController.php               # Devoluciones cliente/proveedor
│   ├── AjusteStockController.php              # Mermas y ajustes manuales
│   ├── AlertaStockController.php              # Semáforo de stock bajo y crítico
│   ├── ConteoFisicoController.php             # Sesiones de inventario a ciegas
│   ├── UbicacionController.php                # Pasillos y estanterías
│   └── UnidadController.php                   # Factores de conversión
├── Models/
│   ├── Stock.php                              # Entidad de stock
│   ├── MovimientoStock.php                    # Kardex inmutable
│   ├── Ubicacion.php                          # Ubicación en depósito
│   ├── SesionConteo.php                       # Sesión de conteo físico
│   ├── DetalleConteo.php                      # Ítems contados a ciegas
│   └── UnidadMedida.php                       # Equivalencias de empaque
├── Services/
│   ├── StockService.php                       # Motor de validación y cálculo de Kardex
│   └── ConteoFisicoService.php                # Cálculo de discrepancias y compensación
└── Repositories/
    └── ConteoFisicoRepository.php             # Persistencia de tomas de inventario
```

---

## 3. Contrato de APIs para Integración (G1, G2 y G4)
👉 **Documentación completa de endpoints, payloads y ejemplos JSON:** Ver [`docs/contrato-api-g3.md`](../../../docs/contrato-api-g3.md).

---

## 4. Pruebas Automatizadas
- `tests/Feature/UserStoriesFrontendTest.php`: Validaciones end-to-end de `S01` a `S16`.
