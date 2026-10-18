<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | Sistema de gestión</title>
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card" aria-labelledby="login-title">
            <div class="brand-block">
                <span class="brand-mark">PyB</span>
                <div>
                    <h1 id="login-title">Iniciar sesión</h1>
                    <p>Sistema de gestión comercial</p>
                </div>
            </div>

            @php
                $reasonMessage = match ($reason) {
                    'session_expired' => 'La sesión expiró por inactividad. Inicie sesión nuevamente.',
                    'session_replaced' => 'La cuenta inició sesión en otro dispositivo. Inicie sesión nuevamente.',
                    default => null,
                };
            @endphp

            @if ($errors->any() || $reasonMessage)
                <div class="alert alert-error" role="alert">
                    @foreach (collect($errors->all())->unique() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                    @if ($reasonMessage)
                        <p>{{ $reasonMessage }}</p>
                    @endif
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success" role="status">
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            <form action="{{ route('login.store') }}" method="post" class="form" data-validate="login" novalidate>
                @csrf

                <div class="field">
                    <label for="usuario">Usuario <span aria-hidden="true">*</span></label>
                    <input type="text" id="usuario" name="usuario" autocomplete="username" maxlength="50" required autofocus value="{{ old('usuario') }}">
                    <small class="field-error" data-error-for="usuario"></small>
                </div>

                <div class="field">
                    <label for="password">Contraseña <span aria-hidden="true">*</span></label>
                    <input type="password" id="password" name="password" autocomplete="current-password" maxlength="255" required>
                    <small class="field-error" data-error-for="password"></small>
                    <a class="auth-link" href="{{ route('password.request') }}">¿Olvidó su contraseña?</a>
                </div>

                <button type="submit" class="button button-primary">Iniciar sesión</button>
            </form>
        </section>
    </main>
    <script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
