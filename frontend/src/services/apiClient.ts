/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

const API_BASE_URL = (import.meta as any).env?.VITE_API_URL || '/api';

export interface ApiResponse<T = any> {
  status: 'success' | 'error';
  message?: string;
  data?: T;
  [key: string]: any;
}

class ApiError extends Error {
  status?: number;
  data?: any;

  constructor(message: string, status?: number, data?: any) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.data = data;
  }
}

async function request<T = any>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
  const url = `${API_BASE_URL}${cleanEndpoint}`;

  const authHeaders: Record<string, string> = {};
  try {
    const savedUser = typeof localStorage !== 'undefined' ? localStorage.getItem('erp_pigüe_current_user') : null;
    if (savedUser) {
      const parsedUser = JSON.parse(savedUser);
      if (parsedUser) {
        if (parsedUser.id) authHeaders['X-User-Id'] = String(parsedUser.id);
        if (parsedUser.name) authHeaders['X-User-Name'] = encodeURIComponent(parsedUser.name);
        if (parsedUser.username) authHeaders['X-User-Username'] = parsedUser.username;
        if (parsedUser.role) authHeaders['X-User-Role'] = parsedUser.role;
      }
    }
  } catch {}

  const headers: HeadersInit = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    ...authHeaders,
    ...(options.headers || {}),
  };

  try {
    const response = await fetch(url, {
      ...options,
      headers,
    });

    const isJson = response.headers?.get ? response.headers.get('content-type')?.includes('application/json') : true;
    const data = isJson ? await response.json() : await response.text();

    if (!response.ok) {
      const errorMessage =
        (data && typeof data === 'object' && (data.message || data.error)) ||
        `Error HTTP ${response.status}: ${response.statusText}`;
      throw new ApiError(errorMessage, response.status, data);
    }

    return data as T;
  } catch (error: any) {
    if (error instanceof ApiError) {
      throw error;
    }
    throw new ApiError(error.message || 'Error de conexión con el servidor', 0);
  }
}

export const apiClient = {
  get: <T = any>(endpoint: string, params?: Record<string, any>, options?: RequestInit): Promise<T> => {
    let url = endpoint;
    if (params) {
      const searchParams = new URLSearchParams();
      Object.entries(params).forEach(([key, val]) => {
        if (val !== undefined && val !== null && val !== '') {
          searchParams.append(key, String(val));
        }
      });
      const qs = searchParams.toString();
      if (qs) {
        url += (url.includes('?') ? '&' : '?') + qs;
      }
    }
    return request<T>(url, { ...options, method: 'GET' });
  },

  post: <T = any>(endpoint: string, body?: any, options?: RequestInit): Promise<T> => {
    return request<T>(endpoint, {
      ...options,
      method: 'POST',
      body: body ? JSON.stringify(body) : undefined,
    });
  },

  patch: <T = any>(endpoint: string, body?: any, options?: RequestInit): Promise<T> => {
    return request<T>(endpoint, {
      ...options,
      method: 'PATCH',
      body: body ? JSON.stringify(body) : undefined,
    });
  },

  put: <T = any>(endpoint: string, body?: any, options?: RequestInit): Promise<T> => {
    return request<T>(endpoint, {
      ...options,
      method: 'PUT',
      body: body ? JSON.stringify(body) : undefined,
    });
  },

  delete: <T = any>(endpoint: string, options?: RequestInit): Promise<T> => {
    return request<T>(endpoint, { ...options, method: 'DELETE' });
  },
};
