const {test} = require('node:test');
const assert = require('node:assert/strict');
const {errorFor, mount} = require('../../public/assets/js/client-form.js');

function setup() {
    const values = {nombre: 'María', apellido_razon_social: 'Comercial 25 & Hnos. S.A.', tipo_documento: 'dni',
        dni_cuit: '12345678', telefono: '+54 2923 123456', email: 'maria@empresa.com.ar', tipo_cliente: 'minorista',
        localidad: '25 de Mayo', zona: 'Norte', direccion_calle: 'Av. San Martín', direccion_numero: '123'};
    const fields = new Map(Object.entries(values).map(([name, value]) => [name, {value, maxLength: -1, events: {}, attrs: {},
        addEventListener(event, handler) { this.events[event] = handler; },
        setAttribute(name, value) { this.attrs[name] = value; }, focus() { this.focused = true; }}]));
    fields.get('direccion_numero').maxLength = 6;
    const errors = new Map(Object.keys(values).map(name => [name, {textContent: ''}]));
    const help = {textContent: ''};
    const form = {elements: {namedItem: name => fields.get(name)}, events: {},
        querySelector(selector) { return selector === '[data-document-help]' ? help : errors.get(selector.match(/="([^"]+)"/)[1]); },
        addEventListener(event, handler) { this.events[event] = handler; }};
    mount(form);
    return {form, fields, errors, help};
}
test('el selector limita DNI y CUIT sin truncar un documento existente al cambiar de tipo', () => {
    const ui = setup(), type = ui.fields.get('tipo_documento'), number = ui.fields.get('dni_cuit');
    assert.equal(number.maxLength, 8); assert.equal(number.minLength, 7);
    type.value = 'cuit'; type.events.change(); assert.equal(number.maxLength, 11);
    number.value = '30123456789'; number.events.input();
    type.value = 'dni'; type.events.change();
    assert.equal(number.value, '30123456789');
    assert.match(ui.errors.get('dni_cuit').textContent, /7 u 8/);
});
test('pegar letras y exceso de caracteres no introduce símbolos en documento o altura', () => {
    const ui = setup(), document = ui.fields.get('dni_cuit'), height = ui.fields.get('direccion_numero');
    document.value = '12x3456789'; document.events.input(); assert.equal(document.value, '12345678');
    height.value = '1x234567'; height.events.input(); assert.equal(height.value, '123456');
});
test('un email sin dominio completo bloquea el envío y enfoca el campo incorrecto', () => {
    const ui = setup(); ui.fields.get('email').value = 'ana@gmail';
    let prevented = false;
    ui.form.events.submit({preventDefault() { prevented = true; }});
    assert.equal(prevented, true); assert.equal(ui.fields.get('email').focused, true);
    assert.equal(ui.fields.get('email').attrs['aria-invalid'], 'true');
});
test('admite acentos, localidades numéricas y dominios empresariales, y rechaza etiquetas y teléfonos vacíos', () => {
    assert.equal(errorFor('nombre', 'María José'), '');
    assert.equal(errorFor('localidad', '25 de Mayo'), '');
    assert.equal(errorFor('apellido_razon_social', 'Comercial 25 & Hnos. S.A.'), '');
    assert.equal(errorFor('email', 'pedidos+web@empresa.com.ar'), '');
    assert.notEqual(errorFor('zona', '<script>'), '');
    assert.notEqual(errorFor('telefono', '(---)'), '');
    assert.notEqual(errorFor('email', 'a@-gmail.com'), '');
});
