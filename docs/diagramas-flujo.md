# Diagramas de Arquitectura, Flujo y Secuencia — ERP Juan23

Este documento contiene los diagramas técnicos modelados en formato **Mermaid**, renderizables directamente de forma nativa en GitHub o en cualquier visor de Markdown.

---

## 1. Diagrama de Estados: Ciclo de Vida de la Orden de Compra (PC06)

Representa la máquina de estados por la que transita una orden de compra desde su emisión hasta su liquidación física:

```mermaid
stateDiagram-v2
    [*] --> Pendiente: Creación de la Orden (PC01)
    
    Pendiente --> Enviada: Notificación al Proveedor (PC06)
    Pendiente --> Cancelada: Cancelación por Comprador (PC05)
    
    Enviada --> Recibida_Parcialmente: Recepción Parcial de Ítems (C03)
    Enviada --> Completada: Recepción Total de Mercadería (C02)
    Enviada --> Cancelada: Cancelación por Falta de Stock
    
    Recibida_Parcialmente --> Completada: Recepción de Remanente Pendiente
    Recibida_Parcialmente --> Cancelada: Cancelación de Saldo Pendiente
    
    Completada --> [*]: Liquidada y Almacenada en Depósito
    Cancelada --> [*]: Liberación de Compromisos
```

---

## 2. Diagrama de Secuencia: Circuito de Abastecimiento e Impacto en Kardex

Muestra la interacción entre los diferentes componentes al recibir una compra con lote y vencimiento:

```mermaid
sequenceDiagram
    autonumber
    actor Operador as Encargado Depósito
    participant View as Interfaz Web (Blade)
    participant RecCtrl as RecepcionController
    participant StkSvc as StockService
    participant LoteMod as Modelo Lote
    participant MovMod as Kardex (movimiento_stock)
    participant DB as MySQL Database

    Operador->>View: Ingresa factura, cantidad, lote y vencimiento
    View->>RecCtrl: POST /api/v1/compras/{id}/recepciones
    Note over RecCtrl: Inicia transacción DB::transaction()
    
    RecCtrl->>LoteMod: Busca o crea Lote (nro_lote, fecha_vencimiento)
    LoteMod-->>RecCtrl: Lote validado y persistido
    
    RecCtrl->>StkSvc: registrarMovimiento(tipo: 'recepcion', cantidad, id_lote)
    StkSvc->>MovMod: create(stock_anterior, cantidad, stock_posterior, lote)
    StkSvc->>DB: UPDATE producto SET stock = stock + cantidad
    StkSvc->>DB: UPDATE lote SET cantidad_actual = cantidad_actual + cantidad
    
    RecCtrl->>DB: Actualiza estado orden a 'completada'
    Note over RecCtrl: Commit de la transacción
    RecCtrl-->>View: 201 Created (Stock y Lote actualizados)
    View-->>Operador: Muestra confirmación visual y comprobante
```

---

## 3. Diagrama de Flujo: Sesión de Conteo Físico a Ciegas (S13)

Describe el proceso de auditoría y ajuste automático de inventario para eliminar discrepancias:

```mermaid
flowchart TD
    Inicio([Inicio de Auditoría]) --> AbrirSesion["Iniciar Sesión de Conteo (POST /sesiones)"]
    AbrirSesion --> EstadoAbierta["Sesión en estado 'abierta'"]
    EstadoAbierta --> Recorrido["Operario recorre depósito físico"]
    Recorrido --> CargaItem["Carga Producto y Cantidad Contada a ciegas"]
    CargaItem --> MasItems{"¿Quedan más productos por contar?"}
    MasItems -- Sí --> CargaItem
    MasItems -- No --> Finalizar["Finalizar Sesión (POST /sesiones/{id}/finalizar)"]
    
    Finalizar --> Comparar["ConteoFisicoService calcula: Diferencia = Contada - Teórica"]
    Comparar --> HayDiferencia{"¿Diferencia == 0?"}
    
    HayDiferencia -- Sí --> SinAjuste["Inventario Exacto (Sin ajuste)"]
    HayDiferencia -- Sobrante (Diff > 0) --> AjusteSobrante["Genera Movimiento Kardex: Ajuste Positivo (+Diff)"]
    HayDiferencia -- Faltante (Diff < 0) --> AjusteFaltante["Genera Movimiento Kardex: Ajuste Negativo (-Diff)"]
    
    SinAjuste --> CerrarSesion["Cierra Sesión en estado 'finalizada'"]
    AjusteSobrante --> CerrarSesion
    AjusteFaltante --> CerrarSesion
    CerrarSesion --> Fin([Fin del Proceso de Conteo])
```

---

## 4. Diagrama de Secuencia: Arquitectura de Seguridad e Identidad

Muestra cómo el middleware desacopla y resuelve la identidad del usuario para la auditoría:

```mermaid
sequenceDiagram
    participant Cliente as Cliente HTTP / Frontend
    participant Middleware as IdentidadUsuario (Middleware)
    participant Resolver as UsuarioActual
    participant Controller as Controlador de Negocio
    participant Auditoria as AuditoriaLogger
    participant TablaAud as Tabla log_auditoria

    Cliente->>Middleware: Petición HTTP (Header X-User-Id o Bearer Token)
    Note over Middleware: Inspecciona Bearer / Headers / Session G1
    Middleware->>Middleware: Resuelve ID numérico del operador
    Middleware->>Resolver: Inyecta atributo 'usuario_autenticado_id'
    
    Middleware->>Controller: Pasa control al Endpoint solicitado
    Controller->>Resolver: UsuarioActual::id($request)
    Resolver-->>Controller: Retorna id_usuario confiable
    
    Controller->>Auditoria: registrar(tabla, id, accion, snapshot_ant, snapshot_nuevo, id_usuario)
    Auditoria->>TablaAud: INSERT INTO log_auditoria (valores_anteriores, valores_nuevos, id_usuario, fecha_hora)
    Controller-->>Cliente: Respuesta JSON 200/201
```
