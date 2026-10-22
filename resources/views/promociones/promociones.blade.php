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

                <div style="display: flex; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <!-- Columna Izquierda: Productos con Cantidad -->
                    <div style="flex: 2;">
                        <label style="display:block; margin-bottom:0.5rem; font-size: 0.85rem; font-weight: 600;">Agregar
                            Productos a la promo</label>
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <input type="text" id="input-nuevo-producto" class="clay-input" placeholder="Buscar por nombre o código..."
                                autocomplete="off" aria-label="Buscar producto para la promoción"
                                style="flex: 1;">
                            <!-- Nuevo input de cantidad -->
                            <input type="number" id="input-cantidad-producto" class="clay-input" value="1" min="1"
                                style="width: 70px;" title="Cantidad">
                            <button type="button" class="clay-btn-primary" onclick="agregarProductoArray()"
                                style="padding: 0 1rem; font-size: 1.2rem;">+</button>
                        </div>
                        <div id="sugerencias-productos-promo"
                            style="display: none; flex-direction: column; max-height: 180px; overflow-y: auto; margin: -0.25rem 0 0.5rem; border: 1px solid #cbd5e1; border-radius: 8px; background: white;">
                        </div>
                        <div id="lista-productos-tags"
                            style="display: flex; flex-direction: column; gap: 0.4rem; min-height: 40px; padding: 0.5rem; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1;">
                        </div>
                        <input type="hidden" id="promo-productos-oculto" required>
                    </div>

                    <!-- Columna Derecha: Precio -->
                    <div style="flex: 1;">
                        <label style="display:block; margin-bottom:0.5rem; font-size: 0.85rem; font-weight: 600;">Tipo y
                            Valor</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <select id="promo-tipo-valor" class="clay-input" style="width: 80px;">
                                <option value="$">$</option>
                                <option value="%">%</option>
                            </select>
                            <input type="text" id="promo-valor-numero" class="clay-input" required placeholder="Ej: 10.500"
                                style="flex: 1;">
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn-action btn-danger" onclick="cerrarModal()"
                        style="min-height: 40px; padding: 0 1.5rem;">Cancelar</button>
                    <button type="submit" class="clay-btn-primary"
                        style="min-height: 40px; border: none; padding: 0 2rem;">Guardar Promoción</button>
                </div>
            </form>
        </div>
    </div>

    @vite(['resources/js/promociones.js'])
@endsection