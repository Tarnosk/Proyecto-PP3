import React, { useState } from 'react';
import { Product, Promotion } from '../../types';
import { 
  Tag, 
  Plus, 
  Trash2, 
  AlertTriangle, 
  CheckCircle2, 
  X, 
  Layers, 
  Check, 
  HelpCircle 
} from 'lucide-react';

interface SalesPromotionsProps {
  products: Product[];
  promotions: Promotion[];
  onUpdatePromotions: (promotions: Promotion[]) => void;
}

export function SalesPromotions({
  products,
  promotions,
  onUpdatePromotions
}: SalesPromotionsProps) {
  const [selectedPromo, setSelectedPromo] = useState<Promotion | null>(promotions[0] || null);
  
  // Nuevo Combo Form States
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [promoName, setPromoName] = useState('');
  const [promoDesc, setPromoDesc] = useState('');
  const [discountPct, setDiscountPct] = useState(10);
  const [discountAmt, setDiscountAmt] = useState(0);
  const [discountType, setDiscountType] = useState<'PERCENT' | 'FIXED'>('PERCENT');

  // Multi-product selection
  const [promoItems, setPromoItems] = useState<{ productId: string; quantity: number }[]>([]);
  const [selectedProdToAdd, setSelectedProdToAdd] = useState('');
  const [prodToAddQty, setProdToAddQty] = useState(1);

  // Alertas
  const [errorMsg, setErrorMsg] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  // Agregar producto al borrador del combo
  const handleAddProductToCombo = () => {
    if (!selectedProdToAdd) return;
    const prod = products.find(p => p.id === selectedProdToAdd);
    if (!prod) return;

    const existing = promoItems.find(it => it.productId === selectedProdToAdd);
    if (existing) {
      setPromoItems(promoItems.map(it => 
        it.productId === selectedProdToAdd 
          ? { ...it, quantity: it.quantity + prodToAddQty }
          : it
      ));
    } else {
      setPromoItems([...promoItems, { productId: selectedProdToAdd, quantity: prodToAddQty }]);
    }

    setSelectedProdToAdd('');
    setProdToAddQty(1);
  };

  // Quitar producto del borrador del combo
  const handleRemoveProductFromCombo = (productId: string) => {
    setPromoItems(promoItems.filter(it => it.productId !== productId));
  };

  // Guardar nueva promoción / combo
  const handleSavePromo = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg('');
    setSuccessMsg('');

    if (!promoName.trim() || !promoDesc.trim()) {
      setErrorMsg('Por favor, complete el nombre y la descripción de la promoción.');
      return;
    }

    if (promoItems.length === 0) {
      setErrorMsg('Debe agregar al menos un producto para conformar el combo.');
      return;
    }

    const itemsWithNames = promoItems.map(item => {
      const prod = products.find(p => p.id === item.productId);
      return {
        productId: item.productId,
        productName: prod ? prod.name : 'Producto desconocido',
        quantity: item.quantity
      };
    });

    const newPromo: Promotion = {
      id: Date.now().toString(),
      name: promoName.trim(),
      description: promoDesc.trim(),
      discountPercentage: discountType === 'PERCENT' ? discountPct : 0,
      discountAmount: discountType === 'FIXED' ? discountAmt : 0,
      items: itemsWithNames,
      requiredProductId: itemsWithNames[0]?.productId, // backward compatibility
      requiredQuantity: itemsWithNames[0]?.quantity // backward compatibility
    };

    const updated = [...promotions, newPromo];
    onUpdatePromotions(updated);
    
    // Auto-seleccionar la nueva promo
    setSelectedPromo(newPromo);

    // Reset
    setPromoName('');
    setPromoDesc('');
    setDiscountPct(10);
    setDiscountAmt(0);
    setPromoItems([]);
    setShowCreateForm(false);
    
    setSuccessMsg('¡Nueva promoción creada exitosamente!');
    setTimeout(() => setSuccessMsg(''), 3000);
  };

  // Eliminar Promoción
  const handleDeletePromo = (id: string) => {
    if (window.confirm('¿Está seguro de que desea eliminar permanentemente esta promoción/combo?')) {
      const updated = promotions.filter(p => p.id !== id);
      onUpdatePromotions(updated);
      setSelectedPromo(updated[0] || null);
      setSuccessMsg('Promoción eliminada correctamente.');
      setTimeout(() => setSuccessMsg(''), 2000);
    }
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      
      {/* HEADER DE PROMOCIONES */}
      <div className="flex justify-between items-center bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
        <div>
          <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block">CONFIGURACIÓN COMERCIAL</span>
          <h2 className="text-xl font-bold text-slate-800 flex items-center gap-2">
            <Tag className="w-5 h-5 text-amber-500" />
            <span>Gestión de Promociones y Combos Especiales</span>
          </h2>
        </div>
        <button
          onClick={() => setShowCreateForm(true)}
          className="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black px-4 py-2.5 rounded-xl flex items-center gap-1.5 transition-all shadow-md shadow-emerald-500/10 cursor-pointer"
        >
          <Plus className="w-4 h-4" />
          <span>CREAR NUEVA PROMO</span>
        </button>
      </div>

      {successMsg && (
        <div className="p-3 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 text-sm font-bold rounded-r-xl flex items-center gap-2">
          <CheckCircle2 className="w-4 h-4 shrink-0" />
          <span>{successMsg}</span>
        </div>
      )}

      {/* DISEÑO EN 2 COLUMNAS INSPIRADO EN "LISTADO DE PEDIDOS" */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {/* PARTE SUPERIOR / IZQUIERDA: LISTADO DE PROMOCIONES */}
        <div className="lg:col-span-6 space-y-4">
          <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
            <h3 className="text-xs font-black text-slate-400 uppercase tracking-wider pb-2 border-b border-slate-100">
              Listado de Promociones Activas ({promotions.length})
            </h3>
            
            <div className="space-y-3 max-h-[600px] overflow-y-auto pr-1">
              {promotions.length === 0 ? (
                <div className="py-20 text-center text-xs font-bold text-slate-400 uppercase">
                  No hay promociones cargadas.
                </div>
              ) : (
                promotions.map(p => (
                  <div 
                    key={p.id}
                    onClick={() => setSelectedPromo(p)}
                    className={`border rounded-xl p-4 cursor-pointer transition-all flex justify-between items-start gap-4 ${selectedPromo?.id === p.id ? 'border-[#0D6EFD] bg-blue-50/20' : 'border-slate-150 hover:bg-slate-50'}`}
                  >
                    <div className="space-y-1.5 flex-1">
                      <div className="flex items-center gap-2">
                        <h4 className="font-extrabold text-slate-800 text-sm">{p.name}</h4>
                        <span className="bg-amber-50 text-amber-700 border border-amber-200 text-[10px] px-1.5 py-0.2 rounded font-black">
                          {p.discountPercentage > 0 ? `-${p.discountPercentage}%` : `-$${p.discountAmount}`}
                        </span>
                      </div>
                      <p className="text-xs text-slate-500 leading-snug">{p.description}</p>
                    </div>
                    
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        handleDeletePromo(p.id);
                      }}
                      className="text-red-500 hover:bg-red-50 p-1.5 rounded-lg transition-colors cursor-pointer shrink-0"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>

        {/* PARTE INFERIOR / DERECHA: DETALLE DE PRODUCTOS INCLUIDOS EN PROMO SELECCIONADA */}
        <div className="lg:col-span-6 space-y-4">
          <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4 flex flex-col justify-between min-h-[400px]">
            <div>
              <h3 className="text-xs font-black text-slate-400 uppercase tracking-wider pb-2 border-b border-slate-100 mb-4">
                Detalle de Productos Incluidos en la Promoción
              </h3>

              {selectedPromo ? (
                <div className="space-y-5">
                  <div className="bg-slate-50 p-4 rounded-xl border border-slate-150">
                    <span className="text-[10px] font-black text-slate-400 uppercase block mb-1">Nombre Combo</span>
                    <h4 className="text-base font-black text-slate-800">{selectedPromo.name}</h4>
                    <p className="text-xs text-slate-500 mt-1 leading-snug">{selectedPromo.description}</p>
                    
                    <div className="grid grid-cols-2 gap-4 mt-4 text-xs font-semibold text-slate-600 border-t border-slate-200/60 pt-3">
                      <div>
                        <span className="text-slate-400 block mb-0.5">Tipo de Descuento</span>
                        <span className="font-extrabold text-slate-800">
                          {selectedPromo.discountPercentage > 0 ? 'Porcentual' : 'Monto Fijo'}
                        </span>
                      </div>
                      <div>
                        <span className="text-slate-400 block mb-0.5">Descuento</span>
                        <span className="font-extrabold text-[#0D6EFD]">
                          {selectedPromo.discountPercentage > 0 ? `${selectedPromo.discountPercentage}%` : `$${selectedPromo.discountAmount}`}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div className="space-y-2.5">
                    <h4 className="text-xs font-black text-slate-700 uppercase tracking-wider">Artículos que componen el combo:</h4>
                    <div className="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden bg-white">
                      {selectedPromo.items && selectedPromo.items.length > 0 ? (
                        selectedPromo.items.map((item, idx) => {
                          const prod = products.find(p => p.id === item.productId);
                          return (
                            <div key={idx} className="p-3 text-xs font-bold text-slate-700 flex justify-between items-center hover:bg-slate-50">
                              <div>
                                <p className="font-extrabold text-slate-800 text-sm">{item.productName}</p>
                                <p className="text-slate-400 mt-0.5">Código: {prod?.code || '---'}</p>
                              </div>
                              <span className="bg-slate-100 text-slate-800 px-2.5 py-1 rounded-lg text-xs font-black">
                                x{item.quantity} unidades
                              </span>
                            </div>
                          );
                        })
                      ) : (
                        // Fallback backward compatibility single product
                        selectedPromo.requiredProductId ? (
                          <div className="p-4 text-xs font-bold text-slate-700 flex justify-between items-center hover:bg-slate-50">
                            <div>
                              <p className="font-extrabold text-slate-800 text-sm">
                                {products.find(p => p.id === selectedPromo.requiredProductId)?.name || 'Producto Requerido'}
                              </p>
                              <p className="text-slate-400 mt-0.5">Compra mínima por volumen</p>
                            </div>
                            <span className="bg-slate-100 text-slate-800 px-2.5 py-1 rounded-lg text-xs font-black">
                              x{selectedPromo.requiredQuantity || 1} unidades
                            </span>
                          </div>
                        ) : (
                          <div className="p-4 text-center text-xs text-slate-400 font-bold uppercase">
                            No hay productos detallados.
                          </div>
                        )
                      )}
                    </div>
                  </div>
                </div>
              ) : (
                <div className="py-20 text-center text-xs font-bold text-slate-400 uppercase">
                  Seleccione una promoción para ver sus productos constituyentes.
                </div>
              )}
            </div>
            
            <p className="text-[10px] text-slate-400 font-bold mt-4">
              * El cajero puede aplicar combos seleccionándolos directamente desde el Punto de Venta. Se autocompletarán los artículos con los descuentos.
            </p>
          </div>
        </div>

      </div>

      {/* =========================================================================
          MODAL: CREAR COMBO DE PROMOCIÓN
          ========================================================================= */}
      {showCreateForm && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-white rounded-2xl border border-slate-200 w-full max-w-xl overflow-hidden shadow-2xl flex flex-col my-8 max-h-[90vh]">
            <header className="bg-slate-50 p-4 border-b border-slate-100 flex justify-between items-center">
              <h3 className="text-base font-black text-slate-800 flex items-center gap-1.5">
                <Tag className="w-4 h-4 text-[#0D6EFD]" />
                <span>Configurar Nueva Promoción o Combo</span>
              </h3>
              <button onClick={() => setShowCreateForm(false)}>
                <X className="w-5 h-5 text-slate-400 hover:text-slate-600" />
              </button>
            </header>

            <form onSubmit={handleSavePromo} className="p-6 space-y-4 flex-1 overflow-y-auto">
              
              {errorMsg && (
                <div className="p-3 bg-red-50 text-red-700 text-xs font-bold rounded-lg flex items-center gap-2">
                  <AlertTriangle className="w-4 h-4" />
                  <span>{errorMsg}</span>
                </div>
              )}

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-1">
                  <label className="text-xs font-bold text-slate-500 block">Nombre del Combo / Promo *</label>
                  <input
                    type="text"
                    placeholder="Ej: Combo Desayuno Completo"
                    required
                    className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold"
                    value={promoName}
                    onChange={(e) => setPromoName(e.target.value)}
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs font-bold text-slate-500 block">Descripción Comercial *</label>
                  <input
                    type="text"
                    placeholder="Ej: Llevá 2 Fideos y 1 Salsa con 15% Desc"
                    required
                    className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold"
                    value={promoDesc}
                    onChange={(e) => setPromoDesc(e.target.value)}
                  />
                </div>
              </div>

              {/* SECCIÓN DESCUENTOS */}
              <div className="grid grid-cols-3 gap-3 items-end bg-slate-50 p-3 rounded-xl border border-slate-150">
                <div className="space-y-1 col-span-1">
                  <label className="text-xs font-bold text-slate-500 block">Tipo Descuento</label>
                  <select
                    className="w-full bg-white border border-slate-250 rounded-lg p-1.5 text-xs font-semibold"
                    value={discountType}
                    onChange={(e) => setDiscountType(e.target.value as any)}
                  >
                    <option value="PERCENT">Porcentual (%)</option>
                    <option value="FIXED">Fijo ($)</option>
                  </select>
                </div>
                
                {discountType === 'PERCENT' ? (
                  <div className="space-y-1 col-span-2">
                    <label className="text-xs font-bold text-slate-500 block">Porcentaje Descuento (%)</label>
                    <input
                      type="number"
                      min="1"
                      max="100"
                      className="w-full bg-white border border-slate-250 rounded-lg p-1.5 text-xs font-extrabold text-slate-800"
                      value={discountPct}
                      onChange={(e) => setDiscountPct(Math.max(1, parseInt(e.target.value) || 0))}
                    />
                  </div>
                ) : (
                  <div className="space-y-1 col-span-2">
                    <label className="text-xs font-bold text-slate-500 block">Monto Descuento Fijo ($)</label>
                    <input
                      type="number"
                      min="1"
                      className="w-full bg-white border border-slate-250 rounded-lg p-1.5 text-xs font-extrabold text-slate-800"
                      value={discountAmt}
                      onChange={(e) => setDiscountAmt(Math.max(1, parseFloat(e.target.value) || 0))}
                    />
                  </div>
                )}
              </div>

              {/* CONSTRUCCIÓN DEL COMBO / ARTÍCULOS */}
              <div className="space-y-2 border-t border-slate-100 pt-3">
                <h4 className="text-xs font-black text-slate-700 uppercase tracking-wider">Componentes del Combo</h4>
                
                {/* Selector para añadir productos al borrador */}
                <div className="grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
                  <div className="md:col-span-8 space-y-1">
                    <label className="text-[10px] font-bold text-slate-500">Añadir Producto</label>
                    <select
                      className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold focus:outline-none"
                      value={selectedProdToAdd}
                      onChange={(e) => setSelectedProdToAdd(e.target.value)}
                    >
                      <option value="">Seleccione producto...</option>
                      {products.map(p => (
                        <option key={p.id} value={p.id}>{p.name} (Cod: {p.code})</option>
                      ))}
                    </select>
                  </div>
                  <div className="md:col-span-2 space-y-1">
                    <label className="text-[10px] font-bold text-slate-500">Cantidad</label>
                    <input
                      type="number"
                      min="1"
                      className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-bold"
                      value={prodToAddQty}
                      onChange={(e) => setProdToAddQty(Math.max(1, parseInt(e.target.value) || 1))}
                    />
                  </div>
                  <button
                    type="button"
                    onClick={handleAddProductToCombo}
                    className="md:col-span-2 bg-[#0D6EFD] text-white rounded-lg py-2.5 text-xs font-black hover:bg-[#0b5ed7] transition-colors cursor-pointer flex items-center justify-center gap-1"
                  >
                    <Plus className="w-3.5 h-3.5" />
                    <span>Añadir</span>
                  </button>
                </div>

                {/* Items seleccionados en borrador */}
                <div className="divide-y divide-slate-100 border border-slate-100 rounded-xl max-h-40 overflow-y-auto bg-slate-50/50 p-2 space-y-1.5">
                  {promoItems.length === 0 ? (
                    <p className="text-center text-[10px] font-bold text-slate-400 py-6 uppercase">Aún no agregó productos al combo.</p>
                  ) : (
                    promoItems.map(item => {
                      const prod = products.find(p => p.id === item.productId);
                      return (
                        <div key={item.productId} className="flex justify-between items-center text-xs font-bold p-1 bg-white border border-slate-100 rounded px-2">
                          <span>{prod ? prod.name : '---'} (x{item.quantity})</span>
                          <button
                            type="button"
                            onClick={() => handleRemoveProductFromCombo(item.productId)}
                            className="text-red-500 hover:bg-red-50 p-1 rounded"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                          </button>
                        </div>
                      );
                    })
                  )}
                </div>
              </div>

              <button
                type="submit"
                className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-3 rounded-xl text-xs uppercase tracking-wider transition-colors cursor-pointer"
              >
                CREAR Y ACTIVAR COMBO COMERCIAL
              </button>

            </form>
          </div>
        </div>
      )}

    </div>
  );
}
