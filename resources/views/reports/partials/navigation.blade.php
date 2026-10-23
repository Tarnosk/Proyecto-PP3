@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/reports.css') }}">
@endpush
<nav class="report-return" aria-label="Navegación de reportes">
    <a class="button button-secondary" href="{{ route('reports.index') }}"><span aria-hidden="true">←</span>&nbsp; Volver a reportes</a>
</nav>
