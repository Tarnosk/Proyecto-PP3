/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState, useEffect } from 'react';
import { Purchase, PurchaseItem, Supplier, Product, User } from '../types';
import { comprasService, proveedoresService } from '../services';
import { formatDateTime } from '../utils/dateUtils';
import { 
  ShoppingBag, 
  ShoppingCart, 
  Plus, 
  Search, 
  FileText, 
  CheckCircle, 
  Clock, 
  XCircle, 
  AlertTriangle, 
  DollarSign, 
  Truck, 
  Send, 
  Sparkles, 
  RotateCcw,
  Check,
  X,
  CreditCard,
  Building,
  Calendar,
  Layers,
  ChevronRight,
  PackageCheck,
  RotateCw
} from 'lucide-react';

interface PurchasesViewProps {
  currentUser: User;
  purchases: Purchase[];
  suppliers: Supplier[];
  products: Product[];
  onUpdatePurchases: (purchases: Purchase[]) => void;
  onUpdateProducts?: (products: Product[]) => void;
}

export function PurchasesView({
  currentUser,
  purchases,
  suppliers,
  products,
  onUpdatePurchases,
  onUpdateProducts,
}: PurchasesViewProps) {
  // Pestaña activa: COMPRAS, ORDENES, SUGERENCIAS
  const [activeTab, setActiveTab] = useState<'COMPRAS' | 'ORDENES' | 'SUGERENCIAS'>('COMPRAS');

  // Filtros Compras
  const [comprasSearch, setComprasSearch] = useState('');
  const [comprasProveedorFilter, setComprasProveedorFilter] = useState('');
  const [comprasEstadoFilter, setComprasEstadoFilter] = useState('');

  // Filtros Órdenes
  const [ordenesSearch, setOrdenesSearch] = useState('');
  const [ordenesEstadoFilter, setOrdenesEstadoFilter] = useState('');

  // Estados de datos
  const [ordenesCompra, setOrdenesCompra] = useState<any[]>([]);
  const [sugerencias, setSugerencias] = useState<any[]>([]);
  const [loadingData, setLoadingData] = useState(false);

  // Modales Compras
  const [showNuevaCompraModal, setShowNuevaCompraModal] = useState(false);
  const [compraDetalleSeleccionada, setCompraDetalleSeleccionada] = useState<any | null>(null);
  const [showRecepcionModal, setShowRecepcionModal] = useState(false);
  const [showPagoModal, setShowPagoModal] = useState(false);

  // Modales Órdenes
  const [showNuevaOrdenModal, setShowNuevaOrdenModal] = useState(false);
  const [ordenDetalleSeleccionada, setOrdenDetalleSeleccionada] = useState<any | null>(null);

  // Formulario Nueva Compra
  const [nuevaCompraProvId, setNuevaCompraProvId] = useState('');
  const [nuevaCompraComprobante, setNuevaCompraComprobante] = useState('');
  const [nuevaCompraItems, setNuevaCompraItems] = useState<PurchaseItem[]>([]);
  const [itemProdId, setItemProdId] = useState('');
  const [itemCantidad, setItemCantidad] = useState(1);
  const [itemPrecio, setItemPrecio] = useState(0);
  const [compraError, setCompraError] = useState('');

  // Formulario Nueva Orden
  const [nuevaOrdenProvId, setNuevaOrdenProvId] = useState('');
  const [nuevaOrdenItems, setNuevaOrdenItems] = useState<Array<{ id_producto: number; cantidad: number; precio_unitario: number; nombre: string }>>([]);
  const [ordenError, setOrdenError] = useState('');

  // Formulario Pago
  const [pagoMonto, setPagoMonto] = useState(0);
  const [pagoForma, setPagoForma] = useState<'efectivo' | 'transferencia' | 'cheque'>('transferencia');
  const [pagoComprobante, setPagoComprobante] = useState('');
  const [pagoError, setPagoError] = useState('');

  // Formulario Recepción
  const [recepcionCantidades, setRecepcionCantidades] = useState<Record<number, number>>({});
  const [recepcionError, setRecepcionError] = useState('');

  // Cargar órdenes y sugerencias al montar o cambiar pestaña
  useEffect(() => {
    cargarOrdenesYSugerencias();
  }, [activeTab]);

  const cargarOrdenesYSugerencias = async () => {
    setLoadingData(true);
    try {
      if (activeTab === 'ORDENES') {
        const ordenes = await comprasService.getOrdenesCompra();
        setOrdenesCompra(ordenes);
      } else if (activeTab === 'SUGERENCIAS') {
        try {
          const sugs = await comprasService.getSugerenciasReposicion();
          if (sugs && sugs.length > 0) {
            setSugerencias(sugs);
            return;
          }
        } catch (apiErr) {
          console.warn('API sugerencias no disponible, calculando localmente:', apiErr);
        }

        // Cálculo local de sugerencias: Detectar productos con stock <= minStock
        const localSugs = products
          .filter(p => (p.stock !== undefined && p.minStock !== undefined && p.stock <= p.minStock))
          .map(p => {
            const cantSugerida = Math.max(1, (p.optimalStock || (p.minStock * 2)) - p.stock);
            const provSugerido = suppliers.find(s => 
              s.productos_asociados?.some((item: any) => 
                (item.id_producto === p.id_producto || item.id_producto === Number(p.id)) && item.es_proveedor_principal
              )
            ) || suppliers[0];

            return {
              id_producto: p.id_producto || Number(p.id),
              producto_nombre: p.name,
              nombre: p.name,
              stock_actual: p.stock,
              stock_minimo: p.minStock,
              cantidad_sugerida: cantSugerida,
              proveedor_id: provSugerido ? (provSugerido.id_proveedor || provSugerido.id) : null,
              proveedor_nombre: provSugerido ? provSugerido.name : 'Sin proveedor asignado',
            };
          });

        setSugerencias(localSugs);
      }
    } catch (err) {
      console.warn('Error al cargar datos adicionales de compras', err);
    } finally {
      setLoadingData(false);
    }
  };

  const [isRefreshing, setIsRefreshing] = useState(false);

  const handleRefrescarCompras = async () => {
    setIsRefreshing(true);
    try {
      await Promise.allSettled([
        cargarOrdenesYSugerencias(),
        (async () => {
          const freshPurchases = await comprasService.getAll();
          if (freshPurchases) onUpdatePurchases(freshPurchases);
        })(),
      ]);
    } catch (e) {
      console.warn('Error al refrescar compras', e);
    } finally {
      setTimeout(() => setIsRefreshing(false), 500);
    }
  };

  // --- ACCIONES DE COMPRA ---

  const handleAddItemToCompra = () => {
    if (!itemProdId) return;
    const prod = products.find(p => p.id === itemProdId || String(p.id_producto) === itemProdId);
    if (!prod) return;

    if (itemCantidad <= 0) {
      alert('La cantidad debe ser mayor a 0');
      return;
    }

    const newItem: PurchaseItem = {
      productId: prod.id,
      id_producto: prod.id_producto || Number(prod.id),
      productName: prod.name,
      quantity: itemCantidad,
      costPrice: itemPrecio > 0 ? itemPrecio : prod.cost,
      precio_unitario: itemPrecio > 0 ? itemPrecio : prod.cost,
      total: itemCantidad * (itemPrecio > 0 ? itemPrecio : prod.cost),
    };

    setNuevaCompraItems([...nuevaCompraItems, newItem]);
    setItemCantidad(1);
    setItemPrecio(0);
  };

  const handleGuardarCompra = async (e: React.FormEvent) => {
    e.preventDefault();
    setCompraError('');

    if (!nuevaCompraProvId) {
      setCompraError('Debe seleccionar un proveedor.');
      return;
    }
    if (nuevaCompraItems.length === 0) {
      setCompraError('Debe ingresar al menos un producto.');
      return;
    }

    try {
      const created = await comprasService.create({
        id_proveedor: Number(nuevaCompraProvId),
        numero_comprobante: nuevaCompraComprobante.trim() || undefined,
        items: nuevaCompraItems.map(it => ({
          id_producto: it.id_producto || Number(it.productId),
          cantidad: it.quantity,
          precio_unitario: it.costPrice,
        })),
      });

      onUpdatePurchases([created, ...purchases]);
      setShowNuevaCompraModal(false);
      setNuevaCompraItems([]);
      setNuevaCompraComprobante('');
    } catch (err: any) {
      setCompraError(err.message || 'Error al registrar la compra.');
    }
  };

  const handleCancelarCompra = async (idCompra: number | string) => {
    const motivo = prompt('Ingrese el motivo de cancelación de la compra:');
    if (motivo === null) return;

    try {
      await comprasService.cancelar(idCompra, motivo);
      const updated = purchases.map(c => 
        (c.id === String(idCompra) || c.id_compra === Number(idCompra)) 
          ? { ...c, status: 'Cancelado' as const, estado: 'cancelada' } 
          : c
      );
      onUpdatePurchases(updated);
      if (compraDetalleSeleccionada) {
        setCompraDetalleSeleccionada(null);
      }
    } catch (err: any) {
      alert(err.message || 'Error al cancelar la compra.');
    }
  };

  const handleGuardarPago = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!compraDetalleSeleccionada) return;
    setPagoError('');

    if (pagoMonto <= 0) {
      setPagoError('El monto debe ser superior a 0.');
      return;
    }

    try {
      const idCompra = compraDetalleSeleccionada.id_compra || compraDetalleSeleccionada.id;
      await comprasService.registrarPago(idCompra, {
        monto: pagoMonto,
        forma_pago: pagoForma,
        comprobante: pagoComprobante.trim() || undefined,
      });

      // Refrescar compra
      const refreshed = await comprasService.getById(idCompra);
      onUpdatePurchases(purchases.map(p => (p.id === String(idCompra) || p.id_compra === Number(idCompra)) ? refreshed : p));
      setCompraDetalleSeleccionada(refreshed);
      setShowPagoModal(false);
      setPagoMonto(0);
      setPagoComprobante('');
    } catch (err: any) {
      setPagoError(err.message || 'Error al registrar el pago.');
    }
  };

  const handleGuardarRecepcion = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!compraDetalleSeleccionada) return;
    setRecepcionError('');

    const itemsRecepcion = Object.entries(recepcionCantidades)
      .filter(([_, cant]) => cant > 0)
      .map(([idDetalle, cant]) => ({
        id_detalle: Number(idDetalle),
        cantidad_recibida: cant,
      }));

    if (itemsRecepcion.length === 0) {
      setRecepcionError('Debe ingresar cantidad recibida mayor a 0 en al menos un ítem.');
      return;
    }

    try {
      const idCompra = compraDetalleSeleccionada.id_compra || compraDetalleSeleccionada.id;
      await comprasService.registrarRecepcion(idCompra, { items: itemsRecepcion });
      
      const refreshed = await comprasService.getById(idCompra);
      onUpdatePurchases(purchases.map(p => (p.id === String(idCompra) || p.id_compra === Number(idCompra)) ? refreshed : p));
      setCompraDetalleSeleccionada(refreshed);
      setShowRecepcionModal(false);
      setRecepcionCantidades({});
    } catch (err: any) {
      setRecepcionError(err.message || 'Error al registrar la recepción.');
    }
  };

  // --- ACCIONES DE ÓRDENES DE COMPRA ---

  const handleAddItemToOrden = () => {
    if (!itemProdId) return;
    const prod = products.find(p => p.id === itemProdId || String(p.id_producto) === itemProdId);
    if (!prod) return;

    if (itemCantidad <= 0) {
      alert('La cantidad debe ser mayor a 0');
      return;
    }

    nuevaOrdenItems.push({
      id_producto: prod.id_producto || Number(prod.id),
      cantidad: itemCantidad,
      precio_unitario: itemPrecio > 0 ? itemPrecio : prod.cost,
      nombre: prod.name,
    });
    setNuevaOrdenItems([...nuevaOrdenItems]);
    setItemCantidad(1);
    setItemPrecio(0);
  };

  const handleGuardarOrden = async (e: React.FormEvent) => {
    e.preventDefault();
    setOrdenError('');

    if (!nuevaOrdenProvId) {
      setOrdenError('Seleccione un proveedor.');
      return;
    }
    if (nuevaOrdenItems.length === 0) {
      setOrdenError('Debe agregar al menos un producto a la orden.');
      return;
    }

    try {
      await comprasService.createOrdenCompra({
        id_proveedor: Number(nuevaOrdenProvId),
        items: nuevaOrdenItems.map(it => ({
          id_producto: it.id_producto,
          cantidad: it.cantidad,
          precio_unitario: it.precio_unitario,
        })),
      });

      setShowNuevaOrdenModal(false);
      setNuevaOrdenItems([]);
      cargarOrdenesYSugerencias();
    } catch (err: any) {
      setOrdenError(err.message || 'Error al crear la orden de compra.');
    }
  };

  const handleEnviarOrden = async (idOrden: number) => {
    if (!confirm('¿Desea marcar esta orden como ENVIADA al proveedor?')) return;
    try {
      await comprasService.enviarOrdenCompra(idOrden);
      cargarOrdenesYSugerencias();
    } catch (err: any) {
      alert(err.message || 'Error al enviar orden.');
    }
  };

  const handleCancelarOrden = async (idOrden: number) => {
    const motivo = prompt('Motivo de cancelación de la orden:');
    if (motivo === null) return;
    try {
      await comprasService.cancelarOrdenCompra(idOrden, motivo);
      cargarOrdenesYSugerencias();
    } catch (err: any) {
      alert(err.message || 'Error al cancelar orden.');
    }
  };

  // --- SUGERENCIAS DE REPOSICIÓN ---

  const handleGenerarSugerencias = async () => {
    try {
      await comprasService.generarSugerenciasReposicion();
      cargarOrdenesYSugerencias();
    } catch (err: any) {
      alert(err.message || 'Error al generar sugerencias.');
    }
  };

  // Filtrado de compras
  const filteredPurchases = purchases.filter(p => {
    const matchSearch = comprasSearch === '' || 
      (p.supplierName && p.supplierName.toLowerCase().includes(comprasSearch.toLowerCase())) ||
      (p.invoiceNumber && p.invoiceNumber.toLowerCase().includes(comprasSearch.toLowerCase())) ||
      (p.numero_compra && p.numero_compra.toLowerCase().includes(comprasSearch.toLowerCase()));
    
    const matchProv = !comprasProveedorFilter || p.supplierId === comprasProveedorFilter || String(p.id_proveedor) === comprasProveedorFilter;
    const matchEstado = !comprasEstadoFilter || p.status === comprasEstadoFilter || p.estado === comprasEstadoFilter;

    return matchSearch && matchProv && matchEstado;
  });

  // KPIs de compras
  const totalCompras = purchases.length;
  const pendientesCompras = purchases.filter(p => p.status === 'Pendiente' || p.estado === 'pendiente').length;
  const completadasCompras = purchases.filter(p => p.status === 'Recibido' || p.estado === 'completada').length;
  const canceladasCompras = purchases.filter(p => p.status === 'Cancelado' || p.estado === 'cancelada').length;
  const deudaTotal = purchases.reduce((acc, curr) => {
    const deuda = curr.saldo_pendiente !== undefined 
      ? curr.saldo_pendiente 
      : (curr.debtAmount !== undefined ? curr.debtAmount : (curr.total - (curr.paidAmount || 0)));
    return acc + (deuda > 0 ? deuda : 0);
  }, 0);

  return (
    <div className="space-y-6 animate-in fade-in duration-300">
      
      {/* HEADER DE MÓDULO */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b-2 border-slate-200 pb-4">
        <div>
          <h1 className="text-3xl font-black text-slate-800 tracking-tight flex items-center gap-3">
            <ShoppingCart className="w-8 h-8 text-blue-600" />
            <span>Módulo de Compras y Abastecimiento</span>
          </h1>
          <p className="text-sm font-semibold text-slate-500 mt-1">
            Gestión completa de Compras, Órdenes de Compra y Sugerencias de Reposición.
          </p>
        </div>

        {/* SELECTOR DE PESTAÑAS */}
        <div className="flex p-1 bg-slate-200/80 rounded-2xl gap-1">
          <button
            onClick={() => setActiveTab('COMPRAS')}
            className={`px-4 py-2.5 rounded-xl font-black text-sm flex items-center gap-2 transition-all cursor-pointer ${
              activeTab === 'COMPRAS' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <ShoppingCart className="w-4 h-4" />
            <span>Compras de Mercadería</span>
          </button>
          
          <button
            onClick={() => setActiveTab('ORDENES')}
            className={`px-4 py-2.5 rounded-xl font-black text-sm flex items-center gap-2 transition-all cursor-pointer ${
              activeTab === 'ORDENES' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <FileText className="w-4 h-4" />
            <span>Órdenes de Compra</span>
          </button>

          <button
            onClick={() => setActiveTab('SUGERENCIAS')}
            className={`px-4 py-2.5 rounded-xl font-black text-sm flex items-center gap-2 transition-all cursor-pointer ${
              activeTab === 'SUGERENCIAS' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <Sparkles className="w-4 h-4" />
            <span>Sugerencias Reposición</span>
          </button>
        </div>
      </div>

      {/* ========================================================= */}
      {/* PESTAÑA 1: COMPRAS DE MERCADERÍA                          */}
      {/* ========================================================= */}
      {activeTab === 'COMPRAS' && (
        <div className="space-y-6">
          {/* KPIS DE COMPRAS */}
          <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
              <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Compras</span>
              <span className="text-2xl font-black text-slate-800 mt-1 block">{totalCompras}</span>
            </div>
            <div className="bg-white p-4 rounded-2xl border border-amber-200/80 shadow-sm">
              <span className="text-[11px] font-bold text-amber-600 uppercase tracking-wider block">Pendientes</span>
              <span className="text-2xl font-black text-amber-600 mt-1 block">{pendientesCompras}</span>
            </div>
            <div className="bg-white p-4 rounded-2xl border border-emerald-200/80 shadow-sm">
              <span className="text-[11px] font-bold text-emerald-600 uppercase tracking-wider block">Completadas</span>
              <span className="text-2xl font-black text-emerald-600 mt-1 block">{completadasCompras}</span>
            </div>
            <div className="bg-white p-4 rounded-2xl border border-rose-200/80 shadow-sm">
              <span className="text-[11px] font-bold text-rose-600 uppercase tracking-wider block">Canceladas</span>
              <span className="text-2xl font-black text-rose-600 mt-1 block">{canceladasCompras}</span>
            </div>
            <div className="bg-white p-4 rounded-2xl border border-indigo-200/80 shadow-sm col-span-2 md:col-span-1">
              <span className="text-[11px] font-bold text-indigo-600 uppercase tracking-wider block">Saldo Adeudado</span>
              <span className="text-xl font-black text-indigo-600 mt-1 block">${deudaTotal.toLocaleString()}</span>
            </div>
          </div>

          {/* BARRA DE FILTROS Y BOTÓN NUEVA COMPRA */}
          <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row gap-3 justify-between items-center">
            <div className="flex flex-1 flex-wrap gap-2.5 w-full">
              <div className="relative flex-1 min-w-[200px]">
                <Search className="w-4 h-4 text-slate-400 absolute left-3 top-3.5" />
                <input
                  type="text"
                  placeholder="Buscar por remito, proveedor..."
                  value={comprasSearch}
                  onChange={(e) => setComprasSearch(e.target.value)}
                  className="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-blue-500"
                />
              </div>

              <select
                value={comprasProveedorFilter}
                onChange={(e) => setComprasProveedorFilter(e.target.value)}
                className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-blue-500"
              >
                <option value="">Todos los proveedores</option>
                {suppliers.map(s => (
                  <option key={s.id} value={s.id}>{s.name}</option>
                ))}
              </select>

              <select
                value={comprasEstadoFilter}
                onChange={(e) => setComprasEstadoFilter(e.target.value)}
                className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-blue-500"
              >
                <option value="">Todos los estados</option>
                <option value="Pendiente">Pendiente</option>
                <option value="Recibido">Recibido / Completado</option>
                <option value="Cancelado">Cancelado</option>
              </select>
            </div>

            <div className="flex items-center gap-2 shrink-0">
              <button
                onClick={handleRefrescarCompras}
                disabled={isRefreshing}
                className="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl flex items-center gap-1.5 cursor-pointer transition-all"
                title="Refrescar compras y pedidos"
              >
                <RotateCw className={`w-4 h-4 text-blue-600 ${isRefreshing ? 'animate-spin' : ''}`} />
                <span>Refrescar</span>
              </button>

              <button
                onClick={() => {
                  setNuevaCompraProvId(suppliers[0]?.id || '');
                  setNuevaCompraComprobante('');
                  setNuevaCompraItems([]);
                  setShowNuevaCompraModal(true);
                }}
                className="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-black text-sm rounded-xl flex items-center gap-2 shadow-sm shrink-0 cursor-pointer"
              >
                <Plus className="w-4 h-4" />
                <span>Registrar Compra</span>
              </button>
            </div>
          </div>

          {/* TABLA DE COMPRAS */}
          <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-extrabold text-[11px] uppercase tracking-wider">
                  <tr>
                    <th className="p-4">N° / Remito</th>
                    <th className="p-4">Proveedor</th>
                    <th className="p-4">Fecha</th>
                    <th className="p-4">Total</th>
                    <th className="p-4">Saldo Pendiente</th>
                    <th className="p-4">Estado</th>
                    <th className="p-4 text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {filteredPurchases.length === 0 ? (
                    <tr>
                      <td colSpan={7} className="p-8 text-center text-slate-400 font-bold">
                        No hay compras registradas con los filtros seleccionados.
                      </td>
                    </tr>
                  ) : (
                    filteredPurchases.map((compra) => {
                      const isCancelada = compra.status === 'Cancelado' || compra.estado === 'cancelada';
                      const isCompletada = compra.status === 'Recibido' || compra.estado === 'completada';
                      return (
                        <tr key={compra.id} className="hover:bg-slate-50/80 transition-colors font-medium">
                          <td className="p-4 font-black text-slate-800">
                            {compra.numero_compra || compra.invoiceNumber || compra.id}
                          </td>
                          <td className="p-4 font-bold text-slate-700">
                            {compra.supplierName || compra.proveedor}
                          </td>
                          <td className="p-4 text-slate-500 font-semibold">{formatDateTime(compra.date || compra.fecha_compra)}</td>
                          <td className="p-4 font-black text-slate-900">${compra.total.toLocaleString()}</td>
                          <td className="p-4 font-bold text-rose-600">
                            {(() => {
                              const s = compra.saldo_pendiente !== undefined 
                                ? compra.saldo_pendiente 
                                : (compra.debtAmount !== undefined ? compra.debtAmount : (compra.total - (compra.paidAmount || 0)));
                              return s > 0 ? `$${s.toLocaleString()}` : '$0';
                            })()}
                          </td>
                          <td className="p-4">
                            <span className={`px-2.5 py-1 rounded-full text-xs font-black inline-flex items-center gap-1.5 ${
                              isCompletada 
                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' 
                                : isCancelada 
                                ? 'bg-rose-50 text-rose-700 border border-rose-200' 
                                : 'bg-amber-50 text-amber-700 border border-amber-200'
                            }`}>
                              <span className={`w-1.5 h-1.5 rounded-full ${
                                isCompletada ? 'bg-emerald-500' : isCancelada ? 'bg-rose-500' : 'bg-amber-500'
                              }`} />
                              {compra.status || compra.estado}
                            </span>
                          </td>
                          <td className="p-4 text-right flex justify-end items-center gap-1.5">
                            <button
                              onClick={() => setCompraDetalleSeleccionada(compra)}
                              className="px-3 py-1.5 bg-slate-100 hover:bg-blue-50 text-blue-600 font-black rounded-lg text-xs transition-colors cursor-pointer"
                            >
                              Ver Detalle
                            </button>
                            <button
                              onClick={() => {
                                setCompraDetalleSeleccionada(compra);
                                setShowRecepcionModal(true);
                              }}
                              className="p-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold cursor-pointer"
                              title="Recepción parcial con lotes"
                            >
                              <PackageCheck className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => {
                                setCompraDetalleSeleccionada(compra);
                                setShowPagoModal(true);
                              }}
                              className="p-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold cursor-pointer"
                              title="Registrar pago"
                            >
                              <CreditCard className="w-4 h-4" />
                            </button>
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

      {/* ========================================================= */}
      {/* PESTAÑA 2: ÓRDENES DE COMPRA                              */}
      {/* ========================================================= */}
      {activeTab === 'ORDENES' && (
        <div className="space-y-6">
          <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row gap-3 justify-between items-center">
            <div className="flex flex-1 flex-wrap gap-3 w-full">
              <div className="relative flex-1 min-w-[200px]">
                <Search className="w-4 h-4 text-slate-400 absolute left-3 top-3.5" />
                <input
                  type="text"
                  placeholder="Buscar órdenes..."
                  value={ordenesSearch}
                  onChange={(e) => setOrdenesSearch(e.target.value)}
                  className="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold"
                />
              </div>

              <select
                value={ordenesEstadoFilter}
                onChange={(e) => setOrdenesEstadoFilter(e.target.value)}
                className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700"
              >
                <option value="">Filtrar por Estado: Todos</option>
                <option value="pendiente">Pendiente</option>
                <option value="enviada">Enviada</option>
                <option value="confirmada">Confirmada</option>
                <option value="rechazada">Rechazada</option>
                <option value="cumplida">Cumplida</option>
                <option value="cancelada">Cancelada</option>
              </select>
            </div>

            <button
              onClick={() => {
                setNuevaOrdenProvId(suppliers[0]?.id || '');
                setNuevaOrdenItems([]);
                setShowNuevaOrdenModal(true);
              }}
              className="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-sm rounded-xl flex items-center gap-2 shadow-sm shrink-0 cursor-pointer"
            >
              <Plus className="w-4 h-4" />
              <span>Emitir Orden de Compra</span>
            </button>
          </div>

          <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-extrabold text-[11px] uppercase tracking-wider">
                  <tr>
                    <th className="p-4">N° Orden</th>
                    <th className="p-4">Proveedor</th>
                    <th className="p-4">Fecha emisión</th>
                    <th className="p-4">Total estimado</th>
                    <th className="p-4">Estado</th>
                    <th className="p-4 text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {ordenesCompra.length === 0 ? (
                    <tr>
                      <td colSpan={6} className="p-8 text-center text-slate-400 font-bold">
                        {loadingData ? 'Cargando órdenes...' : 'No hay órdenes de compra registradas.'}
                      </td>
                    </tr>
                  ) : (
                    ordenesCompra.map((orden) => (
                      <tr key={orden.id_orden_compra || orden.id} className="hover:bg-slate-50 font-medium">
                        <td className="p-4 font-black text-slate-800">
                          {orden.numero_orden || `#${orden.id_orden_compra}`}
                        </td>
                        <td className="p-4 font-bold text-slate-700">{orden.proveedor_nombre || orden.proveedor?.razon_social}</td>
                        <td className="p-4 text-slate-500 font-semibold">{formatDateTime(orden.fecha_emision || orden.fecha_creacion)}</td>
                        <td className="p-4 font-black text-slate-900">${Number(orden.total_estimado || 0).toLocaleString()}</td>
                        <td className="p-4">
                          <span className="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-full text-xs font-black">
                            {orden.estado}
                          </span>
                        </td>
                        <td className="p-4 text-right flex justify-end gap-2">
                          {orden.estado === 'pendiente' && (
                            <button
                              onClick={() => handleEnviarOrden(orden.id_orden_compra)}
                              className="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs"
                            >
                              Enviar
                            </button>
                          )}
                          {orden.estado === 'pendiente' && (
                            <button
                              onClick={() => handleCancelarOrden(orden.id_orden_compra)}
                              className="px-2.5 py-1 bg-rose-50 text-rose-600 hover:bg-rose-100 font-bold rounded-lg text-xs"
                            >
                              Cancelar
                            </button>
                          )}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* ========================================================= */}
      {/* PESTAÑA 3: SUGERENCIAS DE REPOSICIÓN                      */}
      {/* ========================================================= */}
      {activeTab === 'SUGERENCIAS' && (
        <div className="space-y-6">
          <div className="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 rounded-3xl text-white shadow-lg flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
              <h2 className="text-2xl font-black flex items-center gap-2">
                <Sparkles className="w-6 h-6 text-amber-300" />
                <span>Generador Automático de Sugerencias</span>
              </h2>
              <p className="text-sm text-blue-100 mt-1 max-w-xl">
                El sistema detecta automáticamente los productos cuyo stock actual sea igual o menor al stock mínimo configurado y calcula la cantidad ideal de reposición.
              </p>
            </div>
            <button
              onClick={handleGenerarSugerencias}
              className="px-6 py-3 bg-white text-blue-600 hover:bg-blue-50 font-black rounded-2xl shadow-md cursor-pointer transition-all shrink-0"
            >
              Recalcular Sugerencias
            </button>
          </div>

          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-4 border-b border-slate-100 font-black text-slate-800 text-base">
              Productos con Necesidad de Reposición
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 text-slate-500 font-extrabold text-[11px] uppercase">
                  <tr>
                    <th className="p-4">Producto</th>
                    <th className="p-4">Stock Actual</th>
                    <th className="p-4">Stock Mínimo</th>
                    <th className="p-4">Cant. Sugerida</th>
                    <th className="p-4">Proveedor Sugerido</th>
                    <th className="p-4 text-right">Acción</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 font-medium">
                  {sugerencias.length === 0 ? (
                    <tr>
                      <td colSpan={6} className="p-8 text-center text-slate-400 font-bold">
                        No hay productos con stock crítico en este momento.
                      </td>
                    </tr>
                  ) : (
                    sugerencias.map((sug, idx) => (
                      <tr key={idx} className="hover:bg-slate-50">
                        <td className="p-4 font-black text-slate-800">{sug.producto_nombre || sug.nombre}</td>
                        <td className="p-4 font-bold text-rose-600">{sug.stock_actual}</td>
                        <td className="p-4 text-slate-500">{sug.stock_minimo}</td>
                        <td className="p-4 font-black text-emerald-600">{sug.cantidad_sugerida} u.</td>
                        <td className="p-4 font-semibold text-slate-700">{sug.proveedor_nombre || 'Sin proveedor asignado'}</td>
                        <td className="p-4 text-right">
                          <button
                            onClick={() => alert(`Orden sugerida para ${sug.producto_nombre}`)}
                            className="px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 font-black rounded-xl text-xs"
                          >
                            Crear Orden Directa
                          </button>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* MODAL NUEVA COMPRA */}
      {showNuevaCompraModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div className="flex justify-between items-center border-b border-slate-100 pb-4 mb-4">
              <h3 className="text-xl font-black text-slate-800">Registrar Compra de Mercadería</h3>
              <button onClick={() => setShowNuevaCompraModal(false)} className="text-slate-400 hover:text-slate-600">
                <X className="w-6 h-6" />
              </button>
            </div>

            {compraError && (
              <div className="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold rounded-xl">
                {compraError}
              </div>
            )}

            <form onSubmit={handleGuardarCompra} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase">Proveedor *</label>
                  <select
                    value={nuevaCompraProvId}
                    onChange={(e) => setNuevaCompraProvId(e.target.value)}
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                    required
                  >
                    <option value="">Seleccione proveedor</option>
                    {suppliers.map(s => (
                      <option key={s.id} value={s.id_proveedor || s.id}>{s.name}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase">N° Remito / Factura</label>
                  <input
                    type="text"
                    value={nuevaCompraComprobante}
                    onChange={(e) => setNuevaCompraComprobante(e.target.value)}
                    placeholder="Ej: REM-00045"
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                  />
                </div>
              </div>

              {/* AGREGAR PRODUCTOS */}
              <div className="p-4 bg-slate-50 border border-slate-200/80 rounded-2xl space-y-3">
                <span className="text-xs font-black text-slate-700 uppercase block">Agregar Ítems a la Compra</span>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-2">
                  <div className="md:col-span-2">
                    <select
                      value={itemProdId}
                      onChange={(e) => {
                        setItemProdId(e.target.value);
                        const p = products.find(prod => prod.id === e.target.value || String(prod.id_producto) === e.target.value);
                        if (p) setItemPrecio(p.cost);
                      }}
                      className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold"
                    >
                      <option value="">Seleccione producto</option>
                      {products.map(p => (
                        <option key={p.id} value={p.id}>{p.name} (Stock: {p.stock})</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <input
                      type="number"
                      min={1}
                      placeholder="Cant."
                      value={itemCantidad}
                      onChange={(e) => setItemCantidad(Number(e.target.value))}
                      className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold"
                    />
                  </div>
                  <div>
                    <input
                      type="number"
                      min={0}
                      placeholder="Precio costo"
                      value={itemPrecio}
                      onChange={(e) => setItemPrecio(Number(e.target.value))}
                      className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold"
                    />
                  </div>
                </div>
                <button
                  type="button"
                  onClick={handleAddItemToCompra}
                  className="w-full py-2 bg-slate-200 hover:bg-slate-300 font-black text-xs text-slate-700 rounded-xl cursor-pointer"
                >
                  ＋ Agregar Producto a la Lista
                </button>
              </div>

              {/* LISTADO DE ITEMS AGREGADOS */}
              {nuevaCompraItems.length > 0 && (
                <div className="border border-slate-200 rounded-xl overflow-hidden">
                  <table className="w-full text-xs">
                    <thead className="bg-slate-100 font-extrabold text-slate-500 uppercase">
                      <tr>
                        <th className="p-2.5 text-left">Producto</th>
                        <th className="p-2.5">Cant.</th>
                        <th className="p-2.5">Costo U.</th>
                        <th className="p-2.5">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 font-bold">
                      {nuevaCompraItems.map((it, idx) => (
                        <tr key={idx}>
                          <td className="p-2.5 text-slate-800">{it.productName}</td>
                          <td className="p-2.5 text-center">{it.quantity}</td>
                          <td className="p-2.5 text-center">${it.costPrice.toLocaleString()}</td>
                          <td className="p-2.5 text-right font-black text-emerald-600">${it.total.toLocaleString()}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}

              <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowNuevaCompraModal(false)}
                  className="px-4 py-2 text-slate-600 font-bold text-sm"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-black text-sm rounded-xl cursor-pointer shadow-md"
                >
                  Confirmar y Guardar Compra
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL DETALLE DE COMPRA Y ACCIONES */}
      {compraDetalleSeleccionada && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-3xl max-w-3xl w-full p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div className="flex justify-between items-center border-b border-slate-100 pb-4 mb-4">
              <div>
                <h3 className="text-xl font-black text-slate-800">
                  Detalle de la Compra #{compraDetalleSeleccionada.numero_compra || compraDetalleSeleccionada.id}
                </h3>
                <span className="text-xs font-bold text-slate-500">
                  Proveedor: {compraDetalleSeleccionada.supplierName || compraDetalleSeleccionada.proveedor}
                </span>
              </div>
              <button onClick={() => setCompraDetalleSeleccionada(null)} className="text-slate-400 hover:text-slate-600">
                <X className="w-6 h-6" />
              </button>
            </div>

            {/* BOTONES DE ACCIÓN RÁPIDA */}
            <div className="flex flex-wrap gap-2.5 mb-5 p-3 bg-slate-50 rounded-2xl border border-slate-100">
              <button
                onClick={() => setShowRecepcionModal(true)}
                className="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl flex items-center gap-1.5 cursor-pointer"
              >
                <Truck className="w-4 h-4" />
                <span>Registrar Recepción Parcial</span>
              </button>

              <button
                onClick={() => setShowPagoModal(true)}
                className="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs rounded-xl flex items-center gap-1.5 cursor-pointer"
              >
                <CreditCard className="w-4 h-4" />
                <span>Registrar Pago</span>
              </button>

              {compraDetalleSeleccionada.status !== 'Cancelado' && (
                <button
                  onClick={() => handleCancelarCompra(compraDetalleSeleccionada.id_compra || compraDetalleSeleccionada.id)}
                  className="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 font-black text-xs rounded-xl flex items-center gap-1.5 cursor-pointer"
                >
                  <XCircle className="w-4 h-4" />
                  <span>Cancelar Compra</span>
                </button>
              )}
            </div>

            {/* TABLA DE ÍTEMS DE LA COMPRA */}
            <div className="border border-slate-200 rounded-2xl overflow-hidden mb-4">
              <table className="w-full text-xs text-left">
                <thead className="bg-slate-100 text-slate-500 font-black uppercase">
                  <tr>
                    <th className="p-3">Producto</th>
                    <th className="p-3 text-center">Cant. Solicitada</th>
                    <th className="p-3 text-center">Precio Unitario</th>
                    <th className="p-3 text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 font-bold">
                  {compraDetalleSeleccionada.items?.map((it: any, i: number) => (
                    <tr key={i}>
                      <td className="p-3 text-slate-800">{it.productName || it.producto?.nombre}</td>
                      <td className="p-3 text-center">{it.quantity || it.cantidad}</td>
                      <td className="p-3 text-center">${(it.costPrice || it.precio_unitario || 0).toLocaleString()}</td>
                      <td className="p-3 text-right text-emerald-600 font-black">
                        ${(it.total || (it.cantidad * it.precio_unitario) || 0).toLocaleString()}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="flex justify-between items-center text-sm font-black p-4 bg-slate-50 rounded-2xl">
              <span className="text-slate-600">Total de la Compra:</span>
              <span className="text-xl text-slate-900">${(compraDetalleSeleccionada.total || compraDetalleSeleccionada.importe_total || 0).toLocaleString()}</span>
            </div>
          </div>
        </div>
      )}

      {/* MODAL REGISTRAR PAGO */}
      {showPagoModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl">
            <h3 className="text-lg font-black text-slate-800 mb-3">Registrar Pago de Compra</h3>
            {pagoError && <div className="p-2 mb-3 bg-rose-50 text-rose-600 text-xs font-bold rounded-lg">{pagoError}</div>}
            
            <form onSubmit={handleGuardarPago} className="space-y-3">
              <div>
                <label className="text-xs font-bold text-slate-500 uppercase">Monto a Pagar ($)</label>
                <input
                  type="number"
                  step="0.01"
                  min={0.01}
                  value={pagoMonto}
                  onChange={(e) => setPagoMonto(Number(e.target.value))}
                  className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                  required
                />
              </div>

              <div>
                <label className="text-xs font-bold text-slate-500 uppercase">Forma de Pago</label>
                <select
                  value={pagoForma}
                  onChange={(e) => setPagoForma(e.target.value as any)}
                  className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                >
                  <option value="efectivo">Efectivo</option>
                  <option value="transferencia">Transferencia bancaria</option>
                  <option value="cheque">Cheque</option>
                </select>
              </div>

              <div>
                <label className="text-xs font-bold text-slate-500 uppercase">N° Comprobante / Referencia</label>
                <input
                  type="text"
                  value={pagoComprobante}
                  onChange={(e) => setPagoComprobante(e.target.value)}
                  placeholder="Ej: TRANSF-88910"
                  className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3">
                <button type="button" onClick={() => setShowPagoModal(false)} className="px-4 py-2 font-bold text-xs text-slate-500">
                  Cancelar
                </button>
                <button type="submit" className="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs rounded-xl shadow-md">
                  Confirmar Pago
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL REGISTRAR RECEPCIÓN */}
      {showRecepcionModal && compraDetalleSeleccionada && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl">
            <h3 className="text-lg font-black text-slate-800 mb-1">Registrar Recepción Parcial</h3>
            <p className="text-xs text-slate-500 mb-4">Indique la cantidad recibida físicamente para cada producto.</p>
            {recepcionError && <div className="p-2 mb-3 bg-rose-50 text-rose-600 text-xs font-bold rounded-lg">{recepcionError}</div>}

            <form onSubmit={handleGuardarRecepcion} className="space-y-4">
              <div className="space-y-2 max-h-60 overflow-y-auto">
                {compraDetalleSeleccionada.items?.map((it: any) => {
                  const idDet = it.id_detalle || it.id_producto || 1;
                  return (
                    <div key={idDet} className="flex justify-between items-center p-3 bg-slate-50 rounded-xl">
                      <div>
                        <span className="font-bold text-xs text-slate-800 block">{it.productName || it.producto?.nombre}</span>
                        <span className="text-[10px] text-slate-500">Total: {it.quantity || it.cantidad}</span>
                      </div>
                      <input
                        type="number"
                        min={0}
                        max={it.quantity || it.cantidad}
                        placeholder="Recibido"
                        value={recepcionCantidades[idDet] || ''}
                        onChange={(e) => setRecepcionCantidades({ ...recepcionCantidades, [idDet]: Number(e.target.value) })}
                        className="w-20 px-2 py-1 bg-white border border-slate-200 rounded-lg text-sm text-center font-bold"
                      />
                    </div>
                  );
                })}
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onClick={() => setShowRecepcionModal(false)} className="px-4 py-2 font-bold text-xs text-slate-500">
                  Cancelar
                </button>
                <button type="submit" className="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl shadow-md">
                  Ingresar a Inventario
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
