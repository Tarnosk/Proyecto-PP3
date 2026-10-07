import React, { useState } from 'react';
import { Customer, Supplier, Purchase, Product, PriceList, User, PurchaseItem } from '../types';
import { proveedoresService, comprasService } from '../services';
import { formatDateTime } from '../utils/dateUtils';
import { 
  Users, 
  Briefcase, 
  ShoppingCart, 
  Plus, 
  Edit, 
  Trash2, 
  Search, 
  AlertTriangle, 
  MapPin, 
  CreditCard, 
  Phone, 
  Mail, 
  FileText,
  Calendar,
  Check, 
  X,
  PlusCircle,
  Package,
  TrendingDown,
  Clock,
  Layers,
  Star,
  CheckCircle,
  XCircle,
  RefreshCw,
  ExternalLink
} from 'lucide-react';

interface CustomersViewProps {
  currentUser: User;
  customers: Customer[];
  priceLists: PriceList[];
  products: Product[];
  suppliers: Supplier[];
  purchases: Purchase[];
  onUpdateCustomers: (customers: Customer[]) => void;
  onUpdateSuppliers: (suppliers: Supplier[]) => void;
  onUpdatePurchases: (purchases: Purchase[]) => void;
  onUpdateProducts: (products: Product[]) => void;
}

