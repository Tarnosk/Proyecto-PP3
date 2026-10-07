/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState, useEffect } from 'react';
import { 
  User, 
  Product, 
  PriceList, 
  Customer, 
  Promotion, 
  Order, 
  OrderItem 
} from '../types';
import { 
  ShoppingBag, 
  Plus, 
  Search, 
  ArrowLeft, 
  Trash2, 
  Save, 
  Tag, 
  Sparkles, 
  TrendingUp, 
  FileText, 
  Printer, 
  Receipt, 
  AlertTriangle, 
  CheckCircle2,
  Percent,
  Check,
  Eye,
  Calendar,
  Layers,
  ChevronRight,
  Calculator,
  X,
  Wallet,
  User as UserIcon,
  Coins,
  Package,
  RotateCw
} from 'lucide-react';

import { SalesPOS } from './sales/SalesPOS';
import { SalesPromotions } from './sales/SalesPromotions';
import { SalesCurrentAccount } from './sales/SalesCurrentAccount';
import { SalesProductAdmin } from './sales/SalesProductAdmin';

interface SalesViewProps {
  currentUser: User;
  products: Product[];
  priceLists: PriceList[];
  customers: Customer[];
  promotions: Promotion[];
  orders: Order[];
  onUpdateProducts: (updated: Product[]) => void;
  onUpdatePriceLists: (updated: PriceList[]) => void;
  onUpdateCustomers?: (updated: Customer[]) => void;
  onUpdateOrders: (updated: Order[]) => void;
  onUpdatePromotions: (updated: Promotion[]) => void;
}

type SalesSubView = 
  | 'LIST_ORDERS' 
  | 'CREATE_ORDER' 
  | 'VIEW_ORDER_DETAIL' 
  | 'CATALOG_VIEW' 
  | 'PRICE_LISTS' 
  | 'MASSIVE_UPDATE' 
  | 'PROMOTIONS_LIST' 
  | 'SALES_STATS'
  | 'CUENTAS_CORRIENTES'
  | 'PRODUCT_ADMIN';

