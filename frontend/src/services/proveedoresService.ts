/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { apiClient, ApiResponse } from './apiClient';
import { Supplier } from '../types';

export function mapBackendSupplierToSupplier(p: any): Supplier {
  return {
    id: String(p.id_proveedor),
    id_proveedor: p.id_proveedor,
    name: p.razon_social || '',
    razon_social: p.razon_social || '',
    cuit: p.cuit || '',
    phone: p.telefono || '',
    telefono: p.telefono || '',
    email: p.correo || p.email || '',
    correo: p.correo || p.email || '',
    address: p.direccion || 'No especificada',
    category: p.rubro || 'General',
    estado: p.estado || 'activo',
    plazo_entrega_dias: p.plazo_entrega_dias !== null ? Number(p.plazo_entrega_dias) : undefined,
    fecha_alta: p.fecha_alta,
    fecha_modificacion: p.fecha_modificacion,
    fecha_desactivacion: p.fecha_desactivacion,
    id_usuario_carga: p.id_usuario_carga,
    usuario_carga_nombre: p.usuario_carga_nombre,
    id_usuario_modificacion: p.id_usuario_modificacion,
    usuario_modificacion_nombre: p.usuario_modificacion_nombre,
    productos_asociados: p.productos_asociados || [],
  };
}

export const proveedoresService = {
  getAll: async (params?: { search?: string; estado?: string }): Promise<Supplier[]> => {
    const res = await apiClient.get<ApiResponse<any[]>>('/proveedores', params);
    const data = res.data || (Array.isArray(res) ? res : []);
    return data.map(mapBackendSupplierToSupplier);
  },

  getById: async (id: number | string): Promise<Supplier> => {
    const res = await apiClient.get<ApiResponse<any>>(`/proveedores/${id}`);
    const data = res.data || res;
    return mapBackendSupplierToSupplier(data);
  },

  create: async (supplier: Partial<Supplier>): Promise<Supplier> => {
    const payload = {
      razon_social: supplier.name || supplier.razon_social,
      cuit: supplier.cuit,
      telefono: supplier.phone || supplier.telefono,
      correo: supplier.email || supplier.correo,
      plazo_entrega_dias: supplier.plazo_entrega_dias,
    };
    const res = await apiClient.post<ApiResponse<any>>('/proveedores', payload);
    const data = res.data || res;
    return mapBackendSupplierToSupplier(data);
  },

  update: async (id: number | string, supplier: Partial<Supplier>): Promise<Supplier> => {
    const payload = {
      razon_social: supplier.name || supplier.razon_social,
      cuit: supplier.cuit,
      telefono: supplier.phone || supplier.telefono,
      correo: supplier.email || supplier.correo,
      plazo_entrega_dias: supplier.plazo_entrega_dias,
    };
    const res = await apiClient.patch<ApiResponse<any>>(`/proveedores/${id}`, payload);
    const data = res.data || res;
    return mapBackendSupplierToSupplier(data);
  },

  desactivar: async (id: number | string): Promise<void> => {
    await apiClient.patch(`/proveedores/${id}/desactivar`);
  },

  activar: async (id: number | string): Promise<void> => {
    await apiClient.patch(`/proveedores/${id}/activar`);
  },

  asociarProductos: async (
    idProveedor: number | string,
    productos: Array<{ id_producto: number; precio_acordado?: number; es_proveedor_principal?: boolean }>
  ): Promise<any> => {
    return apiClient.post(`/proveedores/${idProveedor}/productos`, { productos });
  },

  desasociarProducto: async (idProductoProveedor: number | string): Promise<any> => {
    return apiClient.delete(`/proveedores/productos/${idProductoProveedor}`);
  },

  definirPrincipal: async (idProductoProveedor: number | string): Promise<any> => {
    return apiClient.post(`/proveedores/productos/${idProductoProveedor}/principal`);
  },

  getPlazos: async (idProveedor: number | string): Promise<any[]> => {
    const res = await apiClient.get<ApiResponse<any>>(`/proveedores/${idProveedor}/plazos`);
    const data = res.data;
    if (data && Array.isArray(data.historial)) {
      return data.historial;
    }
    return Array.isArray(data) ? data : [];
  },

  registrarPlazo: async (
    idProveedor: number | string,
    plazo_entrega_dias: number,
    motivo?: string
  ): Promise<any> => {
    return apiClient.post(`/proveedores/${idProveedor}/plazo`, {
      plazo_entrega_dias,
      motivo,
    });
  },
};
