/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from 'react';
import { User, Order, Vehicle, DeliveryRoute, Product, Customer } from '../types';
import { 
  Truck, 
  Plus, 
  Edit, 
  Trash2, 
  Search, 
  AlertTriangle, 
  MapPin, 
  Navigation, 
  Calendar, 
  Check, 
  X, 
  PlusCircle, 
  FileText, 
  User as UserIcon, 
  Package, 
  RefreshCw, 
  Clipboard, 
  Map, 
  Clock, 
  Smartphone, 
  RotateCcw,
  Printer,
  ChevronRight,
  Phone,
  Filter,
  CheckCircle2,
  AlertCircle,
  Ban,
  Eye
} from 'lucide-react';

interface LogisticsViewProps {
  currentUser: User;
  orders: Order[];
  vehicles: Vehicle[];
  deliveryRoutes: DeliveryRoute[];
  products: Product[];
  users: User[];
  customers: Customer[];
  onUpdateOrders: (orders: Order[]) => void;
  onUpdateVehicles: (vehicles: Vehicle[]) => void;
  onUpdateDeliveryRoutes: (routes: DeliveryRoute[]) => void;
  onUpdateProducts: (products: Product[]) => void;
}

export function LogisticsView({
  currentUser,
  orders,
  vehicles,
  deliveryRoutes,
  products,
  users,
  customers,
  onUpdateOrders,
  onUpdateVehicles,
  onUpdateDeliveryRoutes,
  onUpdateProducts
}: LogisticsViewProps) {
  // Estado para la pestaña activa (Por defecto, ENVIOS, como solicitó el usuario)
  const [activeTab, setActiveTab] = useState<'ENVIOS' | 'RUTAS' | 'VEHICULOS' | 'CHOFER'>('ENVIOS');

  // Estados de búsqueda / filtrado para la pestaña ENVIOS
  const [enviosSearchClient, setEnviosSearchClient] = useState('');
  const [enviosSearchLocation, setEnviosSearchLocation] = useState('');
  const [enviosStatusFilter, setEnviosStatusFilter] = useState('Todas');

  // Estados de búsqueda / filtrado para la pestaña RUTAS
  const [routesSearchQuery, setRoutesSearchQuery] = useState('');
  const [routesStatusFilter, setRoutesStatusFilter] = useState('Todas');

  // Estados para formularios de vehículos
  const [showVehicleForm, setShowVehicleForm] = useState(false);
  const [editingVehicle, setEditingVehicle] = useState<Vehicle | null>(null);
  const [vehicleError, setVehicleError] = useState('');

  // Estados para formularios de hojas de ruta
  const [showRouteForm, setShowRouteForm] = useState(false);
  const [routeError, setRouteError] = useState('');

  // Campos para Vehículos
  const [vPatent, setVPatent] = useState('');
  const [vModel, setVModel] = useState('');
  const [vDriverId, setVDriverId] = useState('');
  const [vCapacity, setVCapacity] = useState(1000);
  const [vStatus, setVStatus] = useState<'Disponible' | 'En Viaje' | 'En Taller'>('Disponible');

  // Campos para Hojas de Ruta
  const [rVehicleId, setRVehicleId] = useState('');
  const [rZone, setRZone] = useState('Norte');
  const [rSelectedOrderIds, setRSelectedOrderIds] = useState<string[]>([]);

  // Estados de Simulación para Chofer
  const [simulatedDriverId, setSimulatedDriverId] = useState('2'); // Carlos Gómez por defecto
  const [showOrderDetailsModal, setShowOrderDetailsModal] = useState<Order | null>(null);

  // NUEVOS MODALES DE INTERACCIÓN (Evitando prompts/confirms nativos para cumplir normas de iframe)
  const [delayModalOrder, setDelayModalOrder] = useState<Order | null>(null);
  const [delayReason, setDelayReason] = useState('');
  
  const [cancelModalOrder, setCancelModalOrder] = useState<Order | null>(null);
  const [cancelReason, setCancelReason] = useState('Local cerrado / Sin dinero');

  // Modal para ver Hoja de Carga y Remito Consolidado (Ver en pantalla)
  const [activeRouteForLoadSheet, setActiveRouteForLoadSheet] = useState<DeliveryRoute | null>(null);
  const [printSuccessMessage, setPrintSuccessMessage] = useState(false);

  // Obtener lista de choferes activos (usuarios con rol de Repartidor)
  const drivers = users.filter(u => u.role === 'Repartidor' && u.status === 'Activo');

  // --- BUSCADOR Y FILTRADO DE ENVÍOS (TAB 1) ---
  const filteredEnvios = orders.filter(o => {
    // El cliente de este envío
    const customer = customers.find(c => c.id === o.customerId);
    
    // Filtro por Cliente
    const matchesClient = o.customerName.toLowerCase().includes(enviosSearchClient.toLowerCase());
    
    // Filtro por Localidad / Dirección / Zona
    const address = customer?.address.toLowerCase() || '';
    const zone = customer?.zone.toLowerCase() || '';
    const matchesLocation = address.includes(enviosSearchLocation.toLowerCase()) || 
                            zone.includes(enviosSearchLocation.toLowerCase());
    
    // Filtro por Estado de Envío
    const matchesStatus = enviosStatusFilter === 'Todas' || o.status === enviosStatusFilter;

    return matchesClient && matchesLocation && matchesStatus;
  });

  // --- FILTRADOS DE VEHÍCULOS ---
  const filteredVehicles = vehicles.filter(v => {
    const query = routesSearchQuery.toLowerCase();
    return v.patent.toLowerCase().includes(query) || 
           v.model.toLowerCase().includes(query) ||
           v.driverName.toLowerCase().includes(query);
  });

  // --- FILTRADOS DE RUTAS ---
  const filteredRoutes = deliveryRoutes.filter(r => {
    const matchesSearch = r.driverName.toLowerCase().includes(routesSearchQuery.toLowerCase()) || 
                          r.vehicleModel.toLowerCase().includes(routesSearchQuery.toLowerCase()) ||
                          r.id.toLowerCase().includes(routesSearchQuery.toLowerCase());
    const matchesStatus = routesStatusFilter === 'Todas' || r.status === routesStatusFilter;
    return matchesSearch && matchesStatus;
  });

  // Pedidos disponibles para asignar (Pendientes o Facturados que no estén ya en otra ruta activa)
  const allRouteOrderIds = deliveryRoutes
    .filter(r => r.status !== 'Completado' && r.status !== 'Cancelado')
    .reduce((acc, r) => [...acc, ...r.orderIds], [] as string[]);

  const assignableOrders = orders.filter(o => {
    const isPendingOrInvoiced = o.status === 'Pendiente' || o.status === 'Facturado' || o.status === 'Remitido' || o.status === 'Demorado';
    const isAlreadyAssigned = allRouteOrderIds.includes(o.id);
    return isPendingOrInvoiced && !isAlreadyAssigned;
  });

  // --- ACCIONES DE VEHÍCULOS ---
  const handleOpenAddVehicle = () => {
    setEditingVehicle(null);
    setVPatent('');
    setVModel('');
    setVDriverId(drivers[0]?.id || '');
    setVCapacity(1000);
    setVStatus('Disponible');
    setVehicleError('');
    setShowVehicleForm(true);
  };

  const handleOpenEditVehicle = (vehicle: Vehicle) => {
    setEditingVehicle(vehicle);
    setVPatent(vehicle.patent);
    setVModel(vehicle.model);
    setVDriverId(vehicle.driverId);
    setVCapacity(vehicle.capacityKg);
    setVStatus(vehicle.status);
    setVehicleError('');
    setShowVehicleForm(true);
  };

  const handleSaveVehicle = (e: React.FormEvent) => {
    e.preventDefault();
    setVehicleError('');

    if (!vPatent.trim() || !vModel.trim() || !vDriverId) {
      setVehicleError('Todos los campos con asterisco (*) son obligatorios.');
      return;
    }

    const driverUser = users.find(u => u.id === vDriverId);
    if (!driverUser) return;

    let updatedVehicles: Vehicle[];
    if (editingVehicle) {
      updatedVehicles = vehicles.map(v => 
        v.id === editingVehicle.id 
          ? { 
              ...v, 
              patent: vPatent.trim().toUpperCase(), 
              model: vModel.trim(), 
              driverId: vDriverId, 
              driverName: driverUser.name, 
              capacityKg: vCapacity, 
              status: vStatus 
            }
          : v
      );
    } else {
      const newVehicle: Vehicle = {
        id: 'VEH-' + Date.now(),
        patent: vPatent.trim().toUpperCase(),
        model: vModel.trim(),
        driverId: vDriverId,
        driverName: driverUser.name,
        capacityKg: vCapacity,
        status: 'Disponible'
      };
      updatedVehicles = [...vehicles, newVehicle];
    }

    onUpdateVehicles(updatedVehicles);
    setShowVehicleForm(false);
  };

  const handleDeleteVehicle = (id: string, patent: string) => {
    if (window.confirm(`¿Seguro que desea dar de baja el vehículo con patente ${patent}?`)) {
      const updated = vehicles.filter(v => v.id !== id);
      onUpdateVehicles(updated);
    }
  };

  // --- ACCIONES DE HOJA DE RUTA ---
  const handleOpenAddRoute = () => {
    setRVehicleId(vehicles[0]?.id || '');
    setRZone('Norte');
    setRSelectedOrderIds([]);
    setRouteError('');
    setShowRouteForm(true);
  };

  const handleToggleOrderSelection = (orderId: string) => {
    if (rSelectedOrderIds.includes(orderId)) {
      setRSelectedOrderIds(rSelectedOrderIds.filter(id => id !== orderId));
    } else {
      setRSelectedOrderIds([...rSelectedOrderIds, orderId]);
    }
  };

  const handleSaveRoute = (e: React.FormEvent) => {
    e.preventDefault();
    setRouteError('');

    if (!rVehicleId) {
      setRouteError('Debe seleccionar un vehículo habilitado.');
      return;
    }

    if (rSelectedOrderIds.length === 0) {
      setRouteError('Debe asociar al menos un pedido para armar la hoja de ruta.');
      return;
    }

    const vehicle = vehicles.find(v => v.id === rVehicleId);
    if (!vehicle) return;

    const newRoute: DeliveryRoute = {
      id: 'RUT-' + (5000 + deliveryRoutes.length + 1),
      vehicleId: vehicle.id,
      vehicleModel: vehicle.model,
      vehiclePatent: vehicle.patent,
      driverId: vehicle.driverId,
      driverName: vehicle.driverName,
      orderIds: rSelectedOrderIds,
      date: new Date().toISOString().split('T')[0],
      status: 'Preparación',
      zone: rZone
    };

    // Cambiar estado de los pedidos seleccionados a 'Remitido' (En reparto)
    const updatedOrders = orders.map(o => {
      if (rSelectedOrderIds.includes(o.id)) {
        return { ...o, status: 'Remitido' as const };
      }
      return o;
    });

    const updatedRoutes = [newRoute, ...deliveryRoutes];
    
    onUpdateOrders(updatedOrders);
    onUpdateDeliveryRoutes(updatedRoutes);
    
    localStorage.setItem('erp_distribuidora_routes', JSON.stringify(updatedRoutes));
    localStorage.setItem('erp_distribuidora_orders', JSON.stringify(updatedOrders));

    setShowRouteForm(false);
  };

  const handleStartRoute = (routeId: string) => {
    const updatedRoutes = deliveryRoutes.map(r => {
      if (r.id === routeId) {
        // Cambiar estado del vehículo
        const updatedVehicles = vehicles.map(v => 
          v.id === r.vehicleId ? { ...v, status: 'En Viaje' as const } : v
        );
        onUpdateVehicles(updatedVehicles);

        return { ...r, status: 'En Tránsito' as const };
      }
      return r;
    });

    onUpdateDeliveryRoutes(updatedRoutes);
    localStorage.setItem('erp_distribuidora_routes', JSON.stringify(updatedRoutes));

    const route = deliveryRoutes.find(r => r.id === routeId);
    if (route) {
      const updatedOrders = orders.map(o => {
        if (route.orderIds.includes(o.id)) {
          return { ...o, status: 'Remitido' as const };
        }
        return o;
      });
      onUpdateOrders(updatedOrders);
      localStorage.setItem('erp_distribuidora_orders', JSON.stringify(updatedOrders));
    }
  };

  const handleCancelRoute = (routeId: string) => {
    if (!window.confirm('¿Desea cancelar esta hoja de ruta? Los pedidos volverán a estar pendientes para despacho.')) {
      return;
    }

    const route = deliveryRoutes.find(r => r.id === routeId);
    if (!route) return;

    // Cambiar estado de los pedidos devueltos a 'Pendiente'
    const updatedOrders = orders.map(o => {
      if (route.orderIds.includes(o.id)) {
        return { ...o, status: 'Pendiente' as const };
      }
      return o;
    });

    // Cambiar estado del vehículo
    const updatedVehicles = vehicles.map(v => 
      v.id === route.vehicleId ? { ...v, status: 'Disponible' as const } : v
    );

    const updatedRoutes = deliveryRoutes.map(r => {
      if (r.id === routeId) {
        return { ...r, status: 'Cancelado' as const };
      }
      return r;
    });

    onUpdateOrders(updatedOrders);
    onUpdateVehicles(updatedVehicles);
    onUpdateDeliveryRoutes(updatedRoutes);

    localStorage.setItem('erp_distribuidora_routes', JSON.stringify(updatedRoutes));
    localStorage.setItem('erp_distribuidora_orders', JSON.stringify(updatedOrders));
  };

  // --- ACCIONES DEL CHOFER EN SU APP MÓVIL SIMULADA ---
  const activeDriverRoute = deliveryRoutes.find(
    r => r.driverId === simulatedDriverId && r.status === 'En Tránsito'
  );

  const handleDeliverOrder = (orderId: string) => {
    const updatedOrders = orders.map(o => {
      if (o.id === orderId) {
        return { ...o, status: 'Entregado' as const };
      }
      return o;
    });

    onUpdateOrders(updatedOrders);
    localStorage.setItem('erp_distribuidora_orders', JSON.stringify(updatedOrders));

    // Verificar si se completaron todas las entregas de la ruta activa
    checkAndCompleteRoute(activeDriverRoute, updatedOrders);
  };

  // Se abre modal para retrasar
  const handleOpenDelayModal = (order: Order) => {
    setDelayModalOrder(order);
    setDelayReason('');
  };

  const handleConfirmDelay = () => {
    if (!delayModalOrder) return;
    const notesWithDelay = `${delayModalOrder.notes || ''} | ⚠️ DEMORADO: ${delayReason || 'Sin motivo especificado'}`.trim();
    
    // Buscar pedidos subsecuentes en la hoja de ruta activa
    const subsequentIds: string[] = [];
    if (activeDriverRoute) {
      const idx = activeDriverRoute.orderIds.indexOf(delayModalOrder.id);
      if (idx !== -1) {
        for (let i = idx + 1; i < activeDriverRoute.orderIds.length; i++) {
          subsequentIds.push(activeDriverRoute.orderIds[i]);
        }
      }
    }
    
    const updatedOrders = orders.map(o => {
      if (o.id === delayModalOrder.id) {
        return { ...o, status: 'Demorado' as const, notes: notesWithDelay };
      }
      // Si el pedido es subsecuente y está en estado 'Remitido' (pendiente de entrega en camión)
      if (subsequentIds.includes(o.id) && o.status === 'Remitido') {
        const autoNotes = `${o.notes || ''} | ⚠️ DEMORADO AUTOMÁTICAMENTE: Consecuencia de demora en parada anterior ${delayModalOrder.id}`.trim();
        return { ...o, status: 'Demorado' as const, notes: autoNotes };
      }
      return o;
    });

    onUpdateOrders(updatedOrders);
    localStorage.setItem('erp_distribuidora_orders', JSON.stringify(updatedOrders));
    setDelayModalOrder(null);

    // Si todo el resto fue procesado, ver si completamos ruta
    checkAndCompleteRoute(activeDriverRoute, updatedOrders);
  };

  // Se abre modal para cancelar / rechazar devolución
  const handleOpenCancelModal = (order: Order) => {
    setCancelModalOrder(order);
    setCancelReason('Local cerrado / Sin dinero');
  };

  const handleConfirmCancelAndReturnStock = () => {
    if (!cancelModalOrder) return;

    const finalNotes = `${cancelModalOrder.notes || ''} | 🚫 RECHAZADO: ${cancelReason}`.trim();

    // Actualizar pedido a Cancelado
    const updatedOrders = orders.map(o => {
      if (o.id === cancelModalOrder.id) {
        return { ...o, status: 'Cancelado' as const, notes: finalNotes };
      }
      return o;
    });

    // Reingresar el stock físico de los productos rechazados automáticamente al inventario
    const updatedProducts = products.map(p => {
      const item = cancelModalOrder.items.find(i => i.productId === p.id);
      if (item) {
        return { ...p, stock: p.stock + item.quantity };
      }
      return p;
    });
    onUpdateProducts(updatedProducts);
    localStorage.setItem('erp_distribuidora_products', JSON.stringify(updatedProducts));

    // Registrar movimiento de stock
    const savedMovementsStr = localStorage.getItem('erp_distribuidora_stock_movements');
    let movementsList = [];
    if (savedMovementsStr) {
      try { movementsList = JSON.parse(savedMovementsStr); } catch (e) { console.error(e); }
    }
    const newMovements = cancelModalOrder.items.map(item => ({
      id: 'MV-' + Date.now() + '-' + Math.random().toString(36).substr(2, 4),
      productId: item.productId,
      productName: item.productName,
      type: 'Entrada' as const,
      quantity: item.quantity,
      reason: `Rechazo / Devolución de Pedido ${cancelModalOrder.id} (${cancelReason})`,
      date: new Date().toISOString().replace('T', ' ').substring(0, 16),
      user: activeDriverRoute?.driverName || currentUser.name
    }));
    localStorage.setItem('erp_distribuidora_stock_movements', JSON.stringify([...newMovements, ...movementsList]));

    onUpdateOrders(updatedOrders);
    localStorage.setItem('erp_distribuidora_orders', JSON.stringify(updatedOrders));
    setCancelModalOrder(null);

    // Verificar si completó todas las entregas de la ruta
    checkAndCompleteRoute(activeDriverRoute, updatedOrders);
  };

  const checkAndCompleteRoute = (route: DeliveryRoute | undefined, currentOrdersList: Order[]) => {
    if (!route) return;

    // Buscar si queda algún pedido de la ruta en estado 'Remitido' o 'Demorado' (estados no finales)
    const routeOrders = currentOrdersList.filter(o => route.orderIds.includes(o.id));
    // Se considera completada automáticamente si todas las paradas fueron procesadas en estados finales (Entregado o Cancelado)
    const pendingDeliveries = routeOrders.filter(o => o.status === 'Remitido' || o.status === 'Demorado');

    if (pendingDeliveries.length === 0) {
      // Completar ruta automáticamente
      const updatedRoutes = deliveryRoutes.map(r => {
        if (r.id === route.id) {
          return { ...r, status: 'Completado' as const };
        }
        return r;
      });

      // Liberar vehículo
      const updatedVehicles = vehicles.map(v => 
        v.id === route.vehicleId ? { ...v, status: 'Disponible' as const } : v
      );

      onUpdateDeliveryRoutes(updatedRoutes);
      onUpdateVehicles(updatedVehicles);
      
      localStorage.setItem('erp_distribuidora_routes', JSON.stringify(updatedRoutes));
      
      alert(`¡Ruta Procesada! Todas las paradas de la Hoja de Ruta ${route.id} se han registrado. Ha quedado archivada como COMPLETADA.`);
    }
  };

  const handleForceCompleteRoute = (route: DeliveryRoute) => {
    const routeOrders = orders.filter(o => route.orderIds.includes(o.id));
    const hasPending = routeOrders.some(o => o.status === 'Remitido');
    const hasDelayed = routeOrders.some(o => o.status === 'Demorado');

    let confirmMsg = '¿Desea finalizar y rendir esta hoja de ruta?';
    if (hasDelayed) {
      confirmMsg += '\n\nLos pedidos que quedaron "Demorados" se restablecerán automáticamente a "Facturados" para que los puedas volver a programar en un nuevo reparto.';
    }
    if (hasPending) {
      confirmMsg += '\n\n⚠️ ¡Atención! Hay pedidos que aún figuran "En Camión" (sin entregar/rechazar). Si finaliza la ruta, se restablecerán a "Facturados" para ser reprogramados.';
    }

    if (!window.confirm(confirmMsg)) {
      return;
    }

    // Liberar pedidos que no fueron resueltos (Remitidos o Demorados) volviéndolos a Facturado
    const updatedOrders = orders.map(o => {
      if (route.orderIds.includes(o.id)) {
        if (o.status === 'Remitido' || o.status === 'Demorado') {
          return { ...o, status: 'Facturado' as const, notes: `${o.notes || ''} | 🔄 Devuelto al depósito desde ruta ${route.id}`.trim() };
        }
      }
      return o;
    });

    const updatedRoutes = deliveryRoutes.map(r => {
      if (r.id === route.id) {
        return { ...r, status: 'Completado' as const };
      }
      return r;
    });

    const updatedVehicles = vehicles.map(v => 
      v.id === route.vehicleId ? { ...v, status: 'Disponible' as const } : v
    );

    onUpdateOrders(updatedOrders);
    onUpdateDeliveryRoutes(updatedRoutes);
    onUpdateVehicles(updatedVehicles);

    localStorage.setItem('erp_distribuidora_orders', JSON.stringify(updatedOrders));
    localStorage.setItem('erp_distribuidora_routes', JSON.stringify(updatedRoutes));
    localStorage.setItem('erp_distribuidora_vehicles', JSON.stringify(updatedVehicles));
  };

  const handleReprogramOrder = (order: Order) => {
    if (!window.confirm(`¿Desea reprogramar el pedido cancelado/rechazado ${order.id}?\n\nAl hacerlo, volverá a estar disponible para reparto y se volverá a descontar la mercadería correspondiente del stock físico del inventario.`)) {
      return;
    }

    // Cambiar estado a 'Facturado' para que vuelva a estar disponible para despachar
    const updatedOrders = orders.map(o => {
      if (o.id === order.id) {
        return { ...o, status: 'Facturado' as const, notes: `${o.notes || ''} | 🔄 REPROGRAMADO PARA NUEVO ENVÍO`.trim() };
      }
      return o;
    });

    // Descontar del stock físico los productos del pedido nuevamente
    const updatedProducts = products.map(p => {
      const item = order.items.find(i => i.productId === p.id);
      if (item) {
        return { ...p, stock: Math.max(0, p.stock - item.quantity) };
      }
      return p;
    });

    onUpdateProducts(updatedProducts);
    localStorage.setItem('erp_distribuidora_products', JSON.stringify(updatedProducts));

    // Registrar movimiento de stock de Salida por reprogramación
    const savedMovementsStr = localStorage.getItem('erp_distribuidora_stock_movements');
    let movementsList = [];
    if (savedMovementsStr) {
      try { movementsList = JSON.parse(savedMovementsStr); } catch (e) { console.error(e); }
    }
    const newMovements = order.items.map(item => ({
      id: 'MV-' + Date.now() + '-' + Math.random().toString(36).substr(2, 4),
      productId: item.productId,
      productName: item.productName,
      type: 'Salida' as const,
      quantity: item.quantity,
      reason: `Reprogramación / Nuevo envío de Pedido ${order.id}`,
      date: new Date().toISOString().replace('T', ' ').substring(0, 16),
      user: currentUser.name
    }));
    localStorage.setItem('erp_distribuidora_stock_movements', JSON.stringify([...newMovements, ...movementsList]));

    onUpdateOrders(updatedOrders);
    localStorage.setItem('erp_distribuidora_orders', JSON.stringify(updatedOrders));

    alert(`El pedido ${order.id} ha vuelto al listado de pendientes de despacho como FACTURADO. La mercadería fue vuelta a descontar del stock.`);
  };

  // --- COMPILACIÓN DE HOJA DE CARGA CONSOLIDADA ---
  const getConsolidatedCargo = (route: DeliveryRoute) => {
    const cargoMap: { [productId: string]: { productName: string, totalQty: number } } = {};
    
    route.orderIds.forEach(orderId => {
      const order = orders.find(o => o.id === orderId);
      if (order) {
        order.items.forEach(item => {
          if (cargoMap[item.productId]) {
            cargoMap[item.productId].totalQty += item.quantity;
          } else {
            cargoMap[item.productId] = {
              productName: item.productName,
              totalQty: item.quantity
            };
          }
        });
      }
    });

    return Object.values(cargoMap);
  };

  return (
    <div className="space-y-8 animate-in fade-in duration-300 px-1">
      
      {/* HEADER DE SECCIÓN */}
      <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b-4 border-slate-200 pb-5">
        <div>
          <h1 className="text-3xl md:text-4xl font-black text-slate-800 tracking-tight flex items-center gap-3">
            <Truck className="w-10 h-10 text-blue-600" />
            <span>Distribución, Logística y Envíos</span>
          </h1>
          <p className="text-sm md:text-base text-slate-500 font-bold mt-1">
            Visualización general de todos los envíos con filtros rápidos, armado de hojas de ruta, hoja de carga consolidada para carga de camiones, y aplicación simulada para el repartidor.
          </p>
        </div>
        
        {/* BOTONES DE ACCIONES RÁPIDAS */}
        <div className="flex gap-2">
          {activeTab === 'RUTAS' && !showRouteForm && (
            <button
              onClick={handleOpenAddRoute}
              className="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-sm rounded-xl flex items-center gap-2 cursor-pointer shadow-md transition-all active:scale-95"
            >
              <Plus className="w-5 h-5" />
              <span>Armar Hoja de Ruta</span>
            </button>
          )}

          {activeTab === 'VEHICULOS' && !showVehicleForm && (
            <button
              onClick={handleOpenAddVehicle}
              className="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm rounded-xl flex items-center gap-2 cursor-pointer shadow-md transition-all active:scale-95"
            >
              <Plus className="w-5 h-5" />
              <span>Alta de Vehículo</span>
            </button>
          )}
        </div>
      </div>

      {/* PESTAÑAS PRINCIPALES COMPACTAS */}
      <div className="flex flex-wrap md:flex-nowrap border border-slate-200/80 p-1 bg-slate-50 rounded-2xl gap-1">
        <button
          onClick={() => setActiveTab('ENVIOS')}
          className={`flex-1 py-3 px-4 rounded-xl font-black text-xs md:text-sm text-center flex items-center justify-center gap-2 border cursor-pointer transition-all ${
            activeTab === 'ENVIOS'
              ? 'bg-blue-600 border-blue-500 text-white shadow-md'
              : 'bg-white hover:bg-slate-100 text-slate-600 border-slate-200'
          }`}
        >
          <Clipboard className="w-4 h-4" />
          <span>Todos los Envíos ({orders.length})</span>
        </button>

        <button
          onClick={() => setActiveTab('RUTAS')}
          className={`flex-1 py-3 px-4 rounded-xl font-black text-xs md:text-sm text-center flex items-center justify-center gap-2 border cursor-pointer transition-all ${
            activeTab === 'RUTAS'
              ? 'bg-emerald-600 border-emerald-500 text-white shadow-md'
              : 'bg-white hover:bg-slate-100 text-slate-600 border-slate-200'
          }`}
        >
          <Map className="w-4 h-4" />
          <span>Hojas de Ruta ({deliveryRoutes.length})</span>
        </button>

        <button
          onClick={() => setActiveTab('VEHICULOS')}
          className={`flex-1 py-3 px-4 rounded-xl font-black text-xs md:text-sm text-center flex items-center justify-center gap-2 border cursor-pointer transition-all ${
            activeTab === 'VEHICULOS'
              ? 'bg-indigo-600 border-indigo-500 text-white shadow-md'
              : 'bg-white hover:bg-slate-100 text-slate-600 border-slate-200'
          }`}
        >
          <Truck className="w-4 h-4" />
          <span>Flota de Camiones ({vehicles.length})</span>
        </button>

        <button
          onClick={() => setActiveTab('CHOFER')}
          className={`flex-1 py-3 px-4 rounded-xl font-black text-xs md:text-sm text-center flex items-center justify-center gap-2 border cursor-pointer transition-all ${
            activeTab === 'CHOFER'
              ? 'bg-purple-600 border-purple-500 text-white shadow-md'
              : 'bg-white hover:bg-slate-100 text-slate-600 border-slate-200'
          }`}
        >
          <Smartphone className="w-4 h-4" />
          <span>📱 App Repartidor</span>
        </button>
      </div>

      {/* ========================================== */}
      {/* 1. CONTENIDO PESTAÑA: TODOS LOS ENVÍOS     */}
      {/* ========================================== */}
      {activeTab === 'ENVIOS' && (
        <div className="space-y-6">
          
          {/* TARJETAS DE MÉTRICAS RÁPIDAS DE ENVÍO */}
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div className="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm text-center">
              <span className="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest block">Total Envíos</span>
              <span className="text-3xl font-black text-slate-800 block mt-1">{orders.length}</span>
            </div>
            <div className="bg-amber-50/50 border border-amber-100 p-4 rounded-2xl shadow-sm text-center">
              <span className="text-[10px] font-extrabold text-amber-500 uppercase tracking-widest block">Pendientes</span>
              <span className="text-3xl font-black text-amber-600 block mt-1">
                {orders.filter(o => o.status === 'Pendiente' || o.status === 'Facturado').length}
              </span>
            </div>
            <div className="bg-blue-50/50 border border-blue-100 p-4 rounded-2xl shadow-sm text-center">
              <span className="text-[10px] font-extrabold text-blue-500 uppercase tracking-widest block">En Camión</span>
              <span className="text-3xl font-black text-blue-600 block mt-1">
                {orders.filter(o => o.status === 'Remitido').length}
              </span>
            </div>
            <div className="bg-emerald-50/50 border border-emerald-100 p-4 rounded-2xl shadow-sm text-center">
              <span className="text-[10px] font-extrabold text-emerald-500 uppercase tracking-widest block">Entregados</span>
              <span className="text-3xl font-black text-emerald-600 block mt-1">
                {orders.filter(o => o.status === 'Entregado').length}
              </span>
            </div>
            <div className="bg-orange-50/50 border border-orange-100 p-4 rounded-2xl shadow-sm text-center">
              <span className="text-[10px] font-extrabold text-orange-500 uppercase tracking-widest block">Demorados</span>
              <span className="text-3xl font-black text-orange-600 block mt-1">
                {orders.filter(o => o.status === 'Demorado').length}
              </span>
            </div>
            <div className="bg-red-50/50 border border-red-100 p-4 rounded-2xl shadow-sm text-center">
              <span className="text-[10px] font-extrabold text-red-500 uppercase tracking-widest block">Cancelados</span>
              <span className="text-3xl font-black text-red-600 block mt-1">
                {orders.filter(o => o.status === 'Cancelado').length}
              </span>
            </div>
          </div>

          {/* PANEL DE FILTROS BUSCADORES */}
          <div className="bg-slate-50 border border-slate-200 p-5 rounded-2xl shadow-[inset_1px_1px_3px_rgba(255,255,255,0.8)]">
            <h3 className="text-sm font-extrabold text-slate-700 uppercase tracking-widest flex items-center gap-2 mb-4">
              <Filter className="w-4 h-4 text-blue-600" />
              <span>Buscador Avanzado de Envíos</span>
            </h3>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              {/* Filtro Cliente */}
              <div>
                <label className="block text-xs font-black text-slate-600 mb-1">Cliente / Comercio:</label>
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-slate-400" />
                  <input
                    type="text"
                    placeholder="Escriba el comercio o cliente..."
                    value={enviosSearchClient}
                    onChange={(e) => setEnviosSearchClient(e.target.value)}
                    className="w-full bg-white border border-slate-200 rounded-xl py-2.5 pl-9 pr-4 text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                  />
                </div>
              </div>

              {/* Filtro Localidad / Dirección / Zona */}
              <div>
                <label className="block text-xs font-black text-slate-600 mb-1">Localidad, Dirección o Zona:</label>
                <div className="relative">
                  <MapPin className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-slate-400" />
                  <input
                    type="text"
                    placeholder="Ej. Centro, Calle, Av., Norte..."
                    value={enviosSearchLocation}
                    onChange={(e) => setEnviosSearchLocation(e.target.value)}
                    className="w-full bg-white border border-slate-200 rounded-xl py-2.5 pl-9 pr-4 text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                  />
                </div>
              </div>

              {/* Filtro Estado */}
              <div>
                <label className="block text-xs font-black text-slate-600 mb-1">Estado del Envío:</label>
                <select
                  value={enviosStatusFilter}
                  onChange={(e) => setEnviosStatusFilter(e.target.value)}
                  className="w-full bg-white border border-slate-200 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                >
                  <option value="Todas">Todos los Estados</option>
                  <option value="Pendiente">Pendiente Despacho (Solo Facturado / Tomado)</option>
                  <option value="Remitido">En Reparto (Con chofer)</option>
                  <option value="Entregado">✓ Entregado</option>
                  <option value="Demorado">⚠️ Demorado</option>
                  <option value="Cancelado">🚫 Rechazado / Cancelado</option>
                </select>
              </div>
            </div>
          </div>

          {/* LISTA / TABLA DE ENVÍOS */}
          <div className="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
            <div className="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
              <h3 className="text-base font-black text-slate-800">Envíos Registrados en Sistema ({filteredEnvios.length})</h3>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-slate-100/70 border-b border-slate-200 text-slate-600 font-extrabold text-xs uppercase">
                    <th className="p-4">Pedido ID</th>
                    <th className="p-4">Cliente / Comercio</th>
                    <th className="p-4">Dirección y Localidad</th>
                    <th className="p-4 text-center">Estado Envío</th>
                    <th className="p-4">Hoja de Ruta</th>
                    <th className="p-4 text-center">Bultos</th>
                    <th className="p-4 text-right">Monto</th>
                    <th className="p-4 text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 font-bold text-sm">
                  {filteredEnvios.length === 0 ? (
                    <tr>
                      <td colSpan={8} className="p-12 text-center text-slate-400">
                        No se encontraron envíos que coincidan con los filtros aplicados.
                      </td>
                    </tr>
                  ) : (
                    filteredEnvios.map(o => {
                      const customer = customers.find(c => c.id === o.customerId);
                      const isAssigned = deliveryRoutes.find(r => r.orderIds.includes(o.id) && r.status !== 'Cancelado');
                      const totalBultos = o.items.reduce((acc, item) => acc + item.quantity, 0);

                      // Badges de estado de envío
                      const badgeStyles: { [key: string]: string } = {
                        'Pendiente': 'bg-slate-100 text-slate-600 border-slate-200',
                        'Facturado': 'bg-slate-100 text-slate-600 border-slate-200',
                        'Remitido': 'bg-blue-50 text-blue-600 border-blue-200',
                        'Entregado': 'bg-emerald-50 text-emerald-600 border-emerald-200',
                        'Demorado': 'bg-orange-50 text-orange-600 border-orange-200',
                        'Cancelado': 'bg-red-50 text-red-600 border-red-200'
                      };

                      const badgeLabels: { [key: string]: string } = {
                        'Pendiente': 'Pendiente Despacho',
                        'Facturado': 'Pendiente Despacho',
                        'Remitido': 'En Viaje',
                        'Entregado': '✓ Entregado',
                        'Demorado': '⚠️ Demorado',
                        'Cancelado': '🚫 Rechazado'
                      };

                      return (
                        <tr key={o.id} className="hover:bg-slate-50 transition-colors">
                          <td className="p-4 font-mono font-black text-slate-400 text-xs">{o.id}</td>
                          <td className="p-4 text-slate-900">{o.customerName}</td>
                          <td className="p-4 text-xs text-slate-500">
                            <div>{customer?.address || 'Sin dirección cargada'}</div>
                            <div className="text-[10px] text-slate-400 mt-0.5 uppercase tracking-wider font-extrabold">{customer?.zone || 'Sin zona'}</div>
                          </td>
                          <td className="p-4 text-center">
                            <span className={`px-2.5 py-1 text-xs rounded-full border ${badgeStyles[o.status] || 'bg-slate-100 text-slate-600'}`}>
                              {badgeLabels[o.status] || o.status}
                            </span>
                          </td>
                          <td className="p-4">
                            {isAssigned ? (
                              <button
                                onClick={() => {
                                  setRoutesSearchQuery(isAssigned.id);
                                  setRoutesStatusFilter('Todas');
                                  setActiveTab('RUTAS');
                                }}
                                className="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-1 rounded-lg border flex items-center gap-1 font-extrabold cursor-pointer transition-all"
                              >
                                <Map className="w-3.5 h-3.5 text-blue-600" />
                                <span>{isAssigned.id}</span>
                              </button>
                            ) : (
                              <span className="text-xs text-slate-400">No Asignado</span>
                            )}
                          </td>
                          <td className="p-4 text-center font-semibold text-slate-600">{totalBultos} bultos</td>
                          <td className="p-4 text-right font-black font-mono text-slate-800">${o.total.toLocaleString('es-AR')}</td>
                          <td className="p-4 text-center">
                            <div className="flex items-center justify-center gap-1.5">
                              {/* Botón ver detalles */}
                              <button
                                onClick={() => setShowOrderDetailsModal(o)}
                                className="p-1.5 bg-slate-50 hover:bg-slate-100 text-slate-600 hover:text-slate-800 rounded-lg border border-slate-200 cursor-pointer transition-all"
                                title="Ver mercadería del envío"
                              >
                                <Eye className="w-4 h-4" />
                              </button>
                              
                              {/* Botón contactar cliente */}
                              {customer?.phone ? (
                                <a
                                  href={`tel:${customer.phone}`}
                                  className="p-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 hover:text-blue-700 rounded-lg border border-blue-200 cursor-pointer transition-all flex items-center justify-center"
                                  title={`Llamar a ${o.customerName}: ${customer.phone}`}
                                >
                                  <Phone className="w-4 h-4" />
                                </a>
                              ) : (
                                <button
                                  disabled
                                  className="p-1.5 bg-slate-50 text-slate-300 rounded-lg border border-slate-100 cursor-not-allowed"
                                  title="No se registró teléfono"
                                >
                                  <Phone className="w-4 h-4" />
                                </button>
                              )}

                              {/* Botón reprogramar pedido cancelado/devuelto */}
                              {o.status === 'Cancelado' && (
                                <button
                                  onClick={() => handleReprogramOrder(o)}
                                  className="p-1.5 bg-amber-50 hover:bg-amber-100 text-amber-600 hover:text-amber-700 rounded-lg border border-amber-200 cursor-pointer transition-all flex items-center justify-center"
                                  title="Reprogramar / Volver a habilitar envío"
                                >
                                  <RotateCcw className="w-4 h-4" />
                                </button>
                              )}
                            </div>
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>
          </div>

        </div>
      )}

      {/* ========================================== */}
      {/* 2. CONTENIDO PESTAÑA: HOJAS DE RUTA        */}
      {/* ========================================== */}
      {activeTab === 'RUTAS' && !showRouteForm && (
        <div className="space-y-6">
          
          {/* BUSCADOR DE HOJAS DE RUTA */}
          <div className="bg-slate-50 border border-slate-200 rounded-2xl p-5 space-y-4 shadow-[inset_1px_1px_3px_rgba(255,255,255,0.8)]">
            <div className="flex items-center gap-2 font-black text-sm text-slate-700 uppercase tracking-widest">
              <Search className="w-4 h-4 text-blue-600" />
              <span>Buscador de Recorridos / Hojas de Ruta</span>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
              <div className="md:col-span-8">
                <label className="block text-xs font-black text-slate-600 mb-1">Buscar Chofer, Unidad o ID de Ruta:</label>
                <input
                  type="text"
                  placeholder="Escriba el chofer, marca de móvil, patente, RUT-500..."
                  value={routesSearchQuery}
                  onChange={(e) => setRoutesSearchQuery(e.target.value)}
                  className="w-full border border-slate-200 bg-white rounded-xl p-2.5 text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                />
              </div>

              <div className="md:col-span-4">
                <label className="block text-xs font-black text-slate-600 mb-1">Filtrar Estado de Ruta:</label>
                <select
                  value={routesStatusFilter}
                  onChange={(e) => setRoutesStatusFilter(e.target.value)}
                  className="w-full border border-slate-200 bg-white rounded-xl p-2.5 text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                >
                  <option value="Todas">Todos los Estados</option>
                  <option value="Preparación">En Preparación (Borrador)</option>
                  <option value="En Tránsito">En Tránsito (Repartiendo)</option>
                  <option value="Completado">✓ Completadas / Rendidas</option>
                  <option value="Cancelado">🚫 Canceladas / Rechazadas</option>
                </select>
              </div>
            </div>
          </div>

          {/* LISTA DE RUTAS */}
          <div className="bg-white border border-slate-200 rounded-2xl p-6 space-y-6 shadow-sm">
            <h2 className="text-xl font-black text-slate-800 border-b pb-3">Hojas de Ruta Programadas</h2>

            {filteredRoutes.length === 0 ? (
              <div className="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                <Map className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                <p className="text-base font-black text-slate-700">No se encontraron hojas de ruta.</p>
                <p className="text-xs text-slate-500 font-bold mt-1">Haga clic en "Armar Hoja de Ruta" en la parte superior derecha.</p>
              </div>
            ) : (
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {filteredRoutes.map(route => {
                  const statusColors = {
                    'Preparación': 'bg-amber-50 border-amber-200 text-amber-700',
                    'En Tránsito': 'bg-blue-50 border-blue-200 text-blue-700',
                    'Completado': 'bg-emerald-50 border-emerald-200 text-emerald-700',
                    'Cancelado': 'bg-red-50 border-red-200 text-red-700'
                  };

                  const statusLabels = {
                    'Preparación': 'Preparación / Carga',
                    'En Tránsito': '🚚 En Tránsito / Reparto',
                    'Completado': '✓ Finalizada y Rendida',
                    'Cancelado': '🚫 Cancelada / Devuelta'
                  };

                  return (
                    <div key={route.id} className="border border-slate-200 hover:border-blue-500/60 rounded-2xl p-5 bg-white flex flex-col justify-between gap-5 transition-all shadow-sm hover:shadow-md">
                      <div className="space-y-4">
                        <div className="flex justify-between items-start gap-2">
                          <div>
                            <span className="font-mono text-[9px] font-black text-slate-400 uppercase tracking-widest">RECORRIDO DISTRIBUCIÓN</span>
                            <h4 className="text-lg font-black text-slate-800">{route.id} - Zona {route.zone}</h4>
                          </div>
                          <span className={`px-2.5 py-0.5 rounded-full text-xs font-black border ${statusColors[route.status]}`}>
                            {statusLabels[route.status]}
                          </span>
                        </div>

                        <div className="grid grid-cols-2 gap-3 bg-slate-50 p-3 rounded-xl border border-slate-100">
                          <div>
                            <span className="text-[9px] font-black text-slate-400 block uppercase">REPARTIDOR</span>
                            <span className="text-xs font-black text-slate-700">{route.driverName}</span>
                          </div>
                          <div>
                            <span className="text-[9px] font-black text-slate-400 block uppercase">CAMIÓN / PATENTE</span>
                            <span className="text-xs font-black text-slate-700">{route.vehicleModel} ({route.vehiclePatent})</span>
                          </div>
                        </div>

                        {/* Paradas asociadas */}
                        <div className="space-y-2">
                          <span className="text-xs font-black text-slate-500 block uppercase tracking-wider">Paradas / Comercios a Entregar ({route.orderIds.length}):</span>
                          <div className="space-y-1 max-h-44 overflow-y-auto pr-1">
                            {route.orderIds.map((orderId, idx) => {
                              const order = orders.find(o => o.id === orderId);
                              const cust = order ? customers.find(c => c.id === order.customerId) : null;
                              return (
                                <div key={orderId} className="flex justify-between items-center text-xs p-2 bg-slate-50 rounded-lg border border-slate-100 font-bold">
                                  <div className="flex items-center gap-1.5">
                                    <span className="text-[10px] text-slate-400 font-mono">#{idx+1}</span>
                                    <span className="text-slate-800">{order?.customerName || 'Cliente'}</span>
                                  </div>
                                  <div className="flex items-center gap-2 shrink-0">
                                    <span className="text-[10px] text-slate-400 font-mono">({cust?.zone || 'Sin zona'})</span>
                                    <span className={`text-[9px] font-black uppercase px-1.5 py-0.5 rounded ${
                                      order?.status === 'Entregado' ? 'bg-green-100 text-green-800' :
                                      order?.status === 'Cancelado' ? 'bg-red-100 text-red-800' :
                                      order?.status === 'Demorado' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800'
                                    }`}>
                                      {order?.status || 'Pendiente'}
                                    </span>
                                  </div>
                                </div>
                              );
                            })}
                          </div>
                        </div>
                      </div>

                      {/* Botonera de acciones para la Hoja de Ruta */}
                      <div className="flex flex-wrap gap-2 pt-3 border-t border-slate-100">
                        {route.status === 'Preparación' && (
                          <>
                            <button
                              onClick={() => handleStartRoute(route.id)}
                              className="flex-1 py-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl cursor-pointer shadow-sm flex items-center justify-center gap-1.5 transition-all"
                            >
                              <Truck className="w-4 h-4" />
                              <span>Despachar / Salir</span>
                            </button>
                            <button
                              onClick={() => handleCancelRoute(route.id)}
                              className="p-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl cursor-pointer transition-all"
                              title="Cancelar Hoja de Ruta"
                            >
                              <X className="w-4.5 h-4.5" />
                            </button>
                          </>
                        )}

                        {route.status === 'En Tránsito' && (
                          <>
                            <button
                              onClick={() => {
                                setSimulatedDriverId(route.driverId);
                                setActiveTab('CHOFER');
                              }}
                              className="flex-1 py-2 bg-purple-600 hover:bg-purple-700 text-white font-extrabold text-xs rounded-xl cursor-pointer shadow-sm flex items-center justify-center gap-1.5 transition-all"
                            >
                              <Smartphone className="w-4 h-4" />
                              <span>Ver en Celular Chofer</span>
                            </button>
                            <button
                              onClick={() => handleForceCompleteRoute(route)}
                              className="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl cursor-pointer shadow-sm flex items-center justify-center gap-1 transition-all"
                              title="Finalizar y rendir recorrido de reparto"
                            >
                              <Check className="w-3.5 h-3.5" />
                              <span>Rendir</span>
                            </button>
                          </>
                        )}

                        {/* HOJA DE CARGA FUNCIONAL (Ver en pantalla) */}
                        <button
                          onClick={() => {
                            setActiveRouteForLoadSheet(route);
                            setPrintSuccessMessage(false);
                          }}
                          className="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-800 font-bold text-xs rounded-xl cursor-pointer flex items-center gap-1.5 transition-all"
                        >
                          <Printer className="w-4 h-4 text-slate-600" />
                          <span>Ver Hoja de Carga (Papel)</span>
                        </button>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      )}

      {/* ========================================== */}
      {/* 2.1 FORMULARIO ARMAR HOJA DE RUTA          */}
      {/* ========================================== */}
      {showRouteForm && activeTab === 'RUTAS' && (
        <div className="bg-white border border-emerald-500 rounded-2xl p-6 space-y-6 shadow-md animate-in slide-in-from-top-4 duration-300">
          <div className="flex items-center justify-between border-b pb-4">
            <h3 className="text-xl font-black text-slate-800 flex items-center gap-2">
              <Map className="w-5 h-5 text-emerald-600" />
              <span>Programar Recorrido (Nueva Hoja de Ruta)</span>
            </h3>
            <button onClick={() => setShowRouteForm(false)} className="p-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl cursor-pointer">
              <X className="w-5 h-5" />
            </button>
          </div>

          {routeError && (
            <div className="p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl font-bold flex items-center gap-2">
              <AlertTriangle className="w-5 h-5 text-red-500" />
              <span>{routeError}</span>
            </div>
          )}

          <form onSubmit={handleSaveRoute} className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-black text-slate-600 mb-1">Móvil asignado con su Chofer habitual:</label>
                <select
                  value={rVehicleId}
                  onChange={(e) => setRVehicleId(e.target.value)}
                  className="w-full border border-slate-200 bg-white rounded-xl p-3 text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                >
                  {vehicles.filter(v => v.status !== 'En Taller').map(v => (
                    <option key={v.id} value={v.id}>{v.model} ({v.patent}) - Chofer: {v.driverName}</option>
                  ))}
                  {vehicles.length === 0 && (
                    <option value="">No hay vehículos cargados en el sistema</option>
                  )}
                </select>
              </div>

              <div>
                <label className="block text-xs font-black text-slate-600 mb-1">Zona Geográfica / Localidad de este Recorrido:</label>
                <select
                  value={rZone}
                  onChange={(e) => setRZone(e.target.value)}
                  className="w-full border border-slate-200 bg-white rounded-xl p-3 text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                >
                  <option value="Norte">Zona Norte / Del Viso / Pilar</option>
                  <option value="Sur">Zona Sur / Berazategui / Quilmes</option>
                  <option value="Centro">Zona Centro / CABA</option>
                  <option value="Oeste">Zona Oeste / Morón / Moreno</option>
                  <option value="Este">Zona Este / Tigre</option>
                </select>
              </div>
            </div>

            {/* SELECCIÓN DE PEDIDOS PENDIENTES */}
            <div className="space-y-3">
              <div className="font-extrabold text-sm text-slate-700 flex items-center gap-2 uppercase tracking-wider">
                <Clipboard className="w-4 h-4 text-emerald-600" />
                <span>Seleccionar Envíos Autorizados para Reparto:</span>
              </div>

              <div className="border border-slate-200 rounded-xl overflow-hidden bg-white max-h-80 overflow-y-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-slate-50 border-b border-slate-200 text-slate-600 font-extrabold text-xs uppercase">
                      <th className="p-3 text-center w-14">Asociar</th>
                      <th className="p-3">Pedido ID</th>
                      <th className="p-3">Comercio / Cliente</th>
                      <th className="p-3">Dirección y Localidad</th>
                      <th className="p-3 text-center">Bultos</th>
                      <th className="p-3 text-right">Monto</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 font-bold text-xs">
                    {assignableOrders.length === 0 ? (
                      <tr>
                        <td colSpan={6} className="p-8 text-center text-slate-400">
                          No hay envíos pendientes/facturados libres en este momento para rutear.
                        </td>
                      </tr>
                    ) : (
                      assignableOrders.map(order => {
                        const totalUnits = order.items.reduce((sum, item) => sum + item.quantity, 0);
                        const isChecked = rSelectedOrderIds.includes(order.id);
                        const cust = customers.find(c => c.id === order.customerId);

                        return (
                          <tr 
                            key={order.id} 
                            onClick={() => handleToggleOrderSelection(order.id)}
                            className={`cursor-pointer transition-colors ${isChecked ? 'bg-emerald-50/50' : 'hover:bg-slate-50'}`}
                          >
                            <td className="p-3 text-center" onClick={(e) => e.stopPropagation()}>
                              <input
                                type="checkbox"
                                checked={isChecked}
                                onChange={() => handleToggleOrderSelection(order.id)}
                                className="w-4 h-4 accent-emerald-600 cursor-pointer"
                              />
                            </td>
                            <td className="p-3 font-mono text-slate-400">
                              <div className="flex flex-col gap-1">
                                <span>{order.id}</span>
                                {order.status === 'Demorado' && (
                                  <span className="text-[8px] font-black uppercase text-orange-500 bg-orange-50 border border-orange-200 px-1.5 py-0.5 rounded w-max animate-pulse">
                                    Demorado
                                  </span>
                                )}
                              </div>
                            </td>
                            <td className="p-3 text-slate-800 text-sm">{order.customerName}</td>
                            <td className="p-3 text-slate-500 font-medium">
                              <div>{cust?.address}</div>
                              <div className="text-[9px] uppercase font-bold text-slate-400 mt-0.5">{cust?.zone}</div>
                            </td>
                            <td className="p-3 text-center text-slate-600">{totalUnits} bultos</td>
                            <td className="p-3 text-right font-mono font-black text-slate-800">
                              ${order.total.toLocaleString('es-AR')}
                            </td>
                          </tr>
                        );
                      })
                    )}
                  </tbody>
                </table>
              </div>
              
              <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 flex justify-between items-center text-xs font-black text-slate-700">
                <span>Envíos Asociados: <strong className="text-emerald-600 text-sm">{rSelectedOrderIds.length}</strong></span>
                <span>Peso Estimado: <strong className="text-blue-600 text-sm">{rSelectedOrderIds.length * 35} kg</strong></span>
              </div>
            </div>

            <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
              <button
                type="button"
                onClick={() => setShowRouteForm(false)}
                className="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-800 font-bold text-xs rounded-xl cursor-pointer"
              >
                Cancelar
              </button>
              <button
                type="submit"
                className="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl cursor-pointer shadow transition-all active:scale-95"
                disabled={rSelectedOrderIds.length === 0}
              >
                Guardar Hoja de Ruta
              </button>
            </div>
          </form>
        </div>
      )}

      {/* ========================================== */}
      {/* 3. CONTENIDO PESTAÑA: FLOTA DE TRANSPORTE   */}
      {/* ========================================== */}
      {activeTab === 'VEHICULOS' && !showVehicleForm && (
        <div className="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-sm">
          <div className="flex items-center justify-between border-b pb-4">
            <h2 className="text-xl font-black text-slate-800">Flota de Transporte Registrada</h2>
            <span className="text-xs font-bold text-slate-500 bg-slate-100 border border-slate-200 px-3 py-1 rounded-xl">
              Total: <strong>{vehicles.length}</strong> unidades de reparto
            </span>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredVehicles.map(v => (
              <div key={v.id} className="border border-slate-200 hover:border-indigo-500/60 rounded-2xl p-5 flex flex-col justify-between gap-4 transition-all bg-white shadow-sm">
                <div className="space-y-3 text-left">
                  <div className="flex justify-between items-start gap-2">
                    <div>
                      <span className="text-[9px] font-black text-slate-400 uppercase tracking-widest">PATENTE</span>
                      <h4 className="text-lg font-black text-mono text-slate-800">{v.patent}</h4>
                    </div>
                    <span className={`px-2 py-0.5 rounded-full text-xs font-black border ${
                      v.status === 'Disponible' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' :
                      v.status === 'En Viaje' ? 'bg-blue-50 text-blue-600 border-blue-200' :
                      'bg-red-50 text-red-600 border-red-200'
                    }`}>
                      {v.status}
                    </span>
                  </div>

                  <div className="space-y-0.5">
                    <p className="text-[9px] font-extrabold text-slate-400 uppercase">Vehículo de Carga</p>
                    <p className="text-sm font-bold text-slate-700">{v.model}</p>
                  </div>

                  <div className="space-y-0.5">
                    <p className="text-[9px] font-extrabold text-slate-400 uppercase">Chofer Asignado habitual</p>
                    <div className="flex items-center gap-1.5 text-xs text-slate-700">
                      <UserIcon className="w-3.5 h-3.5 text-indigo-500" />
                      <span className="font-extrabold">{v.driverName}</span>
                    </div>
                  </div>

                  <div className="bg-slate-50 p-2.5 rounded-xl border border-slate-100 flex justify-between items-center text-xs">
                    <span className="font-bold text-slate-400">Capacidad Máxima:</span>
                    <span className="font-mono font-black text-slate-700">{v.capacityKg} kg</span>
                  </div>
                </div>

                <div className="flex gap-2 border-t border-slate-100 pt-3">
                  <button
                    onClick={() => handleOpenEditVehicle(v)}
                    className="flex-1 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer transition-all"
                  >
                    Editar Datos
                  </button>
                  <button
                    onClick={() => handleDeleteVehicle(v.id, v.patent)}
                    className="p-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl cursor-pointer transition-all"
                    title="Baja de Flota"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* ========================================== */}
      {/* 3.1 FORMULARIO GESTIÓN VEHÍCULO            */}
      {/* ========================================== */}
      {showVehicleForm && activeTab === 'VEHICULOS' && (
        <div className="bg-white border border-indigo-500 rounded-2xl p-6 space-y-6 shadow-md animate-in slide-in-from-top-4 duration-300">
          <div className="flex items-center justify-between border-b pb-4">
            <h3 className="text-lg font-black text-slate-800 flex items-center gap-2">
              <Truck className="w-5 h-5 text-indigo-600" />
              <span>{editingVehicle ? 'Modificar Unidad de Flota' : 'Alta de Nueva Unidad de Carga'}</span>
            </h3>
            <button onClick={() => setShowVehicleForm(false)} className="p-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl cursor-pointer">
              <X className="w-5 h-5" />
            </button>
          </div>

          {vehicleError && (
            <div className="p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl font-bold flex items-center gap-2">
              <AlertTriangle className="w-5 h-5 text-red-500" />
              <span>{vehicleError}</span>
            </div>
          )}

          <form onSubmit={handleSaveVehicle} className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-black text-slate-600 mb-1">Patente / Dominio Fiscal *</label>
              <input
                type="text"
                required
                placeholder="Ej. AF123JK o AA123BB"
                value={vPatent}
                onChange={(e) => setVPatent(e.target.value)}
                className="w-full border border-slate-200 bg-white rounded-xl p-3 text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 uppercase"
              />
            </div>

            <div>
              <label className="block text-xs font-black text-slate-600 mb-1">Marca y Modelo del Camión *</label>
              <input
                type="text"
                required
                placeholder="Ej. Ford Cargo 1722, Sprinter 313"
                value={vModel}
                onChange={(e) => setVModel(e.target.value)}
                className="w-full border border-slate-200 bg-white rounded-xl p-3 text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
              />
            </div>

            <div>
              <label className="block text-xs font-black text-slate-600 mb-1">Conductor / Chofer Designado *</label>
              <select
                value={vDriverId}
                onChange={(e) => setVDriverId(e.target.value)}
                className="w-full border border-slate-200 bg-white rounded-xl p-3 text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
              >
                {drivers.map(d => (
                  <option key={d.id} value={d.id}>{d.name} (Repartidor ID: {d.id})</option>
                ))}
                {drivers.length === 0 && (
                  <option value="">No hay repartidores registrados activos</option>
                )}
              </select>
            </div>

            <div>
              <label className="block text-xs font-black text-slate-600 mb-1">Capacidad de Carga Útil (kg) *</label>
              <input
                type="number"
                min="100"
                value={vCapacity}
                onChange={(e) => setVCapacity(Number(e.target.value))}
                className="w-full border border-slate-200 bg-white rounded-xl p-3 text-sm font-bold text-slate-800 focus:outline-none focus:ring-2"
              />
            </div>

            {editingVehicle && (
              <div className="md:col-span-2">
                <label className="block text-xs font-black text-slate-600 mb-1">Disponibilidad Actual *</label>
                <select
                  value={vStatus}
                  onChange={(e) => setVStatus(e.target.value as any)}
                  className="w-full border border-slate-200 bg-white rounded-xl p-3 text-sm font-bold text-slate-800 focus:outline-none"
                >
                  <option value="Disponible">Disponible para despacho</option>
                  <option value="En Viaje">En Viaje / Reparto Activo</option>
                  <option value="En Taller">En Mantenimiento Taller mecánico</option>
                </select>
              </div>
            )}

            <div className="md:col-span-2 flex justify-end gap-3 pt-4 border-t border-slate-100">
              <button
                type="button"
                onClick={() => setShowVehicleForm(false)}
                className="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-800 font-bold text-xs rounded-xl cursor-pointer"
              >
                Cancelar
              </button>
              <button
                type="submit"
                className="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl cursor-pointer shadow transition-all active:scale-95"
              >
                {editingVehicle ? 'Grabar Cambios' : 'Registrar Camión'}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* ========================================== */}
      {/* 4. CONTENIDO PESTAÑA: DISPOSITIVO CHOFER    */}
      {/* ========================================== */}
      {activeTab === 'CHOFER' && (
        <div className="max-w-xl md:max-w-2xl lg:max-w-md mx-auto space-y-6 animate-in fade-in duration-200">
          
          {/* SIMULADOR DE SELECCIÓN DE REPARTIDOR */}
          <div className="bg-white border border-purple-200 rounded-2xl p-4 space-y-3 shadow-sm text-left">
            <label className="block text-xs font-black text-purple-700 uppercase tracking-wider" htmlFor="sim-driver-select">
              📱 Seleccionar Chofer a Simular:
            </label>
            <select
              id="sim-driver-select"
              value={simulatedDriverId}
              onChange={(e) => setSimulatedDriverId(e.target.value)}
              className="w-full border border-slate-200 rounded-xl p-3 text-sm font-black focus:outline-none bg-slate-50 cursor-pointer"
            >
              {drivers.map(d => (
                <option key={d.id} value={d.id}>Chofer: {d.name} (ID: {d.id})</option>
              ))}
              {drivers.length === 0 && (
                <option value="">No hay repartidores en sistema</option>
              )}
            </select>
            <p className="text-[10px] text-slate-400 font-bold leading-relaxed">
              Permite simular la terminal móvil que lleva el repartidor en la calle para actualizar estados de pedidos en tiempo real.
            </p>
          </div>

          {/* CELULAR SIMULADO ACCESIBLE Y POLISHED */}
          <div className="bg-slate-50 text-slate-800 rounded-2xl md:rounded-[2.5rem] p-3.5 md:p-4 shadow-md md:shadow-xl border border-slate-200 md:border-[12px] md:border-slate-900 relative overflow-hidden min-h-[600px] flex flex-col">
            {/* Cámara / Parlante celular superior */}
            <div className="hidden md:block absolute top-0 left-1/2 transform -translate-x-1/2 bg-slate-900 h-5 w-32 rounded-b-xl z-20"></div>

            {/* Señal / Hora ficticia */}
            <div className="hidden md:flex justify-between items-center text-[10px] text-slate-500 font-bold px-3 pt-2.5 pb-1">
              <span>Distribuidora Pigüé LTE</span>
              <span>12:40</span>
            </div>

            <div className="flex-1 flex flex-col justify-between mt-1.5 md:mt-3 text-left">
              
              {/* Info Chofer Logueado */}
              <div className="bg-white border border-slate-200/80 p-3 rounded-2xl mb-4 text-xs shadow-sm">
                <span className="text-[9px] text-slate-500 font-bold uppercase tracking-wider">CHOFER ACTIVO:</span>
                <div className="text-sm font-black text-slate-950 mt-0.5">
                  {users.find(u => u.id === simulatedDriverId)?.name || 'Sin Chofer'}
                </div>
                <div className="mt-1 flex items-center gap-1.5">
                  <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                  <span className="text-[9px] text-emerald-600 font-extrabold uppercase">Ruta de Reparto de Hoy</span>
                </div>
              </div>

              {/* Paradas asignadas / No asignadas */}
              {!activeDriverRoute ? (
                <div className="flex-1 flex flex-col items-center justify-center p-6 text-center space-y-3">
                  <Clock className="w-12 h-12 text-slate-400" />
                  <h5 className="font-bold text-sm text-slate-700">No posees una hoja de ruta en tránsito hoy.</h5>
                  <p className="text-xs text-slate-500">
                    Comuníquese con la oficina central de despacho para que asignen y autoricen un recorrido de reparto.
                  </p>
                </div>
              ) : (
                <div className="flex-1 flex flex-col justify-between space-y-4">
                  {/* Info Hoja de Ruta Celular */}
                  <div className="bg-purple-50 border border-purple-100 p-3 rounded-2xl flex justify-between items-center shadow-sm">
                    <div>
                      <span className="text-[8px] font-black text-purple-700 uppercase tracking-widest">HOJA DE RUTA ACTIVA</span>
                      <h4 className="text-sm font-black text-purple-950">{activeDriverRoute.id}</h4>
                      <p className="text-[10px] text-slate-600 mt-0.5">Móvil: {activeDriverRoute.vehicleModel} ({activeDriverRoute.vehiclePatent})</p>
                    </div>
                    <span className="text-[10px] font-black bg-purple-100 text-purple-700 px-2.5 py-0.5 rounded-full border border-purple-200">
                      En Tránsito
                    </span>
                  </div>

                  {/* Paradas del camión listadas */}
                  <div className="space-y-3 flex-1 overflow-y-auto max-h-[340px] pr-1">
                    <span className="text-[10px] font-black text-slate-500 uppercase tracking-widest block px-1">PARADAS A ENTREGAR:</span>
                    
                    {activeDriverRoute.orderIds.map((orderId, idx) => {
                      const order = orders.find(o => o.id === orderId);
                      if (!order) return null;
                      const isDelivered = order.status === 'Entregado';
                      const isCancelled = order.status === 'Cancelado';
                      const isDelayed = order.status === 'Demorado';

                      const customer = customers.find(c => c.id === order.customerId);

                      return (
                        <div 
                          key={orderId} 
                          className={`p-3.5 rounded-xl border transition-all shadow-sm ${
                            isDelivered ? 'bg-slate-100/70 border-emerald-500/20 opacity-75' :
                            isCancelled ? 'bg-slate-100/70 border-red-500/20 opacity-60 line-through' :
                            isDelayed ? 'bg-orange-50 border-orange-200' :
                            'bg-white border-slate-200 hover:border-slate-300'
                          }`}
                        >
                          {/* Parada index e indicador */}
                          <div className="flex justify-between items-center">
                            <span className="text-[9px] font-black text-purple-700 uppercase">PARADA #{idx + 1}</span>
                            <span className={`text-[9px] font-black uppercase px-2 py-0.5 rounded-full ${
                              isDelivered ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                              isCancelled ? 'bg-red-50 text-red-700 border border-red-200' :
                              isDelayed ? 'bg-orange-50 text-orange-700 border border-orange-200 animate-pulse' :
                              'bg-blue-50 text-blue-700 border border-blue-200'
                            }`}>
                              {order.status === 'Remitido' ? 'En Camión' : order.status}
                            </span>
                          </div>

                          <h5 className="font-black text-sm text-slate-900 mt-1.5 leading-tight">{order.customerName}</h5>
                          
                          <div className="flex items-start gap-1 text-slate-500 text-xs mt-1.5 font-bold leading-tight">
                            <MapPin className="w-3.5 h-3.5 text-red-500 shrink-0 mt-0.5" />
                            <span>{customer?.address} ({customer?.zone})</span>
                          </div>

                          {order.notes && (
                            <div className="text-[10px] text-slate-500 mt-1 italic pl-5 font-semibold">
                              Notas: {order.notes}
                            </div>
                          )}

                          {/* BOTON DE LLAMADA AL CLIENTE (Práctico para el chofer) */}
                          {customer?.phone && (
                            <div className="mt-2.5 flex justify-start pl-4">
                              <a
                                href={`tel:${customer.phone}`}
                                className="inline-flex items-center gap-1.5 text-xs bg-blue-50 hover:bg-blue-100 text-blue-600 px-3 py-1.5 rounded-lg border border-blue-200 cursor-pointer font-black transition-all shadow-sm"
                              >
                                <Phone className="w-3.5 h-3.5" />
                                <span>Llamar: {customer.phone}</span>
                              </a>
                            </div>
                          )}

                          {/* DETALLES DE BULTOS / COBRO */}
                          <div className="mt-3 bg-slate-50 p-2.5 rounded-xl border border-slate-200/80 flex justify-between items-center text-xs">
                            <span className="text-slate-500 font-extrabold">{order.items.reduce((sum, item) => sum + item.quantity, 0)} bultos</span>
                            <span className="font-mono font-black text-slate-900">${order.total.toLocaleString('es-AR')}</span>
                          </div>

                          {/* BOTONERA ACCIONES DE ENTREGA */}
                          {!isDelivered && !isCancelled && (
                            <div className="grid grid-cols-3 gap-1.5 mt-3 border-t border-slate-200 pt-2.5">
                              {/* Botón Rechazar / Devolver */}
                              <button
                                onClick={() => handleOpenCancelModal(order)}
                                className="bg-red-50 hover:bg-red-100 text-red-700 border-2 border-red-200 py-2 rounded-lg font-black text-[10px] flex items-center justify-center gap-1 cursor-pointer transition-all active:scale-95 shadow-sm"
                                title="Rechazar entrega de mercadería"
                              >
                                <Ban className="w-3.5 h-3.5" />
                                <span>Rechazar</span>
                              </button>

                              {/* Botón Demorar */}
                              <button
                                onClick={() => handleOpenDelayModal(order)}
                                className="bg-orange-50 hover:bg-orange-100 text-orange-700 border-2 border-orange-200 py-2 rounded-lg font-black text-[10px] flex items-center justify-center gap-1 cursor-pointer transition-all active:scale-95 shadow-sm"
                                title="Demorar entrega (esperar o reprogramar)"
                              >
                                <Clock className="w-3.5 h-3.5" />
                                <span>Demorar</span>
                              </button>

                              {/* Botón Entregar */}
                              <button
                                onClick={() => handleDeliverOrder(order.id)}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white border-2 border-emerald-700 py-2 rounded-lg font-black text-[10px] flex items-center justify-center gap-1 cursor-pointer shadow-md shadow-emerald-150 transition-all active:scale-95"
                                title="Confirmar mercadería entregada"
                              >
                                <Check className="w-3.5 h-3.5 stroke-[3]" />
                                <span>Entregar</span>
                              </button>
                            </div>
                          )}
                        </div>
                      );
                    })}
                  </div>
                  
                  {/* Botón de finalización manual si hay pedidos demorados o remanentes */}
                  <div className="pt-2.5 border-t border-slate-200 mt-1 shrink-0">
                    <button
                      onClick={() => handleForceCompleteRoute(activeDriverRoute)}
                      className="w-full py-2.5 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white font-black text-xs rounded-xl flex items-center justify-center gap-1.5 cursor-pointer shadow-md transition-all active:scale-95"
                      title="Finalizar el recorrido actual y archivar la hoja de ruta"
                    >
                      <CheckCircle2 className="w-4 h-4" />
                      <span>Rendir y Finalizar Recorrido</span>
                    </button>
                  </div>
                </div>
              )}

              {/* Botón inferior home simulado celular */}
              <div className="hidden md:flex mt-4 pt-1.5 border-t border-slate-200 justify-center">
                <div className="w-10 h-10 rounded-full border border-slate-300 hover:bg-slate-100 flex items-center justify-center cursor-pointer shadow-sm">
                  <div className="w-3 h-3 bg-slate-400 rounded"></div>
                </div>
              </div>

            </div>
          </div>

        </div>
      )}

      {/* ======================================================= */}
      {/* MODAL 1: VER DETALLE DE MERCADERÍA DEL ENVÍO            */}
      {/* ======================================================= */}
      {showOrderDetailsModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm animate-in fade-in duration-200">
          <div className="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200 flex flex-col max-h-[90vh]">
            {/* Header */}
            <div className="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center text-left">
              <div>
                <span className="text-[10px] font-black text-slate-400 block uppercase tracking-widest">Mercadería del Envío</span>
                <h4 className="text-lg font-black text-slate-800">Pedido Nro {showOrderDetailsModal.id}</h4>
              </div>
              <button 
                onClick={() => setShowOrderDetailsModal(null)}
                className="p-1 bg-slate-200/60 hover:bg-slate-200 text-slate-600 rounded-lg cursor-pointer transition-all"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Contenido */}
            <div className="p-6 overflow-y-auto space-y-4 text-left">
              <div className="bg-slate-50 p-3.5 rounded-xl border border-slate-200/60 text-xs text-slate-600 font-bold space-y-1">
                <p><strong>Comercio / Cliente:</strong> {showOrderDetailsModal.customerName}</p>
                <p><strong>Fecha Registro:</strong> {showOrderDetailsModal.date}</p>
                <p><strong>Observaciones / Notas:</strong> {showOrderDetailsModal.notes || 'Ninguna'}</p>
              </div>

              <div className="space-y-2">
                <p className="text-xs font-black text-slate-500 uppercase tracking-widest">Detalle de Productos a cargar:</p>
                <div className="border rounded-xl overflow-hidden font-bold text-xs divide-y divide-slate-100">
                  <div className="bg-slate-50 p-2.5 grid grid-cols-12 text-slate-500 uppercase font-extrabold text-[10px]">
                    <div className="col-span-8">Producto</div>
                    <div className="col-span-2 text-center">Cant.</div>
                    <div className="col-span-2 text-right">Total</div>
                  </div>
                  {showOrderDetailsModal.items.map(item => (
                    <div key={item.productId} className="p-2.5 grid grid-cols-12 items-center">
                      <div className="col-span-8 text-slate-800 leading-tight">{item.productName}</div>
                      <div className="col-span-2 text-center text-slate-700 bg-slate-100 py-0.5 rounded-md font-mono">{item.quantity} u</div>
                      <div className="col-span-2 text-right text-slate-800 font-mono">${item.total.toLocaleString('es-AR')}</div>
                    </div>
                  ))}
                </div>
              </div>

              <div className="bg-slate-50 p-3.5 rounded-xl border flex justify-between items-center font-black">
                <span className="text-slate-500 text-xs">MONTO TOTAL FACTURADO</span>
                <span className="text-slate-800 text-lg font-mono">${showOrderDetailsModal.total.toLocaleString('es-AR')}</span>
              </div>
            </div>

            {/* Footer */}
            <div className="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end">
              <button
                onClick={() => setShowOrderDetailsModal(null)}
                className="px-5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl cursor-pointer transition-all"
              >
                Cerrar Ventana
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ======================================================= */}
      {/* MODAL 2: CARGO LOAD SHEET / PRINT REMITO (VER EN PANTALLA) */}
      {/* ======================================================= */}
      {activeRouteForLoadSheet && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm overflow-y-auto animate-in fade-in duration-200">
          <div className="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-300 flex flex-col max-h-[95vh] my-4">
            
            {/* Header del Modal */}
            <div className="bg-slate-900 text-white px-6 py-4 border-b border-slate-800 flex justify-between items-center text-left shrink-0">
              <div className="flex items-center gap-2">
                <FileText className="w-5 h-5 text-blue-400" />
                <div>
                  <h4 className="text-base font-black tracking-tight">Hoja de Carga y Remitos Consolidados</h4>
                  <p className="text-[10px] text-slate-400 mt-0.5 font-semibold">Previsualización de Documentos Físicos de Despacho</p>
                </div>
              </div>
              <button 
                onClick={() => {
                  setActiveRouteForLoadSheet(null);
                  setPrintSuccessMessage(false);
                }}
                className="p-1 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white rounded-lg cursor-pointer transition-all"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Contenido Printable del Documento */}
            <div className="p-6 overflow-y-auto flex-1 space-y-6 text-left" id="load-sheet-print-container">
              
              {/* Animación de Simulación de Impresora Matricial */}
              {printSuccessMessage ? (
                <div className="bg-emerald-50 border-2 border-emerald-300 text-emerald-800 p-4 rounded-xl flex items-center gap-3 animate-bounce">
                  <CheckCircle2 className="w-6 h-6 text-emerald-600 shrink-0" />
                  <div>
                    <h5 className="font-extrabold text-sm leading-none">¡Envío Exitoso a Impresora!</h5>
                    <p className="text-xs text-emerald-600 font-bold mt-1">Imprimiendo Hoja de Carga Consolidada y {activeRouteForLoadSheet.orderIds.length} Remitos de clientes...</p>
                  </div>
                </div>
              ) : (
                <div className="bg-blue-50/70 border border-blue-200 text-blue-800 p-3.5 rounded-xl flex items-center gap-2.5 text-xs font-bold leading-relaxed">
                  <AlertCircle className="w-5 h-5 text-blue-600 shrink-0" />
                  <span>Este papel consolidado es el que se le entrega al repartidor y cargador en depósito para saber exactamente qué subir a la unidad de carga antes de salir.</span>
                </div>
              )}

              {/* Cabecera Tipo Papel */}
              <div className="border-4 border-double border-slate-400 p-4 space-y-3 bg-white text-slate-800 shadow-sm font-mono text-xs">
                <div className="flex justify-between items-start border-b-2 border-dashed border-slate-300 pb-2.5">
                  <div>
                    <h3 className="text-base font-black text-slate-900 tracking-tight">DISTRIBUIDORA PIGÜÉ</h3>
                    <p className="text-[9px] text-slate-500 font-bold mt-0.5">SISTEMA ERP DE DESPACHO FISCAL</p>
                  </div>
                  <div className="text-right">
                    <span className="font-black text-sm bg-slate-100 px-2 py-0.5 rounded border border-slate-200">HOJA CARGA</span>
                    <p className="text-[10px] text-slate-500 font-bold mt-1">RUTA: {activeRouteForLoadSheet.id}</p>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4 text-[11px] font-bold py-1">
                  <div>
                    <p><strong className="text-slate-400">FECHA:</strong> {activeRouteForLoadSheet.date}</p>
                    <p><strong className="text-slate-400">UNIDAD:</strong> {activeRouteForLoadSheet.vehicleModel}</p>
                  </div>
                  <div>
                    <p><strong className="text-slate-400">CHOFER:</strong> {activeRouteForLoadSheet.driverName}</p>
                    <p><strong className="text-slate-400">DOMINIO:</strong> {activeRouteForLoadSheet.vehiclePatent}</p>
                  </div>
                </div>

                {/* TABLA CONSOLIDADA - AGREGADO DE ARTICULOS */}
                <div className="space-y-1.5 pt-3 border-t-2 border-dashed border-slate-300">
                  <span className="text-[10px] font-black text-slate-900 uppercase block tracking-wider font-sans">
                    📊 CONSOLIDADO GENERAL DE CARGA DE MERCADERÍA:
                  </span>
                  <div className="border border-slate-300 rounded overflow-hidden">
                    <div className="bg-slate-100 p-2 grid grid-cols-12 text-slate-500 uppercase font-black text-[9px] border-b border-slate-300">
                      <div className="col-span-8">Artículos a Subir al Camión</div>
                      <div className="col-span-4 text-center">Cantidad Total</div>
                    </div>
                    {getConsolidatedCargo(activeRouteForLoadSheet).map((cargoItem, i) => (
                      <div key={i} className="p-2 grid grid-cols-12 font-bold text-[11px] border-b border-slate-200 last:border-0 items-center">
                        <div className="col-span-8 text-slate-800 leading-tight font-mono">{cargoItem.productName}</div>
                        <div className="col-span-4 text-center text-slate-900 font-black font-mono bg-slate-50 border py-0.5 rounded">
                          {cargoItem.totalQty} bultos
                        </div>
                      </div>
                    ))}
                  </div>
                  <p className="text-[9px] text-slate-400 font-bold pt-1 italic">
                    * El cargador de depósito debe chequear el total de bultos consolidado antes de firmar la salida de camión.
                  </p>
                </div>

                {/* DETALLE DE REMITOS ASOCIADOS */}
                <div className="space-y-2 pt-4 border-t-2 border-dashed border-slate-300">
                  <span className="text-[10px] font-black text-slate-900 uppercase block tracking-wider font-sans">
                    🚚 DETALLE DE CLIENTES EN EL CAMINO (ORDEN DE REPARTO):
                  </span>
                  
                  <div className="space-y-3 font-mono">
                    {activeRouteForLoadSheet.orderIds.map((orderId, idx) => {
                      const order = orders.find(o => o.id === orderId);
                      const customer = order ? customers.find(c => c.id === order.customerId) : null;
                      return (
                        <div key={orderId} className="border border-slate-300 p-2.5 rounded bg-slate-50/50 space-y-1 text-[11px] font-bold">
                          <div className="flex justify-between border-b pb-1 mb-1 border-slate-200">
                            <span>REMITO #{idx + 1} - PEDIDO NRO {orderId}</span>
                            <span className="font-black text-slate-700">${order?.total.toLocaleString('es-AR')}</span>
                          </div>
                          <p><strong className="text-slate-400">CLIENTE:</strong> {order?.customerName}</p>
                          <p><strong className="text-slate-400">DIRECCIÓN:</strong> {customer?.address} ({customer?.zone})</p>
                          <p><strong className="text-slate-400">TELÉFONO:</strong> {customer?.phone || 'No registrado'}</p>
                          <p><strong className="text-slate-400">BULTOS:</strong> {order?.items.reduce((sum, item) => sum + item.quantity, 0)} bultos</p>
                        </div>
                      );
                    })}
                  </div>
                </div>

                {/* Firmas */}
                <div className="grid grid-cols-2 gap-8 pt-8 text-[9px] font-extrabold text-slate-500 text-center uppercase tracking-widest font-sans">
                  <div>
                    <div className="border-t border-slate-400 w-28 mx-auto mt-6"></div>
                    <p className="mt-1">Firma Control Depósito</p>
                  </div>
                  <div>
                    <div className="border-t border-slate-400 w-28 mx-auto mt-6"></div>
                    <p className="mt-1">Firma Chofer Repartidor</p>
                  </div>
                </div>

              </div>
            </div>

            {/* Footer de Acciones del Modal */}
            <div className="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-between shrink-0">
              <button
                onClick={() => {
                  setActiveRouteForLoadSheet(null);
                  setPrintSuccessMessage(false);
                }}
                className="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl cursor-pointer transition-all"
              >
                Cerrar Ventana
              </button>

              <button
                onClick={() => {
                  setPrintSuccessMessage(true);
                  setTimeout(() => {
                    window.print();
                  }, 400);
                }}
                className="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl cursor-pointer shadow flex items-center gap-2 transition-all active:scale-95"
              >
                <Printer className="w-4 h-4" />
                <span>Simular / Imprimir Documento</span>
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ======================================================= */}
      {/* MODAL 3: DEMORAR ENVÍO EN RUTA (MÓVIL SIMULADOR)        */}
      {/* ======================================================= */}
      {delayModalOrder && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm animate-in fade-in duration-200 text-left">
          <div className="bg-white rounded-2xl max-w-sm w-full p-6 space-y-4 shadow-2xl border border-slate-200">
            <h4 className="text-base font-black text-slate-800 flex items-center gap-2">
              <Clock className="w-5 h-5 text-orange-500" />
              <span>Demorar Envío de Comercio</span>
            </h4>
            
            <p className="text-xs text-slate-500 font-bold">
              Indique por qué razón el pedido {delayModalOrder.id} de <strong>{delayModalOrder.customerName}</strong> se demorará en entregarse:
            </p>

            <div className="space-y-1">
              <label className="block text-[10px] font-black text-slate-400 uppercase">Motivo / Notas del Chofer:</label>
              <input
                type="text"
                placeholder="Ej. El cliente se retrasó, Tránsito lento..."
                value={delayReason}
                onChange={(e) => setDelayReason(e.target.value)}
                className="w-full border border-slate-200 rounded-xl p-3 text-sm font-bold bg-slate-50 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                required
              />
            </div>

            <div className="flex justify-end gap-2 pt-2 text-xs">
              <button
                onClick={() => setDelayModalOrder(null)}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-xl cursor-pointer transition-all"
              >
                Cancelar
              </button>
              <button
                onClick={handleConfirmDelay}
                className="px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white font-extrabold rounded-xl cursor-pointer shadow transition-all active:scale-95"
              >
                Confirmar Demorado
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ======================================================= */}
      {/* MODAL 4: RECHAZAR / CANCELAR ENVÍO Y REINGRESO STOCK    */}
      {/* ======================================================= */}
      {cancelModalOrder && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm animate-in fade-in duration-200 text-left">
          <div className="bg-white rounded-2xl max-w-sm w-full p-6 space-y-4 shadow-2xl border border-slate-200">
            <h4 className="text-base font-black text-slate-800 flex items-center gap-2">
              <Ban className="w-5 h-5 text-red-500" />
              <span>Rechazo y Devolución</span>
            </h4>
            
            <p className="text-xs text-slate-500 font-bold leading-relaxed">
              Al confirmar el rechazo del pedido {cancelModalOrder.id} de <strong>{cancelModalOrder.customerName}</strong>, toda la mercadería del camión se reingresará de forma automática al depósito.
            </p>

            <div className="space-y-1">
              <label className="block text-[10px] font-black text-slate-400 uppercase">Motivo del Rechazo de Mercadería:</label>
              <select
                value={cancelReason}
                onChange={(e) => setCancelReason(e.target.value)}
                className="w-full border border-slate-200 rounded-xl p-3 text-sm font-bold bg-slate-50 text-slate-800 focus:outline-none"
              >
                <option value="Local cerrado / Sin dinero">Local cerrado / Sin dinero</option>
                <option value="Error de mercadería / Pedido equivocado">Error de mercadería / Pedido equivocado</option>
                <option value="Mercadería rota / dañada">Mercadería rota / dañada</option>
                <option value="El cliente canceló a último momento">El cliente canceló a último momento</option>
                <option value="Sin espacio para almacenamiento">Sin espacio para almacenamiento</option>
              </select>
            </div>

            <div className="flex justify-end gap-2 pt-2 text-xs">
              <button
                onClick={() => setCancelModalOrder(null)}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-xl cursor-pointer transition-all"
              >
                Volver Atrás
              </button>
              <button
                onClick={handleConfirmCancelAndReturnStock}
                className="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold rounded-xl cursor-pointer shadow transition-all active:scale-95"
              >
                Confirmar Devolución Depósito
              </button>
            </div>
          </div>
        </div>
      )}

    </div>
  );
}
