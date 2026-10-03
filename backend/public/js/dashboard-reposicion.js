const DASH_API = '/api/dashboard-reposicion';

document.addEventListener('DOMContentLoaded', function () {
    cargarResumenDashboard();
    cargarGraficoMotivos();
    cargarGraficoCriticidad();
    cargarGraficoTendencia();
});

function obtenerToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
           localStorage.getItem('api_token') || '';
}

function headersAuth() {
    return {
        'Accept': 'application/json'
    };
}

function cargarResumenDashboard() {
    fetch(`${DASH_API}/resumen`, { headers: headersAuth() })
        .then(res => res.json())
        .then(data => {
            if (!data.exito) return;
            document.getElementById('dash-pendientes').textContent = data.sugerencias.sugerencias_pendientes;
            document.getElementById('dash-costo').textContent = data.sugerencias.costo_total_sugerido;
            document.getElementById('dash-vencidos').textContent = data.vencimientos.total_vencidos;
            document.getElementById('dash-criticos').textContent = data.vencimientos.criticos_7dias;
        })
        .catch(err => console.error('Error cargando resumen:', err));
}

function cargarGraficoMotivos() {
    fetch(`${DASH_API}/grafico-motivos`, { headers: headersAuth() })
        .then(res => res.json())
        .then(data => {
            if (!data.exito) return;

            const ctx = document.getElementById('graficoMotivos');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.datos.map(d => d.motivo),
                    datasets: [{
                        label: 'Cantidad de sugerencias',
                        data: data.datos.map(d => d.total),
                        backgroundColor: ['#faa61a', '#f04747', '#00b0f4']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        })
        .catch(err => console.error('Error cargando gráfico de motivos:', err));
}

function cargarGraficoCriticidad() {
    fetch(`${DASH_API}/grafico-criticidad`, { headers: headersAuth() })
        .then(res => res.json())
        .then(data => {
            if (!data.exito) return;

            const ctx = document.getElementById('graficoCriticidad');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.datos.map(d => d.categoria),
                    datasets: [{
                        data: data.datos.map(d => d.total),
                        backgroundColor: ['#f04747', '#faa61a', '#ffd700']
                    }]
                },
                options: { responsive: true }
            });
        })
        .catch(err => console.error('Error cargando gráfico de criticidad:', err));
}

function cargarGraficoTendencia() {
    fetch(`${DASH_API}/grafico-tendencia?dias=30`, { headers: headersAuth() })
        .then(res => res.json())
        .then(data => {
            if (!data.exito) return;

            const ctx = document.getElementById('graficoTendencia');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.datos.map(d => d.fecha),
                    datasets: [
                        {
                            label: 'Sugerencias generadas',
                            data: data.datos.map(d => d.total),
                            borderColor: '#5865f2',
                            backgroundColor: 'rgba(88, 101, 242, 0.1)',
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        })
        .catch(err => console.error('Error cargando gráfico de tendencia:', err));
}
