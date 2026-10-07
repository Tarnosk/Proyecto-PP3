/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { User, RolePermissions, Product, PriceList, Customer, Promotion, Order, Supplier, Purchase, Vehicle, DeliveryRoute, CashMovement, CustomerAccount, CustomerAccountMovement, ElectronicInvoice } from './types';

export const INITIAL_USERS: User[] = [
  {
    id: '1',
    name: 'Don Alberto',
    username: 'alberto',
    email: 'alberto@distribuidorapigue.com',
    role: 'Administrador',
    status: 'Activo',
    phone: '2923-456789',
    lastLogin: '2026-07-13 08:30',
  },
  {
    id: '2',
    name: 'Carlos Gómez',
    username: 'carlos',
    email: 'carlos.gomez@distribuidorapigue.com',
    role: 'Repartidor',
    status: 'Activo',
    phone: '2923-543210',
    lastLogin: '2026-07-13 14:15',
  },
  {
    id: '3',
    name: 'Lucía Pérez',
    username: 'lucia',
    email: 'lucia.perez@distribuidorapigue.com',
    role: 'Preventista',
    status: 'Activo',
    phone: '2923-987654',
    lastLogin: '2026-07-13 07:45',
  },
  {
    id: '4',
    name: 'Marta Rodríguez',
    username: 'marta',
    email: 'marta.rod@distribuidorapigue.com',
    role: 'Administrativo',
    status: 'Activo',
    phone: '2923-234567',
    lastLogin: '2026-07-13 09:00',
  },
  {
    id: '5',
    name: 'Roberto Di Marco',
    username: 'roberto',
    email: 'roberto.dm@distribuidorapigue.com',
    role: 'Repartidor',
    status: 'Inactivo',
    phone: '2923-876543',
    lastLogin: '2026-06-28 14:20',
  },
];

export const INITIAL_PERMISSIONS: RolePermissions[] = [
  {
    role: 'Administrador',
    ventas: true,
    inventario: true,
    clientes: true,
    logistica: true,
    finanzas: true,
  },
  {
    role: 'Repartidor',
    ventas: false,
    inventario: false,
    clientes: false,
    logistica: true,
    finanzas: false,
  },
  {
    role: 'Preventista',
    ventas: true,
    inventario: false,
    clientes: true,
    logistica: false,
    finanzas: false,
  },
  {
    role: 'Administrativo',
    ventas: true,
    inventario: true,
    clientes: true,
    logistica: false,
    finanzas: true,
  },
];

