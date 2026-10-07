/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

export type UserRole = 'Administrador' | 'Repartidor' | 'Preventista' | 'Administrativo';

export interface User {
  id: string;
  name: string;
  username: string;
  email: string;
  role: UserRole;
  status: 'Activo' | 'Inactivo';
  phone: string;
  lastLogin?: string;
}

export interface RolePermissions {
  role: UserRole;
  ventas: boolean;
  inventario: boolean;
  clientes: boolean;
  logistica: boolean;
  finanzas: boolean;
}

export interface Categoria {
  id_categoria: number;
  nombre: string;
  descripcion?: string;
  estado?: string;
}

export interface Marca {
  id_marca: number;
  nombre: string;
  descripcion?: string;
  estado?: string;
}

export interface Product {
  id: string;
  id_producto?: number;
  code: string;
  codigo?: string;
  name: string;
  nombre?: string;
  descripcion?: string;
  category: string;
  categoria_nombre?: string;
  id_categoria?: number;
  id_marca?: number;
  marca_nombre?: string;
  cost: number;
  price: number; // Precio base / minorista
  precio_unitario?: number;
  precio_mayorista?: number;
  precio_minorista?: number;
  iva: '10.5%' | '21%' | '0%';
  stock: number;
  stock_disponible?: number;
  committedStock?: number;
  minStock: number;
  stock_minimo?: number;
  estado?: 'activo' | 'inactivo' | string;
  estado_alerta?: 'normal' | 'bajo' | 'critico' | string;
  barcode?: string;
  supplierId?: string;
  supplierName?: string;
  priceWholesale?: number;
  priceDistributor?: number;
  pricePromo?: number;
  maxDiscount?: number;
  fecha_alta?: string;
  fecha_modificacion?: string;
  fecha_desactivacion?: string;
  id_usuario_carga?: number;
  usuario_carga_nombre?: string;
  id_usuario_modificacion?: number;
  usuario_modificacion_nombre?: string;
  id_ubicacion?: number;
  ubicacion_nombre?: string;
}

export interface PriceList {
  id: string;
  name: string;
  description: string;
  discountPercentage: number; // Porcentaje de descuento o recargo (positivo o negativo)
}

export interface Customer {
  id: string;
  name: string;
  cuit: string;
  address: string;
  phone: string;
  zone: string;
  priceListId: string;
}

export interface Promotion {
  id: string;
  name: string;
  description: string;
  discountPercentage: number;
  requiredProductId?: string;
  requiredQuantity?: number;
  discountAmount?: number;
  items?: { productId: string; productName: string; quantity: number }[];
}

export interface OrderItem {
  productId: string;
  productName: string;
  quantity: number;
  unitPrice: number;
  total: number;
  priceListId?: string;
  discountPercentage?: number;
  discountAmount?: number;
}

export interface Order {
  id: string;
  customerId: string;
  customerName: string;
  items: OrderItem[];
  total: number;
  date: string;
  status: 'Pendiente' | 'Facturado' | 'Remitido' | 'Entregado' | 'Cancelado' | 'Demorado';
  notes: string;
  invoiceNumber?: string;
}

export interface Supplier {
  id: string;
  id_proveedor?: number;
  name: string; // razón social
  razon_social?: string;
  cuit: string;
  phone: string;
  telefono?: string;
  email: string;
  correo?: string;
  address: string;
  category: string; // e.g. "Bebidas", "Lácteos", "Almacén", "Aceites"
  estado?: 'activo' | 'inactivo' | string;
  plazo_entrega_dias?: number;
  fecha_alta?: string;
  fecha_modificacion?: string;
  fecha_desactivacion?: string;
  id_usuario_carga?: number;
  usuario_carga_nombre?: string;
  id_usuario_modificacion?: number;
  usuario_modificacion_nombre?: string;
  productos_asociados?: any[];
}

export interface PurchaseItem {
  productId: string;
  id_producto?: number;
  productName: string;
  quantity: number;
  costPrice: number;
  precio_unitario?: number;
  total: number;
  id_unidad?: number;
}

export interface Purchase {
  id: string;
  id_compra?: number;
  numero_compra?: string;
  numero_comprobante?: string;
  supplierId: string;
  id_proveedor?: number;
  supplierName: string;
  proveedor?: string;
  items: PurchaseItem[];
  total: number;
  importe_total?: number;
  saldo_pendiente?: number;
  date: string; // YYYY-MM-DD HH:MM
  fecha_compra?: string;
  fecha_vencimiento?: string;
  invoiceNumber?: string; // Nro de remito o factura del proveedor
  status: 'Recibido' | 'Pendiente' | 'Cancelado';
  estado?: 'pendiente' | 'parcial' | 'completada' | 'cancelada' | string;
  notes?: string;
}

export interface Vehicle {
  id: string;
  patent: string;
  model: string;
  driverId: string;
  driverName: string;
  capacityKg: number;
  status: 'Disponible' | 'En Viaje' | 'En Taller';
}

export interface DeliveryRoute {
  id: string;
  vehicleId: string;
  vehicleModel: string;
  vehiclePatent: string;
  driverId: string;
  driverName: string;
  orderIds: string[];
  date: string;
  status: 'Preparación' | 'En Tránsito' | 'Completado' | 'Cancelado';
  zone: string;
}

export interface CashMovement {
  id: string;
  type: 'Ingreso' | 'Egreso';
  amount: number;
  category: 'Cobranza' | 'Pago Proveedor' | 'Gasto General' | 'Sueldos' | 'Impuestos' | 'Ajuste';
  description: string;
  date: string; // YYYY-MM-DD HH:MM
  user: string;
  paymentMethod: 'Efectivo' | 'Transferencia' | 'Cheque' | 'Tarjeta';
  referenceId?: string; // ID de Pedido o de Compra si corresponde
}

export interface CustomerAccount {
  customerId: string;
  customerName: string;
  balance: number; // positivo es que debe, negativo es saldo a favor
  lastActivity: string;
}

export interface CustomerAccountMovement {
  id: string;
  customerId: string;
  type: 'Debito' | 'Credito'; // Debito incrementa deuda (pedido cargado en cuenta), Credito disminuye deuda (pago recibido)
  amount: number;
  description: string;
  date: string;
  referenceId?: string; // ID de venta/pago
}

export interface ElectronicInvoice {
  id: string; // Comp-XXXX
  orderId?: string;
  customerName: string;
  customerCuit: string;
  type: 'Factura A' | 'Factura B' | 'Factura C';
  pos: string; // "0005"
  invoiceNumber: string; // "00001234"
  netAmount: number;
  ivaAmount: number;
  total: number;
  cae: string; // Código de Autorización Electrónico
  caeDueDate: string; // Fecha de vto del CAE
  date: string;
  status: 'Aprobada' | 'Rechazada';
}
