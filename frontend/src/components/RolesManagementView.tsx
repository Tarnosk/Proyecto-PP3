/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from 'react';
import { RolePermissions, UserRole } from '../types';
import { Save, CheckCircle2, ShieldAlert, ShieldCheck, Info } from 'lucide-react';

interface RolesManagementViewProps {
  permissions: RolePermissions[];
  onUpdatePermissions: (updatedPermissions: RolePermissions[]) => void;
}

export function RolesManagementView({ permissions, onUpdatePermissions }: RolesManagementViewProps) {
  // Copia local para editar
  const [localPermissions, setLocalPermissions] = useState<RolePermissions[]>([...permissions]);
  const [success, setSuccess] = useState('');

  const handleToggle = (role: UserRole, module: keyof Omit<RolePermissions, 'role'>) => {
    // Si es Administrador, mantenemos todo en true por seguridad
    if (role === 'Administrador') return;

    const updated = localPermissions.map(p => {
      if (p.role === role) {
        return {
          ...p,
          [module]: !p[module]
        };
      }
      return p;
    });
    setLocalPermissions(updated);
    setSuccess('');
  };

  const handleSave = () => {
    onUpdatePermissions(localPermissions);
    setSuccess('¡Permisos por rol actualizados correctamente! Los cambios afectarán inmediatamente el menú e inicio de cada empleado.');
    setTimeout(() => {
      setSuccess('');
    }, 5000);
  };

  const moduleNames: { key: keyof Omit<RolePermissions, 'role'>; label: string; desc: string }[] = [
    { key: 'ventas', label: 'Ventas y Pedidos', desc: 'Toma de pedidos en la calle, tarifas y promociones.' },
    { key: 'inventario', label: 'Productos y Stock', desc: 'Catálogos, costos, control de inventario y alertas.' },
    { key: 'clientes', label: 'Fichas de Clientes', desc: 'Datos fiscales, zonificación y geolocalización de comercios.' },
    { key: 'logistica', label: 'Logística y Reparto', desc: 'Asignación de choferes, hojas de ruta y devoluciones.' },
    { key: 'finanzas', label: 'Finanzas y Caja', desc: 'Facturación ARCA, cuentas corrientes y caja diaria.' }
  ];

  return (
    <div className="space-y-6 animate-in fade-in duration-300">
      
      <header>
        <h1 className="text-3xl font-extrabold text-[#212529]">
          Gestión de Roles y Permisos Avanzados
        </h1>
        <p className="text-lg text-neutral-700 font-semibold mt-1">
          Habilite o bloquee módulos completos del ERP para cada perfil laboral.
        </p>
      </header>

      {/* BANNER INFORMATIVO */}
      <section className="bg-[#DEE2E6] border-2 border-[#DEE2E6] rounded-2xl p-5 flex items-start gap-4">
        <Info className="w-8 h-8 text-[#0D6EFD] shrink-0 mt-0.5" />
        <div className="space-y-1">
          <p className="text-lg font-bold text-[#212529]">¿Cómo funciona este panel?</p>
          <p className="text-base text-neutral-800 font-semibold leading-relaxed">
            Haga clic sobre cualquier casilla de verificación (checkbox) para otorgar o quitar accesos. El rol de 
            <strong className="text-[#212529]"> Administrador</strong> siempre mantendrá acceso completo para garantizar la seguridad de su ERP.
          </p>
        </div>
      </section>

      {success && (
        <div 
          className="p-4 bg-green-100 border-l-4 border-[#198754] text-[#198754] rounded-r-md flex items-start gap-3"
          role="alert"
        >
          <CheckCircle2 className="w-6 h-6 shrink-0 mt-0.5" />
          <div>
            <p className="font-bold text-lg">Permisos Actualizados:</p>
            <p className="text-base font-bold">{success}</p>
          </div>
        </div>
      )}

      {/* GRILLA DE ROLES Y CHECKBOXES */}
      <div className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-6 md:p-8 space-y-6">
        
        {/* RECOMENTADO: VISTA RESPONSIVA ADAPTADA */}
        <div className="space-y-6">
          {localPermissions.map((item) => {
            const isAdmin = item.role === 'Administrador';
            return (
              <div 
                key={item.role} 
                className="bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-2xl p-5 space-y-4"
              >
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 pb-3 border-b-2 border-[#DEE2E6]">
                  <div className="flex items-center gap-2.5">
                    <ShieldCheck className="w-7 h-7 text-[#0D6EFD]" />
                    <h2 className="text-xl font-extrabold text-[#212529]">
                      Permisos del Rol: {item.role}
                    </h2>
                  </div>
                  {isAdmin && (
                    <span className="bg-green-100 text-[#198754] font-extrabold text-sm px-3 py-1 rounded-lg border border-[#198754]">
                      Control Total Centralizado
                    </span>
                  )}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                  {moduleNames.map((mod) => {
                    const isGranted = item[mod.key];
                    return (
                      <button
                        key={mod.key}
                        type="button"
                        onClick={() => handleToggle(item.role, mod.key)}
                        disabled={isAdmin}
                        className={`w-full text-left p-4 rounded-xl border-2 transition-all flex items-start gap-3 cursor-pointer ${
                          isAdmin 
                            ? 'bg-neutral-100 border-neutral-300 opacity-80 cursor-default'
                            : isGranted
                              ? 'bg-green-50 border-[#198754] hover:bg-green-100/70'
                              : 'bg-red-50 border-[#DC3545]/40 hover:bg-red-100/50'
                        }`}
                      >
                        <input
                          type="checkbox"
                          checked={isGranted}
                          disabled={isAdmin}
                          onChange={() => {}} // se maneja en el click del container
                          className="w-6 h-6 mt-1 text-[#198754] border-2 border-[#DEE2E6] rounded-md shrink-0 cursor-pointer"
                        />
                        <div className="space-y-1">
                          <span className={`block font-black text-lg ${
                            isGranted ? 'text-neutral-900' : 'text-neutral-700'
                          }`}>
                            {mod.label}
                          </span>
                          <span className="block text-xs font-semibold text-neutral-600 leading-tight">
                            {mod.desc}
                          </span>
                        </div>
                      </button>
                    );
                  })}
                </div>
              </div>
            );
          })}
        </div>

        {/* BOTÓN DE GUARDAR */}
        <div className="pt-4 flex justify-end">
          <button
            onClick={handleSave}
            className="w-full md:w-auto bg-[#198754] hover:bg-[#146c43] text-white font-black text-xl rounded-xl py-4 px-10 flex items-center justify-center gap-3 cursor-pointer"
          >
            <Save className="w-6 h-6" />
            <span>Guardar Permisos Centralizados</span>
          </button>
        </div>

      </div>
    </div>
  );
}