export const INITIAL_PRODUCTS: Product[] = [
  {
    id: '1',
    code: 'ACE01',
    name: 'Aceite de Girasol Pureza 1.5L',
    category: 'Aceites',
    cost: 1200,
    price: 1800,
    iva: '21%',
    stock: 120,
    committedStock: 15,
    minStock: 20
  },
  {
    id: '2',
    code: 'ARR02',
    name: 'Arroz Integral Gallo 1kg',
    category: 'Almacén',
    cost: 800,
    price: 1200,
    iva: '21%',
    stock: 15,
    committedStock: 10,
    minStock: 30
  },
  {
    id: '3',
    code: 'LEC03',
    name: 'Leche Entera Larga Vida La Serenísima 1L',
    category: 'Lácteos',
    cost: 600,
    price: 900,
    iva: '10.5%',
    stock: 250,
    committedStock: 20,
    minStock: 50
  },
  {
    id: '4',
    code: 'FID04',
    name: 'Fideos Tallarines Lucchetti 500g',
    category: 'Almacén',
    cost: 500,
    price: 750,
    iva: '21%',
    stock: 350,
    committedStock: 0,
    minStock: 40
  },
  {
    id: '5',
    code: 'YER05',
    name: 'Yerba Mate Playadito 1kg',
    category: 'Infusiones',
    cost: 1400,
    price: 2100,
    iva: '21%',
    stock: 8,
    committedStock: 5,
    minStock: 15
  },
  {
    id: '6',
    code: 'QUE06',
    name: 'Queso Cremoso La Paulina (Horma ~4kg)',
    category: 'Fiambrería',
    cost: 19000,
    price: 28000,
    iva: '21%',
    stock: 45,
    committedStock: 6,
    minStock: 5
  },
  {
    id: '7',
    code: 'SAL07',
    name: 'Salame de Milán Cabaña Las Dinas 1kg',
    category: 'Fiambrería',
    cost: 6500,
    price: 9500,
    iva: '21%',
    stock: 30,
    committedStock: 4,
    minStock: 4
  },
  {
    id: '8',
    code: 'DUL08',
    name: 'Dulce de Leche Chimbote 500g',
    category: 'Almacén',
    cost: 1400,
    price: 2200,
    iva: '21%',
    stock: 60,
    committedStock: 12,
    minStock: 10
  },
  {
    id: '9',
    code: 'CER09',
    name: 'Cerveza Quilmes Clásica 1L',
    category: 'Bebidas',
    cost: 1100,
    price: 1600,
    iva: '21%',
    stock: 200,
    committedStock: 25,
    minStock: 30
  },
  {
    id: '10',
    code: 'GAS10',
    name: 'Gaseosa Coca-Cola Original 2.25L',
    category: 'Bebidas',
    cost: 1700,
    price: 2400,
    iva: '21%',
    stock: 180,
    committedStock: 30,
    minStock: 25
  },
  {
    id: '11',
    code: 'HAR11',
    name: 'Harina de Trigo Favorita 000 1kg',
    category: 'Almacén',
    cost: 480,
    price: 750,
    iva: '10.5%',
    stock: 400,
    minStock: 50
  },
  {
    id: '12',
    code: 'TOM12',
    name: 'Puré de Tomates Arcor 520g',
    category: 'Almacén',
    cost: 420,
    price: 650,
    iva: '21%',
    stock: 500,
    minStock: 80
  },
  {
    id: '13',
    code: 'AZU13',
    name: 'Azúcar Ledesma Clásica 1kg',
    category: 'Almacén',
    cost: 750,
    price: 1100,
    iva: '21%',
    stock: 300,
    minStock: 40
  },
  {
    id: '14',
    code: 'GAL14',
    name: 'Galletitas Traviatas Pack 300g',
    category: 'Almacén',
    cost: 600,
    price: 900,
    iva: '21%',
    stock: 150,
    minStock: 20
  }
];

export const INITIAL_PRICE_LISTS: PriceList[] = [
  {
    id: '1',
    name: 'Lista Minorista / Almacenes',
    description: 'Tarifa base al público y almacenes pequeños sin descuento.',
    discountPercentage: 0
  },
  {
    id: '2',
    name: 'Lista Mayorista / Comercios Grandes',
    description: 'Aplica un descuento del 15% sobre el precio base.',
    discountPercentage: 15
  },
  {
    id: '3',
    name: 'Lista Distribuidores / Supermercados',
    description: 'Aplica un descuento preferencial del 25% para compras por volumen.',
    discountPercentage: 25
  }
];

