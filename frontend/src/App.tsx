/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState, useEffect } from 'react';
import { User, RolePermissions, Product, PriceList, Customer, Promotion, Order, Supplier, Purchase, Vehicle, DeliveryRoute, CashMovement, CustomerAccount, CustomerAccountMovement, ElectronicInvoice } from './types';
import { 
  loadUsers, 
  saveUsers, 
  loadPermissions, 
  savePermissions,
  loadProducts,
  saveProducts,
  loadPriceLists,
  savePriceLists,
  loadCustomers,
  saveCustomers,
  loadPromotions,
  savePromotions,
  loadOrders,
  saveOrders,
  loadSuppliers,
  saveSuppliers,
  loadPurchases,
  savePurchases,
  loadVehicles,
  saveVehicles,
  loadDeliveryRoutes,
  saveDeliveryRoutes,
  loadCashMovements,
  saveCashMovements,
  loadCustomerAccounts,
  saveCustomerAccounts,
  loadCustomerAccountMovements,
  saveCustomerAccountMovements,
  loadElectronicInvoices,
  saveElectronicInvoices
} from './data';
import { productosService, proveedoresService, comprasService } from './services';
import { LoginView } from './components/LoginView';
import { RecoveryView } from './components/RecoveryView';
import { DashboardLayout } from './components/DashboardLayout';
import { DashboardMainView } from './components/DashboardMainView';
import { UserProfileView } from './components/UserProfileView';
import { UsersManagementView } from './components/UsersManagementView';
import { RolesManagementView } from './components/RolesManagementView';
import { SalesView } from './components/SalesView';
import { InventoryView } from './components/InventoryView';
import { CustomersView } from './components/CustomersView';
import { PurchasesView } from './components/PurchasesView';
import { LogisticsView } from './components/LogisticsView';
import { FinancesView } from './components/FinancesView';

