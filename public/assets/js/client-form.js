(function (root) {
    'use strict';
    const patterns = {
        nombre: /^(?=.*\p{L})[\p{L}\p{M} .’'\-]+$/u,
        apellido_razon_social: /^(?=.*[\p{L}\p{N}])[\p{L}\p{M}\p{N} .,’'&()/\-]+$/u,
        localidad: /^(?=.*\p{L})[\p{L}\p{M}\p{N} .,’'()/\-]+$/u,
        zona: /^(?=.*[\p{L}\p{N}])[\p{L}\p{M}\p{N} .,’'()/\-]+$/u,
        direccion_calle: /^(?=.*[\p{L}\p{N}])[\p{L}\p{M}\p{N} .,’'°º()/\-]+$/u,
        direccion_numero: /^[0-9]{1,6}$/,
        email: /^[^\s@]+@(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i,
        telefono: /^\+?[0-9 ()\-]+$/,
    };
    const messages = {
        nombre: 'Ingrese un nombre con letras, espacios, puntos, apóstrofes o guiones.',
        apellido_razon_social: 'El apellido o razón social contiene caracteres no permitidos.',
        localidad: 'Ingrese una localidad válida, sin etiquetas ni símbolos especiales.',
        zona: 'Seleccione o escriba una zona válida.',
        direccion_calle: 'Ingrese el nombre de la calle, sin etiquetas ni símbolos especiales.',
        direccion_numero: 'Ingrese entre 1 y 6 dígitos para la altura.',
        email: 'Ingrese un email con @ y dominio completo, por ejemplo nombre@gmail.com.',
        telefono: 'Ingrese un teléfono de 6 a 15 dígitos, sin letras.',
    };
    function errorFor(name, value, type) {
        value = String(value ?? '').trim();
        if (!value) return 'Complete este campo obligatorio.';
        if (name === 'tipo_documento') return ['dni', 'cuit'].includes(value) ? '' : 'Seleccione DNI o CUIT.';
        if (name === 'dni_cuit') {
            if (type === 'dni') return /^[0-9]{7,8}$/.test(value) ? '' : 'El DNI debe tener 7 u 8 dígitos.';
            if (type === 'cuit') return /^[0-9]{11}$/.test(value) ? '' : 'El CUIT debe tener exactamente 11 dígitos.';
            return 'Seleccione primero DNI o CUIT.';
        }
        if (name === 'tipo_cliente') return ['minorista', 'mayorista'].includes(value) ? '' : 'Seleccione el tipo de cliente.';
        if (name === 'estado') return ['activo', 'inactivo'].includes(value) ? '' : 'Seleccione un estado válido.';
        if (patterns[name] && !patterns[name].test(value)) return messages[name];
        if (name === 'telefono') {
            const length = value.replace(/[^0-9]/g, '').length;
            if (length < 6 || length > 15) return messages.telefono;
        }
        return '';
    }
    function mount(form) {
        const fields = ['nombre', 'apellido_razon_social', 'tipo_documento', 'dni_cuit', 'telefono', 'email',
            'tipo_cliente', 'estado', 'localidad', 'zona', 'direccion_calle', 'direccion_numero'];
        const element = name => form.elements.namedItem(name);
        const show = (name, error) => {
            const target = form.querySelector('[data-error-for="' + name + '"]');
            if (target) target.textContent = error;
            element(name)?.setAttribute('aria-invalid', error ? 'true' : 'false');
        };
        const type = element('tipo_documento');
        const number = element('dni_cuit');
        function configureDocument() {
            const selected = type.value;
            number.maxLength = selected === 'cuit' ? 11 : 8;
            number.minLength = selected === 'cuit' ? 11 : 7;
            number.pattern = selected === 'cuit' ? '[0-9]{11}' : '[0-9]{7,8}';
            const help = form.querySelector('[data-document-help]');
            if (help) help.textContent = selected === 'cuit' ? '11 dígitos, sin puntos ni guiones.'
                : selected === 'dni' ? '7 u 8 dígitos, sin puntos.' : 'Seleccione DNI o CUIT; luego ingrese solo números.';
        }
        configureDocument();
        type.addEventListener('change', () => {
            configureDocument();
            if (number.value) show('dni_cuit', errorFor('dni_cuit', number.value, type.value));
        });
        for (const name of ['dni_cuit', 'direccion_numero']) {
            const input = element(name);
            input.addEventListener('input', () => {
                input.value = input.value.replace(/[^0-9]/g, '').slice(0, input.maxLength);
                show(name, '');
            });
        }
        for (const name of fields) {
            const input = element(name);
            if (input) input.addEventListener('blur', () => show(name, errorFor(name, input.value, type.value)));
        }
        form.addEventListener('submit', event => {
            let first = null;
            for (const name of fields) {
                const input = element(name);
                if (!input) continue;
                const error = errorFor(name, input.value, type.value)
                    || (input.maxLength > 0 && input.value.length > input.maxLength ? 'El valor supera la longitud permitida.' : '');
                show(name, error);
                if (error && !first) first = input;
            }
            if (first) { event.preventDefault(); first.focus(); }
        });
    }
    if (typeof module !== 'undefined' && module.exports) module.exports = {errorFor, mount};
    if (root.document) root.document.querySelectorAll('form[data-client-form]').forEach(mount);
}(typeof window !== 'undefined' ? window : globalThis));