export const INITIAL_CUSTOMERS: Customer[] = [
  {
    id: '1',
    name: 'Autoservicio Belgrano (Pigüé)',
    cuit: '20-30456123-8',
    address: 'Belgrano 345, Pigüé',
    phone: '2923-475612',
    zone: 'Pigüé Centro',
    priceListId: '2' // Mayorista
  },
  {
    id: '2',
    name: 'Almacén Las Sierras (Saavedra)',
    cuit: '27-24890123-4',
    address: 'Av. Sarmiento 110, Saavedra',
    phone: '2923-491122',
    zone: 'Saavedra',
    priceListId: '1' // Minorista
  },
  {
    id: '3',
    name: 'Supermercado El Progreso (Coronel Suárez)',
    cuit: '30-71452391-5',
    address: 'Mitre 820, Coronel Suárez',
    phone: '2926-421155',
    zone: 'Coronel Suárez',
    priceListId: '3' // Distribuidores
  },
  {
    id: '4',
    name: 'Despensa Puan (Puan)',
    cuit: '20-18456129-2',
    address: 'San Martín 54, Puan',
    phone: '2923-488991',
    zone: 'Puan',
    priceListId: '1' // Minorista
  },
  {
    id: '5',
    name: 'Minimercado La Laguna (Carhué)',
    cuit: '23-35678912-9',
    address: 'Pellegrini 670, Carhué',
    phone: '2923-412233',
    zone: 'Carhué',
    priceListId: '2' // Mayorista
  },
  {
    id: '6',
    name: 'Autoservicio Espartillar (Espartillar)',
    cuit: '27-29384756-1',
    address: 'Libertad 421, Espartillar',
    phone: '2923-455661',
    zone: 'Espartillar',
    priceListId: '1' // Minorista
  },
  {
    id: '7',
    name: 'Fiambrería El Caldén (Pigüé)',
    cuit: '20-22341856-7',
    address: 'Av. Casey 280, Pigüé',
    phone: '2923-471245',
    zone: 'Pigüé Centro',
    priceListId: '3' // Distribuidores
  }
];

export const INITIAL_PROMOTIONS: Promotion[] = [
  {
    id: '1',
    name: 'Combo Almacenero - Fideos Especial',
    description: '10% de descuento adicional llevando 10 o más paquetes de Fideos Tallarines Lucchetti 500g.',
    discountPercentage: 10,
    requiredProductId: '4',
    requiredQuantity: 10
  },
  {
    id: '2',
    name: 'Promo Súper Lácteos',
    description: '5% de descuento especial llevando 24 o más unidades de Leche La Serenísima Larga Vida.',
    discountPercentage: 5,
    requiredProductId: '3',
    requiredQuantity: 24
  }
];

export const INITIAL_ORDERS: Order[] = [
  {
    id: 'ORD-1001',
    customerId: '1',
    customerName: 'Autoservicio Belgrano (Pigüé)',
    items: [
      {
        productId: '1',
        productName: 'Aceite de Girasol Pureza 1.5L',
        quantity: 10,
        unitPrice: 1530, // 1800 - 15% desc.
        total: 15300
      },
      {
        productId: '3',
        productName: 'Leche Entera Larga Vida La Serenísima 1L',
        quantity: 12,
        unitPrice: 765, // 900 - 15% desc.
        total: 9180
      }
    ],
    total: 24480,
    date: '2026-07-13',
    status: 'Entregado',
    notes: 'Entregar antes del mediodía en la sucursal de Pigüé.'
  },
  {
    id: 'ORD-1002',
    customerId: '2',
    customerName: 'Almacén Las Sierras (Saavedra)',
    items: [
      {
        productId: '5',
        productName: 'Yerba Mate Playadito 1kg',
        quantity: 5,
        unitPrice: 2100, // 100% (Minorista)
        total: 10500
      },
      {
        productId: '2',
        productName: 'Arroz Integral Gallo 1kg',
        quantity: 10,
        unitPrice: 1200,
        total: 12000
      }
    ],
    total: 22500,
    date: '2026-07-13',
    status: 'Pendiente',
    notes: 'Coordinar reparto con chofer Carlos.'
  },
  {
    id: 'ORD-1003',
    customerId: '1',
    customerName: 'Autoservicio Belgrano (Pigüé)',
    items: [
      {
        productId: '167',
        productName: 'Gaseosa Cola 2.25L',
        quantity: 15,
        unitPrice: 1760,
        total: 26400
      },
      {
        productId: '169',
        productName: 'Leche Entera 1L',
        quantity: 2,
        unitPrice: 1045,
        total: 2090
      },
      {
        productId: '171',
        productName: 'Arroz Largo Fino 1kg',
        quantity: 5,
        unitPrice: 2268,
        total: 11340
      }
    ],
    total: 39830,
    date: '2026-10-07',
    status: 'Pendiente',
    notes: 'Pedido con reparto programado a primera hora.'
  }
];