export default function App() {
  const [users, setUsers] = useState<User[]>([]);
  const [permissions, setPermissions] = useState<RolePermissions[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [priceLists, setPriceLists] = useState<PriceList[]>([]);
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [suppliers, setSuppliers] = useState<Supplier[]>([]);
  const [purchases, setPurchases] = useState<Purchase[]>([]);
  const [promotions, setPromotions] = useState<Promotion[]>([]);
  const [orders, setOrders] = useState<Order[]>([]);
  const [vehicles, setVehicles] = useState<Vehicle[]>([]);
  const [deliveryRoutes, setDeliveryRoutes] = useState<DeliveryRoute[]>([]);
  const [cashMovements, setCashMovements] = useState<CashMovement[]>([]);
  const [customerAccounts, setCustomerAccounts] = useState<CustomerAccount[]>([]);
  const [customerAccountMovements, setCustomerAccountMovements] = useState<CustomerAccountMovement[]>([]);
  const [electronicInvoices, setElectronicInvoices] = useState<ElectronicInvoice[]>([]);
  
  // Recuperar sesión y vista previa tras recargas
  const [currentUser, setCurrentUser] = useState<User | null>(() => {
    try {
      const saved = localStorage.getItem('erp_pigüe_current_user');
      return saved ? JSON.parse(saved) : null;
    } catch {
      return null;
    }
  });

  const [currentView, setCurrentView] = useState<'LOGIN' | 'RECOVERY' | 'DASHBOARD' | 'PROFILE' | 'USERS_LIST' | 'ROLES_MANAGEMENT' | 'SALES' | 'INVENTORY' | 'PURCHASES' | 'CUSTOMERS' | 'LOGISTICS' | 'FINANCES'>(() => {
    try {
      const savedUser = localStorage.getItem('erp_pigüe_current_user');
      const savedView = localStorage.getItem('erp_pigüe_current_view');
      if (savedUser && savedView) {
        return savedView as any;
      }
    } catch {}
    return 'LOGIN';
  });

  const handleNavigate = (view: typeof currentView) => {
    setCurrentView(view);
    try {
      localStorage.setItem('erp_pigüe_current_view', view);
    } catch {}
  };

  const [apiConnected, setApiConnected] = useState<boolean | null>(null);

  const fetchApiData = async () => {
    try {
      const [prodsRes, suppsRes, purchRes] = await Promise.allSettled([
        productosService.getAll(),
        proveedoresService.getAll(),
        comprasService.getAll(),
      ]);

      let anySuccess = false;

      if (prodsRes.status === 'fulfilled' && prodsRes.value.length > 0) {
        setProducts(prodsRes.value);
        saveProducts(prodsRes.value);
        anySuccess = true;
      }

      if (suppsRes.status === 'fulfilled' && suppsRes.value.length > 0) {
        setSuppliers(suppsRes.value);
        saveSuppliers(suppsRes.value);
        anySuccess = true;
      }

      if (purchRes.status === 'fulfilled') {
        setPurchases(purchRes.value);
        savePurchases(purchRes.value);
        anySuccess = true;
      }

      setApiConnected(anySuccess);
    } catch {
      setApiConnected(false);
    }
  };

  // Inicializar cargando de localStorage y sincronizar con API de Laravel
  useEffect(() => {
    setUsers(loadUsers());
    setPermissions(loadPermissions());
    setProducts(loadProducts());
    setPriceLists(loadPriceLists());
    setCustomers(loadCustomers());
    setSuppliers(loadSuppliers());
    setPurchases(loadPurchases());
    setPromotions(loadPromotions());
    setOrders(loadOrders());
    setVehicles(loadVehicles());
    setDeliveryRoutes(loadDeliveryRoutes());
    setCashMovements(loadCashMovements());
    setCustomerAccounts(loadCustomerAccounts());
    setCustomerAccountMovements(loadCustomerAccountMovements());
    setElectronicInvoices(loadElectronicInvoices());

    // Carga de la API de Laravel
    fetchApiData();
  }, []);

  // Manejar Login Correcto
  const handleLoginSuccess = (user: User) => {
    // Registramos la última hora de ingreso
    const now = new Date();
    const formattedDate = `${now.getFullYear()}-0${now.getMonth() + 1}-${now.getDate().toString().padStart(2, '0')} ${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;
    
    const updatedUsers = users.map((u) => {
      if (u.id === user.id) {
        return { ...u, lastLogin: formattedDate };
      }
      return u;
    });

    setUsers(updatedUsers);
    saveUsers(updatedUsers);

    // Actualizamos sesión actual y persistimos para recargas de página
    const loggedUser = updatedUsers.find((u) => u.id === user.id) || user;
    setCurrentUser(loggedUser);
    try {
      localStorage.setItem('erp_pigüe_current_user', JSON.stringify(loggedUser));
      localStorage.setItem('erp_pigüe_current_view', 'DASHBOARD');
    } catch {}
    setCurrentView('DASHBOARD');
  };

  // Manejar Logout
  const handleLogout = () => {
    setCurrentUser(null);
    try {
      localStorage.removeItem('erp_pigüe_current_user');
      localStorage.removeItem('erp_pigüe_current_view');
    } catch {}
    setCurrentView('LOGIN');
  };

  // Guardar Cambios de Perfil
  const handleUpdateProfile = (updatedUser: User) => {
    const updatedUsers = users.map((u) => (u.id === updatedUser.id ? updatedUser : u));
    setUsers(updatedUsers);
    saveUsers(updatedUsers);
    setCurrentUser(updatedUser);
    try {
      localStorage.setItem('erp_pigüe_current_user', JSON.stringify(updatedUser));
    } catch {}
  };

  // ABM: Agregar nuevo usuario
  const handleAddUser = (newUser: User) => {
    const updatedList = [...users, newUser];
    setUsers(updatedList);
    saveUsers(updatedList);
  };

  // ABM: Modificar usuario existente o suspenderlo
  const handleUpdateUser = (updatedUser: User) => {
    const updatedList = users.map((u) => (u.id === updatedUser.id ? updatedUser : u));
    setUsers(updatedList);
    saveUsers(updatedList);
    // Si modificamos al usuario que está logueado actualmente, actualizar su sesión
    if (currentUser && currentUser.id === updatedUser.id) {
      setCurrentUser(updatedUser);
    }
  };

  // ABM: Eliminar usuario
  const handleDeleteUser = (userId: string) => {
    const updatedList = users.filter((u) => u.id !== userId);
    setUsers(updatedList);
    saveUsers(updatedList);
  };

  // Actualizar la matriz de permisos de roles
  const handleUpdatePermissions = (updatedPermissions: RolePermissions[]) => {
    setPermissions(updatedPermissions);
    savePermissions(updatedPermissions);
  };

  // Renderizador condicional de pantallas internas del ERP
  const renderView = () => {
    if (!currentUser) return null;

    switch (currentView) {
      case 'DASHBOARD':
        return (
          <DashboardMainView 
            currentUser={currentUser} 
            permissions={permissions} 
            products={products}
            orders={orders}
            onNavigate={(view) => handleNavigate(view as any)} 
            onLogout={handleLogout}
          />
        );
      case 'SALES':
        return (
          <SalesView
            currentUser={currentUser}
            products={products}
            priceLists={priceLists}
            customers={customers}
            promotions={promotions}
            orders={orders}
            onUpdateProducts={(updated) => {
              setProducts(updated);
              saveProducts(updated);
            }}
            onUpdatePriceLists={(updated) => {
              setPriceLists(updated);
              savePriceLists(updated);
            }}
            onUpdateCustomers={(updated) => {
              setCustomers(updated);
              saveCustomers(updated);
            }}
            onUpdateOrders={(updated) => {
              setOrders(updated);
              saveOrders(updated);
            }}
            onUpdatePromotions={(updated) => {
              setPromotions(updated);
              savePromotions(updated);
            }}
          />
        );
      case 'INVENTORY':
        return (
          <InventoryView
            currentUser={currentUser}
            products={products}
            orders={orders}
            onUpdateProducts={(updated) => {
              setProducts(updated);
              saveProducts(updated);
            }}
          />
        );
      case 'PURCHASES':
        return (
          <PurchasesView
            currentUser={currentUser}
            purchases={purchases}
            suppliers={suppliers}
            products={products}
            onUpdatePurchases={(updated) => {
              setPurchases(updated);
              savePurchases(updated);
            }}
            onUpdateProducts={(updated) => {
              setProducts(updated);
              saveProducts(updated);
            }}
          />
        );
      case 'CUSTOMERS':
        return (
          <CustomersView
            currentUser={currentUser!}
            customers={customers}
            priceLists={priceLists}
            products={products}
            suppliers={suppliers}
            purchases={purchases}
            onUpdateCustomers={(updated) => {
              setCustomers(updated);
              saveCustomers(updated);
            }}
            onUpdateSuppliers={(updated) => {
              setSuppliers(updated);
              saveSuppliers(updated);
            }}
            onUpdatePurchases={(updated) => {
              setPurchases(updated);
              savePurchases(updated);
            }}
            onUpdateProducts={(updated) => {
              setProducts(updated);
              saveProducts(updated);
            }}
          />
        );
      case 'LOGISTICS':
        return (
          <LogisticsView
            currentUser={currentUser!}
            orders={orders}
            vehicles={vehicles}
            deliveryRoutes={deliveryRoutes}
            products={products}
            users={users}
            customers={customers}
            onUpdateOrders={(updated) => {
              setOrders(updated);
              saveOrders(updated);
            }}
            onUpdateVehicles={(updated) => {
              setVehicles(updated);
              saveVehicles(updated);
            }}
            onUpdateDeliveryRoutes={(updated) => {
              setDeliveryRoutes(updated);
              saveDeliveryRoutes(updated);
            }}
            onUpdateProducts={(updated) => {
              setProducts(updated);
              saveProducts(updated);
            }}
          />
        );
      case 'FINANCES':
        return (
          <FinancesView
            currentUser={currentUser!}
            orders={orders}
            customers={customers}
            cashMovements={cashMovements}
            customerAccounts={customerAccounts}
            customerAccountMovements={customerAccountMovements}
            electronicInvoices={electronicInvoices}
            onUpdateCashMovements={(updated) => {
              setCashMovements(updated);
              saveCashMovements(updated);
            }}
            onUpdateCustomerAccounts={(updated) => {
              setCustomerAccounts(updated);
              saveCustomerAccounts(updated);
            }}
            onUpdateCustomerAccountMovements={(updated) => {
              setCustomerAccountMovements(updated);
              saveCustomerAccountMovements(updated);
            }}
            onUpdateElectronicInvoices={(updated) => {
              setElectronicInvoices(updated);
              saveElectronicInvoices(updated);
            }}
            onUpdateOrders={(updated) => {
              setOrders(updated);
              saveOrders(updated);
            }}
          />
        );
      case 'PROFILE':
        return (
          <UserProfileView 
            currentUser={currentUser} 
            onUpdateProfile={handleUpdateProfile} 
          />
        );
      case 'USERS_LIST':
        return (
          <UsersManagementView 
            users={users} 
            onAddUser={handleAddUser} 
            onUpdateUser={handleUpdateUser} 
            onDeleteUser={handleDeleteUser} 
          />
        );
      case 'ROLES_MANAGEMENT':
        return (
          <RolesManagementView 
            permissions={permissions} 
            onUpdatePermissions={handleUpdatePermissions} 
          />
        );
      default:
        return (
          <DashboardMainView 
            currentUser={currentUser} 
            permissions={permissions} 
            products={products}
            orders={orders}
            onNavigate={(view) => handleNavigate(view as any)} 
          />
        );
    }
  };

  // VISTAS EXTERNAS (Login y Recuperación)
  if (!currentUser) {
    if (currentView === 'RECOVERY') {
      return (
        <div className="min-h-screen bg-[#F8F9FA] flex flex-col justify-between">
          <div className="flex-1">
            <RecoveryView onBackToLogin={() => setCurrentView('LOGIN')} />
          </div>
          <footer className="text-center py-6 text-sm font-bold text-[#212529] bg-[#E9ECEF] border-t-2 border-[#DEE2E6]">
            ERP Distribuidora Pigüé • Modo Accesible Activado
          </footer>
        </div>
      );
    }

    return (
      <div className="min-h-screen bg-[#F8F9FA] flex flex-col justify-between">
        <div className="flex-1">
          <LoginView 
            users={users} 
            onLoginSuccess={handleLoginSuccess} 
            onNavigateToRecovery={() => setCurrentView('RECOVERY')} 
          />
        </div>
        <footer className="text-center py-6 text-sm font-bold text-[#212529] bg-[#E9ECEF] border-t-2 border-[#DEE2E6]">
          ERP Distribuidora Pigüé • Modo Accesible Activado
        </footer>
      </div>
    );
  }

  // VISTA INTERNA ENMAQUETADA CON DISEÑO Y NAVEGACIÓN ADAPTABLE
  return (
    <DashboardLayout 
      currentUser={currentUser} 
      activeView={currentView} 
      onNavigate={(view) => handleNavigate(view as any)} 
      onLogout={handleLogout}
      permissions={permissions}
      apiConnected={apiConnected}
      onRefreshApi={fetchApiData}
    >
      {renderView()}
    </DashboardLayout>
  );
}

