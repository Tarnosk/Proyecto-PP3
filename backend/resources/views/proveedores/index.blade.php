@extends('layouts.app')

@section('title', 'Proveedores - Distribuidora Pigüé')
@section('page_title', 'Gestión de Proveedores')
@section('page_subtitle', 'Catálogo de Proveedores, Plazos de Entrega y Asociación de Artículos')

@section('content')
<div class="space-y-6">

    <!-- RESUMEN / KPIS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="clay-card p-5 flex items-center gap-4">
            <div class="p-3.5 bg-blue-50 text-blue-600 rounded-2xl shadow-inner border border-blue-100">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Total Proveedores</span>
                <span id="kpiTotalProvs" class="text-2xl font-black text-slate-800">0</span>
                <span class="text-xs text-slate-500 font-bold ml-1">en catálogo</span>
            </div>
        </div>

        <div class="clay-card p-5 flex items-center gap-4">
            <div class="p-3.5 bg-emerald-50 text-emerald-600 rounded-2xl shadow-inner border border-emerald-100">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Proveedores Activos</span>
                <span id="kpiActivosProvs" class="text-2xl font-black text-emerald-700">0</span>
                <span class="text-xs text-emerald-600 font-bold ml-1">habilitados</span>
            </div>
        </div>

        <div class="clay-card p-5 flex items-center gap-4">
            <div class="p-3.5 bg-sky-50 text-sky-600 rounded-2xl shadow-inner border border-sky-100">
                <i data-lucide="clock" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Plazo de Entrega Promedio</span>
                <span id="kpiPlazoPromedio" class="text-2xl font-black text-sky-700">0</span>
                <span class="text-xs text-sky-600 font-bold ml-1">días hábiles</span>
            </div>
        </div>
    </div>

    <!-- BARRA DE ACCIONES Y FILTROS -->
    <div class="clay-card p-5 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
        <div class="flex items-center flex-1 gap-3 flex-wrap">
            <div class="relative flex-1 min-w-[220px]">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                <input type="text" id="filtroSearchProvs" placeholder="Buscar por razón social, CUIT o contacto..." 
                       class="clay-input pl-10 pr-4 py-2 w-full text-sm font-bold text-slate-700"
                       oninput="renderProveedores()">
            </div>

            <select id="filtroEstadoProvs" onchange="renderProveedores()" class="clay-input py-2 text-xs font-bold text-slate-700 min-w-[140px]">
                <option value="todos">Todos los Estados</option>
                <option value="activo">Solo Activos</option>
                <option value="inactivo">Solo Inactivos</option>
            </select>
        </div>

        <div class="shrink-0 flex items-center gap-2">
            <button onclick="recargarProveedores()" class="p-2.5 bg-white text-slate-600 hover:text-blue-600 rounded-xl border border-slate-200/80 shadow-sm" title="Refrescar">
                <i data-lucide="rotate-cw" class="w-4 h-4"></i>
            </button>
            <button onclick="sincronizarAsociacionesAutomaticas()" class="px-3.5 py-2.5 bg-white text-slate-700 hover:text-indigo-600 hover:border-indigo-200 text-xs font-bold flex items-center gap-1.5 rounded-xl border border-slate-200/80 shadow-sm transition-all" title="Asociar automáticamente productos a proveedores según historial de compras e ingresos">
                <i data-lucide="sparkles" class="w-4 h-4 text-indigo-500"></i>
                <span class="hidden sm:inline">Sincronizar Asociaciones</span>
            </button>
            <button onclick="abrirModalProveedor()" class="clay-btn-primary px-5 py-2.5 text-xs font-bold flex items-center gap-2 shadow-md">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Nuevo Proveedor</span>
            </button>
        </div>
    </div>

    <!-- TABLA DE PROVEEDORES -->
    <div class="clay-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="clay-table">
                <thead>
                    <tr>
                        <th>Razón Social</th>
                        <th>CUIT</th>
                        <th>Contacto Principal</th>
                        <th>Teléfono / Email</th>
                        <th>Dirección</th>
                        <th class="text-center">Plazo Entrega</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaProveedoresBody">
                    <tr>
                        <td colspan="8" class="text-center py-8 text-slate-400 font-bold">Cargando proveedores...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@section('modals')
