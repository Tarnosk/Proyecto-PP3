/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { apiClient, ApiResponse } from './apiClient';

export interface StockItem {
  id_producto: number;
  codigo: string;
  descripcion: string;
  id_categoria?: number;
  categoria_nombre?: string;
  id_marca?: number;
  marca_nombre?: string;
  stock_disponible: number;
  stock_minimo: number;
  estado_alerta: 'normal' | 'bajo' | 'critico' | string;
  precio_unitario: number;
  precio_mayorista?: number;
  precio_minorista?: number;
}

export interface StockMovement {
  id_movimiento: number;
  id_producto: number;
  tipo: 'ingreso' | 'venta' | 'devolucion' | 'ajuste' | string;
  cantidad: number;
  cantidad_base?: number;
  fecha: string;
  motivo?: string;
  id_usuario?: number;
  unidad?: string;
}

export interface Lote {
  id_lote: number;
  id_producto: number;
  nro_lote: string;
  cantidad_inicial: number;
  cantidad_actual: number;
  fecha_vencimiento: string;
  producto_codigo?: string;
  producto_nombre?: string;
  dias_para_vencer?: number;
}

export const stockService = {
  getConsultaGeneral: async (params?: { search?: string; estado_alerta?: string }): Promise<StockItem[]> => {
    const res = await apiClient.get<ApiResponse<StockItem[]>>('/stock', params);
    return res.data || (Array.isArray(res) ? res : []);
  },

  getDisponibilidad: async (idProducto: number | string): Promise<any> => {
    const res = await apiClient.get<ApiResponse<any>>(`/stock/${idProducto}/disponibilidad`);
    return res.data || res;
  },

  getHistorialMovimientos: async (idProducto: number | string): Promise<StockMovement[]> => {
    const res = await apiClient.get<ApiResponse<{ movimientos: StockMovement[] }>>(`/stock/${idProducto}/historial-movimientos`);
    return res.data?.movimientos || [];
  },

  registrarIngreso: async (data: {
    id_proveedor: number;
    fecha?: string;
    items: Array<{
      id_producto: number;
      cantidad: number;
      motivo?: string;
      nro_lote?: string;
      fecha_vencimiento?: string;
      id_unidad?: number;
    }>;
  }): Promise<any> => {
    return apiClient.post('/stock/ingresos', data);
  },

  registrarAjuste: async (data: {
    id_producto: number;
    cantidad: number;
    tipo: 'aumentar' | 'disminuir';
    motivo: string;
    fecha?: string;
    id_unidad?: number;
  }): Promise<any> => {
    return apiClient.post('/stock/ajustes', data);
  },

  registrarDevolucion: async (data: {
    id_producto: number;
    cantidad: number;
    motivo: string;
    tipo_devolucion?: 'cliente' | 'proveedor';
  }): Promise<any> => {
    return apiClient.post('/stock/devoluciones', data);
  },

  getAlertas: async (): Promise<any[]> => {
    const res = await apiClient.get<ApiResponse<any[]>>('/stock/alertas');
    return res.data || [];
  },

  getLotes: async (params?: any): Promise<Lote[]> => {
    const res = await apiClient.get<ApiResponse<Lote[]>>('/stock/lotes', params);
    return res.data || (Array.isArray(res) ? res : []);
  },

  registrarLote: async (data: {
    id_producto: number;
    nro_lote: string;
    cantidad: number;
    fecha_vencimiento: string;
  }): Promise<any> => {
    return apiClient.post('/stock/lotes', data);
  },

  getLotesPorVencer: async (): Promise<Lote[]> => {
    const res = await apiClient.get<ApiResponse<Lote[]>>('/stock/lotes-por-vencer');
    return res.data || (Array.isArray(res) ? res : []);
  },

  getVencimientosProximos: async (dias?: number): Promise<any[]> => {
    const res = await apiClient.get<ApiResponse<any[]>>('/vencimientos/proximos', { dias });
    return res.data || [];
  },

  getVencimientosPorCriticidad: async (): Promise<any> => {
    const res = await apiClient.get<ApiResponse<any>>('/vencimientos/por-criticidad');
    return res.data || {};
  },

  getTodosLosMovimientos: async (params?: { id_producto?: number; tipo?: string; limit?: number }): Promise<any[]> => {
    const res = await apiClient.get<ApiResponse<any[]>>('/stock/movimientos', params);
    return res.data || (Array.isArray(res) ? res : []);
  },

  getUbicaciones: async (): Promise<any[]> => {
    const res = await apiClient.get<ApiResponse<any[]>>('/ubicaciones');
    return res.data || (Array.isArray(res) ? res : []);
  },

  crearUbicacion: async (data: { descripcion: string }): Promise<any> => {
    return apiClient.post('/ubicaciones', data);
  },

  asignarUbicacion: async (idProducto: number | string, idUbicacion: number): Promise<any> => {
    return apiClient.post(`/productos/${idProducto}/ubicacion`, { id_ubicacion: idUbicacion });
  },

  desasignarUbicacion: async (idProducto: number | string): Promise<any> => {
    return apiClient.delete(`/productos/${idProducto}/ubicacion`);
  },

  getUnidades: async (idProducto: number | string): Promise<any[]> => {
    const res = await apiClient.get<ApiResponse<{ unidades: any[] }>>(`/productos/${idProducto}/unidades`);
    return res.data?.unidades || (Array.isArray(res.data) ? res.data : []);
  },

  crearUnidad: async (idProducto: number | string, data: { nombre_unidad: string; equivalencia_base: number; descripcion?: string }): Promise<any> => {
    return apiClient.post(`/productos/${idProducto}/unidades`, data);
  },

  eliminarUnidad: async (idProducto: number | string, idUnidad: number | string): Promise<any> => {
    return apiClient.delete(`/productos/${idProducto}/unidades/${idUnidad}`);
  },
};