export function SalesView({
  currentUser,
  products,
  priceLists,
  customers,
  promotions,
  orders,
  onUpdateProducts,
  onUpdatePriceLists,
  onUpdateCustomers,
  onUpdateOrders,
  onUpdatePromotions
}: SalesViewProps) {
  const [subView, setSubView] = useState<SalesSubView>('LIST_ORDERS');
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [showRemitoPreview, setShowRemitoPreview] = useState(false);
  const [showInvoicePreview, setShowInvoicePreview] = useState(false);

  // Filtros de Pedidos
  const [orderSearch, setOrderSearch] = useState('');
  const [orderStatusFilter, setOrderStatusFilter] = useState('TODOS');

  // Estado para la creación de un nuevo pedido
  const [newOrderCustomer, setNewOrderCustomer] = useState<Customer | null>(null);
  const [newOrderItems, setNewOrderItems] = useState<OrderItem[]>([]);
  const [newOrderNotes, setNewOrderNotes] = useState('');
  const [selectedProductId, setSelectedProductId] = useState('');
  const [selectedProductQty, setSelectedProductQty] = useState(1);
  const [orderError, setOrderError] = useState('');
  const [orderSuccess, setOrderSuccess] = useState('');

  // Nuevos estados para la distribución de botones y características del POS
  const [activePriceListId, setActivePriceListId] = useState<string>('');
  const [globalDiscount, setGlobalDiscount] = useState<number>(0);
  const [productSearchQuery, setProductSearchQuery] = useState('');
  const [showSearchClientModal, setShowSearchClientModal] = useState(false);
  const [showNewClientModal, setShowNewClientModal] = useState(false);
  const [showDiscountModal, setShowDiscountModal] = useState(false);
  const [showCobrarModal, setShowCobrarModal] = useState(false);
  const [showCerrarCajaModal, setShowCerrarCajaModal] = useState(false);
  const [clientSearchQuery, setClientSearchQuery] = useState('');
  
  // Nuevo cliente modal state
  const [quickClientName, setQuickClientName] = useState('');
  const [quickClientCuit, setQuickClientCuit] = useState('');
  const [quickClientAddress, setQuickClientAddress] = useState('');
  const [quickClientPhone, setQuickClientPhone] = useState('');
  const [quickClientZone, setQuickClientZone] = useState('Centro');
  const [quickClientPriceList, setQuickClientPriceList] = useState('1');

  // Cobrar modal state
  const [paymentMethod, setPaymentMethod] = useState<'Efectivo' | 'Cuenta Corriente' | 'Transferencia'>('Efectivo');
  const [amountReceived, setAmountReceived] = useState<string>('');
  const [showReceipt, setShowReceipt] = useState(false);
  const [lastSavedOrder, setLastSavedOrder] = useState<Order | null>(null);

  // Estado para actualización masiva de precios
  const [massivePercent, setMassivePercent] = useState(10);
  const [massiveCategory, setMassiveCategory] = useState('Todas');
  const [massiveDirection, setMassiveDirection] = useState<'AUMENTAR' | 'DISMINUIR'>('AUMENTAR');
  const [massiveError, setMassiveError] = useState('');
  const [massiveSuccess, setMassiveSuccess] = useState('');

  // Estado para crear nueva promoción
  const [newPromoName, setNewPromoName] = useState('');
  const [newPromoDesc, setNewPromoDesc] = useState('');
  const [newPromoDiscount, setNewPromoDiscount] = useState(10);
  const [newPromoProdId, setNewPromoProdId] = useState('');
  const [newPromoQty, setNewPromoQty] = useState(5);
  const [promoSuccess, setPromoSuccess] = useState('');

  // Buscador de Catálogo
  const [catalogSearch, setCatalogSearch] = useState('');
  const [catalogCategory, setCatalogCategory] = useState('Todas');

  // Obtener categorías únicas
  const categories = Array.from(new Set(products.map(p => p.category)));

  // 1. OBTENER PRECIO CALCULADO CON LISTA DE PRECIOS ACTIVA
  const getProductPriceForActiveList = (product: Product, priceListId: string): number => {
    const list = priceLists.find(l => l.id === priceListId);
    if (!list) return product.price;
    const discount = product.price * (list.discountPercentage / 100);
    return Number((product.price - discount).toFixed(2));
  };

  const getProductPriceForCustomer = (product: Product, customer: Customer): number => {
    return getProductPriceForActiveList(product, customer.priceListId);
  };

  // 2. COMPROBAR PROMOCIONES APLICABLES EN TIEMPO REAL
  const checkPromotionForItem = (productId: string, quantity: number): { discountPercentage: number; name: string } | null => {
    const activePromo = promotions.find(p => p.requiredProductId === productId && quantity >= (p.requiredQuantity || 0));
    if (activePromo) {
      return {
        discountPercentage: activePromo.discountPercentage,
        name: activePromo.name
      };
    }
    return null;
  };

  // 3. AGREGAR ITEM AL NUEVO PEDIDO
  const handleAddItemToOrder = () => {
    setOrderError('');
    if (!newOrderCustomer) {
      setOrderError('Debe seleccionar primero un cliente para calcular el precio correspondiente.');
      return;
    }
    if (!selectedProductId) {
      setOrderError('Debe elegir un producto.');
      return;
    }

    const prod = products.find(p => p.id === selectedProductId);
    if (!prod) return;

    if (selectedProductQty <= 0) {
      setOrderError('La cantidad debe ser mayor que cero.');
      return;
    }

    // Validar stock disponible
    const existingInOrder = newOrderItems.find(item => item.productId === prod.id);
    const totalQtyNeeded = (existingInOrder ? existingInOrder.quantity : 0) + selectedProductQty;

    if (totalQtyNeeded > prod.stock) {
      setOrderError(`Atención: Stock insuficiente. El stock disponible actual de este producto es de ${prod.stock} unidades.`);
      return;
    }

    const priceWithList = getProductPriceForActiveList(prod, activePriceListId || newOrderCustomer.priceListId);
    
    // Comprobar si aplica promo por volumen
    const promo = checkPromotionForItem(prod.id, totalQtyNeeded);
    let finalUnitPrice = priceWithList;
    if (promo) {
      const discount = priceWithList * (promo.discountPercentage / 100);
      finalUnitPrice = Number((priceWithList - discount).toFixed(2));
    }

    if (existingInOrder) {
      const updatedItems = newOrderItems.map(item => {
        if (item.productId === prod.id) {
          const newQty = item.quantity + selectedProductQty;
          // recalculamos precio por si ahora califica a la promo
          const updatedPromo = checkPromotionForItem(prod.id, newQty);
          let itemPrice = priceWithList;
          if (updatedPromo) {
            const disc = priceWithList * (updatedPromo.discountPercentage / 100);
            itemPrice = Number((priceWithList - disc).toFixed(2));
          }
          return {
            ...item,
            quantity: newQty,
            unitPrice: itemPrice,
            total: Number((newQty * itemPrice).toFixed(2))
          };
        }
        return item;
      });
      setNewOrderItems(updatedItems);
    } else {
      const newItem: OrderItem = {
        productId: prod.id,
        productName: prod.name,
        quantity: selectedProductQty,
        unitPrice: finalUnitPrice,
        total: Number((selectedProductQty * finalUnitPrice).toFixed(2))
      };
      setNewOrderItems([...newOrderItems, newItem]);
    }

    // Reset de selección de producto
    setSelectedProductId('');
    setSelectedProductQty(1);
    setProductSearchQuery('');
  };

  // RECALCULAR TODA LA CARGA DE ITEMS SI SE CAMBIA EL CLIENTE O LA LISTA DE PRECIOS ACTIVA
  useEffect(() => {
    if (newOrderCustomer) {
      setActivePriceListId(newOrderCustomer.priceListId);
    } else {
      setActivePriceListId('');
    }
  }, [newOrderCustomer]);

  useEffect(() => {
    const listId = activePriceListId || (newOrderCustomer ? newOrderCustomer.priceListId : '');
    if (listId && newOrderItems.length > 0) {
      const updated = newOrderItems.map(item => {
        const prod = products.find(p => p.id === item.productId);
        if (!prod) return item;
        const priceWithList = getProductPriceForActiveList(prod, listId);
        const promo = checkPromotionForItem(prod.id, item.quantity);
        let finalUnitPrice = priceWithList;
        if (promo) {
          const discount = priceWithList * (promo.discountPercentage / 100);
          finalUnitPrice = Number((priceWithList - discount).toFixed(2));
        }
        return {
          ...item,
          unitPrice: finalUnitPrice,
          total: Number((item.quantity * finalUnitPrice).toFixed(2))
        };
      });
      setNewOrderItems(updated);
    }
  }, [activePriceListId]);

  // Tecla de acceso rápido F1 para nuevo cliente
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (subView === 'CREATE_ORDER') {
        if (e.key === 'F1') {
          e.preventDefault();
          setShowNewClientModal(true);
        }
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [subView]);

  // ELIMINAR ITEM DE LA ORDEN
  const handleRemoveItem = (index: number) => {
    const updated = [...newOrderItems];
    updated.splice(index, 1);
    setNewOrderItems(updated);
  };

  // CONFIRMAR PEDIDO
  const handleSaveOrder = (e: React.FormEvent) => {
    e.preventDefault();
    setOrderError('');
    setOrderSuccess('');

    if (!newOrderCustomer) {
      setOrderError('Debe ingresar un cliente válido.');
      return;
    }
    if (newOrderItems.length === 0) {
      setOrderError('Debe registrar al menos un producto en el pedido.');
      return;
    }

    // Validar stock final de cada item por seguridad
    for (const item of newOrderItems) {
      const p = products.find(prod => prod.id === item.productId);
      if (!p || p.stock < item.quantity) {
        setOrderError(`No hay suficiente stock disponible de: ${item.productName}.`);
        return;
      }
    }

    const orderId = `ORD-${Date.now().toString().slice(-4)}`;
    const totalOrderAmount = Number(newOrderItems.reduce((acc, item) => acc + item.total, 0).toFixed(2));
    
    const now = new Date();
    const formattedDate = `${now.getFullYear()}-0${now.getMonth() + 1}-${now.getDate().toString().padStart(2, '0')}`;

    const newOrder: Order = {
      id: orderId,
      customerId: newOrderCustomer.id,
      customerName: newOrderCustomer.name,
      items: newOrderItems,
      total: totalOrderAmount,
      date: formattedDate,
      status: 'Pendiente',
      notes: newOrderNotes
    };

    // Actualizar stock de productos
    const updatedProducts = products.map(prod => {
      const soldItem = newOrderItems.find(item => item.productId === prod.id);
      if (soldItem) {
        return {
          ...prod,
          stock: prod.stock - soldItem.quantity
        };
      }
      return prod;
    });

    onUpdateProducts(updatedProducts);
    
    // Guardar pedido
    const updatedOrders = [newOrder, ...orders];
    onUpdateOrders(updatedOrders);

    setOrderSuccess(`¡Pedido ${orderId} registrado con éxito! Redirigiendo al detalle...`);
    
    // Resetear formulario
    setNewOrderCustomer(null);
    setNewOrderItems([]);
    setNewOrderNotes('');

    // Ir al detalle del pedido creado
    setTimeout(() => {
      setSelectedOrder(newOrder);
      setSubView('VIEW_ORDER_DETAIL');
      setOrderSuccess('');
    }, 1500);
  };

  // CONTROL DE CAMBIO DE ESTADO DESDE DETALLE
  const handleUpdateOrderStatus = (status: Order['status']) => {
    if (!selectedOrder) return;
    const updated = orders.map(o => {
      if (o.id === selectedOrder.id) {
        return {
          ...o,
          status
        };
      }
      return o;
    });
    onUpdateOrders(updated);
    setSelectedOrder({ ...selectedOrder, status });
  };

  // FACTURACIÓN AFIP / ARCA SIMULADA
  const handleGenerateInvoice = () => {
    if (!selectedOrder) return;
    const invoiceNum = `Factura A-0004-0000${Math.floor(1000 + Math.random() * 9000)}`;
    const updated = orders.map(o => {
      if (o.id === selectedOrder.id) {
        return {
          ...o,
          status: 'Facturado' as const,
          invoiceNumber: invoiceNum
        };
      }
      return o;
    });
    onUpdateOrders(updated);
    setSelectedOrder({ ...selectedOrder, status: 'Facturado', invoiceNumber: invoiceNum });
  };

  // ACTUALIZACIÓN MASIVA DE PRECIOS
  const handleMassivePriceUpdate = (e: React.FormEvent) => {
    e.preventDefault();
    setMassiveError('');
    setMassiveSuccess('');

    if (massivePercent <= 0) {
      setMassiveError('El porcentaje debe ser un valor positivo mayor que cero.');
      return;
    }

    const multiplier = massiveDirection === 'AUMENTAR' 
      ? 1 + (massivePercent / 100) 
      : 1 - (massivePercent / 100);

    const updated = products.map(p => {
      if (massiveCategory === 'Todas' || p.category === massiveCategory) {
        const newPrice = Number((p.price * multiplier).toFixed(2));
        const newCost = Number((p.cost * multiplier).toFixed(2)); // Actualizar costos correspondientemente
        return {
          ...p,
          price: newPrice,
          cost: newCost
        };
      }
      return p;
    });

    onUpdateProducts(updated);
    setMassiveSuccess(`¡Operación exitosa! Se han ${massiveDirection.toLowerCase()}/do los precios base de los productos pertenecientes a la categoría "${massiveCategory}" en un ${massivePercent}%.`);
    setTimeout(() => {
      setMassiveSuccess('');
    }, 5000);
  };

  // EDICIÓN LISTAS DE PRECIOS DESCUENTOS
  const handleUpdateListDiscount = (listId: string, value: number) => {
    const updated = priceLists.map(l => {
      if (l.id === listId) {
        return {
          ...l,
          discountPercentage: value
        };
      }
      return l;
    });
    onUpdatePriceLists(updated);
  };

  // CREAR NUEVA PROMOCIÓN
  const handleCreatePromo = (e: React.FormEvent) => {
    e.preventDefault();
    setPromoSuccess('');

    if (!newPromoName.trim() || !newPromoDesc.trim() || !newPromoProdId) {
      return;
    }

    const newPromo: Promotion = {
      id: Date.now().toString(),
      name: newPromoName.trim(),
      description: newPromoDesc.trim(),
      discountPercentage: newPromoDiscount,
      requiredProductId: newPromoProdId,
      requiredQuantity: newPromoQty
    };

    onUpdatePromotions([...promotions, newPromo]);
    setPromoSuccess('¡Nueva promoción de volumen creada y activada!');
    
    // reset
    setNewPromoName('');
    setNewPromoDesc('');
    setNewPromoProdId('');
    setNewPromoDiscount(10);
    setNewPromoQty(5);

    setTimeout(() => {
      setPromoSuccess('');
    }, 3000);
  };

  const handleSaveOrderFromPOS = (orderData: {
    customer: Customer;
    items: OrderItem[];
    globalDiscountType: 'PERCENTAGE' | 'FIXED';
    globalDiscountValue: number;
    paymentSplit: Record<string, number>;
  }) => {
    const { customer, items, globalDiscountType, globalDiscountValue, paymentSplit } = orderData;
    
    // 1. Calcular importe final con descuento global
    const subtotal = items.reduce((acc, it) => acc + it.total, 0);
    const finalDiscount = globalDiscountType === 'PERCENTAGE' 
      ? Number((subtotal * (globalDiscountValue / 100)).toFixed(2)) 
      : globalDiscountValue;
    const finalTotal = Number(Math.max(0, subtotal - finalDiscount).toFixed(2));

    // 2. Crear objeto pedido
    const orderId = `ORD-${Date.now().toString().slice(-4)}`;
    const now = new Date();
    const formattedDate = `${now.getFullYear()}-0${now.getMonth() + 1}-${now.getDate().toString().padStart(2, '0')}`;

    const newOrder: Order = {
      id: orderId,
      customerId: customer.id,
      customerName: customer.name,
      items: items,
      total: finalTotal,
      date: formattedDate,
      status: 'Facturado', // Auto facturado al cobrarse en caja
      notes: `Venta POS. Cobro split: ${Object.entries(paymentSplit).filter(([m, val]) => val > 0).map(([m, val]) => `${m}: $${val}`).join(', ')}`,
      invoiceNumber: `Factura A-0004-0000${Math.floor(1000 + Math.random() * 9000)}`
    };

    // 3. Actualizar stock de productos
    const updatedProducts = products.map(p => {
      const soldItem = items.find(it => it.productId === p.id);
      if (soldItem) {
        return {
          ...p,
          stock: Math.max(0, p.stock - soldItem.quantity)
        };
      }
      return p;
    });
    onUpdateProducts(updatedProducts);

    // 4. Si hay saldo en cuenta corriente, actualizar la CC
    const ccAmount = paymentSplit['Cuenta Corriente'] || 0;
    if (ccAmount > 0) {
      const savedAccounts = localStorage.getItem('erp_distribuidora_customer_accounts');
      const savedMovements = localStorage.getItem('erp_distribuidora_customer_account_movements');
      
      let accounts: any[] = [];
      let movements: any[] = [];
      
      if (savedAccounts) {
        try { accounts = JSON.parse(savedAccounts); } catch (e) { console.error(e); }
      }
      if (savedMovements) {
        try { movements = JSON.parse(savedMovements); } catch (e) { console.error(e); }
      }

      let accIdx = accounts.findIndex(a => a.customerId === customer.id);
      const accountId = accIdx > -1 ? accounts[accIdx].id : `ACC-${customer.id}`;
      
      if (accIdx === -1) {
        const newAcc = {
          id: accountId,
          customerId: customer.id,
          customerName: customer.name,
          balance: 0,
          limit: 100000,
          status: 'Activo'
        };
        accounts.push(newAcc);
        accIdx = accounts.length - 1;
      }

      accounts[accIdx].balance = Number((accounts[accIdx].balance + ccAmount).toFixed(2));

      const newMovement = {
        id: `MOV-${Date.now().toString().slice(-4)}`,
        accountId: accountId,
        type: 'Debito', // Debito incrementa la deuda del cliente
        amount: ccAmount,
        date: new Date().toISOString().replace('T', ' ').slice(0, 16),
        concept: `Cargo por venta POS - Pedido ${orderId}`,
        referenceId: orderId
      };

      localStorage.setItem('erp_distribuidora_customer_accounts', JSON.stringify(accounts));
      localStorage.setItem('erp_distribuidora_customer_account_movements', JSON.stringify([newMovement, ...movements]));
    }

    // 5. Registrar movimientos de caja para porciones no de CC
    const cashPortions = Object.entries(paymentSplit)
      .filter(([m, val]) => m !== 'Cuenta Corriente' && val > 0);

    if (cashPortions.length > 0) {
      const savedCashMovements = localStorage.getItem('erp_distribuidora_cash_movements');
      let cashMovs: any[] = [];
      if (savedCashMovements) {
        try { cashMovs = JSON.parse(savedCashMovements); } catch (e) { console.error(e); }
      }

      cashPortions.forEach(([m, val]) => {
        cashMovs.push({
          id: `CSH-${Date.now().toString().slice(-4)}`,
          type: 'Ingreso',
          category: 'Cobranza',
          amount: val,
          date: new Date().toISOString().replace('T', ' ').slice(0, 16),
          concept: `Cobranza Venta POS ${orderId} (${m})`,
          paymentMethod: m,
          referenceId: orderId
        });
      });

      localStorage.setItem('erp_distribuidora_cash_movements', JSON.stringify(cashMovs));
    }

    // 6. Guardar el pedido
    const updatedOrders = [newOrder, ...orders];
    onUpdateOrders(updatedOrders);

    // Ir al detalle del pedido creado
    setSelectedOrder(newOrder);
    setSubView('VIEW_ORDER_DETAIL');
  };

  // FILTRADO DE PEDIDOS EN PANEL
  const filteredOrders = orders.filter(o => {
    const matchesSearch = o.customerName.toLowerCase().includes(orderSearch.toLowerCase()) || o.id.toLowerCase().includes(orderSearch.toLowerCase());
    const matchesStatus = orderStatusFilter === 'TODOS' || o.status === orderStatusFilter;
    return matchesSearch && matchesStatus;
  });

  // METRICAS ESTADÍSTICAS COMERCIALES
  const calculateStats = () => {
    const totalAmount = orders.reduce((acc, o) => o.status !== 'Cancelado' ? acc + o.total : acc, 0);
    const count = orders.filter(o => o.status !== 'Cancelado').length;
    const average = count > 0 ? Number((totalAmount / count).toFixed(2)) : 0;
    
    // Contar productos más vendidos
    const productSalesMap: Record<string, number> = {};
    orders.forEach(o => {
      if (o.status !== 'Cancelado') {
        o.items.forEach(item => {
          productSalesMap[item.productName] = (productSalesMap[item.productName] || 0) + item.quantity;
        });
      }
    });

    const sortedProducts = Object.entries(productSalesMap)
      .map(([name, qty]) => ({ name, qty }))
      .sort((a, b) => b.qty - a.qty)
      .slice(0, 4);

    return { totalAmount, count, average, topProducts: sortedProducts };
  };

  const stats = calculateStats();

  return (
    <div className="space-y-6">
      
      {/* MENÚ DE ACCESOS RÁPIDOS INTERNOS (Sub-navegación compacta y estética en una sola fila) */}
      <div className="flex flex-nowrap overflow-x-auto gap-2 bg-white/75 backdrop-blur-md border border-slate-200/60 p-2 rounded-2xl shadow-[inset_2px_2px_4px_rgba(255,255,255,0.8),0_10px_25px_rgba(13,110,253,0.02)] scrollbar-none">
        <button
          onClick={() => setSubView('LIST_ORDERS')}
          className={`px-4 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2 cursor-pointer shrink-0 border ${
            subView === 'LIST_ORDERS' || subView === 'VIEW_ORDER_DETAIL'
              ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white border-emerald-400 shadow-md shadow-emerald-500/10'
              : 'bg-white/90 text-slate-600 border-slate-200/60 hover:bg-slate-50 hover:text-slate-800 shadow-sm'
          }`}
        >
          <ShoppingBag className="w-4 h-4" />
          <span>Panel de Ventas</span>
        </button>

        <button
          onClick={() => {
            setSubView('CREATE_ORDER');
            setNewOrderCustomer(null);
            setNewOrderItems([]);
            setOrderError('');
          }}
          className={`px-4 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2 cursor-pointer shrink-0 border ${
            subView === 'CREATE_ORDER'
              ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white border-emerald-400 shadow-md shadow-emerald-500/10'
              : 'bg-white/90 text-slate-600 border-slate-200/60 hover:bg-slate-50 hover:text-slate-800 shadow-sm'
          }`}
        >
          <Plus className="w-4 h-4" />
          <span>Toma de Pedido</span>
        </button>

        <button
          onClick={() => setSubView('CATALOG_VIEW')}
          className={`px-4 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2 cursor-pointer shrink-0 border ${
            subView === 'CATALOG_VIEW'
              ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white border-emerald-400 shadow-md shadow-emerald-500/10'
              : 'bg-white/90 text-slate-600 border-slate-200/60 hover:bg-slate-50 hover:text-slate-800 shadow-sm'
          }`}
        >
          <Eye className="w-4 h-4" />
          <span>Catálogo</span>
        </button>

        <button
          onClick={() => setSubView('PRICE_LISTS')}
          className={`px-4 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2 cursor-pointer shrink-0 border ${
            subView === 'PRICE_LISTS'
              ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white border-emerald-400 shadow-md shadow-emerald-500/10'
              : 'bg-white/90 text-slate-600 border-slate-200/60 hover:bg-slate-50 hover:text-slate-800 shadow-sm'
          }`}
        >
          <Calculator className="w-4 h-4" />
          <span>Listas de Precios</span>
        </button>

        {currentUser.role === 'Administrador' && (
          <button
            onClick={() => setSubView('MASSIVE_UPDATE')}
            className={`px-4 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2 cursor-pointer shrink-0 border ${
              subView === 'MASSIVE_UPDATE'
                ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white border-emerald-400 shadow-md shadow-emerald-500/10'
                : 'bg-white/90 text-slate-600 border-slate-200/60 hover:bg-slate-50 hover:text-slate-800 shadow-sm'
            }`}
          >
            <TrendingUp className="w-4 h-4" />
            <span>Act. Masiva</span>
          </button>
        )}

        <button
          onClick={() => setSubView('PROMOTIONS_LIST')}
          className={`px-4 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2 cursor-pointer shrink-0 border ${
            subView === 'PROMOTIONS_LIST'
              ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white border-emerald-400 shadow-md shadow-emerald-500/10'
              : 'bg-white/90 text-slate-600 border-slate-200/60 hover:bg-slate-50 hover:text-slate-800 shadow-sm'
          }`}
        >
          <Tag className="w-4 h-4" />
          <span>Promociones</span>
        </button>

        <button
          onClick={() => setSubView('SALES_STATS')}
          className={`px-4 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2 cursor-pointer shrink-0 border ${
            subView === 'SALES_STATS'
              ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white border-emerald-400 shadow-md shadow-emerald-500/10'
              : 'bg-white/90 text-slate-600 border-slate-200/60 hover:bg-slate-50 hover:text-slate-800 shadow-sm'
          }`}
        >
          <Percent className="w-4 h-4" />
          <span>Estadísticas</span>
        </button>

        <button
          onClick={() => setSubView('CUENTAS_CORRIENTES')}
          className={`px-4 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2 cursor-pointer shrink-0 border ${
            subView === 'CUENTAS_CORRIENTES'
              ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white border-emerald-400 shadow-md shadow-emerald-500/10'
              : 'bg-white/90 text-slate-600 border-slate-200/60 hover:bg-slate-50 hover:text-slate-800 shadow-sm'
          }`}
        >
          <Coins className="w-4 h-4" />
          <span>Cuentas Corrientes</span>
        </button>

        <button
          onClick={() => setSubView('PRODUCT_ADMIN')}
          className={`px-4 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2 cursor-pointer shrink-0 border ${
            subView === 'PRODUCT_ADMIN'
              ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white border-emerald-400 shadow-md shadow-emerald-500/10'
              : 'bg-white/90 text-slate-600 border-slate-200/60 hover:bg-slate-50 hover:text-slate-800 shadow-sm'
          }`}
        >
          <Package className="w-4 h-4" />
          <span>Config. Productos</span>
        </button>

        <button
          onClick={() => window.location.reload()}
          className="px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-1.5 cursor-pointer shrink-0 border bg-white/90 text-slate-600 border-slate-200/60 hover:bg-slate-50 hover:text-blue-600 shadow-sm ml-auto"
          title="Refrescar datos de ventas"
        >
          <RotateCw className="w-4 h-4 text-blue-600" />
          <span>Refrescar</span>
        </button>
      </div>

      {/* ===================================================
          PANTALLA 1: PANEL / LISTADO DE PEDIDOS
          =================================================== */}
      {subView === 'LIST_ORDERS' && (
        <div className="space-y-6 animate-in fade-in duration-200">
          <header className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
              <h1 className="text-3xl font-extrabold text-[#212529]">
                Pedidos de Clientes y Ventas
              </h1>
              <p className="text-lg text-neutral-700 font-semibold mt-1">
                Visualice, controle estados de entrega y gestione facturas de la distribuidora.
              </p>
            </div>
            <button
              onClick={() => {
                setSubView('CREATE_ORDER');
                setNewOrderCustomer(null);
                setNewOrderItems([]);
                setOrderError('');
              }}
              className="w-full sm:w-auto bg-[#0D6EFD] hover:bg-[#0b5ed7] text-white font-extrabold text-lg py-3.5 px-6 rounded-xl flex items-center justify-center gap-2 cursor-pointer"
            >
              <Plus className="w-6 h-6" />
              <span>Nueva Toma de Pedido</span>
            </button>
          </header>

          {/* FILTROS */}
          <div className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-5 space-y-4">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div className="flex flex-col gap-1.5">
                <label htmlFor="ord-search" className="text-base font-bold text-neutral-800">
                  Buscar por Cliente o Código
                </label>
                <div className="relative">
                  <span className="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                    <Search className="h-5 w-5 text-neutral-600" />
                  </span>
                  <input
                    id="ord-search"
                    type="text"
                    className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl pl-11 pr-4 py-3 text-base font-semibold text-[#212529]"
                    placeholder="Ej: Don Luis o ORD-1001..."
                    value={orderSearch}
                    onChange={(e) => setOrderSearch(e.target.value)}
                  />
                </div>
              </div>

              <div className="flex flex-col gap-1.5">
                <label htmlFor="ord-status" className="text-base font-bold text-neutral-800">
                  Filtrar por Estado Comercial
                </label>
                <select
                  id="ord-status"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-base font-semibold text-[#212529]"
                  value={orderStatusFilter}
                  onChange={(e) => setOrderStatusFilter(e.target.value)}
                >
                  <option value="TODOS">Todos los estados</option>
                  <option value="Pendiente">Pendiente</option>
                  <option value="Facturado">Facturado</option>
                  <option value="Remitido">Remitido</option>
                  <option value="Entregado">Entregado</option>
                  <option value="Cancelado">Cancelado</option>
                </select>
              </div>
            </div>
          </div>

          {filteredOrders.length === 0 ? (
            <div className="p-12 text-center bg-[#E9ECEF] rounded-2xl border-2 border-[#DEE2E6]">
              <p className="text-xl font-bold text-[#212529]">No se encontraron pedidos.</p>
              <p className="text-base text-neutral-700 mt-1">Pruebe modificando los filtros de búsqueda.</p>
            </div>
          ) : (
            <>
              {/* TABLA DE ESCRITORIO */}
              <div className="hidden md:block overflow-x-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-[#E9ECEF] border-2 border-[#DEE2E6] text-[#212529]">
                      <th className="p-4 font-extrabold text-lg">N° Pedido / Fecha</th>
                      <th className="p-4 font-extrabold text-lg">Cliente</th>
                      <th className="p-4 font-extrabold text-lg text-right">Importe Total</th>
                      <th className="p-4 font-extrabold text-lg">Estado</th>
                      <th className="p-4 font-extrabold text-lg text-center">Acción</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y-2 divide-[#DEE2E6]">
                    {filteredOrders.map(o => (
                      <tr key={o.id} className="bg-[#E9ECEF] border-x-2 border-b-2 border-[#DEE2E6] hover:bg-[#DEE2E6]/60 transition-colors">
                        <td className="p-4">
                          <span className="font-extrabold text-xl text-[#212529] block">{o.id}</span>
                          <span className="text-sm font-semibold text-neutral-700 block mt-0.5">
                            Fecha: {o.date}
                          </span>
                        </td>
                        <td className="p-4">
                          <span className="font-extrabold text-lg text-[#212529] block">{o.customerName}</span>
                          {o.invoiceNumber && (
                            <span className="inline-block mt-1 text-xs font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded border border-green-200">
                              📄 {o.invoiceNumber}
                            </span>
                          )}
                        </td>
                        <td className="p-4 text-right font-black text-xl text-[#198754]">
                          ${o.total.toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                        </td>
                        <td className="p-4">
                          <span className={`inline-block px-3 py-1.5 rounded-xl text-sm font-black border-2 ${
                            o.status === 'Pendiente' ? 'bg-amber-100 text-amber-800 border-amber-300' :
                            o.status === 'Facturado' ? 'bg-blue-100 text-blue-800 border-blue-300' :
                            o.status === 'Remitido' ? 'bg-purple-100 text-purple-800 border-purple-300' :
                            o.status === 'Entregado' ? 'bg-green-100 text-green-800 border-green-300' :
                            'bg-red-100 text-red-800 border-red-300'
                          }`}>
                            {o.status}
                          </span>
                        </td>
                        <td className="p-4 text-center">
                          <button
                            onClick={() => {
                              setSelectedOrder(o);
                              setSubView('VIEW_ORDER_DETAIL');
                            }}
                            className="bg-white hover:bg-neutral-200 border-2 border-[#DEE2E6] text-neutral-900 px-4 py-2 rounded-xl font-bold flex items-center gap-1.5 mx-auto cursor-pointer"
                          >
                            <FileText className="w-5 h-5 text-[#0D6EFD]" />
                            <span>Ver Ficha / Gestionar</span>
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {/* CARDS EN MÓVIL */}
              <div className="block md:hidden space-y-4">
                {filteredOrders.map(o => (
                  <div key={o.id} className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-5 space-y-4">
                    <div className="flex justify-between items-start">
                      <div>
                        <span className="font-extrabold text-2xl text-[#212529] block">{o.id}</span>
                        <span className="text-sm font-bold text-neutral-700 mt-1 block">F: {o.date}</span>
                      </div>
                      <span className={`inline-block px-3 py-1 rounded-xl text-sm font-black border-2 ${
                        o.status === 'Pendiente' ? 'bg-amber-100 text-amber-800 border-amber-300' :
                        o.status === 'Facturado' ? 'bg-blue-100 text-blue-800 border-blue-300' :
                        o.status === 'Remitido' ? 'bg-purple-100 text-purple-800 border-purple-300' :
                        o.status === 'Entregado' ? 'bg-green-100 text-green-800 border-green-300' :
                        'bg-red-100 text-red-800 border-red-300'
                      }`}>
                        {o.status}
                      </span>
                    </div>

                    <div className="border-y border-[#DEE2E6] py-2">
                      <span className="text-lg font-bold text-neutral-800 block">{o.customerName}</span>
                      {o.invoiceNumber && (
                        <p className="text-xs font-bold text-green-700 mt-1 bg-green-50 px-2 py-0.5 rounded border border-green-100 inline-block">
                          📄 {o.invoiceNumber}
                        </p>
                      )}
                    </div>

                    <div className="flex justify-between items-center">
                      <div>
                        <span className="text-xs text-neutral-600 font-bold block uppercase">Importe total</span>
                        <span className="text-2xl font-black text-[#198754]">
                          ${o.total.toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                        </span>
                      </div>
                      <button
                        onClick={() => {
                          setSelectedOrder(o);
                          setSubView('VIEW_ORDER_DETAIL');
                        }}
                        className="bg-white hover:bg-neutral-200 border-2 border-[#DEE2E6] text-neutral-900 px-4 py-3 rounded-xl font-bold flex items-center gap-2 cursor-pointer"
                      >
                        <FileText className="w-5 h-5 text-[#0D6EFD]" />
                        <span>Ficha</span>
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}
        </div>
      )}

      {/* ===================================================
          PANTALLA 2: TOMA DE PEDIDO / NUEVO PEDIDO (POS EXPRESS LAYOUT)
          =================================================== */}
      {subView === 'CREATE_ORDER' && (
        <SalesPOS 
          currentUser={currentUser} 
          products={products} 
          priceLists={priceLists} 
          customers={customers} 
          promotions={promotions} 
          onSaveOrder={handleSaveOrderFromPOS} 
          onCerrarCaja={() => setSubView('LIST_ORDERS')} 
        />
      )}

      {/* ===================================================
          PANTALLA 3: FICHA DE DETALLE DEL PEDIDO
          =================================================== */}
      {subView === 'VIEW_ORDER_DETAIL' && selectedOrder && (
        <div className="space-y-6 animate-in fade-in duration-200">
          <header className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <button
              onClick={() => setSubView('LIST_ORDERS')}
              className="flex items-center gap-2 text-[#0D6EFD] font-bold text-lg hover:underline cursor-pointer"
            >
              <ArrowLeft className="w-6 h-6" />
              <span>Volver a Pedidos</span>
            </button>
            
            <div className="flex gap-2">
              <button
                onClick={() => setShowRemitoPreview(true)}
                className="bg-[#FAFAFA] hover:bg-[#DEE2E6] border-2 border-[#DEE2E6] text-neutral-900 px-4 py-2.5 rounded-xl font-bold flex items-center gap-1.5 cursor-pointer shadow-sm"
              >
                <Printer className="w-5 h-5 text-neutral-700" />
                <span>Ver / Imprimir Remito</span>
              </button>
              {selectedOrder.invoiceNumber && (
                <button
                  onClick={() => setShowInvoicePreview(true)}
                  className="bg-blue-600 hover:bg-blue-700 text-white border-2 border-blue-700 px-4 py-2.5 rounded-xl font-bold flex items-center gap-1.5 cursor-pointer shadow-sm"
                >
                  <Receipt className="w-5 h-5" />
                  <span>Ver Factura AFIP</span>
                </button>
              )}
            </div>
          </header>

          <div className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-6 md:p-8 space-y-6">
            
            {/* ENCABEZADO DETALLE */}
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b-2 border-[#DEE2E6] pb-4">
              <div>
                <span className="text-xs font-bold text-neutral-600 block uppercase">Ficha de Pedido Comercial</span>
                <h1 className="text-3xl font-black text-[#212529] mt-0.5">{selectedOrder.id}</h1>
                <p className="text-base text-neutral-700 font-bold mt-1">Registrado el {selectedOrder.date}</p>
              </div>

              <div className="flex flex-col sm:items-end gap-2">
                <span className="text-xs font-bold text-neutral-600 uppercase block">Estado Actual:</span>
                <span className={`inline-block px-4 py-2 rounded-xl text-lg font-black border-2 ${
                  selectedOrder.status === 'Pendiente' ? 'bg-amber-100 text-amber-800 border-amber-300' :
                  selectedOrder.status === 'Facturado' ? 'bg-blue-100 text-blue-800 border-blue-300' :
                  selectedOrder.status === 'Remitido' ? 'bg-purple-100 text-purple-800 border-purple-300' :
                  selectedOrder.status === 'Entregado' ? 'bg-green-100 text-green-800 border-green-300' :
                  'bg-red-100 text-red-800 border-red-300'
                }`}>
                  {selectedOrder.status}
                </span>
              </div>
            </div>

            {/* DATOS COMERCIO */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 bg-white border-2 border-[#DEE2E6] rounded-2xl p-5">
              <div>
                <span className="text-sm font-bold text-neutral-600 uppercase block">Comercio / Cliente:</span>
                <p className="text-xl font-black text-[#212529] mt-1">{selectedOrder.customerName}</p>
                
                {(() => {
                  const cust = customers.find(c => c.id === selectedOrder.customerId);
                  if (!cust) return null;
                  return (
                    <div className="text-sm font-semibold text-neutral-800 mt-2 space-y-1">
                      <p>CUIT: {cust.cuit}</p>
                      <p>Teléfono: {cust.phone}</p>
                      <p>Dirección: {cust.address}</p>
                      <p>Zona comercial: {cust.zone}</p>
                    </div>
                  );
                })()}
              </div>

              <div className="space-y-3">
                <span className="text-sm font-bold text-neutral-600 uppercase block">Integraciones y Documentos:</span>
                {selectedOrder.invoiceNumber ? (
                  <div className="bg-green-50 border border-green-300 p-4 rounded-xl flex items-start gap-3">
                    <Receipt className="w-7 h-7 text-[#198754] shrink-0 mt-0.5" />
                    <div className="flex-1">
                      <p className="font-extrabold text-[#198754] text-base">Comprobante Electrónico ARCA / AFIP</p>
                      <p className="text-lg font-black text-neutral-900 mt-1">{selectedOrder.invoiceNumber}</p>
                      <p className="text-xs text-neutral-700 font-bold mt-1">✓ Transmisión completada y procesada.</p>
                      <button
                        onClick={() => setShowInvoicePreview(true)}
                        className="mt-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2 px-4 rounded-lg flex items-center gap-1.5 cursor-pointer shadow-sm"
                      >
                        <Printer className="w-4 h-4" />
                        <span>Ver Factura AFIP</span>
                      </button>
                    </div>
                  </div>
                ) : (
                  <div className="bg-amber-50 border border-amber-300 p-4 rounded-xl flex items-start gap-3">
                    <AlertTriangle className="w-7 h-7 text-amber-600 shrink-0 mt-0.5" />
                    <div>
                      <p className="font-extrabold text-amber-800 text-base">Pendiente de Facturación</p>
                      <p className="text-sm text-neutral-800 mt-1">Este pedido no posee factura electrónica asociada en ARCA.</p>
                      <button
                        onClick={handleGenerateInvoice}
                        className="mt-3 bg-[#198754] hover:bg-[#146c43] text-white font-bold text-sm py-2 px-4 rounded-lg flex items-center gap-1.5 cursor-pointer"
                      >
                        <Receipt className="w-4 h-4" />
                        <span>Simular Generación Factura A</span>
                      </button>
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* ARTICULOS */}
            <div className="space-y-4">
              <h3 className="text-xl font-bold text-[#212529]">Artículos Incluidos:</h3>
              
              <div className="overflow-x-auto">
                <table className="w-full text-left bg-white border-2 border-[#DEE2E6] rounded-2xl overflow-hidden">
                  <thead>
                    <tr className="bg-[#DEE2E6] text-[#212529] border-b border-[#DEE2E6]">
                      <th className="p-4 font-bold text-base">Artículo</th>
                      <th className="p-4 font-bold text-base text-center">Cantidad</th>
                      <th className="p-4 font-bold text-base text-right">Precio Unitario</th>
                      <th className="p-4 font-bold text-base text-right">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[#DEE2E6]">
                    {selectedOrder.items.map((item, index) => (
                      <tr key={index}>
                        <td className="p-4 text-base font-extrabold text-[#212529]">{item.productName}</td>
                        <td className="p-4 text-center text-base font-bold text-neutral-800">{item.quantity} unidades</td>
                        <td className="p-4 text-right text-base font-semibold text-neutral-800">${item.unitPrice.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</td>
                        <td className="p-4 text-right text-base font-black text-[#198754]">${item.total.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              <div className="bg-white border-2 border-[#DEE2E6] rounded-2xl p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div className="max-w-md">
                  <span className="text-xs font-bold text-neutral-600 block uppercase">Notas de entrega registradas</span>
                  <p className="text-base text-neutral-800 font-bold mt-1 leading-relaxed">
                    {selectedOrder.notes || 'Ninguna nota especial cargada.'}
                  </p>
                </div>

                <div className="text-right">
                  <span className="text-xs text-neutral-600 font-bold block uppercase">Total Facturado</span>
                  <span className="text-4xl font-black text-[#198754] block mt-1">
                    ${selectedOrder.total.toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                  </span>
                </div>
              </div>
            </div>

            {/* CONTROL DE CAMBIO DE ESTADOS */}
            <div className="pt-6 border-t border-[#DEE2E6] space-y-4">
              <span className="text-lg font-bold text-[#212529] block">Modificar Estado de Entrega de forma manual:</span>
              
              <div className="flex flex-wrap gap-3">
                <button
                  onClick={() => handleUpdateOrderStatus('Pendiente')}
                  className={`px-4 py-2 rounded-xl font-bold border-2 cursor-pointer ${
                    selectedOrder.status === 'Pendiente'
                      ? 'bg-amber-100 text-amber-800 border-amber-400'
                      : 'bg-white border-[#DEE2E6] text-neutral-700 hover:bg-[#DEE2E6]'
                  }`}
                >
                  Marcar como Pendiente
                </button>

                <button
                  onClick={() => handleUpdateOrderStatus('Remitido')}
                  className={`px-4 py-2 rounded-xl font-bold border-2 cursor-pointer ${
                    selectedOrder.status === 'Remitido'
                      ? 'bg-purple-100 text-purple-800 border-purple-400'
                      : 'bg-white border-[#DEE2E6] text-neutral-700 hover:bg-[#DEE2E6]'
                  }`}
                >
                  Generar Remito de Chofer (Remitido)
                </button>

                <button
                  onClick={() => handleUpdateOrderStatus('Entregado')}
                  className={`px-4 py-2 rounded-xl font-bold border-2 cursor-pointer ${
                    selectedOrder.status === 'Entregado'
                      ? 'bg-green-100 text-green-800 border-green-400'
                      : 'bg-white border-[#DEE2E6] text-neutral-700 hover:bg-[#DEE2E6]'
                  }`}
                >
                  Marcar como Entregado
                </button>

                <button
                  onClick={() => handleUpdateOrderStatus('Cancelado')}
                  className={`px-4 py-2 rounded-xl font-bold border-2 cursor-pointer ${
                    selectedOrder.status === 'Cancelado'
                      ? 'bg-red-100 text-red-800 border-red-400'
                      : 'bg-white border-[#DEE2E6] text-neutral-700 hover:bg-[#DEE2E6]'
                  }`}
                >
                  Anular / Cancelar Pedido
                </button>
              </div>
            </div>

          </div>
        </div>
      )}

      {/* ===================================================
          PANTALLA 4: CATÁLOGO WEB (SOLO LECTURA)
          =================================================== */}
      {subView === 'CATALOG_VIEW' && (
        <div className="space-y-6 animate-in fade-in duration-200">
          <header>
            <h1 className="text-3xl font-extrabold text-[#212529]">
              Catálogo de Productos y Precios
            </h1>
            <p className="text-lg text-neutral-700 font-semibold mt-1">
              Consulta rápida del catálogo de artículos, alícuotas IVA y estado real de existencias en depósito.
            </p>
          </header>

          {/* Buscador de Catálogo */}
          <div className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="flex flex-col gap-1.5">
              <label htmlFor="cat-search" className="text-base font-bold text-neutral-800">
                Buscar por Nombre o Código EAN
              </label>
              <div className="relative">
                <span className="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                  <Search className="h-5 w-5 text-neutral-600" />
                </span>
                <input
                  id="cat-search"
                  type="text"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl pl-11 pr-4 py-3 text-base font-semibold text-[#212529]"
                  placeholder="Ej: Aceite, Yerba..."
                  value={catalogSearch}
                  onChange={(e) => setCatalogSearch(e.target.value)}
                />
              </div>
            </div>

            <div className="flex flex-col gap-1.5">
              <label htmlFor="cat-cat" className="text-base font-bold text-neutral-800">
                Filtrar por Categoría / Rubro
              </label>
              <select
                id="cat-cat"
                className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-base font-semibold text-[#212529]"
                value={catalogCategory}
                onChange={(e) => setCatalogCategory(e.target.value)}
              >
                <option value="Todas">Ver todas las categorías</option>
                {categories.map(c => (
                  <option key={c} value={c}>{c}</option>
                ))}
              </select>
            </div>
          </div>

          {/* Grilla de productos en catálogo */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {products
              .filter(p => {
                const matchesSearch = p.name.toLowerCase().includes(catalogSearch.toLowerCase()) || p.code.toLowerCase().includes(catalogSearch.toLowerCase());
                const matchesCat = catalogCategory === 'Todas' || p.category === catalogCategory;
                return matchesSearch && matchesCat;
              })
              .map(p => {
                const isCritical = p.stock <= p.minStock;
                return (
                  <div key={p.id} className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-5 flex flex-col justify-between gap-4">
                    <div className="space-y-2">
                      <div className="flex justify-between items-start gap-2">
                        <span className="text-xs font-black text-neutral-500 uppercase tracking-wide">Código: {p.code}</span>
                        <span className="bg-white border border-[#DEE2E6] rounded-md px-2 py-0.5 text-xs font-bold text-[#0D6EFD]">{p.category}</span>
                      </div>
                      <h3 className="text-xl font-extrabold text-[#212529] leading-tight min-h-[50px]">{p.name}</h3>
                      
                      <div className="border-t border-[#DEE2E6] pt-3 flex justify-between items-end">
                        <div>
                          <span className="text-xs text-neutral-600 font-bold block uppercase">Precio Base (Minorista)</span>
                          <span className="text-3xl font-black text-[#198754]">
                            ${p.price.toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                          </span>
                        </div>
                        <span className="text-xs font-bold text-neutral-700">IVA: {p.iva}</span>
                      </div>
                    </div>

                    <div className="bg-[#FAFAFA] border border-[#DEE2E6] rounded-xl p-3 flex justify-between items-center mt-2">
                      <div>
                        <span className="text-xs text-neutral-600 font-bold block">Stock Depósito:</span>
                        <span className={`text-xl font-extrabold ${isCritical ? 'text-[#DC3545]' : 'text-neutral-900'}`}>{p.stock} unidades</span>
                      </div>
                      
                      {isCritical ? (
                        <span className="bg-red-100 text-[#DC3545] font-black text-xs px-2.5 py-1 rounded-lg border border-red-200">Stock Crítico</span>
                      ) : (
                        <span className="bg-green-100 text-[#198754] font-black text-xs px-2.5 py-1 rounded-lg border border-green-200">Disponible</span>
                      )}
                    </div>
                  </div>
                );
              })}
          </div>
        </div>
      )}

      {/* ===================================================
          PANTALLA 5: GESTIÓN DE LISTAS DE PRECIOS
          =================================================== */}
      {subView === 'PRICE_LISTS' && (
        <div className="space-y-6 animate-in fade-in duration-200">
          <header>
            <h1 className="text-3xl font-extrabold text-[#212529]">
              Matrices de Listas de Precios
            </h1>
            <p className="text-lg text-neutral-700 font-semibold mt-1">
              Configure los descuentos porcentuales asignados a cada tipo de cliente de la distribuidora.
            </p>
          </header>

          <div className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-6 md:p-8 space-y-6">
            <div className="flex items-start gap-4 bg-blue-50 border-2 border-blue-200 p-4 rounded-xl text-blue-900">
              <Layers className="w-8 h-8 shrink-0 text-[#0D6EFD]" />
              <p className="text-base font-bold text-[#212529] leading-relaxed">
                Asignación Inteligente de Descuentos: Modificar estos valores alterará en tiempo real la facturación de todos los preventistas al generar nuevas órdenes según la ficha del cliente.
              </p>
            </div>

            <div className="space-y-4">
              {priceLists.map(list => (
                <div key={list.id} className="bg-white border-2 border-[#DEE2E6] rounded-2xl p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                  <div className="space-y-1 max-w-md">
                    <h3 className="text-xl font-extrabold text-[#212529]">{list.name}</h3>
                    <p className="text-sm font-semibold text-neutral-600 leading-normal">{list.description}</p>
                  </div>

                  <div className="flex items-center gap-4 w-full md:w-auto">
                    <div className="flex flex-col gap-1 w-full md:w-40">
                      <label htmlFor={`l-pct-${list.id}`} className="text-xs font-black text-neutral-600 uppercase">Porcentaje descuento</label>
                      <div className="relative">
                        <input
                          id={`l-pct-${list.id}`}
                          type="number"
                          min="0"
                          max="90"
                          disabled={list.id === '1'} // Minorista no lleva descuento base
                          className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl pr-10 pl-3 py-2 text-lg font-bold text-neutral-900 disabled:bg-neutral-100 disabled:text-neutral-500"
                          value={list.discountPercentage}
                          onChange={(e) => handleUpdateListDiscount(list.id, parseInt(e.target.value) || 0)}
                        />
                        <span className="absolute right-3.5 top-2.5 font-bold text-neutral-500">%</span>
                      </div>
                    </div>

                    <div className="bg-[#E9ECEF] border border-[#DEE2E6] rounded-xl p-3.5 text-center shrink-0 min-w-[120px]">
                      <span className="text-[10px] text-neutral-600 font-bold block uppercase">Factor Calculado</span>
                      <span className="text-xl font-black text-[#0D6EFD] block mt-0.5">x{(1 - list.discountPercentage / 100).toFixed(2)}</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* ===================================================
          PANTALLA 6: ACTUALIZACIÓN MASIVA DE PRECIOS
          =================================================== */}
      {subView === 'MASSIVE_UPDATE' && currentUser.role === 'Administrador' && (
        <div className="space-y-6 animate-in fade-in duration-200">
          <header>
            <h1 className="text-3xl font-extrabold text-[#212529]">
              Actualización Masiva de Precios (Gerencial)
            </h1>
            <p className="text-lg text-neutral-700 font-semibold mt-1">
              Herramienta de aumento masivo de precios base del catálogo por porcentaje o rubro para mitigar inflación de costos.
            </p>
          </header>

          {massiveError && (
            <div className="p-4 bg-red-100 border-l-4 border-[#DC3545] text-[#DC3545] rounded-r-md flex items-start gap-3" role="alert">
              <AlertTriangle className="w-6 h-6 shrink-0 mt-0.5" />
              <div>
                <p className="font-bold text-lg">Error:</p>
                <p className="text-base font-bold">{massiveError}</p>
              </div>
            </div>
          )}

          {massiveSuccess && (
            <div className="p-4 bg-green-100 border-l-4 border-[#198754] text-[#198754] rounded-r-md flex items-start gap-3" role="alert">
              <CheckCircle2 className="w-6 h-6 shrink-0" />
              <div>
                <p className="font-bold text-lg">Actualización Masiva Procesada:</p>
                <p className="text-base font-bold text-neutral-800 leading-relaxed">{massiveSuccess}</p>
              </div>
            </div>
          )}

          <form onSubmit={handleMassivePriceUpdate} className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-6 md:p-8 space-y-6">
            
            <div className="bg-red-50 border-2 border-red-200 p-5 rounded-2xl flex items-start gap-4 text-red-900">
              <AlertTriangle className="w-10 h-10 shrink-0 text-[#DC3545] mt-1" />
              <div className="space-y-1">
                <h3 className="text-xl font-black text-[#212529]">Peligro: Acción Crítica No Reversible</h3>
                <p className="text-base font-semibold leading-relaxed">
                  Esta acción actualizará de forma masiva los precios base del stock seleccionado de la distribuidora. Los preventistas verán reflejado el impacto en sus celulares de manera instantánea.
                </p>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              
              {/* Acción */}
              <div className="flex flex-col gap-2">
                <label htmlFor="m-dir" className="text-lg font-bold text-[#212529]">
                  Tipo de Operación Comercial
                </label>
                <select
                  id="m-dir"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                  value={massiveDirection}
                  onChange={(e) => setMassiveDirection(e.target.value as 'AUMENTAR' | 'DISMINUIR')}
                >
                  <option value="AUMENTAR">Aumentar Precios Base (+)</option>
                  <option value="DISMINUIR">Disminuir Precios Base (-)</option>
                </select>
              </div>

              {/* Categoría */}
              <div className="flex flex-col gap-2">
                <label htmlFor="m-cat" className="text-lg font-bold text-[#212529]">
                  Categoría de Productos a Afectar
                </label>
                <select
                  id="m-cat"
                  className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl px-4 py-3 text-lg font-semibold text-[#212529]"
                  value={massiveCategory}
                  onChange={(e) => setMassiveCategory(e.target.value)}
                >
                  <option value="Todas">Todas las categorías (Catálogo entero)</option>
                  {categories.map(c => (
                    <option key={c} value={c}>{c}</option>
                  ))}
                </select>
              </div>

              {/* Porcentaje */}
              <div className="flex flex-col gap-2">
                <label htmlFor="m-pct" className="text-lg font-bold text-[#212529]">
                  Porcentaje de Ajuste *
                </label>
                <div className="relative">
                  <input
                    id="m-pct"
                    type="number"
                    min="1"
                    max="150"
                    className="w-full bg-[#FAFAFA] border-2 border-[#DEE2E6] rounded-xl pr-14 pl-4 py-3 text-lg font-bold text-neutral-900"
                    value={massivePercent}
                    onChange={(e) => setMassivePercent(parseInt(e.target.value) || 0)}
                    required
                  />
                  <span className="absolute right-4 top-3 text-lg font-black text-neutral-500">%</span>
                </div>
              </div>

            </div>

            {/* BOTÓN EJECUTAR */}
            <div className="pt-4 flex justify-end">
              <button
                type="submit"
                className="w-full md:w-auto bg-[#DC3545] hover:bg-[#b02a37] text-white font-black text-xl rounded-xl py-4 px-10 flex items-center justify-center gap-2 cursor-pointer"
              >
                <TrendingUp className="w-6 h-6" />
                <span>Aplicar Actualización Masiva de Precios</span>
              </button>
            </div>

          </form>
        </div>
      )}

      {/* ===================================================
          PANTALLA 7: PANEL DE PROMOCIONES / NUEVA PROMO
          =================================================== */}
      {subView === 'PROMOTIONS_LIST' && (
        <SalesPromotions 
          products={products} 
          promotions={promotions} 
          onUpdatePromotions={onUpdatePromotions} 
        />
      )}

      {/* ===================================================
          PANTALLA 8: HISTORIAL DE VENTAS Y ESTADÍSTICAS COMERCIALES
          =================================================== */}
      {subView === 'SALES_STATS' && (
        <div className="space-y-6 animate-in fade-in duration-200">
          <header>
            <h1 className="text-3xl font-extrabold text-[#212529]">
              Reporte de Rendimiento y Ventas
            </h1>
            <p className="text-lg text-neutral-700 font-semibold mt-1">
              Indicadores clave de facturación para control del dueño de la distribuidora.
            </p>
          </header>

          {/* KPI CARDS */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-5 text-center">
              <span className="text-base font-bold text-neutral-700 block uppercase">Facturación Total (Ventas Netas)</span>
              <span className="text-4xl font-black text-[#198754] block mt-2">
                ${stats.totalAmount.toLocaleString('es-AR', { minimumFractionDigits: 2 })}
              </span>
              <span className="text-xs font-bold text-neutral-600 block mt-2">
                * Excluyendo pedidos anulados o cancelados
              </span>
            </div>

            <div className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-5 text-center">
              <span className="text-base font-bold text-neutral-700 block uppercase">Pedidos Emitidos Activos</span>
              <span className="text-4xl font-black text-[#0D6EFD] block mt-2">
                {stats.count} pedidos
              </span>
              <span className="text-xs font-bold text-neutral-600 block mt-2">
                * Confeccionados por preventistas y administrativos
              </span>
            </div>

            <div className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-5 text-center">
              <span className="text-base font-bold text-neutral-700 block uppercase">Ticket de Compra Promedio</span>
              <span className="text-4xl font-black text-neutral-900 block mt-2">
                ${stats.average.toLocaleString('es-AR', { minimumFractionDigits: 2 })}
              </span>
              <span className="text-xs font-bold text-neutral-600 block mt-2">
                * Relación importe/cantidad de transacciones
              </span>
            </div>
          </div>

          {/* TOP PRODUCTOS MÁS VENDIDOS (BARRAS ESTILO ACCESIBLE) */}
          <div className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-6 space-y-6">
            <h2 className="text-2xl font-black text-[#212529]">
              Volumen de Artículos Vendidos en Depósito:
            </h2>

            {stats.topProducts.length === 0 ? (
              <p className="text-base font-semibold text-neutral-700">Aún no se registran artículos entregados o facturados para graficar estadísticas comerciales.</p>
            ) : (
              <div className="space-y-4">
                {stats.topProducts.map((p, index) => {
                  const maxVal = Math.max(...stats.topProducts.map(tp => tp.qty)) || 1;
                  const pct = Math.min(100, Math.max(10, (p.qty / maxVal) * 100));
                  return (
                    <div key={index} className="space-y-1.5">
                      <div className="flex justify-between items-center text-base font-extrabold text-[#212529]">
                        <span>{p.name}</span>
                        <span className="text-[#198754]">{p.qty} unidades</span>
                      </div>
                      <div className="w-full bg-[#DEE2E6] rounded-full h-7 overflow-hidden border border-[#DEE2E6]">
                        <div 
                          style={{ width: `${pct}%` }}
                          className="bg-[#198754] h-full transition-all duration-500 flex items-center justify-end px-3"
                        >
                          <span className="text-xs font-black text-white">{pct.toFixed(0)}%</span>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      )}

      {subView === 'CUENTAS_CORRIENTES' && (
        <SalesCurrentAccount customers={customers} />
      )}

      {subView === 'PRODUCT_ADMIN' && (
        <SalesProductAdmin 
          products={products} 
          promotions={promotions} 
          onUpdateProducts={onUpdateProducts} 
        />
      )}

      {/* ======================================================= */}
      {/* MODAL REMITO PREVIEW (VER EN PANTALLA / IMPRIMIR)       */}
      {/* ======================================================= */}
      {showRemitoPreview && selectedOrder && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm overflow-y-auto animate-in fade-in duration-200">
          <div className="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-300 flex flex-col max-h-[95vh] my-4">
            
            {/* Header del Modal */}
            <div className="bg-slate-900 text-white px-6 py-4 border-b border-slate-800 flex justify-between items-center text-left shrink-0">
              <div className="flex items-center gap-2">
                <FileText className="w-5 h-5 text-blue-400" />
                <div>
                  <h4 className="text-base font-black tracking-tight">Remito de Entrega de Mercadería</h4>
                  <p className="text-[10px] text-slate-400 mt-0.5 font-semibold">Previsualización de Documento de Despacho Físico</p>
                </div>
              </div>
              <button 
                onClick={() => setShowRemitoPreview(false)}
                className="p-1 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white rounded-lg cursor-pointer transition-all"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Contenido del Documento */}
            <div className="p-6 overflow-y-auto flex-1 space-y-6 text-left">
              <div className="border-4 border-double border-slate-400 p-4 space-y-3 bg-white text-slate-800 shadow-sm font-mono text-xs">
                <div className="flex justify-between items-start border-b-2 border-dashed border-slate-300 pb-2.5">
                  <div>
                    <h3 className="text-base font-black text-slate-900 tracking-tight">DISTRIBUIDORA PIGÜÉ</h3>
                    <p className="text-[9px] text-slate-500 font-bold mt-0.5">SISTEMA ERP DE DESPACHO FISCAL</p>
                    <p className="text-[9px] text-slate-400">Av. Casey 450, Pigüé (B8170), Buenos Aires</p>
                  </div>
                  <div className="text-right">
                    <span className="font-black text-sm bg-slate-100 px-2 py-0.5 rounded border border-slate-200">REMITO R</span>
                    <p className="text-[10px] text-slate-500 font-bold mt-1">NÚMERO: R-0004-0000{selectedOrder.id.slice(-4)}</p>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4 text-[11px] font-bold py-1">
                  <div>
                    <p><strong className="text-slate-400">FECHA EMISIÓN:</strong> {selectedOrder.date}</p>
                    <p><strong className="text-slate-400">PEDIDO REFERENCIA:</strong> {selectedOrder.id}</p>
                  </div>
                  <div>
                    <p><strong className="text-slate-400">ESTADO ENTREGA:</strong> {selectedOrder.status.toUpperCase()}</p>
                    <p><strong className="text-slate-400">TIPO:</strong> DUPLICADO CONTROL COMERCIAL</p>
                  </div>
                </div>

                <div className="bg-neutral-50 p-3 border rounded space-y-1 text-[11px] font-semibold border-slate-300">
                  <p className="font-extrabold text-[#0D6EFD]">DATOS DEL DESTINATARIO / COMERCIO:</p>
                  <p>RAZÓN SOCIAL: <strong className="font-bold text-slate-900">{selectedOrder.customerName}</strong></p>
                  {(() => {
                    const cust = customers.find(c => c.id === selectedOrder.customerId);
                    if (!cust) return null;
                    return (
                      <>
                        <p>CUIT: <strong className="font-mono text-slate-900">{cust.cuit}</strong></p>
                        <p>DIRECCIÓN: <strong className="text-slate-900">{cust.address} ({cust.zone})</strong></p>
                        <p>TELÉFONO: <strong className="text-slate-900">{cust.phone || 'No registrado'}</strong></p>
                      </>
                    );
                  })()}
                </div>

                {/* TABLA DE ARTÍCULOS */}
                <div className="space-y-1.5 pt-3 border-t-2 border-dashed border-slate-300">
                  <span className="text-[10px] font-black text-slate-900 uppercase block tracking-wider font-sans">
                    📦 DETALLE DE MERCADERÍA DETALLADA:
                  </span>
                  <div className="border border-slate-300 rounded overflow-hidden">
                    <div className="bg-slate-100 p-2 grid grid-cols-12 text-slate-500 uppercase font-black text-[9px] border-b border-slate-300">
                      <div className="col-span-8">Artículo</div>
                      <div className="col-span-4 text-center">Cantidad</div>
                    </div>
                    {selectedOrder.items.map((item, i) => (
                      <div key={i} className="p-2 grid grid-cols-12 font-bold text-[11px] border-b border-slate-200 last:border-0 items-center">
                        <div className="col-span-8 text-slate-800 leading-tight font-mono">{item.productName}</div>
                        <div className="col-span-4 text-center text-slate-900 font-black font-mono bg-slate-50 border py-0.5 rounded">
                          {item.quantity} unidades
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                <div className="text-right pt-2 text-xs font-bold font-mono">
                  <span>TOTAL BULTOS CONSOLIDADO: </span>
                  <span className="bg-neutral-100 px-2.5 py-1 rounded border font-black text-sm text-neutral-900">
                    {selectedOrder.items.reduce((sum, item) => sum + item.quantity, 0)} bultos
                  </span>
                </div>

                {/* Firmas */}
                <div className="grid grid-cols-2 gap-8 pt-8 text-[9px] font-extrabold text-slate-500 text-center uppercase tracking-widest font-sans">
                  <div>
                    <div className="border-t border-slate-400 w-28 mx-auto mt-6"></div>
                    <p className="mt-1">Firma Chofer Repartidor</p>
                  </div>
                  <div>
                    <div className="border-t border-slate-400 w-28 mx-auto mt-6"></div>
                    <p className="mt-1">Recibí Conforme (Firma Cliente)</p>
                  </div>
                </div>
              </div>
            </div>

            {/* Footer de Acciones */}
            <div className="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-between shrink-0">
              <button
                onClick={() => setShowRemitoPreview(false)}
                className="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl cursor-pointer transition-all"
              >
                Cerrar Ventana
              </button>
              <button
                onClick={() => {
                  alert("Enviando comando de impresión para el remito R-0004-0000" + selectedOrder.id.slice(-4));
                }}
                className="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl flex items-center gap-1.5 cursor-pointer transition-all"
              >
                <Printer className="w-4 h-4" />
                <span>Imprimir Duplicado</span>
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ======================================================= */}
      {/* MODAL FACTURA PREVIEW (VER EN PANTALLA / IMPRIMIR)       */}
      {/* ======================================================= */}
      {showInvoicePreview && selectedOrder && selectedOrder.invoiceNumber && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm overflow-y-auto animate-in fade-in duration-200">
          <div className="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-300 flex flex-col max-h-[95vh] my-4">
            
            {/* Header del Modal */}
            <div className="bg-slate-900 text-white px-6 py-4 border-b border-slate-800 flex justify-between items-center text-left shrink-0">
              <div className="flex items-center gap-2">
                <Receipt className="w-5 h-5 text-blue-400" />
                <div>
                  <h4 className="text-base font-black tracking-tight font-sans">Comprobante Fiscal Autorizado</h4>
                  <p className="text-[10px] text-slate-400 mt-0.5 font-semibold font-sans">Simulación de Comprobante Electrónico AFIP / ARCA</p>
                </div>
              </div>
              <button 
                onClick={() => setShowInvoicePreview(false)}
                className="p-1 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white rounded-lg cursor-pointer transition-all"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Contenido del Documento */}
            <div className="p-6 overflow-y-auto flex-1 space-y-6 text-left">
              <div className="border-4 border-neutral-900 p-6 bg-white font-sans text-neutral-900 text-left space-y-4">
                <div className="grid grid-cols-12 border-b-2 border-neutral-900 pb-4">
                  <div className="col-span-5">
                    <h4 className="text-2xl font-black tracking-tight text-neutral-900">DISTRIBUIDORA PIGÜÉ</h4>
                    <p className="text-xs font-bold text-neutral-600 mt-1">Distribuidora Comercial Pigüé</p>
                    <p className="text-xs font-semibold text-neutral-500 mt-1">Av. Casey 450, Pigüé (B8170), Buenos Aires</p>
                    <p className="text-xs font-semibold text-neutral-500">Tel: 2923-475678 | info@distribuidorapigue.com</p>
                  </div>

                  <div className="col-span-2 flex flex-col items-center justify-center border-l-2 border-r-2 border-neutral-900 bg-neutral-50">
                    <span className="text-4xl font-black text-neutral-900">
                      {selectedOrder.invoiceNumber.includes('Factura A') ? 'A' : selectedOrder.invoiceNumber.includes('Factura B') ? 'B' : 'C'}
                    </span>
                    <span className="text-[10px] font-black text-center mt-1">CÓD. 011</span>
                  </div>

                  <div className="col-span-5 pl-4 text-right">
                    <h4 className="text-lg font-black text-neutral-900">
                      {selectedOrder.invoiceNumber.includes('Factura A') ? 'FACTURA A' : selectedOrder.invoiceNumber.includes('Factura B') ? 'FACTURA B' : 'FACTURA C'}
                    </h4>
                    <p className="text-xs font-extrabold font-mono text-neutral-800 mt-1">Nº COMP: 0004-{selectedOrder.invoiceNumber.split('-').pop()}</p>
                    <p className="text-xs font-bold text-neutral-600">FECHA: {selectedOrder.date}</p>
                    <p className="text-xs font-semibold text-neutral-500 mt-2">CUIT: 30-71452389-4</p>
                    <p className="text-xs font-semibold text-neutral-500">ING. BRUTOS: 30-71452389-4</p>
                    <p className="text-xs font-semibold text-neutral-500">INICIO ACTIVIDAD: 01/01/2018</p>
                  </div>
                </div>

                <div className="bg-neutral-50 p-3 border rounded-lg space-y-1 text-xs font-semibold border-slate-300">
                  <p className="font-extrabold text-[#0D6EFD]">DATOS DEL COMPRADOR / COMERCIO:</p>
                  <div className="grid grid-cols-2">
                    <div>
                      <p>RAZÓN SOCIAL: <strong className="font-bold text-slate-900">{selectedOrder.customerName}</strong></p>
                      {(() => {
                        const cust = customers.find(c => c.id === selectedOrder.customerId);
                        return cust ? <p>CUIT: <strong className="font-mono font-bold text-slate-900">{cust.cuit}</strong></p> : null;
                      })()}
                    </div>
                    <div className="text-right">
                      <p>CONDICION IVA: <strong className="font-bold text-slate-900">{selectedOrder.invoiceNumber.includes('Factura A') ? 'Resp. Inscripto' : 'Consumidor Final'}</strong></p>
                      <p>ASOCIADO A PEDIDO: <strong className="font-mono font-bold text-slate-900">{selectedOrder.id}</strong></p>
                    </div>
                  </div>
                </div>

                {/* DETALLE ARTICULOS FACTURA */}
                <div className="border border-slate-300 rounded overflow-hidden text-xs">
                  <div className="bg-slate-100 p-2.5 grid grid-cols-12 text-slate-500 uppercase font-black text-[9px] border-b border-slate-300">
                    <div className="col-span-6">Artículo</div>
                    <div className="col-span-2 text-center">Cant.</div>
                    <div className="col-span-2 text-right">P. Unit</div>
                    <div className="col-span-2 text-right">Total</div>
                  </div>
                  {selectedOrder.items.map((item, i) => (
                    <div key={i} className="p-2.5 grid grid-cols-12 font-bold border-b border-slate-200 last:border-0 items-center">
                      <div className="col-span-6 text-slate-800 font-mono leading-tight">{item.productName}</div>
                      <div className="col-span-2 text-center text-slate-900 font-mono">{item.quantity} u.</div>
                      <div className="col-span-2 text-right text-slate-600 font-mono">${item.unitPrice.toLocaleString('es-AR')}</div>
                      <div className="col-span-2 text-right text-[#198754] font-mono font-black">${item.total.toLocaleString('es-AR')}</div>
                    </div>
                  ))}
                </div>

                <div className="border-t-2 border-neutral-950 pt-4 space-y-1 text-right text-xs font-bold">
                  <p className="flex justify-between">
                    <span>SUBTOTAL (NETO GRAVADO):</span>
                    <span className="font-mono font-extrabold">
                      ${(selectedOrder.invoiceNumber.includes('Factura A') 
                        ? Number((selectedOrder.total / 1.21).toFixed(2)) 
                        : selectedOrder.total
                      ).toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                    </span>
                  </p>
                  <p className="flex justify-between text-neutral-700">
                    <span>I.V.A. INSCRIPTO (21% / 10.5%):</span>
                    <span className="font-mono font-extrabold">
                      ${(selectedOrder.invoiceNumber.includes('Factura A') 
                        ? Number((selectedOrder.total - (selectedOrder.total / 1.21)).toFixed(2)) 
                        : 0
                      ).toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                    </span>
                  </p>
                  <p className="flex justify-between text-lg font-black text-neutral-900 border-t pt-2 mt-2">
                    <span>TOTAL FACTURADO:</span>
                    <span className="font-mono">${selectedOrder.total.toLocaleString('es-AR', { minimumFractionDigits: 2 })} ARS</span>
                  </p>
                </div>

                {/* CAE AFIP BARCODE FOOTER */}
                <div className="border-t border-dashed border-slate-400 pt-4 flex flex-col md:flex-row items-center justify-between gap-4 text-xs">
                  <div className="flex items-center gap-2">
                    <div className="p-1 bg-white border border-slate-300">
                      <div className="h-8 w-44 bg-neutral-900 flex items-center justify-center text-white text-[10px] tracking-[6px] font-mono select-none">
                        ||||| | | |||| | ||
                      </div>
                    </div>
                    <span className="text-[10px] font-semibold text-neutral-500 font-mono">Simulado Barcode ARCA</span>
                  </div>

                  <div className="text-right font-semibold text-[11px] space-y-0.5">
                    <p>CAE Nº: <strong className="font-mono font-extrabold">76284910283746</strong></p>
                    <p>VTO CAE: <strong className="font-mono font-extrabold">{new Date(Date.now() + 10 * 24 * 60 * 60 * 1000).toLocaleDateString('es-AR')}</strong></p>
                    <p className="text-[9px] text-[#198754] font-bold">✓ AUTORIZADO POR AFIP DIGITAL</p>
                  </div>
                </div>
              </div>
            </div>

            {/* Footer de Acciones */}
            <div className="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-between shrink-0">
              <button
                onClick={() => setShowInvoicePreview(false)}
                className="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl cursor-pointer transition-all"
              >
                Cerrar Ventana
              </button>
              <button
                onClick={() => {
                  alert("Enviando comando de impresión fiscal para la factura " + selectedOrder.invoiceNumber);
                }}
                className="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl flex items-center gap-1.5 cursor-pointer transition-all"
              >
                <Printer className="w-4 h-4" />
                <span>Imprimir Comprobante</span>
              </button>
            </div>
          </div>
        </div>
      )}

    </div>
  );
}