<!-- ========================================== -->
<!-- MODAL: ALTA / EDICIÓN DE PROVEEDOR         -->
<!-- ========================================== -->
<div id="modalProveedor" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalProveedor()">
    <div class="clay-modal-content max-w-xl p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 id="modalProveedorTitulo" class="text-base font-black text-slate-800">Nuevo Proveedor</h3>
            <button onclick="cerrarModalProveedor()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="guardarProveedor(event)" class="space-y-4">
            <input type="hidden" id="provId">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Razón Social *</label>
                    <input type="text" id="provRazonSocial" required placeholder="Distribuidora S.A." class="clay-input w-full text-sm font-bold">
                </div>
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">CUIT *</label>
                    <input type="text" id="provCuit" required placeholder="30-12345678-9" 
                        inputmode="numeric" pattern="[0-9\-]+" title="Solo se permiten números y guiones"
                        oninput="this.value = this.value.replace(/[^0-9\-]/g, '')"
                        onkeydown="if(event.key && event.key.length === 1 && !/[0-9\-]/.test(event.key) && !event.ctrlKey && !event.metaKey) event.preventDefault();"
                        class="clay-input w-full font-bold text-sm">
                    <span class="text-[10px] text-slate-400 font-bold mt-0.5 block">Solo números y guiones (sin letras)</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Nombre de Contacto</label>
                    <input type="text" id="provContacto" placeholder="Ej: Juan Pérez" class="clay-input w-full text-sm font-bold">
                </div>
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Plazo Entrega Promedio (Días)</label>
                    <input type="number" min="1" id="provPlazo" value="5" required class="clay-input w-full font-black text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Teléfono</label>
                    <input type="tel" id="provTelefono" placeholder="011-4567-8901" 
                        inputmode="tel" pattern="[0-9\+\-\s\(\)]+" title="Solo se permiten números y signos telefónicos"
                        oninput="this.value = this.value.replace(/[^0-9\+\-\s\(\)]/g, '')"
                        onkeydown="if(event.key && event.key.length === 1 && !/[0-9\+\-\s\(\)]/.test(event.key) && !event.ctrlKey && !event.metaKey) event.preventDefault();"
                        class="clay-input w-full text-sm">
                    <span class="text-[10px] text-slate-400 font-bold mt-0.5 block">Solo números y signos telefónicos (sin letras)</span>
                </div>
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Correo Electrónico</label>
                    <input type="email" id="provEmail" placeholder="contacto@proveedor.com" class="clay-input w-full text-sm">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Dirección / Localidad</label>
                <input type="text" id="provDireccion" placeholder="Av. Principal 123" class="clay-input w-full text-sm">
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="cerrarModalProveedor()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cancelar</button>
                <button type="submit" class="clay-btn-primary px-5 py-2 text-xs font-black shadow-md">Guardar Proveedor</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: PRODUCTOS ASOCIADOS AL PROVEEDOR    -->
<!-- ========================================== -->
<div id="modalAsociarProductos" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalAsociar()">
    <div class="clay-modal-content max-w-3xl p-6 space-y-5" onclick="event.stopPropagation()">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 shadow-xs">
                    <i data-lucide="package" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-800">Productos Asociados al Proveedor</h3>
                    <p id="asocProvSubtitulo" class="text-xs text-slate-500 font-bold">Catálogo de artículos provistos y precios acordados</p>
                </div>
            </div>
            <button onclick="cerrarModalAsociar()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Formulario: Asociar Producto -->
        <div class="p-4 rounded-xl border border-indigo-100 bg-indigo-50/40 space-y-3">
            <span class="text-xs font-black text-indigo-900 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="plus-circle" class="w-4 h-4 text-indigo-600"></i>
                Asociar Producto del Catálogo
            </span>

            <form onsubmit="guardarAsociacionProducto(event)" class="space-y-3">
                <input type="hidden" id="asocIdProveedor">

                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <div class="md:col-span-5">
                        <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Producto *</label>
                        <select id="asocProductoSelect" onchange="alCambiarProductoAsociar()" required class="clay-input w-full text-xs font-bold bg-white"></select>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Precio Acordado ($)</label>
                        <input type="number" step="0.01" min="0" id="asocPrecioAcordado" class="clay-input w-full font-black text-xs bg-white" placeholder="0.00">
                    </div>

                    <div class="md:col-span-2 flex items-center pb-2">
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs font-bold text-slate-700">
                            <input type="checkbox" id="asocEsPrincipal" class="w-4 h-4 text-indigo-600 rounded">
                            <span>¿Principal?</span>
                        </label>
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" id="btnAsociarProdSubmit" class="clay-btn-primary w-full py-2 text-xs font-black shadow-md flex items-center justify-center gap-1">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Asociar</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla: Listado de Productos Asociados -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                    <i data-lucide="list" class="w-4 h-4 text-slate-500"></i>
                    Artículos Provistos Actualmente (<span id="asocCantidadTotal">0</span>)
                </span>
                <span id="asocMsgEstado" class="text-xs font-bold text-emerald-600"></span>
            </div>

            <div class="overflow-x-auto max-h-[38vh] overflow-y-auto rounded-xl border border-slate-200/80 bg-white">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto / Descripción</th>
                            <th>Categoría / Marca</th>
                            <th class="text-right">Precio Acordado</th>
                            <th class="text-center">Rol Proveedor</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaProductosAsociadosBody">
                        <tr>
                            <td colspan="6" class="text-center py-6 text-slate-400 font-bold">Cargando productos asociados...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
            <p class="text-[11px] text-slate-400 font-medium">Marcá un proveedor como "Principal" para que sea la opción por defecto en compras y reposición.</p>
            <button type="button" onclick="cerrarModalAsociar()" class="clay-btn-secondary px-5 py-2 text-xs font-bold">Cerrar</button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: EDITAR ASOCIACIÓN DE PRODUCTO       -->
