@extends('layouts.app')

@section('title', 'Promociones')

@section('content')
    <div class="promociones-container">

        <!-- Header principal -->
        <header class="clay-card" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <h1 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #1e293b;">PROMOS</h1>
                <div style="width: 1px; height: 20px; background-color: #cbd5e1;"></div>
                <form onsubmit="event.preventDefault();" style="margin: 0; display: flex; align-items: center; gap: 1rem;">
                    <input type="text" id="input-busqueda" class="clay-input" placeholder="Filtrar..."
                        aria-label="Filtrar promociones" style="width: 160px;">
                    <!-- Nuevo Checkbox para el Historial -->
                    <label
                        style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: #64748b; cursor: pointer; font-weight: 600;">
                        <input type="checkbox" id="check-historial" style="cursor: pointer;">
                        Ver historial (Dadas de baja)
                    </label>
                </form>
            </div>
            <div>
                <button type="button" class="clay-btn-primary" onclick="abrirModal()">+ NUEVA PROMO</button>
            </div>
        </header>

        <!-- Tabla 1: Listado de Promociones -->
        <section class="clay-card" style="padding: 0; overflow: hidden;">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 90px;">CÓDIGO</th>
                        <th>NOMBRE DE LA PROMO</th>
                        <th>VIGENCIA</th>
                        <th class="text-center" style="width: 140px;">PRECIO/DESC.</th>
                        <th class="text-right" style="width: 340px;">ACCIONES</th>
                    </tr>
                </thead>
                <tbody id="tabla-promociones-body">
                    <!-- Filas inyectadas por JavaScript -->
                </tbody>
            </table>
        </section>

        <!-- Tabla 2: Productos de la promo seleccionada -->
        <section class="clay-card" style="padding: 0; overflow: hidden;" id="seccion-detalle-promo">
            <header
                style="display: flex; justify-content: space-between; align-items: center; padding: 0.8rem 1rem; background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <span style="font-size: 0.75rem; font-weight: bold; color: #94a3b8; text-transform: uppercase;">
                    PRODUCTOS DE LA PROMO:
                    <span id="titulo-productos-promo"
                        style="color: #1e293b; text-transform: none; font-size: 0.9rem; font-weight: 700;">
                        Seleccioná una promo
                    </span>
                </span>
                <span id="badge-detalle-descuento"
                    style="color: var(--brand-blue); font-weight: 700; font-size: 0.85rem;"></span>
            </header>

            <div id="contenedor-tabla-detalle">
                <p style="padding: 1rem; color: #94a3b8; font-size: 0.9rem; margin: 0;">Hacé clic en una promoción para ver
                    sus detalles y productos asociados.</p>
            </div>
        </section>

    </div>

    <!-- MODAL PARA AGREGAR/EDITAR PROMO -->
    <div id="modal-promo"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 50; box-sizing: border-box; overflow: hidden;">
        <div class="clay-card"
            style="width: 95%; max-width: 800px; padding: 2.5rem; background: white; border-radius: 15px; max-height: 90vh; overflow-y: auto; box-sizing: border-box;">
            <h2 id="modal-title" style="margin-top: 0; margin-bottom: 1.5rem; color: #1e293b; font-size: 1.5rem;">Nueva
                Promoción</h2>

            <form id="form-promocion">
                <input type="hidden" id="promo-id">

                <div style="display: flex; gap: 1.5rem; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <label style="display:block; margin-bottom:0.5rem; font-size: 0.85rem; font-weight: 600;">Nombre de
                            la promoción</label>
                        <input type="text" id="promo-nombre" class="clay-input" required
                            style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label
                        style="display:block; margin-bottom:0.5rem; font-size: 0.85rem; font-weight: 600;">Descripción</label>
                    <textarea id="promo-descripcion" class="clay-input" rows="2"
                        style="width: 100%; box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                <div style="display: flex; gap: 1.5rem; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <label style="display:block; margin-bottom:0.5rem; font-size: 0.85rem; font-weight: 600;">Fecha de
                            Inicio</label>
                        <input type="date" id="promo-inicio" class="clay-input" required
                            style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display:block; margin-bottom:0.5rem; font-size: 0.85rem; font-weight: 600;">Fecha
                            Final</label>
                        <input type="date" id="promo-fin" class="clay-input" required
                            style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 1.5rem 0;">

                <section style="margin-bottom: 1.5rem;">
                    <h3 style="margin: 0 0 0.75rem; font-size: 0.95rem; color: #1e293b;">Productos de la promo (<span
                            id="contador-productos-promo">0</span>)</h3>
                    <div style="display: flex; align-items: flex-end; gap: 0.75rem; margin-bottom: 0.35rem;">
                        <div style="flex: 1;">
                            <label for="input-nuevo-producto"
                                style="display:block; margin-bottom:0.35rem; font-size: 0.85rem; font-weight: 600;">Producto</label>
                            <input type="text" id="input-nuevo-producto" class="clay-input"
                                placeholder="Buscar por nombre o código…" autocomplete="off"
                                aria-label="Buscar producto para la promoción" aria-controls="sugerencias-productos-promo"
                                style="width: 100%; box-sizing: border-box;">
                        </div>
                        <div>
                            <label for="input-cantidad-producto"
                                style="display:block; margin-bottom:0.35rem; font-size: 0.85rem; font-weight: 600;">Cant.</label>
                            <input type="number" id="input-cantidad-producto" class="clay-input" value="1"
                                min="1" max="9999" aria-label="Cantidad del producto" style="width: 76px; box-sizing: border-box;">
                        </div>
                        <button type="button" id="btn-agregar-producto" class="clay-btn-primary"
                            aria-label="Agregar producto seleccionado" title="Agregar producto"
                            style="padding: 0 1rem; font-size: 1.2rem;">+</button>
                    </div>
                    <p id="error-productos-promo" role="alert"
                        style="display: none; margin: 0 0 0.5rem; color: #dc2626; font-size: 0.82rem;"></p>
                    <div id="sugerencias-productos-promo" role="listbox" aria-label="Resultados de productos"
                        style="display: none; flex-direction: column; max-height: 180px; overflow-y: auto; margin: 0 0 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; background: white;">
                    </div>
                    <div id="lista-productos-tags"
                        style="display: flex; flex-direction: column; gap: 0.4rem; max-height: 240px; min-height: 52px; overflow-y: auto; padding: 0.5rem; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1;">
                    </div>
                    <input type="hidden" id="promo-productos-oculto">
                </section>

                <section style="margin-bottom: 1.25rem;">
                    <h3 style="margin: 0 0 0.75rem; font-size: 0.95rem; color: #1e293b;">Descuento</h3>
                    <div style="display: flex; align-items: center; gap: 0.5rem; max-width: 440px;">
                        <select id="promo-tipo-valor" class="clay-input" aria-label="Tipo de descuento" style="width: 90px;">
                            <option value="$">$</option>
                            <option value="%">%</option>
                        </select>
                        <input type="text" id="promo-valor-numero" class="clay-input" required
                            placeholder="Valor del descuento" aria-label="Valor del descuento" style="width: 150px;">
                        <span style="font-size: 0.85rem; color: #64748b;">sobre el total de los productos</span>
                    </div>
                    <p id="error-descuento-promo" role="alert" aria-live="polite"
                        style="display: none; margin: 0.5rem 0 0; color: #dc2626; font-size: 0.82rem;"></p>
                </section>

                <section aria-label="Resumen de la promoción"
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; padding: 0.9rem 1rem; margin-bottom: 1.5rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <div>
                        <span style="display: block; color: #64748b; font-size: 0.8rem;">Precio normal</span>
                        <strong id="resumen-precio-normal">$ 0,00</strong>
                    </div>
                    <div>
                        <span style="display: block; color: #64748b; font-size: 0.8rem;">Descuento</span>
                        <strong id="resumen-descuento">−$ 0,00</strong>
                    </div>
                    <div>
                        <span style="display: block; color: #64748b; font-size: 0.8rem;">Precio de la promo</span>
                        <strong id="resumen-precio-promo" style="color: var(--brand-blue);">$ 0,00</strong>
                    </div>
                </section>

                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn-action btn-danger" onclick="cerrarModal()"
                        style="min-height: 40px; padding: 0 1.5rem;">Cancelar</button>
                    <button type="submit" class="clay-btn-primary"
                        style="min-height: 40px; border: none; padding: 0 2rem;">Guardar Promoción</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/promociones.js') }}" defer></script>
    @endpush
@endsection
