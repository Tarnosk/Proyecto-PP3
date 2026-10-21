<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva contraseña | Sistema de gestión</title>
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card" aria-labelledby="reset-title">
            <div class="brand-block">
                <span class="brand-mark">PyB</span>
                <div>
                    <h1 id="reset-title">Nueva contraseña</h1>
                    <p>Use al menos 8 caracteres.</p>
                </div>
            </div>

            @include('partials.messages')

            <form action="{{ route('password.update') }}" method="post" class="form">
                @csrf
                <div class="field">
                    <label for="password">Nueva contraseña <span aria-hidden="true">*</span></label>
                    <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" maxlength="255" required autofocus>
                </div>
                <div class="field">
                    <label for="password_confirmation">Repetir contraseña <span aria-hidden="true">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" minlength="8" maxlength="255" required>
                </div>
                <button type="submit" class="button button-primary">Guardar contraseña</button>
            </form>
        </section>
    </main>
</body>
</html>