export const INITIAL_SUPPLIERS: Supplier[] = [
  {
    id: 'SUP-1',
    name: 'Molinos Río de la Plata',
    cuit: '30-50001091-2',
    phone: '11-4340-1111',
    email: 'contacto@molinos.com.ar',
    address: 'Av. Manuel Belgrano 2147, Victoria',
    category: 'Aceites'
  },
  {
    id: 'SUP-2',
    name: 'La Serenísima S.A.',
    cuit: '30-50002134-5',
    phone: '11-4649-3000',
    email: 'proveedores@laserenisima.com.ar',
    address: 'Adolfo Alsina 720, General Rodriguez',
    category: 'Lácteos'
  },
  {
    id: 'SUP-3',
    name: 'Establecimiento Las Marías',
    cuit: '30-51234901-4',
    phone: '11-4789-2345',
    email: 'ventas@lasmarias.com.ar',
    address: 'Ruta Nacional 14 Km 739, Corrientes',
    category: 'Infusiones'
  }
];

export const INITIAL_PURCHASES: Purchase[] = [
  {
    id: 'PUR-2001',
    supplierId: 'SUP-2',
    supplierName: 'La Serenísima S.A.',
    items: [
      {
        productId: '3',
        productName: 'Leche Entera Larga Vida La Serenísima 1L',
        quantity: 100,
        costPrice: 600,
        total: 60000
      }
    ],
    total: 60000,
    date: '2026-07-10 10:30',
    invoiceNumber: 'FAC-0001-00029348',
    status: 'Recibido',
    notes: 'Compra de stock mensual de leches de primera marca.'
  },
  {
    id: 'PUR-2002',
    supplierId: 'SUP-1',
    supplierName: 'Molinos Río de la Plata',
    items: [
      {
        productId: '1',
        productName: 'Aceite de Girasol Pureza 1.5L',
        quantity: 50,
        costPrice: 1200,
        total: 60000
      }
    ],
    total: 60000,
    date: '2026-07-12 08:15',
    invoiceNumber: 'REM-0004-11920',
    status: 'Recibido',
    notes: 'Recepción matutina de aceites.'
  }
];

export const INITIAL_VEHICLES: Vehicle[] = [
  {
    id: 'VEH-1',
    patent: 'AF-123-JK',
    model: 'Mercedes Sprinter 311',
    driverId: '2', // Carlos Gómez
    driverName: 'Carlos Gómez',
    capacityKg: 1500,
    status: 'Disponible'
  },
  {
    id: 'VEH-2',
    patent: 'AE-982-LO',
    model: 'Ford Transit Van',
    driverId: '5', // Roberto Di Marco
    driverName: 'Roberto Di Marco',
    capacityKg: 1200,
    status: 'Disponible'
  }
];

export const INITIAL_ROUTES: DeliveryRoute[] = [
  {
    id: 'RUT-5001',
    vehicleId: 'VEH-1',
    vehicleModel: 'Mercedes Sprinter 311',
    vehiclePatent: 'AF-123-JK',
    driverId: '2',
    driverName: 'Carlos Gómez',
    orderIds: ['ORD-1002'],
    date: '2026-07-13',
    status: 'Preparación',
    zone: 'Norte'
  }
];