export function CustomersView({
  currentUser,
  customers,
  priceLists,
  products,
  suppliers,
  purchases,
  onUpdateCustomers,
  onUpdateSuppliers,
  onUpdatePurchases,
  onUpdateProducts
}: CustomersViewProps) {
  // Estado para la pestaña activa
  const [activeTab, setActiveTab] = useState<'CLIENTES' | 'PROVEEDORES' | 'COMPRAS'>('CLIENTES');

  // Búsqueda y filtrado general
  const [searchQuery, setSearchQuery] = useState('');
  const [zoneFilter, setZoneFilter] = useState('Todas');
  const [categoryFilter, setCategoryFilter] = useState('Todas');

  // Estados para formularios
  const [showCustomerForm, setShowCustomerForm] = useState(false);
  const [editingCustomer, setEditingCustomer] = useState<Customer | null>(null);
  const [customerError, setCustomerError] = useState('');

  const [showSupplierForm, setShowSupplierForm] = useState(false);
  const [editingSupplier, setEditingSupplier] = useState<Supplier | null>(null);
  const [supplierError, setSupplierError] = useState('');

  const [showPurchaseForm, setShowPurchaseForm] = useState(false);
  const [purchaseError, setPurchaseError] = useState('');

  // Campos para Clientes
  const [cName, setCName] = useState('');
  const [cCuit, setCCuit] = useState('');
  const [cAddress, setCAddress] = useState('');
  const [cPhone, setCPhone] = useState('');
  const [cZone, setCZone] = useState('Norte');
  const [cPriceList, setCPriceList] = useState('');

  // Campos para Proveedores
  const [sName, setSName] = useState('');
  const [sCuit, setSCuit] = useState('');
  const [sPhone, setSPhone] = useState('');
  const [sEmail, setSEmail] = useState('');
  const [sAddress, setSAddress] = useState('');
  const [sCategory, setSCategory] = useState('');
  const [sPlazoDias, setSPlazoDias] = useState<number | ''>('');
  const [supplierStatusFilter, setSupplierStatusFilter] = useState<'Todos' | 'Activos' | 'Inactivos'>('Todos');

  // Modal de Asociación de Productos
  const [supplierForProducts, setSupplierForProducts] = useState<Supplier | null>(null);
  const [assocProductId, setAssocProductId] = useState('');
  const [assocPrice, setAssocPrice] = useState<number>(0);
  const [assocIsPrincipal, setAssocIsPrincipal] = useState(false);
  const [assocLoading, setAssocLoading] = useState(false);
  const [assocError, setAssocError] = useState('');

  // Modal de Plazos de Entrega
  const [supplierForPlazos, setSupplierForPlazos] = useState<Supplier | null>(null);
  const [plazosList, setPlazosList] = useState<any[]>([]);
  const [nuevoPlazoDias, setNuevoPlazoDias] = useState<number>(3);
  const [nuevoPlazoMotivo, setNuevoPlazoMotivo] = useState('');
  const [plazosLoading, setPlazosLoading] = useState(false);
  const [plazosError, setPlazosError] = useState('');

  // Campos para una Nueva Compra (Ingreso de Mercadería)
  const [purchaseSupplierId, setPurchaseSupplierId] = useState('');
  const [purchaseInvoiceNumber, setPurchaseInvoiceNumber] = useState('');
  const [purchaseNotes, setPurchaseNotes] = useState('');
  
  // Ítems de la compra en borrador
  const [purchaseItems, setPurchaseItems] = useState<PurchaseItem[]>([]);
  // Ítem actual que se está cargando en la compra
  const [currentSelectedProductId, setCurrentSelectedProductId] = useState('');
  const [currentSelectedProductQuantity, setCurrentSelectedProductQuantity] = useState(1);
  const [currentSelectedProductCost, setCurrentSelectedProductCost] = useState(0);

  // Zonas de ruteo conocidas
  const zones = ['Todas', 'Norte', 'Centro', 'Sur', 'Oeste', 'Este'];
  const supplierCategories = ['Todas', ...Array.from(new Set(suppliers.map(s => s.category)))];

  // --- FILTRADOS ---
  const filteredCustomers = customers.filter(c => {
    const matchesSearch = c.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
                          c.cuit.includes(searchQuery) || 
                          c.address.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesZone = zoneFilter === 'Todas' || c.zone === zoneFilter;
    return matchesSearch && matchesZone;
  });

  const filteredSuppliers = suppliers.filter(s => {
    const matchesSearch = s.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
                          s.cuit.includes(searchQuery) || 
                          s.category.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesCategory = categoryFilter === 'Todas' || s.category === categoryFilter;
    const matchesStatus = supplierStatusFilter === 'Todos' || 
      (supplierStatusFilter === 'Activos' && (s.estado === 'activo' || !s.estado)) ||
      (supplierStatusFilter === 'Inactivos' && s.estado === 'inactivo');
    return matchesSearch && matchesCategory && matchesStatus;
  });

  const filteredPurchases = purchases.filter(p => {
    return p.supplierName.toLowerCase().includes(searchQuery.toLowerCase()) || 
           (p.invoiceNumber && p.invoiceNumber.includes(searchQuery));
  });

  // --- ACCIONES DE CLIENTES ---
  const handleOpenAddCustomer = () => {
    setEditingCustomer(null);
    setCName('');
    setCCuit('');
    setCAddress('');
    setCPhone('');
    setCZone('Norte');
    setCPriceList(priceLists[0]?.id || '');
    setCustomerError('');
    setShowCustomerForm(true);
  };

  const handleOpenEditCustomer = (customer: Customer) => {
    setEditingCustomer(customer);
    setCName(customer.name);
    setCCuit(customer.cuit);
    setCAddress(customer.address);
    setCPhone(customer.phone);
    setCZone(customer.zone);
    setCPriceList(customer.priceListId);
    setCustomerError('');
    setShowCustomerForm(true);
  };

  const handleSaveCustomer = (e: React.FormEvent) => {
    e.preventDefault();
    setCustomerError('');

    if (!cName.trim() || !cCuit.trim() || !cAddress.trim() || !cPhone.trim()) {
      setCustomerError('Todos los campos son obligatorios para registrar la ficha fiscal.');
      return;
    }

    // Validar CUIT duplicado
    const duplicateCuit = customers.find(c => c.cuit === cCuit.trim() && (!editingCustomer || c.id !== editingCustomer.id));
    if (duplicateCuit) {
      setCustomerError(`Ya existe un cliente registrado con el CUIT ${cCuit}: ${duplicateCuit.name}`);
      return;
    }

    let updatedCustomers: Customer[];
    if (editingCustomer) {
      updatedCustomers = customers.map(c => 
        c.id === editingCustomer.id 
          ? { ...c, name: cName.trim(), cuit: cCuit.trim(), address: cAddress.trim(), phone: cPhone.trim(), zone: cZone, priceListId: cPriceList }
          : c
      );
    } else {
      const newCustomer: Customer = {
        id: 'CUST-' + Date.now(),
        name: cName.trim(),
        cuit: cCuit.trim(),
        address: cAddress.trim(),
        phone: cPhone.trim(),
        zone: cZone,
        priceListId: cPriceList
      };
      updatedCustomers = [...customers, newCustomer];
    }

    onUpdateCustomers(updatedCustomers);
    setShowCustomerForm(false);
    setEditingCustomer(null);
  };

  const handleDeleteCustomer = (id: string, name: string) => {
    if (window.confirm(`¿Seguro que desea eliminar la ficha del cliente "${name}"?`)) {
      onUpdateCustomers(customers.filter(c => c.id !== id));
    }
  };

  // --- ACCIONES DE PROVEEDORES ---
  const handleOpenAddSupplier = () => {
    setEditingSupplier(null);
    setSName('');
    setSCuit('');
    setSPhone('');
    setSEmail('');
    setSAddress('');
    setSCategory('');
    setSPlazoDias('');
    setSupplierError('');
    setShowSupplierForm(true);
  };

  const handleOpenEditSupplier = (supplier: Supplier) => {
    setEditingSupplier(supplier);
    setSName(supplier.name);
    setSCuit(supplier.cuit);
    setSPhone(supplier.phone);
    setSEmail(supplier.email);
    setSAddress(supplier.address);
    setSCategory(supplier.category);
    setSPlazoDias(supplier.plazo_entrega_dias ?? '');
    setSupplierError('');
    setShowSupplierForm(true);
  };

  const handleSaveSupplier = async (e: React.FormEvent) => {
    e.preventDefault();
    setSupplierError('');

    if (!sName.trim() || !sCuit.trim()) {
      setSupplierError('La razón social y el CUIT son obligatorios.');
      return;
    }

    try {
      if (editingSupplier) {
        const idToUpdate = editingSupplier.id_proveedor || editingSupplier.id;
        const updatedSupplier = await proveedoresService.update(idToUpdate, {
          name: sName.trim(),
          razon_social: sName.trim(),
          cuit: sCuit.trim(),
          phone: sPhone.trim(),
          telefono: sPhone.trim(),
          email: sEmail.trim(),
          correo: sEmail.trim(),
          address: sAddress.trim() || 'General',
          category: sCategory.trim() || 'General',
          plazo_entrega_dias: sPlazoDias !== '' ? Number(sPlazoDias) : undefined,
        });
        onUpdateSuppliers(suppliers.map(s => s.id === editingSupplier.id ? updatedSupplier : s));
      } else {
        const createdSupplier = await proveedoresService.create({
          name: sName.trim(),
          razon_social: sName.trim(),
          cuit: sCuit.trim(),
          phone: sPhone.trim(),
          telefono: sPhone.trim(),
          email: sEmail.trim(),
          correo: sEmail.trim(),
          address: sAddress.trim() || 'General',
          category: sCategory.trim() || 'General',
          plazo_entrega_dias: sPlazoDias !== '' ? Number(sPlazoDias) : undefined,
        });
        onUpdateSuppliers([createdSupplier, ...suppliers]);
      }
      setShowSupplierForm(false);
      setEditingSupplier(null);
    } catch (err: any) {
      setSupplierError(err.message || 'Error al guardar el proveedor en el backend.');
    }
  };

  // PV04: Desactivación / Reactivación lógica de proveedor
  const handleToggleSupplierStatus = async (supplier: Supplier) => {
    const isActivo = supplier.estado === 'activo' || !supplier.estado;
    const idProv = supplier.id_proveedor || supplier.id;
    try {
      if (isActivo) {
        if (!window.confirm(`¿Seguro que desea desactivar al proveedor "${supplier.name}"? Los datos históricos y órdenes registradas se mantendrán intactos.`)) {
          return;
        }
        await proveedoresService.desactivar(idProv);
        onUpdateSuppliers(suppliers.map(s => s.id === supplier.id ? { ...s, estado: 'inactivo' } : s));
      } else {
        await proveedoresService.activar(idProv);
        onUpdateSuppliers(suppliers.map(s => s.id === supplier.id ? { ...s, estado: 'activo' } : s));
      }
    } catch (err: any) {
      alert(err.message || 'Error al cambiar estado del proveedor en la API.');
    }
  };

  // PV05: Asociación de productos a proveedores con precio acordado
  const handleOpenProductAssociation = async (supplier: Supplier) => {
    setSupplierForProducts(supplier);
    setAssocError('');
    if (products.length > 0) {
      setAssocProductId(products[0].id);
      setAssocPrice(products[0].cost);
    }
    setAssocIsPrincipal(false);

    const idProv = supplier.id_proveedor || Number(supplier.id);
    if (!isNaN(idProv) && idProv > 0) {
      try {
        const fullProv = await proveedoresService.getById(idProv);
        setSupplierForProducts(fullProv);
      } catch (e) {
        console.warn('Usando proveedor local para asociación de productos:', e);
      }
    }
  };

  const handleAssociateProduct = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!supplierForProducts || !assocProductId) return;
    setAssocLoading(true);
    setAssocError('');
    const idProv = supplierForProducts.id_proveedor || Number(supplierForProducts.id);
    const prod = products.find(p => p.id === assocProductId);
    const idProdNum = prod?.id_producto || Number(assocProductId);

    try {
      if (!isNaN(idProv) && idProv > 0 && !isNaN(idProdNum) && idProdNum > 0) {
        await proveedoresService.asociarProductos(idProv, [{
          id_producto: idProdNum,
          precio_acordado: assocPrice,
          es_proveedor_principal: assocIsPrincipal
        }]);
        const fullProv = await proveedoresService.getById(idProv);
        setSupplierForProducts(fullProv);
        onUpdateSuppliers(suppliers.map(s => s.id === supplierForProducts.id ? fullProv : s));
      } else {
        const newAssoc = {
          id_producto: idProdNum || Date.now(),
          nombre: prod?.name || 'Producto',
          codigo_sku: prod?.code || '',
          precio_acordado: assocPrice,
          es_proveedor_principal: assocIsPrincipal ? 1 : 0
        };
        const currentList = supplierForProducts.productos_asociados || [];
        const updatedList = [...currentList.filter((p: any) => p.id_producto !== idProdNum), newAssoc];
        const updatedProv = { ...supplierForProducts, productos_asociados: updatedList };
        setSupplierForProducts(updatedProv);
        onUpdateSuppliers(suppliers.map(s => s.id === supplierForProducts.id ? updatedProv : s));
      }
    } catch (err: any) {
      setAssocError(err.message || 'Error al asociar producto al proveedor.');
    } finally {
      setAssocLoading(false);
    }
  };

  const handleRemoveAssociatedProduct = async (idProducto: number) => {
    if (!supplierForProducts) return;
    try {
      const idProv = supplierForProducts.id_proveedor || Number(supplierForProducts.id);
      if (!isNaN(idProv) && idProv > 0) {
        const assoc = supplierForProducts.productos_asociados?.find((p: any) => p.id_producto === idProducto);
        if (assoc && assoc.id_producto_proveedor) {
          await proveedoresService.desasociarProducto(assoc.id_producto_proveedor);
        }
        const fullProv = await proveedoresService.getById(idProv);
        setSupplierForProducts(fullProv);
        onUpdateSuppliers(suppliers.map(s => s.id === supplierForProducts.id ? fullProv : s));
      } else {
        const updatedList = (supplierForProducts.productos_asociados || []).filter((p: any) => p.id_producto !== idProducto);
        const updatedProv = { ...supplierForProducts, productos_asociados: updatedList };
        setSupplierForProducts(updatedProv);
        onUpdateSuppliers(suppliers.map(s => s.id === supplierForProducts.id ? updatedProv : s));
      }
    } catch (err: any) {
      alert(err.message || 'Error al desasociar producto.');
    }
  };

  const handleSetPrincipalProduct = async (idProductoProveedor?: number) => {
    if (!supplierForProducts) return;
    try {
      if (idProductoProveedor) {
        await proveedoresService.definirPrincipal(idProductoProveedor);
        const idProv = supplierForProducts.id_proveedor || Number(supplierForProducts.id);
        const fullProv = await proveedoresService.getById(idProv);
        setSupplierForProducts(fullProv);
        onUpdateSuppliers(suppliers.map(s => s.id === supplierForProducts.id ? fullProv : s));
      }
    } catch (err: any) {
      alert(err.message || 'Error al definir proveedor principal.');
    }
  };

  // PV07: Plazos de entrega e historial
  const handleOpenPlazosModal = async (supplier: Supplier) => {
    setSupplierForPlazos(supplier);
    setPlazosError('');
    setNuevoPlazoDias(supplier.plazo_entrega_dias || 3);
    setNuevoPlazoMotivo('');
    setPlazosLoading(true);
    const idProv = supplier.id_proveedor || Number(supplier.id);
    if (!isNaN(idProv) && idProv > 0) {
      try {
        const list = await proveedoresService.getPlazos(idProv);
        setPlazosList(list);
      } catch (err) {
        setPlazosList([]);
      } finally {
        setPlazosLoading(false);
      }
    } else {
      setPlazosList([
        {
          id_plazo: 1,
          plazo_entrega_dias: supplier.plazo_entrega_dias || 3,
          motivo: 'Plazo inicial acordado en el alta de proveedor',
          fecha_registro: supplier.fecha_alta || new Date().toISOString().split('T')[0]
        }
      ]);
      setPlazosLoading(false);
    }
  };

  const handleRegisterPlazo = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!supplierForPlazos) return;
    const idProv = supplierForPlazos.id_proveedor || Number(supplierForPlazos.id);
    try {
      if (!isNaN(idProv) && idProv > 0) {
        await proveedoresService.registrarPlazo(idProv, nuevoPlazoDias, nuevoPlazoMotivo);
        const list = await proveedoresService.getPlazos(idProv);
        setPlazosList(list);
        const fullProv = await proveedoresService.getById(idProv);
        setSupplierForPlazos(fullProv);
        onUpdateSuppliers(suppliers.map(s => s.id === supplierForPlazos.id ? fullProv : s));
      } else {
        const newRecord = {
          id_plazo: Date.now(),
          plazo_entrega_dias: nuevoPlazoDias,
          motivo: nuevoPlazoMotivo || 'Actualización de plazo acordado',
          fecha_registro: new Date().toISOString().split('T')[0]
        };
        setPlazosList([newRecord, ...plazosList]);
        const updatedProv = { ...supplierForPlazos, plazo_entrega_dias: nuevoPlazoDias };
        setSupplierForPlazos(updatedProv);
        onUpdateSuppliers(suppliers.map(s => s.id === supplierForPlazos.id ? updatedProv : s));
      }
      setNuevoPlazoMotivo('');
    } catch (err: any) {
      setPlazosError(err.message || 'Error al registrar plazo de entrega.');
    }
  };

  // --- ACCIONES DE NUEVA COMPRA (INGRESO) ---
  const handleOpenAddPurchase = () => {
    setPurchaseSupplierId(suppliers[0]?.id || '');
    setPurchaseInvoiceNumber('');
    setPurchaseNotes('');
    setPurchaseItems([]);
    setPurchaseError('');
    
    // Resetear el cargador de items
    if (products.length > 0) {
      const firstProd = products[0];
      setCurrentSelectedProductId(firstProd.id);
      setCurrentSelectedProductQuantity(1);
      setCurrentSelectedProductCost(firstProd.cost);
    }
    
    setShowPurchaseForm(true);
  };

  const handleProductChangeInForm = (productId: string) => {
    setCurrentSelectedProductId(productId);
    const prod = products.find(p => p.id === productId);
    if (prod) {
      setCurrentSelectedProductCost(prod.cost);
    }
  };

  const handleAddPurchaseItem = () => {
    if (!currentSelectedProductId) return;
    
    const prod = products.find(p => p.id === currentSelectedProductId);
    if (!prod) return;

    if (currentSelectedProductQuantity <= 0) {
      alert('La cantidad de unidades ingresada debe ser mayor que cero.');
      return;
    }

    // Si ya existe el producto en el carrito, sumarle cantidad
    const existingIndex = purchaseItems.findIndex(item => item.productId === currentSelectedProductId);
    if (existingIndex >= 0) {
      const updatedItems = [...purchaseItems];
      updatedItems[existingIndex].quantity += currentSelectedProductQuantity;
      updatedItems[existingIndex].total = updatedItems[existingIndex].quantity * updatedItems[existingIndex].costPrice;
      setPurchaseItems(updatedItems);
    } else {
      const newItem: PurchaseItem = {
        productId: currentSelectedProductId,
        productName: prod.name,
        quantity: currentSelectedProductQuantity,
        costPrice: currentSelectedProductCost,
        total: currentSelectedProductQuantity * currentSelectedProductCost
      };
      setPurchaseItems([...purchaseItems, newItem]);
    }

    // Reset cantidad
    setCurrentSelectedProductQuantity(1);
  };

  const handleRemovePurchaseItem = (index: number) => {
    setPurchaseItems(purchaseItems.filter((_, i) => i !== index));
  };

  const handleSavePurchase = async (e: React.FormEvent) => {
    e.preventDefault();
    setPurchaseError('');

    if (!purchaseSupplierId) {
      setPurchaseError('Debe seleccionar un proveedor para registrar el ingreso.');
      return;
    }

    if (purchaseItems.length === 0) {
      setPurchaseError('Debe agregar al menos un producto a la lista de compra para poder registrar el ingreso de mercadería.');
      return;
    }

    const supplier = suppliers.find(s => s.id === purchaseSupplierId);
    if (!supplier) return;

    const totalCost = purchaseItems.reduce((acc, item) => acc + item.total, 0);

    try {
      let createdPurchase: Purchase;
      const idProv = supplier.id_proveedor || Number(purchaseSupplierId);

      if (!isNaN(idProv) && idProv > 0) {
        // Enviar a la API de Laravel (Módulo Compras C01)
        createdPurchase = await comprasService.create({
          id_proveedor: idProv,
          numero_comprobante: purchaseInvoiceNumber.trim() || undefined,
          items: purchaseItems.map(item => ({
            id_producto: item.id_producto || Number(item.productId),
            cantidad: item.quantity,
            precio_unitario: item.costPrice,
          })),
        });
      } else {
        createdPurchase = {
          id: 'PUR-' + Date.now(),
          supplierId: purchaseSupplierId,
          supplierName: supplier.name,
          items: purchaseItems,
          total: totalCost,
          date: new Date().toISOString().split('T')[0] + ' ' + new Date().toTimeString().split(' ')[0].substring(0, 5),
          invoiceNumber: purchaseInvoiceNumber.trim() || undefined,
          status: 'Recibido',
          notes: purchaseNotes.trim() || undefined
        };
      }

      // Impacto en inventario en memoria
      const updatedProductsList = products.map(p => {
        const purchaseItem = purchaseItems.find(item => item.productId === p.id || item.id_producto === p.id_producto);
        if (purchaseItem) {
          return {
            ...p,
            stock: p.stock + purchaseItem.quantity,
            cost: purchaseItem.costPrice
          };
        }
        return p;
      });

      // Guardar colecciones
      onUpdateProducts(updatedProductsList);
      onUpdatePurchases([createdPurchase, ...purchases]);
      setShowPurchaseForm(false);
      setPurchaseItems([]);
    } catch (err: any) {
      setPurchaseError(err.message || 'Error al registrar la compra en la API Laravel.');
    }
  };

  return (
    <div className="space-y-8 animate-in fade-in duration-300">
      
      {/* HEADER PRINCIPAL */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b-4 border-[#DEE2E6] pb-4">
        <div>
          <h1 className="text-4xl font-extrabold text-[#212529] tracking-tight flex items-center gap-3">
            <Users className="w-10 h-10 text-[#0D6EFD]" />
            <span>Fichas, Proveedores y Compras</span>
          </h1>
          <p className="text-lg text-neutral-700 font-semibold mt-1">
            Gestión de clientes y tarifas diferenciadas, base fiscal de proveedores y compras con ingreso automático al inventario.
          </p>
        </div>
        
        {/* BOTÓN REGISTRO RÁPIDO SEGÚN PESTAÑA */}
        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => window.location.reload()}
            className="px-4 py-3 bg-white hover:bg-neutral-100 text-neutral-700 font-extrabold text-base rounded-xl flex items-center gap-2 cursor-pointer shadow-sm border border-neutral-300 transition-all"
            title="Refrescar datos y listados"
          >
            <RefreshCw className="w-5 h-5 text-blue-600" />
            <span>Refrescar</span>
          </button>

          {activeTab === 'CLIENTES' && !showCustomerForm && (
            <button
              onClick={handleOpenAddCustomer}
              className="px-5 py-3 bg-[#0D6EFD] hover:bg-[#0b5ed7] text-white font-extrabold text-lg rounded-xl flex items-center gap-2 cursor-pointer shadow-md"
            >
              <Plus className="w-6 h-6" />
              <span>Nueva Ficha Cliente</span>
            </button>
          )}

          {activeTab === 'PROVEEDORES' && !showSupplierForm && (
            <button
              onClick={handleOpenAddSupplier}
              className="px-5 py-3 bg-[#198754] hover:bg-[#146c43] text-white font-extrabold text-lg rounded-xl flex items-center gap-2 cursor-pointer shadow-md"
            >
              <Plus className="w-6 h-6" />
              <span>Nuevo Proveedor</span>
            </button>
          )}

          {activeTab === 'COMPRAS' && !showPurchaseForm && (
            <button
              onClick={handleOpenAddPurchase}
              className="px-5 py-3 bg-[#820dfd] hover:bg-[#6c0bce] text-white font-extrabold text-lg rounded-xl flex items-center gap-2 cursor-pointer shadow-md"
              disabled={suppliers.length === 0}
              title={suppliers.length === 0 ? "Primero registre un proveedor en el sistema" : ""}
            >
              <ShoppingCart className="w-6 h-6" />
              <span>Registrar Compra (Ingreso)</span>
            </button>
          )}
        </div>
      </div>

      {/* TABS DE SELECCIÓN */}
      <div className="flex border-b-2 border-[#DEE2E6] gap-2 p-1 bg-neutral-100 rounded-xl">
        <button
          onClick={() => { setActiveTab('CLIENTES'); setSearchQuery(''); }}
          className={`flex-1 py-4 px-6 rounded-lg font-black text-lg text-center flex items-center justify-center gap-2 border-2 cursor-pointer transition-all ${
            activeTab === 'CLIENTES'
              ? 'bg-[#0D6EFD] border-[#0c5ed7] text-white shadow-md'
              : 'bg-white hover:bg-neutral-50 text-[#212529] border-transparent'
          }`}
        >
          <Users className="w-5 h-5" />
          <span>Fichas de Clientes ({customers.length})</span>
        </button>

        <button
          onClick={() => { setActiveTab('PROVEEDORES'); setSearchQuery(''); }}
          className={`flex-1 py-4 px-6 rounded-lg font-black text-lg text-center flex items-center justify-center gap-2 border-2 cursor-pointer transition-all ${
            activeTab === 'PROVEEDORES'
              ? 'bg-[#198754] border-[#146c43] text-white shadow-md'
              : 'bg-white hover:bg-neutral-50 text-[#212529] border-transparent'
          }`}
        >
          <Briefcase className="w-5 h-5" />
          <span>Proveedores / Fábricas ({suppliers.length})</span>
        </button>

        <button
          onClick={() => { setActiveTab('COMPRAS'); setSearchQuery(''); }}
          className={`flex-1 py-4 px-6 rounded-lg font-black text-lg text-center flex items-center justify-center gap-2 border-2 cursor-pointer transition-all ${
            activeTab === 'COMPRAS'
              ? 'bg-[#820dfd] border-[#6c0bce] text-white shadow-md'
              : 'bg-white hover:bg-neutral-50 text-[#212529] border-transparent'
          }`}
        >
          <ShoppingCart className="w-5 h-5" />
          <span>Ingreso de Compras ({purchases.length})</span>
        </button>
      </div>

      {/* --- FORMULARIO DE CLIENTES --- */}
      {showCustomerForm && activeTab === 'CLIENTES' && (
        <div className="bg-white border-4 border-[#0D6EFD] rounded-2xl p-6 space-y-6 shadow-lg animate-in slide-in-from-top-4 duration-300">
          <div className="flex items-center justify-between border-b pb-4">
            <h3 className="text-2xl font-black text-[#212529] flex items-center gap-2">
              <Edit className="w-6 h-6 text-[#0D6EFD]" />
              <span>{editingCustomer ? 'Editar Ficha Fiscal del Cliente' : 'Registrar Nueva Ficha Cliente'}</span>
            </h3>
            <button onClick={() => setShowCustomerForm(false)} className="p-2 bg-neutral-100 text-neutral-700 hover:bg-neutral-200 rounded-lg cursor-pointer">
              <X className="w-6 h-6" />
            </button>
          </div>

          {customerError && (
            <div className="p-4 bg-red-100 border-2 border-[#DC3545] text-[#DC3545] rounded-xl font-bold flex items-center gap-2">
              <AlertTriangle className="w-5 h-5" />
              <span>{customerError}</span>
            </div>
          )}

          <form onSubmit={handleSaveCustomer} className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Razón Social o Nombre Comercial *</label>
              <input
                type="text"
                required
                placeholder="Ej. Minimercado San Juan"
                value={cName}
                onChange={(e) => setCName(e.target.value)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-[#0D6EFD] focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">CUIT / Identificación Fiscal *</label>
              <input
                type="text"
                required
                placeholder="Ej. 20-12345678-9"
                value={cCuit}
                onChange={(e) => setCCuit(e.target.value)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-[#0D6EFD] focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Dirección del Local Comercial *</label>
              <input
                type="text"
                required
                placeholder="Ej. Av. Rivadavia 4520, CABA"
                value={cAddress}
                onChange={(e) => setCAddress(e.target.value)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-[#0D6EFD] focus:outline-none"
              />
              <p className="text-xs text-neutral-500 font-bold mt-1">Sirve para el cálculo de hoja de ruta y geolocalización.</p>
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Teléfono de Contacto *</label>
              <input
                type="text"
                required
                placeholder="Ej. 11-4433-2211"
                value={cPhone}
                onChange={(e) => setCPhone(e.target.value)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-[#0D6EFD] focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Zona Geográfica de Reparto *</label>
              <select
                value={cZone}
                onChange={(e) => setCZone(e.target.value)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-[#0D6EFD] focus:outline-none"
              >
                {zones.filter(z => z !== 'Todas').map(z => (
                  <option key={z} value={z}>{z}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Tarifa Diferencial Asignada *</label>
              <select
                value={cPriceList}
                onChange={(e) => setCPriceList(e.target.value)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:border-[#0D6EFD] focus:outline-none"
              >
                {priceLists.map(list => (
                  <option key={list.id} value={list.id}>{list.name} ({list.discountPercentage}% desc.)</option>
                ))}
              </select>
            </div>

            <div className="md:col-span-2 flex justify-end gap-3 pt-4 border-t">
              <button
                type="button"
                onClick={() => { setShowCustomerForm(false); setEditingCustomer(null); }}
                className="px-6 py-3 bg-neutral-100 hover:bg-neutral-200 border-2 border-neutral-300 text-neutral-800 font-bold rounded-xl cursor-pointer"
              >
                Cancelar
              </button>
              <button
                type="submit"
                className="px-8 py-3 bg-[#0D6EFD] hover:bg-[#0b5ed7] text-white font-extrabold text-lg rounded-xl cursor-pointer shadow"
              >
                {editingCustomer ? 'Guardar Cambios' : 'Registrar Ficha'}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* --- FORMULARIO DE PROVEEDORES --- */}
      {showSupplierForm && activeTab === 'PROVEEDORES' && (
        <div className="bg-white border-4 border-[#198754] rounded-2xl p-6 space-y-6 shadow-lg animate-in slide-in-from-top-4 duration-300">
          <div className="flex items-center justify-between border-b pb-4">
            <h3 className="text-2xl font-black text-[#212529] flex items-center gap-2">
              <Edit className="w-6 h-6 text-[#198754]" />
              <span>{editingSupplier ? 'Editar Ficha de Proveedor' : 'Registrar Nuevo Proveedor / Fábrica'}</span>
            </h3>
            <button onClick={() => setShowSupplierForm(false)} className="p-2 bg-neutral-100 text-neutral-700 hover:bg-neutral-200 rounded-lg cursor-pointer">
              <X className="w-6 h-6" />
            </button>
          </div>

          {supplierError && (
            <div className="p-4 bg-red-100 border-2 border-[#DC3545] text-[#DC3545] rounded-xl font-bold flex items-center gap-2">
              <AlertTriangle className="w-5 h-5" />
              <span>{supplierError}</span>
            </div>
          )}

          <form onSubmit={handleSaveSupplier} className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Nombre Comercial o Fábrica *</label>
              <input
                type="text"
                required
                placeholder="Ej. Unilever Argentina"
                value={sName}
                onChange={(e) => setSName(e.target.value)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">CUIT Fiscal *</label>
              <input
                type="text"
                required
                placeholder="Ej. 30-11223344-5"
                value={sCuit}
                onChange={(e) => setSCuit(e.target.value)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Teléfono Comercial *</label>
              <input
                type="text"
                required
                placeholder="Ej. 0800-444-1234"
                value={sPhone}
                onChange={(e) => setSPhone(e.target.value)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Correo Electrónico (Pedidos)</label>
              <input
                type="email"
                placeholder="Ej. ventas@fabrica.com"
                value={sEmail}
                onChange={(e) => setSEmail(e.target.value)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Dirección de Planta o Depósito *</label>
              <input
                type="text"
                required
                placeholder="Ej. Ruta 8 Km 60, Pilar"
                value={sAddress}
                onChange={(e) => setSAddress(e.target.value)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Rubro / Categoría de Suministro *</label>
              <input
                type="text"
                required
                placeholder="Ej. Bebidas, Almacén, Lácteos"
                value={sCategory}
                onChange={(e) => setSCategory(e.target.value)}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-lg font-bold text-neutral-800 mb-1">Plazo de Entrega Habitual (Días)</label>
              <input
                type="number"
                min="0"
                placeholder="Ej. 3 (días)"
                value={sPlazoDias}
                onChange={(e) => setSPlazoDias(e.target.value === '' ? '' : Number(e.target.value))}
                className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-lg font-bold focus:outline-none"
              />
              <p className="text-xs text-neutral-500 font-bold mt-1">Tiempo promedio de entrega acordado con la fábrica.</p>
            </div>

            {editingSupplier && (
              <div className="md:col-span-2 p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-1.5 text-xs">
                <div className="font-extrabold text-slate-700 flex items-center gap-1.5 mb-1">
                  <Clock className="w-4 h-4 text-emerald-600" />
                  <span className="text-sm">Auditoría y Registro del Proveedor</span>
                </div>
                <div className="flex flex-wrap items-center justify-between text-slate-600 font-medium">
                  <span>
                    <strong className="text-slate-700">Fecha y Hora de Alta:</strong> {formatDateTime(editingSupplier.fecha_alta, 'Registrada en alta')}
                  </span>
                  <span>
                    <strong className="text-slate-700">Cargado por:</strong> {editingSupplier.usuario_carga_nombre || (editingSupplier.id_usuario_carga ? `Usuario #${editingSupplier.id_usuario_carga}` : 'Don Alberto')}
                  </span>
                </div>
                {editingSupplier.fecha_modificacion && (
                  <div className="flex flex-wrap items-center justify-between text-slate-600 font-medium pt-1 border-t border-slate-200/60">
                    <span>
                      <strong className="text-slate-700">Última Modificación:</strong> {formatDateTime(editingSupplier.fecha_modificacion)}
                    </span>
                    <span>
                      <strong className="text-slate-700">Modificado por:</strong> {editingSupplier.usuario_modificacion_nombre || 'Don Alberto'}
                    </span>
                  </div>
                )}
                {editingSupplier.fecha_desactivacion && (
                  <div className="text-rose-600 font-bold pt-1 border-t border-slate-200/60">
                    <span>Desactivado el: {formatDateTime(editingSupplier.fecha_desactivacion)}</span>
                  </div>
                )}
              </div>
            )}

            <div className="md:col-span-2 flex justify-end gap-3 pt-4 border-t">
              <button
                type="button"
                onClick={() => { setShowSupplierForm(false); setEditingSupplier(null); }}
                className="px-6 py-3 bg-neutral-100 hover:bg-neutral-200 border-2 border-neutral-300 text-neutral-800 font-bold rounded-xl cursor-pointer"
              >
                Cancelar
              </button>
              <button
                type="submit"
                className="px-8 py-3 bg-[#198754] hover:bg-[#146c43] text-white font-extrabold text-lg rounded-xl cursor-pointer shadow"
              >
                {editingSupplier ? 'Guardar Cambios' : 'Registrar Proveedor'}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* --- FORMULARIO DE INGRESO DE COMPRAS --- */}
      {showPurchaseForm && activeTab === 'COMPRAS' && (
        <div className="bg-white border-4 border-[#820dfd] rounded-2xl p-6 space-y-6 shadow-lg animate-in slide-in-from-top-4 duration-300">
          <div className="flex items-center justify-between border-b pb-4">
            <h3 className="text-2xl font-black text-[#212529] flex items-center gap-2">
              <ShoppingCart className="w-6 h-6 text-[#820dfd]" />
              <span>Registrar Compra / Recepción de Stock</span>
            </h3>
            <button onClick={() => setShowPurchaseForm(false)} className="p-2 bg-neutral-100 text-neutral-700 hover:bg-neutral-200 rounded-lg cursor-pointer">
              <X className="w-6 h-6" />
            </button>
          </div>

          {purchaseError && (
            <div className="p-4 bg-red-100 border-2 border-[#DC3545] text-[#DC3545] rounded-xl font-bold flex items-center gap-2">
              <AlertTriangle className="w-5 h-5" />
              <span>{purchaseError}</span>
            </div>
          )}

          <form onSubmit={handleSavePurchase} className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label className="block text-base font-bold text-neutral-800 mb-1">Seleccionar Proveedor *</label>
                <select
                  value={purchaseSupplierId}
                  onChange={(e) => setPurchaseSupplierId(e.target.value)}
                  className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-base font-bold focus:outline-none"
                >
                  {suppliers.map(s => (
                    <option key={s.id} value={s.id}>{s.name} ({s.category})</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-base font-bold text-neutral-800 mb-1">Nro Factura / Remito del Proveedor</label>
                <input
                  type="text"
                  placeholder="Ej. 0001-00045129"
                  value={purchaseInvoiceNumber}
                  onChange={(e) => setPurchaseInvoiceNumber(e.target.value)}
                  className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-base font-bold focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-base font-bold text-neutral-800 mb-1">Comentarios / Observaciones</label>
                <input
                  type="text"
                  placeholder="Ej. Ingresó todo en buen estado"
                  value={purchaseNotes}
                  onChange={(e) => setPurchaseNotes(e.target.value)}
                  className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-base font-bold focus:outline-none"
                />
              </div>
            </div>

            {/* BUSCADOR DE PRODUCTOS PARA AGREGAR A LA COMPRA */}
            <div className="bg-neutral-50 p-5 rounded-2xl border-2 border-[#DEE2E6] space-y-4">
              <div className="font-extrabold text-base text-[#212529] flex items-center gap-2">
                <Package className="w-5 h-5 text-[#820dfd]" />
                <span>Agregar Productos a la Recepción</span>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div className="md:col-span-5">
                  <label className="block text-sm font-bold text-neutral-700 mb-1">Seleccionar Producto del Catálogo:</label>
                  <select
                    value={currentSelectedProductId}
                    onChange={(e) => handleProductChangeInForm(e.target.value)}
                    className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-sm font-bold focus:outline-none bg-white"
                  >
                    {products.map(p => (
                      <option key={p.id} value={p.id}>[{p.code}] {p.name} (Stock: {p.stock})</option>
                    ))}
                  </select>
                </div>

                <div className="md:col-span-3">
                  <label className="block text-sm font-bold text-neutral-700 mb-1">Costo Neto Unitario ($):</label>
                  <input
                    type="number"
                    min="0"
                    step="any"
                    value={currentSelectedProductCost}
                    onChange={(e) => setCurrentSelectedProductCost(Number(e.target.value))}
                    className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-sm font-bold focus:outline-none bg-white"
                  />
                </div>

                <div className="md:col-span-2">
                  <label className="block text-sm font-bold text-neutral-700 mb-1">Cantidad a Comprar:</label>
                  <input
                    type="number"
                    min="1"
                    value={currentSelectedProductQuantity}
                    onChange={(e) => setCurrentSelectedProductQuantity(Math.max(1, Number(e.target.value)))}
                    className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-sm font-bold focus:outline-none bg-white"
                  />
                </div>

                <div className="md:col-span-2">
                  <button
                    type="button"
                    onClick={handleAddPurchaseItem}
                    className="w-full py-3 bg-[#820dfd] hover:bg-[#6c0bce] text-white font-bold rounded-xl flex items-center justify-center gap-2 cursor-pointer shadow-sm text-sm"
                  >
                    <PlusCircle className="w-5 h-5" />
                    <span>Agregar</span>
                  </button>
                </div>
              </div>
            </div>

            {/* DETALLE DEL CARRITO DE COMPRA */}
            <div className="border-2 rounded-xl overflow-hidden bg-white">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-neutral-100 border-b">
                    <th className="p-3 font-bold text-sm text-[#212529]">Producto</th>
                    <th className="p-3 font-bold text-sm text-[#212529] text-right">Costo Unitario</th>
                    <th className="p-3 font-bold text-sm text-[#212529] text-center">Cantidad</th>
                    <th className="p-3 font-bold text-sm text-[#212529] text-right">Subtotal</th>
                    <th className="p-3 font-bold text-sm text-[#212529] text-center w-20">Quitar</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200">
                  {purchaseItems.length === 0 ? (
                    <tr>
                      <td colSpan={5} className="p-8 text-center text-neutral-500 font-bold text-base">
                        Ningún ítem agregado a la orden de compra todavía. Use el cargador de arriba.
                      </td>
                    </tr>
                  ) : (
                    purchaseItems.map((item, index) => (
                      <tr key={index} className="font-semibold text-base">
                        <td className="p-3 text-neutral-800">{item.productName}</td>
                        <td className="p-3 text-right text-neutral-700 font-mono">${item.costPrice.toFixed(2)}</td>
                        <td className="p-3 text-center text-[#212529] font-bold">{item.quantity} units</td>
                        <td className="p-3 text-right text-black font-black font-mono">${item.total.toLocaleString('es-AR')}</td>
                        <td className="p-3 text-center">
                          <button
                            type="button"
                            onClick={() => handleRemovePurchaseItem(index)}
                            className="p-1.5 text-red-500 hover:bg-red-50 rounded-lg cursor-pointer"
                            aria-label="Remover ítem"
                          >
                            <Trash2 className="w-4 h-4 mx-auto" />
                          </button>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>

              {purchaseItems.length > 0 && (
                <div className="bg-neutral-100 p-4 flex items-center justify-between border-t font-black text-xl">
                  <span>TOTAL COMPRA:</span>
                  <span className="text-[#820dfd] font-mono">
                    ${purchaseItems.reduce((acc, i) => acc + i.total, 0).toLocaleString('es-AR')}
                  </span>
                </div>
              )}
            </div>

            <div className="flex justify-end gap-3 pt-4 border-t">
              <button
                type="button"
                onClick={() => { setShowPurchaseForm(false); setPurchaseItems([]); }}
                className="px-6 py-3 bg-neutral-100 hover:bg-neutral-200 border-2 border-neutral-300 text-neutral-800 font-bold rounded-xl cursor-pointer"
              >
                Cancelar
              </button>
              <button
                type="submit"
                className="px-8 py-3 bg-[#198754] hover:bg-[#146c43] text-white font-extrabold text-lg rounded-xl cursor-pointer shadow flex items-center gap-2"
                disabled={purchaseItems.length === 0}
              >
                <Check className="w-5 h-5" />
                <span>Confirmar Recepción e Incrementar Stock</span>
              </button>
            </div>
          </form>
        </div>
      )}

      {/* --- PANEL DE BUSCADORES DE LA PESTAÑA ACTIVA --- */}
      {!showCustomerForm && !showSupplierForm && !showPurchaseForm && (
        <div className="bg-[#E9ECEF] border-2 border-[#DEE2E6] rounded-2xl p-5 space-y-4">
          <div className="flex items-center gap-2 font-black text-xl text-[#212529]">
            <Search className="w-5 h-5" />
            <span>
              {activeTab === 'CLIENTES' && 'Buscador y Ruteo de Clientes'}
              {activeTab === 'PROVEEDORES' && 'Buscador de Proveedores y Fábricas'}
              {activeTab === 'COMPRAS' && 'Buscador de Compras e Ingresos'}
            </span>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div className={activeTab === 'PROVEEDORES' ? "md:col-span-6" : activeTab === 'CLIENTES' ? "md:col-span-8" : "md:col-span-12"}>
              <label className="block text-base font-bold text-neutral-700 mb-1" htmlFor="tab-search">
                {activeTab === 'CLIENTES' && 'Buscar por Razón Social, CUIT o Dirección:'}
                {activeTab === 'PROVEEDORES' && 'Buscar por Nombre, CUIT o Rubro:'}
                {activeTab === 'COMPRAS' && 'Buscar por Nombre de Proveedor o Nro de Remitente:'}
              </label>
              <div className="relative">
                <span className="absolute left-3.5 top-3.5 text-neutral-500">
                  <Search className="w-5 h-5" />
                </span>
                <input
                  id="tab-search"
                  type="text"
                  placeholder={
                    activeTab === 'CLIENTES' ? "Escriba nombre, CUIT o dirección de comercio..." :
                    activeTab === 'PROVEEDORES' ? "Escriba nombre de la fábrica, CUIT o categoría..." :
                    "Escriba nombre del proveedor o nro de factura..."
                  }
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 pl-11 text-base font-bold text-[#212529] bg-white focus:border-[#0D6EFD] focus:outline-none"
                />
              </div>
            </div>

            {activeTab === 'CLIENTES' && (
              <div className="md:col-span-4">
                <label className="block text-base font-bold text-neutral-700 mb-1" htmlFor="zone-filter-select">Filtrar por Zona de Reparto:</label>
                <select
                  id="zone-filter-select"
                  value={zoneFilter}
                  onChange={(e) => setZoneFilter(e.target.value)}
                  className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-base font-bold text-[#212529] bg-white focus:outline-none"
                >
                  {zones.map(z => (
                    <option key={z} value={z}>{z === 'Todas' ? 'Todas las Zonas' : `Zona ${z}`}</option>
                  ))}
                </select>
              </div>
            )}

            {activeTab === 'PROVEEDORES' && (
              <>
                <div className="md:col-span-3">
                  <label className="block text-base font-bold text-neutral-700 mb-1" htmlFor="category-filter-select">Filtrar por Rubro:</label>
                  <select
                    id="category-filter-select"
                    value={categoryFilter}
                    onChange={(e) => setCategoryFilter(e.target.value)}
                    className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-base font-bold text-[#212529] bg-white focus:outline-none"
                  >
                    {supplierCategories.map(cat => (
                      <option key={cat} value={cat}>{cat === 'Todas' ? 'Todos los Rubros' : cat}</option>
                    ))}
                  </select>
                </div>

                <div className="md:col-span-3">
                  <label className="block text-base font-bold text-neutral-700 mb-1" htmlFor="status-filter-select">Filtrar por Estado:</label>
                  <select
                    id="status-filter-select"
                    value={supplierStatusFilter}
                    onChange={(e) => setSupplierStatusFilter(e.target.value as any)}
                    className="w-full border-2 border-[#DEE2E6] rounded-xl p-3 text-base font-bold text-[#212529] bg-white focus:outline-none"
                  >
                    <option value="Todos">Todos los Estados</option>
                    <option value="Activos">Solo Activos (Operativos)</option>
                    <option value="Inactivos">Solo Desactivados</option>
                  </select>
                </div>
              </>
            )}
          </div>
        </div>
      )}

      {/* --- RENDER PESTAÑA: CLIENTES --- */}
      {activeTab === 'CLIENTES' && !showCustomerForm && (
        <div className="bg-white border-2 border-[#DEE2E6] rounded-2xl p-6 space-y-4">
          <div className="flex items-center justify-between border-b pb-4">
            <h2 className="text-2xl font-extrabold text-[#212529]">Base de Clientes Autorizados</h2>
            <span className="text-sm font-bold text-neutral-600 bg-neutral-100 border px-3 py-1 rounded-lg">
              Mostrando <strong>{filteredCustomers.length}</strong> de {customers.length} fichas
            </span>
          </div>

          {filteredCustomers.length === 0 ? (
            <div className="text-center py-12 bg-neutral-50 rounded-2xl border-2 border-dashed border-neutral-300">
              <Users className="w-16 h-16 text-neutral-400 mx-auto mb-4" />
              <p className="text-xl font-extrabold text-neutral-700">No se encontraron fichas de clientes.</p>
              <p className="text-sm text-neutral-500 font-bold mt-1">Modifique los filtros o registre un nuevo comercio.</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {filteredCustomers.map(customer => {
                const plist = priceLists.find(l => l.id === customer.priceListId);
                return (
                  <div key={customer.id} className="border-2 border-[#DEE2E6] hover:border-[#0D6EFD] rounded-2xl p-5 flex flex-col justify-between gap-4 transition-all hover:shadow-md bg-white">
                    <div className="space-y-3">
                      <div className="flex justify-between items-start gap-2">
                        <h4 className="text-xl font-black text-[#212529] leading-tight">{customer.name}</h4>
                        <span className="inline-block bg-[#0D6EFD] text-white font-extrabold text-xs px-2.5 py-1 rounded-full shrink-0">
                          Zona {customer.zone}
                        </span>
                      </div>

                      <div className="space-y-1 bg-neutral-50 p-3 rounded-xl border border-neutral-200">
                        <p className="text-xs font-bold text-neutral-500">IDENTIFICACIÓN FISCAL:</p>
                        <p className="text-sm font-extrabold text-[#212529] font-mono">{customer.cuit}</p>
                      </div>

                      <div className="space-y-1 font-semibold text-sm">
                        <div className="flex items-center gap-2 text-neutral-700">
                          <MapPin className="w-4 h-4 text-[#0D6EFD] shrink-0" />
                          <span>{customer.address}</span>
                        </div>
                        <div className="flex items-center gap-2 text-neutral-700">
                          <Phone className="w-4 h-4 text-[#198754] shrink-0" />
                          <span>{customer.phone}</span>
                        </div>
                      </div>

                      <div className="bg-[#0D6EFD]/5 border border-[#0D6EFD]/20 p-3 rounded-xl flex items-center justify-between">
                        <div>
                          <p className="text-[10px] font-black text-[#0D6EFD]">TARIFA ASIGNADA:</p>
                          <p className="text-sm font-black text-[#212529]">{plist ? plist.name : 'Tarifa Normal'}</p>
                        </div>
                        <span className="text-base font-black text-[#198754] bg-white border px-2 py-0.5 rounded-lg">
                          -{plist ? plist.discountPercentage : 0}%
                        </span>
                      </div>
                    </div>

                    <div className="flex gap-2 border-t pt-3">
                      <button
                        onClick={() => handleOpenEditCustomer(customer)}
                        className="flex-1 py-2 bg-neutral-100 hover:bg-neutral-200 border border-neutral-300 text-neutral-800 font-bold text-sm rounded-xl flex items-center justify-center gap-1 cursor-pointer transition-colors"
                      >
                        <Edit className="w-4 h-4" />
                        <span>Editar Ficha</span>
                      </button>

                      <button
                        onClick={() => handleDeleteCustomer(customer.id, customer.name)}
                        className="p-2 bg-red-50 hover:bg-red-500 text-[#DC3545] hover:text-white border border-red-200 rounded-xl transition-colors cursor-pointer"
                        title="Eliminar ficha"
                        aria-label="Eliminar"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      )}

      {/* --- RENDER PESTAÑA: PROVEEDORES --- */}
      {activeTab === 'PROVEEDORES' && !showSupplierForm && (
        <div className="bg-white border-2 border-[#DEE2E6] rounded-2xl p-6 space-y-4">
          <div className="flex items-center justify-between border-b pb-4">
            <h2 className="text-2xl font-extrabold text-[#212529]">Registro de Fábricas y Proveedores</h2>
            <span className="text-sm font-bold text-neutral-600 bg-neutral-100 border px-3 py-1 rounded-lg">
              Mostrando <strong>{filteredSuppliers.length}</strong> de {suppliers.length} fábricas
            </span>
          </div>

          {filteredSuppliers.length === 0 ? (
            <div className="text-center py-12 bg-neutral-50 rounded-2xl border-2 border-dashed border-neutral-300">
              <Briefcase className="w-16 h-16 text-neutral-400 mx-auto mb-4" />
              <p className="text-xl font-extrabold text-neutral-700">No se encontraron fábricas registradas.</p>
              <p className="text-sm text-neutral-500 font-bold mt-1">Pruebe agregando un nuevo proveedor para compras directas.</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {filteredSuppliers.map(supplier => {
                const isActivo = supplier.estado === 'activo' || !supplier.estado;
                const assocCount = supplier.productos_asociados?.length || 0;

                return (
                  <div 
                    key={supplier.id} 
                    className={`border-2 rounded-2xl p-5 flex flex-col justify-between gap-4 transition-all hover:shadow-md bg-white ${
                      isActivo ? 'border-[#DEE2E6] hover:border-[#198754]' : 'border-slate-300 bg-slate-50/70 opacity-80'
                    }`}
                  >
                    <div className="space-y-3">
                      <div className="flex justify-between items-start gap-2">
                        <div>
                          <h4 className="text-xl font-black text-[#212529] leading-tight">{supplier.name}</h4>
                          <span className="text-[11px] font-mono text-neutral-400 font-bold block mt-0.5">
                            ID: {supplier.id_proveedor || supplier.id}
                          </span>
                        </div>
                        
                        <div className="flex flex-col items-end gap-1.5 shrink-0">
                          <span className="inline-block bg-[#198754] text-white font-extrabold text-xs px-2.5 py-0.5 rounded-full uppercase">
                            {supplier.category}
                          </span>
                          <span className={`inline-flex items-center gap-1 font-extrabold text-[11px] px-2 py-0.5 rounded-full ${
                            isActivo 
                              ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' 
                              : 'bg-red-100 text-red-700 border border-red-200'
                          }`}>
                            {isActivo ? (
                              <>
                                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Activo
                              </>
                            ) : (
                              <>
                                <span className="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                Desactivado
                              </>
                            )}
                          </span>
                        </div>
                      </div>

                    <div className="space-y-1 bg-neutral-50 p-3 rounded-xl border border-neutral-200 font-semibold">
                      <p className="text-xs font-bold text-neutral-500">CUIT PROVEEDOR:</p>
                      <p className="text-sm font-extrabold text-[#212529] font-mono">{supplier.cuit}</p>
                    </div>

                    <div className="space-y-1.5 font-semibold text-sm">
                      <div className="flex items-center gap-2 text-neutral-700">
                        <MapPin className="w-4 h-4 text-[#198754] shrink-0" />
                        <span>{supplier.address}</span>
                      </div>
                      <div className="flex items-center gap-2 text-neutral-700">
                        <Phone className="w-4 h-4 text-neutral-500 shrink-0" />
                        <span>{supplier.phone}</span>
                      </div>
                      {supplier.email && (
                        <div className="flex items-center gap-2 text-neutral-700">
                          <Mail className="w-4 h-4 text-neutral-500 shrink-0" />
                          <span className="truncate">{supplier.email}</span>
                        </div>
                      )}
                    </div>

                    {/* Badges de Plazo de Entrega y Productos Asociados */}
                    <div className="grid grid-cols-2 gap-2 pt-1 border-t border-dashed">
                        <button
                          type="button"
                          onClick={() => handleOpenPlazosModal(supplier)}
                          className="p-2 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-xl text-left cursor-pointer transition-colors group"
                          title="Ver plazos de entrega e historial"
                        >
                          <span className="text-[10px] font-bold text-amber-700 flex items-center gap-1">
                            <Clock className="w-3 h-3 text-amber-600" />
                            Plazo Entrega
                          </span>
                          <span className="text-xs font-black text-amber-900 block mt-0.5 group-hover:underline">
                            {supplier.plazo_entrega_dias !== undefined ? `${supplier.plazo_entrega_dias} días` : 'No asignado'}
                          </span>
                        </button>

                        <button
                          type="button"
                          onClick={() => handleOpenProductAssociation(supplier)}
                          className="p-2 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl text-left cursor-pointer transition-colors group"
                          title="Gestionar productos asociados y costos pactados"
                        >
                          <span className="text-[10px] font-bold text-blue-700 flex items-center gap-1">
                            <Package className="w-3 h-3 text-blue-600" />
                            Catálogo Prov.
                          </span>
                          <span className="text-xs font-black text-blue-900 block mt-0.5 group-hover:underline">
                            {assocCount} {assocCount === 1 ? 'producto' : 'productos'}
                          </span>
                        </button>
                      </div>
                    </div>

                    <div className="flex flex-wrap gap-2 border-t pt-3">
                      <button
                        onClick={() => handleOpenEditSupplier(supplier)}
                        className="flex-1 py-2 bg-neutral-100 hover:bg-neutral-200 border border-neutral-300 text-neutral-800 font-bold text-xs rounded-xl flex items-center justify-center gap-1 cursor-pointer transition-colors"
                        title="Editar datos del proveedor"
                      >
                        <Edit className="w-3.5 h-3.5" />
                        <span>Editar</span>
                      </button>

                      <button
                        onClick={() => handleOpenProductAssociation(supplier)}
                        className="px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-bold text-xs rounded-xl flex items-center justify-center gap-1 cursor-pointer transition-colors"
                        title="Asociar productos con precios acordados"
                      >
                        <Layers className="w-3.5 h-3.5" />
                        <span>Productos</span>
                      </button>

                      <button
                        onClick={() => handleToggleSupplierStatus(supplier)}
                        className={`px-3 py-2 border font-bold text-xs rounded-xl flex items-center justify-center gap-1 cursor-pointer transition-colors ${
                          isActivo 
                            ? 'bg-red-50 hover:bg-red-500 text-red-700 hover:text-white border-red-200' 
                            : 'bg-emerald-50 hover:bg-emerald-500 text-emerald-700 hover:text-white border-emerald-200'
                        }`}
                        title={isActivo ? 'Desactivar proveedor' : 'Reactivar proveedor'}
                      >
                        {isActivo ? (
                          <>
                            <XCircle className="w-3.5 h-3.5" />
                            <span>Desactivar</span>
                          </>
                        ) : (
                          <>
                            <CheckCircle className="w-3.5 h-3.5" />
                            <span>Reactivar</span>
                          </>
                        )}
                      </button>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      )}

      {/* --- MODAL ASOCIACIÓN DE PRODUCTOS --- */}
      {supplierForProducts && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200">
          <div className="bg-white rounded-3xl max-w-3xl w-full p-6 space-y-6 shadow-2xl border-4 border-blue-500">
            <div className="flex items-center justify-between border-b pb-4">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                  <Package className="w-6 h-6" />
                </div>
                <div>
                  <h3 className="text-2xl font-black text-slate-800">
                    Productos Asociados & Costos Pactados
                  </h3>
                  <p className="text-sm font-bold text-slate-500">
                    Proveedor: <span className="text-blue-600 font-extrabold">{supplierForProducts.name}</span> • CUIT: {supplierForProducts.cuit}
                  </p>
                </div>
              </div>
              <button 
                onClick={() => setSupplierForProducts(null)} 
                className="p-2 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl cursor-pointer"
              >
                <X className="w-6 h-6" />
              </button>
            </div>

            {assocError && (
              <div className="p-3 bg-red-50 border-2 border-red-300 text-red-700 rounded-xl font-bold text-sm flex items-center gap-2">
                <AlertTriangle className="w-4 h-4 shrink-0" />
                <span>{assocError}</span>
              </div>
            )}

            {/* FORMULARIO PARA ASOCIAR NUEVO PRODUCTO */}
            <form onSubmit={handleAssociateProduct} className="bg-blue-50/70 border-2 border-blue-200 rounded-2xl p-4 space-y-4">
              <div className="font-extrabold text-sm text-blue-900 flex items-center gap-2">
                <PlusCircle className="w-4 h-4 text-blue-600" />
                <span>Vincular Producto del Catálogo con Costo Acordado:</span>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div className="md:col-span-5">
                  <label className="block text-xs font-bold text-slate-700 mb-1">Producto:</label>
                  <select
                    value={assocProductId}
                    onChange={(e) => {
                      setAssocProductId(e.target.value);
                      const p = products.find(prod => prod.id === e.target.value);
                      if (p) setAssocPrice(p.cost);
                    }}
                    className="w-full border-2 border-slate-300 rounded-xl p-2.5 text-xs font-bold bg-white focus:outline-none"
                  >
                    {products.map(p => (
                      <option key={p.id} value={p.id}>[{p.code}] {p.name}</option>
                    ))}
                  </select>
                </div>

                <div className="md:col-span-3">
                  <label className="block text-xs font-bold text-slate-700 mb-1">Precio Acordado ($):</label>
                  <input
                    type="number"
                    min="0"
                    step="any"
                    value={assocPrice}
                    onChange={(e) => setAssocPrice(Number(e.target.value))}
                    className="w-full border-2 border-slate-300 rounded-xl p-2.5 text-xs font-bold bg-white focus:outline-none font-mono"
                  />
                </div>

                <div className="md:col-span-2 flex items-center gap-2 pb-2">
                  <label className="flex items-center gap-1.5 text-xs font-extrabold text-slate-700 cursor-pointer select-none">
                    <input
                      type="checkbox"
                      checked={assocIsPrincipal}
                      onChange={(e) => setAssocIsPrincipal(e.target.checked)}
                      className="w-4 h-4 rounded text-blue-600 focus:ring-blue-500"
                    />
                    <span>Principal</span>
                  </label>
                </div>

                <div className="md:col-span-2">
                  <button
                    type="submit"
                    disabled={assocLoading}
                    className="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold rounded-xl text-xs cursor-pointer shadow flex items-center justify-center gap-1"
                  >
                    {assocLoading ? <RefreshCw className="w-3.5 h-3.5 animate-spin" /> : <Plus className="w-3.5 h-3.5" />}
                    <span>Vincular</span>
                  </button>
                </div>
              </div>
            </form>

            {/* TABLA DE PRODUCTOS ASOCIADOS */}
            <div className="border-2 border-slate-200 rounded-2xl overflow-hidden bg-white max-h-80 overflow-y-auto">
              <table className="w-full text-left border-collapse text-xs">
                <thead>
                  <tr className="bg-slate-100 border-b font-extrabold text-slate-700 uppercase">
                    <th className="p-3">Producto Asociado</th>
                    <th className="p-3 text-right">Precio Acordado</th>
                    <th className="p-3 text-center">Proveedor Habitual</th>
                    <th className="p-3 text-center w-28">Acciones</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-200">
                  {(!supplierForProducts.productos_asociados || supplierForProducts.productos_asociados.length === 0) ? (
                    <tr>
                      <td colSpan={4} className="p-8 text-center text-slate-400 font-bold">
                        No hay productos asociados a este proveedor aún. Use el cargador superior.
                      </td>
                    </tr>
                  ) : (
                    supplierForProducts.productos_asociados.map((item: any, idx: number) => {
                      const idProd = item.id_producto;
                      const nombre = item.nombre || item.producto?.nombre || `Producto #${idProd}`;
                      const sku = item.codigo_sku || item.producto?.codigo_sku || '';
                      const isPrincipal = !!item.es_proveedor_principal;
                      const precio = item.precio_acordado !== undefined ? Number(item.precio_acordado) : 0;

                      return (
                        <tr key={idx} className="hover:bg-slate-50 font-semibold">
                          <td className="p-3">
                            <span className="font-extrabold text-slate-900 block">{nombre}</span>
                            {sku && <span className="text-[10px] font-mono text-slate-500 font-bold">SKU: {sku}</span>}
                          </td>
                          <td className="p-3 text-right font-mono font-black text-slate-800 text-sm">
                            ${precio.toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                          </td>
                          <td className="p-3 text-center">
                            {isPrincipal ? (
                              <span className="inline-flex items-center gap-1 bg-amber-100 text-amber-800 border border-amber-300 px-2 py-0.5 rounded-full font-black text-[10px] uppercase">
                                <Star className="w-3 h-3 fill-amber-500 text-amber-600" />
                                Principal
                              </span>
                            ) : (
                              <button
                                type="button"
                                onClick={() => handleSetPrincipalProduct(item.id_producto_proveedor)}
                                className="text-[11px] text-slate-500 hover:text-blue-600 underline font-bold cursor-pointer"
                                title="Fijar como proveedor principal para este producto"
                              >
                                Hacer Principal
                              </button>
                            )}
                          </td>
                          <td className="p-3 text-center">
                            <button
                              type="button"
                              onClick={() => handleRemoveAssociatedProduct(idProd)}
                              className="p-1.5 text-red-500 hover:bg-red-50 rounded-lg cursor-pointer transition-colors"
                              title="Desasociar producto del proveedor"
                            >
                              <Trash2 className="w-4 h-4 mx-auto" />
                            </button>
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>

            <div className="flex justify-end pt-2 border-t">
              <button
                type="button"
                onClick={() => setSupplierForProducts(null)}
                className="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-sm rounded-xl cursor-pointer"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      )}

      {/* --- MODAL PLAZOS DE ENTREGA E HISTORIAL --- */}
      {supplierForPlazos && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200">
          <div className="bg-white rounded-3xl max-w-2xl w-full p-6 space-y-6 shadow-2xl border-4 border-amber-500">
            <div className="flex items-center justify-between border-b pb-4">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                  <Clock className="w-6 h-6" />
                </div>
                <div>
                  <h3 className="text-2xl font-black text-slate-800">
                    Plazos de Entrega e Historial
                  </h3>
                  <p className="text-sm font-bold text-slate-500">
                    Proveedor: <span className="text-amber-700 font-extrabold">{supplierForPlazos.name}</span>
                  </p>
                </div>
              </div>
              <button 
                onClick={() => setSupplierForPlazos(null)} 
                className="p-2 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl cursor-pointer"
              >
                <X className="w-6 h-6" />
              </button>
            </div>

            {plazosError && (
              <div className="p-3 bg-red-50 border-2 border-red-300 text-red-700 rounded-xl font-bold text-sm flex items-center gap-2">
                <AlertTriangle className="w-4 h-4 shrink-0" />
                <span>{plazosError}</span>
              </div>
            )}

            {/* FORMULARIO NUEVO PLAZO */}
            <form onSubmit={handleRegisterPlazo} className="bg-amber-50/70 border-2 border-amber-200 rounded-2xl p-4 space-y-3">
              <div className="font-extrabold text-sm text-amber-900 flex items-center gap-2">
                <Clock className="w-4 h-4 text-amber-600" />
                <span>Registrar Nuevo Plazo de Entrega Acordado:</span>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div className="md:col-span-4">
                  <label className="block text-xs font-bold text-slate-700 mb-1">Días de Entrega:</label>
                  <input
                    type="number"
                    min="1"
                    required
                    value={nuevoPlazoDias}
                    onChange={(e) => setNuevoPlazoDias(Number(e.target.value))}
                    className="w-full border-2 border-slate-300 rounded-xl p-2.5 text-sm font-bold bg-white focus:outline-none font-mono"
                  />
                </div>

                <div className="md:col-span-5">
                  <label className="block text-xs font-bold text-slate-700 mb-1">Motivo / Justificación:</label>
                  <input
                    type="text"
                    placeholder="Ej. Reducción por depósito cercano"
                    value={nuevoPlazoMotivo}
                    onChange={(e) => setNuevoPlazoMotivo(e.target.value)}
                    className="w-full border-2 border-slate-300 rounded-xl p-2.5 text-xs font-bold bg-white focus:outline-none"
                  />
                </div>

                <div className="md:col-span-3">
                  <button
                    type="submit"
                    className="w-full py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-extrabold rounded-xl text-xs cursor-pointer shadow flex items-center justify-center gap-1"
                  >
                    <Check className="w-3.5 h-3.5" />
                    <span>Guardar Plazo</span>
                  </button>
                </div>
              </div>
            </form>

            {/* HISTORIAL CRONOLÓGICO DE PLAZOS */}
            <div className="border-2 border-slate-200 rounded-2xl overflow-hidden bg-white max-h-72 overflow-y-auto">
              <table className="w-full text-left border-collapse text-xs">
                <thead>
                  <tr className="bg-slate-100 border-b font-extrabold text-slate-700 uppercase">
                    <th className="p-3">Fecha Registro</th>
                    <th className="p-3 text-center">Plazo (Días)</th>
                    <th className="p-3">Motivo / Observación</th>
                    <th className="p-3 text-right">Usuario Responsable</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-200">
                  {plazosLoading ? (
                    <tr>
                      <td colSpan={4} className="p-6 text-center text-slate-400 font-bold">
                        Cargando historial de plazos...
                      </td>
                    </tr>
                  ) : plazosList.length === 0 ? (
                    <tr>
                      <td colSpan={4} className="p-6 text-center text-slate-400 font-bold">
                        No hay registros históricos de plazos para este proveedor.
                      </td>
                    </tr>
                  ) : (
                    plazosList.map((p, idx) => (
                      <tr key={idx} className="hover:bg-slate-50 font-semibold">
                        <td className="p-3 font-mono text-slate-600">
                          {p.fecha_cambio || p.fecha_registro || p.created_at || 'Fecha no registrada'}
                        </td>
                        <td className="p-3 text-center font-black text-amber-700 text-sm font-mono">
                          {p.plazo_nuevo_dias !== undefined ? p.plazo_nuevo_dias : p.plazo_entrega_dias} días
                        </td>
                        <td className="p-3 text-slate-700">
                          {p.motivo || (p.plazo_anterior_dias !== null && p.plazo_anterior_dias !== undefined ? `Modificación (de ${p.plazo_anterior_dias} a ${p.plazo_nuevo_dias} días)` : 'Acuerdo inicial')}
                        </td>
                        <td className="p-3 text-right font-bold text-slate-700">
                          {p.usuario_nombre || (p.id_usuario ? `Usuario #${p.id_usuario}` : 'Administrador')}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>

            <div className="flex justify-end pt-2 border-t">
              <button
                type="button"
                onClick={() => setSupplierForPlazos(null)}
                className="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-sm rounded-xl cursor-pointer"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      )}

      {/* --- RENDER PESTAÑA: COMPRAS --- */}
      {activeTab === 'COMPRAS' && !showPurchaseForm && (
        <div className="bg-white border-2 border-[#DEE2E6] rounded-2xl p-6 space-y-4">
          <div className="flex items-center justify-between border-b pb-4 border-dashed">
            <h2 className="text-2xl font-extrabold text-[#212529] flex items-center gap-2">
              <FileText className="w-6 h-6 text-[#820dfd]" />
              <span>Compras de Inventario / Recepción Directa</span>
            </h2>
            <span className="text-sm font-bold text-neutral-600 bg-neutral-100 border px-3 py-1 rounded-lg">
              Total compras registradas: <strong>{purchases.length}</strong>
            </span>
          </div>

          {filteredPurchases.length === 0 ? (
            <div className="text-center py-12 bg-neutral-50 rounded-2xl border-2 border-dashed border-neutral-300">
              <ShoppingCart className="w-16 h-16 text-neutral-400 mx-auto mb-4" />
              <p className="text-xl font-extrabold text-neutral-700">No se encontraron recepciones de compra.</p>
              <p className="text-sm text-neutral-500 font-bold mt-1">Inicie un ingreso de mercadería para incrementar el stock del catálogo automáticamente.</p>
            </div>
          ) : (
            <div className="space-y-4">
              {filteredPurchases.map(purchase => (
                <div key={purchase.id} className="border-2 border-[#DEE2E6] hover:border-[#820dfd] rounded-2xl p-5 transition-all bg-neutral-50/50">
                  <div className="flex justify-between items-start flex-wrap gap-4 border-b pb-3 mb-3 border-dashed">
                    <div>
                      <span className="text-xs font-black text-neutral-500 font-mono block">COMPRA ID: {purchase.id}</span>
                      <h4 className="text-xl font-black text-[#212529] mt-0.5">{purchase.supplierName}</h4>
                    </div>

                    <div className="text-right">
                      <span className="text-xs font-black text-neutral-500 flex items-center gap-1 justify-end font-mono">
                        <Calendar className="w-3.5 h-3.5" />
                        {purchase.date}
                      </span>
                      <span className="inline-flex items-center gap-1 px-3 py-1 bg-green-100 border border-green-200 text-[#198754] font-black text-xs rounded-full uppercase mt-1">
                        <Check className="w-3 h-3" />
                        <span>{purchase.status}</span>
                      </span>
                    </div>
                  </div>

                  <div className="space-y-3 font-semibold">
                    <p className="text-xs font-black text-neutral-500 uppercase">Detalle de mercadería ingresada:</p>
                    <div className="bg-white border rounded-xl overflow-hidden divide-y divide-neutral-100">
                      {purchase.items.map((item, idx) => (
                        <div key={idx} className="p-3 flex justify-between text-sm md:text-base font-semibold">
                          <span className="text-[#212529]">{item.productName}</span>
                          <div className="flex gap-4 font-mono text-xs md:text-sm">
                            <span className="text-neutral-500">{item.quantity} units @ ${item.costPrice}</span>
                            <span className="font-extrabold text-black">${item.total.toLocaleString('es-AR')}</span>
                          </div>
                        </div>
                      ))}
                    </div>

                    {purchase.notes && (
                      <div className="text-xs font-bold text-neutral-600 bg-neutral-100 border p-2 rounded-lg">
                        <strong>Obs:</strong> {purchase.notes}
                      </div>
                    )}

                    <div className="flex justify-between items-center bg-[#820dfd]/5 border border-[#820dfd]/10 rounded-xl p-3">
                      <span className="text-xs font-black text-[#820dfd]">DOCUMENTO PROVEEDOR: <strong className="text-black ml-1">{purchase.invoiceNumber || 'No especificado'}</strong></span>
                      <span className="text-lg font-black text-[#820dfd] font-mono">TOTAL COMPRA: ${purchase.total.toLocaleString('es-AR')}</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

    </div>
  );
}
