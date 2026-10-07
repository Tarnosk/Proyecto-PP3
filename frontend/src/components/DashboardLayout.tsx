/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from 'react';
import { User, RolePermissions } from '../types';
import { 
  LayoutDashboard, 
  User as UserIcon, 
  Users, 
  ShieldCheck, 
  ShoppingBag,
  ShoppingCart,
  Package,
  LogOut,
  Menu,
  X,
  Truck,
  Wallet,
  ChevronLeft,
  ChevronRight,
  RotateCw
} from 'lucide-react';

interface DashboardLayoutProps {
  currentUser: User;
  activeView: string;
  onNavigate: (view: string) => void;
  onLogout: () => void;
  permissions: RolePermissions[];
  apiConnected?: boolean | null;
  onRefreshApi?: () => void;
  children: React.ReactNode;
}

export function DashboardLayout({ 
  currentUser, 
  activeView, 
  onNavigate, 
  onLogout, 
  permissions,
  apiConnected,
  onRefreshApi,
  children 
}: DashboardLayoutProps) {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [isCollapsed, setIsCollapsed] = useState(false);

  const navItems = [
    { id: 'DASHBOARD', label: 'Inicio / Panel', icon: LayoutDashboard },
    { id: 'SALES', label: 'Ventas y Pedidos', icon: ShoppingBag, permissionKey: 'ventas' },
    { id: 'INVENTORY', label: 'Inventario y Stock', icon: Package, permissionKey: 'inventario' },
    { id: 'PURCHASES', label: 'Compras y Órdenes', icon: ShoppingCart, permissionKey: 'inventario' },
    { id: 'CUSTOMERS', label: 'Clientes y Proveedores', icon: Users, permissionKey: 'clientes' },
    { id: 'LOGISTICS', label: 'Logística y Transporte', icon: Truck, permissionKey: 'logistica' },
    { id: 'FINANCES', label: 'Finanzas y Tesorería', icon: Wallet, permissionKey: 'finanzas' },
    { id: 'USERS_LIST', label: 'Lista de Usuarios', icon: Users, adminOnly: true },
    { id: 'ROLES_MANAGEMENT', label: 'Roles y Accesos', icon: ShieldCheck, adminOnly: true },
    { id: 'PROFILE', label: 'Mi Perfil', icon: UserIcon },
  ];

  // Filtramos opciones según rol del usuario y matriz de permisos activos
  const visibleNavItems = navItems.filter(item => {
    if (item.adminOnly && currentUser.role !== 'Administrador') {
      return false;
    }
    if (item.permissionKey) {
      const rolePerm = permissions.find(p => p.role === currentUser.role);
      if (rolePerm && !((rolePerm as any)[item.permissionKey])) {
        return false;
      }
    }
    return true;
  });

  if (activeView === 'DASHBOARD') {
    return (
      <div className="min-h-screen bg-[#EBF4FC] flex flex-col overflow-y-auto">
        {children}
      </div>
    );
  }

  return (
    <div className="min-h-screen flex flex-col md:flex-row bg-[#EBF4FC]">
      
      {/* HEADER PARA MÓVIL (Celulares / Tablets) */}
      <header className="md:hidden flex items-center justify-between bg-white/80 backdrop-blur-md border-b border-slate-200/80 px-5 py-3.5 shrink-0 shadow-sm">
        <div className="flex items-center gap-2.5">
          <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-black shadow-md shadow-blue-500/10 shrink-0">
            <Truck className="w-5 h-5 text-white" />
          </div>
          <div className="flex flex-col text-left">
            <span className="font-black text-base text-slate-800 tracking-tight">Pigüé ERP</span>
            <span className="text-[9px] font-extrabold text-blue-600 uppercase tracking-wider">{currentUser.role}</span>
          </div>
        </div>
        
        <div className="flex items-center gap-2.5">
          <button
            onClick={() => window.location.reload()}
            className="p-2 text-slate-600 hover:text-blue-600 hover:bg-slate-100/80 rounded-xl font-bold text-xs flex items-center gap-1 transition-all cursor-pointer"
            title="Refrescar página"
          >
            <RotateCw className="w-5 h-5 text-blue-600" />
            <span className="sr-only">Refrescar página</span>
          </button>

          <button
            onClick={() => {
              onNavigate('PROFILE');
              setMobileMenuOpen(false);
            }}
            className="p-2 text-slate-600 hover:text-blue-600 hover:bg-slate-100/80 rounded-xl font-bold text-xs flex items-center gap-1 transition-all"
            title="Mi Perfil"
          >
            <UserIcon className="w-5 h-5" />
            <span className="sr-only">Mi Perfil</span>
          </button>
          
          <button
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className="p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl focus:outline-none flex items-center justify-center cursor-pointer transition-all border border-slate-200/50"
            aria-label={mobileMenuOpen ? 'Cerrar Menú' : 'Abrir Menú'}
          >
            {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
          </button>
        </div>
      </header>

      {/* MENÚ DESPLEGABLE MÓVIL */}
      {mobileMenuOpen && (
        <div className="md:hidden bg-white/95 backdrop-blur-md border-b border-slate-200 px-5 py-5 space-y-4 z-50 animate-in fade-in slide-in-from-top-4 duration-200 shadow-md">
          <div className="p-4 bg-slate-50 border border-slate-100 rounded-2xl mb-2 flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-blue-600 text-white font-black flex items-center justify-center shadow-md shrink-0">
              {currentUser.name.slice(0, 2).toUpperCase()}
            </div>
            <div className="text-left">
              <p className="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider">Sesión iniciada como:</p>
              <p className="text-base font-black text-slate-800 leading-tight">{currentUser.name}</p>
              <p className="text-xs font-bold text-blue-600 mt-0.5">{currentUser.role}</p>
            </div>
          </div>
          
          <nav className="flex flex-col gap-2">
            {visibleNavItems.map((item) => {
              const IconComponent = item.icon;
              const isActive = activeView === item.id || 
                (item.id === 'USERS_LIST' && ['USERS_CREATE', 'USERS_EDIT'].includes(activeView));
              return (
                <button
                  key={item.id}
                  onClick={() => {
                    onNavigate(item.id);
                    setMobileMenuOpen(false);
                  }}
                  className={`w-full flex items-center gap-3.5 px-4 py-3 rounded-xl font-black text-sm text-left border transition-all cursor-pointer ${
                    isActive 
                      ? 'bg-blue-600 text-white border-blue-500 shadow-md' 
                      : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
                  }`}
                >
                  <IconComponent className="w-5 h-5 shrink-0" />
                  <span>{item.label}</span>
                </button>
              );
            })}
            
            <button
              onClick={() => {
                setMobileMenuOpen(false);
                onLogout();
              }}
              className="w-full flex items-center gap-3.5 px-4 py-3 rounded-xl font-black text-sm text-left text-white bg-red-600 border border-red-500 hover:bg-red-700 transition-all cursor-pointer shadow-sm"
            >
              <LogOut className="w-5 h-5 shrink-0" />
              <span>Cerrar Sesión</span>
            </button>
          </nav>
        </div>
      )}

      {/* BARRA LATERAL FIJA EN ESCRITORIO (PC) */}
      <aside 
        className={`hidden md:flex flex-col ${
          isCollapsed ? 'w-24 px-3' : 'w-72 p-6'
        } clay-sidebar py-6 shrink-0 justify-between text-white relative transition-all duration-300 ease-in-out`}
      >
        {/* BOTÓN COLAPSAR */}
        <button
          onClick={() => setIsCollapsed(!isCollapsed)}
          className="absolute -right-3 top-10 bg-[#0D6EFD] hover:bg-blue-600 text-white rounded-full p-1.5 border-2 border-white shadow-md z-40 hidden md:flex items-center justify-center cursor-pointer transition-transform"
          title={isCollapsed ? 'Expandir barra lateral' : 'Colapsar barra lateral'}
        >
          {isCollapsed ? <ChevronRight className="w-5 h-5" /> : <ChevronLeft className="w-5 h-5" />}
        </button>

        <div className="space-y-8 overflow-hidden">
          {/* Logo / Marca */}
          <div className={`transition-all duration-300 ${isCollapsed ? 'text-center' : ''}`}>
            {isCollapsed ? (
              <span className="inline-block bg-white/10 w-12 h-12 rounded-2xl border border-white/20 text-center leading-12 font-black text-2xl text-white tracking-wider shadow-inner">
                P
              </span>
            ) : (
              <>
                <h2 className="text-2xl font-black text-white tracking-tight drop-shadow-sm">
                  Pigüé ERP
                </h2>
                <p className="text-[10px] text-blue-200 font-extrabold uppercase tracking-widest mt-1">
                  Distribuidora Comercial
                </p>
              </>
            )}
          </div>

          {/* Tarjeta de Usuario en Sidebar */}
          <div className={`bg-white/10 rounded-2xl p-4 border border-white/10 shadow-inner overflow-hidden transition-all duration-300 ${isCollapsed ? 'px-2 py-3 text-center' : ''}`}>
            {isCollapsed ? (
              <div className="flex flex-col items-center gap-1">
                <span className="w-10 h-10 rounded-full bg-white text-[#1D4ED8] font-black flex items-center justify-center text-sm shadow-md" title={currentUser.name}>
                  {currentUser.name.slice(0, 2).toUpperCase()}
                </span>
                <span className="text-[10px] font-black text-blue-200 truncate max-w-full">
                  {currentUser.role.slice(0, 5)}...
                </span>
              </div>
            ) : (
              <>
                <span className="text-[10px] font-bold text-blue-200 block uppercase tracking-wider">Usuario Activo</span>
                <span className="text-base font-extrabold text-white block truncate mt-1" title={currentUser.name}>
                  {currentUser.name}
                </span>
                <span className="inline-block mt-2 px-2.5 py-0.5 bg-white/20 border border-white/10 rounded-lg text-xs font-bold text-blue-100">
                  {currentUser.role}
                </span>
              </>
            )}
          </div>

          {/* Indicador de Conexión a la API Laravel */}
          {!isCollapsed && (
            <div className="flex items-center justify-between px-3.5 py-2.5 bg-white/10 rounded-xl border border-white/15 text-xs shadow-inner">
              <div className="flex items-center gap-2">
                <span className={`w-2.5 h-2.5 rounded-full shrink-0 ${
                  apiConnected === true 
                    ? 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)] animate-pulse' 
                    : apiConnected === false 
                    ? 'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.8)]' 
                    : 'bg-blue-300 animate-spin'
                }`} />
                <span className="text-[11px] font-bold text-white tracking-wide">
                  {apiConnected === true 
                    ? 'API Laravel: OK' 
                    : apiConnected === false 
                    ? 'API: Modo Local' 
                    : 'Conectando API...'}
                </span>
              </div>
              {onRefreshApi && (
                <button
                  onClick={onRefreshApi}
                  className="p-1 hover:bg-white/20 rounded-lg text-white/80 hover:text-white transition-all cursor-pointer"
                  title="Sincronizar datos con Laravel"
                >
                  <RotateCw className="w-3.5 h-3.5" />
                </button>
              )}
            </div>
          )}

          <nav className="flex flex-col gap-2">
            {visibleNavItems.map((item) => {
              const IconComponent = item.icon;
              const isActive = activeView === item.id || 
                (item.id === 'USERS_LIST' && ['USERS_CREATE', 'USERS_EDIT'].includes(activeView));
              return (
                <button
                  key={item.id}
                  onClick={() => onNavigate(item.id)}
                  className={`w-full flex items-center ${
                    isCollapsed ? 'justify-center p-3' : 'gap-3.5 px-4 py-3'
                  } rounded-xl font-bold text-base text-left border transition-all cursor-pointer ${
                    isActive 
                      ? 'bg-white/20 text-white border-white/25 shadow-[inset_2px_2px_4px_rgba(255,255,255,0.25),inset_-2px_-2px_4px_rgba(0,0,0,0.1),0_4px_12px_rgba(0,0,0,0.08)] font-black' 
                      : 'bg-transparent text-blue-100 border-transparent hover:bg-white/10 hover:text-white'
                  }`}
                  title={isCollapsed ? item.label : undefined}
                >
                  <IconComponent className="w-6 h-6 shrink-0" />
                  {!isCollapsed && <span className="truncate">{item.label}</span>}
                </button>
              );
            })}
          </nav>
        </div>

        {/* Botones de Pie de Barra Lateral */}
        <div className={`pt-4 space-y-2 border-t border-white/10 ${isCollapsed ? 'text-center' : ''}`}>
          <button
            onClick={() => window.location.reload()}
            className={`w-full flex items-center ${
              isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
            } rounded-xl font-bold text-sm text-left text-blue-100 hover:text-white bg-white/10 hover:bg-white/20 border border-white/15 transition-all cursor-pointer shadow-xs`}
            title="Refrescar página"
          >
            <RotateCw className="w-5 h-5 shrink-0" />
            {!isCollapsed && <span>Refrescar Página</span>}
          </button>

          <button
            onClick={onLogout}
            className={`w-full flex items-center ${
              isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
            } rounded-xl font-bold text-sm text-left text-white bg-red-500/80 hover:bg-red-600 border border-red-400/30 transition-colors cursor-pointer`}
            title={isCollapsed ? 'Cerrar Sesión' : undefined}
          >
            <LogOut className="w-5 h-5 shrink-0" />
            {!isCollapsed && <span>Cerrar Sesión</span>}
          </button>
        </div>
      </aside>

      {/* ÁREA DE CONTENIDO PRINCIPAL */}
      <main className="flex-1 overflow-y-auto p-4 md:p-8">
        <div className="max-w-6xl mx-auto space-y-6">
          {/* BARRA SUPERIOR DE ENTORNO Y ACCIÓN RÁPIDA (ESCRITORIO) */}
          <div className="hidden md:flex items-center justify-between pb-3 border-b border-slate-200/80">
            <div className="flex items-center gap-2 text-xs font-bold text-slate-500">
              <span 
                className="cursor-pointer hover:text-blue-600 transition-colors" 
                onClick={() => onNavigate('DASHBOARD')}
              >
                Panel de Control
              </span>
              <span>/</span>
              <span className="text-slate-800 font-extrabold">
                {visibleNavItems.find(i => i.id === activeView)?.label || activeView}
              </span>
            </div>

            <button
              onClick={() => window.location.reload()}
              className="flex items-center gap-2 px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 hover:text-blue-600 rounded-xl border border-slate-200 shadow-xs text-xs font-bold transition-all cursor-pointer"
              title="Refrescar la página completa"
            >
              <RotateCw className="w-3.5 h-3.5 text-blue-600" />
              <span>Refrescar Página</span>
            </button>
          </div>

          {children}
        </div>
      </main>

      {/* BARRA DE NAVEGACIÓN INFERIOR PARA DISPOSITIVOS MÓVILES (Adicionalmente para choferes/repartidores en calle) */}
      <nav className="md:hidden fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-md border-t border-slate-200/80 flex justify-around py-1.5 z-40 px-3 shrink-0 h-16 shadow-lg">
        {visibleNavItems.slice(0, 5).map((item) => {
          const IconComponent = item.icon;
          const isActive = activeView === item.id || 
            (item.id === 'USERS_LIST' && ['USERS_CREATE', 'USERS_EDIT'].includes(activeView));
          return (
            <button
              key={item.id}
              onClick={() => onNavigate(item.id)}
              className={`flex flex-col items-center justify-center flex-1 rounded-xl py-1 px-1.5 cursor-pointer transition-all ${
                isActive 
                  ? 'text-blue-600 font-black bg-blue-50/50' 
                  : 'text-slate-500 hover:text-slate-800 font-semibold'
              }`}
            >
              <IconComponent className="w-5.5 h-5.5 shrink-0" />
              <span className="text-[9px] font-bold tracking-tight truncate max-w-[65px] mt-0.5">{item.label.split(' ')[0]}</span>
            </button>
          );
        })}
      </nav>

      {/* Relleno inferior para evitar superposición con el Bottom Nav en móvil */}
      <div className="h-16 md:hidden block shrink-0" />
    </div>
  );
}
