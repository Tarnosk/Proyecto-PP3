@extends('layouts.app')

@section('title', 'Punto de Venta')

@push('styles')
<style>
    /* ---------- Cabecera de venta ---------- */
    .venta-top {
        display: grid;
        grid-template-columns: 1fr auto auto;
        gap: 1rem;
        align-items: stretch;
    }

    .factura-box { display: flex; flex-direction: column; gap: .25rem; }
    .factura-box .label {
        font-size: .72rem;
        font-weight: 700;
        color: var(--muted);
        letter-spacing: .5px;
        text-transform: uppercase;
    }
    .factura-box .cliente {
        font-size: 1.15rem;
        font-weight: 700;
    }
    .factura-box .cuit { font-size: .9rem; color: var(--muted); }
    .factura-box .cuit a { color: var(--primary); font-weight: 600; text-decoration: none; }

    .lista-toggle {
        display: flex;
        gap: .5rem;
        align-items: center;
    }
    .lista-btn {
        min-height: 48px;
        padding: 0 1.1rem;
        border-radius: 14px;
        border: 1px solid var(--border);
        background: #fff;
        font-family: inherit;
        font-size: .9rem;
        font-weight: 600;
        cursor: pointer;
        color: var(--text);
    }
    .lista-btn.active {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }

    .acciones-cliente {
        display: flex;
        flex-direction: column;
        gap: .5rem;
        justify-content: center;
        min-width: 230px;
    }
    .acciones-cliente label {
        color: var(--muted);
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
    }
    .acciones-cliente select {
        width: 100%;
        min-height: 44px;
        padding: 0 .7rem;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #fff;
        color: var(--text);
        font: inherit;
    }
    .venta-feedback {
        grid-column: 1 / -1;
        margin: 0;
        font-size: .85rem;
    }
    .venta-feedback.success { color: #008a35; }
    .venta-feedback.error { color: var(--danger); }
    @media (max-width: 900px) {
        .venta-top { grid-template-columns: 1fr; }
        .lista-toggle { flex-wrap: wrap; }
        .acciones-cliente { min-width: 0; }
    }

    /* ---------- Buscador ---------- */
    .buscador {
        position: relative;
        margin-top: 1rem;
        z-index: 50;
    }
    .buscador input {
        padding-left: 2.75rem;
        font-size: 1rem;
    }
    .buscador .ico {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--muted);
        pointer-events: none;
    }

    /* ---------- Tabla del carrito ---------- */
    .carrito {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 380px;
        padding: 0;
        overflow: hidden;
    }
    .carrito-header {
        display: grid;
        grid-template-columns: 90px 1fr 100px 120px 90px 130px 40px;
        gap: .5rem;
        padding: .9rem 1.25rem;
        font-size: .72rem;
        font-weight: 700;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .5px;
        border-bottom: 1px solid var(--border);
    }
    .carrito-body {
        flex: 1;
        overflow-y: auto;
        padding: .5rem 0;
    }
    .carrito-row {
        display: grid;
        grid-template-columns: 90px 1fr 100px 120px 90px 130px 40px;
        gap: .5rem;
        align-items: center;
        padding: .6rem 1.25rem;
        font-size: .95rem;
        border-bottom: 1px solid #F1F5F9;
    }
    .carrito-row input {
        width: 100%;
        min-height: 38px;
        padding: 0 .5rem;
        border: 1px solid var(--border);
        border-radius: 10px;
        font-family: inherit;
        font-size: .9rem;
        text-align: right;
    }
    .carrito-row .num { text-align: right; font-variant-numeric: tabular-nums; }
    .carrito-row .quitar {
        background: none;
        border: none;
        color: var(--danger);
        cursor: pointer;
        font-size: 1.1rem;
        padding: .25rem;
        justify-self: center;
        line-height: 1;
        border-radius: 8px;
        transition: background .15s;
    }
    .carrito-row .quitar:hover {
        background: #FEE2E2;
    }

    .carrito-vacio {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #94A3B8;
        gap: .75rem;
        padding: 3rem 0;
    }
    .carrito-vacio .icono {
        font-size: 3rem;
        opacity: .5;
    }

    /* ---------- Footer ---------- */
    .carrito-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        border-top: 1px solid var(--border);
        background: #F8FAFC;
        font-size: .9rem;
        color: var(--muted);
    }
    .total-box {
        display: flex;
        align-items: baseline;
        gap: .75rem;
    }
    .total-box .label { font-size: .95rem; font-weight: 600; color: var(--text); }
    .total-box .monto {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--text);
        font-variant-numeric: tabular-nums;
    }

    /* ---------- Barra de acciones inferior ---------- */
    .acciones-finales {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: .75rem;
        margin-top: 1rem;
    }
    .acciones-finales button {
        min-height: 56px;
        font-size: 1rem;
    }
    /* ---------- Modal de descuentos ---------- */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .45);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 1rem;
    }
    .modal-overlay.activo { display: flex; }

    .modal-desc {
        width: 100%;
        max-width: 440px;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 20px 50px rgba(30, 58, 138, .25);
        overflow: hidden;
        animation: modalIn .18s ease-out;
    }
    @keyframes modalIn {
        from { opacity: 0; transform: translateY(-8px) scale(.97); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .modal-desc-header {
        background: var(--primary);
        color: #fff;
        padding: 1rem 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 700;
        font-size: 1rem;
    }
    .modal-desc-header .cerrar {
        background: transparent;
        border: none;
        color: #fff;
        font-size: 1.4rem;
        cursor: pointer;
        line-height: 1;
        padding: 0 .25rem;
        opacity: .85;
    }
    .modal-desc-header .cerrar:hover { opacity: 1; }

    .modal-desc-body {
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    /* Toggle Porcentaje / Monto Fijo */
    .desc-toggle {
        display: grid;
        grid-template-columns: 1fr 1fr;
        background: #F1F5F9;
        border-radius: 12px;
        padding: 4px;
        gap: 4px;
    }
    .desc-toggle button {
        min-height: 42px;
        border: none;
        border-radius: 9px;
        background: transparent;
        font-family: inherit;
        font-size: .9rem;
        font-weight: 600;
        color: var(--muted);
        cursor: pointer;
        transition: all .15s;
    }
    .desc-toggle button.active {
        background: var(--primary);
        color: #fff;
        box-shadow: 0 2px 8px rgba(59, 130, 246, .35);
    }

    .desc-label {
        font-size: .85rem;
        font-weight: 600;
        color: var(--muted);
        margin-bottom: -.5rem;
    }

    .desc-input-wrap {
        position: relative;
    }
    .desc-input-wrap input {
        width: 100%;
        min-height: 62px;
        padding: 0 3rem 0 1rem;
        border: 2px solid var(--primary);
        border-radius: 14px;
        font-family: inherit;
        font-size: 1.6rem;
        font-weight: 700;
        text-align: center;
        color: var(--text);
        outline: none;
        background: #fff;
        font-variant-numeric: tabular-nums;
    }
    .desc-input-wrap input::placeholder {
        color: #CBD5E1;
        font-weight: 600;
    }
    .desc-input-wrap .sufijo {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--muted);
        pointer-events: none;
    }
    .desc-input-wrap .prefijo {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--muted);
        pointer-events: none;
    }

    .desc-preview {
        background: #F8FAFC;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: .75rem 1rem;
        display: flex;
        justify-content: space-between;
        font-size: .9rem;
        color: var(--muted);
    }
    .desc-preview strong {
        color: var(--text);
        font-variant-numeric: tabular-nums;
    }
    .desc-preview .nuevo-total {
        color: #16A34A;
        font-weight: 800;
        font-size: 1rem;
    }

    .modal-desc-footer {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .75rem;
        padding: 0 1.25rem 1.25rem;
    }
    .modal-desc-footer button {
        min-height: 48px;
        font-size: .95rem;
        font-weight: 700;
        border-radius: 12px;
        cursor: pointer;
        font-family: inherit;
        border: 1px solid var(--border);
    }
    .modal-desc-footer .btn-cancelar {
        background: #fff;
        color: var(--text);
    }
    .modal-desc-footer .btn-cancelar:hover { background: #F8FAFC; }
    .modal-desc-footer .btn-aplicar {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }
    .modal-desc-footer .btn-aplicar:hover { filter: brightness(1.05); }
    /* ---------- Footer con descuento ---------- */
    .carrito-footer .footer-info {
        display: flex;
        flex-direction: column;
        gap: .35rem;
    }

    .descuento-aplicado {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        font-size: .85rem;
        color: #16A34A;
        background: #DCFCE7;
        border: 1px solid #86EFAC;
        padding: .25rem .6rem;
        border-radius: 999px;
        width: fit-content;
        font-weight: 600;
    }
    .descuento-aplicado strong {
        font-variant-numeric: tabular-nums;
    }
    .descuento-aplicado .quitar-descuento {
        background: transparent;
        border: none;
        color: #16A34A;
        cursor: pointer;
        font-size: .9rem;
        line-height: 1;
        padding: 0 .1rem;
        opacity: .7;
    }
    .descuento-aplicado .quitar-descuento:hover {
        opacity: 1;
    }
    /* ---------- Botón de descuento por línea ---------- */
    .desc-linea {
        width: 100%;
        min-height: 38px;
        padding: 0 .5rem;
        border: 1px dashed var(--border);
        border-radius: 10px;
        background: #fff;
        font-family: inherit;
        font-size: .9rem;
        font-weight: 600;
        color: var(--muted);
        cursor: pointer;
        transition: all .15s;
        font-variant-numeric: tabular-nums;
    }
    .desc-linea:hover {
        border-color: var(--primary);
        color: var(--primary);
        background: #EFF6FF;
    }
    .desc-linea.con-desc {
        background: #FEF3C7;
        border: 1px solid #FCD34D;
        color: #B45309;
    }
    .desc-linea.con-desc:hover {
        background: #FDE68A;
        color: #92400E;
    }
    /* ---------- Botón de descuento por línea ---------- */
    .desc-linea {
        width: 100%;
        min-height: 38px;
        padding: 0 .5rem;
        border: 1px dashed var(--border);
        border-radius: 10px;
        background: #fff;
        font-family: inherit;
        font-size: .9rem;
        font-weight: 600;
        color: var(--muted);
        cursor: pointer;
        transition: all .15s;
        font-variant-numeric: tabular-nums;
    }
    .desc-linea:hover {
        border-color: var(--primary);
        color: var(--primary);
        background: #EFF6FF;
    }
    .desc-linea.con-desc {
        background: #FEF3C7;
        border: 1px solid #FCD34D;
        color: #B45309;
    }
    .desc-linea.con-desc:hover {
        background: #FDE68A;
        color: #92400E;
    }

    /* ---------- Celda de subtotal con bruto tachado ---------- */
    .subtotal-cell {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        line-height: 1.15;
        font-variant-numeric: tabular-nums;
    }
    .subtotal-cell .sub-bruto {
        font-size: .78rem;
        color: #94A3B8;
        text-decoration: line-through;
    }
    .subtotal-cell .sub-neto {
        font-weight: 700;
        color: var(--text);
    }

    /* ---------- Footer mejorado ---------- */
    .carrito-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        border-top: 1px solid var(--border);
        background: #F8FAFC;
        font-size: .9rem;
        color: var(--muted);
        gap: 1rem;
    }
    .footer-izq {
        display: flex;
        flex-direction: column;
        gap: .35rem;
    }
    .footer-der {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: .15rem;
    }
    .footer-linea {
        display: flex;
        gap: .5rem;
        font-size: .85rem;
        color: var(--muted);
        font-variant-numeric: tabular-nums;
    }
    .footer-linea .monto-tachado {
        text-decoration: line-through;
        color: #94A3B8;
    }
    .footer-linea.descuento {
        color: #B45309;
        font-weight: 600;
    }
    .footer-linea.total-final {
        margin-top: .25rem;
        align-items: baseline;
        color: var(--text);
    }
    .footer-linea.total-final .label {
        font-size: .95rem;
        font-weight: 600;
    }
    .footer-linea.total-final .monto {
        font-size: 1.75rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
    }

    .descuento-aplicado {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        font-size: .85rem;
        color: #16A34A;
        background: #DCFCE7;
        border: 1px solid #86EFAC;
        padding: .25rem .6rem;
        border-radius: 999px;
        width: fit-content;
        font-weight: 600;
    }
    .descuento-aplicado .quitar-descuento {
        background: transparent;
        border: none;
        color: #16A34A;
        cursor: pointer;
        font-size: .9rem;
        line-height: 1;
        opacity: .7;
    }
    .descuento-aplicado .quitar-descuento:hover { opacity: 1; }
    /* ---------- Modal de cobro ---------- */
    .cobro-resumen {
        background: #F8FAFC;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: .85rem 1rem;
        display: flex;
        flex-direction: column;
        gap: .35rem;
    }
    .cobro-resumen .fila {
        display: flex;
        justify-content: space-between;
        font-size: .9rem;
        color: var(--muted);
    }
    .cobro-resumen .fila strong {
        color: var(--text);
        font-variant-numeric: tabular-nums;
    }
    .cobro-resumen .fila.descuento strong { color: #B45309; }
    .cobro-resumen .fila.total {
        border-top: 1px solid var(--border);
        margin-top: .35rem;
        padding-top: .55rem;
        align-items: baseline;
    }
    .cobro-resumen .fila.total span {
        font-size: .95rem;
        font-weight: 700;
        color: var(--text);
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .cobro-resumen .fila.total strong {
        font-size: 1.55rem;
        font-weight: 800;
    }

    .cobro-textarea {
        width: 100%;
        padding: .75rem 1rem;
        border: 1px solid var(--border);
        border-radius: 12px;
        font-family: inherit;
        font-size: .92rem;
        resize: vertical;
        min-height: 80px;
        max-height: 180px;
        color: var(--text);
        outline: none;
        transition: border-color .15s;
    }
    .cobro-textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(59,130,246,.15);
    }

    .cobro-contador {
        font-size: .75rem;
        color: var(--muted);
        text-align: right;
        margin-top: -.5rem;
    }
</style>
@endpush

@section('content')

    {{-- Cabecera: emisor + lista + acciones cliente --}}
    <div class="clay-card venta-top">
        <div class="factura-box">
            <span class="label">Emitir factura a:</span>
            <span class="cliente" id="nombreClienteVenta"></span>
            <span class="cuit" id="cuitClienteVenta"></span>
            <span class="cuit" id="domicilioClienteVenta"></span>
        </div>

        <div class="lista-toggle">
            <button type="button" class="lista-btn active" data-lista="minorista">
                Lista 1 — Minorista
            </button>
            <button type="button" class="lista-btn" data-lista="mayorista">
                Lista 2 — Mayorista
            </button>
        </div>

        <div class="acciones-cliente">
            <label for="clienteVenta">Cliente (venta a cuenta corriente)</label>
            <select id="clienteVenta" required>
                @foreach ($clientes as $cliente)
                    <option value="{{ $cliente['id'] }}">{{ $cliente['nombre'] }} — {{ $cliente['cuit'] }}</option>
                @endforeach
            </select>
        </div>
        <p class="venta-feedback" id="ventaFeedback" role="status" aria-live="polite" hidden></p>
    </div>

    {{-- Buscador de productos --}}
    <div class="clay-card buscador" style="padding: .5rem 1rem;">
        <span class="ico">🔍</span>
        <input
            type="text"
            id="buscadorProductos"
            class="clay-input"
            placeholder="Buscar producto por nombre o código..."
            autocomplete="off"
        >
        <div id="sugerencias" style="
            position:absolute; left:1rem; right:1rem; top:100%;
            background:#fff; border:1px solid var(--border); border-radius:14px;
            box-shadow: 0 8px 24px rgba(30,58,138,.12);
            max-height:280px; overflow-y:auto; z-index:10; display:none;">
        </div>
    </div>
    {{-- Carrito --}}
    <div class="clay-card carrito">
        <div class="carrito-header">
            <span>Cód.</span>
            <span>Descripción</span>
            <span style="text-align:right;">Cantidad</span>
            <span style="text-align:right;">Precio U.</span>
            <span style="text-align:right;">Desc.</span>
            <span style="text-align:right;">Subtotal</span>
            <span></span>
        </div>

        <div class="carrito-body" id="carritoBody">
            {{-- Las filas se generan con JS --}}
        </div>

        <div class="carrito-vacio" id="carritoVacio">
            <div class="icono">🛒</div>
            <p style="margin:0;">Carrito vacío — busque un producto</p>
        </div>

        <div class="carrito-footer">
            <div class="footer-izq">
                <span id="resumenItems">0 art. · 0 unid.</span>
                <span class="descuento-aplicado" id="descuentoAplicado" style="display:none;">
                    🏷 Desc. global: <strong id="descuentoMonto">- $ 0,00</strong>
                    <button type="button" class="quitar-descuento" id="quitarDescuento" title="Quitar descuento global">✕</button>
                </span>
            </div>

            <div class="footer-der">
                <div class="footer-linea" id="lineaSubtotal">
                    <span>Subtotal:</span>
                    <span id="subtotalSinDesc">$ 0,00</span>
                </div>
                <div class="footer-linea descuento" id="lineaDescLineas" style="display:none;">
                    <span>Desc. productos:</span>
                    <span id="descLineasMonto">- $ 0,00</span>
                </div>
                <div class="footer-linea descuento" id="lineaDescGlobal" style="display:none;">
                    <span>Desc. global:</span>
                    <span id="descGlobalMonto">- $ 0,00</span>
                </div>
                <div class="footer-linea total-final">
                    <span class="label">Total</span>
                    <span class="monto" id="totalMonto">$ 0,00</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Acciones finales --}}
    <div class="acciones-finales">
        <button type="button" class="clay-btn-primary" id="btnCobrar" disabled>
            🧾 COBRAR
        </button>
        <button type="button" class="clay-btn-secondary" id="btnLimpiar">
            🗑 LIMPIAR
        </button>
        <button type="button" class="clay-btn-primary" id="btnDescuentos">
            % DESCUENTOS
        </button>
        <button type="button" class="clay-btn-danger" id="btnCerrarCaja">
            🔒 CERRAR CAJA
        </button>
    </div>
    {{-- Modal de descuentos --}}
    <div class="modal-overlay" id="modalDescuento">
        <div class="modal-desc">
            <div class="modal-desc-header">
                <span id="modalDescTitulo">Descuento sobre el Total</span>
                <button type="button" class="cerrar" id="cerrarModalDesc">✕</button>
            </div>

            <div class="modal-desc-body">
                {{-- Toggle Porcentaje / Monto Fijo --}}
                <div class="desc-toggle">
                    <button type="button" class="active" data-modo="porcentaje">% Porcentaje</button>
                    <button type="button" data-modo="monto">$ Monto Fijo</button>
                </div>

                <span class="desc-label" id="descLabel">Porcentaje de descuento (%)</span>

                <div class="desc-input-wrap">
                    <span class="prefijo" id="descPrefijo" style="display:none;">$</span>
                    <input
                        type="number"
                        id="descInput"
                        min="0"
                        step="0.01"
                        placeholder="Ej: 10"
                    >
                    <span class="sufijo" id="descSufijo">%</span>
                </div>

                <div class="desc-preview">
                    <span>Total actual: <strong id="descTotalActual">$ 0,00</strong></span>
                    <span>Nuevo total: <span class="nuevo-total" id="descNuevoTotal">$ 0,00</span></span>
                </div>
            </div>

            <div class="modal-desc-footer">
                <button type="button" class="btn-cancelar" id="btnCancelarDesc">Cancelar</button>
                <button type="button" class="btn-aplicar" id="btnAplicarDesc">Aplicar Descuento</button>
            </div>
        </div>
    </div>
    {{-- Modal de cobro --}}
    <div class="modal-overlay" id="modalCobro">
        <div class="modal-desc">
            <div class="modal-desc-header">
                <span>Confirmar Cobro</span>
                <button type="button" class="cerrar" id="cerrarModalCobro">✕</button>
            </div>

            <div class="modal-desc-body">
                {{-- Resumen compacto --}}
                <div class="cobro-resumen">
                    <div class="fila">
                        <span>Artículos</span>
                        <strong id="cobroItems">0</strong>
                    </div>
                    <div class="fila">
                        <span>Subtotal</span>
                        <strong id="cobroSubtotal">$ 0,00</strong>
                    </div>
                    <div class="fila descuento" id="cobroFilaDescProd" style="display:none;">
                        <span>Desc. productos</span>
                        <strong id="cobroDescProd">- $ 0,00</strong>
                    </div>
                    <div class="fila descuento" id="cobroFilaDescGlobal" style="display:none;">
                        <span>Desc. global</span>
                        <strong id="cobroDescGlobal">- $ 0,00</strong>
                    </div>
                    <div class="fila total">
                        <span>TOTAL</span>
                        <strong id="cobroTotal">$ 0,00</strong>
                    </div>
                </div>

                {{-- Observaciones --}}
                <span class="desc-label">Observaciones (opcional)</span>
                <textarea
                    id="cobroObservaciones"
                    class="cobro-textarea"
                    rows="3"
                    maxlength="150"
                    placeholder="Ej: cliente retira mañana, pago con transferencia, etc."></textarea>
                <span class="cobro-contador"><span id="cobroContador">0</span>/150</span>
            </div>

            <div class="modal-desc-footer">
                <button type="button" class="btn-cancelar" id="btnCancelarCobro">Cancelar</button>
                <button type="button" class="btn-aplicar" id="btnConfirmarCobro">✓ Confirmar Cobro</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Datos que vienen del controlador (por ahora hardcodeados en VentaController)
    const PRODUCTOS = @json($productos);
    const CLIENTES = @json($clientes);
    const URL_VENTAS = @json(route('ventas.store'));
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

    // ---------- Estado ----------
    let listaActual = 'minorista'; // o 'mayorista'
    let descuentoGlobal = { modo: 'porcentaje', valor: 0 }; // valor: % o $ según modo
    let modoDescTemporal = 'porcentaje';
    let itemDescTarget = null; // null = descuento global; id = descuento de esa línea
    const carrito = []; // { id, codigo, nombre, cantidad, precioUnit, descuento }
    let ventaEnCurso = false;

    const $ = (id) => document.getElementById(id);

    function actualizarCliente() {
        const cliente = CLIENTES.find(x => x.id === Number($('clienteVenta').value));
        if (!cliente) return;

        $('nombreClienteVenta').textContent = cliente.nombre;
        $('cuitClienteVenta').textContent = `CUIT: ${cliente.cuit} · ${cliente.condicion}`;
        $('domicilioClienteVenta').textContent = cliente.domicilio;
    }

    $('clienteVenta').addEventListener('change', actualizarCliente);
    actualizarCliente();

    // ---------- Buscador ----------
    const input = $('buscadorProductos');
    const panelSug = $('sugerencias');

    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        if (q.length < 1) { panelSug.style.display = 'none'; return; }

        const coincidencias = PRODUCTOS.filter(p =>
            p.nombre.toLowerCase().includes(q) ||
            p.codigo.toLowerCase().includes(q)
        ).slice(0, 8);

        if (!coincidencias.length) {
            panelSug.innerHTML = '<div style="padding:.75rem 1rem;color:#94A3B8;">Sin resultados</div>';
        } else {
            panelSug.innerHTML = coincidencias.map(p => `
                <div class="sugerencia-item" data-id="${p.id}"
                     style="padding:.7rem 1rem; cursor:pointer; border-bottom:1px solid #F1F5F9;">
                    <strong>${p.codigo}</strong> · ${p.nombre}
                    <span style="float:right;color:#64748B;">
                        $${Number(p.precioMin).toLocaleString('es-AR')}
                    </span>
                </div>
            `).join('');
        }
        panelSug.style.display = 'block';

        panelSug.querySelectorAll('.sugerencia-item').forEach(el => {
            el.addEventListener('click', () => {
                agregarProducto(Number(el.dataset.id));
                input.value = '';
                panelSug.style.display = 'none';
                input.focus();
            });
        });
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const q = input.value.trim().toLowerCase();
            const p = PRODUCTOS.find(p =>
                p.codigo.toLowerCase() === q || p.nombre.toLowerCase() === q
            );
            if (p) {
                agregarProducto(p.id);
                input.value = '';
                panelSug.style.display = 'none';
            }
        }
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.buscador')) panelSug.style.display = 'none';
    });

    // ---------- Carrito ----------
    function precioDe(p) {
        return listaActual === 'mayorista' ? p.precioMay : p.precioMin;
    }

    function agregarProducto(id) {
        const p = PRODUCTOS.find(x => x.id === id);
        if (!p) return;

        const existente = carrito.find(x => x.id === id);
        if (existente) {
            existente.cantidad += 1;
        } else {
            carrito.push({
                id: p.id,
                codigo: p.codigo,
                nombre: p.nombre,
                cantidad: 1,
                precioUnit: precioDe(p),
                descuentoMonto: 0,
                descuentoInputModo: null,
                descuentoInputValor: null
            });
        }
        render();
    }

    function quitarProducto(id) {
        const i = carrito.findIndex(x => x.id === id);
        if (i >= 0) carrito.splice(i, 1);
        render();
    }
    function descuentoLineaMonto(item) {
        const descuento = Number(item.descuentoMonto) || 0;
        const bruto = item.cantidad * item.precioUnit;

        return Math.min(Math.max(descuento, 0), bruto);
    }
    function brutoLinea(item) {
    return item.cantidad * item.precioUnit;
    }
    function netoLinea(item) {
    return brutoLinea(item) - descuentoLineaMonto(item);
    }
    function getSubtotalBruto() {
    return carrito.reduce((s, x) => s + brutoLinea(x), 0);
    }
    function getDescuentoLineasTotal() {
    return carrito.reduce((s, x) => s + descuentoLineaMonto(x), 0);
    }
    function getSubtotalConDescLineas() {
    return getSubtotalBruto() - getDescuentoLineasTotal();
    }
    // Aplica el descuento global sobre la base ya neta de líneas
    function calcularTotalConDescuento(subtotalNetoLineas) {
    if (!descuentoGlobal.valor) return subtotalNetoLineas;
    if (descuentoGlobal.modo === 'porcentaje') {
        return subtotalNetoLineas * (1 - descuentoGlobal.valor / 100);
    }
    return Math.max(0, subtotalNetoLineas - descuentoGlobal.valor);
    }
    function render() {
        const body = $('carritoBody');
        const vacio = $('carritoVacio');
        const btnCobrar = $('btnCobrar');

        if (!carrito.length) {
            body.innerHTML = '';
            vacio.style.display = 'flex';
            $('resumenItems').textContent = '0 art. · 0 unid.';
            $('totalMonto').textContent = '$ 0,00';
            btnCobrar.disabled = true;
            return;
        }

        vacio.style.display = 'none';

        body.innerHTML = carrito.map(item => {
            /* ---- columna Desc. (botón) ---- */
            const desc = descuentoLineaMonto(item);
            let descLabel;
            if (desc <= 0) {
                descLabel = '—';
            } else if (item.descuentoInputModo === 'porcentaje' && item.descuentoInputValor) {
                descLabel = '-' + item.descuentoInputValor + '%';
            } else {
                descLabel = '-$' + desc.toLocaleString('es-AR', { maximumFractionDigits: 2 });
            }

            /* ---- columna Subtotal (bruto tachado + neto) ---- */
            const bruto = brutoLinea(item);
            const neto = netoLinea(item);
            const subtotalHTML = desc > 0
                ? `<span class="sub-bruto">$${bruto.toLocaleString('es-AR',{minimumFractionDigits:2,maximumFractionDigits:2})}</span>
                <span class="sub-neto">$${neto.toLocaleString('es-AR',{minimumFractionDigits:2,maximumFractionDigits:2})}</span>`
                : `<span class="num">$${neto.toLocaleString('es-AR',{minimumFractionDigits:2,maximumFractionDigits:2})}</span>`;
            return `
                <div class="carrito-row" data-id="${item.id}">
                    <span>${item.codigo}</span>
                    <span>${item.nombre}</span>
                    <input type="number" min="1" value="${item.cantidad}"
                        data-campo="cantidad" data-id="${item.id}">
                    <span class="num">$${Number(item.precioUnit).toLocaleString('es-AR')}</span>
                    <button type="button"
                            class="desc-linea ${desc > 0 ? 'con-desc' : ''}"
                            data-desc-linea="${item.id}"
                            title="Aplicar descuento a este producto">
                        ${descLabel}
                    </button>
                    <span class="subtotal-cell">${subtotalHTML}</span>
                    <button class="quitar" data-quitar="${item.id}" title="Quitar">✕</button>
                </div>
            `;
        }).join('');

        body.querySelectorAll('input[data-campo="cantidad"]').forEach(inp => {
            inp.addEventListener('change', (e) => {
                const id = Number(e.target.dataset.id);
                const item = carrito.find(x => x.id === id);
                if (!item) return;

                let val = Number(e.target.value) || 0;
                if (val < 1) val = 1;
                item.cantidad = val;
                render();
            });
        });

        body.querySelectorAll('[data-quitar]').forEach(btn => {
            btn.addEventListener('click', () => quitarProducto(Number(btn.dataset.quitar)));
        });

        // --- Resumen de items ---
        const totalUnid = carrito.reduce((s, x) => s + x.cantidad, 0);
        $('resumenItems').textContent = `${carrito.length} art. · ${totalUnid} unid.`;

        // --- Cálculos ---
        const subtotalBruto     = getSubtotalBruto();              // sin NADA de descuento
        const descLineasTotal   = getDescuentoLineasTotal();
        const subtotalNetoLin   = subtotalBruto - descLineasTotal; // con desc. de líneas
        const totalFinal        = calcularTotalConDescuento(subtotalNetoLin);
        const descGlobalMonto   = subtotalNetoLin - totalFinal;

        const fmt = (n) => '$ ' + n.toLocaleString('es-AR', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });

        // --- Subtotal (siempre visible) ---
        $('subtotalSinDesc').textContent = fmt(subtotalBruto);

        // --- Fila descuentos de línea (solo si hay) ---
        if (descLineasTotal > 0) {
            $('lineaDescLineas').style.display = 'flex';
            $('descLineasMonto').textContent = '- ' + fmt(descLineasTotal).replace('$ ', '$ ');
        } else {
            $('lineaDescLineas').style.display = 'none';
        }

        // --- Badge + fila descuento global ---
        if (descuentoGlobal.valor > 0 && descGlobalMonto > 0) {
            $('descuentoAplicado').style.display = 'inline-flex';
            const etiqueta = descuentoGlobal.modo === 'porcentaje'
                ? `(${descuentoGlobal.valor}%)` : '';
            $('descuentoMonto').textContent =
                `- ${fmt(descGlobalMonto)} ${etiqueta}`.trim();

            $('lineaDescGlobal').style.display = 'flex';
            $('descGlobalMonto').textContent = '- ' + fmt(descGlobalMonto);
        } else {
            $('descuentoAplicado').style.display = 'none';
            $('lineaDescGlobal').style.display = 'none';
        }

        // --- Total ---
        $('totalMonto').textContent = fmt(totalFinal);

        btnCobrar.disabled = false;

        // --- Persistencia ---
        localStorage.setItem('pos_carrito', JSON.stringify(carrito));
        localStorage.setItem('pos_descuento', JSON.stringify(descuentoGlobal));
    }

    // ---------- Lista minorista / mayorista ----------
    document.querySelectorAll('.lista-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.lista-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            listaActual = btn.dataset.lista;

            // Recalcular precios de todo el carrito según la lista elegida
            carrito.forEach(item => {
                const p = PRODUCTOS.find(x => x.id === item.id);
                if (p) item.precioUnit = precioDe(p);
            });
            render();
        });
    });

    // ---------- Botones finales ----------
        $('btnLimpiar').addEventListener('click', () => {
        if (!carrito.length) return;
        if (confirm('¿Vaciar el carrito?')) {
            carrito.length = 0;
            descuentoGlobal = { modo: 'porcentaje', valor: 0 };
            localStorage.removeItem('pos_carrito');
            localStorage.removeItem('pos_descuento');
            render();
        }
    });

    // ---------- Modal de cobro ----------
    const modalCobro = $('modalCobro');
    const cobroObservaciones = $('cobroObservaciones');

    function abrirModalCobro() {
        if (!carrito.length) return;

        // --- Llenar resumen ---
        const subtotalBruto   = getSubtotalBruto();
        const descLineasTotal = getDescuentoLineasTotal();
        const subtotalNetoLin = subtotalBruto - descLineasTotal;
        const totalFinal      = calcularTotalConDescuento(subtotalNetoLin);
        const descGlobalMonto = subtotalNetoLin - totalFinal;

        const fmt = (n) => '$ ' + n.toLocaleString('es-AR', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });

        const totalUnid = carrito.reduce((s, x) => s + x.cantidad, 0);
        $('cobroItems').textContent    = `${carrito.length} art. · ${totalUnid} unid.`;
        $('cobroSubtotal').textContent = fmt(subtotalBruto);

        if (descLineasTotal > 0) {
            $('cobroFilaDescProd').style.display = 'flex';
            $('cobroDescProd').textContent = '- ' + fmt(descLineasTotal);
        } else {
            $('cobroFilaDescProd').style.display = 'none';
        }

        if (descGlobalMonto > 0) {
            $('cobroFilaDescGlobal').style.display = 'flex';
            $('cobroDescGlobal').textContent = '- ' + fmt(descGlobalMonto);
        } else {
            $('cobroFilaDescGlobal').style.display = 'none';
        }

        $('cobroTotal').textContent = fmt(totalFinal);

        // --- Limpiar observaciones ---
        cobroObservaciones.value = '';
        $('cobroContador').textContent = '0';

        modalCobro.classList.add('activo');
        setTimeout(() => cobroObservaciones.focus(), 60);
    }

    function cerrarModalCobro() {
        modalCobro.classList.remove('activo');
    }

    $('btnCobrar').addEventListener('click', abrirModalCobro);
    $('cerrarModalCobro').addEventListener('click', cerrarModalCobro);
    $('btnCancelarCobro').addEventListener('click', cerrarModalCobro);

    modalCobro.addEventListener('click', (e) => {
        if (e.target === modalCobro) cerrarModalCobro();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalCobro.classList.contains('activo')) {
            cerrarModalCobro();
        }
    });

    cobroObservaciones.addEventListener('input', () => {
        $('cobroContador').textContent = cobroObservaciones.value.length;
    });

    // ---------- Confirmar cobro ----------
    $('btnConfirmarCobro').addEventListener('click', async () => {
        if (!carrito.length) return;

        const boton = $('btnConfirmarCobro');
        boton.disabled = true;

        try {
            const respuesta = await fetch(URL_VENTAS, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({
                    cliente_id: Number($('clienteVenta').value),
                    lista: listaActual,
                    observaciones: cobroObservaciones.value.trim(),
                    descuento_global: {
                        modo: descuentoGlobal.modo,
                        valor: Number(descuentoGlobal.valor) || 0
                    },
                    items: carrito.map(item => ({
                        id: item.id,
                        cantidad: item.cantidad,

                        // Siempre enviamos el descuento como monto fijo ($)
                        descuento: Number(
                            ((item.descuentoMonto || 0) / item.cantidad).toFixed(2)
                        )
                    }))
                })
            });

            const resultado = await respuesta.json();

            if (!respuesta.ok) {
                const mensaje = Object.values(resultado.errors || {})[0]?.[0]
                    || resultado.message
                    || 'No se pudo registrar la venta.';

                throw new Error(mensaje);
            }

            carrito.length = 0;
            descuentoGlobal = {
                modo: 'porcentaje',
                valor: 0
            };

            localStorage.removeItem('pos_carrito');
            localStorage.removeItem('pos_descuento');

            cerrarModalCobro();
            render();

            alert(
                `Venta #${resultado.venta_id} registrada correctamente.\n` +
                `Total: $ ${Number(resultado.total).toLocaleString('es-AR')}`
            );

        } catch (error) {
            alert(error.message || 'No se pudo registrar la venta.');
        } finally {
            boton.disabled = false;
        }
    });

    // ---------- Modal de descuentos (global o por línea) ----------
    const modalDesc = $('modalDescuento');
    const descInput = $('descInput');
    const descPrefijo = $('descPrefijo');
    const descSufijo = $('descSufijo');
    const descLabel = $('descLabel');
    const descTotalActual = $('descTotalActual');
    const descNuevoTotal = $('descNuevoTotal');
    const modalDescTitulo = $('modalDescTitulo');

    /**
     * Abre el modal.
     * @param {number|null} itemId  null = descuento global; number = id de producto
     */
    function abrirModalDesc(itemId = null) {
        if (!carrito.length) {
            alert('Agregue productos antes de aplicar un descuento.');
            return;
        }

        itemDescTarget = itemId;

        if (itemId === null) {
            // Modo global
            modalDescTitulo.textContent = 'Descuento sobre el Total';
            modoDescTemporal = descuentoGlobal.modo;
            descInput.value = descuentoGlobal.valor || '';
        } else {
            // Modo línea
            if (itemId === null) {
                modalDescTitulo.textContent = 'Descuento sobre el Total';
                modoDescTemporal = descuentoGlobal.modo;
                descInput.value  = descuentoGlobal.valor || '';
            } else {
                const item = carrito.find(x => x.id === itemId);
                if (!item) return;
                modalDescTitulo.textContent = `Descuento: ${item.nombre}`;

                // Recuperar cómo lo había cargado el usuario
                modoDescTemporal = item.descuentoInputModo || 'monto';
                descInput.value  = item.descuentoInputValor ?? (item.descuentoMonto || '');
            }
        }

        actualizarModoDescUI();
        actualizarPreviewDesc();
        modalDesc.classList.add('activo');
        setTimeout(() => { descInput.focus(); descInput.select(); }, 50);
    }

    function cerrarModalDesc() {
        modalDesc.classList.remove('activo');
        itemDescTarget = null;
    }

    function actualizarModoDescUI() {
        document.querySelectorAll('.desc-toggle button').forEach(b => {
            b.classList.toggle('active', b.dataset.modo === modoDescTemporal);
            b.disabled = false;
            b.style.opacity = 1;
            b.style.cursor = 'pointer';
        });

        if (modoDescTemporal === 'porcentaje') {
            descLabel.textContent = 'Porcentaje de descuento (%)';
            descPrefijo.style.display = 'none';
            descSufijo.style.display = 'inline';
            descSufijo.textContent = '%';
            descInput.placeholder = 'Ej: 10';
            descInput.max = 100;
        } else {
            descLabel.textContent = 'Monto fijo de descuento ($)';
            descPrefijo.style.display = 'inline';
            descSufijo.style.display = 'none';
            descInput.placeholder = 'Ej: 500';
            descInput.removeAttribute('max');
        }
    }

    function actualizarPreviewDesc() {
        const valor = Number(descInput.value) || 0;
        const fmt = (n) => '$ ' + n.toLocaleString('es-AR', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });

        let base = 0;
        let nuevo = 0;

        if (itemDescTarget === null) {
            // Global → base = subtotal ya neto de descuentos de línea
            base = getSubtotalConDescLineas();
            if (modoDescTemporal === 'porcentaje') {
                const pct = Math.min(Math.max(valor, 0), 100);
                nuevo = base * (1 - pct / 100);
            } else {
                nuevo = Math.max(0, base - Math.max(valor, 0));
            }
        } else {
            // Línea → base = bruto de la línea
            const item = carrito.find(x => x.id === itemDescTarget);
            if (!item) return;
            base = brutoLinea(item);
            if (modoDescTemporal === 'porcentaje') {
                const pct = Math.min(Math.max(valor, 0), 100);
                nuevo = base * (1 - pct / 100);
            } else {
                nuevo = Math.max(0, base - Math.max(valor, 0));
            }
        }

        descTotalActual.textContent = fmt(base);
        descNuevoTotal.textContent  = fmt(nuevo);
    }

    // Eventos del modal
    // Abrir descuento GLOBAL
    $('btnDescuentos').addEventListener('click', () => abrirModalDesc(null));

    // Abrir descuento POR LÍNEA (delegación de eventos)
    $('carritoBody').addEventListener('click', (e) => {
        const btn = e.target.closest('[data-desc-linea]');
        if (!btn) return;
        abrirModalDesc(Number(btn.dataset.descLinea));
    });

    $('cerrarModalDesc').addEventListener('click', cerrarModalDesc);
    $('btnCancelarDesc').addEventListener('click', cerrarModalDesc);

    modalDesc.addEventListener('click', (e) => {
        if (e.target === modalDesc) cerrarModalDesc();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalDesc.classList.contains('activo')) {
            cerrarModalDesc();
        }
    });

    document.querySelectorAll('.desc-toggle button').forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.disabled) return;
            modoDescTemporal = btn.dataset.modo;
            descInput.value = '';
            actualizarModoDescUI();
            actualizarPreviewDesc();
            descInput.focus();
        });
    });

    descInput.addEventListener('input', actualizarPreviewDesc);

    descInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('btnAplicarDesc').click();
        }
    });

    $('btnAplicarDesc').addEventListener('click', () => {
        const valor = Number(descInput.value) || 0;

        if (modoDescTemporal === 'porcentaje' && (valor < 0 || valor > 100)) {
            alert('El porcentaje debe estar entre 0 y 100.');
            return;
        }
        if (modoDescTemporal === 'monto' && valor < 0) {
            alert('El monto no puede ser negativo.');
            return;
        }

        if (itemDescTarget === null) {
            // ---- GLOBAL ----
            descuentoGlobal = { modo: modoDescTemporal, valor };
            localStorage.setItem('pos_descuento', JSON.stringify(descuentoGlobal));
        } else {
            // ---- LÍNEA ----
            const item = carrito.find(x => x.id === itemDescTarget);
            if (!item) return;

            let monto = 0;

            const precioUnitario = item.precioUnit;

            if (modoDescTemporal === 'porcentaje') {
                monto = (item.cantidad * precioUnitario) * (valor / 100);
            } else {
                monto = Math.min(valor, item.cantidad * precioUnitario);
            }

            // Guardamos SIEMPRE como monto fijo (canónico, va a la BBDD)
            item.descuentoMonto      = monto;
            // Y guardamos cómo lo cargó el usuario, solo para la UI
            item.descuentoInputModo  = modoDescTemporal;
            item.descuentoInputValor = valor;
        }

        cerrarModalDesc();
        render();
    });

    $('btnCerrarCaja').addEventListener('click', () => {
        alert('Cierre de caja — a implementar');
    });
    // Quitar descuento desde el footer
    $('quitarDescuento').addEventListener('click', () => {
        descuentoGlobal = { modo: 'porcentaje', valor: 0 };
        localStorage.removeItem('pos_descuento');
        render();
    });
    // ---------- Recuperar carrito al cargar ----------
    (function restaurar() {
        const guardado = localStorage.getItem('pos_carrito');
        if (guardado) {
            try {
                const items = JSON.parse(guardado);
                items.forEach(x => {
                    // Compatibilidad con la estructura anterior
                    if (typeof x.descuentoMonto === 'undefined') {
                        // Si venía con `descuento` como %, lo convertimos a monto fijo
                        const pct = Number(x.descuento) || 0;
                        const precioUnitario = Number(x.precioUnit) || 0;
                        x.descuentoMonto = precioUnitario * (pct / 100);
                        x.descuentoInputModo  = pct > 0 ? 'porcentaje' : null;
                        x.descuentoInputValor = pct > 0 ? pct : null;
                        delete x.descuento;
                    }
                    // Defaults defensivos
                    x.descuentoMonto      = Number(x.descuentoMonto) || 0;
                    x.descuentoInputModo  ??= null;
                    x.descuentoInputValor ??= null;
                    carrito.push(x);
                });
            } catch (e) {}
        }

        const descGuardado = localStorage.getItem('pos_descuento');
        if (descGuardado) {
            try { descuentoGlobal = JSON.parse(descGuardado); } catch (e) {}
        }

        render();
    })();
</script>
@endpush
