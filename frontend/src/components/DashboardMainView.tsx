/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React from 'react';
import { User, RolePermissions, Product, Order } from '../types';
import { 
  ShoppingBag, 
  ShoppingCart,
  Package, 
  Truck, 
  Wallet, 
  Users, 
  UserCog,
  ChevronRight,
  Bell,
  LogOut,
  RotateCw
} from 'lucide-react';

interface DashboardMainViewProps {
  currentUser: User;
  permissions: RolePermissions[];
  onNavigate: (view: string) => void;
  products?: Product[];
  orders?: Order[];
  onLogout?: () => void;
}

export function DashboardMainView({ 
  currentUser, 
  permissions, 
  onNavigate, 
  onLogout
}: DashboardMainViewProps) {
  // Encontrar permisos del rol actual
  const rolePermissions = permissions.find(p => p.role === currentUser.role) || {
    role: currentUser.role,
    ventas: false,
    inventario: false,
    clientes: false,
    logistica: false,
    finanzas: false,
  };

  const modules = [
    {
      id: 'SALES',
      title: 'Venta',
      description: 'Gestioná ventas, clientes y facturación.',
      icon: ShoppingBag,
      color: 'emerald',
      enabled: rolePermissions.ventas,
      iconClass: 'bg-emerald-50 text-emerald-600 border border-emerald-100',
      shadowClass: 'hover:shadow-emerald-500/5',
    },
    {
      id: 'INVENTORY',
      title: 'Stock',
      description: 'Gestión de productos, inventario y movimientos.',
      icon: Package,
      color: 'blue',
      enabled: rolePermissions.inventario,
      iconClass: 'bg-blue-50 text-blue-600 border border-blue-100',
      shadowClass: 'hover:shadow-blue-500/5',
    },
    {
      id: 'PURCHASES',
      title: 'Compras',
      description: 'Órdenes de compra, recepción y proveedores.',
      icon: ShoppingCart,
      color: 'indigo',
      enabled: rolePermissions.inventario,
      iconClass: 'bg-indigo-50 text-indigo-600 border border-indigo-100',
      shadowClass: 'hover:shadow-indigo-500/5',
    },
    {
      id: 'CUSTOMERS',
      title: 'Clientes',
      description: 'Administración de clientes y proveedores.',
      icon: Users,
      color: 'sky',
      enabled: rolePermissions.clientes,
      iconClass: 'bg-sky-50 text-sky-600 border border-sky-100',
      shadowClass: 'hover:shadow-sky-500/5',
    },
    {
      id: 'LOGISTICS',
      title: 'Entregas',
      description: 'Seguimiento de entregas y estado de pedidos.',
      icon: Truck,
      color: 'violet',
      enabled: rolePermissions.logistica,
      iconClass: 'bg-violet-50 text-violet-600 border border-violet-100',
      shadowClass: 'hover:shadow-violet-500/5',
    },
    {
      id: 'FINANCES',
      title: 'Finanzas',
      description: 'Control de cobros, pagos e ingresos.',
      icon: Wallet,
      color: 'amber',
      enabled: rolePermissions.finanzas,
      iconClass: 'bg-amber-50 text-amber-600 border border-amber-100',
      shadowClass: 'hover:shadow-amber-500/5',
    },
    {
      id: 'PROFILE',
      title: `Perfil del ${currentUser.role === 'Administrador' ? 'Admin' : 'Usuario'}`,
      description: 'Mi perfil y ajustes del sistema.',
      icon: UserCog,
      color: 'slate',
      enabled: true,
      iconClass: 'bg-slate-50 text-slate-600 border border-slate-200/60',
      shadowClass: 'hover:shadow-slate-500/5',
    }
  ];

  return (
    <div className="min-h-screen max-w-7xl mx-auto w-full px-4 py-6 md:py-8 flex flex-col justify-between gap-6 animate-in fade-in duration-300">
      
      {/* CABECERA PRINCIPAL (Claymorphism Header similar a la imagen) */}
      <header className="bg-white/75 backdrop-blur-md border border-white/80 rounded-2xl px-6 py-4 flex items-center justify-between shadow-[inset_3px_3px_6px_rgba(255,255,255,0.9),0_10px_30px_rgba(13,110,253,0.04)]">
        <div className="flex items-center gap-3.5">
          <div className="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-black shadow-md shadow-blue-500/15 shrink-0">
            <Package className="w-6 h-6 text-white" />
          </div>
          <div className="text-left">
            <h1 className="text-lg md:text-xl font-black text-slate-800 tracking-tight leading-none">DISTRIBUIDORA PIGÜÉ</h1>
            <p className="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mt-1">Sistema de Gestión</p>
          </div>
        </div>

        <div className="flex items-center gap-3 md:gap-4">
          {/* Botón Refrescar Página */}
          <button
            onClick={() => window.location.reload()}
            className="flex items-center gap-2 px-3.5 py-2 bg-white/95 hover:bg-slate-50 text-slate-700 hover:text-blue-600 rounded-full border border-slate-200/60 shadow-sm text-xs font-bold cursor-pointer transition-all"
            title="Refrescar la página"
          >
            <RotateCw className="w-4 h-4 text-blue-600" />
            <span className="hidden sm:inline">Refrescar</span>
          </button>

          {/* Notificaciones */}
          <div className="relative p-2.5 bg-white/95 rounded-full border border-slate-200/60 shadow-sm hover:bg-slate-50 transition-colors cursor-pointer group">
            <Bell className="w-5 h-5 text-slate-600 group-hover:scale-110 transition-transform" />
            <span className="absolute top-1 right-1 w-5 h-5 bg-red-500 border-2 border-white rounded-full text-[10px] font-black text-white flex items-center justify-center">2</span>
          </div>
          
          {/* Iniciales / Rol */}
          <div className="flex items-center gap-2.5 bg-white/95 border border-slate-200/60 p-1.5 pr-4 rounded-full shadow-sm">
            <div className="w-9 h-9 rounded-full bg-blue-600 text-white font-black flex items-center justify-center text-sm shadow-md shadow-blue-500/10 shrink-0">
              {currentUser.name.slice(0, 2).toUpperCase()}
            </div>
            <div className="hidden sm:block text-left">
              <div className="text-xs font-black text-slate-800 leading-none">{currentUser.name}</div>
              <div className="text-[9px] font-extrabold text-blue-500 uppercase tracking-wider mt-1">{currentUser.role}</div>
            </div>
          </div>

          {/* Botón Cerrar Sesión */}
          {onLogout && (
            <button
              onClick={onLogout}
              className="p-2.5 bg-red-50/80 hover:bg-red-100 text-red-600 hover:text-red-700 rounded-full border border-red-200/30 shadow-sm transition-all cursor-pointer flex items-center justify-center"
              title="Cerrar Sesión"
            >
              <LogOut className="w-5 h-5" />
            </button>
          )}
        </div>
      </header>

      {/* GRIDS DE MÓDULOS DE ACCESO - 3 columnas en pantallas grandes, 2 en tablets, ocupando el espacio disponible de forma fluida */}
      <main className="flex-1 flex items-center justify-center py-4">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8 w-full max-w-6xl">
          {modules.map((m) => {
            const Icon = m.icon;
            return (
              <div
                key={m.id}
                onClick={() => m.enabled && onNavigate(m.id)}
                className={`clay-card clay-card-hover p-6 md:p-8 flex flex-col justify-between items-center text-center cursor-pointer relative overflow-hidden group select-none min-h-[220px] md:min-h-[250px] ${m.shadowClass} ${
                  !m.enabled ? 'opacity-40 filter grayscale pointer-events-none' : ''
                }`}
              >
                <div className="absolute -right-10 -bottom-10 w-32 h-32 rounded-full bg-slate-100/10 group-hover:scale-150 transition-transform duration-500" />
                
                <div className="flex flex-col items-center">
                  {/* Icono con clay shadows */}
                  <div className={`w-14 h-14 md:w-16 md:h-16 rounded-2xl flex items-center justify-center shadow-[inset_2px_2px_4px_rgba(255,255,255,0.8)] transition-transform duration-300 group-hover:scale-110 ${m.iconClass}`}>
                    <Icon className="w-7 h-7 md:w-8 md:h-8" />
                  </div>

                  <h3 className="text-lg md:text-xl font-black text-slate-800 mt-4 md:mt-5 leading-tight tracking-tight">
                    {m.title}
                  </h3>

                  <p className="text-xs md:text-sm text-slate-500 font-bold mt-2 leading-relaxed px-1 max-w-[260px]">
                    {m.description}
                  </p>
                </div>

                {/* Botón de Flecha circular */}
                <div className="mt-4 md:mt-6 w-10 h-10 md:w-12 md:h-12 rounded-full border border-slate-200/60 bg-white/95 flex items-center justify-center shadow-[2px_2px_5px_rgba(0,0,0,0.05),-2px_-2px_5px_rgba(255,255,255,0.8)] text-slate-500 group-hover:bg-slate-50 group-hover:border-slate-300 group-hover:text-slate-800 transition-all shrink-0">
                  <ChevronRight className="w-5 h-5 md:w-6 md:h-6 group-hover:translate-x-0.5 transition-transform" />
                </div>
              </div>
            );
          })}
        </div>
      </main>

      {/* FOOTER DISCRETO Y MINIMALISTA */}
      <footer className="text-center py-2 shrink-0">
        <p className="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">
          Pigüé ERP © 2026 • Distribuidora Comercial
        </p>
      </footer>

    </div>
  );
}