<!-- ========================================== -->
<div id="modalEditarAsociacion" class="clay-modal-backdrop z-[60] hidden" onclick="if(event.target === this) cerrarModalEditarAsociacion()">
    <div class="clay-modal-content max-w-md p-6 space-y-4" onclick="event.stopPropagation()">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 shadow-xs">
                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-800">Editar Asociación de Producto</h3>
                    <p id="editAsocSubtitulo" class="text-[11px] text-slate-400 font-bold">Modificar precio acordado y condición comercial</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalEditarAsociacion()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="guardarEdicionAsociacion(event)" class="space-y-4">
            <input type="hidden" id="editAsocId">
            <input type="hidden" id="editAsocIdProveedor">

            <!-- Ficha informativa del producto -->
            <div class="p-3 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                <div class="flex items-center justify-between">
                    <span id="editAsocCodigo" class="text-[10px] font-black uppercase text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">COD</span>
                    <span id="editAsocCategoriaMarca" class="text-[10px] font-bold text-slate-400">Categoría</span>
                </div>
                <div id="editAsocNombre" class="text-xs font-black text-slate-800 leading-tight">Nombre del Producto</div>
                <div id="editAsocPrecioRef" class="text-[11px] text-slate-500 font-medium">Precio lista catálogo: -</div>
            </div>

            <!-- Precio Acordado -->
            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Precio Acordado de Compra ($)</label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-slate-400 font-black text-xs">$</span>
                    <input type="number" step="0.01" min="0" id="editAsocPrecio" class="clay-input pl-7 pr-3 py-2 w-full font-black text-sm bg-white" placeholder="0.00">
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Precio pactado con este proveedor para órdenes y reposición.</p>
            </div>

            <!-- Rol Proveedor Principal -->
            <div class="p-3 bg-indigo-50/40 rounded-xl border border-indigo-100/70 flex items-center justify-between">
                <div>
                    <span class="text-xs font-black text-slate-800 block">¿Es Proveedor Principal?</span>
                    <span class="text-[10px] text-slate-500 font-medium">Prioritario para comprar y reponer este producto</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="editAsocEsPrincipal" class="w-5 h-5 text-indigo-600 rounded border-slate-300">
                </label>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="cerrarModalEditarAsociacion()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cancelar</button>
                <button type="submit" id="btnGuardarEdicionAsoc" class="clay-btn-primary px-5 py-2 text-xs font-black flex items-center gap-1.5 shadow-md">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Guardar Cambios</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: HISTORIAL DE CAMBIOS Y AUDITORÍA    -->