export const INITIAL_CASH_MOVEMENTS: CashMovement[] = [
  {
    id: 'MOV-3001',
    type: 'Ingreso',
    amount: 125000,
    category: 'Cobranza',
    description: 'Cobro de Pedido ORD-1001 en efectivo',
    date: '2026-07-13 09:15',
    user: 'Carlos Gómez',
    paymentMethod: 'Efectivo',
    referenceId: 'ORD-1001'
  },
  {
    id: 'MOV-3002',
    type: 'Egreso',
    amount: 15000,
    category: 'Gasto General',
    description: 'Compra de combustible utilitario Ford Transit',
    date: '2026-07-13 10:20',
    user: 'Roberto Di Marco',
    paymentMethod: 'Efectivo'
  }
];

export const INITIAL_CUSTOMER_ACCOUNTS: CustomerAccount[] = [
  {
    customerId: '1',
    customerName: 'Autoservicio Belgrano (Pigüé)',
    balance: 45000,
    lastActivity: '2026-07-13 09:30'
  },
  {
    customerId: '2',
    customerName: 'Almacén Las Sierras (Saavedra)',
    balance: 0,
    lastActivity: '2026-07-13 16:15'
  },
  {
    customerId: '3',
    customerName: 'Supermercado El Progreso (Coronel Suárez)',
    balance: -12500,
    lastActivity: '2026-07-13 11:00'
  }
];

export const INITIAL_CUSTOMER_ACCOUNT_MOVEMENTS: CustomerAccountMovement[] = [
  {
    id: 'CAM-1',
    customerId: '1',
    type: 'Debito',
    amount: 45000,
    description: 'Carga de Pedido ORD-1002 en cuenta corriente',
    date: '2026-07-13 09:30',
    referenceId: 'ORD-1002'
  },
  {
    id: 'CAM-2',
    customerId: '3',
    type: 'Credito',
    amount: 12500,
    description: 'Pago adelantado recibido por transferencia bancaria',
    date: '2026-07-13 11:00'
  }
];

export const INITIAL_ELECTRONIC_INVOICES: ElectronicInvoice[] = [
  {
    id: 'INV-4001',
    orderId: 'ORD-1001',
    customerName: 'Autoservicio Belgrano (Pigüé)',
    customerCuit: '20-30456123-8',
    type: 'Factura B',
    pos: '0005',
    invoiceNumber: '00002145',
    netAmount: 103305.78,
    ivaAmount: 21694.22,
    total: 125000,
    cae: '73254109854732',
    caeDueDate: '2026-07-23',
    date: '2026-07-13',
    status: 'Aprobada'
  }
];

const USERS_KEY = 'erp_distribuidora_users';
const PERMISSIONS_KEY = 'erp_distribuidora_permissions';
const PRODUCTS_KEY = 'erp_distribuidora_products';
const PRICE_LISTS_KEY = 'erp_distribuidora_price_lists';
const CUSTOMERS_KEY = 'erp_distribuidora_customers';
const PROMOTIONS_KEY = 'erp_distribuidora_promotions';
const ORDERS_KEY = 'erp_distribuidora_orders';
const SUPPLIERS_KEY = 'erp_distribuidora_suppliers';
const PURCHASES_KEY = 'erp_distribuidora_purchases';
const VEHICLES_KEY = 'erp_distribuidora_vehicles';
const ROUTES_KEY = 'erp_distribuidora_routes';
const CASH_MOVEMENTS_KEY = 'erp_distribuidora_cash_movements';
const CUSTOMER_ACCOUNTS_KEY = 'erp_distribuidora_customer_accounts';
const CUSTOMER_ACCOUNT_MOVEMENTS_KEY = 'erp_distribuidora_customer_account_movements';
const ELECTRONIC_INVOICES_KEY = 'erp_distribuidora_electronic_invoices';

