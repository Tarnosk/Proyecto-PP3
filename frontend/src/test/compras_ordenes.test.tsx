import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import React from 'react';
import { PurchasesView } from '../components/PurchasesView';
import { Purchase, Supplier, Product, User } from '../types';

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
    email: 'ventas@molinos.com.ar',
    address: 'Av. Paseo Colón 746',
    category: 'Alimentos',
    estado: 'activo',
    plazo_entrega_dias: 3,
    productos_asociados: [
      {
        id_producto: 1,
        id_producto_proveedor: 10,
        nombre: 'Harina 000 1Kg',
        precio_acordado: 750,
        es_proveedor_principal: 1,
      }
    ]
  }
];

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
    stock: 5, // Bajo stock (5 <= 20) => sugerencia de reposición PC07
    minStock: 20,
    optimalStock: 100,
    status: 'activo',
  }
];

const mockPurchases: Purchase[] = [
  {
    id: 'PUR-1',
    id_compra: 101,
    supplierId: 'SUPP-1',
    supplierName: 'Molinos Río de la Plata',
    invoiceNumber: '0001-00045129',
    total: 15000,
    paidAmount: 5000,
    debtAmount: 10000,
    saldo_pendiente: 10000,
    status: 'Parcial',
    date: '2026-10-01 10:00',
    items: [
      {
        productId: 'PROD-1',
        productName: 'Harina 000 1Kg',
        quantity: 20,
        costPrice: 750,
        total: 15000,
      }
    ]
  }
];

describe('Módulo Compras & Pedidos de Compra (C01-C09, PC01-PC07)', () => {

  it('C01 & C02: Listado y consulta de compras registradas', () => {
    render(
      <PurchasesView
        currentUser={mockUser}
        purchases={mockPurchases}
        suppliers={mockSuppliers}
        products={mockProducts}
        onUpdatePurchases={() => {}}
      />
    );

    expect(screen.getAllByText(/Molinos Río de la Plata/i).length).toBeGreaterThan(0);
    expect(screen.getByText(/0001-00045129/i)).toBeInTheDocument();
    expect(screen.getAllByText(/\$15.000/i).length).toBeGreaterThan(0);
  });

  it('C08 & C09: Visualización de saldos, pagos y deudas con proveedores', () => {
    render(
      <PurchasesView
        currentUser={mockUser}
        purchases={mockPurchases}
        suppliers={mockSuppliers}
        products={mockProducts}
        onUpdatePurchases={() => {}}
      />
    );

    // Saldo adeudado de 10.000
    expect(screen.getAllByText(/\$10.000/i).length).toBeGreaterThan(0);
    expect(screen.getByTitle(/Registrar pago/i)).toBeInTheDocument();
  });

  it('C06: Modal para registrar recepciones parciales con lotes y vencimientos', () => {
    render(
      <PurchasesView
        currentUser={mockUser}
        purchases={mockPurchases}
        suppliers={mockSuppliers}
        products={mockProducts}
        onUpdatePurchases={() => {}}
      />
    );

    const receptionBtn = screen.getByTitle(/Recepción parcial con lotes/i);
    fireEvent.click(receptionBtn);

    expect(screen.getAllByText(/Registrar Recepción Parcial/i).length).toBeGreaterThanOrEqual(1);
    expect(screen.getByText(/Ingresar a Inventario/i)).toBeInTheDocument();
  });

  it('C08: Modal para registrar pagos a proveedores', () => {
    render(
      <PurchasesView
        currentUser={mockUser}
        purchases={mockPurchases}
        suppliers={mockSuppliers}
        products={mockProducts}
        onUpdatePurchases={() => {}}
      />
    );

    const payBtn = screen.getByTitle(/Registrar pago/i);
    fireEvent.click(payBtn);

    expect(screen.getByText(/Registrar Pago de Compra/i)).toBeInTheDocument();
    expect(screen.getByText(/Efectivo/i)).toBeInTheDocument();
    expect(screen.getByText(/Transferencia bancaria/i)).toBeInTheDocument();
  });

  it('PC01 & PC02: Pestaña de Órdenes de Compra y ciclo de estados', async () => {
    render(
      <PurchasesView
        currentUser={mockUser}
        purchases={mockPurchases}
        suppliers={mockSuppliers}
        products={mockProducts}
        onUpdatePurchases={() => {}}
      />
    );

    const ordersTab = screen.getByRole('button', { name: /Órdenes de Compra/i });
    fireEvent.click(ordersTab);

    expect(await screen.findByText(/Emitir Orden de Compra/i)).toBeInTheDocument();
    expect(screen.getByText(/Filtrar por Estado:/i)).toBeInTheDocument();
  });

  it('PC07: Sugerencias Automáticas de Reposición según stock mínimo', async () => {
    render(
      <PurchasesView
        currentUser={mockUser}
        purchases={mockPurchases}
        suppliers={mockSuppliers}
        products={mockProducts}
        onUpdatePurchases={() => {}}
      />
    );

    const suggestionsTab = screen.getByText(/Sugerencias Reposición/i);
    fireEvent.click(suggestionsTab);

    expect(await screen.findByText(/Generador Automático de Sugerencias/i)).toBeInTheDocument();
    // Harina 000 tiene stock 5 <= minStock 20, óptimo 100 => sugerido: 100 - 5 = 95 u.
    expect(await screen.findByText(/Harina 000 1Kg/i)).toBeInTheDocument();
    expect(await screen.findByText(/95 u\./i)).toBeInTheDocument();
    expect(await screen.findByText(/Crear Orden Directa/i)).toBeInTheDocument();
  });
});