<!-- ========================================== -->
<div id="modalHistorialProveedor" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalHistorialProveedor()">
    <div class="clay-modal-content max-w-2xl p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center text-purple-600">
                    <i data-lucide="history" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-800">Historial de Cambios</h3>
                    <p id="historialProvSubtitulo" class="text-xs text-slate-500 font-bold">Registro de alta, modificaciones y estado</p>
                </div>
            </div>
            <button onclick="cerrarModalHistorialProveedor()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div id="historialProvContenido" class="max-h-[60vh] overflow-y-auto pr-1 space-y-3">
            <div class="text-center py-8 text-slate-400 font-bold">Cargando historial...</div>
        </div>

        <div class="flex items-center justify-end pt-3 border-t border-slate-100">
            <button type="button" onclick="cerrarModalHistorialProveedor()" class="clay-btn-secondary px-5 py-2 text-xs font-bold">Cerrar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const API_BASE = '/api';
    let proveedoresCache = [];
    let productosCache = [];
    let productosAsociadosCache = [];

    document.addEventListener('DOMContentLoaded', () => {
        recargarProveedores();
    });

    async function recargarProveedores() {
        await Promise.all([cargarProveedores(), cargarProductosCatalogo()]);
        if (window.lucide) window.lucide.createIcons();
    }

    async function cargarProveedores() {
        try {
            const res = await fetch(`${API_BASE}/proveedores`);
            const json = await res.json();
            if (json.status === 'success') {
                proveedoresCache = json.data;
                renderProveedores();
                actualizarKPIsProveedores();
            }
        } catch (e) { console.error('Error proveedores', e); }
    }

    async function cargarProductosCatalogo() {
        try {
            const res = await fetch(`${API_BASE}/productos`);
            const json = await res.json();
            if (json.status === 'success') {
                productosCache = json.data;
                const select = document.getElementById('asocProductoSelect');
                if (select) {
                    select.innerHTML = productosCache.map(p => `<option value="${p.id_producto}">${p.codigo} - ${p.nombre}</option>`).join('');
                }
            }
        } catch (e) {}
    }

    function renderProveedores() {
        const search = document.getElementById('filtroSearchProvs').value.toLowerCase().trim();
        const estado = document.getElementById('filtroEstadoProvs').value;

        const filtrados = proveedoresCache.filter(p => {
            const razon = (p.razon_social || '').toLowerCase();
            const cuit = (p.cuit || p.CUIT || '').toLowerCase();
            const contacto = (p.contacto || '').toLowerCase();
            const direccion = (p.direccion || '').toLowerCase();
            const coincideTexto = !search || razon.includes(search) || cuit.includes(search) || contacto.includes(search) || direccion.includes(search);
            const coincideEstado = estado === 'todos' || p.estado === estado;
            return coincideTexto && coincideEstado;
        });

        const tbody = document.getElementById('tablaProveedoresBody');
        if (filtrados.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400 font-bold">No se encontraron proveedores.</td></tr>`;
            return;
        }

        tbody.innerHTML = filtrados.map(p => {
            const esActivo = p.estado === 'activo';
            const badgeClase = esActivo ? 'clay-badge-success' : 'clay-badge-danger';
            const email = p.email || p.correo || '-';
            const direccion = p.direccion || 'Sin dirección';

            return `
                <tr>
                    <td>
                        <div class="font-black text-slate-800 text-sm leading-tight">${p.razon_social}</div>
                        <div class="text-[11px] text-slate-400 font-bold">${direccion}</div>
                        <div class="mt-1">
                            <button onclick="abrirModalAsociarProducto(${p.id_proveedor})" class="text-[10px] font-extrabold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200/60 px-2 py-0.5 rounded-md inline-flex items-center gap-1 transition-colors" title="Ver y asociar productos provistos">
                                <i data-lucide="package" class="w-3 h-3"></i>
                                <span>${p.total_productos ?? 0} art. asociados</span>
                            </button>
                        </div>
                    </td>
                    <td class="font-extrabold text-slate-700">${p.cuit || p.CUIT || '-'}</td>
                    <td class="font-bold text-slate-700 text-xs">${p.contacto || '-'}</td>
                    <td>
                        <div class="text-xs font-bold text-slate-800">${p.telefono || '-'}</div>
                        <div class="text-[11px] text-slate-400">${email}</div>
                    </td>
                    <td class="text-xs text-slate-600 font-bold">${p.direccion || '-'}</td>
                    <td class="text-center font-black text-blue-700">${p.plazo_entrega_dias ?? 5} días</td>
                    <td class="text-center"><span class="clay-badge ${badgeClase}">${p.estado}</span></td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <button onclick="verHistorialProveedor(${p.id_proveedor}, '${(p.razon_social || '').replace(/'/g, "\\'")}')" class="p-1.5 text-purple-600 hover:bg-purple-50 rounded-lg" title="Ver Historial de Cambios">
                                <i data-lucide="history" class="w-4 h-4"></i>
                            </button>
                            <button onclick="editarProveedor(${p.id_proveedor})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg" title="Editar">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            ${esActivo ? `
                            <button onclick="abrirModalAsociarProducto(${p.id_proveedor})" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg" title="Ver y asociar productos">
                                <i data-lucide="package-plus" class="w-4 h-4"></i>
                            </button>
                            ` : `
                            <button disabled class="p-1.5 text-slate-300 cursor-not-allowed rounded-lg opacity-40" title="Proveedor inactivo (dado de baja). No disponible para operaciones.">
                                <i data-lucide="package-plus" class="w-4 h-4"></i>
                            </button>
                            `}
                            <button onclick="cambiarEstadoProveedor(${p.id_proveedor}, '${p.estado}')" class="p-1.5 ${esActivo ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50'} rounded-lg" title="${esActivo ? 'Desactivar' : 'Activar'}">
                                <i data-lucide="${esActivo ? 'power' : 'check'}" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        if (window.lucide) window.lucide.createIcons();
    }

    function actualizarKPIsProveedores() {
        const total = proveedoresCache.length;
        const activos = proveedoresCache.filter(p => p.estado === 'activo').length;
        const sumaPlazos = proveedoresCache.reduce((acc, p) => acc + Number(p.plazo_entrega_dias || 5), 0);
        const promedio = total > 0 ? Math.round(sumaPlazos / total) : 0;

        document.getElementById('kpiTotalProvs').textContent = total;
        document.getElementById('kpiActivosProvs').textContent = activos;
        document.getElementById('kpiPlazoPromedio').textContent = `${promedio} d.`;
    }

    function abrirModalProveedor() {
        document.getElementById('modalProveedorTitulo').textContent = 'Nuevo Proveedor';
        document.getElementById('provId').value = '';
        document.getElementById('provRazonSocial').value = '';
        document.getElementById('provCuit').value = '';
        document.getElementById('provContacto').value = '';
        document.getElementById('provTelefono').value = '';
        document.getElementById('provEmail').value = '';
        document.getElementById('provDireccion').value = '';
        document.getElementById('provPlazo').value = '5';
        document.getElementById('modalProveedor').classList.remove('hidden');
    }

    async function editarProveedor(id) {
        let prov = proveedoresCache.find(p => p.id_proveedor === id);
        if (!prov) {
            try {
                const res = await fetch(`${API_BASE}/proveedores/${id}`);
                const json = await res.json();
                if (json.status === 'success') {
                    prov = json.data;
                }
            } catch (e) {}
        }
        if (!prov) return;

        document.getElementById('modalProveedorTitulo').textContent = 'Editar Proveedor';
        document.getElementById('provId').value = prov.id_proveedor;
        document.getElementById('provRazonSocial').value = prov.razon_social || '';
        document.getElementById('provCuit').value = prov.cuit || prov.CUIT || '';
        document.getElementById('provContacto').value = prov.contacto || '';
        document.getElementById('provTelefono').value = prov.telefono || '';
        document.getElementById('provEmail').value = prov.email || prov.correo || '';
        document.getElementById('provDireccion').value = prov.direccion || '';
        document.getElementById('provPlazo').value = prov.plazo_entrega_dias ?? 5;
        document.getElementById('modalProveedor').classList.remove('hidden');
    }

    function cerrarModalProveedor() { document.getElementById('modalProveedor').classList.add('hidden'); }

    async function guardarProveedor(e) {
        e.preventDefault();
        const id = document.getElementById('provId').value;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        const cuit = document.getElementById('provCuit').value.trim();
        const telefono = document.getElementById('provTelefono').value.trim();

        if (/[a-zA-Z]/i.test(cuit)) {
            alert('El CUIT no puede contener letras.');
            document.getElementById('provCuit').focus();
            return;
        }

        if (!/^[0-9\-]+$/.test(cuit)) {
            alert('El CUIT solo debe contener números y guiones.');
            document.getElementById('provCuit').focus();
            return;
        }

        if (telefono && /[a-zA-Z]/i.test(telefono)) {
            alert('El teléfono no puede contener letras.');
            document.getElementById('provTelefono').focus();
            return;
        }

        if (telefono && !/^[0-9\+\-\s\(\)]+$/.test(telefono)) {
            alert('El teléfono solo debe contener números y caracteres telefónicos (+, -, espacios, paréntesis).');
            document.getElementById('provTelefono').focus();
            return;
        }

        const payload = {
            razon_social: document.getElementById('provRazonSocial').value.trim(),
            cuit: cuit,
            contacto: document.getElementById('provContacto').value.trim(),
            telefono: telefono,
            email: document.getElementById('provEmail').value.trim(),
            correo: document.getElementById('provEmail').value.trim(),
            direccion: document.getElementById('provDireccion').value.trim(),
            plazo_entrega_dias: Number(document.getElementById('provPlazo').value || 5)
        };

        const url = id ? `${API_BASE}/proveedores/${id}` : `${API_BASE}/proveedores`;
        const method = id ? 'PATCH' : 'POST';

        try {
            const res = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                cerrarModalProveedor();
                await cargarProveedores();
            } else {
                alert(data.message || 'Error al guardar proveedor');
            }
        } catch (err) { alert('Error de conexión'); }
    }

    async function cambiarEstadoProveedor(id, estadoActual) {
        const nuevoEstado = estadoActual === 'activo' ? 'inactivo' : 'activo';
        if (!confirm(`¿Desea cambiar el estado del proveedor a "${nuevoEstado}"?`)) return;

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const accion = nuevoEstado === 'activo' ? 'activar' : 'desactivar';

        try {
            const res = await fetch(`${API_BASE}/proveedores/${id}/${accion}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            if (data.status === 'success') {
                await cargarProveedores();
            } else {
                alert(data.message || 'Error al cambiar estado');
            }
        } catch (err) { alert('Error de conexión'); }
    }

    async function abrirModalAsociarProducto(id) {
        let prov = proveedoresCache.find(p => p.id_proveedor === id);
        if (prov && prov.estado === 'inactivo') {
            alert('No se pueden asociar artículos ni realizar operaciones con un proveedor dado de baja.');
            return;
        }

        document.getElementById('asocIdProveedor').value = id;
        const nombreProv = prov ? prov.razon_social : `Proveedor #${id}`;
        const cuitProv = prov ? (prov.cuit || prov.CUIT || '') : '';
        document.getElementById('asocProvSubtitulo').textContent = `${nombreProv} ${cuitProv ? '(CUIT: ' + cuitProv + ')' : ''}`;
        document.getElementById('asocPrecioAcordado').value = '';
        document.getElementById('asocEsPrincipal').checked = false;
        document.getElementById('asocMsgEstado').textContent = '';

        const select = document.getElementById('asocProductoSelect');
        if (select && Array.isArray(productosCache) && productosCache.length > 0) {
            select.innerHTML = '<option value="">-- Seleccionar Producto del Catálogo --</option>' + 
                productosCache.map(p => `<option value="${p.id_producto}">[${p.codigo}] ${p.nombre || p.descripcion}</option>`).join('');
        }

        document.getElementById('modalAsociarProductos').classList.remove('hidden');
        if (window.lucide) window.lucide.createIcons();

        await cargarProductosAsociados(id);
    }

    function cerrarModalAsociar() {
        document.getElementById('modalAsociarProductos').classList.add('hidden');
    }

    async function cargarProductosAsociados(idProveedor) {
        const tbody = document.getElementById('tablaProductosAsociadosBody');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-slate-400 font-bold">Cargando productos asociados...</td></tr>';

        try {
            const res = await fetch(`${API_BASE}/proveedores/${idProveedor}/productos`);
            const json = await res.json();
            if (json.status !== 'success') {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-6 text-rose-500 font-bold">${json.message || 'Error al obtener productos'}</td></tr>`;
                return;
            }

            const lista = json.data || [];
            productosAsociadosCache = lista;
            document.getElementById('asocCantidadTotal').textContent = lista.length;

            if (lista.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-6 text-slate-400 font-bold">Este proveedor no tiene productos asociados todavía. Seleccioná un producto arriba para asociarlo.</td></tr>`;
                return;
            }

            tbody.innerHTML = lista.map(item => {
                const esPrincipal = !!item.es_proveedor_principal;
                const precio = item.precio_acordado !== null ? `$${Number(item.precio_acordado).toLocaleString('es-AR', {minimumFractionDigits: 2})}` : '-';
                const catMarca = [item.categoria, item.marca].filter(Boolean).join(' • ') || '-';

                return `
                    <tr>
                        <td class="font-extrabold text-slate-700 text-xs">${item.codigo || '-'}</td>
                        <td>
                            <div class="font-black text-slate-800 text-xs">${item.nombre || item.descripcion || 'Producto'}</div>
                            <div class="text-[10px] text-slate-400">${item.descripcion && item.nombre ? item.descripcion : ''}</div>
                        </td>
                        <td class="text-xs text-slate-500 font-medium">${catMarca}</td>
                        <td class="text-right font-black text-slate-800 text-xs">${precio}</td>
                        <td class="text-center">
                            ${esPrincipal 
                                ? '<span class="clay-badge clay-badge-success text-[10px]">Principal</span>' 
                                : `<button onclick="marcarComoPrincipal(${item.id_producto_proveedor}, ${idProveedor})" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200/60 px-2 py-0.5 rounded-md transition-colors" title="Fijar como proveedor principal para este producto">Hacer Principal</button>`
                            }
                        </td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="abrirModalEditarAsociacion(${item.id_producto_proveedor})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Editar asociación (precio acordado, rol)">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>
                                <button onclick="desasociarProductoDeProveedor(${item.id_producto_proveedor}, ${idProveedor}, '${(item.nombre || item.codigo || '').replace(/'/g, "\\'")}')" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors" title="Desasociar este producto del proveedor">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            if (window.lucide) window.lucide.createIcons();
        } catch (e) {
            console.error(e);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-rose-500 font-bold">Error de conexión al cargar productos asociados.</td></tr>';
        }
    }

    async function guardarAsociacionProducto(e) {
        e.preventDefault();
        const idProv = document.getElementById('asocIdProveedor').value;
        const idProd = document.getElementById('asocProductoSelect').value;
        if (!idProd) {
            alert('Debe seleccionar un producto del catálogo.');
            document.getElementById('asocProductoSelect').focus();
            return;
        }

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const precioVal = document.getElementById('asocPrecioAcordado').value.trim();
        const payload = {
            id_productos: [Number(idProd)],
            id_producto: Number(idProd),
            precio_acordado: precioVal !== '' ? Number(precioVal) : null,
            es_proveedor_principal: document.getElementById('asocEsPrincipal').checked
        };

        const submitBtn = document.getElementById('btnAsociarProdSubmit');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const res = await fetch(`${API_BASE}/proveedores/${idProv}/productos`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                document.getElementById('asocPrecioAcordado').value = '';
                document.getElementById('asocEsPrincipal').checked = false;
                document.getElementById('asocProductoSelect').value = '';
                if (submitBtn) {
                    submitBtn.innerHTML = '<i data-lucide="plus" class="w-4 h-4"></i><span>Asociar</span>';
                }
                const msgEl = document.getElementById('asocMsgEstado');
                if (msgEl) {
                    msgEl.textContent = '✓ Operación completada exitosamente';
                    setTimeout(() => { msgEl.textContent = ''; }, 3500);
                }
                await cargarProductosAsociados(idProv);
                await cargarProveedores();
                if (window.lucide) window.lucide.createIcons();
            } else {
                alert(data.message || 'Error al asociar el producto');
            }
        } catch (err) {
            alert('Error de conexión al asociar el producto');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    function alCambiarProductoAsociar() {
        const idProd = Number(document.getElementById('asocProductoSelect').value);
        const btnSubmit = document.getElementById('btnAsociarProdSubmit');
        const msgEl = document.getElementById('asocMsgEstado');
        if (!idProd) {
            document.getElementById('asocPrecioAcordado').value = '';
            document.getElementById('asocEsPrincipal').checked = false;
            if (btnSubmit) btnSubmit.innerHTML = '<i data-lucide="plus" class="w-4 h-4"></i><span>Asociar</span>';
            if (msgEl) msgEl.textContent = '';
            if (window.lucide) window.lucide.createIcons();
            return;
        }

        const asocExistente = (productosAsociadosCache || []).find(a => a.id_producto === idProd);
        if (asocExistente) {
            document.getElementById('asocPrecioAcordado').value = asocExistente.precio_acordado !== null ? asocExistente.precio_acordado : '';
            document.getElementById('asocEsPrincipal').checked = !!asocExistente.es_proveedor_principal;
            if (btnSubmit) btnSubmit.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span>Actualizar</span>';
            if (msgEl) msgEl.textContent = 'ℹ Ya asociado: podés modificar su precio acordado o condición comercial.';
        } else {
            document.getElementById('asocPrecioAcordado').value = '';
            document.getElementById('asocEsPrincipal').checked = false;
            if (btnSubmit) btnSubmit.innerHTML = '<i data-lucide="plus" class="w-4 h-4"></i><span>Asociar</span>';
            if (msgEl) msgEl.textContent = '';
        }
        if (window.lucide) window.lucide.createIcons();
    }

    function abrirModalEditarAsociacion(idProductoProveedor) {
        const item = (productosAsociadosCache || []).find(a => a.id_producto_proveedor === idProductoProveedor);
        if (!item) return;

        document.getElementById('editAsocId').value = item.id_producto_proveedor;
        document.getElementById('editAsocIdProveedor').value = document.getElementById('asocIdProveedor').value;
        document.getElementById('editAsocCodigo').textContent = item.codigo || 'S/C';
        document.getElementById('editAsocNombre').textContent = item.nombre || item.descripcion || 'Producto';
        document.getElementById('editAsocCategoriaMarca').textContent = [item.categoria, item.marca].filter(Boolean).join(' • ') || '-';
        document.getElementById('editAsocPrecioRef').textContent = item.precio_unitario ? `Precio lista catálogo: $${Number(item.precio_unitario).toLocaleString('es-AR', {minimumFractionDigits: 2})}` : '';
        document.getElementById('editAsocPrecio').value = item.precio_acordado !== null ? item.precio_acordado : '';
        document.getElementById('editAsocEsPrincipal').checked = !!item.es_proveedor_principal;

        document.getElementById('modalEditarAsociacion').classList.remove('hidden');
        if (window.lucide) window.lucide.createIcons();
    }

    function cerrarModalEditarAsociacion() {
        document.getElementById('modalEditarAsociacion').classList.add('hidden');
    }

    async function guardarEdicionAsociacion(e) {
        e.preventDefault();
        const id = document.getElementById('editAsocId').value;
        const idProv = document.getElementById('editAsocIdProveedor').value;
        const precioVal = document.getElementById('editAsocPrecio').value.trim();
        const esPrincipal = document.getElementById('editAsocEsPrincipal').checked;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const btn = document.getElementById('btnGuardarEdicionAsoc');
        if (btn) btn.disabled = true;

        try {
            const res = await fetch(`${API_BASE}/proveedores/productos/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    precio_acordado: precioVal !== '' ? Number(precioVal) : null,
                    es_proveedor_principal: esPrincipal,
                })
            });

            let json = {};
            try {
                json = await res.json();
            } catch (jsonErr) {
                console.error('Error al parsear respuesta JSON:', jsonErr);
            }

            if (res.ok && (json.status === 'success' || json.data)) {
                cerrarModalEditarAsociacion();
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion('Asociación actualizada exitosamente', 'success');
                }
                await cargarProductosAsociados(idProv);
                await cargarProveedores();
            } else {
                alert(json.message || `Error (${res.status}): No se pudo actualizar la asociación.`);
            }
        } catch (err) {
            console.error('Error al guardar edición de asociación:', err);
            alert('Error al guardar cambios: ' + (err.message || 'Error de conexión'));
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    async function marcarComoPrincipal(idProductoProveedor, idProveedor) {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        try {
            const res = await fetch(`${API_BASE}/proveedores/productos/${idProductoProveedor}/principal`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            if (data.status === 'success') {
                await cargarProductosAsociados(idProveedor);
                await cargarProveedores();
            } else {
                alert(data.message || 'Error al definir proveedor principal');
            }
        } catch (e) {
            alert('Error de conexión');
        }
    }

    async function desasociarProductoDeProveedor(idProductoProveedor, idProveedor, nombreProducto) {
        if (!confirm(`¿Desea desasociar "${nombreProducto}" de este proveedor?`)) return;

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        try {
            const res = await fetch(`${API_BASE}/proveedores/productos/${idProductoProveedor}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            if (data.status === 'success') {
                await cargarProductosAsociados(idProveedor);
                await cargarProveedores();
            } else {
                alert(data.message || 'Error al desasociar producto');
            }
        } catch (e) {
            alert('Error de conexión');
        }
    }

    function cerrarModalHistorialProveedor() {
        document.getElementById('modalHistorialProveedor').classList.add('hidden');
    }

    async function verHistorialProveedor(id, razonSocial) {
        document.getElementById('historialProvSubtitulo').textContent = `${razonSocial || 'Proveedor'} - Registro de alta, modificaciones y estado`;
        const contenedor = document.getElementById('historialProvContenido');
        contenedor.innerHTML = '<div class="text-center py-8 text-slate-400 font-bold">Cargando historial...</div>';
        document.getElementById('modalHistorialProveedor').classList.remove('hidden');
        if (window.lucide) window.lucide.createIcons();

        try {
            const res = await fetch(`${API_BASE}/proveedores/${id}/historial`);
            const json = await res.json();
            if (json.status !== 'success') {
                contenedor.innerHTML = `<div class="p-4 text-center text-rose-500 text-xs font-bold">${json.message || 'Error al obtener historial'}</div>`;
                return;
            }

            const eventos = json.data || [];
            if (eventos.length === 0) {
                contenedor.innerHTML = '<div class="p-6 text-center text-slate-400 text-xs font-bold">No se registran eventos de auditoría para este proveedor.</div>';
                return;
            }

            contenedor.innerHTML = eventos.map(ev => {
                const tipo = ev.tipo_evento || 'modificacion';
                let badgeClase = 'bg-blue-50 text-blue-700 border-blue-200';
                let iconName = 'edit-3';
                let labelEvento = 'Modificación';

                if (tipo === 'alta') {
                    badgeClase = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    iconName = 'user-plus';
                    labelEvento = 'Alta / Registro';
                } else if (tipo === 'baja') {
                    badgeClase = 'bg-rose-50 text-rose-700 border-rose-200';
                    iconName = 'power';
                    labelEvento = 'Baja / Desactivación';
                } else if (tipo === 'reactivacion') {
                    badgeClase = 'bg-teal-50 text-teal-700 border-teal-200';
                    iconName = 'check-circle';
                    labelEvento = 'Reactivación';
                } else if (tipo === 'plazo') {
                    badgeClase = 'bg-amber-50 text-amber-700 border-amber-200';
                    iconName = 'clock';
                    labelEvento = 'Plazo de Entrega';
                }

                let contenidoHtml = '';
                if (tipo === 'alta' && ev.valores_nuevos) {
                    const vn = ev.valores_nuevos;
                    contenidoHtml = `
                        <div class="mt-2 text-xs bg-slate-50 p-2.5 rounded-lg border border-slate-100 grid grid-cols-2 gap-x-4 gap-y-1">
                            <div><span class="text-slate-400 font-bold">Razón Social:</span> <span class="font-extrabold text-slate-700">${vn.razon_social || '-'}</span></div>
                            <div><span class="text-slate-400 font-bold">CUIT:</span> <span class="font-extrabold text-slate-700">${vn.cuit || '-'}</span></div>
                            <div><span class="text-slate-400 font-bold">Teléfono:</span> <span class="font-extrabold text-slate-700">${vn.telefono || '-'}</span></div>
                            <div><span class="text-slate-400 font-bold">Email:</span> <span class="font-extrabold text-slate-700">${vn.email || vn.correo || '-'}</span></div>
                            <div><span class="text-slate-400 font-bold">Plazo:</span> <span class="font-extrabold text-blue-700">${vn.plazo_entrega_dias ? vn.plazo_entrega_dias + ' días' : 'No especificado'}</span></div>
                            <div><span class="text-slate-400 font-bold">Estado:</span> <span class="font-extrabold text-emerald-700">${vn.estado || 'activo'}</span></div>
                        </div>
                    `;
                } else if (Array.isArray(ev.cambios) && ev.cambios.length > 0) {
                    contenidoHtml = `
                        <div class="mt-2 text-xs bg-slate-50 p-2.5 rounded-lg border border-slate-100 space-y-1">
                            ${ev.cambios.map(c => `
                                <div class="flex items-center justify-between py-0.5 border-b border-slate-100 last:border-0">
                                    <span class="font-bold text-slate-500">${c.label || c.campo}:</span>
                                    <div class="font-extrabold">
                                        <span class="text-rose-600 line-through mr-1">${c.anterior !== null && c.anterior !== '' ? c.anterior : '(vacío)'}</span>
                                        <span class="text-slate-400">→</span>
                                        <span class="text-emerald-700 ml-1">${c.nuevo !== null && c.nuevo !== '' ? c.nuevo : '(vacío)'}</span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                } else if (ev.cambios_resumen) {
                    contenidoHtml = `<div class="mt-1 text-xs text-slate-600 font-semibold">${ev.cambios_resumen}</div>`;
                }

                return `
                    <div class="p-3.5 rounded-xl border border-slate-200/80 bg-white/70 shadow-xs space-y-1.5 transition-all">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-black border ${badgeClase}">
                                    <i data-lucide="${iconName}" class="w-3 h-3"></i>
                                    ${labelEvento}
                                </span>
                                <span class="text-xs font-black text-slate-800">${ev.titulo || ev.descripcion || 'Evento'}</span>
                            </div>
                            <span class="text-[11px] font-bold text-slate-400">${ev.fecha_hora || '-'}</span>
                        </div>
                        <div class="text-[11px] text-slate-500 font-medium">
                            Responsable: <span class="font-bold text-slate-700">${ev.usuario_nombre || 'Sistema'}</span>
                        </div>
                        ${contenidoHtml}
                    </div>
                `;
            }).join('');

            if (window.lucide) window.lucide.createIcons();
        } catch (e) {
            console.error(e);
            contenedor.innerHTML = '<div class="p-4 text-center text-rose-500 text-xs font-bold">Error de conexión al cargar historial.</div>';
        }
    }

    async function sincronizarAsociacionesAutomaticas() {
        if (!confirm('¿Desea escanear el historial de compras, recepciones y sugerencias para asociar automáticamente productos a sus proveedores correspondientes?')) {
            return;
        }
        mostrarNotificacion('Analizando y sincronizando asociaciones históricas...', 'info');
        try {
            const res = await fetch(`${API_BASE}/proveedores/sincronizar-asociaciones`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            const json = await res.json();
            if (json.status === 'success' || json.success) {
                const total = json.data?.asociaciones_creadas ?? 0;
                mostrarNotificacion(json.message || `Sincronización completada: ${total} asociaciones registradas`, 'success');
                await recargarProveedores();
            } else {
                mostrarNotificacion(json.message || 'Error al sincronizar asociaciones', 'error');
            }
        } catch (e) {
            console.error(e);
            mostrarNotificacion('Error de red al sincronizar asociaciones', 'error');
        }
    }
</script>
@endsection
