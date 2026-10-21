<!doctype html>
<html lang="es">
<body style="margin:0;padding:24px;background:#f4ecdc;color:#111827;font-family:Arial,Helvetica,sans-serif">
    <div style="max-width:520px;margin:0 auto;padding:28px;background:#fffaf0;border:1px solid #dfd0b8;border-radius:8px">
        <h1 style="margin:0 0 12px;font-size:24px">Recuperación de contraseña</h1>
        <p>Hola, {{ $userName }}.</p>
        <p>Su código para generar una nueva contraseña es:</p>
        <p style="margin:24px 0;color:#075bd8;font-size:32px;font-weight:700">{{ $recoveryCode }}</p>
        <p>El código vence en 15 minutos. Si no solicitó este cambio, puede ignorar el mensaje.</p>
    </div>
</body>
</html>
