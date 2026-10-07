/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from 'react';
import { User, UserRole } from '../types';
import { LogIn, KeyRound, AlertTriangle, ShieldCheck } from 'lucide-react';

interface LoginViewProps {
  users: User[];
  onLoginSuccess: (user: User) => void;
  onNavigateToRecovery: () => void;
}

export function LoginView({ users, onLoginSuccess, onNavigateToRecovery }: LoginViewProps) {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');

  // Simulación de credenciales para facilitar pruebas de roles
  const handleFormSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!username.trim()) {
      setError('Por favor, ingrese su nombre de usuario.');
      return;
    }

    const foundUser = users.find(
      (u) => u.username.toLowerCase() === username.trim().toLowerCase()
    );

    if (foundUser) {
      if (foundUser.status === 'Inactivo') {
        setError('Este usuario está Inactivo. Contacte al administrador.');
        return;
      }
      // Loguea exitosamente
      onLoginSuccess(foundUser);
    } else {
      setError('Usuario no encontrado. Pruebe los accesos rápidos de prueba abajo.');
    }
  };

  const handleQuickLogin = (user: User) => {
    if (user.status === 'Inactivo') {
      setError('Este usuario está Inactivo. Contacte al administrador.');
      return;
    }
    onLoginSuccess(user);
  };

  return (
    <div className="flex flex-col items-center justify-center min-h-[85vh] px-4 py-8">
      {/* Tarjeta de Login - Color de fondo suave para evitar deslumbramiento */}
      <div className="w-full max-w-xl bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-6 md:p-8 shadow-md">
        
        <header className="text-center mb-6">
          {/* Nombre descriptivo y humilde */}
          <h1 className="text-3xl font-extrabold text-[#212529] tracking-tight mb-2">
            Distribuidora Pigüé
          </h1>
          <p className="text-lg text-neutral-700 font-medium">
            Sistema de Gestión Comercial (ERP)
          </p>
        </header>

        {error && (
          <div 
            className="mb-6 p-4 bg-red-100 border-l-4 border-[#DC3545] text-[#DC3545] rounded-r-md flex items-start gap-3"
            role="alert"
          >
            <AlertTriangle className="w-6 h-6 shrink-0 mt-0.5" />
            <div>
              <p className="font-bold text-lg">Atención:</p>
              <p className="text-base font-semibold">{error}</p>
            </div>
          </div>
        )}

        <form onSubmit={handleFormSubmit} className="space-y-6">
          {/* Campo Usuario */}
          <div className="flex flex-col gap-2">
            <label 
              htmlFor="username" 
              className="text-lg font-bold text-[#212529]"
            >
              Nombre de Usuario
            </label>
            <input
              id="username"
              type="text"
              className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529] focus:bg-[#FAFAFA]"
              placeholder="Ej: alberto"
              value={username}
              onChange={(e) => {
                setUsername(e.target.value);
                setError('');
              }}
            />
          </div>

          {/* Campo Contraseña */}
          <div className="flex flex-col gap-2">
            <label 
              htmlFor="password" 
              className="text-lg font-bold text-[#212529]"
            >
              Contraseña de Acceso
            </label>
            <input
              id="password"
              type="password"
              className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529] focus:bg-[#FAFAFA]"
              placeholder="••••••••"
              value={password}
              onChange={(e) => {
                setPassword(e.target.value);
                setError('');
              }}
            />
          </div>

          {/* Botones de acción principales */}
          <div className="flex flex-col gap-3 pt-2">
            <button
              type="submit"
              className="w-full bg-[#198754] hover:bg-[#146c43] text-white font-bold text-xl rounded-xl py-4 px-6 flex items-center justify-center gap-3 transition-colors cursor-pointer"
            >
              <LogIn className="w-6 h-6" />
              <span>Ingresar al Sistema</span>
            </button>

            <button
              type="button"
              onClick={onNavigateToRecovery}
              className="w-full bg-transparent hover:bg-neutral-200 text-[#0D6EFD] font-bold text-lg rounded-xl py-3 px-6 flex items-center justify-center gap-2 transition-colors cursor-pointer"
            >
              <KeyRound className="w-5 h-5" />
              <span>¿Olvidó su contraseña? Recuperar aquí</span>
            </button>
          </div>
        </form>
      </div>

      {/* Panel de Accesos Rápidos para Facilitar Pruebas de Roles */}
      <div className="w-full max-w-xl mt-8 bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-6">
        <div className="flex items-center gap-2 mb-4 text-[#212529]">
          <ShieldCheck className="w-6 h-6" />
          <h2 className="text-xl font-bold">Accesos Rápidos para Prueba de Roles:</h2>
        </div>
        <p className="text-base text-neutral-700 mb-4 font-semibold">
          Haga clic en cualquiera para ingresar instantáneamente con el perfil y permisos configurados:
        </p>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {users.map((user) => (
            <button
              key={user.id}
              onClick={() => handleQuickLogin(user)}
              className="bg-[#FAFAFA] hover:bg-[#DEE2E6] border-2 border-[#DEE2E6] rounded-xl p-3 text-left transition-colors cursor-pointer flex flex-col justify-between"
            >
              <span className="font-bold text-lg text-[#212529]">{user.name}</span>
              <div className="flex justify-between items-center mt-1 text-sm">
                <span className="font-semibold text-[#0D6EFD]">{user.role}</span>
                <span className={`px-2 py-0.5 rounded-md font-bold text-xs ${
                  user.status === 'Activo' ? 'bg-green-100 text-[#198754]' : 'bg-red-100 text-[#DC3545]'
                }`}>
                  {user.status}
                </span>
              </div>
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}
