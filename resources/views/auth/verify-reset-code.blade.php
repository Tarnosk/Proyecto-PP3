<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificar código | Sistema de gestión</title>
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card" aria-labelledby="code-title">
            <div class="brand-block">
                <span class="brand-mark">PyB</span>
                <div>
                    <h1 id="code-title">Ingrese el código</h1>
                    <p>Enviado a {{ $email }}. Vence en 15 minutos.</p>
                </div>
            </div>

            @include('partials.messages')

            <form action="{{ route('password.code.verify') }}" method="post" class="form">
                @csrf
                <div class="field">
                    <label for="code">Código de recuperación <span aria-hidden="true">*</span></label>
                    <input class="code-input" type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" minlength="6" maxlength="6" pattern="[0-9]{6}" required autofocus>
                </div>
                <button type="submit" class="button button-primary">Verificar código</button>
                <a class="auth-link auth-link-centered" href="{{ route('password.request') }}">Solicitar un código nuevo</a>
            </form>
        </section>
    </main>
</body>
</html>
