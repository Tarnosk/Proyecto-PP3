/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from 'react';
import { User, UserRole } from '../types';
import { 
  Plus, 
  Edit, 
  Trash2, 
  Search, 
  Check, 
  X, 
  ChevronLeft, 
  ChevronRight, 
  UserPlus, 
  Filter,
  UserCheck,
  UserX,
  ArrowLeft,
  Save,
  AlertTriangle,
  CheckCircle2
} from 'lucide-react';

interface UsersManagementViewProps {
  users: User[];
  onAddUser: (user: User) => void;
  onUpdateUser: (user: User) => void;
  onDeleteUser: (userId: string) => void;
}

export function UsersManagementView({ 
  users, 
  onAddUser, 
  onUpdateUser, 
  onDeleteUser 
}: UsersManagementViewProps) {
  const [subView, setSubView] = useState<'LIST' | 'CREATE' | 'EDIT'>('LIST');
  const [selectedUser, setSelectedUser] = useState<User | null>(null);

  // Filtros de Búsqueda
  const [searchTerm, setSearchTerm] = useState('');
  const [roleFilter, setRoleFilter] = useState<string>('TODOS');
  const [statusFilter, setStatusFilter] = useState<string>('TODOS');

  // Paginación
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 4;

  // Formulario Crear / Editar
  const [formData, setFormData] = useState({
    name: '',
    username: '',
    email: '',
    phone: '',
    role: 'Preventista' as UserRole,
    status: 'Activo' as 'Activo' | 'Inactivo'
  });
  const [formError, setFormError] = useState('');
  const [formSuccess, setFormSuccess] = useState('');

  // Control de Confirmación de Borrado
  const [confirmingDeleteId, setConfirmingDeleteId] = useState<string | null>(null);

  // Filtrado de Datos
  const filteredUsers = users.filter(user => {
    const matchesSearch = 
      user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      user.username.toLowerCase().includes(searchTerm.toLowerCase()) ||
      user.email.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesRole = roleFilter === 'TODOS' || user.role === roleFilter;
    const matchesStatus = statusFilter === 'TODOS' || user.status === statusFilter;

    return matchesSearch && matchesRole && matchesStatus;
  });

  // Cálculo de Paginación
  const totalPages = Math.ceil(filteredUsers.length / itemsPerPage) || 1;
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginatedUsers = filteredUsers.slice(startIndex, startIndex + itemsPerPage);

  // Manejar cambio de página
  const handlePageChange = (direction: 'PREV' | 'NEXT') => {
    if (direction === 'PREV' && currentPage > 1) {
      setCurrentPage(currentPage - 1);
    } else if (direction === 'NEXT' && currentPage < totalPages) {
      setCurrentPage(currentPage + 1);
    }
  };

  // Abrir Formulario Alta
  const handleOpenCreate = () => {
    setFormData({
      name: '',
      username: '',
      email: '',
      phone: '',
      role: 'Preventista',
      status: 'Activo'
    });
    setFormError('');
    setFormSuccess('');
    setSubView('CREATE');
  };

  // Abrir Formulario Modificar
  const handleOpenEdit = (user: User) => {
    setSelectedUser(user);
    setFormData({
      name: user.name,
      username: user.username,
      email: user.email,
      phone: user.phone,
      role: user.role,
      status: user.status
    });
    setFormError('');
    setFormSuccess('');
    setSubView('EDIT');
  };

  // Procesar Alta
  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setFormError('');
    setFormSuccess('');

    if (!formData.name.trim() || !formData.username.trim() || !formData.email.trim() || !formData.phone.trim()) {
      setFormError('Por favor, complete todos los campos obligatorios.');
      return;
    }

    // Verificar si el username ya existe
    const usernameExists = users.some(u => u.username.toLowerCase() === formData.username.trim().toLowerCase());
    if (usernameExists) {
      setFormError('El nombre de usuario ingresado ya está registrado.');
      return;
    }

    const newUser: User = {
      id: Date.now().toString(),
      name: formData.name.trim(),
      username: formData.username.trim().toLowerCase(),
      email: formData.email.trim(),
      phone: formData.phone.trim(),
      role: formData.role,
      status: formData.status,
      lastLogin: '-'
    };

    onAddUser(newUser);
    setFormSuccess('¡Usuario creado correctamente!');
    setTimeout(() => {
      setSubView('LIST');
    }, 1200);
  };

  // Procesar Modificación
  const handleEditSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedUser) return;
    
    setFormError('');
    setFormSuccess('');

    if (!formData.name.trim() || !formData.username.trim() || !formData.email.trim() || !formData.phone.trim()) {
      setFormError('Por favor, complete todos los campos obligatorios.');
      return;
    }

    // Verificar si el username ya existe en otro usuario
    const usernameExists = users.some(u => u.id !== selectedUser.id && u.username.toLowerCase() === formData.username.trim().toLowerCase());
    if (usernameExists) {
      setFormError('El nombre de usuario ingresado ya está en uso por otro empleado.');
      return;
    }

    const updatedUser: User = {
      ...selectedUser,
      name: formData.name.trim(),
      username: formData.username.trim().toLowerCase(),
      email: formData.email.trim(),
      phone: formData.phone.trim(),
      role: formData.role,
      status: formData.status
    };

    onUpdateUser(updatedUser);
    setFormSuccess('¡Usuario actualizado correctamente!');
    setTimeout(() => {
      setSubView('LIST');
    }, 1200);
  };

  // Alternar estado Activo/Inactivo rápido desde el listado
  const handleToggleStatus = (user: User) => {
    const updatedUser: User = {
      ...user,
      status: user.status === 'Activo' ? 'Inactivo' : 'Activo'
    };
    onUpdateUser(updatedUser);
  };

  // Borrado directo o baja definitiva
  const handleDeleteConfirm = (userId: string) => {
    onDeleteUser(userId);
    setConfirmingDeleteId(null);
  };

  return (
    <div className="space-y-6">
      
      {/* ===================================================
          VISTA 1: LISTADO DE USUARIOS (ABM MASTER)
          =================================================== */}
      {subView === 'LIST' && (
        <div className="space-y-6 animate-in fade-in duration-200">
          
          <header className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
              <h1 className="text-3xl font-extrabold text-[#212529]">
                Panel de Personal y Usuarios
              </h1>
              <p className="text-lg text-neutral-700 font-semibold mt-1">
                Administre el acceso del personal de la distribuidora.
              </p>
            </div>

            <button
              onClick={handleOpenCreate}
              className="w-full md:w-auto bg-[#198754] hover:bg-[#146c43] text-white font-extrabold text-xl py-3.5 px-6 rounded-xl flex items-center justify-center gap-3 transition-colors cursor-pointer"
            >
              <Plus className="w-6 h-6" />
              <span>Alta de Usuario</span>
            </button>
          </header>

          {/* FILTROS DE BÚSQUEDA Y SELECCIÓN */}
          <div className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-5 space-y-4">
            <div className="flex items-center gap-2 text-[#212529]">
              <Filter className="w-6 h-6" />
              <h2 className="text-lg font-bold">Filtros de Búsqueda Avanzada:</h2>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              
              {/* Buscador de texto */}
              <div className="flex flex-col gap-1.5">
                <label htmlFor="search" className="text-base font-bold text-neutral-800">
                  Buscar por Nombre o Correo
                </label>
                <div className="relative">
                  <span className="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                    <Search className="h-5 w-5 text-neutral-600" />
                  </span>
                  <input
                    id="search"
                    type="text"
                    className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl pl-11 pr-4 py-3 text-base font-semibold text-[#212529]"
                    placeholder="Escriba aquí..."
                    value={searchTerm}
                    onChange={(e) => {
                      setSearchTerm(e.target.value);
                      setCurrentPage(1);
                    }}
                  />
                </div>
              </div>

              {/* Filtro por Rol */}
              <div className="flex flex-col gap-1.5">
                <label htmlFor="role-filter" className="text-base font-bold text-neutral-800">
                  Filtrar por Rol
                </label>
                <select
                  id="role-filter"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-base font-semibold text-[#212529]"
                  value={roleFilter}
                  onChange={(e) => {
                    setRoleFilter(e.target.value);
                    setCurrentPage(1);
                  }}
                >
                  <option value="TODOS">Ver todos los Roles</option>
                  <option value="Administrador">Administrador</option>
                  <option value="Repartidor">Repartidor</option>
                  <option value="Preventista">Preventista</option>
                  <option value="Administrativo">Administrativo</option>
                </select>
              </div>

              {/* Filtro por Estado */}
              <div className="flex flex-col gap-1.5">
                <label htmlFor="status-filter" className="text-base font-bold text-neutral-800">
                  Filtrar por Estado
                </label>
                <select
                  id="status-filter"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-base font-semibold text-[#212529]"
                  value={statusFilter}
                  onChange={(e) => {
                    setStatusFilter(e.target.value);
                    setCurrentPage(1);
                  }}
                >
                  <option value="TODOS">Ver todos los Estados</option>
                  <option value="Activo">Solo Activos</option>
                  <option value="Inactivo">Solo Inactivos</option>
                </select>
              </div>

            </div>
          </div>

          {/* MENSAJE CUANDO NO HAY RESULTADOS */}
          {filteredUsers.length === 0 && (
            <div className="p-12 text-center bg-[#E9ECEF] rounded-2xl border-2 border-[#DEE2E6]">
              <p className="text-xl font-bold text-[#212529]">No se encontraron usuarios coincidentes.</p>
              <p className="text-base text-neutral-700 mt-1">Pruebe limpiando los filtros o realizando otra búsqueda.</p>
            </div>
          )}

          {/* TABLA DE ESCRITORIO (md:block) */}
          {filteredUsers.length > 0 && (
            <div className="hidden md:block bg-transparent overflow-x-auto">
              <table className="w-full text-left border-collapse border-spacing-0">
                <thead>
                  <tr className="bg-[#E9ECEF] border-2 border-[#DEE2E6] text-[#212529]">
                    <th className="p-4 font-extrabold text-lg">Nombre / Datos de Contacto</th>
                    <th className="p-4 font-extrabold text-lg">Usuario</th>
                    <th className="p-4 font-extrabold text-lg">Rol Asignado</th>
                    <th className="p-4 font-extrabold text-lg">Estado</th>
                    <th className="p-4 font-extrabold text-lg text-center">Acciones y Gestión</th>
                  </tr>
                </thead>
                <tbody className="divide-y-2 divide-[#DEE2E6]">
                  {paginatedUsers.map((user) => (
                    <tr 
                      key={user.id} 
                      className="bg-[#E9ECEF] border-x-2 border-b-2 border-[#DEE2E6] hover:bg-[#DEE2E6]/60 transition-colors"
                    >
                      <td className="p-4">
                        <span className="font-extrabold text-xl text-[#212529] block">
                          {user.name}
                        </span>
                        <span className="text-sm text-neutral-700 font-semibold block">
                          Email: {user.email}
                        </span>
                        <span className="text-sm text-neutral-700 font-semibold block">
                          Tel: {user.phone}
                        </span>
                      </td>
                      <td className="p-4 font-bold text-[#212529] text-lg">
                        {user.username}
                      </td>
                      <td className="p-4">
                        <span className="inline-block px-3 py-1 bg-white border border-[#DEE2E6] rounded-lg text-base font-bold text-[#0D6EFD]">
                          {user.role}
                        </span>
                      </td>
                      <td className="p-4">
                        <button
                          onClick={() => handleToggleStatus(user)}
                          className={`px-3 py-1.5 rounded-lg font-black text-sm flex items-center gap-2 border-2 transition-all cursor-pointer ${
                            user.status === 'Activo'
                              ? 'bg-green-100 text-[#198754] border-[#198754] hover:bg-green-200'
                              : 'bg-red-100 text-[#DC3545] border-[#DC3545] hover:bg-red-200'
                          }`}
                          title="Haga clic para cambiar estado"
                        >
                          {user.status === 'Activo' ? <UserCheck className="w-4 h-4" /> : <UserX className="w-4 h-4" />}
                          <span>{user.status}</span>
                        </button>
                      </td>
                      <td className="p-4">
                        <div className="flex items-center justify-center gap-3">
                          <button
                            onClick={() => handleOpenEdit(user)}
                            className="bg-[#FAFAFA] hover:bg-neutral-200 border-2 border-[#DEE2E6] text-[#212529] px-4 py-2 rounded-xl font-bold text-base flex items-center gap-1.5 cursor-pointer"
                          >
                            <Edit className="w-5 h-5 text-[#0D6EFD]" />
                            <span>Editar</span>
                          </button>

                          {/* Confirmación inline para borrado seguro */}
                          {confirmingDeleteId === user.id ? (
                            <div className="flex items-center gap-1.5 bg-red-100 border border-red-300 p-1 rounded-xl">
                              <button
                                onClick={() => handleDeleteConfirm(user.id)}
                                className="bg-[#DC3545] hover:bg-[#b02a37] text-white font-bold text-xs px-2 py-1.5 rounded-lg cursor-pointer"
                              >
                                Confirmar Baja
                              </button>
                              <button
                                onClick={() => setConfirmingDeleteId(null)}
                                className="bg-neutral-300 hover:bg-neutral-400 text-neutral-800 font-bold text-xs px-2 py-1.5 rounded-lg cursor-pointer"
                              >
                                Cancelar
                              </button>
                            </div>
                          ) : (
                            <button
                              onClick={() => setConfirmingDeleteId(user.id)}
                              className="bg-transparent hover:bg-red-100 text-[#DC3545] border-2 border-transparent hover:border-[#DC3545] px-4 py-2 rounded-xl font-bold text-base flex items-center gap-1.5 cursor-pointer"
                            >
                              <Trash2 className="w-5 h-5" />
                              <span>Eliminar</span>
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {/* TARJETAS MÓVILES PARA REEMPLAZAR LA TABLA (md:hidden) */}
          {filteredUsers.length > 0 && (
            <div className="block md:hidden space-y-4">
              <span className="text-sm font-bold text-neutral-700 block mb-2 uppercase tracking-wide">
                Listado de Usuarios (Versión Móvil Adaptada):
              </span>
              
              {paginatedUsers.map((user) => (
                <div 
                  key={user.id} 
                  className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-5 space-y-4"
                >
                  <div className="flex justify-between items-start gap-2">
                    <div>
                      <h3 className="text-xl font-black text-[#212529]">{user.name}</h3>
                      <p className="text-base text-neutral-800 font-bold mt-0.5">Usuario: {user.username}</p>
                    </div>
                    <span className="px-3 py-1 bg-white border border-[#DEE2E6] rounded-lg text-sm font-black text-[#0D6EFD]">
                      {user.role}
                    </span>
                  </div>

                  <div className="text-base text-neutral-800 space-y-1 font-semibold border-y border-[#DEE2E6] py-2">
                    <p className="truncate">Email: {user.email}</p>
                    <p>Teléfono: {user.phone}</p>
                  </div>

                  <div className="flex flex-col gap-3">
                    <div className="flex justify-between items-center">
                      <span className="text-base font-bold text-neutral-800">Estado:</span>
                      <button
                        onClick={() => handleToggleStatus(user)}
                        className={`px-4 py-2 rounded-xl font-black text-base flex items-center gap-2 border-2 transition-all cursor-pointer ${
                          user.status === 'Activo'
                            ? 'bg-green-100 text-[#198754] border-[#198754] hover:bg-green-200'
                            : 'bg-red-100 text-[#DC3545] border-[#DC3545] hover:bg-red-200'
                        }`}
                      >
                        {user.status === 'Activo' ? <UserCheck className="w-5 h-5" /> : <UserX className="w-5 h-5" />}
                        <span>{user.status}</span>
                      </button>
                    </div>

                    <div className="flex gap-2 pt-2 border-t border-[#DEE2E6]">
                      <button
                        onClick={() => handleOpenEdit(user)}
                        className="flex-1 bg-[#FAFAFA] hover:bg-[#DEE2E6] border-2 border-[#DEE2E6] text-[#212529] py-3.5 px-4 rounded-xl font-bold text-lg flex items-center justify-center gap-2 cursor-pointer"
                      >
                        <Edit className="w-5 h-5 text-[#0D6EFD]" />
                        <span>Editar</span>
                      </button>

                      {confirmingDeleteId === user.id ? (
                        <div className="flex-1 flex flex-col gap-1.5">
                          <button
                            onClick={() => handleDeleteConfirm(user.id)}
                            className="w-full bg-[#DC3545] text-white font-bold py-3 px-4 rounded-xl cursor-pointer"
                          >
                            ✓ Confirmar Borrado
                          </button>
                          <button
                            onClick={() => setConfirmingDeleteId(null)}
                            className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] text-neutral-800 font-bold py-2 rounded-xl cursor-pointer"
                          >
                            Cancelar
                          </button>
                        </div>
                      ) : (
                        <button
                          onClick={() => setConfirmingDeleteId(user.id)}
                          className="flex-1 bg-transparent hover:bg-red-100 text-[#DC3545] border-2 border-[#DC3545] py-3.5 px-4 rounded-xl font-bold text-lg flex items-center justify-center gap-2 cursor-pointer"
                        >
                          <Trash2 className="w-5 h-5" />
                          <span>Eliminar</span>
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}

          {/* PAGINACIÓN CON BOTONES DE TAMAÑO ACCESIBLE */}
          {filteredUsers.length > 0 && (
            <div className="flex items-center justify-between bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-4">
              <span className="text-base md:text-lg font-bold text-neutral-800">
                Página <strong className="text-[#212529] text-xl">{currentPage}</strong> de <strong className="text-[#212529] text-xl">{totalPages}</strong>
              </span>
              
              <div className="flex gap-3">
                <button
                  onClick={() => handlePageChange('PREV')}
                  disabled={currentPage === 1}
                  className={`px-5 py-3 rounded-xl border-2 font-bold text-lg flex items-center gap-2 cursor-pointer transition-colors ${
                    currentPage === 1
                      ? 'bg-neutral-200 border-neutral-300 text-neutral-400 cursor-not-allowed'
                      : 'bg-[#FAFAFA] border-[#DEE2E6] text-[#212529] hover:bg-[#DEE2E6]'
                  }`}
                >
                  <ChevronLeft className="w-6 h-6" />
                  <span>Anterior</span>
                </button>

                <button
                  onClick={() => handlePageChange('NEXT')}
                  disabled={currentPage === totalPages}
                  className={`px-5 py-3 rounded-xl border-2 font-bold text-lg flex items-center gap-2 cursor-pointer transition-colors ${
                    currentPage === totalPages
                      ? 'bg-neutral-200 border-neutral-300 text-neutral-400 cursor-not-allowed'
                      : 'bg-[#FAFAFA] border-[#DEE2E6] text-[#212529] hover:bg-[#DEE2E6]'
                  }`}
                >
                  <span>Siguiente</span>
                  <ChevronRight className="w-6 h-6" />
                </button>
              </div>
            </div>
          )}

        </div>
      )}

      {/* ===================================================
          VISTA 2: ALTA DE USUARIO (ABM CREAR)
          =================================================== */}
      {subView === 'CREATE' && (
        <div className="space-y-6 animate-in fade-in duration-200">
          
          <header>
            <button
              onClick={() => setSubView('LIST')}
              className="flex items-center gap-2 text-[#0D6EFD] font-bold text-lg mb-3 hover:underline cursor-pointer"
            >
              <ArrowLeft className="w-6 h-6" />
              <span>Volver al Listado</span>
            </button>
            <h1 className="text-3xl font-extrabold text-[#212529]">
              Alta de Nuevo Usuario
            </h1>
            <p className="text-lg text-neutral-700 font-semibold mt-1">
              Registre las credenciales y el perfil para un nuevo integrante de la distribuidora.
            </p>
          </header>

          {formError && (
            <div className="p-4 bg-red-100 border-l-4 border-[#DC3545] text-[#DC3545] rounded-r-md flex items-start gap-3" role="alert">
              <AlertTriangle className="w-6 h-6 shrink-0 mt-0.5" />
              <div>
                <p className="font-bold text-lg">Error:</p>
                <p className="text-base font-semibold">{formError}</p>
              </div>
            </div>
          )}

          {formSuccess && (
            <div className="p-4 bg-green-100 border-l-4 border-[#198754] text-[#198754] rounded-r-md flex items-start gap-3" role="alert">
              <CheckCircle2 className="w-6 h-6 shrink-0 mt-0.5" />
              <div>
                <p className="font-bold text-lg">Éxito:</p>
                <p className="text-base font-semibold">{formSuccess}</p>
              </div>
            </div>
          )}

          <form onSubmit={handleCreateSubmit} className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-6 md:p-8 space-y-6">
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="flex flex-col gap-2">
                <label htmlFor="create-name" className="text-lg font-bold text-[#212529]">
                  Nombre Completo *
                </label>
                <input
                  id="create-name"
                  type="text"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                  placeholder="Ej: Don Alberto"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  required
                />
              </div>

              <div className="flex flex-col gap-2">
                <label htmlFor="create-username" className="text-lg font-bold text-[#212529]">
                  Nombre de Usuario *
                </label>
                <input
                  id="create-username"
                  type="text"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                  placeholder="Ej: alberto (para ingreso)"
                  value={formData.username}
                  onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                  required
                />
              </div>

              <div className="flex flex-col gap-2">
                <label htmlFor="create-email" className="text-lg font-bold text-[#212529]">
                  Correo Electrónico *
                </label>
                <input
                  id="create-email"
                  type="email"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                  placeholder="Ej: alberto@correo.com"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  required
                />
              </div>

              <div className="flex flex-col gap-2">
                <label htmlFor="create-phone" className="text-lg font-bold text-[#212529]">
                  Teléfono de Contacto *
                </label>
                <input
                  id="create-phone"
                  type="text"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                  placeholder="Ej: 11-4567-8901"
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                  required
                />
              </div>

              <div className="flex flex-col gap-2">
                <label htmlFor="create-role" className="text-lg font-bold text-[#212529]">
                  Rol Asignado *
                </label>
                <select
                  id="create-role"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                  value={formData.role}
                  onChange={(e) => setFormData({ ...formData, role: e.target.value as UserRole })}
                >
                  <option value="Administrador">Administrador</option>
                  <option value="Repartidor">Repartidor</option>
                  <option value="Preventista">Preventista</option>
                  <option value="Administrativo">Administrativo</option>
                </select>
              </div>

              <div className="flex flex-col gap-2">
                <label htmlFor="create-status" className="text-lg font-bold text-[#212529]">
                  Estado Inicial *
                </label>
                <select
                  id="create-status"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                  value={formData.status}
                  onChange={(e) => setFormData({ ...formData, status: e.target.value as 'Activo' | 'Inactivo' })}
                >
                  <option value="Activo">Activo (Habilitado para ingresar)</option>
                  <option value="Inactivo">Inactivo (Acceso suspendido)</option>
                </select>
              </div>
            </div>

            <div className="pt-6 border-t border-[#DEE2E6] flex flex-col sm:flex-row justify-end gap-3">
              <button
                type="button"
                onClick={() => setSubView('LIST')}
                className="w-full sm:w-auto bg-[#FAFAFA] hover:bg-[#DEE2E6] border-2 border-[#DEE2E6] text-neutral-800 font-bold text-lg py-3 px-6 rounded-xl cursor-pointer"
              >
                Cancelar
              </button>
              
              <button
                type="submit"
                className="w-full sm:w-auto bg-[#198754] hover:bg-[#146c43] text-white font-bold text-lg py-3 px-8 rounded-xl flex items-center justify-center gap-2 cursor-pointer"
              >
                <Save className="w-5 h-5" />
                <span>Crear Usuario</span>
              </button>
            </div>

          </form>
        </div>
      )}

      {/* ===================================================
          VISTA 3: MODIFICACIÓN DE USUARIO (ABM EDITAR)
          =================================================== */}
      {subView === 'EDIT' && selectedUser && (
        <div className="space-y-6 animate-in fade-in duration-200">
          
          <header>
            <button
              onClick={() => setSubView('LIST')}
              className="flex items-center gap-2 text-[#0D6EFD] font-bold text-lg mb-3 hover:underline cursor-pointer"
            >
              <ArrowLeft className="w-6 h-6" />
              <span>Volver al Listado</span>
            </button>
            <h1 className="text-3xl font-extrabold text-[#212529]">
              Modificar Usuario: {selectedUser.name}
            </h1>
            <p className="text-lg text-neutral-700 font-semibold mt-1">
              Edite la información del perfil o suspenda temporalmente su estado.
            </p>
          </header>

          {formError && (
            <div className="p-4 bg-red-100 border-l-4 border-[#DC3545] text-[#DC3545] rounded-r-md flex items-start gap-3" role="alert">
              <AlertTriangle className="w-6 h-6 shrink-0 mt-0.5" />
              <div>
                <p className="font-bold text-lg">Error:</p>
                <p className="text-base font-semibold">{formError}</p>
              </div>
            </div>
          )}

          {formSuccess && (
            <div className="p-4 bg-green-100 border-l-4 border-[#198754] text-[#198754] rounded-r-md flex items-start gap-3" role="alert">
              <CheckCircle2 className="w-6 h-6 shrink-0 mt-0.5" />
              <div>
                <p className="font-bold text-lg">Éxito:</p>
                <p className="text-base font-semibold">{formSuccess}</p>
              </div>
            </div>
          )}

          <form onSubmit={handleEditSubmit} className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-6 md:p-8 space-y-6">
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="flex flex-col gap-2">
                <label htmlFor="edit-name" className="text-lg font-bold text-[#212529]">
                  Nombre Completo *
                </label>
                <input
                  id="edit-name"
                  type="text"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  required
                />
              </div>

              <div className="flex flex-col gap-2">
                <label htmlFor="edit-username" className="text-lg font-bold text-[#212529]">
                  Nombre de Usuario *
                </label>
                <input
                  id="edit-username"
                  type="text"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                  value={formData.username}
                  onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                  required
                />
              </div>

              <div className="flex flex-col gap-2">
                <label htmlFor="edit-email" className="text-lg font-bold text-[#212529]">
                  Correo Electrónico *
                </label>
                <input
                  id="edit-email"
                  type="email"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  required
                />
              </div>

              <div className="flex flex-col gap-2">
                <label htmlFor="edit-phone" className="text-lg font-bold text-[#212529]">
                  Teléfono de Contacto *
                </label>
                <input
                  id="edit-phone"
                  type="text"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                  required
                />
              </div>

              <div className="flex flex-col gap-2">
                <label htmlFor="edit-role" className="text-lg font-bold text-[#212529]">
                  Rol Asignado *
                </label>
                <select
                  id="edit-role"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                  value={formData.role}
                  onChange={(e) => setFormData({ ...formData, role: e.target.value as UserRole })}
                >
                  <option value="Administrador">Administrador</option>
                  <option value="Repartidor">Repartidor</option>
                  <option value="Preventista">Preventista</option>
                  <option value="Administrativo">Administrativo</option>
                </select>
              </div>

              <div className="flex flex-col gap-2">
                <label htmlFor="edit-status" className="text-lg font-bold text-[#212529]">
                  Estado *
                </label>
                <select
                  id="edit-status"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                  value={formData.status}
                  onChange={(e) => setFormData({ ...formData, status: e.target.value as 'Activo' | 'Inactivo' })}
                >
                  <option value="Activo">Activo (Habilitado)</option>
                  <option value="Inactivo">Inactivo (Suspendido)</option>
                </select>
              </div>
            </div>

            <div className="pt-6 border-t border-[#DEE2E6] flex flex-col sm:flex-row justify-end gap-3">
              <button
                type="button"
                onClick={() => setSubView('LIST')}
                className="w-full sm:w-auto bg-[#FAFAFA] hover:bg-[#DEE2E6] border-2 border-[#DEE2E6] text-neutral-800 font-bold text-lg py-3 px-6 rounded-xl cursor-pointer"
              >
                Cancelar
              </button>
              
              <button
                type="submit"
                className="w-full sm:w-auto bg-[#198754] hover:bg-[#146c43] text-white font-bold text-lg py-3 px-8 rounded-xl flex items-center justify-center gap-2 cursor-pointer"
              >
                <Save className="w-5 h-5" />
                <span>Guardar Cambios</span>
              </button>
            </div>

          </form>
        </div>
      )}

    </div>
  );
}
