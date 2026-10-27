/* Peticiones exclusivas del frontend G2: sesión G1, JSON y CSRF Laravel. */
async function g2Fetch(url, options = {}) {
    const target = new URL(url, window.location.origin);
    if (target.origin !== window.location.origin || !target.pathname.startsWith('/g2/api/')) {
        throw new Error('El frontend G2 sólo puede llamar a sus rutas de sesión.');
    }
    const headers = new Headers(options.headers);
    headers.set('Accept', 'application/json');
    const method = (options.method || 'GET').toUpperCase();
    if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
        headers.set('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
    }
    const response = await fetch(target, { ...options, headers, credentials: 'same-origin' });
    if (response.status === 401 || response.status === 419 || response.redirected) {
        throw new Error('La sesión venció. Volvé a iniciar sesión.');
    }
    if (!response.ok) {
        const body = await response.json().catch(() => ({}));
        return new Response(JSON.stringify({
            ...body,
            error: true,
            codigo: response.status,
            mensaje: body.mensaje || Object.values(body.errors || {}).flat().join(' ') || body.message || 'No se pudo completar la operación.'
        }), { status: response.status, headers: { 'Content-Type': 'application/json' } });
    }
    return response;
}
