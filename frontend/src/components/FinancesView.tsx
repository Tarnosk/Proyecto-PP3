import React, { useState } from 'react';
import { 
  User, 
  Order, 
  Customer, 
  CashMovement, 
  CustomerAccount, 
  CustomerAccountMovement, 
  ElectronicInvoice 
} from '../types';
import { 
  Wallet, 
  DollarSign, 
  TrendingUp, 
  TrendingDown, 
  Plus, 
  Search, 
  FileText, 
  Printer, 
  ArrowDownRight, 
  ArrowUpRight, 
  CreditCard, 
  Users, 
  X, 
  AlertTriangle, 
  CheckCircle,
  FileCheck,
  Scale,
  RefreshCw,
  PlusCircle,
  Activity
} from 'lucide-react';

interface FinancesViewProps {
  currentUser: User;
  orders: Order[];
  customers: Customer[];
  cashMovements: CashMovement[];
  customerAccounts: CustomerAccount[];
  customerAccountMovements: CustomerAccountMovement[];
  electronicInvoices: ElectronicInvoice[];
  onUpdateCashMovements: (movements: CashMovement[]) => void;
  onUpdateCustomerAccounts: (accounts: CustomerAccount[]) => void;
  onUpdateCustomerAccountMovements: (movements: CustomerAccountMovement[]) => void;
  onUpdateElectronicInvoices: (invoices: ElectronicInvoice[]) => void;
  onUpdateOrders: (orders: Order[]) => void;
}

