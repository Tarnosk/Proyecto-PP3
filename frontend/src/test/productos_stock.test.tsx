import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import React from 'react';
import { InventoryView } from '../components/InventoryView';
import { Product, User } from '../types';

const mockUser: User = {
  id: 'USR-1',
  name: 'Admin Test',
  email: 'admin@distribuidora.com',
  role: 'Administrador',
};

const mockProducts: Product[] = [
  {
    id: 'PROD-1',
    code: 'YER-001',
    name: 'Yerba Mate Playadito 1Kg',
    description: 'Yerba tradicional',
    category: 'Almacén',
    brand: 'Playadito',
    cost: 2000,
    price: 3200,
    stock: 50,
    minStock: 20,
    optimalStock: 100,
    committedStock: 10, // S02: 50 - 10 = 40 disponible
    status: 'activo',
  },
  {
    id: 'PROD-2',
    code: 'GAL-002',
    name: 'Galletitas Oreo 118g',
    description: 'Galletitas rellenas',
    category: 'Golosinas',
    brand: 'Mondelez',
    cost: 800,
    price: 1500,
    stock: 5, // S07, S16: Bajo stock (5 <= 15)
    minStock: 15,
    optimalStock: 60,
    committedStock: 0,
    status: 'activo',
  },
  {
    id: 'PROD-3',
    code: 'BEB-003',
    name: 'Agua Mineral 2L',
    description: 'Agua sin gas',
    category: 'Bebidas',
    brand: 'Villavicencio',
    cost: 600,
    price: 1100,
    stock: 0, // S16: Agotado / Crítico
    minStock: 25,
    optimalStock: 80,
    committedStock: 0,
    status: 'inactivo', // P06: Inactivo
  },
];

