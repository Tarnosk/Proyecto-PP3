@extends('layouts.app')

@section('titulo', 'Portal de Acceso - ERP Distribuidora')

@push('styles')
<style>
.hub-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 1.5rem;
    font-family: 'Inter', system-ui, sans-serif;
}
.hub-header {
    margin-bottom: 2rem;
    border-bottom: 2px solid #E2E8F0;
    padding-bottom: 1rem;
}
.hub-header h1 {
    font-size: 2rem;
    font-weight: 800;
    color: #1E293B;
}
.hub-header p {
    color: #64748B;
    font-size: 1.05rem;
    margin-top: 0.25rem;
}
.hub-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1.5rem;
}
.module-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.module-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}
.badge-group {
    display: inline-block;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 0.75rem;
}
.bg-g2 { background: #DBEAFE; color: #1E40AF; }
.bg-general { background: #F1F5F9; color: #475569; }

.module-card h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 0.5rem;
}
.module-card p {
    color: #64748B;
    font-size: 0.9rem;
    margin-bottom: 1.25rem;
    flex-grow: 1;
}
.link-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.module-btn {
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.65rem 1rem;
    background: #F8FAFC;
    color: #1E293B;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    transition: background 0.15s;
}
.module-btn:hover {
    background: #2563EB;
    color: #FFFFFF;
    border-color: #2563EB;
}
.module-btn.primary {
    background: #2563EB;
    color: #FFFFFF;
    border-color: #2563EB;
}
.module-btn.primary:hover {
    background: #1D4ED8;
}
</style>
@endpush

@section('contenido')
<div class="hub-container">
    <div class="hub-header">
        <h1>🧭 Portal Integrado de Módulos (ERP Juan 23)</h1>
        <p>Acceso directo sin barrera de login para explorar todas las pantallas y funciones integradas en tu rama.</p>
    </div>

    <div class="hub-grid">
        {{-- Módulo Conciliación Bancaria (Tu trabajo - G2) --}}
        <div class="module-card" style="border-top: 4px solid #2563EB;">
            <div>
                <span class="badge-group bg-g2">Grupo 2 • Tu Módulo</span>
                <h3>Conciliación Bancaria</h3>
                <p>Pantalla dividida (Split Screen) según Issue #147, subida y normalización de extractos de Banco Provincia (.xls / .txt con SHA-256) y listado de períodos.</p>
            </div>
            <div class="link-list">
                <a href="/conciliacion/split" class="module-btn primary">
                    <span>⚡ Abrir Split Screen e Importación</span>
                    <span>→</span>
                </a>
                <a href="/conciliacion" class="module-btn">
                    <span>Períodos de Conciliación</span>
                    <span>→</span>
                </a>
                <a href="/api/movimientos-bancarios" target="_blank" class="module-btn">
                    <span>API Movimientos Bancarios (JSON)</span>
                    <span>↗</span>
                </a>
            </div>
        </div>

        {{-- Módulo Entregas (G2) --}}
        <div class="module-card" style="border-top: 4px solid #0D9488;">
            <div>
                <span class="badge-group bg-g2">Grupo 2</span>
                <h3>Entregas y Despacho</h3>
                <p>Armado de carga de reparto, emisión de Remitos oficiales correlativos y desglose de bultos cerrados y unidades sueltas.</p>
            </div>
            <div class="link-list">
                <a href="/entregas.html" class="module-btn">
                    <span>Abrir Módulo Entregas (HTML)</span>
                    <span>→</span>
                </a>
                <a href="/api/v1/entregas/pedidos-pendientes" target="_blank" class="module-btn">
                    <span>API Pedidos Pendientes (JSON)</span>
                    <span>↗</span>
                </a>
            </div>
        </div>

        {{-- Módulo Caja (G2) --}}
        <div class="module-card" style="border-top: 4px solid #16A34A;">
            <div>
                <span class="badge-group bg-g2">Grupo 2</span>
                <h3>Caja y Tesorería</h3>
                <p>Apertura, movimientos de caja del día, cierres diarios con arqueo de valores e historial con filtros.</p>
            </div>
            <div class="link-list">
                <a href="/caja/dia" class="module-btn">
                    <span>Caja del Día</span>
                    <span>→</span>
                </a>
                <a href="/caja/abrir" class="module-btn">
                    <span>Apertura de Caja</span>
                    <span>→</span>
                </a>
                <a href="/caja/cierres" class="module-btn">
                    <span>Historial de Cierres</span>
                    <span>→</span>
                </a>
            </div>
        </div>

        {{-- Módulo Rendiciones (G2) --}}
        <div class="module-card" style="border-top: 4px solid #F59E0B;">
            <div>
                <span class="badge-group bg-g2">Grupo 2</span>
                <h3>Rendiciones de Reparto</h3>
                <p>Control de cobros recaudados por repartidores, rendición de valores, devoluciones y diferencias.</p>
            </div>
            <div class="link-list">
                <a href="/rendiciones" class="module-btn">
                    <span>Panel de Rendiciones (Blade)</span>
                    <span>→</span>
                </a>
                <a href="/rendiciones.php" class="module-btn">
                    <span>Laboratorio de Pruebas Rendición (.php)</span>
                    <span>→</span>
                </a>
            </div>
        </div>

        {{-- Módulos Generales Integrados --}}
        <div class="module-card" style="border-top: 4px solid #64748B;">
            <div>
                <span class="badge-group bg-general">Integración ERP</span>
                <h3>Ventas y Clientes</h3>
                <p>Módulos integrados de los otros grupos en develop: ventas, reclamos, promociones y catálogo.</p>
            </div>
            <div class="link-list">
                <a href="/ventas" class="module-btn">
                    <span>Ventas</span>
                    <span>→</span>
                </a>
                <a href="/clientes" class="module-btn">
                    <span>Clientes</span>
                    <span>→</span>
                </a>
                <a href="/reclamos" class="module-btn">
                    <span>Reclamos</span>
                    <span>→</span>
                </a>
            </div>
        </div>

        {{-- Reportes y Stock --}}
        <div class="module-card" style="border-top: 4px solid #8B5CF6;">
            <div>
                <span class="badge-group bg-general">Integración ERP</span>
                <h3>Compras y Reportes</h3>
                <p>Gestión de compras, deudas a proveedores y reportes de gestión comercial.</p>
            </div>
            <div class="link-list">
                <a href="/panel-g3" class="module-btn">
                    <span>Panel Compras (G3)</span>
                    <span>→</span>
                </a>
                <a href="/reportes" class="module-btn">
                    <span>Reportes de Gestión</span>
                    <span>→</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