export function FinancesView({
  currentUser,
  orders,
  customers,
  cashMovements,
  customerAccounts,
  customerAccountMovements,
  electronicInvoices,
  onUpdateCashMovements,
  onUpdateCustomerAccounts,
  onUpdateCustomerAccountMovements,
  onUpdateElectronicInvoices,
  onUpdateOrders
}: FinancesViewProps) {
  // Estado para la pestaña activa
  const [activeTab, setActiveTab] = useState<'INVOICES' | 'CASH' | 'ACCOUNTS'>('INVOICES');

  // Estados de búsquedas y filtros
  const [invoiceSearch, setInvoiceSearch] = useState('');
  const [cashSearch, setCashSearch] = useState('');
  const [accountSearch, setAccountSearch] = useState('');
  
  // Estados para formularios y modales
  const [showInvoiceForm, setShowInvoiceForm] = useState(false);
  const [invoiceFormError, setInvoiceFormError] = useState('');
  const [selectedOrderIdForInvoice, setSelectedOrderIdForInvoice] = useState('');
  const [invoiceType, setInvoiceType] = useState<'Factura A' | 'Factura B' | 'Factura C'>('Factura B');
  const [invoicePOS, setInvoicePOS] = useState('0005');
  const [invoiceNumberInput, setInvoiceNumberInput] = useState('');

  // Formulario de movimientos de caja
  const [showCashForm, setShowCashForm] = useState(false);
  const [cashFormError, setCashFormError] = useState('');
  const [cashType, setCashType] = useState<'Ingreso' | 'Egreso'>('Ingreso');
  const [cashAmount, setCashAmount] = useState(0);
  const [cashCategory, setCashCategory] = useState<'Cobranza' | 'Pago Proveedor' | 'Gasto General' | 'Sueldos' | 'Impuestos' | 'Ajuste'>('Gasto General');
  const [cashDescription, setCashDescription] = useState('');
  const [cashPaymentMethod, setCashPaymentMethod] = useState<'Efectivo' | 'Transferencia' | 'Cheque' | 'Tarjeta'>('Efectivo');

  // Formulario para cobrar deuda de cuenta corriente
  const [showPaymentForm, setShowPaymentForm] = useState(false);
  const [paymentFormError, setPaymentFormError] = useState('');
  const [paymentCustomerId, setPaymentCustomerId] = useState('');
  const [paymentAmount, setPaymentAmount] = useState(0);
  const [paymentMethod, setPaymentMethod] = useState<'Efectivo' | 'Transferencia' | 'Cheque'>('Transferencia');
  const [paymentDescription, setPaymentDescription] = useState('Pago / Entrega a cuenta corriente');

  // Vista detalle de cuenta corriente
  const [selectedCustomerAccountDetail, setSelectedCustomerAccountDetail] = useState<CustomerAccount | null>(null);

  // Vista detalle / simulación de PDF de factura electrónica
  const [selectedInvoicePreview, setSelectedInvoicePreview] = useState<ElectronicInvoice | null>(null);

  // --- FILTRADOS ---
  const filteredInvoices = electronicInvoices.filter(i => {
    return i.customerName.toLowerCase().includes(invoiceSearch.toLowerCase()) ||
           i.id.toLowerCase().includes(invoiceSearch.toLowerCase()) ||
           i.invoiceNumber.includes(invoiceSearch);
  });

  const filteredCashMovements = cashMovements.filter(m => {
    return m.description.toLowerCase().includes(cashSearch.toLowerCase()) ||
           m.category.toLowerCase().includes(cashSearch.toLowerCase()) ||
           m.user.toLowerCase().includes(cashSearch.toLowerCase());
  });

  // Asegurar que existan cuentas corrientes para todos los clientes
  const normalizedAccounts = customers.map(c => {
    const existing = customerAccounts.find(a => a.customerId === c.id);
    return existing || {
      customerId: c.id,
      customerName: c.name,
      balance: 0,
      lastActivity: 'Sin actividad'
    };
  });

  const filteredAccounts = normalizedAccounts.filter(a => {
    return a.customerName.toLowerCase().includes(accountSearch.toLowerCase());
  });

  // Órdenes que ya tienen factura
  const invoicedOrderIds = electronicInvoices.map(i => i.orderId).filter(Boolean);
  // Órdenes completadas / entregadas que NO han sido facturadas aún
  const facturablesOrders = orders.filter(o => 
    (o.status === 'Entregado' || o.status === 'Remitido' || o.status === 'Facturado') && 
    !invoicedOrderIds.includes(o.id)
  );

  // --- ACCIONES DE FACTURACIÓN ---
  const handleOpenAddInvoice = () => {
    setInvoiceFormError('');
    setSelectedOrderIdForInvoice(facturablesOrders[0]?.id || '');
    setInvoiceType('Factura B');
    setInvoicePOS('0005');
    
    // Autogenerar siguiente nro de factura
    const lastNumber = electronicInvoices.reduce((max, i) => {
      const num = parseInt(i.invoiceNumber, 10);
      return num > max ? num : max;
    }, 2145);
    setInvoiceNumberInput(String(lastNumber + 1).padStart(8, '0'));
    setShowInvoiceForm(true);
  };

  const handleGenerateInvoice = (e: React.FormEvent) => {
    e.preventDefault();
    setInvoiceFormError('');

    if (!selectedOrderIdForInvoice) {
      setInvoiceFormError('Debe seleccionar un pedido válido para facturar.');
      return;
    }

    const order = orders.find(o => o.id === selectedOrderIdForInvoice);
    if (!order) return;

    // Buscar CUIT del cliente
    const customerObj = customers.find(c => c.name === order.customerName);
    const cuit = customerObj ? customerObj.cuit : '20-99999999-9';

    // Calcular IVA simulado (tasa promedio 21% para mostrar precisión)
    const net = Number((order.total / 1.21).toFixed(2));
    const iva = Number((order.total - net).toFixed(2));

    // Generar CAE simulado de 14 dígitos
    const caeNum = Math.floor(10000000000000 + Math.random() * 90000000000000).toString();
    const caeDueDateStr = new Date();
    caeDueDateStr.setDate(caeDueDateStr.getDate() + 10); // vence en 10 días

    const newInvoice: ElectronicInvoice = {
      id: 'INV-' + (4000 + electronicInvoices.length + 1),
      orderId: order.id,
      customerName: order.customerName,
      customerCuit: cuit,
      type: invoiceType,
      pos: invoicePOS.padStart(4, '0'),
      invoiceNumber: invoiceNumberInput.padStart(8, '0'),
      netAmount: net,
      ivaAmount: iva,
      total: order.total,
      cae: caeNum,
      caeDueDate: caeDueDateStr.toISOString().split('T')[0],
      date: new Date().toISOString().split('T')[0],
      status: 'Aprobada'
    };

    // Actualizar estado del pedido a 'Facturado' si no lo estaba
    const updatedOrders = orders.map(o => {
      if (o.id === order.id) {
        return { ...o, status: 'Facturado' as const, invoiceNumber: `${newInvoice.pos}-${newInvoice.invoiceNumber}` };
      }
      return o;
    });

    // Agregar movimiento automático de ingreso en Caja Diaria
    const newMovement: CashMovement = {
      id: 'MOV-' + Date.now(),
      type: 'Ingreso',
      amount: order.total,
      category: 'Cobranza',
      description: `Facturación Electrónica ${newInvoice.type} Nro ${newInvoice.pos}-${newInvoice.invoiceNumber} (Pedido ${order.id})`,
      date: new Date().toISOString().replace('T', ' ').substring(0, 16),
      user: currentUser.name,
      paymentMethod: 'Transferencia',
      referenceId: order.id
    };

    const updatedInvoices = [newInvoice, ...electronicInvoices];
    const updatedCashMovements = [newMovement, ...cashMovements];

    onUpdateOrders(updatedOrders);
    onUpdateElectronicInvoices(updatedInvoices);
    onUpdateCashMovements(updatedCashMovements);

    localStorage.setItem('erp_distribuidora_electronic_invoices', JSON.stringify(updatedInvoices));
    localStorage.setItem('erp_distribuidora_orders', JSON.stringify(updatedOrders));
    localStorage.setItem('erp_distribuidora_cash_movements', JSON.stringify(updatedCashMovements));

    setShowInvoiceForm(false);
    setSelectedInvoicePreview(newInvoice); // Mostrar comprobante recién generado
  };

  // --- ACCIONES DE CAJA DIARIA ---
  const handleOpenAddCash = () => {
    setCashFormError('');
    setCashType('Egreso');
    setCashAmount(0);
    setCashCategory('Gasto General');
    setCashDescription('');
    setCashPaymentMethod('Efectivo');
    setShowCashForm(true);
  };

  const handleSaveCashMovement = (e: React.FormEvent) => {
    e.preventDefault();
    setCashFormError('');

    if (cashAmount <= 0) {
      setCashFormError('El importe debe ser mayor que cero.');
      return;
    }

    if (!cashDescription.trim()) {
      setCashFormError('Debe ingresar una descripción detallada.');
      return;
    }

    const newMovement: CashMovement = {
      id: 'MOV-' + Date.now(),
      type: cashType,
      amount: cashAmount,
      category: cashCategory,
      description: cashDescription.trim(),
      date: new Date().toISOString().replace('T', ' ').substring(0, 16),
      user: currentUser.name,
      paymentMethod: cashPaymentMethod
    };

    const updatedMovements = [newMovement, ...cashMovements];
    onUpdateCashMovements(updatedMovements);
    localStorage.setItem('erp_distribuidora_cash_movements', JSON.stringify(updatedMovements));
    setShowCashForm(false);
  };

  // --- ACCIONES DE CUENTAS CORRIENTES ---
  const handleOpenPaymentForm = (account: CustomerAccount) => {
    setPaymentFormError('');
    setPaymentCustomerId(account.customerId);
    setPaymentAmount(Math.max(0, account.balance)); // Cargar saldo adeudado por defecto
    setPaymentMethod('Transferencia');
    setPaymentDescription(`Cobranza / Entrega de fondos de ${account.customerName}`);
    setShowPaymentForm(true);
  };

  const handleProcessPayment = (e: React.FormEvent) => {
    e.preventDefault();
    setPaymentFormError('');

    if (paymentAmount <= 0) {
      setPaymentFormError('El importe de cobro debe ser mayor que cero.');
      return;
    }

    const customerObj = customers.find(c => c.id === paymentCustomerId);
    if (!customerObj) return;

    // Crear movimiento de crédito para la cuenta corriente
    const newCCMovement: CustomerAccountMovement = {
      id: 'CAM-' + Date.now(),
      customerId: customerObj.id,
      type: 'Credito', // disminuye deuda
      amount: paymentAmount,
      description: paymentDescription.trim(),
      date: new Date().toISOString().replace('T', ' ').substring(0, 16)
    };

    // Actualizar saldo de cuenta corriente
    const updatedAccounts = normalizedAccounts.map(acc => {
      if (acc.customerId === customerObj.id) {
        return {
          ...acc,
          balance: acc.balance - paymentAmount,
          lastActivity: newCCMovement.date
        };
      }
      return acc;
    });

    // Agregar ingreso automático a la caja diaria
    const newCashMovement: CashMovement = {
      id: 'MOV-' + Date.now(),
      type: 'Ingreso',
      amount: paymentAmount,
      category: 'Cobranza',
      description: `Cobranza de Cta. Cte. - ${customerObj.name} (${newCCMovement.description})`,
      date: newCCMovement.date,
      user: currentUser.name,
      paymentMethod: paymentMethod === 'Transferencia' ? 'Transferencia' : paymentMethod === 'Cheque' ? 'Cheque' : 'Efectivo'
    };

    const updatedCCMovements = [newCCMovement, ...customerAccountMovements];
    const updatedCashMovements = [newCashMovement, ...cashMovements];

    onUpdateCustomerAccounts(updatedAccounts);
    onUpdateCustomerAccountMovements(updatedCCMovements);
    onUpdateCashMovements(updatedCashMovements);

    localStorage.setItem('erp_distribuidora_customer_accounts', JSON.stringify(updatedAccounts));
    localStorage.setItem('erp_distribuidora_customer_account_movements', JSON.stringify(updatedCCMovements));
    localStorage.setItem('erp_distribuidora_cash_movements', JSON.stringify(updatedCashMovements));

    setShowPaymentForm(false);
    
    // Si estaba viendo el detalle de este cliente, actualizarlo
    if (selectedCustomerAccountDetail && selectedCustomerAccountDetail.customerId === customerObj.id) {
      setSelectedCustomerAccountDetail({
        ...selectedCustomerAccountDetail,
        balance: selectedCustomerAccountDetail.balance - paymentAmount,
        lastActivity: newCCMovement.date
      });
    }

    alert(`¡Cobro registrado! Se ingresaron $${paymentAmount.toLocaleString('es-AR')} a la cuenta de ${customerObj.name} y se registró en la caja diaria.`);
  };

  // --- CÁLCULOS GENERALES DE TOTALES ---
  const totalInvoiced = electronicInvoices.reduce((sum, i) => sum + i.total, 0);
  
  const cashInflow = cashMovements.filter(m => m.type === 'Ingreso').reduce((sum, m) => sum + m.amount, 0);
  const cashOutflow = cashMovements.filter(m => m.type === 'Egreso').reduce((sum, m) => sum + m.amount, 0);
  const currentCashBalance = cashInflow - cashOutflow;

  const totalOutstandingDeudas = normalizedAccounts.reduce((sum, a) => sum + (a.balance > 0 ? a.balance : 0), 0);
  const totalSaldosFavor = normalizedAccounts.reduce((sum, a) => sum + (a.balance < 0 ? Math.abs(a.balance) : 0), 0);

  return (
    <div className="space-y-8 animate-in fade-in duration-300">
      
      {/* HEADER DE SECCIÓN */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b-4 border-[#DEE2E6] pb-4">
        <div>
          <h1 className="text-4xl font-extrabold text-[#212529] tracking-tight flex items-center gap-3">
            <Wallet className="w-10 h-10 text-[#0D6EFD]" />
            <span>Facturación, Finanzas y Tesorería</span>
          </h1>
          <p className="text-lg text-neutral-700 font-semibold mt-1">
            Generación de facturas electrónicas (AFIP), control de cuentas corrientes de clientes, cobranzas, egresos y control de caja diaria.
          </p>
        </div>
        
        {/* ACCIONES RÁPIDAS EN EL HEADER */}
        <div className="flex gap-2">
          {activeTab === 'INVOICES' && !showInvoiceForm && (
            <button
              onClick={handleOpenAddInvoice}
              className="px-5 py-3 bg-[#0D6EFD] hover:bg-[#0b5ed7] text-white font-extrabold text-lg rounded-xl flex items-center gap-2 cursor-pointer shadow-md"
            >
              <Plus className="w-6 h-6" />
              <span>Nueva Factura Electrónica</span>
            </button>
          )}

          {activeTab === 'CASH' && !showCashForm && (
            <button
              onClick={handleOpenAddCash}
              className="px-5 py-3 bg-[#DC3545] hover:bg-[#bb2d3b] text-white font-extrabold text-lg rounded-xl flex items-center gap-2 cursor-pointer shadow-md"
            >
              <Plus className="w-6 h-6" />
              <span>Registrar Gasto / Egreso</span>
            </button>
          )}
        </div>
      </div>

      {/* TARJETAS DE MÉTRICAS CLAVE (ALTO CONTRASTE Y LEGIBLES) */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div className="bg-white border-2 border-[#DEE2E6] rounded-2xl p-5 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-sm font-black text-neutral-500 uppercase">Caja Actual (Efectivo/Bancos)</span>
            <Wallet className="w-5 h-5 text-neutral-400" />
          </div>
          <span className={`text-3xl font-black block mt-2 font-mono ${currentCashBalance >= 0 ? 'text-[#198754]' : 'text-[#DC3545]'}`}>
            ${currentCashBalance.toLocaleString('es-AR')}
          </span>
          <div className="flex justify-between text-xs font-semibold text-neutral-500 mt-2">
            <span>Ingresos: +${cashInflow.toLocaleString('es-AR')}</span>
            <span>Egresos: -${cashOutflow.toLocaleString('es-AR')}</span>
          </div>
        </div>

        <div className="bg-white border-2 border-[#DEE2E6] rounded-2xl p-5 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-sm font-black text-neutral-500 uppercase">Facturado AFIP (Mes)</span>
            <FileCheck className="w-5 h-5 text-[#198754]" />
          </div>
          <span className="text-3xl font-black text-[#212529] block mt-2 font-mono">
            ${totalInvoiced.toLocaleString('es-AR')}
          </span>
          <span className="text-xs font-bold text-[#198754] block mt-2 bg-green-50 px-2 py-0.5 rounded border border-green-100">
            ✓ CAE autorizados por AFIP/ARCA
          </span>
        </div>

        <div className="bg-white border-2 border-[#DEE2E6] rounded-2xl p-5 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-sm font-black text-neutral-500 uppercase">Cuentas por Cobrar (Clientes)</span>
            <Users className="w-5 h-5 text-[#0D6EFD]" />
          </div>
          <span className="text-3xl font-black text-[#DC3545] block mt-2 font-mono">
            ${totalOutstandingDeudas.toLocaleString('es-AR')}
          </span>
          <span className="text-xs font-bold text-neutral-500 block mt-2">
            Deuda activa en cuentas corrientes
          </span>
        </div>

        <div className="bg-white border-2 border-[#DEE2E6] rounded-2xl p-5 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-sm font-black text-neutral-500 uppercase">Saldos a Favor (Clientes)</span>
            <Scale className="w-5 h-5 text-amber-500" />
          </div>
          <span className="text-3xl font-black text-blue-700 block mt-2 font-mono">
            ${totalSaldosFavor.toLocaleString('es-AR')}
          </span>
          <span className="text-xs font-bold text-neutral-500 block mt-2">
            Crédito precargado o pagos a cuenta
          </span>
        </div>
      </div>

      {/* PESTAÑAS DE NAVEGACIÓN */}
      <div className="flex border-b-2 border-[#DEE2E6] gap-2 p-1 bg-neutral-100 rounded-xl">
        <button
          onClick={() => {
            setActiveTab('INVOICES');
            setSelectedCustomerAccountDetail(null);
          }}
          className={`flex-1 py-4 px-6 rounded-lg font-black text-lg text-center flex items-center justify-center gap-2 border-2 cursor-pointer transition-all ${
            activeTab === 'INVOICES'
              ? 'bg-[#0D6EFD] border-[#0a58ca] text-white shadow-md'
              : 'bg-white hover:bg-neutral-50 text-[#212529] border-transparent'
          }`}
        >
          <FileText className="w-5 h-5" />
          <span>Facturas AFIP ({electronicInvoices.length})</span>
        </button>

        <button
          onClick={() => {
            setActiveTab('CASH');
            setSelectedCustomerAccountDetail(null);
          }}
          className={`flex-1 py-4 px-6 rounded-lg font-black text-lg text-center flex items-center justify-center gap-2 border-2 cursor-pointer transition-all ${
            activeTab === 'CASH'
              ? 'bg-[#198754] border-[#146c43] text-white shadow-md'
              : 'bg-white hover:bg-neutral-50 text-[#212529] border-transparent'
          }`}
        >
          <Activity className="w-5 h-5" />
          <span>Caja y Tesorería ({cashMovements.length})</span>
        </button>

        <button
          onClick={() => setActiveTab('ACCOUNTS')}
          className={`flex-1 py-4 px-6 rounded-lg font-black text-lg text-center flex items-center justify-center gap-2 border-2 cursor-pointer transition-all ${
            activeTab === 'ACCOUNTS'
              ? 'bg-amber-600 border-amber-700 text-white shadow-md'
              : 'bg-white hover:bg-neutral-50 text-[#212529] border-transparent'
          }`}
        >
          <Users className="w-5 h-5" />
          <span>Cuentas Corrientes ({normalizedAccounts.length})</span>
        </button>
      </div>

      {/* --- FORMULARIO NUEVA FACTURA ELECTRÓNICA --- */}
      {showInvoiceForm && activeTab === 'INVOICES' && (
        <div className="bg-white border-4 border-[#0D6EFD] rounded-2xl p-6 space-y-6 shadow-lg animate-in slide-in-from-top-4 duration-300">
          <div className="flex items-center justify-between border-b pb-4">
            <h3 className="text-2xl font-black text-[#212529] flex items-center gap-2">
              <FileCheck className="w-6 h-6 text-[#0D6EFD]" />
              <span>Generar Factura de Venta AFIP / ARCA</span>
            </h3>
            <button onClick={() => setShowInvoiceForm(false)} className="p-2 bg-neutral-100 text-neutral-700 hover:bg-neutral-200 rounded-lg cursor-pointer">
              <X className="w-6 h-6" />
            </button>
          </div>

          {invoiceFormError && (
            <div className="p-4 bg-red-100 border-2 border-[#DC3545] text-[#DC3545] rounded-xl font-bold flex items-center gap-2">
              <AlertTriangle className="w-5 h-5" />
              <span>{invoiceFormError}</span>
            </div>
          )}

          <form onSubmit={handleGenerateInvoice} className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="md:col-span-2">
              <label className="block text-lg font-bold text-neutral-800 mb-1">Seleccionar Pedido Entregado / Remitido *</label>
              <select
                value={selectedOrderIdForInvoice}
                onChange={(e) => setSelectedOrderIdForInvoice(e.target.value)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-[#0D6EFD] focus:outline-none bg-white cursor-pointer"
              >
                {facturablesOrders.map(o => (
                  <option key={o.id} value={o.id}>
                    Pedido {o.id} - {o.customerName} - Importe: ${o.total.toLocaleString('es-AR')}
                  </option>
                ))}
                {facturablesOrders.length === 0 && (
                  <option value="">No hay pedidos pendientes de facturar en este momento</option>
                )}
              </select>
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Tipo de Factura *</label>
              <select
                value={invoiceType}
                onChange={(e) => setInvoiceType(e.target.value as any)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-[#0D6EFD] focus:outline-none bg-white"
              >
                <option value="Factura B">Factura B (I.V.A. Consumidor Final)</option>
                <option value="Factura A">Factura A (I.V.A. Responsable Inscripto)</option>
                <option value="Factura C">Factura C (Exento / Monotributo)</option>
              </select>
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Punto de Venta (POS)</label>
              <input
                type="text"
                maxLength={4}
                value={invoicePOS}
                onChange={(e) => setInvoicePOS(e.target.value.replace(/\D/g, ''))}
                placeholder="0005"
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold font-mono focus:border-[#0D6EFD] focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Número de Factura AFIP</label>
              <input
                type="text"
                value={invoiceNumberInput}
                onChange={(e) => setInvoiceNumberInput(e.target.value.replace(/\D/g, ''))}
                placeholder="00000001"
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold font-mono focus:border-[#0D6EFD] focus:outline-none"
              />
            </div>

            <div className="flex items-end">
              <div className="bg-neutral-50 p-3 rounded-xl border w-full text-xs font-bold text-neutral-600 flex items-center gap-1">
                <RefreshCw className="w-4 h-4 animate-spin text-[#0D6EFD]" />
                <span>Simulando WSFE V1 Homologación AFIP</span>
              </div>
            </div>

            <div className="md:col-span-3 flex justify-end gap-3 pt-4 border-t">
              <button
                type="button"
                onClick={() => setShowInvoiceForm(false)}
                className="px-6 py-3 bg-neutral-100 hover:bg-neutral-200 border-2 border-neutral-300 text-neutral-800 font-bold rounded-xl cursor-pointer"
              >
                Cancelar
              </button>
              <button
                type="submit"
                className="px-8 py-3 bg-[#0D6EFD] hover:bg-[#0b5ed7] text-white font-extrabold text-lg rounded-xl cursor-pointer shadow flex items-center gap-2"
                disabled={facturablesOrders.length === 0}
              >
                <FileCheck className="w-5 h-5" />
                <span>Emitir y Autorizar CAE AFIP</span>
              </button>
            </div>
          </form>
        </div>
      )}

      {/* --- FORMULARIO NUEVO MOVIMIENTO DE CAJA DIARIA --- */}
      {showCashForm && activeTab === 'CASH' && (
        <div className="bg-white border-4 border-[#DC3545] rounded-2xl p-6 space-y-6 shadow-lg animate-in slide-in-from-top-4 duration-300">
          <div className="flex items-center justify-between border-b pb-4">
            <h3 className="text-2xl font-black text-[#212529] flex items-center gap-2">
              <TrendingDown className="w-6 h-6 text-[#DC3545]" />
              <span>Registrar Gasto / Egreso de Caja Diaria</span>
            </h3>
            <button onClick={() => setShowCashForm(false)} className="p-2 bg-neutral-100 text-neutral-700 hover:bg-neutral-200 rounded-lg cursor-pointer">
              <X className="w-6 h-6" />
            </button>
          </div>

          {cashFormError && (
            <div className="p-4 bg-red-100 border-2 border-[#DC3545] text-[#DC3545] rounded-xl font-bold flex items-center gap-2">
              <AlertTriangle className="w-5 h-5" />
              <span>{cashFormError}</span>
            </div>
          )}

          <form onSubmit={handleSaveCashMovement} className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Importe ($) *</label>
              <input
                type="number"
                min="1"
                required
                value={cashAmount || ''}
                onChange={(e) => setCashAmount(Number(e.target.value))}
                placeholder="Monto en pesos"
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-[#DC3545] focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Categoría del Gasto *</label>
              <select
                value={cashCategory}
                onChange={(e) => setCashCategory(e.target.value as any)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-[#DC3545] focus:outline-none bg-white cursor-pointer"
              >
                <option value="Gasto General">Gasto General / Caja Chica</option>
                <option value="Pago Proveedor">Pago a Proveedores</option>
                <option value="Sueldos">Salarios / Viáticos del personal</option>
                <option value="Impuestos">Impuestos / Tasas municipales / Luz</option>
                <option value="Ajuste">Ajuste técnico de inventario/caja</option>
              </select>
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Método de Pago *</label>
              <select
                value={cashPaymentMethod}
                onChange={(e) => setCashPaymentMethod(e.target.value as any)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-[#DC3545] focus:outline-none bg-white cursor-pointer"
              >
                <option value="Efectivo">Efectivo de Caja Chica</option>
                <option value="Transferencia">Transferencia Bancaria</option>
                <option value="Cheque">Emisión de Cheque diferido</option>
                <option value="Tarjeta">Tarjeta Corporativa / Débito</option>
              </select>
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Descripción del Movimiento *</label>
              <input
                type="text"
                required
                placeholder="Ej. Pago de boleta de luz de depósito julio 2026"
                value={cashDescription}
                onChange={(e) => setCashDescription(e.target.value)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-[#DC3545] focus:outline-none"
              />
            </div>

            <div className="md:col-span-2 flex justify-end gap-3 pt-4 border-t">
              <button
                type="button"
                onClick={() => setShowCashForm(false)}
                className="px-6 py-3 bg-neutral-100 hover:bg-neutral-200 border-2 border-neutral-300 text-neutral-800 font-bold rounded-xl cursor-pointer"
              >
                Cancelar
              </button>
              <button
                type="submit"
                className="px-8 py-3 bg-[#DC3545] hover:bg-[#bb2d3b] text-white font-extrabold text-lg rounded-xl cursor-pointer shadow"
              >
                Registrar Movimiento
              </button>
            </div>
          </form>
        </div>
      )}

      {/* --- FORMULARIO REALIZAR COBRO CUENTA CORRIENTE --- */}
      {showPaymentForm && activeTab === 'ACCOUNTS' && (
        <div className="bg-white border-4 border-amber-600 rounded-2xl p-6 space-y-6 shadow-lg animate-in slide-in-from-top-4 duration-300">
          <div className="flex items-center justify-between border-b pb-4">
            <h3 className="text-2xl font-black text-[#212529] flex items-center gap-2">
              <DollarSign className="w-6 h-6 text-amber-600" />
              <span>Registrar Cobro / Recibo Cuenta Corriente</span>
            </h3>
            <button onClick={() => setShowPaymentForm(false)} className="p-2 bg-neutral-100 text-neutral-700 hover:bg-neutral-200 rounded-lg cursor-pointer">
              <X className="w-6 h-6" />
            </button>
          </div>

          {paymentFormError && (
            <div className="p-4 bg-red-100 border-2 border-[#DC3545] text-[#DC3545] rounded-xl font-bold flex items-center gap-2">
              <AlertTriangle className="w-5 h-5" />
              <span>{paymentFormError}</span>
            </div>
          )}

          <form onSubmit={handleProcessPayment} className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Cliente que realiza el Pago *</label>
              <select
                value={paymentCustomerId}
                onChange={(e) => setPaymentCustomerId(e.target.value)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-amber-600 focus:outline-none bg-white cursor-pointer"
              >
                {customers.map(c => {
                  const acc = normalizedAccounts.find(a => a.customerId === c.id);
                  const bal = acc ? acc.balance : 0;
                  return (
                    <option key={c.id} value={c.id}>
                      {c.name} (Saldo: ${bal.toLocaleString('es-AR')})
                    </option>
                  );
                })}
              </select>
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Importe Cobrado ($) *</label>
              <input
                type="number"
                min="1"
                required
                value={paymentAmount || ''}
                onChange={(e) => setPaymentAmount(Number(e.target.value))}
                placeholder="Importe entregado"
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-amber-600 focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Vía de Cobro *</label>
              <select
                value={paymentMethod}
                onChange={(e) => setPaymentMethod(e.target.value as any)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-amber-600 focus:outline-none bg-white cursor-pointer"
              >
                <option value="Transferencia">Transferencia Bancaria / Mercado Pago</option>
                <option value="Efectivo">Dinero en Efectivo</option>
                <option value="Cheque">Cheque Físico de Terceros</option>
              </select>
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Detalle / Concepto *</label>
              <input
                type="text"
                required
                value={paymentDescription}
                onChange={(e) => setPaymentDescription(e.target.value)}
                placeholder="Detalle para el recibo"
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-amber-600 focus:outline-none"
              />
            </div>

            <div className="md:col-span-2 flex justify-end gap-3 pt-4 border-t">
              <button
                type="button"
                onClick={() => setShowPaymentForm(false)}
                className="px-6 py-3 bg-neutral-100 hover:bg-neutral-200 border-2 border-neutral-300 text-neutral-800 font-bold rounded-xl cursor-pointer"
              >
                Cancelar
              </button>
              <button
                type="submit"
                className="px-8 py-3 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-lg rounded-xl cursor-pointer shadow flex items-center gap-2"
              >
                <CheckCircle className="w-5 h-5" />
                <span>Registrar Cobranza e Imprimir Recibo</span>
              </button>
            </div>
          </form>
        </div>
      )}

      {/* --- DETALLE DE CUENTA CORRIENTE SELECCIONADA --- */}
      {selectedCustomerAccountDetail && activeTab === 'ACCOUNTS' && (
        <div className="bg-white border-2 border-amber-600 rounded-2xl p-6 space-y-6 shadow animate-in slide-in-from-top-4 duration-300">
          <div className="flex items-center justify-between border-b pb-4">
            <div>
              <span className="text-xs font-black text-amber-600 block uppercase">HISTORIAL DE CUENTA</span>
              <h3 className="text-2xl font-black text-[#212529]">
                {selectedCustomerAccountDetail.customerName}
              </h3>
            </div>
            <div className="flex gap-2">
              <button
                onClick={() => handleOpenPaymentForm(selectedCustomerAccountDetail)}
                className="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg cursor-pointer flex items-center gap-1.5 shadow"
              >
                <DollarSign className="w-4 h-4" />
                <span>Registrar Cobro</span>
              </button>
              <button 
                onClick={() => setSelectedCustomerAccountDetail(null)} 
                className="p-2 bg-neutral-100 text-neutral-700 hover:bg-neutral-200 rounded-lg cursor-pointer"
              >
                <X className="w-5 h-5" />
              </button>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
            <div className="bg-neutral-50 border p-4 rounded-xl text-center">
              <span className="text-xs font-bold text-neutral-500 block uppercase">Estado Actual</span>
              <span className={`text-2xl font-black block mt-1 ${selectedCustomerAccountDetail.balance > 0 ? 'text-[#DC3545]' : selectedCustomerAccountDetail.balance < 0 ? 'text-blue-700' : 'text-[#198754]'}`}>
                {selectedCustomerAccountDetail.balance > 0 ? 'Mantiene Deuda Activa' : selectedCustomerAccountDetail.balance < 0 ? 'Tiene Saldo a Favor' : 'Cuenta al Día / Cancelada'}
              </span>
            </div>

            <div className="bg-neutral-50 border p-4 rounded-xl text-center">
              <span className="text-xs font-bold text-neutral-500 block uppercase">Saldo Acumulado</span>
              <span className={`text-3xl font-black font-mono block mt-1 ${selectedCustomerAccountDetail.balance > 0 ? 'text-[#DC3545]' : 'text-[#198754]'}`}>
                ${selectedCustomerAccountDetail.balance.toLocaleString('es-AR')}
              </span>
            </div>

            <div className="bg-neutral-50 border p-4 rounded-xl text-center">
              <span className="text-xs font-bold text-neutral-500 block uppercase">Último Movimiento</span>
              <span className="text-sm font-black text-neutral-800 block mt-2">
                {selectedCustomerAccountDetail.lastActivity}
              </span>
            </div>
          </div>

          <div className="space-y-3">
            <h4 className="font-extrabold text-lg text-neutral-800">Historial de Cargos y Entregas (CC)</h4>
            
            <div className="border-2 rounded-xl overflow-hidden bg-white max-h-72 overflow-y-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-neutral-50 border-b">
                    <th className="p-3 font-bold text-xs text-[#212529] w-40">Fecha / Hora</th>
                    <th className="p-3 font-bold text-xs text-[#212529]">Descripción / Concepto</th>
                    <th className="p-3 font-bold text-xs text-[#212529] text-center w-28">Tipo</th>
                    <th className="p-3 font-bold text-xs text-[#212529] text-right w-36">Importe</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 font-semibold text-sm">
                  {customerAccountMovements.filter(m => m.customerId === selectedCustomerAccountDetail.customerId).length === 0 ? (
                    <tr>
                      <td colSpan={4} className="p-6 text-center text-neutral-500">
                        No hay movimientos registrados en la cuenta corriente de este cliente.
                      </td>
                    </tr>
                  ) : (
                    customerAccountMovements
                      .filter(m => m.customerId === selectedCustomerAccountDetail.customerId)
                      .map(movement => (
                        <tr key={movement.id} className="hover:bg-neutral-50">
                          <td className="p-3 font-mono text-neutral-600 text-xs">{movement.date}</td>
                          <td className="p-3 text-neutral-800">{movement.description}</td>
                          <td className="p-3 text-center">
                            <span className={`px-2.5 py-0.5 rounded text-xs font-bold ${
                              movement.type === 'Debito' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200'
                            }`}>
                              {movement.type === 'Debito' ? 'Deuda (+)' : 'Pago (-)'}
                            </span>
                          </td>
                          <td className={`p-3 text-right font-black font-mono ${movement.type === 'Debito' ? 'text-[#DC3545]' : 'text-[#198754]'}`}>
                            ${movement.amount.toLocaleString('es-AR')}
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

      {/* --- PREVIEW / SIMULACIÓN DE COMPROBANTE ELECTRÓNICO IMPRESO --- */}
      {selectedInvoicePreview && activeTab === 'INVOICES' && (
        <div className="bg-white border-2 border-neutral-800 rounded-2xl p-6 space-y-6 shadow-xl animate-in slide-in-from-top-4 duration-300">
          <div className="flex items-center justify-between border-b pb-4">
            <h3 className="text-xl font-black text-[#212529] flex items-center gap-1.5">
              <Printer className="w-5 h-5 text-neutral-700" />
              <span>Simulador de Impresión AFIP - Factura Autorizada</span>
            </h3>
            <div className="flex gap-2">
              <button
                onClick={() => {
                  alert("Enviando comando ESC/POS a impresora térmica en red local...");
                }}
                className="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 border text-neutral-800 font-bold rounded-lg flex items-center gap-1 cursor-pointer"
              >
                <Printer className="w-4 h-4" />
                <span>Imprimir Comprobante</span>
              </button>
              <button 
                onClick={() => setSelectedInvoicePreview(null)} 
                className="p-2 bg-neutral-100 text-neutral-700 hover:bg-neutral-200 rounded-lg cursor-pointer"
              >
                <X className="w-5 h-5" />
              </button>
            </div>
          </div>

          {/* DISEÑO FACTURA AFIP ORIGINAL */}
          <div className="border-4 border-neutral-900 p-6 bg-white font-sans text-neutral-900 text-left max-w-2xl mx-auto space-y-4">
            <div className="grid grid-cols-12 border-b-2 border-neutral-900 pb-4">
              <div className="col-span-5">
                <h4 className="text-2xl font-black tracking-tight">DISTRIBUIDORA PIGÜÉ</h4>
                <p className="text-xs font-bold text-neutral-600 mt-1">Distribuidora Comercial Pigüé</p>
                <p className="text-xs font-semibold text-neutral-500 mt-1">Av. Casey 450, Pigüé (B8170), Buenos Aires</p>
                <p className="text-xs font-semibold text-neutral-500">Tel: 2923-475678 | info@distribuidorapigue.com</p>
              </div>

              <div className="col-span-2 flex flex-col items-center justify-center border-l-2 border-r-2 border-neutral-900 bg-neutral-50">
                <span className="text-4xl font-black">
                  {selectedInvoicePreview.type.endsWith('A') ? 'A' : selectedInvoicePreview.type.endsWith('B') ? 'B' : 'C'}
                </span>
                <span className="text-[10px] font-black text-center mt-1">CÓD. 011</span>
              </div>

              <div className="col-span-5 pl-4 text-right">
                <h4 className="text-lg font-black">{selectedInvoicePreview.type.toUpperCase()}</h4>
                <p className="text-xs font-extrabold font-mono text-neutral-800 mt-1">Nº COMP: {selectedInvoicePreview.pos}-{selectedInvoicePreview.invoiceNumber}</p>
                <p className="text-xs font-bold text-neutral-600">FECHA: {selectedInvoicePreview.date}</p>
                <p className="text-xs font-semibold text-neutral-500 mt-2">CUIT: 30-71452389-4</p>
                <p className="text-xs font-semibold text-neutral-500">ING. BRUTOS: 30-71452389-4</p>
                <p className="text-xs font-semibold text-neutral-500">INICIO ACTIVIDAD: 01/01/2018</p>
              </div>
            </div>

            <div className="bg-neutral-50 p-3 border rounded-lg space-y-1 text-xs font-semibold">
              <p className="font-extrabold text-[#0D6EFD]">DATOS DEL COMPRADOR / COMERCIO:</p>
              <div className="grid grid-cols-2">
                <div>
                  <p>RAZÓN SOCIAL: <strong className="font-bold">{selectedInvoicePreview.customerName}</strong></p>
                  <p>CUIT: <strong className="font-mono font-bold">{selectedInvoicePreview.customerCuit}</strong></p>
                </div>
                <div className="text-right">
                  <p>CONDICION IVA: <strong className="font-bold">{selectedInvoicePreview.type === 'Factura A' ? 'Resp. Inscripto' : 'Consumidor Final'}</strong></p>
                  <p>ASOCIADO A PEDIDO: <strong className="font-mono font-bold">{selectedInvoicePreview.orderId}</strong></p>
                </div>
              </div>
            </div>

            <div className="bg-neutral-100 p-4 border text-center font-bold text-sm rounded">
              <p>Simulador AFIP: Venta mayorista de mercaderías alimenticias y bebidas embotelladas según pedido correspondiente.</p>
            </div>

            <div className="border-t-2 border-neutral-950 pt-4 space-y-1 text-right text-xs font-bold">
              <p className="flex justify-between">
                <span>SUBTOTAL (NETO GRAVADO):</span>
                <span className="font-mono font-extrabold">${selectedInvoicePreview.netAmount.toLocaleString('es-AR')}</span>
              </p>
              <p className="flex justify-between text-neutral-700">
                <span>I.V.A. INSCRIPTO (21% / 10.5%):</span>
                <span className="font-mono font-extrabold">${selectedInvoicePreview.ivaAmount.toLocaleString('es-AR')}</span>
              </p>
              <p className="flex justify-between text-lg font-black text-neutral-900 border-t pt-2 mt-2">
                <span>TOTAL FACTURADO:</span>
                <span className="font-mono">${selectedInvoicePreview.total.toLocaleString('es-AR')} ARS</span>
              </p>
            </div>

            {/* CAE AFIP BARCODE FOOTER */}
            <div className="border-t border-dashed pt-4 flex flex-col md:flex-row items-center justify-between gap-4 text-xs">
              <div className="flex items-center gap-2">
                <div className="p-1 bg-white border">
                  <div className="h-8 w-44 bg-neutral-900 flex items-center justify-center text-white text-[10px] tracking-[6px] font-mono select-none">
                    ||||| | | |||| | ||
                  </div>
                </div>
                <span className="text-[10px] font-semibold text-neutral-500 font-mono">Simulado Barcode ARCA</span>
              </div>

              <div className="text-right font-semibold text-[11px] space-y-0.5">
                <p>CAE Nº: <strong className="font-mono font-extrabold">{selectedInvoicePreview.cae}</strong></p>
                <p>VTO CAE: <strong className="font-mono font-extrabold">{selectedInvoicePreview.caeDueDate}</strong></p>
                <p className="text-[9px] text-[#198754] font-bold">✓ AUTORIZADO POR AFIP DIGITAL</p>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* --- RENDER PESTAÑA: FACTURAS AFIP --- */}
      {activeTab === 'INVOICES' && !showInvoiceForm && (
        <div className="space-y-6">
          {/* BUSCADOR DE FACTURAS */}
          <div className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-5">
            <div className="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
              <div className="md:col-span-9">
                <label className="block text-base font-bold text-neutral-700 mb-1" htmlFor="inv-search">Buscar por Comercio o Nro de Factura:</label>
                <input
                  id="inv-search"
                  type="text"
                  placeholder="Escriba el nombre del cliente, ID del comprobante o número..."
                  value={invoiceSearch}
                  onChange={(e) => setInvoiceSearch(e.target.value)}
                  className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-base font-bold text-[#212529] bg-white focus:outline-none"
                />
              </div>

              <div className="md:col-span-3">
                <button
                  onClick={handleOpenAddInvoice}
                  className="w-full py-3 bg-[#0D6EFD] hover:bg-[#0b5ed7] text-white font-black text-lg rounded-xl flex items-center justify-center gap-2 cursor-pointer shadow"
                >
                  <PlusCircle className="w-5 h-5" />
                  <span>Emitir Factura</span>
                </button>
              </div>
            </div>
          </div>

          {/* LISTA DE COMPROBANTES */}
          <div className="bg-white border-2 border-[#DEE2E6] rounded-2xl p-6 space-y-4">
            <h3 className="text-2xl font-black text-[#212529] border-b pb-3">Historial de Facturación Electrónica</h3>

            <div className="border rounded-xl overflow-hidden bg-white">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-neutral-50 border-b">
                    <th className="p-3.5 font-bold text-sm text-[#212529]">Comprobante</th>
                    <th className="p-3.5 font-bold text-sm text-[#212529]">Fecha</th>
                    <th className="p-3.5 font-bold text-sm text-[#212529]">Cliente / Comercio</th>
                    <th className="p-3.5 font-bold text-sm text-[#212529] font-mono text-center">CAE / AFIP</th>
                    <th className="p-3.5 font-bold text-sm text-[#212529] text-right">Monto Total</th>
                    <th className="p-3.5 font-bold text-sm text-[#212529] text-center">Acción</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 font-semibold text-sm">
                  {filteredInvoices.length === 0 ? (
                    <tr>
                      <td colSpan={6} className="p-8 text-center text-neutral-500">
                        No se encontraron facturas emitidas con los criterios de búsqueda.
                      </td>
                    </tr>
                  ) : (
                    filteredInvoices.map(invoice => (
                      <tr key={invoice.id} className="hover:bg-neutral-50">
                        <td className="p-3">
                          <span className="text-[10px] font-black uppercase text-neutral-400 block">{invoice.type}</span>
                          <span className="font-mono font-bold text-[#212529]">{invoice.pos}-{invoice.invoiceNumber}</span>
                        </td>
                        <td className="p-3 text-neutral-600 font-mono text-xs">{invoice.date}</td>
                        <td className="p-3 font-bold text-neutral-900">{invoice.customerName}</td>
                        <td className="p-3 font-mono text-xs text-neutral-600 text-center">{invoice.cae}</td>
                        <td className="p-3 text-right font-black font-mono text-[#198754] text-base">
                          ${invoice.total.toLocaleString('es-AR')}
                        </td>
                        <td className="p-3 text-center">
                          <button
                            onClick={() => setSelectedInvoicePreview(invoice)}
                            className="px-3 py-1.5 bg-neutral-100 hover:bg-neutral-200 border rounded-lg text-xs font-bold text-[#212529] flex items-center gap-1 mx-auto cursor-pointer"
                          >
                            <Printer className="w-3.5 h-3.5" />
                            <span>Ver PDF / Imprimir</span>
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

      {/* --- RENDER PESTAÑA: CAJA DIARIA --- */}
      {activeTab === 'CASH' && !showCashForm && (
        <div className="space-y-6">
          {/* CONTROL Y FILTRO DE CAJA */}
          <div className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-5">
            <div className="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
              <div className="md:col-span-9">
                <label className="block text-base font-bold text-neutral-700 mb-1" htmlFor="cash-search">Buscar Movimiento de Caja:</label>
                <input
                  id="cash-search"
                  type="text"
                  placeholder="Escriba la descripción del gasto, cobro, categoría, usuario..."
                  value={cashSearch}
                  onChange={(e) => setCashSearch(e.target.value)}
                  className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-base font-bold text-[#212529] bg-white focus:outline-none"
                />
              </div>

              <div className="md:col-span-3">
                <button
                  onClick={handleOpenAddCash}
                  className="w-full py-3 bg-[#DC3545] hover:bg-[#bb2d3b] text-white font-black text-lg rounded-xl flex items-center justify-center gap-2 cursor-pointer shadow"
                >
                  <TrendingDown className="w-5 h-5" />
                  <span>Registrar Egreso</span>
                </button>
              </div>
            </div>
          </div>

          {/* LISTA DE MOVIMIENTOS */}
          <div className="bg-white border-2 border-[#DEE2E6] rounded-2xl p-6 space-y-4">
            <h3 className="text-2xl font-black text-[#212529] border-b pb-3">Registro Diario de Fondos (Caja de Seguridad)</h3>

            <div className="border rounded-xl overflow-hidden bg-white">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-neutral-50 border-b">
                    <th className="p-3.5 font-bold text-sm text-[#212529] w-44">Fecha / Hora</th>
                    <th className="p-3.5 font-bold text-sm text-[#212529] text-center w-28">Tipo</th>
                    <th className="p-3.5 font-bold text-sm text-[#212529]">Categoría</th>
                    <th className="p-3.5 font-bold text-sm text-[#212529]">Descripción / Concepto</th>
                    <th className="p-3.5 font-bold text-sm text-[#212529]">Vía / Operador</th>
                    <th className="p-3.5 font-bold text-sm text-[#212529] text-right">Importe</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 font-semibold text-sm">
                  {filteredCashMovements.length === 0 ? (
                    <tr>
                      <td colSpan={6} className="p-8 text-center text-neutral-500">
                        No hay movimientos registrados de caja diaria.
                      </td>
                    </tr>
                  ) : (
                    filteredCashMovements.map(movement => (
                      <tr key={movement.id} className="hover:bg-neutral-50">
                        <td className="p-3 font-mono text-neutral-600 text-xs">{movement.date}</td>
                        <td className="p-3 text-center">
                          <span className={`px-2.5 py-1 rounded-full text-xs font-black flex items-center justify-center gap-1 w-24 mx-auto ${
                            movement.type === 'Ingreso' 
                              ? 'bg-[#198754]/10 text-[#198754] border border-[#198754]/20' 
                              : 'bg-red-50 text-red-700 border border-red-200'
                          }`}>
                            {movement.type === 'Ingreso' ? (
                              <>
                                <ArrowUpRight className="w-3.5 h-3.5" />
                                <span>Ingreso</span>
                              </>
                            ) : (
                              <>
                                <ArrowDownRight className="w-3.5 h-3.5" />
                                <span>Egreso</span>
                              </>
                            )}
                          </span>
                        </td>
                        <td className="p-3">
                          <span className="bg-neutral-100 border text-neutral-700 px-2.5 py-1 rounded text-xs">
                            {movement.category}
                          </span>
                        </td>
                        <td className="p-3 text-neutral-800">{movement.description}</td>
                        <td className="p-3 text-neutral-500 text-xs">
                          <p className="font-extrabold text-neutral-700">{movement.paymentMethod}</p>
                          <p>Op: {movement.user}</p>
                        </td>
                        <td className={`p-3 text-right font-black font-mono text-base ${movement.type === 'Ingreso' ? 'text-[#198754]' : 'text-[#DC3545]'}`}>
                          {movement.type === 'Ingreso' ? '+' : '-'}${movement.amount.toLocaleString('es-AR')}
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

      {/* --- RENDER PESTAÑA: CUENTAS CORRIENTES --- */}
      {activeTab === 'ACCOUNTS' && (
        <div className="space-y-6">
          {/* BUSCADOR DE CUENTAS CORRIENTES */}
          <div className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-5">
            <div className="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
              <div className="md:col-span-9">
                <label className="block text-base font-bold text-neutral-700 mb-1" htmlFor="ac-search">Buscar Cliente:</label>
                <input
                  id="ac-search"
                  type="text"
                  placeholder="Escriba el nombre del comercio..."
                  value={accountSearch}
                  onChange={(e) => setAccountSearch(e.target.value)}
                  className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-base font-bold text-[#212529] bg-white focus:outline-none"
                />
              </div>

              <div className="md:col-span-3">
                <button
                  onClick={() => {
                    setPaymentFormError('');
                    setPaymentCustomerId(customers[0]?.id || '');
                    setPaymentAmount(0);
                    setPaymentMethod('Transferencia');
                    setPaymentDescription('Cobranza / Entrega a cuenta corriente');
                    setShowPaymentForm(true);
                  }}
                  className="w-full py-3 bg-amber-600 hover:bg-amber-700 text-white font-black text-lg rounded-xl flex items-center justify-center gap-2 cursor-pointer shadow"
                >
                  <DollarSign className="w-5 h-5" />
                  <span>Cobrar a Cuenta</span>
                </button>
              </div>
            </div>
          </div>

          {/* LISTADO DE CUENTAS */}
          <div className="bg-white border-2 border-[#DEE2E6] rounded-2xl p-6 space-y-4">
            <h3 className="text-2xl font-black text-[#212529] border-b pb-3">Cuentas Corrientes y Estado de Deudas</h3>

            <div className="border rounded-xl overflow-hidden bg-white">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-neutral-50 border-b">
                    <th className="p-3.5 font-bold text-sm text-[#212529]">Comercio / Razón Social</th>
                    <th className="p-3.5 font-bold text-sm text-[#212529]">Última Actividad</th>
                    <th className="p-3.5 font-bold text-sm text-[#212529] text-center">Estado Financiero</th>
                    <th className="p-3.5 font-bold text-sm text-[#212529] text-right">Saldo Neto</th>
                    <th className="p-3.5 font-bold text-sm text-[#212529] text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 font-semibold text-sm">
                  {filteredAccounts.map(account => {
                    const isDebtor = account.balance > 0;
                    const hasCredit = account.balance < 0;

                    return (
                      <tr key={account.customerId} className="hover:bg-neutral-50">
                        <td className="p-3 font-bold text-neutral-900">{account.customerName}</td>
                        <td className="p-3 font-mono text-xs text-neutral-500">{account.lastActivity}</td>
                        <td className="p-3 text-center">
                          <span className={`px-2.5 py-1 rounded-full text-xs font-bold inline-block ${
                            isDebtor ? 'bg-red-50 text-[#DC3545] border border-red-200' :
                            hasCredit ? 'bg-blue-50 text-blue-800 border border-blue-200' :
                            'bg-[#198754]/10 text-[#198754] border border-[#198754]/20'
                          }`}>
                            {isDebtor ? 'Adeuda Pago' : hasCredit ? 'Saldo a Favor' : 'Cuenta al Día'}
                          </span>
                        </td>
                        <td className={`p-3 text-right font-black font-mono text-base ${isDebtor ? 'text-[#DC3545]' : 'text-[#198754]'}`}>
                          ${account.balance.toLocaleString('es-AR')}
                        </td>
                        <td className="p-3">
                          <div className="flex gap-2 justify-center">
                            <button
                              onClick={() => setSelectedCustomerAccountDetail(account)}
                              className="px-3 py-1.5 bg-neutral-100 hover:bg-neutral-200 border rounded-lg text-xs font-bold text-neutral-800 cursor-pointer"
                            >
                              Ver Historial
                            </button>
                            <button
                              onClick={() => handleOpenPaymentForm(account)}
                              className="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white border border-amber-700 rounded-lg text-xs font-bold cursor-pointer"
                            >
                              Registrar Cobro
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

    </div>
  );
}