describe('Módulo Productos & Stock (P01-P08, S01-S16)', () => {

  it('P01: Visualiza catálogo de productos con información técnica y comercial', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    expect(screen.getByText(/Yerba Mate Playadito 1Kg/i)).toBeInTheDocument();
    expect(screen.getByText(/YER-001/i)).toBeInTheDocument();
    expect(screen.getByText(/Galletitas Oreo 118g/i)).toBeInTheDocument();
  });

  it('S01 & S02: Calcula y muestra correctamente el stock disponible (Total - Comprometido)', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    // PROD-1 tiene stock 50, comprometido 10 => disponible 40
    expect(screen.getByText(/Físico: 50/i)).toBeInTheDocument();
    expect(screen.getByText(/Comp: 10/i)).toBeInTheDocument();
    expect(screen.getByText(/Disp: 40/i)).toBeInTheDocument();
  });

  it('S07 & S16: Semáforo visual de stock y alerta de reposición crítica', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    const stockTab = screen.getByText(/Stock y Alertas/i);
    fireEvent.click(stockTab);
    expect(screen.getByText(/Stock Crítico/i)).toBeInTheDocument();
    expect(screen.getByText(/Stock Normal/i)).toBeInTheDocument();
  });

  it('P05: Búsqueda y filtrado en tiempo real por texto', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    const searchInput = screen.getByPlaceholderText(/Buscar por código, nombre o descripción/i);
    fireEvent.change(searchInput, { target: { value: 'Oreo' } });
    
    expect(screen.getByText(/Galletitas Oreo 118g/i)).toBeInTheDocument();
    expect(screen.queryByText(/Yerba Mate Playadito 1Kg/i)).not.toBeInTheDocument();
  });

  it('P01: Validación y apertura de formulario para nuevo producto', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    const addBtn = screen.getByText(/Nuevo Producto/i);
    fireEvent.click(addBtn);

    expect(screen.getByText(/Registrar Nuevo Producto/i)).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/Ej: C-3301/i)).toBeInTheDocument();
  });

  it('P04: Edición de producto existente', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    const editBtns = screen.getAllByTitle(/Modificar Producto/i);
    fireEvent.click(editBtns[0]);

    expect(screen.getByText(/Modificar Producto/i)).toBeInTheDocument();
    const nameInput = screen.getByDisplayValue(/Yerba Mate Playadito 1Kg/i);
    expect(nameInput).toBeInTheDocument();
  });

  it('P06: Desactivación lógica de producto', () => {
    const handleUpdate = vi.fn();
    window.confirm = vi.fn(() => true);
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={handleUpdate} />);
    
    const deactivateBtns = screen.getAllByTitle(/Desactivar Producto/i);
    fireEvent.click(deactivateBtns[0]);

    expect(handleUpdate).toHaveBeenCalled();
    const updatedCall = handleUpdate.mock.calls[0][0];
    const targetProd = updatedCall.find((p: Product) => p.id === 'PROD-1');
    expect(targetProd.status).toBe('inactivo');
  });

  it('P07: Aumento Masivo de Precios con previsualización', async () => {
    const handleUpdate = vi.fn();
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={handleUpdate} />);
    
    const aumBtn = screen.getByText(/Aumento Masivo/i);
    fireEvent.click(aumBtn);

    expect(screen.getByText(/Aumento Masivo de Precios/i)).toBeInTheDocument();
    
    const percentageInput = screen.getByDisplayValue('10');
    fireEvent.change(percentageInput, { target: { value: '10' } });

    const applyBtn = screen.getByText(/Aplicar Aumento Masivo/i);
    fireEvent.click(applyBtn);

    await waitFor(() => {
      expect(handleUpdate).toHaveBeenCalled();
    });
    const updated = handleUpdate.mock.calls[0][0];
    const yerba = updated.find((p: Product) => p.id === 'PROD-1');
    // 3200 * 1.10 = 3520
    expect(yerba.price).toBe(3520);
  });

  it('P08: Historial de precios cronológico', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    const historyBtns = screen.getAllByTitle(/Consultar Historial de Precios/i);
    fireEvent.click(historyBtns[0]);

    expect(screen.getByText(/Historial de Precios/i)).toBeInTheDocument();
  });

  it('S09-S11: Control de Lotes y Vencimientos', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    const lotesTab = screen.getByText(/Lotes y Vencimientos/i);
    fireEvent.click(lotesTab);

    expect(screen.getByText(/Trazabilidad de Lotes y Control de Vencimientos/i)).toBeInTheDocument();
    expect(screen.queryByText(/Registrar Nuevo Lote/i)).not.toBeInTheDocument();
    expect(screen.getByText(/Los lotes se registran en/i)).toBeInTheDocument();
  });

  it('S12: Historial de Movimientos de Stock (Kardex)', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    const kardexTab = screen.getByText(/Movimientos/i);
    fireEvent.click(kardexTab);

    expect(screen.getByText(/Historial de Movimientos de Stock/i)).toBeInTheDocument();
  });

  it('S13: Conteo Físico y Conciliación periódica', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    const conteoTab = screen.getByText(/Conteo Físico/i);
    fireEvent.click(conteoTab);

    expect(screen.getByText(/Conteo Físico y Conciliación/i)).toBeInTheDocument();
  });

  it('S06: Ajuste manual por rotura o pérdida', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    const stockTab = screen.getByText(/Stock y Alertas/i);
    fireEvent.click(stockTab);
    
    const adjustBtns = screen.getAllByText(/Ajustar Stock/i);
    fireEvent.click(adjustBtns[0]);

    expect(screen.getByText(/Ajuste Manual de Inventario/i)).toBeInTheDocument();
    expect(screen.getByText(/Motivo del Ajuste \*/i)).toBeInTheDocument();
  });

  it('S05: Registro de devolución de mercadería con distinción de cliente (+ stock) y proveedor (- stock)', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    const devTab = screen.getByText(/Devoluciones/i);
    fireEvent.click(devTab);

    expect(screen.getByText(/Registro de Devoluciones al Depósito/i)).toBeInTheDocument();
    expect(screen.getByText(/Producto Devuelto \*/i)).toBeInTheDocument();
    expect(screen.getByText(/Devolución de Cliente: Suma al Stock/i)).toBeInTheDocument();
    expect(screen.getByText(/Cliente \(Reingreso → \+ Suma a Stock\)/i)).toBeInTheDocument();
    expect(screen.getByText(/Proveedor \(Salida → - Descuenta de Stock\)/i)).toBeInTheDocument();
  });

  it('P02: Administrar Categorías con edición y eliminación', async () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    
    const catBtn = screen.getByText(/^Categorías$/i);
    fireEvent.click(catBtn);

    expect(await screen.findByText(/Administrar Categorías/i)).toBeInTheDocument();
    // Verify categories loaded
    expect((await screen.findAllByText(/Almacén/i)).length).toBeGreaterThanOrEqual(1);

    // Verify edit button is available
    const editCatBtns = screen.getAllByTitle(/Editar categoría/i);
    expect(editCatBtns.length).toBeGreaterThan(0);

    // Click edit on first category
    fireEvent.click(editCatBtns[0]);
    const editInput = screen.getByDisplayValue(/Almacén/i);
    expect(editInput).toBeInTheDocument();

    // Verify save button appears
    expect(screen.getByTitle(/Guardar cambios/i)).toBeInTheDocument();

    // Verify delete button is available
    const deleteCatBtns = screen.getAllByTitle(/Eliminar categoría/i);
    expect(deleteCatBtns.length).toBeGreaterThan(0);
  });

  it('P03: Administrar Marcas con edición y eliminación', async () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    
    const marcaBtn = screen.getByText(/^Marcas$/i);
    fireEvent.click(marcaBtn);

    expect(await screen.findByText(/Administrar Marcas/i)).toBeInTheDocument();
    // Verify brands loaded
    expect((await screen.findAllByText(/Playadito/i)).length).toBeGreaterThanOrEqual(1);

    // Verify edit button is available
    const editMarcaBtns = screen.getAllByTitle(/Editar marca/i);
    expect(editMarcaBtns.length).toBeGreaterThan(0);

    // Click edit on first brand
    fireEvent.click(editMarcaBtns[0]);
    const editInput = screen.getByDisplayValue(/Playadito/i);
    expect(editInput).toBeInTheDocument();

    // Verify save button appears
    expect(screen.getByTitle(/Guardar cambios/i)).toBeInTheDocument();

    // Verify delete button is available
    const deleteMarcaBtns = screen.getAllByTitle(/Eliminar marca/i);
    expect(deleteMarcaBtns.length).toBeGreaterThan(0);
  });

  it('S03: Modal de registro de ingreso de mercadería de proveedores en pestaña Stock y Alertas', async () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    // El botón se ubica en la pestaña Stock y Alertas
    const stockTab = screen.getByText(/Stock y Alertas/i);
    fireEvent.click(stockTab);

    const ingresoBtn = screen.getByText(/\+ Ingreso Mercadería/i);
    fireEvent.click(ingresoBtn);

    expect(await screen.findByText(/Registrar Ingreso de Mercadería/i)).toBeInTheDocument();
    expect(screen.getByText(/Proveedor \*/i)).toBeInTheDocument();
    expect(screen.getByText(/Fecha de Recepción \*/i)).toBeInTheDocument();
    expect(screen.getByText(/Productos a Ingresar \*/i)).toBeInTheDocument();

    // Validar restricción de fecha máxima <= hoy
    const d = new Date();
    const hoyStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const fechaInput = screen.getByLabelText(/Fecha de Recepción \*/i) as HTMLInputElement;
    expect(fechaInput.max).toBe(hoyStr);

    // Intentar ingresar fecha futura activa bloqueo y alerta
    window.alert = vi.fn();
    fireEvent.change(fechaInput, { target: { value: '2099-12-31' } });
    expect(window.alert).toHaveBeenCalledWith(expect.stringMatching(/posterior/i));
    expect(fechaInput.value).toBe(hoyStr);

    // Validar que los productos inactivos NO figuren en el selector de ingreso
    const optionInactivo = screen.queryByRole('option', { name: /Agua Mineral 2L/i });
    expect(optionInactivo).not.toBeInTheDocument();
  });

  it('Bloqueo de acciones y ajustes sobre productos inactivos', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    // 1. En catálogo, botón unidades deshabilitado para producto inactivo
    const disabledUnitsBtns = screen.getAllByTitle(/Producto inactivo: gestión de unidades no permitida/i);
    expect(disabledUnitsBtns.length).toBeGreaterThan(0);

    // 2. En pestaña Stock, producto inactivo muestra badge "Inactivo" en vez de botón "Ajustar Stock"
    const stockTab = screen.getByText(/Stock y Alertas/i);
    fireEvent.click(stockTab);
    expect(screen.getByTitle(/Producto inactivo: no se permiten ajustes de stock/i)).toBeInTheDocument();
  });

  it('S08: Modal de gestión de unidades de medida y equivalencias por producto', async () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    const unitsBtns = screen.getAllByTitle(/Unidades de Medida y Equivalencias/i);
    expect(unitsBtns.length).toBeGreaterThan(0);
    fireEvent.click(unitsBtns[0]);

    expect(await screen.findByText(/Unidades y Equivalencias/i)).toBeInTheDocument();
    expect(screen.getByText(/Unidad Base de Venta/i)).toBeInTheDocument();
    expect(screen.getByText(/Registrar Unidad Equivalente/i)).toBeInTheDocument();
  });

  it('S14: Gestión y visualización de ubicaciones físicas de almacenamiento', async () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    // Verifica columna ubicación en la tabla
    expect(screen.getByText(/^Ubicación$/i)).toBeInTheDocument();

    // Abrir modal de ubicaciones
    const ubicacionesBtn = screen.getByText(/^Ubicaciones$/i);
    fireEvent.click(ubicacionesBtn);

    expect(await screen.findByText(/Ubicaciones Físicas de Almacenamiento/i)).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/Pasillo 3 - Estante B/i)).toBeInTheDocument();
  });

  it('S15: Filtrado de productos por nivel de stock disponible (mínimo y máximo)', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    const minInput = screen.getByPlaceholderText(/^Mín$/i);
    const maxInput = screen.getByPlaceholderText(/^Máx$/i);

    // Filtrar productos con stock entre 40 y 60 (solo Yerba Playadito con stock 50)
    fireEvent.change(minInput, { target: { value: '40' } });
    fireEvent.change(maxInput, { target: { value: '60' } });

    expect(screen.getByText(/Yerba Mate Playadito 1Kg/i)).toBeInTheDocument();
    expect(screen.queryByText(/Galletitas Oreo 118g/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Agua Mineral 2L/i)).not.toBeInTheDocument();
  });

  it('S09, S10, S11: Pestaña de Lotes con alertas y ordenamiento por vencimiento', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    const lotesTab = screen.getByText(/Lotes y Vencimientos/i);
    fireEvent.click(lotesTab);

    expect(screen.getByText(/Trazabilidad de Lotes y Control de Vencimientos/i)).toBeInTheDocument();
    expect(screen.getByText(/Solo próximos a vencer/i)).toBeInTheDocument();
    expect(screen.getByTitle(/Cambiar orden por fecha de vencimiento/i)).toBeInTheDocument();
  });

  it('Filtro de solo próximos a vencer no muestra lotes vencidos', async () => {
    const pad = (n: number) => String(n).padStart(2, '0');
    const addDays = (days: number) => {
      const dt = new Date();
      dt.setDate(dt.getDate() + days);
      return `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())}`;
    };

    const mockFetch = vi.fn().mockImplementation((url: RequestInfo | URL) => {
      const urlStr = url.toString();
      if (urlStr.includes('/stock/lotes')) {
        return Promise.resolve({
          ok: true,
          status: 200,
          headers: { get: () => 'application/json' },
          json: () => Promise.resolve({
            success: true,
            data: [
              {
                id_lote: 101,
                id_producto: 1,
                producto_nombre: 'Yerba Mate Playadito 1Kg',
                nro_lote: 'LOTE-EXPIRADO-01',
                cantidad_actual: 10,
                fecha_vencimiento: addDays(-5), // Vencido hace 5 días
              },
              {
                id_lote: 102,
                id_producto: 1,
                producto_nombre: 'Yerba Mate Playadito 1Kg',
                nro_lote: 'LOTE-PROXIMO-02',
                cantidad_actual: 20,
                fecha_vencimiento: addDays(10), // Próximo en 10 días
              },
              {
                id_lote: 103,
                id_producto: 1,
                producto_nombre: 'Yerba Mate Playadito 1Kg',
                nro_lote: 'LOTE-LEJOS-03',
                cantidad_actual: 30,
                fecha_vencimiento: addDays(90), // Vence en 90 días
              },
            ],
          }),
        });
      }
      return Promise.resolve({
        ok: true,
        status: 200,
        headers: { get: () => 'application/json' },
        json: () => Promise.resolve({ success: true, data: [] }),
      });
    });

    const originalFetch = global.fetch;
    global.fetch = mockFetch as any;

    try {
      render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
      const lotesTab = screen.getByText(/Lotes y Vencimientos/i);
      fireEvent.click(lotesTab);

      // Esperar a que carguen los lotes
      expect(await screen.findByText('LOTE-EXPIRADO-01')).toBeInTheDocument();
      expect(screen.getByText('LOTE-PROXIMO-02')).toBeInTheDocument();
      expect(screen.getByText('LOTE-LEJOS-03')).toBeInTheDocument();

      // Marcar checkbox "Solo próximos a vencer"
      const checkbox = screen.getByLabelText(/Solo próximos a vencer/i);
      fireEvent.click(checkbox);

      // El lote expirado NO debe mostrarse
      expect(screen.queryByText('LOTE-EXPIRADO-01')).not.toBeInTheDocument();
      // El lote a 90 días tampoco (supera el plazo por defecto de 30 días)
      expect(screen.queryByText('LOTE-LEJOS-03')).not.toBeInTheDocument();
      // El lote próximo a vencer sí debe mostrarse
      expect(screen.getByText('LOTE-PROXIMO-02')).toBeInTheDocument();
    } finally {
      global.fetch = originalFetch;
    }
  });

  it('S12: Historial de movimientos de stock con responsable y filtro por producto', () => {
    render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
    const movTab = screen.getByText(/^Movimientos$/i);
    fireEvent.click(movTab);

    expect(screen.getByText(/Historial de Movimientos de Stock \(Kardex\)/i)).toBeInTheDocument();
    expect(screen.getAllByText(/Usuario Responsable/i).length).toBeGreaterThanOrEqual(1);
    expect(screen.getByText(/Filtrar por Producto:/i)).toBeInTheDocument();
  });

  it('S03 & S09: En ingreso de mercadería permite seleccionar lote, autorrellena vencimiento y muestra comprometidos con su fecha de vencimiento', async () => {
    const mockFetch = vi.fn().mockImplementation((url: RequestInfo | URL) => {
      const urlStr = String(url);
      if (urlStr.includes('/stock/lotes')) {
        return Promise.resolve({
          ok: true,
          status: 200,
          headers: { get: () => 'application/json' },
          json: () => Promise.resolve({
            success: true,
            data: [
              {
                id_lote: 201,
                id_producto: 'PROD-1',
                nro_lote: 'LOTE-TEST-AUTO',
                fecha_vencimiento: '2026-12-25',
                cantidad_actual: 30,
                cantidad_inicial: 50,
                producto_nombre: 'YER-001 - Yerba Mate Playadito 1Kg',
              },
            ],
          }),
        });
      }
      return Promise.resolve({
        ok: true,
        status: 200,
        headers: { get: () => 'application/json' },
        json: () => Promise.resolve({ success: true, data: [] }),
      });
    });

    const originalFetch = global.fetch;
    global.fetch = mockFetch as any;

    try {
      render(<InventoryView currentUser={mockUser} products={mockProducts} onUpdateProducts={() => {}} />);
      const stockTab = screen.getByText(/Stock y Alertas/i);
      fireEvent.click(stockTab);

      // Abrir modal de ingreso
      const ingresoBtn = screen.getByText(/\+ Ingreso Mercadería/i);
      fireEvent.click(ingresoBtn);

      expect(await screen.findByText(/Registrar Ingreso de Mercadería/i)).toBeInTheDocument();

      // Verificar que el formulario contenga la sección de comprometidos y vencimiento
      expect(screen.getByText(/Comprometido:/i)).toBeInTheDocument();
      expect(screen.getByText(/Vencimiento Comprometido \/ Próximo:/i)).toBeInTheDocument();
    } finally {
      global.fetch = originalFetch;
    }
  });
});
