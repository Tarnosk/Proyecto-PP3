/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from 'react';
import { Mail, ArrowLeft, CheckCircle2, AlertTriangle } from 'lucide-react';

interface RecoveryViewProps {
  onBackToLogin: () => void;
}

export function RecoveryView({ onBackToLogin }: RecoveryViewProps) {
  const [email, setEmail] = useState('');
  const [submitted, setSubmitted] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!email.trim()) {
      setError('Por favor, ingrese un correo electrónico.');
      return;
    }
    if (!email.includes('@')) {
      setError('Por favor, ingrese un correo electrónico válido.');
      return;
    }
    
    setSubmitted(true);
    setError('');
  };

  return (
    <div className="flex flex-col items-center justify-center min-h-[75vh] px-4 py-8">
      <div className="w-full max-w-xl bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-6 md:p-8 shadow-md">
        
        <header className="mb-6">
          <button
            onClick={onBackToLogin}
            className="flex items-center gap-2 text-[#0D6EFD] font-bold text-lg mb-4 hover:underline cursor-pointer"
          >
            <ArrowLeft className="w-6 h-6" />
            <span>Volver al Login</span>
          </button>
          
          <h1 className="text-2xl font-extrabold text-[#212529]">
            Recuperar Contraseña
          </h1>
          <p className="text-base text-neutral-700 mt-2">
            Le enviaremos un código de seguridad para restaurar su cuenta.
          </p>
        </header>

        {error && (
          <div 
            className="mb-6 p-4 bg-red-100 border-l-4 border-[#DC3545] text-[#DC3545] rounded-r-md flex items-start gap-3"
            role="alert"
          >
            <AlertTriangle className="w-6 h-6 shrink-0 mt-0.5" />
            <div>
              <p className="font-bold text-lg">Error:</p>
              <p className="text-base font-semibold">{error}</p>
            </div>
          </div>
        )}

        {!submitted ? (
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="flex flex-col gap-2">
              <label 
                htmlFor="recovery-email" 
                className="text-lg font-bold text-[#212529]"
              >
                Su Correo Electrónico Registrado
              </label>
              <input
                id="recovery-email"
                type="email"
                className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529] focus:bg-[#FAFAFA]"
                placeholder="Ej: alberto@distribuidorapigue.com"
                value={email}
                onChange={(e) => {
                  setEmail(e.target.value);
                  setError('');
                }}
              />
            </div>

            <button
              type="submit"
              className="w-full bg-[#0D6EFD] hover:bg-[#0b5ed7] text-white font-bold text-xl rounded-xl py-4 px-6 flex items-center justify-center gap-3 transition-colors cursor-pointer"
            >
              <Mail className="w-6 h-6" />
              <span>Enviar Código de Recuperación</span>
            </button>
          </form>
        ) : (
          <div className="text-center py-6 space-y-6">
            <div className="flex flex-col items-center justify-center text-[#198754]">
              <CheckCircle2 className="w-16 h-16 mb-4" />
              <h2 className="text-2xl font-extrabold">¡Correo Enviado!</h2>
            </div>
            
            <p className="text-lg text-neutral-800 font-semibold max-w-md mx-auto">
              Hemos enviado instrucciones detalladas y un enlace de recuperación a: 
              <br />
              <strong className="text-[#212529] text-xl block mt-2 break-all">{email}</strong>
            </p>

            <div className="bg-[#DEE2E6] p-4 rounded-xl text-left border-2 border-[#DEE2E6]">
              <p className="text-base font-bold text-[#212529] mb-1">Pasos a seguir:</p>
              <ol className="list-decimal pl-5 space-y-1 text-base text-neutral-800 font-medium">
                <li>Abra su correo e ingrese al mensaje de Pigüé ERP.</li>
                <li>Haga clic en el botón de restablecer clave.</li>
                <li>Defina su nueva contraseña segura.</li>
              </ol>
            </div>

            <button
              onClick={onBackToLogin}
              className="w-full bg-[#198754] hover:bg-[#146c43] text-white font-bold text-xl rounded-xl py-4 px-6 transition-colors cursor-pointer"
            >
              Entendido, volver al Login
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
