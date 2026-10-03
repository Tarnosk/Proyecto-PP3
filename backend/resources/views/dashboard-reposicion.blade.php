@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="clay-header mb-4">
        <h1 class="clay-title">📊 Dashboard de Reposición</h1>
        <p class="clay-subtitle">Vista general de sugerencias y vencimientos</p>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="clay-card clay-card-highlight">
                <div class="clay-card-icon">📦</div>
                <div class="clay-card-content">
                    <p class="clay-label">Pendientes</p>
                    <h3 id="dash-pendientes">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="clay-card clay-card-highlight">
                <div class="clay-card-icon">💰</div>
                <div class="clay-card-content">
                    <p class="clay-label">Costo Estimado</p>
                    <h3 id="dash-costo">$0.00</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="clay-card clay-alert-critica">
                <div class="clay-card-icon">🔴</div>
                <div class="clay-card-content">
                    <p class="clay-label">Vencidos</p>
                    <h3 id="dash-vencidos">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="clay-card clay-alert-alta">
                <div class="clay-card-icon">🟠</div>
                <div class="clay-card-content">
                    <p class="clay-label">Críticos (7 días)</p>
                    <h3 id="dash-criticos">0</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="clay-card">
                <div class="clay-card-header">
                    <h5>Sugerencias por Motivo</h5>
                </div>
                <canvas id="graficoMotivos" height="220"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="clay-card">
                <div class="clay-card-header">
                    <h5>Criticidad de Vencimientos</h5>
                </div>
                <canvas id="graficoCriticidad" height="220"></canvas>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="clay-card">
                <div class="clay-card-header">
                    <h5>Tendencia de Sugerencias (últimos 30 días)</h5>
                </div>
                <canvas id="graficoTendencia" height="120"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="{{ asset('js/dashboard-reposicion.js') }}"></script>
@endsection
