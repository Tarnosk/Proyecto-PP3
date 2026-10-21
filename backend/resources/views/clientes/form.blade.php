@php($editing = isset($cliente))

<form action="{{ $editing ? route('clientes.update', $cliente) : route('clientes.store') }}" method="post" class="form form-panel" data-validate="cliente" novalidate>
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="form-grid">
        <div class="field">
            <label for="nombre">Nombre <span aria-hidden="true">*</span></label>
            <input type="text" id="nombre" name="nombre" maxlength="120" required value="{{ old('nombre', $cliente->nombre ?? '') }}">
            <small class="field-error" data-error-for="nombre"></small>
        </div>

        <div class="field">
            <label for="apellido_razon_social">Apellido / Razón social <span aria-hidden="true">*</span></label>
            <input type="text" id="apellido_razon_social" name="apellido_razon_social" maxlength="160" required value="{{ old('apellido_razon_social', $cliente->apellido_razon_social ?? '') }}">
            <small class="field-error" data-error-for="apellido_razon_social"></small>
        </div>

        <div class="field">
            <label for="dni_cuit">DNI / CUIT <span aria-hidden="true">*</span></label>
            <input type="text" id="dni_cuit" name="dni_cuit" maxlength="20" required value="{{ old('dni_cuit', $cliente->dni_cuit ?? '') }}">
            <small class="field-error" data-error-for="dni_cuit"></small>
        </div>

        <div class="field">
            <label for="telefono">Teléfono <span aria-hidden="true">*</span></label>
            <input type="text" id="telefono" name="telefono" maxlength="30" required value="{{ old('telefono', $cliente->telefono ?? '') }}">
            <small class="field-error" data-error-for="telefono"></small>
        </div>

        <div class="field">
            <label for="email">Email <span aria-hidden="true">*</span></label>
            <input type="email" id="email" name="email" maxlength="160" required value="{{ old('email', $cliente->email ?? '') }}">
            <small class="field-error" data-error-for="email"></small>
        </div>

        <div class="field">
            <label for="tipo_cliente">Tipo de cliente <span aria-hidden="true">*</span></label>
            <select id="tipo_cliente" name="tipo_cliente" required>
                <option value="">Seleccione</option>
                <option value="minorista" @selected(old('tipo_cliente', isset($cliente) ? $cliente->tipo_cliente->value : '') === 'minorista')>Minorista</option>
                <option value="mayorista" @selected(old('tipo_cliente', isset($cliente) ? $cliente->tipo_cliente->value : '') === 'mayorista')>Mayorista</option>
            </select>
            <small class="field-error" data-error-for="tipo_cliente"></small>
        </div>

        @if ($editing)
            <div class="field">
                <label for="estado">Estado <span aria-hidden="true">*</span></label>
                <select id="estado" name="estado" required>
                    <option value="activo" @selected(old('estado', $cliente->estado->value) === 'activo')>Activo</option>
                    <option value="inactivo" @selected(old('estado', $cliente->estado->value) === 'inactivo')>Inactivo</option>
                </select>
                <small class="field-error" data-error-for="estado"></small>
            </div>
        @endif

        <div class="field">
            <label for="localidad">Localidad <span aria-hidden="true">*</span></label>
            <input type="text" id="localidad" name="localidad" maxlength="120" required value="{{ old('localidad', $cliente->localidad ?? '') }}">
            <small class="field-error" data-error-for="localidad"></small>
        </div>

        <div class="field field-full">
            <label for="direccion">Domicilio <span aria-hidden="true">*</span></label>
            <textarea id="direccion" name="direccion" rows="3" maxlength="255" required>{{ old('direccion', $cliente->direccion ?? '') }}</textarea>
            <small class="field-error" data-error-for="direccion"></small>
        </div>
    </div>

    <div class="form-actions">
        <a class="button button-secondary" href="{{ $editing ? route('clientes.index') : route('dashboard') }}">Cancelar</a>
        <button type="submit" class="button button-primary">{{ $editing ? 'Guardar cambios' : 'Registrar cliente' }}</button>
    </div>
</form>
