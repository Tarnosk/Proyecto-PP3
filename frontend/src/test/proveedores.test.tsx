import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import React from 'react';
import { CustomersView } from '../components/CustomersView';
import { Supplier, Customer, Product, PriceList, User, Purchase } from '../types';

const mockUser: User = {
  id: 'USR-1',
  name: 'Admin Test',
  email: 'admin@distribuidora.com',
  role: 'Administrador',
};

const mockSuppliers: Supplier[] = [
  {
    id: 'SUPP-1',
    id_proveedor: 1,
    name: 'Molinos Río de la Plata',
    razon_social: 'Molinos Río de la Plata SA',
    cuit: '30-50001234-9',
    phone: '011-4321-0000',
    email: 'contacto@molinos.com.ar',
    address: 'Av. Paseo Colón 746, CABA',
    category: 'Alimentos',
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
      }
    ]
  },
  {
    id: 'SUPP-2',
    id_proveedor: 2,
    name: 'Arcor Alimentos',
    razon_social: 'Arcor SAIC',
    cuit: '30-50274263-5',
    phone: '0800-444-2726',
    email: 'pedidos@arcor.com',
    address: 'Chacabuco 1160, Córdoba',
    category: 'Golosinas',
    estado: 'inactivo',
    plazo_entrega_dias: 7,
    productos_asociados: []
  }
];

const mockCustomers: Customer[] = [];
const mockPriceLists: PriceList[] = [];
const mockProducts: Product[] = [
  {
    id: 'PROD-1',
    id_producto: 1,
    code: 'HAR-001',
    name: 'Harina 000 1Kg',
    description: 'Harina de trigo',
    category: 'Alimentos',
    brand: 'Blancaflor',
    cost: 750,
    price: 1200,
    stock: 80,
    minStock: 20,
    optimalStock: 150,
    status: 'activo',
  }
];
const mockPurchases: Purchase[] = [];

