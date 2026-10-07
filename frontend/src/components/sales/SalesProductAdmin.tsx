import React, { useState, useEffect } from 'react';
import { Product, Promotion } from '../../types';
import { 
  Package, 
  Plus, 
  Edit, 
  Trash2, 
  Search, 
  Save, 
  X, 
  Barcode, 
  DollarSign, 
  Scale, 
  Tag, 
  CheckCircle2, 
  AlertTriangle 
} from 'lucide-react';

interface SalesProductAdminProps {
  products: Product[];
  promotions: Promotion[];
  onUpdateProducts: (products: Product[]) => void;
}

interface Supplier {
  id: string;
  name: string;
}

export function SalesProductAdmin({
  products,
  promotions,
  onUpdateProducts
}: SalesProductAdminProps) {
  const [searchTerm, setSearchTerm] = useState('');
  const [showForm, setShowForm] = useState(false);
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);

  // Form Fields
  const [pCode, setPCode] = useState('');
  const [pName, setPName] = useState('');
  const [pCategory, setPCategory] = useState('');
  const [pCost, setPCost] = useState(0);
  const [pPrice, setPPrice] = useState(0); // Minorista Base
  const [pPriceWholesale, setPPriceWholesale] = useState(0); // Mayorista Override
  const [pPriceDistributor, setPPriceDistributor] = useState(0); // Distribuidor Override
  const [pPricePromo, setPPricePromo] = useState(0); // Promocional Override
  const [pBarcode, setPBarcode] = useState('');
  const [pIva, setPIva] = useState<'0%' | '10.5%' | '21%'>('21%');
  const [pStock, setPStock] = useState(0);
  const [pMinStock, setPMinStock] = useState(10);
  const [pMaxDiscount, setPMaxDiscount] = useState(15);
  const [pSupplierId, setPSupplierId] = useState('');
  const [pAssociatedPromoId, setPAssociatedPromoId] = useState('');

  // Proveedores cargados de localStorage
  const [suppliers, setSuppliers] = useState<Supplier[]>([]);
  useEffect(() => {
    const saved = localStorage.getItem('erp_distribuidora_suppliers');
    if (saved) {
      try { setSuppliers(JSON.parse(saved)); } catch (e) { console.error(e); }
    } else {
      const defaultSuppliers = [
        { id: '1', name: 'Arcor S.A.' },
        { id: '2', name: 'Coca-Cola Andina' },
        { id: '3', name: 'Molinos Río de la Plata' }
      ];
      localStorage.setItem('erp_distribuidora_suppliers', JSON.stringify(defaultSuppliers));
      setSuppliers(defaultSuppliers);
    }
  }, []);

  // Alertas
  const [successMsg, setSuccessMsg] = useState('');
  const [errorMsg, setErrorMsg] = useState('');

  // Categorías de productos
  const categories = Array.from(new Set(products.map(p => p.category)));

  // Filtrar productos
  const filteredProducts = products.filter(p => 
    p.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    p.code.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (p.barcode && p.barcode.includes(searchTerm))
  );

  // Abrir Formulario
  const handleOpenAddForm = () => {
    setEditingProduct(null);
    setPCode('');
    setPName('');
    setPCategory(categories[0] || 'Almacén');
    setPCost(0);
    setPPrice(0);
    setPPriceWholesale(0);
    setPPriceDistributor(0);
    setPPricePromo(0);
    setPBarcode('');
    setPIva('21%');
    setPStock(100);
    setPMinStock(10);
    setPMaxDiscount(15);
    setPSupplierId('');
    setPAssociatedPromoId('');
    setErrorMsg('');
    setShowForm(true);
  };

  const handleOpenEditForm = (p: Product) => {
    setEditingProduct(p);
    setPCode(p.code);
    setPName(p.name);
    setPCategory(p.category);
    setPCost(p.cost);
    setPPrice(p.price);
    setPPriceWholesale(p.priceWholesale || 0);
    setPPriceDistributor(p.priceDistributor || 0);
    setPPricePromo(p.pricePromo || 0);
    setPBarcode(p.barcode || '');
    setPIva(p.iva);
    setPStock(p.stock);
    setPMinStock(p.minStock);
    setPMaxDiscount(p.maxDiscount || 15);
    setPSupplierId(p.supplierId || '');
    setPAssociatedPromoId(p.associatedPromoId || '');
    setErrorMsg('');
    setShowForm(true);
  };

  // Guardar Producto
  const handleSaveProduct = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg('');
    setSuccessMsg('');

    if (!pCode.trim() || !pName.trim()) {
      setErrorMsg('Por favor complete el nombre y código del artículo.');
      return;
    }

    // Validar duplicado de código
    const codeDup = products.find(p => p.code.toLowerCase() === pCode.trim().toLowerCase() && (!editingProduct || p.id !== editingProduct.id));
    if (codeDup) {
      setErrorMsg(`Código duplicado. El código "${pCode}" ya pertenece a: ${codeDup.name}`);
      return;
    }

    const selectedSupplier = suppliers.find(s => s.id === pSupplierId);

    const productData: Product = {
      id: editingProduct ? editingProduct.id : Date.now().toString(),
      code: pCode.trim(),
      name: pName.trim(),
      category: pCategory,
      cost: pCost,
      price: pPrice,
      iva: pIva,
      stock: pStock,
      minStock: pMinStock,
      // Commercial overrides
      barcode: pBarcode.trim() || undefined,
      supplierId: pSupplierId || undefined,
      supplierName: selectedSupplier ? selectedSupplier.name : undefined,
      priceWholesale: pPriceWholesale > 0 ? pPriceWholesale : undefined,
      priceDistributor: pPriceDistributor > 0 ? pPriceDistributor : undefined,
      pricePromo: pPricePromo > 0 ? pPricePromo : undefined,
      maxDiscount: pMaxDiscount,
      associatedPromoId: pAssociatedPromoId || undefined
    };

    let updatedList: Product[] = [];
    if (editingProduct) {
      updatedList = products.map(p => p.id === editingProduct.id ? productData : p);
    } else {
      updatedList = [productData, ...products];
    }

    onUpdateProducts(updatedList);
    setShowForm(false);
    setSuccessMsg(`¡Producto "${productData.name}" guardado exitosamente!`);
    setTimeout(() => setSuccessMsg(''), 3000);
  };

  // Eliminar Producto
  const handleDeleteProduct = (id: string) => {
    if (window.confirm('¿Está seguro de que desea eliminar este producto?')) {
      const updated = products.filter(p => p.id !== id);
      onUpdateProducts(updated);
      setSuccessMsg('Producto eliminado correctamente.');
      setTimeout(() => setSuccessMsg(''), 2000);
    }
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      
      {/* HEADER PRODUCTOS */}
      <div className="flex justify-between items-center bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
        <div>
          <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block">CONFIGURACIÓN COMERCIAL</span>
          <h2 className="text-xl font-bold text-slate-800 flex items-center gap-2">
            <Package className="w-5 h-5 text-[#0D6EFD]" />
            <span>Administración de Productos Comerciales</span>
          </h2>
        </div>
        <button
          onClick={handleOpenAddForm}
          className="bg-[#0D6EFD] hover:bg-[#0b5ed7] text-white text-xs font-black px-4 py-2.5 rounded-xl flex items-center gap-1.5 transition-all shadow-md cursor-pointer"
        >
          <Plus className="w-4 h-4" />
          <span>NUEVO PRODUCTO</span>
        </button>
      </div>

      {successMsg && (
        <div className="p-3 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 text-sm font-bold rounded-r-xl flex items-center gap-2">
          <CheckCircle2 className="w-4 h-4 shrink-0" />
          <span>{successMsg}</span>
        </div>
      )}

      {/* RECONOCIMIENTO DE FILTRADO Y TABLA */}
      <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
        
        <div className="relative">
          <Search className="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400 w-5 h-5" />
          <input
            type="text"
            placeholder="Filtrar por nombre, código o código de barras..."
            className="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-4 py-3 text-xs font-bold text-slate-800"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>

        <div className="border border-slate-150 rounded-xl overflow-hidden bg-white">
          <table className="w-full text-left text-xs font-bold border-collapse">
            <thead>
              <tr className="bg-slate-50 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-100">
                <th className="p-3.5">Código</th>
                <th className="p-3.5">Producto</th>
                <th className="p-3.5">Proveedor</th>
                <th className="p-3.5">IVA / Stock</th>
                <th className="p-3.5 text-right">Lista Minorista</th>
                <th className="p-3.5 text-right">Lista Mayorista</th>
                <th className="p-3.5 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-slate-700">
              {filteredProducts.length === 0 ? (
                <tr>
                  <td colSpan={7} className="p-8 text-center text-slate-400 font-bold uppercase">No se hallaron productos.</td>
                </tr>
              ) : (
                filteredProducts.map(p => (
                  <tr key={p.id} className="hover:bg-slate-50/50">
                    <td className="p-3.5">
                      <span className="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-black">
                        {p.code}
                      </span>
                      {p.barcode && (
                        <span className="text-[10px] text-slate-400 block mt-1 font-normal flex items-center gap-0.5">
                          <Barcode className="w-3 h-3 text-slate-400" /> {p.barcode}
                        </span>
                      )}
                    </td>
                    <td className="p-3.5">
                      <p className="font-extrabold text-slate-800 text-sm">{p.name}</p>
                      <p className="text-[10px] text-slate-400 mt-0.5">Categoría: {p.category}</p>
                    </td>
                    <td className="p-3.5 text-slate-500 text-xs">
                      {p.supplierName || 'No asignado'}
                    </td>
                    <td className="p-3.5 text-xs text-slate-600">
                      <p>IVA: <span className="font-bold text-slate-700">{p.iva}</span></p>
                      <p className="mt-0.5">
                        Stock: <span className={`font-black ${p.stock <= p.minStock ? 'text-red-500' : 'text-slate-700'}`}>{p.stock}</span>
                      </p>
                    </td>
                    <td className="p-3.5 text-right font-black text-slate-800 text-xs">
                      ${p.price.toFixed(2)}
                    </td>
                    <td className="p-3.5 text-right font-black text-blue-700 text-xs">
                      ${(p.priceWholesale || p.price * 0.85).toFixed(2)}
                    </td>
                    <td className="p-3.5 text-center">
                      <div className="flex justify-center gap-1">
                        <button
                          onClick={() => handleOpenEditForm(p)}
                          className="text-[#0D6EFD] hover:bg-blue-50 p-1.5 rounded transition-all cursor-pointer"
                        >
                          <Edit className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => handleDeleteProduct(p.id)}
                          className="text-red-500 hover:bg-red-50 p-1.5 rounded transition-all cursor-pointer"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

      </div>

      {/* =========================================================================
          MODAL: NUEVO / EDITAR PRODUCTO (COMERCIAL COMPLETO)
          ========================================================================= */}
      {showForm && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-white rounded-3xl border border-slate-200 w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col my-8 max-h-[90vh]">
            <header className="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
              <h3 className="text-base font-black text-slate-800 flex items-center gap-1.5">
                <Package className="w-5 h-5 text-[#0D6EFD]" />
                <span>{editingProduct ? 'Modificar Artículo Comercial' : 'Agregar Nuevo Artículo'}</span>
              </h3>
              <button onClick={() => setShowForm(false)}>
                <X className="w-6 h-6 text-slate-400 hover:text-slate-600" />
              </button>
            </header>

            <form onSubmit={handleSaveProduct} className="p-6 space-y-5 overflow-y-auto flex-1">
              
              {errorMsg && (
                <div className="p-3.5 bg-red-50 text-red-700 text-xs font-bold rounded-lg flex items-center gap-1.5">
                  <AlertTriangle className="w-4 h-4" />
                  <span>{errorMsg}</span>
                </div>
              )}

              {/* SECCIÓN 1: DATOS GENERALES */}
              <div className="space-y-3">
                <h4 className="text-xs font-black text-slate-400 uppercase tracking-wider pb-1 border-b border-slate-100">Ficha Técnica General</h4>
                
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div className="col-span-2 space-y-1">
                    <label className="text-xs font-bold text-slate-500">Nombre del Producto *</label>
                    <input
                      type="text"
                      placeholder="Ej: Fideos Tallarín Arcor 500g"
                      required
                      className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold focus:outline-none focus:border-[#0D6EFD]"
                      value={pName}
                      onChange={(e) => setPName(e.target.value)}
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-500">Código Único *</label>
                    <input
                      type="text"
                      placeholder="FIDE-01"
                      required
                      className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold focus:outline-none focus:border-[#0D6EFD]"
                      value={pCode}
                      onChange={(e) => setPCode(e.target.value)}
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-500">Categoría *</label>
                    <select
                      className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold focus:outline-none focus:border-[#0D6EFD]"
                      value={pCategory}
                      onChange={(e) => setPCategory(e.target.value)}
                    >
                      {categories.map(c => (
                        <option key={c} value={c}>{c}</option>
                      ))}
                      {!categories.includes('Almacén') && <option value="Almacén">Almacén</option>}
                      {!categories.includes('Bebidas') && <option value="Bebidas">Bebidas</option>}
                      {!categories.includes('Fiambrería') && <option value="Fiambrería">Fiambrería</option>}
                    </select>
                  </div>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-500">Código de Barras</label>
                    <input
                      type="text"
                      placeholder="Ej: 7791234567890"
                      className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold focus:outline-none focus:border-[#0D6EFD]"
                      value={pBarcode}
                      onChange={(e) => setPBarcode(e.target.value)}
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-500">Proveedor</label>
                    <select
                      className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold focus:outline-none focus:border-[#0D6EFD]"
                      value={pSupplierId}
                      onChange={(e) => setPSupplierId(e.target.value)}
                    >
                      <option value="">Ninguno...</option>
                      {suppliers.map(s => (
                        <option key={s.id} value={s.id}>{s.name}</option>
                      ))}
                    </select>
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-500">Tasa de IVA</label>
                    <select
                      className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold focus:outline-none"
                      value={pIva}
                      onChange={(e) => setPIva(e.target.value as any)}
                    >
                      <option value="21%">21%</option>
                      <option value="10.5%">10.5%</option>
                      <option value="0%">0%</option>
                    </select>
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-500">Desc. Máximo Permitido %</label>
                    <input
                      type="number"
                      min="0"
                      max="100"
                      className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold focus:outline-none"
                      value={pMaxDiscount}
                      onChange={(e) => setPMaxDiscount(Math.max(0, parseInt(e.target.value) || 0))}
                    />
                  </div>
                </div>
              </div>

              {/* SECCIÓN 2: ESTRUCTURA DE MÚLTIPLES LISTAS DE PRECIOS */}
              <div className="space-y-3 pt-3 border-t border-slate-100">
                <h4 className="text-xs font-black text-slate-400 uppercase tracking-wider pb-1 border-b border-slate-100">Tarifario de Listas de Precios</h4>
                
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div className="space-y-1 bg-slate-50 p-2.5 rounded-xl border border-slate-150">
                    <label className="text-[10px] font-black text-slate-500 uppercase block mb-1">Costo Base ($)</label>
                    <input
                      type="number"
                      step="0.01"
                      className="w-full bg-white border border-slate-200 rounded p-1 text-xs font-extrabold"
                      value={pCost}
                      onChange={(e) => setPCost(Math.max(0, parseFloat(e.target.value) || 0))}
                    />
                  </div>
                  <div className="space-y-1 bg-[#0D6EFD]/5 p-2.5 rounded-xl border border-[#0D6EFD]/10">
                    <label className="text-[10px] font-black text-blue-700 uppercase block mb-1">P. Minorista Base *</label>
                    <input
                      type="number"
                      step="0.01"
                      required
                      className="w-full bg-white border border-slate-250 rounded p-1 text-xs font-black text-[#0D6EFD]"
                      value={pPrice}
                      onChange={(e) => setPPrice(Math.max(0, parseFloat(e.target.value) || 0))}
                    />
                  </div>
                  <div className="space-y-1 bg-slate-50 p-2.5 rounded-xl border border-slate-150">
                    <label className="text-[10px] font-black text-slate-500 uppercase block mb-1">P. Mayorista Override</label>
                    <input
                      type="number"
                      step="0.01"
                      placeholder="Autocalc -15%"
                      className="w-full bg-white border border-slate-200 rounded p-1 text-xs font-extrabold"
                      value={pPriceWholesale || ''}
                      onChange={(e) => setPPriceWholesale(Math.max(0, parseFloat(e.target.value) || 0))}
                    />
                  </div>
                  <div className="space-y-1 bg-slate-50 p-2.5 rounded-xl border border-slate-150">
                    <label className="text-[10px] font-black text-slate-500 uppercase block mb-1">P. Distribuidor Override</label>
                    <input
                      type="number"
                      step="0.01"
                      placeholder="Autocalc -25%"
                      className="w-full bg-white border border-slate-200 rounded p-1 text-xs font-extrabold"
                      value={pPriceDistributor || ''}
                      onChange={(e) => setPPriceDistributor(Math.max(0, parseFloat(e.target.value) || 0))}
                    />
                  </div>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div className="col-span-1 space-y-1 bg-slate-50 p-2.5 rounded-xl border border-slate-150">
                    <label className="text-[10px] font-black text-slate-500 uppercase block mb-1">P. Promocional Override</label>
                    <input
                      type="number"
                      step="0.01"
                      placeholder="Autocalc -10%"
                      className="w-full bg-white border border-slate-200 rounded p-1 text-xs font-extrabold"
                      value={pPricePromo || ''}
                      onChange={(e) => setPPricePromo(Math.max(0, parseFloat(e.target.value) || 0))}
                    />
                  </div>
                  <div className="col-span-2 space-y-1">
                    <label className="text-xs font-bold text-slate-500">Combo Promocional Asociado</label>
                    <select
                      className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold focus:outline-none"
                      value={pAssociatedPromoId}
                      onChange={(e) => setPAssociatedPromoId(e.target.value)}
                    >
                      <option value="">Ninguno...</option>
                      {promotions.map(promo => (
                        <option key={promo.id} value={promo.id}>{promo.name}</option>
                      ))}
                    </select>
                  </div>
                </div>
              </div>

              {/* SECCIÓN 3: CONTROL DE STOCK */}
              <div className="space-y-3 pt-3 border-t border-slate-100">
                <h4 className="text-xs font-black text-slate-400 uppercase tracking-wider pb-1 border-b border-slate-100">Control de Almacenamiento</h4>
                
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-500">Stock Actual en Góndola</label>
                    <input
                      type="number"
                      className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-bold"
                      value={pStock}
                      onChange={(e) => setPStock(Math.max(0, parseInt(e.target.value) || 0))}
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-500">Stock Mínimo Crítico (Alerta)</label>
                    <input
                      type="number"
                      className="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-bold"
                      value={pMinStock}
                      onChange={(e) => setPMinStock(Math.max(1, parseInt(e.target.value) || 1))}
                    />
                  </div>
                </div>
              </div>

              <button
                type="submit"
                className="w-full bg-[#0D6EFD] hover:bg-[#0b5ed7] text-white font-black py-3 rounded-xl text-xs uppercase tracking-wider transition-colors cursor-pointer mt-4"
              >
                GUARDAR Y COMPILAR ARTÍCULO COMERCIAL
              </button>

            </form>
          </div>
        </div>
      )}

    </div>
  );
}
