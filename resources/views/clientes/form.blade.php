@php($editing = isset($cliente))
@php($documentType = old('tipo_documento', $cliente->tipo_documento ?? ''))
@php($documentValue = old('dni_cuit', $cliente->dni_cuit ?? ''))
@php($documentValue = \App\Modules\Clientes\Services\ClientFields::documentDigits($documentValue) ?? $documentValue)

<form action="{{ $editing ? route('clientes.update', $cliente) : route('clientes.store') }}" method="post" class="form form-panel client-form" data-validate="cliente" data-client-form novalidate>
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="form-grid">
        <div class="field">
            <label for="nombre">Nombre <span aria-hidden="true">*</span></label>
            <input type="text" id="nombre" name="nombre" maxlength="120" autocomplete="given-name" required aria-describedby="error-nombre" value="{{ old('nombre', $cliente->nombre ?? '') }}">
            <small id="error-nombre" class="field-error" data-error-for="nombre">@error('nombre'){{ $message }}@enderror</small>
        </div>

        <div class="field">
            <label for="apellido_razon_social">Apellido / Razón social <span aria-hidden="true">*</span></label>
            <input type="text" id="apellido_razon_social" name="apellido_razon_social" maxlength="160" required value="{{ old('apellido_razon_social', $cliente->apellido_razon_social ?? '') }}">
            <small class="field-error" data-error-for="apellido_razon_social">@error('apellido_razon_social'){{ $message }}@enderror</small>
        </div>

        <div class="field">
            <label for="tipo_documento">Tipo de documento <span aria-hidden="true">*</span></label>
            <select id="tipo_documento" name="tipo_documento" required>
                <option value="">Seleccione DNI o CUIT</option>
                <option value="dni" @selected($documentType === 'dni')>DNI</option>
                <option value="cuit" @selected($documentType === 'cuit')>CUIT</option>
            </select>
            <small class="field-error" data-error-for="tipo_documento">@error('tipo_documento'){{ $message }}@enderror</small>
        </div>

        <div class="field">
            <label for="dni_cuit" data-document-label>Número de documento <span aria-hidden="true">*</span></label>
            <input type="text" id="dni_cuit" name="dni_cuit" inputmode="numeric" autocomplete="off" maxlength="{{ $documentType === 'cuit' ? 11 : 8 }}" required aria-describedby="document-help error-dni-cuit" value="{{ $documentValue }}">
            <small id="document-help" class="client-field-help" data-document-help>Seleccione el tipo e ingrese solo números, sin puntos ni guiones.</small>
            <small id="error-dni-cuit" class="field-error" data-error-for="dni_cuit">@error('dni_cuit'){{ $message }}@enderror</small>
        </div>

        <div class="field">
            <label for="telefono">Teléfono <span aria-hidden="true">*</span></label>
            <input type="tel" id="telefono" name="telefono" maxlength="30" autocomplete="tel" required placeholder="Ej.: +54 2923 123456" value="{{ old('telefono', $cliente->telefono ?? '') }}">
            <small class="field-error" data-error-for="telefono">@error('telefono'){{ $message }}@enderror</small>
        </div>

        <div class="field">
            <label for="email">Email <span aria-hidden="true">*</span></label>
            <input type="email" id="email" name="email" maxlength="160" autocomplete="email" required placeholder="nombre@gmail.com" value="{{ old('email', $cliente->email ?? '') }}">
            <small class="field-error" data-error-for="email">@error('email'){{ $message }}@enderror</small>
        </div>

        <div class="field {{ $editing ? '' : 'field-full client-type-field' }}">
            <label for="tipo_cliente">Tipo de cliente <span aria-hidden="true">*</span></label>
            <select id="tipo_cliente" name="tipo_cliente" required>
                <option value="">Seleccione</option>
                <option value="minorista" @selected(old('tipo_cliente', isset($cliente) ? $cliente->tipo_cliente->value : '') === 'minorista')>Minorista</option>
                <option value="mayorista" @selected(old('tipo_cliente', isset($cliente) ? $cliente->tipo_cliente->value : '') === 'mayorista')>Mayorista</option>
            </select>
            <small class="field-error" data-error-for="tipo_cliente">@error('tipo_cliente'){{ $message }}@enderror</small>
        </div>

        @if ($editing)
            <div class="field">
                <label for="estado">Estado <span aria-hidden="true">*</span></label>
                <select id="estado" name="estado" required>
                    <option value="activo" @selected(old('estado', $cliente->estado->value) === 'activo')>Activo</option>
                    <option value="inactivo" @selected(old('estado', $cliente->estado->value) === 'inactivo')>Inactivo</option>
                </select>
                <small class="field-error" data-error-for="estado">@error('estado'){{ $message }}@enderror</small>
            </div>
        @endif

        <div class="form-grid field-full client-location-row">
        <div class="field">
            <label for="localidad">Localidad <span aria-hidden="true">*</span></label>
            <input type="text" id="localidad" name="localidad" maxlength="120" required value="{{ old('localidad', $cliente->localidad ?? '') }}">
            <small class="field-error" data-error-for="localidad">@error('localidad'){{ $message }}@enderror</small>
        </div>
        <div class="field">
            <label for="zona">Zona <span aria-hidden="true">*</span></label>
            <input type="text" id="zona" name="zona" list="client-zones" maxlength="120" required autocomplete="off" placeholder="Seleccione o escriba una zona" value="{{ old('zona', isset($cliente) ? $cliente->zona?->nombre : '') }}">
            <datalist id="client-zones">
                @foreach(($zonas ?? []) as $zone)
                    <option value="{{ $zone->nombre }}"></option>
                @endforeach
            </datalist>
            <small class="field-error" data-error-for="zona">@error('zona'){{ $message }}@enderror</small>
        </div>
        </div>

        <div class="field">
            <label for="direccion_calle">Calle / nombre de dirección <span aria-hidden="true">*</span></label>
            <input type="text" id="direccion_calle" name="direccion_calle" maxlength="200" required placeholder="Ej.: Av. San Martín" value="{{ old('direccion_calle', $cliente->direccion_calle ?? '') }}">
            <small class="field-error" data-error-for="direccion_calle">@error('direccion_calle'){{ $message }}@enderror</small>
        </div>
        <div class="field">
            <label for="direccion_numero">Número / altura <span aria-hidden="true">*</span></label>
            <input type="text" id="direccion_numero" name="direccion_numero" inputmode="numeric" pattern="[0-9]{1,6}" maxlength="6" required placeholder="Ej.: 123" value="{{ old('direccion_numero', $cliente->direccion_numero ?? '') }}">
            <small class="field-error" data-error-for="direccion_numero">@error('direccion_numero'){{ $message }}@enderror</small>
        </div>
    </div>

    <div class="form-actions">
        <a class="button button-secondary" href="{{ $editing ? route('clientes.index') : route('dashboard') }}">Cancelar</a>
        <button type="submit" class="button button-primary">{{ $editing ? 'Guardar cambios' : 'Registrar cliente' }}</button>
    </div>
</form>