function performPigueMigrationIfNeeded(): void {
  const MIGRATE_KEY = 'erp_distribuidora_db_pigue_migrated_v7';
  if (typeof window !== 'undefined' && localStorage.getItem(MIGRATE_KEY) !== 'true') {
    // Write fresh Pigüé data!
    localStorage.setItem(USERS_KEY, JSON.stringify(INITIAL_USERS));
    localStorage.setItem(PRODUCTS_KEY, JSON.stringify(INITIAL_PRODUCTS));
    localStorage.setItem(CUSTOMERS_KEY, JSON.stringify(INITIAL_CUSTOMERS));
    localStorage.setItem(ORDERS_KEY, JSON.stringify(INITIAL_ORDERS));
    localStorage.setItem(SUPPLIERS_KEY, JSON.stringify(INITIAL_SUPPLIERS));
    localStorage.setItem(PURCHASES_KEY, JSON.stringify(INITIAL_PURCHASES));
    localStorage.setItem(ROUTES_KEY, JSON.stringify(INITIAL_ROUTES));
    localStorage.setItem(CASH_MOVEMENTS_KEY, JSON.stringify(INITIAL_CASH_MOVEMENTS));
    localStorage.setItem(CUSTOMER_ACCOUNTS_KEY, JSON.stringify(INITIAL_CUSTOMER_ACCOUNTS));
    localStorage.setItem(CUSTOMER_ACCOUNT_MOVEMENTS_KEY, JSON.stringify(INITIAL_CUSTOMER_ACCOUNT_MOVEMENTS));
    localStorage.setItem(ELECTRONIC_INVOICES_KEY, JSON.stringify(INITIAL_ELECTRONIC_INVOICES));
    
    // Reset other related custom keys to avoid mismatch
    localStorage.removeItem('erp_distribuidora_vehicles');
    localStorage.removeItem('erp_distribuidora_routes');
    localStorage.removeItem('erp_distribuidora_permissions');
    
    localStorage.setItem(MIGRATE_KEY, 'true');
    console.log("Migración a Base de Datos de Pigüé completada exitosamente.");
  }
}

export function loadUsers(): User[] {
  performPigueMigrationIfNeeded();
  const saved = localStorage.getItem(USERS_KEY);
  if (saved) {
    try {
      return JSON.parse(saved);
    } catch (e) {
      console.error(e);
    }
  }
  localStorage.setItem(USERS_KEY, JSON.stringify(INITIAL_USERS));
  return INITIAL_USERS;
}

export function saveUsers(users: User[]): void {
  localStorage.setItem(USERS_KEY, JSON.stringify(users));
}

export function loadPermissions(): RolePermissions[] {
  performPigueMigrationIfNeeded();
  const saved = localStorage.getItem(PERMISSIONS_KEY);
  if (saved) {
    try {
      return JSON.parse(saved);
    } catch (e) {
      console.error(e);
    }
  }
  localStorage.setItem(PERMISSIONS_KEY, JSON.stringify(INITIAL_PERMISSIONS));
  return INITIAL_PERMISSIONS;
}

export function savePermissions(permissions: RolePermissions[]): void {
  localStorage.setItem(PERMISSIONS_KEY, JSON.stringify(permissions));
}

// PRODUCTOS
export function loadProducts(): Product[] {
  performPigueMigrationIfNeeded();
  const saved = localStorage.getItem(PRODUCTS_KEY);
  if (saved) {
    try {
      return JSON.parse(saved);
    } catch (e) {
      console.error(e);
    }
  }
  localStorage.setItem(PRODUCTS_KEY, JSON.stringify(INITIAL_PRODUCTS));
  return INITIAL_PRODUCTS;
}

export function saveProducts(products: Product[]): void {
  localStorage.setItem(PRODUCTS_KEY, JSON.stringify(products));
}

// LISTAS DE PRECIOS
export function loadPriceLists(): PriceList[] {
  performPigueMigrationIfNeeded();
  const saved = localStorage.getItem(PRICE_LISTS_KEY);
  if (saved) {
    try {
      return JSON.parse(saved);
    } catch (e) {
      console.error(e);
    }
  }
  localStorage.setItem(PRICE_LISTS_KEY, JSON.stringify(INITIAL_PRICE_LISTS));
  return INITIAL_PRICE_LISTS;
}

