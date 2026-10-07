/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { apiClient, ApiResponse } from './apiClient';
import { Purchase, PurchaseItem } from '../types';

export function mapBackendCompraToPurchase(c: any): Purchase {
  const items: PurchaseItem[] = Array.isArray(c.detalles)
    ? c.detalles.map((d: any) => ({
        productId: String(d.id_producto),
        id_producto: d.id_producto,
        productName: d.producto?.nombre || d.producto?.descripcion || `Producto #${d.id_producto}`,
        quantity: Number(d.cantidad || 0),
        costPrice: Number(d.precio_unitario || 0),
        precio_unitario: Number(d.precio_unitario || 0),
        total: Number(d.subtotal || (d.cantidad * d.precio_unitario) || 0),
        id_unidad: d.id_unidad,
      }))
    : [];

  let statusMapped: 'Recibido' | 'Pendiente' | 'Cancelado' = 'Pendiente';
  if (c.estado === 'completada' || c.estado === 'recibida' || c.estado === 'Recibido') {
    statusMapped = 'Recibido';
  } else if (c.estado === 'cancelada' || c.estado === 'Cancelado') {
    statusMapped = 'Cancelado';
  }

  return {
    id: String(c.id_compra || c.id),
    id_compra: c.id_compra,
    numero_compra: c.numero_compra,
    numero_comprobante: c.numero_comprobante,
    supplierId: String(c.id_proveedor || ''),
    id_proveedor: c.id_proveedor,
    supplierName: c.proveedor || c.proveedor_nombre || `Proveedor #${c.id_proveedor}`,
    proveedor: c.proveedor || c.proveedor_nombre,
    items,
    total: Number(c.importe_total || 0),
    importe_total: Number(c.importe_total || 0),
    saldo_pendiente: Number(c.saldo_pendiente || 0),
    date: c.fecha_compra || new Date().toISOString().slice(0, 10),
    fecha_compra: c.fecha_compra,
    fecha_vencimiento: c.fecha_vencimiento,
    invoiceNumber: c.numero_comprobante,
    status: statusMapped,
    estado: c.estado,
    notes: c.observaciones || '',
  };
}

export const comprasService = {
  getAll: async (params?: { search?: string; estado?: string; id_proveedor?: number }): Promise<Purchase[]> => {
    const res = await apiClient.get<ApiResponse<any[]>>('/compras', params);
    const data = res.data || (Array.isArray(res) ? res : []);
    return data.map(mapBackendCompraToPurchase);
  },

  getById: async (id: number | string): Promise<Purchase> => {
    const res = await apiClient.get<ApiResponse<any>>(`/compras/${id}`);
    const data = res.data || res;
    return mapBackendCompraToPurchase(data);
  },

  create: async (compraData: {
    id_proveedor: number;
    numero_comprobante?: string;
    fecha_compra?: string;
    fecha_vencimiento?: string;
    items: Array<{
      id_producto: number;
      cantidad: number;
      precio_unitario: number;
      id_unidad?: number;
    }>;
  }): Promise<Purchase> => {
    const res = await apiClient.post<ApiResponse<any>>('/compras', compraData);
    const data = res.data || res;
    return mapBackendCompraToPurchase(data);
  },

  cancelar: async (id: number | string, motivo?: string): Promise<any> => {
    return apiClient.patch(`/compras/${id}/cancelar`, { motivo });
  },

  registrarRecepcion: async (id: number | string, recepcionData: {
    items: Array<{
      id_detalle: number;
      cantidad_recibida: number;
      nro_lote?: string;
      fecha_vencimiento?: string;
    }>;
  }): Promise<any> => {
    return apiClient.post(`/compras/${id}/recepciones`, recepcionData);
  },

  registrarPago: async (id: number | string, pagoData: {
    monto: number;
    forma_pago: 'efectivo' | 'transferencia' | 'cheque' | string;
    comprobante?: string;
  }): Promise<any> => {
    return apiClient.post(`/compras/${id}/pagos`, pagoData);
  },

  getPagos: async (id: number | string): Promise<any[]> => {
    const res = await apiClient.get<ApiResponse<any[]>>(`/compras/${id}/pagos`);
    return res.data || [];
  },

  // Órdenes de Compra (PC01 - PC06)
  getOrdenesCompra: async (params?: any): Promise<any[]> => {
    const res = await apiClient.get<ApiResponse<any[]>>('/ordenes-compra', params);
    return res.data || (Array.isArray(res) ? res : []);
  },

  createOrdenCompra: async (data: any): Promise<any> => {
    return apiClient.post('/ordenes-compra', data);
  },

  cancelarOrdenCompra: async (id: number | string, motivo?: string): Promise<any> => {
    return apiClient.patch(`/ordenes-compra/${id}/cancelar`, { motivo });
  },

  enviarOrdenCompra: async (id: number | string): Promise<any> => {
    return apiClient.patch(`/ordenes-compra/${id}/enviar`);
  },

  cambiarEstadoOrdenCompra: async (id: number | string, estado: string): Promise<any> => {
    return apiClient.patch(`/ordenes-compra/${id}/cambiar-estado`, { estado });
  },

  // Sugerencias de Reposición (PC07)
  getSugerenciasReposicion: async (): Promise<any[]> => {
    const res = await apiClient.get<ApiResponse<any[]>>('/sugerencias-reposicion');
    return res.data || [];
  },

  generarSugerenciasReposicion: async (): Promise<any> => {
    return apiClient.post('/sugerencias-reposicion/generar');
  },
};
