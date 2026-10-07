/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from 'react';
import { User } from '../types';
import { Save, CheckCircle2, AlertTriangle, KeyRound } from 'lucide-react';

interface UserProfileViewProps {
  currentUser: User;
  onUpdateProfile: (updatedUser: User) => void;
}

export function UserProfileView({ currentUser, onUpdateProfile }: UserProfileViewProps) {
  const [name, setName] = useState(currentUser.name);
  const [username, setUsername] = useState(currentUser.username);
  const [email, setEmail] = useState(currentUser.email);
  const [phone, setPhone] = useState(currentUser.phone);
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  
  const [success, setSuccess] = useState('');
  const [error, setError] = useState('');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSuccess('');

    if (!name.trim() || !username.trim() || !email.trim() || !phone.trim()) {
      setError('Por favor complete todos los campos obligatorios.');
      return;
    }

    if (password && password !== confirmPassword) {
      setError('Las contraseñas nuevas no coinciden.');
      return;
    }

    // Actualizamos el objeto de usuario
    const updatedUser: User = {
      ...currentUser,
      name: name.trim(),
      username: username.trim().toLowerCase(),
      email: email.trim(),
      phone: phone.trim(),
    };

    onUpdateProfile(updatedUser);
    setSuccess('¡Su perfil ha sido guardado exitosamente!');
    setPassword('');
    setConfirmPassword('');
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-300">
      
      <header>
        <h1 className="text-3xl font-extrabold text-[#212529]">
          Mi Perfil de Usuario
        </h1>
        <p className="text-lg text-neutral-700 font-semibold mt-1">
          Modifique sus datos personales y contraseña de acceso aquí.
        </p>
      </header>

      {success && (
        <div 
          className="p-4 bg-green-100 border-l-4 border-[#198754] text-[#198754] rounded-r-md flex items-start gap-3"
          role="alert"
        >
          <CheckCircle2 className="w-6 h-6 shrink-0 mt-0.5" />
          <div>
            <p className="font-bold text-lg">Operación Exitosa:</p>
            <p className="text-base font-bold">{success}</p>
          </div>
        </div>
      )}

      {error && (
        <div 
          className="p-4 bg-red-100 border-l-4 border-[#DC3545] text-[#DC3545] rounded-r-md flex items-start gap-3"
          role="alert"
        >
          <AlertTriangle className="w-6 h-6 shrink-0 mt-0.5" />
          <div>
            <p className="font-bold text-lg">Error de Validación:</p>
            <p className="text-base font-bold">{error}</p>
          </div>
        </div>
      )}

      <form onSubmit={handleSubmit} className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-6 md:p-8 space-y-6">
        
        {/* GRUPO: DATOS BÁSICOS */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div className="flex flex-col gap-2">
            <label htmlFor="p-name" className="text-lg font-bold text-[#212529]">
              Nombre Completo *
            </label>
            <input
              id="p-name"
              type="text"
              className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
              value={name}
              onChange={(e) => setName(e.target.value)}
              required
            />
          </div>

          <div className="flex flex-col gap-2">
            <label htmlFor="p-username" className="text-lg font-bold text-[#212529]">
              Nombre de Usuario *
            </label>
            <input
              id="p-username"
              type="text"
              className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              required
            />
          </div>

          <div className="flex flex-col gap-2">
            <label htmlFor="p-email" className="text-lg font-bold text-[#212529]">
              Correo Electrónico *
            </label>
            <input
              id="p-email"
              type="email"
              className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
          </div>

          <div className="flex flex-col gap-2">
            <label htmlFor="p-phone" className="text-lg font-bold text-[#212529]">
              Teléfono de Contacto *
            </label>
            <input
              id="p-phone"
              type="text"
              className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              required
            />
          </div>
        </div>

        {/* INDICADOR DE ROL - NO EDITABLE */}
        <div className="p-4 bg-[#DEE2E6] rounded-xl border border-[#DEE2E6] flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <p className="text-sm text-neutral-700 font-bold">Su Rol en el ERP:</p>
            <p className="text-xl font-black text-[#212529]">{currentUser.role}</p>
          </div>
          <p className="text-sm text-neutral-800 font-medium max-w-md">
            * Su nivel de permisos está administrado de forma centralizada. 
            Para cambiar su rol, consulte con el Administrador del sistema.
          </p>
        </div>

        {/* CAMBIO DE CLAVE */}
        <div className="border-t-2 border-[#DEE2E6] pt-6 space-y-4">
          <div className="flex items-center gap-2">
            <KeyRound className="w-6 h-6 text-[#0D6EFD]" />
            <h3 className="text-xl font-bold text-[#212529]">Cambiar Contraseña (Opcional)</h3>
          </div>
          <p className="text-base text-neutral-700 font-medium">
            Complete únicamente si desea establecer una nueva clave para sus próximos ingresos.
          </p>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="flex flex-col gap-2">
              <label htmlFor="p-pass" className="text-lg font-bold text-[#212529]">
                Nueva Contraseña
              </label>
              <input
                id="p-pass"
                type="password"
                className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                placeholder="Escriba la nueva clave"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>

            <div className="flex flex-col gap-2">
              <label htmlFor="p-pass-conf" className="text-lg font-bold text-[#212529]">
                Confirmar Nueva Contraseña
              </label>
              <input
                id="p-pass-conf"
                type="password"
                className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                placeholder="Repita la nueva clave"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
              />
            </div>
          </div>
        </div>

        {/* BOTÓN DE ENVÍO */}
        <div className="pt-4 flex justify-end">
          <button
            type="submit"
            className="w-full md:w-auto bg-[#198754] hover:bg-[#146c43] text-white font-bold text-xl rounded-xl py-4 px-8 flex items-center justify-center gap-3 cursor-pointer"
          >
            <Save className="w-6 h-6" />
            <span>Guardar Mis Cambios</span>
          </button>
        </div>

      </form>
    </div>
  );
}