export function savePriceLists(priceLists: PriceList[]): void {
  localStorage.setItem(PRICE_LISTS_KEY, JSON.stringify(priceLists));
}

// CLIENTES
export function loadCustomers(): Customer[] {
  performPigueMigrationIfNeeded();
  const saved = localStorage.getItem(CUSTOMERS_KEY);
  if (saved) {
    try {
      return JSON.parse(saved);
    } catch (e) {
      console.error(e);
    }
  }
  localStorage.setItem(CUSTOMERS_KEY, JSON.stringify(INITIAL_CUSTOMERS));
  return INITIAL_CUSTOMERS;
}

export function saveCustomers(customers: Customer[]): void {
  localStorage.setItem(CUSTOMERS_KEY, JSON.stringify(customers));
}

// PROMOCIONES
export function loadPromotions(): Promotion[] {
  performPigueMigrationIfNeeded();
  const saved = localStorage.getItem(PROMOTIONS_KEY);
  if (saved) {
    try {
      return JSON.parse(saved);
    } catch (e) {
      console.error(e);
    }
  }
  localStorage.setItem(PROMOTIONS_KEY, JSON.stringify(INITIAL_PROMOTIONS));
  return INITIAL_PROMOTIONS;
}

export function savePromotions(promotions: Promotion[]): void {
  localStorage.setItem(PROMOTIONS_KEY, JSON.stringify(promotions));
}

// PEDIDOS / VENTAS
export function loadOrders(): Order[] {
  performPigueMigrationIfNeeded();
  const saved = localStorage.getItem(ORDERS_KEY);
  if (saved) {
    try {
      return JSON.parse(saved);
    } catch (e) {
      console.error(e);
    }
  }
  localStorage.setItem(ORDERS_KEY, JSON.stringify(INITIAL_ORDERS));
  return INITIAL_ORDERS;
}

export function saveOrders(orders: Order[]): void {
  localStorage.setItem(ORDERS_KEY, JSON.stringify(orders));
}

// PROVEEDORES
export function loadSuppliers(): Supplier[] {
  performPigueMigrationIfNeeded();
  const saved = localStorage.getItem(SUPPLIERS_KEY);
  if (saved) {
    try {
      return JSON.parse(saved);
    } catch (e) {
      console.error(e);
    }
  }
  localStorage.setItem(SUPPLIERS_KEY, JSON.stringify(INITIAL_SUPPLIERS));
  return INITIAL_SUPPLIERS;
}

export function saveSuppliers(suppliers: Supplier[]): void {
  localStorage.setItem(SUPPLIERS_KEY, JSON.stringify(suppliers));
}

// COMPRAS
export function loadPurchases(): Purchase[] {
  performPigueMigrationIfNeeded();
  const saved = localStorage.getItem(PURCHASES_KEY);
  if (saved) {
    try {
      return JSON.parse(saved);
    } catch (e) {
      console.error(e);
    }
  }
  localStorage.setItem(PURCHASES_KEY, JSON.stringify(INITIAL_PURCHASES));
  return INITIAL_PURCHASES;
}

export function savePurchases(purchases: Purchase[]): void {
  localStorage.setItem(PURCHASES_KEY, JSON.stringify(purchases));
}

// VEHÍCULOS
export function loadVehicles(): Vehicle[] {
  performPigueMigrationIfNeeded();
  const saved = localStorage.getItem(VEHICLES_KEY);
  if (saved) {
    try {
      return JSON.parse(saved);
    } catch (e) {
      console.error(e);
    }
  }
  localStorage.setItem(VEHICLES_KEY, JSON.stringify(INITIAL_VEHICLES));
  return INITIAL_VEHICLES;
}

export function saveVehicles(vehicles: Vehicle[]): void {
  localStorage.setItem(VEHICLES_KEY, JSON.stringify(vehicles));
}

