import React, { useState, useEffect } from 'react';
import { Customer } from '../../types';
import { 
  Users, 
  Search, 
  Coins, 
  PlusCircle, 
  RotateCcw, 
  TrendingUp, 
  TrendingDown, 
  Printer, 
  CheckSquare, 
  AlertTriangle, 
  X,
  CreditCard
} from 'lucide-react';

interface LocalCustomerAccount {
  id: string;
  customerId: string;
  customerName: string;
  balance: number;
  limit: number;
  status: string;
  lastActivity?: string;
}

interface LocalCustomerAccountMovement {
  id: string;
  accountId: string;
  customerId?: string;
  type: 'Debito' | 'Credito';
  amount: number;
  date: string;
  concept: string;
  referenceId?: string;
}

interface SalesCurrentAccountProps {
  customers: Customer[];
}

export function SalesCurrentAccount({
  customers
}: SalesCurrentAccountProps) {
  const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(null);
  const [accounts, setAccounts] = useState<LocalCustomerAccount[]>([]);
  const [movements, setMovements] = useState<LocalCustomerAccountMovement[]>([]);
  
  // Búsqueda
  const [searchQuery, setSearchQuery] = useState('');
  
  // Registrar pago modal
  const [showPayModal, setShowPayModal] = useState(false);
  const [payAmount, setPayAmount] = useState(0);
  const [payMethod, setPayMethod] = useState<'Efectivo' | 'Transferencia'>('Efectivo');
  const [payConcept, setPayConcept] = useState('Pago parcial de saldo');

  // Alertas
  const [successMsg, setSuccessMsg] = useState('');

  // Cargar cuentas y movimientos desde localStorage
  useEffect(() => {
    const savedAccounts = localStorage.getItem('erp_distribuidora_customer_accounts');
    const savedMovements = localStorage.getItem('erp_distribuidora_customer_account_movements');
    
    // Si no existen, inicializar vacíos o generar a partir de clientes
    let loadedAccounts: LocalCustomerAccount[] = [];
    if (savedAccounts) {
      try { loadedAccounts = JSON.parse(savedAccounts); } catch (e) { console.error(e); }
    } else {
      // Inicializar con saldo cero por defecto para cada cliente
      loadedAccounts = customers.map(c => ({
        id: `ACC-${c.id}`,
        customerId: c.id,
        customerName: c.name,
        balance: 0,
        limit: 100000,
        status: 'Activo'
      }));
      localStorage.setItem('erp_distribuidora_customer_accounts', JSON.stringify(loadedAccounts));
    }
    setAccounts(loadedAccounts);

    if (savedMovements) {
      try { setMovements(JSON.parse(savedMovements)); } catch (e) { console.error(e); }
    }
  }, [customers]);

  // Guardar datos
  const saveState = (updatedAccounts: LocalCustomerAccount[], updatedMovements: LocalCustomerAccountMovement[]) => {
    setAccounts(updatedAccounts);
    setMovements(updatedMovements);
    localStorage.setItem('erp_distribuidora_customer_accounts', JSON.stringify(updatedAccounts));
    localStorage.setItem('erp_distribuidora_customer_account_movements', JSON.stringify(updatedMovements));
    
    // Despachar evento para sincronizar con FinancesView u otras vistas
    window.dispatchEvent(new Event('storage'));
  };

  // Obtener cuenta del cliente seleccionado
  const activeAccount = selectedCustomer 
    ? accounts.find(a => a.customerId === selectedCustomer.id) 
    : null;

  // Obtener movimientos del cliente seleccionado
  const activeMovements = selectedCustomer
    ? movements.filter(m => m.accountId === `ACC-${selectedCustomer.id}`).sort((a,b) => b.date.localeCompare(a.date))
    : [];

  // Filtrar clientes
  const filteredCustomers = customers.filter(c => {
    const matchesQuery = c.name.toLowerCase().includes(searchQuery.toLowerCase()) || c.cuit.includes(searchQuery);
    return matchesQuery;
  });

  // Registrar un Pago (Abono / Crédito)
  const handleRegisterPayment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedCustomer || !activeAccount) return;
    if (payAmount <= 0) {
      alert('El monto debe ser un valor positivo mayor que cero.');
      return;
    }

    const accountId = activeAccount.id;

    // 1. Crear movimiento de crédito (disminuye la deuda)
    const newMovement: LocalCustomerAccountMovement = {
      id: `MOV-${Date.now().toString().slice(-4)}`,
      accountId: accountId,
      type: 'Credito', // Credito disminuye deuda
      amount: payAmount,
      date: new Date().toISOString().replace('T', ' ').slice(0, 16),
      concept: payConcept.trim() || 'Pago a cuenta corriente',
      referenceId: `REC-${Date.now().toString().slice(-4)}`
    };

    // 2. Actualizar saldo
    const updatedAccounts = accounts.map(acc => {
      if (acc.id === accountId) {
        return {
          ...acc,
          balance: Number((acc.balance - payAmount).toFixed(2)) // restamos saldo de deuda
        };
      }
      return acc;
    });

    const updatedMovements = [newMovement, ...movements];

    // 3. Registrar en Movimientos de Caja General (Cash Movement)
    const cashMovsSaved = localStorage.getItem('erp_distribuidora_cash_movements');
    let cashMovs = [];
    if (cashMovsSaved) {
      try { cashMovs = JSON.parse(cashMovsSaved); } catch(e) { console.error(e); }
    }
    const newCashMov = {
      id: `CSH-${Date.now().toString().slice(-4)}`,
      type: 'Ingreso',
      category: 'Cobranza',
      amount: payAmount,
      date: new Date().toISOString().replace('T', ' ').slice(0, 16),
      concept: `Cobranza CC - ${selectedCustomer.name} (${payConcept})`,
      paymentMethod: payMethod,
      referenceId: newMovement.id
    };
    localStorage.setItem('erp_distribuidora_cash_movements', JSON.stringify([newCashMov, ...cashMovs]));

    saveState(updatedAccounts, updatedMovements);

    setShowPayModal(false);
    setPayAmount(0);
    setPayConcept('Pago parcial de saldo');

    setSuccessMsg(`¡Pago de $${payAmount.toLocaleString('es-AR')} registrado con éxito!`);
    setTimeout(() => setSuccessMsg(''), 3000);
  };

  // Liquidación / Cancelación rápida de deuda total
  const handleCancelTotalDebt = () => {
    if (!selectedCustomer || !activeAccount) return;
    const debt = activeAccount.balance;
    if (debt <= 0) {
      alert('El cliente no registra saldos deudores pendientes.');
      return;
    }

    if (window.confirm(`¿Confirmar cancelación total de deuda por $${debt.toLocaleString('es-AR')}?`)) {
      const accountId = activeAccount.id;

      const newMovement: LocalCustomerAccountMovement = {
        id: `MOV-${Date.now().toString().slice(-4)}`,
        accountId: accountId,
        type: 'Credito',
        amount: debt,
        date: new Date().toISOString().replace('T', ' ').slice(0, 16),
        concept: 'Cancelación total de saldo pendiente',
        referenceId: `REC-${Date.now().toString().slice(-4)}`
      };

      const updatedAccounts = accounts.map(acc => {
        if (acc.id === accountId) {
          return { ...acc, balance: 0 };
        }
        return acc;
      });

      const updatedMovements = [newMovement, ...movements];

      // Sincronizar caja
      const cashMovsSaved = localStorage.getItem('erp_distribuidora_cash_movements');
      let cashMovs = [];
      if (cashMovsSaved) {
        try { cashMovs = JSON.parse(cashMovsSaved); } catch(e) { console.error(e); }
      }
      const newCashMov = {
        id: `CSH-${Date.now().toString().slice(-4)}`,
        type: 'Ingreso',
        category: 'Cobranza',
        amount: debt,
        date: new Date().toISOString().replace('T', ' ').slice(0, 16),
        concept: `Cobranza CC - Cancelación total ${selectedCustomer.name}`,
        paymentMethod: 'Efectivo',
        referenceId: newMovement.id
      };
      localStorage.setItem('erp_distribuidora_cash_movements', JSON.stringify([newCashMov, ...cashMovs]));

      saveState(updatedAccounts, updatedMovements);

      setSuccessMsg('¡Deuda total cancelada con éxito!');
      setTimeout(() => setSuccessMsg(''), 3000);
    }
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-200">
      
      {/* LATERAL IZQUIERDO: SELECCIÓN DE CLIENTES */}
      <div className="lg:col-span-4 space-y-4">
        <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
          <div className="pb-2 border-b border-slate-100">
            <h3 className="text-xs font-black text-slate-400 uppercase tracking-wider">Cartera de Clientes CC</h3>
          </div>

          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 w-4 h-4" />
            <input
              type="text"
              placeholder="Buscar por Nombre o CUIT..."
              className="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs font-bold text-slate-800"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
            />
          </div>

          <div className="space-y-2 max-h-[500px] overflow-y-auto pr-1">
            {filteredCustomers.map(c => {
              const cAcc = accounts.find(a => a.customerId === c.id);
              const isDeudor = cAcc && cAcc.balance > 0;
              
              return (
                <div
                  key={c.id}
                  onClick={() => setSelectedCustomer(c)}
                  className={`p-3.5 border rounded-xl cursor-pointer transition-all flex justify-between items-center ${selectedCustomer?.id === c.id ? 'border-[#0D6EFD] bg-blue-50/20' : 'border-slate-150 hover:bg-slate-50'}`}
                >
                  <div>
                    <h4 className="font-extrabold text-slate-800 text-xs leading-tight">{c.name}</h4>
                    <p className="text-[10px] text-slate-400 mt-1">CUIT: {c.cuit}</p>
                  </div>
                  <div className="text-right shrink-0">
                    <span className={`font-black text-xs block ${isDeudor ? 'text-red-600' : 'text-emerald-600'}`}>
                      ${cAcc ? cAcc.balance.toLocaleString('es-AR') : '0,00'}
                    </span>
                    <span className="text-[9px] font-bold text-slate-400">Saldo CC</span>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </div>

      {/* DETALLE LEDGER CENTRAL */}
      <div className="lg:col-span-8 space-y-4">
        
        {successMsg && (
          <div className="p-3 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 text-xs font-bold rounded-r-xl flex items-center gap-2">
            <CheckSquare className="w-4 h-4" />
            <span>{successMsg}</span>
          </div>
        )}

        {selectedCustomer && activeAccount ? (
          <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
            
            {/* FICHA TÉCNICA E IMPORTES GENERALES */}
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 pb-4 border-b border-slate-100">
              <div>
                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">RESUMEN DE CUENTA CORRIENTE</span>
                <h3 className="text-lg font-black text-slate-800 mt-0.5">{selectedCustomer.name}</h3>
                <p className="text-xs text-slate-500 mt-0.5">CUIT: {selectedCustomer.cuit} | {selectedCustomer.address}</p>
              </div>

              <div className="flex gap-2 w-full md:w-auto">
                <button
                  onClick={() => setShowPayModal(true)}
                  className="flex-1 md:flex-initial bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black px-4 py-2.5 rounded-xl flex items-center justify-center gap-1.5 transition-all shadow-md cursor-pointer"
                >
                  <PlusCircle className="w-4 h-4" />
                  <span>REGISTRAR PAGO</span>
                </button>
                <button
                  onClick={handleCancelTotalDebt}
                  className="flex-1 md:flex-initial bg-slate-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl flex items-center justify-center gap-1.5 transition-all cursor-pointer"
                >
                  <CheckSquare className="w-4 h-4" />
                  <span>LIQUIDAR TOTAL</span>
                </button>
              </div>
            </div>

            {/* CARD DE BALANCE */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-150 text-center font-bold">
              <div>
                <span className="text-[10px] text-slate-400 block mb-1">SALDO DEUDOR ACTUAL</span>
                <span className={`text-xl font-black block ${activeAccount.balance > 0 ? 'text-red-600' : 'text-emerald-600'}`}>
                  ${activeAccount.balance.toLocaleString('es-AR')}
                </span>
              </div>
              <div>
                <span className="text-[10px] text-slate-400 block mb-1">LÍMITE DE CRÉDITO</span>
                <span className="text-xl font-black text-slate-800 block">
                  ${activeAccount.limit?.toLocaleString('es-AR') || '$100.000'}
                </span>
              </div>
              <div>
                <span className="text-[10px] text-slate-400 block mb-1">ESTADO CUENTA</span>
                <span className="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-full font-black inline-block mt-0.5">
                  {activeAccount.status || 'Activo'}
                </span>
              </div>
            </div>

            {/* LISTADO DE MOVIMIENTOS HISTÓRICOS */}
            <div className="space-y-4">
              <h4 className="text-xs font-black text-slate-400 uppercase tracking-wider">Historial Cronológico de Movimientos</h4>
              
              <div className="border border-slate-150 rounded-xl overflow-hidden bg-white">
                <table className="w-full text-left text-xs font-bold border-collapse">
                  <thead>
                    <tr className="bg-slate-50 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-100">
                      <th className="p-3">Fecha</th>
                      <th className="p-3">Concepto</th>
                      <th className="p-3 text-right">Cargo (Débito)</th>
                      <th className="p-3 text-right">Abono (Crédito)</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {activeMovements.length === 0 ? (
                      <tr>
                        <td colSpan={4} className="p-8 text-center text-slate-400 font-bold uppercase">
                          No se registran movimientos en la cuenta.
                        </td>
                      </tr>
                    ) : (
                      activeMovements.map(mov => (
                        <tr key={mov.id} className="hover:bg-slate-50/50">
                          <td className="p-3 text-slate-500">{mov.date}</td>
                          <td className="p-3">
                            <span className="text-slate-800 font-extrabold">{mov.concept}</span>
                            <span className="text-[10px] text-slate-400 block font-normal">Ref: {mov.referenceId || mov.id}</span>
                          </td>
                          <td className="p-3 text-right">
                            {mov.type === 'Debito' ? (
                              <span className="text-red-600 font-black flex items-center justify-end gap-0.5">
                                <TrendingUp className="w-3 h-3" />
                                ${mov.amount.toLocaleString('es-AR')}
                              </span>
                            ) : '-'}
                          </td>
                          <td className="p-3 text-right">
                            {mov.type === 'Credito' ? (
                              <span className="text-emerald-600 font-black flex items-center justify-end gap-0.5">
                                <TrendingDown className="w-3 h-3" />
                                -${mov.amount.toLocaleString('es-AR')}
                              </span>
                            ) : '-'}
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        ) : (
          <div className="bg-white border border-slate-200 rounded-2xl p-12 shadow-sm text-center font-bold text-slate-400 uppercase text-xs flex flex-col items-center justify-center gap-3">
            <Coins className="w-12 h-12 text-slate-300" />
            <span>Seleccione un cliente comercial en el listado izquierdo para auditar su estado de cuenta corriente.</span>
          </div>
        )}

      </div>

      {/* =========================================================================
          MODAL: REGISTRAR ABONO / PAGO
          ========================================================================= */}
      {showPayModal && selectedCustomer && activeAccount && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl border border-slate-200 w-full max-w-sm overflow-hidden shadow-2xl flex flex-col">
            <header className="bg-slate-50 p-4 border-b border-slate-100 flex justify-between items-center">
              <h3 className="text-sm font-black text-slate-800 flex items-center gap-1.5">
                <CreditCard className="w-4 h-4 text-emerald-600" />
                <span>Registrar Pago de Cliente</span>
              </h3>
              <button onClick={() => setShowPayModal(false)}>
                <X className="w-5 h-5 text-slate-400" />
              </button>
            </header>

            <form onSubmit={handleRegisterPayment} className="p-5 space-y-4">
              <div className="space-y-1">
                <label className="text-xs font-bold text-slate-500 block">Cliente</label>
                <input
                  type="text"
                  disabled
                  className="w-full bg-slate-100 border border-slate-200 rounded-lg p-2 text-xs font-extrabold text-slate-600"
                  value={selectedCustomer.name}
                />
              </div>

              <div className="space-y-1">
                <label className="text-xs font-bold text-slate-500 block">Deuda Pendiente</label>
                <span className="text-sm font-black text-red-600 block bg-red-50/50 p-2 rounded-lg border border-red-100">
                  ${activeAccount.balance.toLocaleString('es-AR')}
                </span>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1">
                  <label className="text-xs font-bold text-slate-500 block">Importe a Abonar ($) *</label>
                  <input
                    type="number"
                    min="1"
                    placeholder="Ej: 5000"
                    required
                    className="w-full bg-slate-50 border border-slate-250 rounded-lg p-2.5 text-xs font-extrabold text-slate-800"
                    value={payAmount || ''}
                    onChange={(e) => setPayAmount(Math.max(0, parseFloat(e.target.value) || 0))}
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs font-bold text-slate-500 block">Medio Cobro</label>
                  <select
                    className="w-full bg-slate-50 border border-slate-250 rounded-lg p-2.5 text-xs font-semibold"
                    value={payMethod}
                    onChange={(e) => setPayMethod(e.target.value as any)}
                  >
                    <option value="Efectivo">Efectivo</option>
                    <option value="Transferencia">Transferencia Bancaria</option>
                  </select>
                </div>
              </div>

              <div className="space-y-1">
                <label className="text-xs font-bold text-slate-500 block">Concepto / Glosa</label>
                <input
                  type="text"
                  placeholder="Ej: Pago parcial / Recibo N° 1241"
                  className="w-full bg-slate-50 border border-slate-250 rounded-lg p-2 text-xs font-semibold"
                  value={payConcept}
                  onChange={(e) => setPayConcept(e.target.value)}
                />
              </div>

              <button
                type="submit"
                className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-2.5 rounded-xl text-xs uppercase transition-colors cursor-pointer"
              >
                PROCESAR COBRANZA CC
              </button>
            </form>
          </div>
        </div>
      )}

    </div>
  );
}
