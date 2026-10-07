/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { apiClient, ApiResponse } from './apiClient';
import { Product, Categoria, Marca } from '../types';

export function mapBackendProductToProduct(p: any): Product {
  const precioUnitario = Number(p.precio_unitario ?? p.precio_minorista ?? p.price ?? 0);
  const stockDisp = Number(p.stock_disponible ?? p.stock ?? 0);
  const stockMin = Number(p.stock_minimo ?? p.minStock ?? 10);
  const committedStock = Number(p.stock_comprometido ?? p.committedStock ?? p.comprometido ?? 0);

  return {
    id: String(p.id_producto || p.id),
    id_producto: p.id_producto,
    code: p.codigo || p.code || '',
    codigo: p.codigo || p.code || '',
    name: p.nombre || p.descripcion || p.name || '',
    nombre: p.nombre || p.descripcion || p.name || '',
    descripcion: p.descripcion || '',
    category: p.categoria_nombre || p.category || 'General',
    categoria_nombre: p.categoria_nombre,
    id_categoria: p.id_categoria,
    id_marca: p.id_marca,
    marca_nombre: p.marca_nombre,
    cost: Number(p.cost ?? (precioUnitario * 0.7)),
    price: precioUnitario,
    precio_unitario: precioUnitario,
    precio_mayorista: Number(p.precio_mayorista ?? precioUnitario),
    precio_minorista: Number(p.precio_minorista ?? precioUnitario),
    iva: (p.iva as any) || '21%',
    stock: stockDisp,
    stock_disponible: stockDisp,
    committedStock: committedStock,
    minStock: stockMin,
    stock_minimo: stockMin,
    estado: p.estado || 'activo',
    estado_alerta: p.estado_alerta || (stockDisp <= stockMin ? 'bajo' : 'normal'),
    barcode: p.codigo || p.barcode,
    priceWholesale: Number(p.precio_mayorista ?? precioUnitario * 0.85),
    fecha_alta: p.fecha_alta,
    fecha_modificacion: p.fecha_modificacion,
    fecha_desactivacion: p.fecha_desactivacion,
    id_usuario_carga: p.id_usuario_carga,
    usuario_carga_nombre: p.usuario_carga_nombre,
    id_usuario_modificacion: p.id_usuario_modificacion,
    usuario_modificacion_nombre: p.usuario_modificacion_nombre,
    id_ubicacion: p.id_ubicacion,
    ubicacion_nombre: p.ubicacion_nombre,
  };
}

export const productosService = {
  getAll: async (params?: { search?: string; categoria_id?: number; marca_id?: number; estado?: string }): Promise<Product[]> => {
    const queryParams = { estado: 'todos', ...(params || {}) };
    const res = await apiClient.get<ApiResponse<any[]>>('/productos', queryParams);
    const data = res.data || (Array.isArray(res) ? res : []);
    return data.map(mapBackendProductToProduct);
  },

  getById: async (id: number | string): Promise<Product> => {
    const res = await apiClient.get<ApiResponse<any>>(`/productos/${id}`);
    const data = res.data || res;
    return mapBackendProductToProduct(data);
  },

  create: async (productData: {
    codigo: string;
    nombre?: string;
    descripcion: string;
    precio_unitario: number;
    precio_minorista?: number;
    id_categoria: number;
    id_marca: number;
    stock_disponible?: number;
    stock_minimo?: number;
  }): Promise<Product> => {
    const res = await apiClient.post<ApiResponse<any>>('/productos', productData);
    const data = res.data || res;
    return mapBackendProductToProduct(data);
  },

  update: async (id: number | string, productData: Partial<{
    codigo: string;
    nombre: string;
    descripcion: string;
    precio_unitario: number;
    precio_minorista: number;
    id_categoria: number;
    id_marca: number;
    stock_disponible: number;
    stock_minimo: number;
  }>): Promise<Product> => {
    const res = await apiClient.patch<ApiResponse<any>>(`/productos/${id}`, productData);
    const data = res.data || res;
    return mapBackendProductToProduct(data);
  },

  desactivar: async (id: number | string): Promise<void> => {
    await apiClient.patch(`/productos/${id}/desactivar`);
  },

  activar: async (id: number | string): Promise<void> => {
    await apiClient.patch(`/productos/${id}/activar`);
  },

  aumentoMasivo: async (data: {
    porcentaje: number;
    id_categoria?: number;
    id_marca?: number;
  }): Promise<any> => {
    return apiClient.post('/productos/aumento-masivo', data);
  },

  getHistorialPrecios: async (id: number | string): Promise<any[]> => {
    const res = await apiClient.get<ApiResponse<any[]>>(`/productos/${id}/historial`);
    return res.data || [];
  },

  getAuditoria: async (id: number | string): Promise<any[]> => {
    const res = await apiClient.get<ApiResponse<any[]>>(`/productos/${id}/auditoria`);
    return res.data || [];
  },

  getCategorias: async (): Promise<Categoria[]> => {
    const res = await apiClient.get<ApiResponse<Categoria[]>>('/categorias');
    const data = res.data || (Array.isArray(res) ? res : []);
    return data.map((item: any, idx: number) => {
      if (typeof item === 'string') {
        return { id_categoria: idx + 1, nombre: item };
      }
      return item;
    });
  },

  createCategoria: async (nombre: string): Promise<Categoria> => {
    const res = await apiClient.post<ApiResponse<Categoria>>('/categorias', { nombre });
    return res.data || (res as any);
  },

  updateCategoria: async (id: number, nombre: string): Promise<Categoria> => {
    const res = await apiClient.patch<ApiResponse<Categoria>>(`/categorias/${id}`, { nombre });
    return res.data || (res as any);
  },

  deleteCategoria: async (id: number): Promise<void> => {
    await apiClient.delete(`/categorias/${id}`);
  },

  getMarcas: async (): Promise<Marca[]> => {
    const res = await apiClient.get<ApiResponse<Marca[]>>('/marcas');
    const data = res.data || (Array.isArray(res) ? res : []);
    return data.map((item: any, idx: number) => {
      if (typeof item === 'string') {
        return { id_marca: idx + 1, nombre: item };
      }
      return item;
    });
  },

  createMarca: async (nombre: string): Promise<Marca> => {
    const res = await apiClient.post<ApiResponse<Marca>>('/marcas', { nombre });
    return res.data || (res as any);
  },

  updateMarca: async (id: number, nombre: string): Promise<Marca> => {
    const res = await apiClient.patch<ApiResponse<Marca>>(`/marcas/${id}`, { nombre });
    return res.data || (res as any);
  },

  deleteMarca: async (id: number): Promise<void> => {
    await apiClient.delete(`/marcas/${id}`);
  },
};