describe('Módulo Proveedores (PV01-PV08)', () => {
  it('PV01 & PV02: Visualiza proveedores con ficha técnica, CUIT, rubro y estado', () => {
    render(
      <CustomersView
        currentUser={mockUser}
        customers={mockCustomers}
        priceLists={mockPriceLists}
        products={mockProducts}
        suppliers={mockSuppliers}
        purchases={mockPurchases}
        onUpdateCustomers={() => {}}
        onUpdateSuppliers={() => {}}
        onUpdatePurchases={() => {}}
        onUpdateProducts={() => {}}
      />
    );

    const suppTab = screen.getByText(/Proveedores \/ Fábricas/i);
    fireEvent.click(suppTab);

    expect(screen.getAllByText(/Molinos Río de la Plata/i).length).toBeGreaterThan(0);
    expect(screen.getByText(/30-50001234-9/i)).toBeInTheDocument();
    expect(screen.getAllByText(/Alimentos/i).length).toBeGreaterThan(0);
    expect(screen.getAllByText(/Arcor Alimentos/i).length).toBeGreaterThan(0);
  });

  it('PV07: Indicador de plazo de entrega en días acordado', () => {
    render(
      <CustomersView
        currentUser={mockUser}
        customers={mockCustomers}
        priceLists={mockPriceLists}
        products={mockProducts}
        suppliers={mockSuppliers}
        purchases={mockPurchases}
        onUpdateCustomers={() => {}}
        onUpdateSuppliers={() => {}}
        onUpdatePurchases={() => {}}
        onUpdateProducts={() => {}}
      />
    );

    const suppTab = screen.getByText(/Proveedores \/ Fábricas/i);
    fireEvent.click(suppTab);

    expect(screen.getByText(/3 días/i)).toBeInTheDocument();
    expect(screen.getByText(/7 días/i)).toBeInTheDocument();
  });

  it('PV04: Desactivación lógica y filtro por estado activo/inactivo', async () => {
    const handleUpdateSuppliers = vi.fn();
    window.confirm = vi.fn(() => true);

    render(
      <CustomersView
        currentUser={mockUser}
        customers={mockCustomers}
        priceLists={mockPriceLists}
        products={mockProducts}
        suppliers={mockSuppliers}
        purchases={mockPurchases}
        onUpdateCustomers={() => {}}
        onUpdateSuppliers={handleUpdateSuppliers}
        onUpdatePurchases={() => {}}
        onUpdateProducts={() => {}}
      />
    );

    const suppTab = screen.getByText(/Proveedores \/ Fábricas/i);
    fireEvent.click(suppTab);

    const deactivateBtn = screen.getByTitle(/Desactivar proveedor/i);
    fireEvent.click(deactivateBtn);

    await waitFor(() => {
      expect(handleUpdateSuppliers).toHaveBeenCalled();
    });
  });

  it('PV05: Modal de gestión de productos asociados y costos pactados', async () => {
    render(
      <CustomersView
        currentUser={mockUser}
        customers={mockCustomers}
        priceLists={mockPriceLists}
        products={mockProducts}
        suppliers={mockSuppliers}
        purchases={mockPurchases}
        onUpdateCustomers={() => {}}
        onUpdateSuppliers={() => {}}
        onUpdatePurchases={() => {}}
        onUpdateProducts={() => {}}
      />
    );

    const suppTab = screen.getByText(/Proveedores \/ Fábricas/i);
    fireEvent.click(suppTab);

    const assocBtn = screen.getAllByTitle(/Asociar productos con precios acordados/i)[0];
    fireEvent.click(assocBtn);

    expect(await screen.findByText(/Productos Asociados & Costos Pactados/i)).toBeInTheDocument();
    const harinaItems = await screen.findAllByText(/Harina 000 1Kg/i);
    expect(harinaItems.length).toBeGreaterThanOrEqual(1);
    expect(screen.getAllByText(/Principal/i).length).toBeGreaterThanOrEqual(1);
  });

  it('PV07: Modal de plazos de entrega e historial cronológico', async () => {
    render(
      <CustomersView
        currentUser={mockUser}
        customers={mockCustomers}
        priceLists={mockPriceLists}
        products={mockProducts}
        suppliers={mockSuppliers}
        purchases={mockPurchases}
        onUpdateCustomers={() => {}}
        onUpdateSuppliers={() => {}}
        onUpdatePurchases={() => {}}
        onUpdateProducts={() => {}}
      />
    );

    const suppTab = screen.getByText(/Proveedores \/ Fábricas/i);
    fireEvent.click(suppTab);

    const leadTimeBtn = screen.getAllByTitle(/Ver plazos de entrega e historial/i)[0];
    fireEvent.click(leadTimeBtn);

    expect(await screen.findByText(/Plazos de Entrega e Historial/i)).toBeInTheDocument();
    expect(screen.getByText(/Registrar Nuevo Plazo de Entrega Acordado/i)).toBeInTheDocument();
  });

  it('PV01: Formulario para dar de alta nuevo proveedor con plazo habitual', () => {
    render(
      <CustomersView
        currentUser={mockUser}
        customers={mockCustomers}
        priceLists={mockPriceLists}
        products={mockProducts}
        suppliers={mockSuppliers}
        purchases={mockPurchases}
        onUpdateCustomers={() => {}}
        onUpdateSuppliers={() => {}}
        onUpdatePurchases={() => {}}
        onUpdateProducts={() => {}}
      />
    );

    const suppTab = screen.getByText(/Proveedores \/ Fábricas/i);
    fireEvent.click(suppTab);

    const newBtn = screen.getByText(/Nuevo Proveedor/i);
    fireEvent.click(newBtn);

    expect(screen.getByText(/Registrar Nuevo Proveedor \/ Fábrica/i)).toBeInTheDocument();
    expect(screen.getByText(/Plazo de Entrega Habitual \(Días\)/i)).toBeInTheDocument();
  });
});
