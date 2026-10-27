@extends('layouts.app')

@section('title', 'Historial de Ventas')

@push('styles')
<style>
    /* ---------- Cabecera ---------- */
    .hv-top {
        display: flex; justify-content: space-between; align-items: center; gap: 1rem;
    }
    .hv-titulo {
        display: flex; align-items: center; gap: .75rem;
        font-size: 1.35rem; font-weight: 800; letter-spacing: .3px;
    }
    .hv-contador {
        font-size: .9rem; color: var(--muted); font-weight: 600;
        background: #F1F5F9; padding: .45rem .9rem; border-radius: 999px;
    }

    /* ---------- Filtros ---------- */
    .hv-filtros-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: .85rem;
    }
    .hv-filtros-opciones { display: flex; align-items: center; gap: 1rem; }
    .hv-toggle-canceladas {
        display: inline-flex; align-items: center; gap: .45rem;
        color: var(--muted); font-size: .85rem; font-weight: 600; cursor: pointer;
    }
    .hv-toggle-canceladas input { width: 16px; height: 16px; min-height: 0; accent-color: var(--primary); cursor: pointer; }
    .hv-filtros-titulo {
        display: flex; align-items: center; gap: .5rem;
        font-size: .85rem; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: .5px;
    }
    .hv-limpiar {
        background: none; border: none; color: var(--primary);
        font-family: inherit; font-size: .85rem; font-weight: 600;
        cursor: pointer; padding: .25rem .5rem; border-radius: 8px;
        text-decoration: none;
    }
    .hv-limpiar:hover { background: #EFF6FF; }

    .hv-filtros-grid {
        display: grid; grid-template-columns: 1.2fr repeat(5, 1fr); gap: .75rem;
    }
    .hv-campo { display: flex; flex-direction: column; gap: .35rem; min-width: 0; }
    .hv-campo label {
        font-size: .72rem; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: .4px;
    }
    .hv-campo input, .hv-campo select {
        width: 100%; min-height: 44px; padding: 0 .75rem;
        border: 1px solid var(--border); border-radius: 12px;
        background: #fff; font-family: inherit; font-size: .9rem; color: var(--text);
    }
    .hv-campo input:focus, .hv-campo select:focus {
        outline: 4px solid var(--primary); outline-offset: 2px; border-color: var(--primary);
    }

    /* ---------- KPIs ---------- */
    .hv-resumen { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
    .hv-kpi { display: flex; flex-direction: column; gap: .35rem; padding: 1rem 1.25rem; }
    .hv-kpi .label {
        font-size: .72rem; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: .5px;
    }
    .hv-kpi .valor {
        font-size: 1.55rem; font-weight: 800;
        font-variant-numeric: tabular-nums; line-height: 1.1;
    }
    .hv-kpi .valor.azul    { color: var(--primary); }
    .hv-kpi .valor.naranja { color: #EA580C; }
    .hv-kpi .valor.rojo    { color: var(--danger); }

    /* ---------- Tabla ---------- */
    .hv-tabla { padding: 0; overflow: hidden; display: flex; flex-direction: column; min-height: 320px; }
    .hv-tabla-header {
        display: grid;
        grid-template-columns: 220px 120px 1.3fr 1.1fr 120px 130px 120px 40px;
        gap: .5rem; padding: .9rem 1.25rem;
        font-size: .72rem; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: .5px;
        border-bottom: 1px solid var(--border);
    }
    .hv-tabla-body { flex: 1; overflow-y: auto; }

    .hv-grupo { border-bottom: 1px solid #F1F5F9; }

    .hv-row {
        display: grid;
        grid-template-columns: 220px 120px 1.3fr 1.1fr 120px 130px 120px 40px;
        gap: .5rem; align-items: center;
        padding: .85rem 1.25rem; font-size: .92rem;
        cursor: pointer; transition: background .12s;
    }
    .hv-row:hover { background: #F8FAFC; }
    .hv-row.abierta { background: #EFF6FF; }
    .hv-row .num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; }
    .hv-row .factura { font-weight: 700; color: var(--primary); }
    .hv-row .usuario {
        font-size: .85rem; color: var(--muted);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .hv-row .fecha { display: flex; flex-direction: column; line-height: 1.25; }
    .hv-row .fecha .hora { font-size: .78rem; color: var(--muted); }

    .hv-toggle {
        justify-self: center; background: none; border: 1px solid var(--border);
        width: 30px; height: 30px; border-radius: 8px; cursor: pointer;
        color: var(--muted); font-size: .8rem; line-height: 1;
        display: grid; place-items: center; transition: all .15s;
    }
    .hv-toggle:hover { background: #EFF6FF; border-color: var(--primary); color: var(--primary); }
    .hv-row.abierta .hv-toggle { transform: rotate(180deg); background: var(--primary); color: #fff; border-color: var(--primary); }

    /* ---------- Badges ---------- */
    .badge {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .65rem; border-radius: 999px;
        font-size: .78rem; font-weight: 700; white-space: nowrap;
    }
    .badge-cc             { background: #FEE2E2; color: #B91C1C; border: 1px solid #FECACA; }
    .badge-efectivo       { background: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; }
    .badge-transferencia  { background: #E0F2FE; color: #0369A1; border: 1px solid #BAE6FD; }
    .badge-tarjeta        { background: #F3E8FF; color: #7E22CE; border: 1px solid #E9D5FF; }
    .badge-vacio          { color: #CBD5E1; font-weight: 600; }

    /* Estados */
    .badge-estado { font-size: .72rem; }
    .estado-pendiente  { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .estado-confirmada { background: #DBEAFE; color: #1E40AF; border: 1px solid #BFDBFE; }
    .estado-pagada     { background: #DCFCE7; color: #166534; border: 1px solid #86EFAC; }
    .estado-facturada  { background: #E0E7FF; color: #3730A3; border: 1px solid #C7D2FE; }
    .estado-cancelada  { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }

    /* ---------- Panel de detalle expandible ---------- */
    .hv-detalle {
        background: #F8FAFC;
        border-top: 1px dashed var(--border);
        padding: 1rem 1.25rem 1.25rem 3rem;
        display: none;
    }
    .hv-grupo.abierto .hv-detalle { display: block; }

    .hv-detalle-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: .75rem;
        font-size: .8rem; color: var(--muted); font-weight: 600;
        text-transform: uppercase; letter-spacing: .5px;
    }
    .hv-detalle-header .obs {
        text-transform: none; letter-spacing: 0;
        font-weight: 500; font-style: italic;
        color: #64748B; max-width: 60%;
        text-align: right;
    }
    .hv-detalle-acciones {
        display: flex;
        justify-content: flex-end;
        margin-top: .75rem;
    }

    .hv-detalle-tabla {
        width: 100%; border-collapse: collapse; background: #fff;
        border-radius: 12px; overflow: hidden;
        box-shadow: 0 2px 6px rgba(30,58,138,.05);
    }
    .hv-detalle-tabla th {
        text-align: left; font-size: .72rem; color: var(--muted);
        text-transform: uppercase; letter-spacing: .5px;
        padding: .55rem .85rem; background: #F1F5F9;
        border-bottom: 1px solid var(--border);
    }
    .hv-detalle-tabla th.num, .hv-detalle-tabla td.num { text-align: right; }
    .hv-detalle-tabla td {
        padding: .55rem .85rem; font-size: .88rem;
        border-bottom: 1px solid #F1F5F9;
    }
    .hv-detalle-tabla tr:last-child td { border-bottom: none; }
    .hv-detalle-tabla td.num { font-variant-numeric: tabular-nums; }

    .hv-detalle-vacio {
        padding: 1rem; text-align: center;
        font-size: .88rem; color: var(--muted);
        background: #fff; border-radius: 12px;
    }

    /* ---------- Editor de venta ---------- */
    .hv-editor {
        margin: 1rem 0;
        padding: 1rem;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: 0 3px 10px rgba(30, 58, 138, .06);
    }
    .hv-editor-header {
        display: flex; justify-content: space-between; align-items: center; gap: 1rem;
        margin-bottom: 1rem; padding-bottom: .75rem;
        border-bottom: 1px solid var(--border);
    }
    .hv-editor-title { margin: 0; font-size: 1rem; font-weight: 800; color: var(--text); }
    .hv-editor-label {
        display: flex; align-items: center; gap: .5rem;
        font-size: .82rem; font-weight: 700; color: var(--muted);
    }
    .hv-editor-input {
        min-height: 40px; padding: .45rem .65rem;
        border: 1px solid var(--border); border-radius: 10px;
        background: #fff; color: var(--text); font: inherit;
    }
    .hv-editor-input:focus {
        outline: 3px solid var(--primary); outline-offset: 1px; border-color: var(--primary);
    }
    .hv-editor-tabla-wrap { overflow-x: auto; border: 1px solid var(--border); border-radius: 12px; }
    .hv-editor-tabla { min-width: 760px; }
    .hv-editor-tabla input { width: 90px; }
    .hv-editor-tabla [data-campo="cantidad"] { width: 76px; }
    .hv-editor-tabla [data-campo="descuento"] { width: 100px; }
    .hv-editor-vacio { padding: 1rem; text-align: center; color: var(--muted); }
    .hv-editor-agregar {
        display: flex; align-items: end; flex-wrap: wrap; gap: .75rem;
        margin-top: 1rem; padding: 1rem;
        background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px;
    }
    .hv-editor-agregar .hv-editor-label { flex: 1 1 320px; flex-direction: column; align-items: stretch; }
    .hv-editor-agregar [data-nuevo-producto] { width: 100%; }
    .hv-editor-cantidad { width: 100px; }
    .hv-editor-resumen {
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;
        margin-top: 1rem; padding: .85rem 1rem;
        background: #EFF6FF; border-radius: 12px;
    }
    .hv-editor-resumen .hv-editor-label { align-items: center; }
    .hv-editor-descuento { width: 130px; }
    .hv-editor-total { font-size: 1.05rem; font-weight: 800; color: var(--primary); }
    .hv-editor-total small { display: block; margin-top: .15rem; font-size: .78rem; font-weight: 500; color: var(--muted); }
    .hv-editor-error {
        margin: .75rem 0 0; padding: .7rem .85rem;
        color: #991B1B; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 10px;
    }
    .hv-editor-actions { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
    .hv-editor-remove { padding: .35rem .65rem; }
    .hv-anulacion-form {
        margin-top: 1rem; padding: 1rem;
        background: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px;
    }
    .hv-anulacion-form[hidden] { display: none; }
    .hv-anulacion-form label {
        display: block; margin-bottom: .45rem;
        font-size: .85rem; font-weight: 700; color: #991B1B;
    }
    .hv-anulacion-form textarea {
        display: block; width: 100%; min-height: 84px; resize: vertical;
        padding: .65rem .75rem; border: 1px solid #FCA5A5; border-radius: 10px;
        background: #fff; color: var(--text); font: inherit;
    }
    .hv-anulacion-form textarea:focus {
        outline: 3px solid #FECACA; outline-offset: 1px; border-color: #EF4444;
    }
    .hv-anulacion-ayuda { display: block; margin-top: .35rem; font-size: .78rem; color: #7F1D1D; }
    .hv-anulacion-acciones { display: flex; justify-content: flex-end; gap: .5rem; margin-top: .75rem; }

    /* ---------- Responsive ---------- */
    @media (max-width: 1100px) {
        .hv-filtros-grid { grid-template-columns: 1fr 1fr; }
        .hv-resumen { grid-template-columns: 1fr 1fr; }
        .hv-tabla { overflow-x: auto; }
        .hv-tabla-header, .hv-row { min-width: 1100px; }
        .hv-detalle { min-width: 1100px; }
        .hv-editor { min-width: 0; }
    }
    @media (max-width: 720px) {
        .hv-filtros-grid { grid-template-columns: 1fr; }
        .hv-resumen { grid-template-columns: 1fr; }
        .hv-editor-header, .hv-editor-resumen { align-items: stretch; flex-direction: column; }
        .hv-editor-actions { justify-content: stretch; }
        .hv-editor-actions .btn-action { flex: 1; justify-content: center; }
    }
    .hv-row .factura {
        font-weight: 700;
        color: var(--primary);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
        max-width: 100%;
    }
</style>
@endpush

@section('content')

    {{-- Cabecera --}}
    <div class="clay-card hv-top">
        <div class="hv-titulo">
            <span class="icono">🧾</span>
            HISTORIAL DE VENTAS
        </div>
        <span class="hv-contador" id="contadorVentas">{{ $kpis['cantidad'] }} ventas</span>
    </div>

    {{-- Filtros --}}
    <form class="clay-card" id="formFiltros">
        <div class="hv-filtros-header">
            <span class="hv-filtros-titulo">🔽 Filtros</span>
            <div class="hv-filtros-opciones">
                <label class="hv-toggle-canceladas" for="fCanceladas">
                    <input type="checkbox" id="fCanceladas">
                    Ver ventas canceladas
                </label>
                <button type="button" class="hv-limpiar" id="limpiarFiltros">Limpiar filtros</button>
            </div>
        </div>

        <div class="hv-filtros-grid">
            <div class="hv-campo">
                <label for="fTexto">Buscar (cliente / #)</label>
                <input type="text" id="fTexto" name="texto" placeholder="Texto...">
            </div>
            <div class="hv-campo">
                <label for="fCliente">👤 Cliente</label>
                <select id="fCliente" name="cliente">
                    <option value="">Todos</option>
                    @foreach ($clientesFiltro as $c)
                        <option value="{{ $c['id'] }}">
                            {{ $c['nombre'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="hv-campo">
                <label for="fProducto">📦 Producto</label>
                <select id="fProducto" name="producto">
                    <option value="">Todos</option>
                @foreach ($productosFiltro as $p)
                    <option value="{{ $p['id_producto'] }}">
                        {{ $p['nombre'] }}
                    </option>
                @endforeach
                </select>
            </div>
            <div class="hv-campo">
                <label for="fLocalidad">📍 Localidad</label>
                <select id="fLocalidad" name="localidad">
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="hv-campo">
                <label for="fDesde">📅 Desde</label>
                <input type="date" id="fDesde" name="desde">
            </div>
            <div class="hv-campo">
                <label for="fHasta">📅 Hasta</label>
                <input type="date" id="fHasta" name="hasta">
            </div>
        </div>
    </form>

    {{-- KPIs --}}
    <div class="hv-resumen">
        <div class="clay-card hv-kpi">
            <span class="label">Total vendido</span>
            <span class="valor azul" data-kpi="total-vendido">$ {{ number_format($kpis['total_vendido'], 2, ',', '.') }}</span>
        </div>
        <div class="clay-card hv-kpi">
            <span class="label">Descuentos otorgados</span>
            <span class="valor naranja" data-kpi="descuentos">$ {{ number_format($kpis['descuentos'], 2, ',', '.') }}</span>
        </div>
        <div class="clay-card hv-kpi">
            <span class="label">En cuenta corriente</span>
            <span class="valor rojo" data-kpi="en-cc">$ {{ number_format($kpis['en_cc'], 2, ',', '.') }}</span>
        </div>
        <div class="clay-card hv-kpi">
            <span class="label">Ventas realizadas</span>
            <span class="valor azul" data-kpi="cantidad">{{ $kpis['cantidad'] }}</span>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="clay-card hv-tabla">
        <div class="hv-tabla-header">
            <span>Factura</span>
            <span>Fecha</span>
            <span>Cliente</span>
            <span>Usuario</span>
            <span style="text-align:right;">Total</span>
            <span>Método de pago</span>
            <span>Estado</span>
            <span></span>
        </div>

        <div class="hv-tabla-body">
            @foreach ($ventas as $v)
                <div class="hv-grupo" data-venta-id="{{ $v['id'] }}">
                    <div class="hv-row" data-toggle-detalle>
                        <span class="factura" title="{{ $v['factura'] }}">{{ $v['factura'] }}</span>

                        <span class="fecha">
                            {{ $v['fecha'] }}
                        </span>

                        <span>{{ $v['cliente'] }}</span>

                        <span class="usuario" title="{{ $v['usuario'] }}">{{ $v['usuario'] }}</span>

                        <span class="num">$ {{ number_format($v['total'], 2, ',', '.') }}</span>

                        <span>
                            @php
                                $badgeMetodo = match($v['metodo']) {
                                    'Cuenta Corriente' => 'badge-cc',
                                    'Efectivo'         => 'badge-efectivo',
                                    'Transferencia'    => 'badge-transferencia',
                                    default            => 'badge-tarjeta',
                                };
                            @endphp
                            <span class="badge {{ $badgeMetodo }}">{{ $v['metodo'] }}</span>
                        </span>

                        <span>
                            <span class="badge badge-estado estado-{{ $v['estado'] }}">
                                {{ ucfirst($v['estado']) }}
                            </span>
                        </span>

                        <button type="button" class="hv-toggle" aria-label="Ver detalle">▾</button>
                    </div>

                    <div class="hv-detalle">
                        <div class="hv-detalle-header">
                            <span>Productos vendidos ({{ count($v['detalle']) }})</span>

                            @if (!empty($v['observaciones']))
                                <span class="obs">📝 {{ $v['observaciones'] }}</span>
                            @endif
                        </div>

                        {{-- Editor de "Modificar" (se arma con JS al apretar el botón; ver abrirEditor() abajo) --}}
                        <div data-editor="{{ $v['id'] }}" hidden></div>

                        @if (count($v['detalle']))
                            <table class="hv-detalle-tabla">
                                <thead>
                                    <tr>
                                        <th style="width:90px;">Código</th>
                                        <th>Producto</th>
                                        <th class="num" style="width:90px;">Cantidad</th>
                                        <th class="num" style="width:130px;">Precio unit.</th>
                                        <th class="num" style="width:110px;">Descuento</th>
                                        <th class="num" style="width:130px;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($v['detalle'] as $d)
                                        <tr>
                                            <td>{{ $d['codigo'] }}</td>
                                            <td>{{ $d['producto'] }}</td>
                                            <td class="num">{{ $d['cantidad'] }}</td>
                                            <td class="num">$ {{ number_format($d['precio_unitario'], 2, ',', '.') }}</td>
                                            <td class="num">
                                                @if ($d['descuento'] > 0)
                                                    - $ {{ number_format($d['descuento'], 2, ',', '.') }}
                                                @else
                                                    <span class="badge-vacio">—</span>
                                                @endif
                                            </td>
                                            <td class="num">$ {{ number_format($d['subtotal'], 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="hv-detalle-vacio">Esta venta no tiene detalle cargado.</div>
                        @endif

                        {{-- Las acciones se muestran según los flags que manda VentaController@historial. --}}
                        <div class="hv-detalle-acciones">
                            <div class="actions-group">
                                @if ($v['puede_modificar'])
                                    <button type="button" class="btn-action btn-edit" data-accion="modificar" data-id="{{ $v['id'] }}">
                                        <span aria-hidden="true"></span> Modificar
                                    </button>
                                @endif
                                @if ($v['puede_eliminar'])
                                    <button type="button" class="btn-action btn-danger" data-accion="eliminar" data-id="{{ $v['id'] }}">
                                        <span aria-hidden="true"></span> Eliminar
                                    </button>
                                @endif
                                @if ($v['puede_restaurar'])
                                    <button type="button" class="btn-action btn-cart" data-accion="restaurar" data-id="{{ $v['id'] }}">
                                        <span aria-hidden="true">↻</span> Restaurar
                                    </button>
                                @endif
                                {{-- Cambiar estado se conserva aquí, pero está deshabilitado por ahora.
                                @if (! $v['anulada'] && $v['estado'] !== 'facturada')
                                    <button type="button" data-accion="estado" data-id="{{ $v['id'] }}" data-estado="{{ $v['estado'] }}">Cambiar estado</button>
                                @endif --}}
                            </div>
                        </div>
                        @if ($v['puede_eliminar'])
                            <form class="hv-anulacion-form" data-form-anulacion="{{ $v['id'] }}" hidden>
                                <label for="motivo-anulacion-{{ $v['id'] }}">Motivo de la anulación</label>
                                <textarea
                                    id="motivo-anulacion-{{ $v['id'] }}"
                                    name="motivo"
                                    required
                                    minlength="5"
                                    maxlength="150"
                                    placeholder="Explicá por qué se anula esta venta (mínimo 5 caracteres)"
                                ></textarea>
                                <span class="hv-anulacion-ayuda">Entre 5 y 150 caracteres.</span>
                                <div class="hv-anulacion-acciones">
                                    <button type="button" class="btn-action btn-cart" data-cancelar-anulacion>Cancelar</button>
                                    <button type="submit" class="btn-action btn-danger">Confirmar anulación</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
            <div id="sinResultados" style="padding:2rem; text-align:center; color:var(--muted);" @if ($ventas->isNotEmpty()) hidden @endif>
                No se encontraron ventas con los filtros aplicados
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>

    // ---------- Auto-submit de filtros con debounce ----------
    const form = document.getElementById('formFiltros');
    const fTexto = document.getElementById('fTexto');
    let timer = null;

    fTexto.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => form.submit(), 500);
    });

    ['fCliente', 'fProducto', 'fDesde', 'fHasta'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => form.submit());
    });

    // ---------- BOTONES DE PRUEBA: modificar / eliminar / restaurar / cambiar estado ----------
    // Llaman a la API de app/Modules/Ventas/Controllers/VentaApiController.php (ver docs/VENTAS-BACKEND.md).
    // Muestran la respuesta del back en un alert y recargan la página.
    async function llamarApi(metodo, url, body) {
        const respuesta = await fetch(url, {
            method: metodo,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: body ? JSON.stringify(body) : undefined,
        });
        const json = await respuesta.json().catch(() => ({}));

        // 422 trae "errors" campo por campo; 404/409/500/503 traen "message".
        const errores = json.errors ? '\n\n' + Object.values(json.errors).flat().join('\n') : '';
        alert(`HTTP ${respuesta.status}\n${json.message ?? ''}${errores}`);

        if (respuesta.ok) location.reload();
    }

    // ---------- EDITOR DE "MODIFICAR" ----------
    // Permite cambiar cantidades y descuentos, QUITAR productos, AGREGAR productos
    // del catálogo, elegir la lista de precios y el descuento global.
    // El total que se ve es una ESTIMACIÓN: el total real lo calcula el back
    // (CalculadoraVenta) con los precios del servidor al guardar.
    //
    // Datos que usa:
    //   GET /api/ventas/{id}         → la venta con sus productos (id_producto, cantidad, descuento)
    //   GET /api/ventas/productos    → catálogo (hoy mock; mañana la API de Productos)
    //   PUT /api/ventas/{id}         → guarda los cambios
    const pesos = n => '$ ' + Number(n).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const escapar = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    let catalogo = null; // se pide una sola vez

    async function getJson(url) {
        const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const json = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(`HTTP ${r.status}: ${json.message ?? 'error'}`);
        return json.data;
    }

    async function abrirEditor(id) {
        const contenedor = document.querySelector(`[data-editor="${id}"]`);

        let venta;
        try {
            catalogo ??= await getJson('/api/ventas/productos');
            venta = await getJson(`/api/ventas/${id}`);
        } catch (e) {
            alert('No se pudo cargar la venta: ' + e.message);
            return;
        }

        const porId = Object.fromEntries(catalogo.map(p => [p.id, p]));

        // La venta no guarda la lista de precios: se deduce del precio del primer producto.
        const primero = venta.detalles[0];
        const lista = primero && porId[primero.id_producto]
            && Number(primero.precio_unitario) === Number(porId[primero.id_producto].precioMay)
            && porId[primero.id_producto].precioMay !== porId[primero.id_producto].precioMin
            ? 'mayorista' : 'minorista';

        // Estado del editor (lo que se va a mandar al guardar).
        const editor = {
            lista,
            descuentoGlobal: venta.descuento_global,
            items: venta.detalles.map(d => ({ id: d.id_producto, cantidad: d.cantidad, descuento: d.descuento })),
        };

        const precio = idProducto => {
            const p = porId[idProducto];
            if (!p) return 0;
            return editor.lista === 'mayorista' ? p.precioMay : p.precioMin;
        };

        function dibujar() {
            const filas = editor.items.map((item, i) => {
                const p = porId[item.id];
                const subtotal = item.cantidad * (precio(item.id) - item.descuento);
                return `<tr>
                    <td>${escapar(p?.codigo ?? '')}</td>
                    <td>${escapar(p?.nombre ?? 'Producto #' + item.id)}</td>
                    <td>${pesos(precio(item.id))}</td>
                    <td><input class="hv-editor-input" type="number" min="1" max="9999" step="1" value="${item.cantidad}" data-campo="cantidad" data-i="${i}" aria-label="Cantidad de ${escapar(p?.nombre ?? 'producto')}"></td>
                    <td><input class="hv-editor-input" type="number" min="0" step="0.01" value="${item.descuento}" data-campo="descuento" data-i="${i}" aria-label="Descuento por unidad de ${escapar(p?.nombre ?? 'producto')}"></td>
                    <td>${pesos(subtotal)}</td>
                    <td><button type="button" class="btn-action btn-danger hv-editor-remove" data-quitar="${i}">Quitar</button></td>
                </tr>`;
            }).join('');

            const subtotal = editor.items.reduce((s, it) => s + it.cantidad * (precio(it.id) - it.descuento), 0);
            const total = subtotal - Math.min(Number(editor.descuentoGlobal) || 0, subtotal);

            const opciones = catalogo.map(p =>
                `<option value="${p.id}">${escapar(p.codigo)} - ${escapar(p.nombre)} (${pesos(editor.lista === 'mayorista' ? p.precioMay : p.precioMin)})</option>`
            ).join('');

            contenedor.innerHTML = `
                <section class="hv-editor" aria-label="Editar venta #${venta.id}">
                    <header class="hv-editor-header">
                        <h3 class="hv-editor-title">Modificar venta #${venta.id}</h3>
                        <label class="hv-editor-label">
                            Lista de precios
                            <select class="hv-editor-input" data-campo="lista">
                        <option value="minorista" ${editor.lista === 'minorista' ? 'selected' : ''}>Minorista</option>
                        <option value="mayorista" ${editor.lista === 'mayorista' ? 'selected' : ''}>Mayorista</option>
                            </select>
                        </label>
                    </header>
                    <div class="hv-editor-tabla-wrap">
                        <table class="hv-detalle-tabla hv-editor-tabla">
                            <thead><tr><th>Código</th><th>Producto</th><th class="num">Precio</th><th class="num">Cantidad</th><th class="num">Desc. c/u ($)</th><th class="num">Subtotal</th><th>Acción</th></tr></thead>
                            <tbody>${filas || '<tr><td class="hv-editor-vacio" colspan="7">Sin productos: agregá al menos uno.</td></tr>'}</tbody>
                        </table>
                    </div>
                    <div class="hv-editor-agregar">
                        <label class="hv-editor-label">
                            Agregar producto
                            <select class="hv-editor-input" data-nuevo-producto>${opciones}</select>
                        </label>
                        <label class="hv-editor-label">
                            Cantidad
                            <input class="hv-editor-input hv-editor-cantidad" type="number" min="1" value="1" data-nueva-cantidad>
                        </label>
                        <button type="button" class="btn-action btn-cart" data-agregar>Agregar producto</button>
                    </div>
                    <div class="hv-editor-resumen">
                        <label class="hv-editor-label">
                            Descuento global ($)
                            <input class="hv-editor-input hv-editor-descuento" type="number" min="0" step="0.01" value="${editor.descuentoGlobal}" data-campo="descuentoGlobal">
                        </label>
                        <div class="hv-editor-total">
                            Total estimado: ${pesos(total)}
                            <small>Antes: ${pesos(venta.total)}</small>
                        </div>
                    </div>
                    <p data-errores class="hv-editor-error" role="alert" hidden></p>
                    <div class="hv-editor-actions">
                        <button type="button" class="btn-action btn-danger" data-cancelar>Cancelar</button>
                        <button type="button" class="btn-action btn-cart" data-guardar>Guardar cambios</button>
                    </div>
                </section>`;
        }

        // Un solo "escuchador" para todo el editor (se re-dibuja después de cada cambio).
        contenedor.onchange = e => {
            const campo = e.target.dataset.campo;
            if (!campo) return;
            if (campo === 'lista') editor.lista = e.target.value;
            if (campo === 'descuentoGlobal') editor.descuentoGlobal = Number(e.target.value) || 0;
            if (campo === 'cantidad' || campo === 'descuento') {
                editor.items[e.target.dataset.i][campo] = Number(e.target.value) || 0;
            }
            dibujar();
        };

        contenedor.onclick = async e => {
            const t = e.target;

            if (t.dataset.quitar !== undefined) {
                editor.items.splice(Number(t.dataset.quitar), 1);
                dibujar();
            }

            if (t.hasAttribute('data-agregar')) {
                const idNuevo = Number(contenedor.querySelector('[data-nuevo-producto]').value);
                const cantidad = Number(contenedor.querySelector('[data-nueva-cantidad]').value) || 1;
                const existente = editor.items.find(it => it.id === idNuevo);
                // Si el producto ya estaba, se suma la cantidad en vez de repetir la fila.
                if (existente) existente.cantidad += cantidad;
                else editor.items.push({ id: idNuevo, cantidad, descuento: 0 });
                dibujar();
            }

            if (t.hasAttribute('data-cancelar')) {
                contenedor.hidden = true;
                contenedor.innerHTML = '';
            }

            if (t.hasAttribute('data-guardar')) {
                const cuerpo = { lista: editor.lista, items: editor.items };
                if (Number(editor.descuentoGlobal) > 0) {
                    cuerpo.descuento_global = { modo: 'monto', valor: Number(editor.descuentoGlobal) };
                }

                const r = await fetch(`/api/ventas/${id}`, {
                    method: 'PUT',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify(cuerpo),
                });
                const json = await r.json().catch(() => ({}));

                if (r.ok) {
                    alert(`${json.message}\nNuevo total: ${pesos(json.data.total)}`);
                    location.reload();
                    return;
                }

                // Errores del back (422 campo por campo, o 409/404/500 con "message").
                const errores = json.errors ? Object.values(json.errors).flat() : [];
                const errorElement = contenedor.querySelector('[data-errores]');
                errorElement.textContent =
                    `HTTP ${r.status}: ${json.message ?? ''} ${errores.length ? '→ ' + errores.join(' | ') : ''}`;
                errorElement.hidden = false;
            }
        };

        dibujar();
        contenedor.hidden = false;
    }

    document.querySelectorAll('[data-accion]').forEach(boton => {
        boton.addEventListener('click', async () => {
            const id = boton.dataset.id;
            const url = `/api/ventas/${id}`;

            if (boton.dataset.accion === 'modificar') {
                await abrirEditor(id);
            }

            if (boton.dataset.accion === 'eliminar') {
                const grupo = boton.closest('.hv-grupo');
                const formulario = grupo.querySelector(`[data-form-anulacion="${id}"]`);
                formulario.hidden = false;
                formulario.querySelector('textarea').focus();
            }

            if (boton.dataset.accion === 'restaurar') {
                if (!confirm('¿Restaurar la venta anulada?')) return;
                await llamarApi('POST', `${url}/restaurar`);
            }

            if (boton.dataset.accion === 'estado') {
                const estado = prompt(
                    `Estado actual: ${boton.dataset.estado}\nNuevo estado (pendiente, confirmada, preparada, entregada, pagada, facturada):`
                );
                if (estado === null) return;
                await llamarApi('PATCH', `${url}/estado`, { estado });
            }
        });
    });

    document.querySelectorAll('[data-form-anulacion]').forEach(formulario => {
        formulario.addEventListener('submit', async event => {
            event.preventDefault();
            const motivo = formulario.elements.motivo.value.trim();
            if (motivo.length < 5 || motivo.length > 150) {
                formulario.elements.motivo.setCustomValidity('El motivo debe tener entre 5 y 150 caracteres.');
                formulario.elements.motivo.reportValidity();
                return;
            }

            formulario.elements.motivo.setCustomValidity('');
            const id = formulario.dataset.formAnulacion;
            await llamarApi('DELETE', `/api/ventas/${id}`, { motivo });
        });

        formulario.elements.motivo.addEventListener('input', event => {
            event.target.setCustomValidity('');
        });

        formulario.querySelector('[data-cancelar-anulacion]').addEventListener('click', () => {
            formulario.reset();
            formulario.hidden = true;
        });
    });
</script>
<script type="application/json" id="ventasHistorialData">@json($ventas, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@endpush
