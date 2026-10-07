import '@testing-library/jest-dom';
import { vi } from 'vitest';

// Mock global window.alert, window.confirm and window.location.reload
if (typeof window !== 'undefined') {
  window.alert = vi.fn();
  window.confirm = vi.fn(() => true);
  try {
    Object.defineProperty(window, 'location', {
      configurable: true,
      value: { ...window.location, reload: vi.fn() },
    });
  } catch {
    // fallback if already mocked or defined
  }
}

const mockHeaders = {
  get: (name: string) => (name.toLowerCase() === 'content-type' ? 'application/json' : null),
};

function createJsonResponse(data: any, status = 200): Response {
  return {
    ok: status >= 200 && status < 300,
    status,
    headers: mockHeaders as any,
    json: () => Promise.resolve({ success: true, data }),
    text: () => Promise.resolve(JSON.stringify({ success: true, data })),
  } as Response;
}

// Mock fetch for headless JSDOM environment
global.fetch = vi.fn((url: RequestInfo | URL) => {
  const urlStr = url.toString();

  if (urlStr.includes('/proveedores/1/plazos')) {
    return Promise.resolve(
      createJsonResponse([
        {
          id_plazo: 1,
          plazo_entrega_dias: 3,
          motivo: 'Acuerdo inicial con Molinos',
          fecha_registro: '2026-09-01',
        },
      ])
    );
  }

  if (urlStr.includes('/proveedores/1')) {
    return Promise.resolve(
      createJsonResponse({
        id_proveedor: 1,
        razon_social: 'Molinos Río de la Plata',
        cuit: '30-50001234-9',
        telefono: '011-4321-0000',
        correo: 'contacto@molinos.com.ar',
        direccion: 'Av. Paseo Colón 746, CABA',
        rubro: 'Alimentos',
        estado: 'activo',
        plazo_entrega_dias: 3,
        productos_asociados: [
          {
            id_producto: 1,
            id_producto_proveedor: 10,
            nombre: 'Harina 000 1Kg',
            codigo_sku: 'HAR-001',
            precio_acordado: 750,
            es_proveedor_principal: 1,
          },
        ],
      })
    );
  }

  if (urlStr.includes('/categorias')) {
    return Promise.resolve(
      createJsonResponse([
        { id_categoria: 1, nombre: 'Almacén', total_productos: 1 },
        { id_categoria: 2, nombre: 'Bebidas', total_productos: 1 },
        { id_categoria: 3, nombre: 'Golosinas', total_productos: 1 },
      ])
    );
  }

  if (urlStr.includes('/marcas')) {
    return Promise.resolve(
      createJsonResponse([
        { id_marca: 1, nombre: 'Playadito', total_productos: 1 },
        { id_marca: 2, nombre: 'Mondelez', total_productos: 1 },
        { id_marca: 3, nombre: 'Villavicencio', total_productos: 1 },
        { id_marca: 4, nombre: 'Blancaflor', total_productos: 1 },
      ])
    );
  }

  if (urlStr.includes('/stock/lotes')) {
    return Promise.resolve(
      createJsonResponse([
        {
          id_lote: 1,
          id_producto: 1,
          nro_lote: 'LOT-2026-01',
          cantidad_actual: 50,
          fecha_vencimiento: '2026-12-31',
        },
      ])
    );
  }

  if (urlStr.includes('/stock/movimientos')) {
    return Promise.resolve(
      createJsonResponse([
        {
          id_movimiento: 1,
          id_producto: 1,
          tipo: 'ingreso',
          cantidad: 50,
          fecha: '2026-10-01 12:00',
        },
      ])
    );
  }

  if (urlStr.includes('/compras/sugerencias-reposicion')) {
    return Promise.resolve(
      createJsonResponse([
        {
          id_producto: 1,
          producto_nombre: 'Harina 000 1Kg',
          nombre: 'Harina 000 1Kg',
          stock_actual: 5,
          stock_minimo: 20,
          cantidad_sugerida: 95,
          proveedor_nombre: 'Molinos Río de la Plata',
        },
      ])
    );
  }

  // Default empty successful API response
  return Promise.resolve(createJsonResponse([]));
});
