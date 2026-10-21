<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contraseña | Sistema de gestión</title>
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card" aria-labelledby="recovery-title">
            <div class="brand-block">
                <span class="brand-mark">PyB</span>
                <div>
                    <h1 id="recovery-title">Recuperar contraseña</h1>
                    <p>Le enviaremos un código temporal.</p>
                </div>
            </div>

            @include('partials.messages')

            <form action="{{ route('password.email') }}" method="post" class="form">
                @csrf
                <div class="field">
                    <label for="email">Email de la cuenta <span aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" autocomplete="email" maxlength="160" required autofocus value="{{ old('email') }}">
                </div>
                <button type="submit" class="button button-primary">Enviar código</button>
                <a class="auth-link auth-link-centered" href="{{ route('login') }}">Volver al inicio de sesión</a>
            </form>
        </section>
    </main>
</body>
</html>
