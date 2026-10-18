(function () {
    function setError(form, fieldName, message) {
        var target = form.querySelector('[data-error-for="' + fieldName + '"]');
        if (target) {
            target.textContent = message;
        }
    }

    function clearErrors(form) {
        form.querySelectorAll('.field-error').forEach(function (element) {
            element.textContent = '';
        });
    }

    function isBlank(value) {
        return value.trim() === '';
    }

    function validateRequired(form, fields) {
        var valid = true;

        fields.forEach(function (fieldName) {
            var field = form.elements[fieldName];
            if (field && isBlank(field.value)) {
                setError(form, fieldName, 'Campo obligatorio.');
                valid = false;
            }
        });

        return valid;
    }

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            clearErrors(form);
            var valid = true;
            var type = form.getAttribute('data-validate');

            if (type === 'login') {
                valid = validateRequired(form, ['usuario', 'password']);
            }

            if (type === 'cliente') {
                valid = validateRequired(form, [
                    'nombre',
                    'apellido_razon_social',
                    'dni_cuit',
                    'telefono',
                    'email',
                    'direccion',
                    'tipo_cliente',
                    'estado'
                ]);

                var email = form.elements.email;
                if (email && !isBlank(email.value) && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                    setError(form, 'email', 'Email no valido.');
                    valid = false;
                }

                var telefono = form.elements.telefono;
                if (telefono && !isBlank(telefono.value) && !/^[0-9\s+\-()]{6,30}$/.test(telefono.value.trim())) {
                    setError(form, 'telefono', 'Telefono no valido.');
                    valid = false;
                }
            }

            if (type === 'reclamo') {
                valid = validateRequired(form, [
                    'cliente_id',
                    'asunto',
                    'prioridad',
                    'descripcion'
                ]);
            }

            if (!valid) {
                event.preventDefault();
            }
        });
    });
}());
