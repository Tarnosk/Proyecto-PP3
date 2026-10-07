import React, { useState, useEffect } from 'react';
import { 
  User, 
  Product, 
  PriceList, 
  Customer, 
  Promotion, 
  OrderItem, 
  Order,
  CustomerAccount
} from '../../types';
import { 
  ShoppingBag, 
  Plus, 
  Search, 
  Trash2, 
  Tag, 
  Sparkles, 
  Receipt, 
  AlertTriangle, 
  CheckCircle2,
  Percent,
  X,
  Wallet,
  User as UserIcon,
  RotateCcw,
  Edit2,
  ArrowUpRight,
  Sparkle,
  Barcode,
  Coins
} from 'lucide-react';

interface SalesPOSProps {
  currentUser: User;
  products: Product[];
  priceLists: PriceList[];
  customers: Customer[];
  promotions: Promotion[];
  onSaveOrder: (orderData: {
    customer: Customer;
    items: OrderItem[];
    globalDiscountType: 'PERCENTAGE' | 'FIXED';
    globalDiscountValue: number;
    paymentSplit: Record<string, number>;
  }) => void;
  onCerrarCaja: () => void;
}

export function SalesPOS({
  currentUser,
  products,
  priceLists,
  customers,
  promotions,
  onSaveOrder,
  onCerrarCaja
}: SalesPOSProps) {
  // Estado de Carrito y Cliente
  const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(null);
  const [cartItems, setCartItems] = useState<OrderItem[]>([]);
  const [productSearch, setProductSearch] = useState('');
  
  // Modales
  const [showClientSearch, setShowClientSearch] = useState(false);
  const [showNewClient, setShowNewClient] = useState(false);
  const [showGlobalDiscount, setShowGlobalDiscount] = useState(false);
  const [showCheckout, setShowCheckout] = useState(false);
  
  // Alertas
  const [errorMsg, setErrorMsg] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  // Cuenta Corriente del cliente seleccionado
  const [customerAccounts, setCustomerAccounts] = useState<CustomerAccount[]>([]);
  useEffect(() => {
    const saved = localStorage.getItem('erp_distribuidora_customer_accounts');
    if (saved) {
      try {
        setCustomerAccounts(JSON.parse(saved));
      } catch (e) {
        console.error(e);
      }
    }
  }, [showCheckout]); // Recargar al cobrar o cambiar de estado

  const activeAccount = selectedCustomer 
    ? customerAccounts.find(a => a.customerId === selectedCustomer.id) 
    : null;

  // Estado para Nuevo Cliente Rápido
  const [newClientName, setNewClientName] = useState('');
  const [newClientCuit, setNewClientCuit] = useState('');
  const [newClientAddress, setNewClientAddress] = useState('');
  const [newClientPhone, setNewClientPhone] = useState('');
  const [newClientIva, setNewClientIva] = useState('Monotributista');
  const [newClientPriceListId, setNewClientPriceListId] = useState('1');

  // Búsqueda de clientes
  const [clientQuery, setClientQuery] = useState('');
  const filteredCustomers = customers.filter(c => 
    c.name.toLowerCase().includes(clientQuery.toLowerCase()) ||
    c.cuit.includes(clientQuery)
  );

  // Descuento Global
  const [globalDiscountType, setGlobalDiscountType] = useState<'PERCENTAGE' | 'FIXED'>('PERCENTAGE');
  const [globalDiscountValue, setGlobalDiscountValue] = useState(0);

  // Cobranza Split State
  const [paymentAmounts, setPaymentAmounts] = useState<Record<string, number>>({
    'Efectivo': 0,
    'Débito': 0,
    'Crédito': 0,
    'Transferencia': 0,
    'Mercado Pago QR': 0,
    'Cuenta Corriente': 0
  });

  // Ventas Suspendidas (Borradores)
  const [suspendedSales, setSuspendedSales] = useState<{ id: string; customer: Customer; items: OrderItem[]; date: string }[]>([]);
  
  useEffect(() => {
    const saved = localStorage.getItem('erp_distribuidora_suspended_sales');
    if (saved) {
      try { setSuspendedSales(JSON.parse(saved)); } catch (e) { console.error(e); }
    }
  }, []);

  const saveSuspendedSales = (updated: typeof suspendedSales) => {
    setSuspendedSales(updated);
    localStorage.setItem('erp_distribuidora_suspended_sales', JSON.stringify(updated));
  };

  // Precios según lista de precio
  const getPriceForProductAndList = (prod: Product, listId: string): number => {
    if (listId === '1') return prod.price; // Retail Base
    if (listId === '2') return prod.priceWholesale ?? Number((prod.price * 0.85).toFixed(2)); // Mayorista
    if (listId === '3') return prod.priceDistributor ?? Number((prod.price * 0.75).toFixed(2)); // Distribuidor
    if (listId === '4' || listId === 'promocional') return prod.pricePromo ?? Number((prod.price * 0.90).toFixed(2)); // Promocional
    
    // Fallback por porcentaje de descuento genérico
    const list = priceLists.find(l => l.id === listId);
    if (!list) return prod.price;
    const discount = prod.price * (list.discountPercentage / 100);
    return Number((prod.price - discount).toFixed(2));
  };

  // Buscar Producto Filtrados
  const filteredProducts = products.filter(p => 
    p.name.toLowerCase().includes(productSearch.toLowerCase()) ||
    p.code.toLowerCase().includes(productSearch.toLowerCase()) ||
    (p.barcode && p.barcode.includes(productSearch))
  );

  // Agregar Producto al Carrito
  const handleAddProduct = (prod: Product, preferredListId?: string) => {
    setErrorMsg('');
    const listId = preferredListId || (selectedCustomer ? selectedCustomer.priceListId : '1');
    const unitPrice = getPriceForProductAndList(prod, listId);

    const existing = cartItems.find(item => item.productId === prod.id && item.priceListId === listId);
    const quantityInCart = existing ? existing.quantity : 0;

    if (quantityInCart + 1 > prod.stock) {
      setErrorMsg(`Atención: Stock insuficiente para agregar "${prod.name}". Disponible: ${prod.stock}`);
      return;
    }

    if (existing) {
      setCartItems(cartItems.map(item => 
        item.productId === prod.id && item.priceListId === listId
          ? { ...item, quantity: item.quantity + 1, total: Number(((item.quantity + 1) * item.unitPrice * (1 - (item.discountPercentage || 0)/100) - (item.discountAmount || 0)).toFixed(2)) }
          : item
      ));
    } else {
      setCartItems([...cartItems, {
        productId: prod.id,
        productName: prod.name,
        quantity: 1,
        unitPrice: unitPrice,
        priceListId: listId,
        discountPercentage: 0,
        discountAmount: 0,
        total: unitPrice
      }]);
    }
    setProductSearch('');
  };

  // Modificar Cantidad Inline
  const handleUpdateQty = (prodId: string, listId: string, qty: number) => {
    setErrorMsg('');
    const prod = products.find(p => p.id === prodId);
    if (!prod) return;

    if (qty > prod.stock) {
      setErrorMsg(`Atención: Stock insuficiente para "${prod.name}". Disponible: ${prod.stock}`);
      return;
    }

    if (qty <= 0) {
      setCartItems(cartItems.filter(item => !(item.productId === prodId && item.priceListId === listId)));
    } else {
      setCartItems(cartItems.map(item => {
        if (item.productId === prodId && item.priceListId === listId) {
          const discountPct = item.discountPercentage || 0;
          const discountFixed = item.discountAmount || 0;
          const discountedUnit = item.unitPrice * (1 - discountPct / 100);
          const totalVal = Number((qty * discountedUnit - discountFixed).toFixed(2));
          return { ...item, quantity: qty, total: Math.max(0, totalVal) };
        }
        return item;
      }));
    }
  };

  // Modificar Lista de Precios Individual en Carrito
  const handleUpdateItemPriceList = (prodId: string, currentListId: string, newListId: string) => {
    const prod = products.find(p => p.id === prodId);
    if (!prod) return;

    const newUnitPrice = getPriceForProductAndList(prod, newListId);
    
    // Si ya existe ese producto con la nueva lista, los unimos, sino actualizamos el actual
    const targetExisting = cartItems.find(item => item.productId === prodId && item.priceListId === newListId);
    
    if (targetExisting && currentListId !== newListId) {
      // Unir cantidades
      const newQty = targetExisting.quantity + cartItems.find(item => item.productId === prodId && item.priceListId === currentListId)!.quantity;
      if (newQty > prod.stock) {
        setErrorMsg(`No se puede cambiar la lista de precios ya que superaría el stock disponible de ${prod.name}.`);
        return;
      }
      const itemWithoutOld = cartItems.filter(item => !(item.productId === prodId && item.priceListId === currentListId));
      setCartItems(itemWithoutOld.map(item => 
        item.productId === prodId && item.priceListId === newListId
          ? { 
              ...item, 
              quantity: newQty, 
              unitPrice: newUnitPrice,
              total: Number((newQty * newUnitPrice * (1 - (item.discountPercentage || 0)/100) - (item.discountAmount || 0)).toFixed(2))
            }
          : item
      ));
    } else {
      setCartItems(cartItems.map(item => {
        if (item.productId === prodId && item.priceListId === currentListId) {
          const discountPct = item.discountPercentage || 0;
          const discountFixed = item.discountAmount || 0;
          const discountedUnit = newUnitPrice * (1 - discountPct / 100);
          const totalVal = Number((item.quantity * discountedUnit - discountFixed).toFixed(2));
          return { 
            ...item, 
            priceListId: newListId, 
            unitPrice: newUnitPrice, 
            total: Math.max(0, totalVal) 
          };
        }
        return item;
      }));
    }
  };

  // Modificar Descuento Individual
  const handleUpdateItemDiscount = (prodId: string, listId: string, pct: number, fixed: number) => {
    setCartItems(cartItems.map(item => {
      if (item.productId === prodId && item.priceListId === listId) {
        const discountedUnit = item.unitPrice * (1 - pct / 100);
        const totalVal = Number((item.quantity * discountedUnit - fixed).toFixed(2));
        return { 
          ...item, 
          discountPercentage: pct, 
          discountAmount: fixed, 
          total: Math.max(0, totalVal) 
        };
      }
      return item;
    }));
  };

  // Totales de la Orden
  const cartSubtotal = cartItems.reduce((acc, item) => acc + item.total, 0);
  const finalDiscount = globalDiscountType === 'PERCENTAGE' 
    ? Number((cartSubtotal * (globalDiscountValue / 100)).toFixed(2)) 
    : globalDiscountValue;
  const cartTotal = Math.max(0, cartSubtotal - finalDiscount);

  // Suspender Venta Actual
  const handleSuspendSale = () => {
    if (!selectedCustomer) {
      setErrorMsg('Debe seleccionar un cliente antes de suspender la venta.');
      return;
    }
    if (cartItems.length === 0) {
      setErrorMsg('El carrito está vacío.');
      return;
    }

    const newSuspended = {
      id: `SUS-${Date.now().toString().slice(-4)}`,
      customer: selectedCustomer,
      items: cartItems,
      date: new Date().toISOString().replace('T', ' ').slice(0, 16)
    };

    saveSuspendedSales([newSuspended, ...suspendedSales]);
    
    // Limpiar pantalla
    setSelectedCustomer(null);
    setCartItems([]);
    setGlobalDiscountValue(0);
    setSuccessMsg(`Venta suspendida exitosamente como ${newSuspended.id}`);
    setTimeout(() => setSuccessMsg(''), 3000);
  };

  // Recuperar Venta Suspendida
  const handleResumeSale = (sale: typeof suspendedSales[0]) => {
    setSelectedCustomer(sale.customer);
    setCartItems(sale.items);
    saveSuspendedSales(suspendedSales.filter(s => s.id !== sale.id));
    setSuccessMsg(`Venta suspendida ${sale.id} recuperada.`);
    setTimeout(() => setSuccessMsg(''), 2000);
  };

  // Aplicar Promociones/Combos del sistema de referencia
  const handleApplyPromotion = (promo: Promotion) => {
    if (!selectedCustomer) {
      setErrorMsg('Debe seleccionar un cliente antes de aplicar un combo.');
      return;
    }

    // Si es combo con múltiples productos
    if (promo.items && promo.items.length > 0) {
      // Verificar stock
      for (const item of promo.items) {
        const prod = products.find(p => p.id === item.productId);
        if (!prod || prod.stock < item.quantity) {
          setErrorMsg(`Stock insuficiente de "${prod?.name || 'Producto'}" para aplicar este combo.`);
          return;
        }
      }

      // Añadir productos
      const updated = [...cartItems];
      promo.items.forEach(pItem => {
        const prod = products.find(p => p.id === pItem.productId);
        if (!prod) return;

        const listId = selectedCustomer.priceListId;
        const unitPrice = getPriceForProductAndList(prod, listId);
        const discountPct = promo.discountPercentage || 0;
        const discountedUnit = unitPrice * (1 - discountPct / 100);

        const existingIdx = updated.findIndex(it => it.productId === prod.id && it.priceListId === listId);
        if (existingIdx > -1) {
          const newQty = updated[existingIdx].quantity + pItem.quantity;
          updated[existingIdx] = {
            ...updated[existingIdx],
            quantity: newQty,
            discountPercentage: discountPct,
            total: Number((newQty * discountedUnit).toFixed(2))
          };
        } else {
          updated.push({
            productId: prod.id,
            productName: prod.name,
            quantity: pItem.quantity,
            unitPrice: unitPrice,
            priceListId: listId,
            discountPercentage: discountPct,
            total: Number((pItem.quantity * discountedUnit).toFixed(2))
          });
        }
      });

      setCartItems(updated);
      setSuccessMsg(`Combo "${promo.name}" añadido al carrito.`);
      setTimeout(() => setSuccessMsg(''), 2000);
    } else if (promo.requiredProductId && promo.requiredQuantity) {
      // Regla de descuento tradicional por volumen
      const prod = products.find(p => p.id === promo.requiredProductId);
      if (prod) {
        const listId = selectedCustomer.priceListId;
        const unitPrice = getPriceForProductAndList(prod, listId);
        const discountPct = promo.discountPercentage;
        const qty = promo.requiredQuantity;

        const updated = [...cartItems];
        const existingIdx = updated.findIndex(it => it.productId === prod.id && it.priceListId === listId);
        if (existingIdx > -1) {
          const newQty = Math.max(qty, updated[existingIdx].quantity);
          updated[existingIdx] = {
            ...updated[existingIdx],
            quantity: newQty,
            discountPercentage: discountPct,
            total: Number((newQty * unitPrice * (1 - discountPct / 100)).toFixed(2))
          };
        } else {
          updated.push({
            productId: prod.id,
            productName: prod.name,
            quantity: qty,
            unitPrice: unitPrice,
            priceListId: listId,
            discountPercentage: discountPct,
            total: Number((qty * unitPrice * (1 - discountPct / 100)).toFixed(2))
          });
        }
        setCartItems(updated);
        setSuccessMsg(`Promo "${promo.name}" aplicada.`);
        setTimeout(() => setSuccessMsg(''), 2000);
      }
    }
  };

  // Manejar Cobro Split de Modal Cobrar
  const totalReceived: number = (Object.values(paymentAmounts) as number[]).reduce((acc: number, val: number) => acc + val, 0);
  const remainingPending: number = Math.max(0, cartTotal - totalReceived);
  
  // El vuelto se genera si el total de efectivo cargado supera la diferencia o el total general
  const calculatedChange: number = totalReceived > cartTotal && paymentAmounts['Efectivo'] > 0
    ? Number((totalReceived - cartTotal).toFixed(2))
    : 0;

  const handleOpenCheckoutModal = () => {
    if (!selectedCustomer) {
      setErrorMsg('Debe seleccionar un cliente antes de proceder al cobro.');
      return;
    }
    if (cartItems.length === 0) {
      setErrorMsg('El carrito está vacío.');
      return;
    }

    // Inicializar split payment con todo asignado a Efectivo por comodidad
    setPaymentAmounts({
      'Efectivo': cartTotal,
      'Débito': 0,
      'Crédito': 0,
      'Transferencia': 0,
      'Mercado Pago QR': 0,
      'Cuenta Corriente': 0
    });
    setShowCheckout(true);
  };

  const handleConfirmSplitPayment = () => {
    // Validar saldo restante
    if (remainingPending > 0.1) {
      alert(`Aún resta cubrir un saldo de $${remainingPending.toLocaleString('es-AR')}`);
      return;
    }

    // Ejecutar callback
    onSaveOrder({
      customer: selectedCustomer!,
      items: cartItems,
      globalDiscountType,
      globalDiscountValue,
      paymentSplit: paymentAmounts
    });

    // Limpiar
    setSelectedCustomer(null);
    setCartItems([]);
    setGlobalDiscountValue(0);
    setShowCheckout(false);
  };

  // Crear Cliente Rápido
  const handleCreateQuickCustomer = () => {
    if (!newClientName.trim()) return;
    const newCust: Customer = {
      id: Date.now().toString(),
      name: newClientName.trim(),
      cuit: newClientCuit.trim() || '---',
      address: newClientAddress.trim() || 'Sin dirección declarada',
      phone: newClientPhone.trim() || '---',
      zone: 'Centro',
      priceListId: newClientPriceListId
    };

    // Actualizar localStorage y state
    const saved = localStorage.getItem('erp_distribuidora_customers');
    let list: Customer[] = [];
    if (saved) {
      try { list = JSON.parse(saved); } catch (e) { console.error(e); }
    }
    const updatedList = [newCust, ...list];
    localStorage.setItem('erp_distribuidora_customers', JSON.stringify(updatedList));
    
    // Auto-seleccionar cliente
    setSelectedCustomer(newCust);
    setShowNewClient(false);

    // Reset fields
    setNewClientName('');
    setNewClientCuit('');
    setNewClientAddress('');
    setNewClientPhone('');
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-200">
      
      {/* SECCIÓN IZQUIERDA: CLIENTE, BÚSQUEDA Y PROMOS (COL 7) */}
      <div className="lg:col-span-7 space-y-6">
        
        {/* PANEL CLIENTE SUPERIOR */}
        <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
          <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-3 border-b border-slate-100">
            <div>
              <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block">MÓDULO DE VENTAS / CAJA</span>
              <h2 className="text-xl font-bold text-slate-800 flex items-center gap-2">
                <UserIcon className="w-5 h-5 text-[#0D6EFD]" />
                {selectedCustomer ? selectedCustomer.name : 'Consumidor Final'}
              </h2>
            </div>
            <div className="flex gap-2 w-full sm:w-auto">
              <button
                onClick={() => {
                  setClientQuery('');
                  setShowClientSearch(true);
                }}
                className="flex-1 sm:flex-initial bg-slate-900 hover:bg-black text-white text-xs font-extrabold px-3.5 py-2.5 rounded-xl flex items-center justify-center gap-1.5 transition-all cursor-pointer"
              >
                <Search className="w-3.5 h-3.5" />
                <span>BUSCAR CLIENTE</span>
              </button>
              <button
                onClick={() => setShowNewClient(true)}
                className="flex-1 sm:flex-initial bg-[#0D6EFD] hover:bg-[#0b5ed7] text-white text-xs font-extrabold px-3.5 py-2.5 rounded-xl flex items-center justify-center gap-1.5 transition-all cursor-pointer"
              >
                <Plus className="w-3.5 h-3.5" />
                <span>NUEVO CLIENTE</span>
              </button>
            </div>
          </div>

          {/* FICHA TÉCNICA CLIENTE */}
          {selectedCustomer ? (
            <div className="grid grid-cols-2 md:grid-cols-3 gap-4 text-xs font-semibold text-slate-600 bg-slate-50/70 p-3.5 rounded-xl border border-slate-100">
              <div>
                <span className="text-slate-400 block mb-0.5">CUIT/DNI</span>
                <span className="font-extrabold text-slate-800">{selectedCustomer.cuit}</span>
              </div>
              <div>
                <span className="text-slate-400 block mb-0.5">Condición IVA</span>
                <span className="text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded font-bold">
                  {newClientIva}
                </span>
              </div>
              <div>
                <span className="text-slate-400 block mb-0.5">Domicilio</span>
                <span className="font-extrabold text-slate-800 truncate block">{selectedCustomer.address}</span>
              </div>
              <div>
                <span className="text-slate-400 block mb-0.5">Lista Asignada</span>
                <span className="font-bold text-[#0D6EFD]">
                  {priceLists.find(l => l.id === selectedCustomer.priceListId)?.name || 'Minorista'}
                </span>
              </div>
              <div className="col-span-1 md:col-span-2">
                <span className="text-slate-400 block mb-0.5">Saldo CC</span>
                <span className={`font-black text-sm flex items-center gap-1 ${activeAccount && activeAccount.balance > 0 ? 'text-red-600' : 'text-emerald-600'}`}>
                  <Coins className="w-4 h-4" />
                  {activeAccount ? `$${activeAccount.balance.toLocaleString('es-AR')}` : '$0,00'}
                </span>
              </div>
            </div>
          ) : (
            <p className="text-sm font-semibold text-amber-600 flex items-center gap-1.5 bg-amber-50 p-3 rounded-xl border border-amber-100">
              <AlertTriangle className="w-4 h-4 shrink-0" />
              Asigne un cliente para operar con listas de precios preferenciales y compras en cuenta corriente.
            </p>
          )}
        </div>

        {/* ALERTA MENSAJE */}
        {errorMsg && (
          <div className="p-3 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-bold rounded-r-xl flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 shrink-0" />
            <span>{errorMsg}</span>
          </div>
        )}
        {successMsg && (
          <div className="p-3 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 text-sm font-bold rounded-r-xl flex items-center gap-2">
            <CheckCircle2 className="w-4 h-4 shrink-0" />
            <span>{successMsg}</span>
          </div>
        )}

        {/* BÚSQUEDA RÁPIDA DE PRODUCTOS */}
        <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
          <div className="relative">
            <Search className="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400 w-5 h-5" />
            <input
              type="text"
              placeholder="Buscar por Nombre, Código o Código de Barras..."
              className="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-4 py-3.5 text-sm font-bold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#0D6EFD] focus:bg-white transition-all shadow-inner"
              value={productSearch}
              onChange={(e) => setProductSearch(e.target.value)}
            />
          </div>

          {/* LISTADO DE RESULTADOS EN REJILLA */}
          {productSearch.trim() !== '' && (
            <div className="border border-slate-150 rounded-xl max-h-72 overflow-y-auto divide-y divide-slate-100 bg-white shadow-md">
              {filteredProducts.length === 0 ? (
                <div className="p-4 text-center text-xs font-extrabold text-slate-400 uppercase">
                  Ningún producto coincide.
                </div>
              ) : (
                filteredProducts.map(p => {
                  const defaultListId = selectedCustomer ? selectedCustomer.priceListId : '1';
                  const pMinorista = getPriceForProductAndList(p, '1');
                  const pMayorista = getPriceForProductAndList(p, '2');
                  
                  return (
                    <div 
                      key={p.id} 
                      onClick={() => handleAddProduct(p)}
                      className="p-3.5 hover:bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-2 cursor-pointer transition-colors"
                    >
                      <div>
                        <div className="flex items-center gap-2">
                          <p className="font-extrabold text-slate-800 text-sm">{p.name}</p>
                          <span className="text-[10px] font-black px-1.5 py-0.2 bg-slate-100 text-slate-600 rounded">
                            Cod: {p.code}
                          </span>
                        </div>
                        <p className="text-xs text-slate-400 mt-0.5">
                          Categoría: <span className="font-bold text-slate-500">{p.category}</span> | Stock: <span className={`font-black ${p.stock <= p.minStock ? 'text-red-500' : 'text-slate-600'}`}>{p.stock} unid</span>
                        </p>
                      </div>
                      
                      {/* PRECIOS DE LAS LISTAS */}
                      <div className="flex items-center gap-4 text-right shrink-0">
                        <div>
                          <span className="text-[10px] text-slate-400 block">Minorista</span>
                          <span className="font-black text-slate-700 text-sm">${pMinorista}</span>
                        </div>
                        <div>
                          <span className="text-[10px] text-slate-400 block">Mayorista</span>
                          <span className="font-black text-blue-700 text-sm">${pMayorista}</span>
                        </div>
                        <button className="bg-[#0D6EFD]/10 hover:bg-[#0D6EFD] text-[#0D6EFD] hover:text-white p-1.5 rounded-lg transition-colors">
                          <Plus className="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  );
                })
              )}
            </div>
          )}
        </div>

        {/* COMBOS Y PROMOCIONES ACTIVAS EN ESTA VENTA */}
        <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
          <h3 className="text-sm font-black text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
            <Sparkle className="w-4 h-4 text-amber-500" />
            <span>Combos y Promociones Disponibles</span>
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {promotions.map(promo => (
              <div 
                key={promo.id} 
                onClick={() => handleApplyPromotion(promo)}
                className="border border-slate-150 rounded-xl p-3.5 hover:border-[#0D6EFD]/50 hover:bg-slate-50/50 cursor-pointer transition-all flex flex-col justify-between gap-2.5"
              >
                <div>
                  <div className="flex justify-between items-start gap-2">
                    <h4 className="font-extrabold text-slate-800 text-sm leading-tight">{promo.name}</h4>
                    <span className="bg-emerald-50 text-emerald-700 border border-emerald-200 font-black text-[10px] px-2 py-0.5 rounded">
                      -{promo.discountPercentage}%
                    </span>
                  </div>
                  <p className="text-xs text-slate-500 mt-1 leading-snug">{promo.description}</p>
                </div>
                <div className="flex items-center justify-between text-[10px] font-bold text-slate-400 pt-2 border-t border-slate-100">
                  <span>Autofill al carrito</span>
                  <span className="text-[#0D6EFD] hover:underline flex items-center gap-0.5">
                    Aplicar Combo <ArrowUpRight className="w-3 h-3" />
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* VENTAS SUSPENDIDAS (MOCKUP BORRADORES CAJA) */}
        {suspendedSales.length > 0 && (
          <div className="bg-amber-50/50 border border-amber-200 rounded-2xl p-4 space-y-3">
            <h4 className="text-xs font-black text-amber-800 uppercase tracking-wider flex items-center gap-1">
              <RotateCcw className="w-4 h-4" />
              <span>Ventas Suspendidas en Espera ({suspendedSales.length})</span>
            </h4>
            <div className="flex flex-wrap gap-2">
              {suspendedSales.map(sale => (
                <button
                  key={sale.id}
                  onClick={() => handleResumeSale(sale)}
                  className="bg-white border border-amber-200 hover:border-amber-400 text-amber-900 text-xs font-bold px-3 py-1.5 rounded-lg flex items-center gap-1 transition-all"
                >
                  <span className="font-extrabold">{sale.id}</span>
                  <span className="text-[10px] text-amber-600">({sale.customer.name.slice(0, 10)}...)</span>
                </button>
              ))}
            </div>
          </div>
        )}

      </div>

      {/* SECCIÓN DERECHA: CARRITO Y ACCIONES FINALES (COL 5 - FIJO EN PANTALLA PRINCIPAL) */}
      <div className="lg:col-span-5 space-y-6">
        
        {/* PANEL DEL CARRITO DE COMPRA */}
        <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4 flex flex-col min-h-[580px] justify-between">
          
          <div className="space-y-4">
            <div className="flex justify-between items-center pb-2 border-b border-slate-100">
              <h3 className="text-sm font-black text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                <ShoppingBag className="w-4 h-4 text-emerald-600" />
                <span>CARRITO DE COMPRAS</span>
              </h3>
              <span className="text-xs font-bold text-slate-400">
                {cartItems.length} ítems · {cartItems.reduce((acc, i) => acc + i.quantity, 0)} u.
              </span>
            </div>

            {/* LISTA DE ITEMS */}
            <div className="space-y-3 max-h-[360px] overflow-y-auto pr-1">
              {cartItems.length === 0 ? (
                <div className="py-20 text-center text-slate-400 font-bold text-xs uppercase tracking-wide">
                  El carrito está vacío.<br />Busque artículos a la izquierda para despachar.
                </div>
              ) : (
                cartItems.map((item, idx) => {
                  const matchingProd = products.find(p => p.id === item.productId);
                  return (
                    <div 
                      key={`${item.productId}-${item.priceListId}`} 
                      className="border border-slate-100 rounded-xl p-3 bg-slate-50/50 hover:bg-slate-50 transition-colors space-y-2"
                    >
                      <div className="flex justify-between items-start gap-1">
                        <div>
                          <span className="text-[10px] font-black text-slate-400 uppercase block tracking-wider">
                            Cod: {matchingProd?.code}
                          </span>
                          <h4 className="font-extrabold text-slate-800 text-xs leading-snug">{item.productName}</h4>
                        </div>
                        <button
                          onClick={() => {
                            setCartItems(cartItems.filter(it => !(it.productId === item.productId && it.priceListId === item.priceListId)));
                          }}
                          className="text-red-500 hover:bg-red-50 p-1 rounded transition-colors"
                          title="Quitar"
                        >
                          <X className="w-3.5 h-3.5" />
                        </button>
                      </div>

                      {/* OPERACIONES ADICIONALES (LISTA DE PRECIO POR ARTÍCULO & DESCUENTOS INDIVIDUALES) */}
                      <div className="grid grid-cols-2 gap-2 text-[10px]">
                        <div>
                          <label className="text-slate-400 block mb-0.5 font-bold">TARIFA</label>
                          <select
                            value={item.priceListId}
                            onChange={(e) => handleUpdateItemPriceList(item.productId, item.priceListId || '1', e.target.value)}
                            className="bg-white border border-slate-200 rounded px-1.5 py-0.5 font-extrabold text-slate-700 focus:outline-none focus:border-[#0D6EFD]"
                          >
                            <option value="1">Minorista</option>
                            <option value="2">Mayorista (-15%)</option>
                            <option value="3">Distribuidor (-25%)</option>
                            <option value="4">Promocional (-10%)</option>
                          </select>
                        </div>
                        <div>
                          <label className="text-slate-400 block mb-0.5 font-bold">DESC. UNITARIO</label>
                          <div className="flex gap-1 items-center">
                            <input
                              type="number"
                              placeholder="%"
                              min="0"
                              max="100"
                              className="w-10 bg-white border border-slate-200 rounded px-1 py-0.5 font-bold focus:outline-none focus:border-[#0D6EFD]"
                              value={item.discountPercentage || ''}
                              onChange={(e) => handleUpdateItemDiscount(item.productId, item.priceListId || '1', parseInt(e.target.value) || 0, item.discountAmount || 0)}
                            />
                            <span className="text-slate-400 font-extrabold">%</span>
                          </div>
                        </div>
                      </div>

                      {/* FILA DE CONTROLES E IMPORTE */}
                      <div className="flex items-center justify-between pt-1 border-t border-slate-100/50">
                        {/* Steppers de Cantidad */}
                        <div className="flex items-center gap-1.5 bg-white border border-slate-200 rounded-lg p-0.5">
                          <button
                            onClick={() => handleUpdateQty(item.productId, item.priceListId || '1', item.quantity - 1)}
                            className="w-6 h-6 rounded bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center transition-all cursor-pointer"
                          >
                            -
                          </button>
                          <input
                            type="number"
                            className="w-8 text-center text-xs font-black text-slate-800 bg-transparent focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                            value={item.quantity}
                            onChange={(e) => handleUpdateQty(item.productId, item.priceListId || '1', parseInt(e.target.value) || 1)}
                          />
                          <button
                            onClick={() => handleUpdateQty(item.productId, item.priceListId || '1', item.quantity + 1)}
                            className="w-6 h-6 rounded bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center transition-all cursor-pointer"
                          >
                            +
                          </button>
                        </div>

                        {/* Precio Unitario y Subtotal */}
                        <div className="text-right">
                          <p className="text-[10px] text-slate-400 font-bold">
                            Unit: ${item.unitPrice} 
                            {item.discountPercentage ? ` (-${item.discountPercentage}%)` : ''}
                          </p>
                          <p className="text-xs font-black text-slate-800">
                            Subtotal: ${item.total}
                          </p>
                        </div>
                      </div>

                    </div>
                  );
                })
              )}
            </div>
          </div>

          {/* TOTALES Y ACCIONES DEL CARRITO */}
          <div className="space-y-4 pt-4 border-t border-slate-100">
            <div className="space-y-1.5 text-xs font-semibold text-slate-500">
              <div className="flex justify-between">
                <span>Subtotal Neto</span>
                <span>${cartSubtotal.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</span>
              </div>
              
              {globalDiscountValue > 0 && (
                <div className="flex justify-between text-blue-700 bg-blue-50/50 p-1.5 rounded">
                  <span>Descuento Global {globalDiscountType === 'PERCENTAGE' ? `(${globalDiscountValue}%)` : 'Fijo'}</span>
                  <span>-${finalDiscount.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</span>
                </div>
              )}

              <div className="flex justify-between items-baseline pt-2 border-t border-slate-100 text-slate-800">
                <span className="text-sm font-black uppercase">TOTAL A COBRAR</span>
                <span className="text-2xl font-black text-slate-900">
                  ${cartTotal.toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                </span>
              </div>
            </div>

            {/* REJILLA DE ACCIONES COMERCIALES */}
            <div className="grid grid-cols-2 gap-2">
              <button
                onClick={handleOpenCheckoutModal}
                className="col-span-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black py-3 rounded-xl flex items-center justify-center gap-1.5 shadow-md shadow-emerald-500/10 cursor-pointer text-sm"
              >
                <Receipt className="w-4 h-4" />
                <span>COBRAR (SPLIT DE PAGO)</span>
              </button>
              
              <button
                onClick={() => {
                  if (cartItems.length > 0) setShowGlobalDiscount(true);
                  else setErrorMsg('Carrito vacío. No puede aplicar descuentos.');
                }}
                className="bg-[#0D6EFD] hover:bg-[#0b5ed7] text-white font-bold py-2 rounded-xl text-xs flex items-center justify-center gap-1 transition-colors cursor-pointer"
              >
                <Percent className="w-3.5 h-3.5" />
                <span>DESCUENTO GLOBAL</span>
              </button>

              <button
                onClick={handleSuspendSale}
                className="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 rounded-xl text-xs flex items-center justify-center gap-1 transition-colors cursor-pointer"
              >
                <RotateCcw className="w-3.5 h-3.5" />
                <span>SUSPENDER VENTA</span>
              </button>

              <button
                onClick={() => {
                  if (window.confirm('¿Desea vaciar completamente el carrito?')) {
                    setCartItems([]);
                    setGlobalDiscountValue(0);
                    setSelectedCustomer(null);
                  }
                }}
                className="bg-slate-400 hover:bg-slate-500 text-white font-bold py-2 rounded-xl text-xs flex items-center justify-center gap-1 transition-colors cursor-pointer"
              >
                <Trash2 className="w-3.5 h-3.5" />
                <span>LIMPIAR CARRITO</span>
              </button>

              <button
                onClick={onCerrarCaja}
                className="bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded-xl text-xs flex items-center justify-center gap-1 transition-colors cursor-pointer"
              >
                <Wallet className="w-3.5 h-3.5" />
                <span>CERRAR CAJA</span>
              </button>
            </div>

          </div>

        </div>

      </div>

      {/* =========================================================================
          1. MODAL: BUSCAR CLIENTE
          ========================================================================= */}
      {showClientSearch && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl border border-slate-200 w-full max-w-xl overflow-hidden shadow-2xl flex flex-col max-h-[80vh]">
            <header className="bg-slate-50 p-4 border-b border-slate-100 flex justify-between items-center">
              <h3 className="text-base font-black text-slate-800 flex items-center gap-1.5">
                <Search className="w-4 h-4 text-[#0D6EFD]" />
                <span>Buscar Cliente Comercial</span>
              </h3>
              <button onClick={() => setShowClientSearch(false)}>
                <X className="w-5 h-5 text-slate-400 hover:text-slate-600" />
              </button>
            </header>
            
            <div className="p-4 space-y-4 flex-1 overflow-y-auto">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 w-4 h-4" />
                <input
                  type="text"
                  placeholder="Filtrar por nombre o CUIT..."
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-sm font-semibold"
                  value={clientQuery}
                  onChange={(e) => setClientQuery(e.target.value)}
                />
              </div>

              <div className="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden">
                {filteredCustomers.map(c => (
                  <button
                    key={c.id}
                    onClick={() => {
                      setSelectedCustomer(c);
                      setShowClientSearch(false);
                    }}
                    className="w-full text-left p-3 hover:bg-slate-50 flex justify-between items-center transition-colors text-xs font-bold text-slate-700"
                  >
                    <div>
                      <p className="font-extrabold text-sm text-slate-800">{c.name}</p>
                      <p className="text-slate-400 mt-0.5">CUIT: {c.cuit} | Zona: {c.zone}</p>
                    </div>
                    <span className="text-[#0D6EFD] text-[10px] uppercase font-black tracking-wider">Asignar</span>
                  </button>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* =========================================================================
          2. MODAL: NUEVO CLIENTE (RÁPIDO)
          ========================================================================= */}
      {showNewClient && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl border border-slate-200 w-full max-w-md overflow-hidden shadow-2xl flex flex-col">
            <header className="bg-slate-50 p-4 border-b border-slate-100 flex justify-between items-center">
              <h3 className="text-base font-black text-slate-800 flex items-center gap-1.5">
                <UserIcon className="w-4 h-4 text-[#0D6EFD]" />
                <span>Crear Nuevo Cliente Rápido</span>
              </h3>
              <button onClick={() => setShowNewClient(false)}>
                <X className="w-5 h-5 text-slate-400 hover:text-slate-600" />
              </button>
            </header>

            <div className="p-5 space-y-4">
              <div className="space-y-1">
                <label className="text-xs font-bold text-slate-500 block">Razón Social / Nombre *</label>
                <input
                  type="text"
                  placeholder="Ej: Distribuidora Sur"
                  required
                  className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold focus:outline-none focus:border-[#0D6EFD]"
                  value={newClientName}
                  onChange={(e) => setNewClientName(e.target.value)}
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1">
                  <label className="text-xs font-bold text-slate-500 block">CUIT / DNI</label>
                  <input
                    type="text"
                    placeholder="20-33444555-9"
                    className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold focus:outline-none focus:border-[#0D6EFD]"
                    value={newClientCuit}
                    onChange={(e) => setNewClientCuit(e.target.value)}
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs font-bold text-slate-500 block">Teléfono</label>
                  <input
                    type="text"
                    placeholder="261-555555"
                    className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold focus:outline-none focus:border-[#0D6EFD]"
                    value={newClientPhone}
                    onChange={(e) => setNewClientPhone(e.target.value)}
                  />
                </div>
              </div>

              <div className="space-y-1">
                <label className="text-xs font-bold text-slate-500 block">Dirección de Entrega</label>
                <input
                  type="text"
                  placeholder="Ej: San Martín 123, Mendoza"
                  className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold focus:outline-none focus:border-[#0D6EFD]"
                  value={newClientAddress}
                  onChange={(e) => setNewClientAddress(e.target.value)}
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1">
                  <label className="text-xs font-bold text-slate-500 block">Condición frente al IVA</label>
                  <select
                    className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold focus:outline-none focus:border-[#0D6EFD]"
                    value={newClientIva}
                    onChange={(e) => setNewClientIva(e.target.value)}
                  >
                    <option value="Monotributista">Monotributista</option>
                    <option value="Responsable Inscripto">Responsable Inscripto</option>
                    <option value="Exento">Exento</option>
                    <option value="Consumidor Final">Consumidor Final</option>
                  </select>
                </div>
                <div className="space-y-1">
                  <label className="text-xs font-bold text-slate-500 block">Lista de Precios Asignada</label>
                  <select
                    className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold focus:outline-none focus:border-[#0D6EFD]"
                    value={newClientPriceListId}
                    onChange={(e) => setNewClientPriceListId(e.target.value)}
                  >
                    <option value="1">Minorista</option>
                    <option value="2">Mayorista</option>
                    <option value="3">Distribuidor</option>
                  </select>
                </div>
              </div>

              <button
                onClick={handleCreateQuickCustomer}
                className="w-full bg-[#198754] hover:bg-[#146c43] text-white font-black py-2.5 rounded-xl text-xs transition-colors cursor-pointer"
              >
                CREAR CLIENTE E INTEGRAR A LA VENTA
              </button>
            </div>
          </div>
        </div>
      )}

      {/* =========================================================================
          3. MODAL: DESCUENTOS GLOBALES
          ========================================================================= */}
      {showGlobalDiscount && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl border border-slate-200 w-full max-w-sm overflow-hidden shadow-2xl flex flex-col">
            <header className="bg-slate-50 p-4 border-b border-slate-100 flex justify-between items-center">
              <h3 className="text-sm font-black text-slate-800 flex items-center gap-1.5">
                <Percent className="w-4 h-4 text-[#0D6EFD]" />
                <span>Aplicar Descuento Global</span>
              </h3>
              <button onClick={() => setShowGlobalDiscount(false)}>
                <X className="w-5 h-5 text-slate-400" />
              </button>
            </header>

            <div className="p-5 space-y-4">
              <div className="flex bg-slate-100 p-1 rounded-xl gap-1">
                <button
                  type="button"
                  onClick={() => setGlobalDiscountType('PERCENTAGE')}
                  className={`flex-1 text-center py-2 text-xs font-bold rounded-lg transition-all ${globalDiscountType === 'PERCENTAGE' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500'}`}
                >
                  Porcentaje (%)
                </button>
                <button
                  type="button"
                  onClick={() => setGlobalDiscountType('FIXED')}
                  className={`flex-1 text-center py-2 text-xs font-bold rounded-lg transition-all ${globalDiscountType === 'FIXED' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500'}`}
                >
                  Monto Fijo ($)
                </button>
              </div>

              <div className="space-y-1">
                <label className="text-xs font-bold text-slate-500 block">
                  {globalDiscountType === 'PERCENTAGE' ? 'Porcentaje de descuento' : 'Importe de descuento fijo'}
                </label>
                <input
                  type="number"
                  placeholder={globalDiscountType === 'PERCENTAGE' ? '10' : '1500'}
                  className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-xs font-extrabold"
                  value={globalDiscountValue || ''}
                  onChange={(e) => setGlobalDiscountValue(Math.max(0, parseFloat(e.target.value) || 0))}
                />
              </div>

              <button
                onClick={() => setShowGlobalDiscount(false)}
                className="w-full bg-[#0D6EFD] hover:bg-[#0b5ed7] text-white font-black py-2.5 rounded-xl text-xs transition-colors"
              >
                APLICAR AL TOTAL
              </button>
            </div>
          </div>
        </div>
      )}

      {/* =========================================================================
          4. MODAL: COBRAR / CHECKOUT (SPLIT PAYMENT)
          ========================================================================= */}
      {showCheckout && (
        <div className="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-white rounded-3xl border border-slate-200 w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col my-8">
            <header className="bg-slate-50 px-6 py-5 border-b border-slate-100 flex justify-between items-center">
              <div>
                <span className="text-[10px] font-bold text-[#0D6EFD] uppercase tracking-wider block">checkout de caja</span>
                <h3 className="text-lg font-black text-slate-800 flex items-center gap-1.5">
                  <Receipt className="w-5 h-5 text-emerald-600" />
                  <span>Cobranza Multimedio Dividida</span>
                </h3>
              </div>
              <button onClick={() => setShowCheckout(false)}>
                <X className="w-6 h-6 text-slate-400 hover:text-slate-600" />
              </button>
            </header>

            <div className="p-6 space-y-6">
              
              {/* RESUMEN DE IMPORTES */}
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100 text-center font-bold">
                <div>
                  <span className="text-[10px] text-slate-400 uppercase block mb-1">Total Venta</span>
                  <span className="text-lg font-black text-slate-800">${cartTotal.toLocaleString('es-AR')}</span>
                </div>
                <div>
                  <span className="text-[10px] text-slate-400 uppercase block mb-1">Total Cargado</span>
                  <span className="text-lg font-black text-emerald-600">${totalReceived.toLocaleString('es-AR')}</span>
                </div>
                <div>
                  <span className="text-[10px] text-slate-400 uppercase block mb-1">Pendiente</span>
                  <span className={`text-lg font-black ${remainingPending > 0 ? 'text-amber-600 animate-pulse' : 'text-slate-500'}`}>
                    ${remainingPending.toLocaleString('es-AR')}
                  </span>
                </div>
                <div>
                  <span className="text-[10px] text-slate-400 uppercase block mb-1">Vuelto (Vuelto)</span>
                  <span className={`text-lg font-black ${calculatedChange > 0 ? 'text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200' : 'text-slate-500'}`}>
                    ${calculatedChange.toLocaleString('es-AR')}
                  </span>
                </div>
              </div>

              {/* INPUTS MULTIPLES PARA CADA MEDIO DE PAGO */}
              <div className="space-y-4">
                <h4 className="text-xs font-black text-slate-400 uppercase tracking-wider">Asignación de importes por medio de pago:</h4>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {Object.keys(paymentAmounts).map(method => {
                    // Validar si cuenta corriente está habilitada (solo si hay cliente)
                    const isDisabledCC = method === 'Cuenta Corriente' && !selectedCustomer;
                    
                    return (
                      <div 
                        key={method} 
                        className={`border rounded-xl p-3.5 flex items-center justify-between gap-4 transition-all ${isDisabledCC ? 'bg-slate-100 opacity-50' : 'bg-white hover:border-slate-300'}`}
                      >
                        <div>
                          <p className="text-xs font-extrabold text-slate-800">{method}</p>
                          {method === 'Cuenta Corriente' && selectedCustomer && (
                            <span className="text-[10px] font-bold text-slate-400">Saldo actual CC: ${activeAccount ? activeAccount.balance : 0}</span>
                          )}
                        </div>
                        <div className="flex items-center gap-1 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 max-w-[140px]">
                          <span className="text-xs font-black text-slate-400">$</span>
                          <input
                            type="number"
                            disabled={isDisabledCC}
                            placeholder="0"
                            className="w-full bg-transparent text-right font-black text-xs text-slate-800 focus:outline-none"
                            value={paymentAmounts[method] || ''}
                            onChange={(e) => {
                              const value = Math.max(0, parseFloat(e.target.value) || 0);
                              setPaymentAmounts({
                                ...paymentAmounts,
                                [method]: value
                              });
                            }}
                          />
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>

              {/* ACCIÓN FINAL */}
              <div className="pt-4 border-t border-slate-100 flex flex-col md:flex-row gap-4 items-center justify-between">
                <p className="text-xs font-bold text-slate-400 max-w-sm text-center md:text-left">
                  * Al confirmar, se registrarán automáticamente los movimientos de caja correspondientes y se actualizarán las cuentas corrientes en caso de deudas.
                </p>
                <button
                  onClick={handleConfirmSplitPayment}
                  disabled={remainingPending > 0.1}
                  className={`w-full md:w-auto px-6 py-3 rounded-xl font-black text-xs uppercase tracking-wider transition-all cursor-pointer ${remainingPending > 0.1 ? 'bg-slate-200 text-slate-400 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-md'}`}
                >
                  Confirmar Cobranza y Factura
                </button>
              </div>

            </div>
          </div>
        </div>
      )}

    </div>
  );
}