// HOJAS DE RUTA (LOGÍSTICA)
export function loadDeliveryRoutes(): DeliveryRoute[] {
  performPigueMigrationIfNeeded();
  const saved = localStorage.getItem(ROUTES_KEY);
  if (saved) {
    try {
      return JSON.parse(saved);
    } catch (e) {
      console.error(e);
    }
  }
  localStorage.setItem(ROUTES_KEY, JSON.stringify(INITIAL_ROUTES));
  return INITIAL_ROUTES;
}

export function saveDeliveryRoutes(routes: DeliveryRoute[]): void {
  localStorage.setItem(ROUTES_KEY, JSON.stringify(routes));
}

// CAJA DIARIA
export function loadCashMovements(): CashMovement[] {
  performPigueMigrationIfNeeded();
  const saved = localStorage.getItem(CASH_MOVEMENTS_KEY);
  if (saved) {
    try {
      return JSON.parse(saved);
    } catch (e) {
      console.error(e);
    }
  }
  localStorage.setItem(CASH_MOVEMENTS_KEY, JSON.stringify(INITIAL_CASH_MOVEMENTS));
  return INITIAL_CASH_MOVEMENTS;
}

export function saveCashMovements(movements: CashMovement[]): void {
  localStorage.setItem(CASH_MOVEMENTS_KEY, JSON.stringify(movements));
}

// CUENTAS CORRIENTES CLIENTES
export function loadCustomerAccounts(): CustomerAccount[] {
  performPigueMigrationIfNeeded();
  const saved = localStorage.getItem(CUSTOMER_ACCOUNTS_KEY);
  if (saved) {
    try {
      return JSON.parse(saved);
    } catch (e) {
      console.error(e);
    }
  }
  localStorage.setItem(CUSTOMER_ACCOUNTS_KEY, JSON.stringify(INITIAL_CUSTOMER_ACCOUNTS));
  return INITIAL_CUSTOMER_ACCOUNTS;
}

export function saveCustomerAccounts(accounts: CustomerAccount[]): void {
  localStorage.setItem(CUSTOMER_ACCOUNTS_KEY, JSON.stringify(accounts));
}

// MOVIMIENTOS CUENTAS CORRIENTES
export function loadCustomerAccountMovements(): CustomerAccountMovement[] {
  performPigueMigrationIfNeeded();
  const saved = localStorage.getItem(CUSTOMER_ACCOUNT_MOVEMENTS_KEY);
  if (saved) {
    try {
      return JSON.parse(saved);
    } catch (e) {
      console.error(e);
    }
  }
  localStorage.setItem(CUSTOMER_ACCOUNT_MOVEMENTS_KEY, JSON.stringify(INITIAL_CUSTOMER_ACCOUNT_MOVEMENTS));
  return INITIAL_CUSTOMER_ACCOUNT_MOVEMENTS;
}

export function saveCustomerAccountMovements(movements: CustomerAccountMovement[]): void {
  localStorage.setItem(CUSTOMER_ACCOUNT_MOVEMENTS_KEY, JSON.stringify(movements));
}

// FACTURACIÓN ELECTRÓNICA
export function loadElectronicInvoices(): ElectronicInvoice[] {
  performPigueMigrationIfNeeded();
  const saved = localStorage.getItem(ELECTRONIC_INVOICES_KEY);
  if (saved) {
    try {
      return JSON.parse(saved);
    } catch (e) {
      console.error(e);
    }
  }
  localStorage.setItem(ELECTRONIC_INVOICES_KEY, JSON.stringify(INITIAL_ELECTRONIC_INVOICES));
  return INITIAL_ELECTRONIC_INVOICES;
}

export function saveElectronicInvoices(invoices: ElectronicInvoice[]): void {
  localStorage.setItem(ELECTRONIC_INVOICES_KEY, JSON.stringify(invoices));
}
