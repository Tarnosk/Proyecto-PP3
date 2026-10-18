<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ERP Distribuidora - Base Modular</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            display: flex;
            min-height: 100vh;
            margin: 0;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: white;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            max-width: 620px;
            width: 90%;
            border: 1px solid #e2e8f0;
        }
        h1 { color: #0f172a; margin-top: 0; font-size: 1.5rem; }
        p { color: #64748b; line-height: 1.6; }
        .badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        ul { padding-left: 1.25rem; color: #475569; }
        li { margin-bottom: 0.5rem; }
        .footer {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-top: 1.5rem;
            border-top: 1px solid #f1f5f9;
            padding-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">Laravel 12 • Base de Datos Unificada</span>
        <h1>ERP Distribuidora - Rama develop-g3</h1>
        <p>Módulo de Catálogo, Proveedores, Stock y Compras con <strong>base de datos unificada</strong> (sin Vite).</p>
        <div class="footer">
            Consulte <code>docs/GUIA_INTEGRACION_MODULAR.md</code> para el flujo de trabajo y registro de rutas sin conflictos.
        </div>
    </div>
</body>
</html>
