/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState, useEffect } from 'react';
import { Product, User, Categoria, Marca, Order } from '../types';
import { productosService, stockService, proveedoresService } from '../services';
import { formatDateTime } from '../utils/dateUtils';
import { loadOrders } from '../data';
import { 
  Package, 
  Plus, 
  Edit, 
  Trash2, 
  Search, 
  AlertTriangle, 
  RotateCcw, 
  History, 
  TrendingUp, 
  DollarSign, 
  Filter, 
  Check, 
  X,
  Layers,
  Calendar,
  ClipboardList,
  ArrowUpDown,
  Tag,
  Star,
  Clock,
  ShieldAlert,
  Archive,
  RefreshCw,
  MapPin,
  Truck,
  Scale,
  ArrowDownRight,
  Info
} from 'lucide-react';

interface InventoryViewProps {
  currentUser: User;
  products: Product[];
  onUpdateProducts: (products: Product[]) => void;
  orders?: Order[];
}

export function InventoryView({ currentUser, products, onUpdateProducts, orders }: InventoryViewProps) {
  // Pestañas: CATALOGO, STOCK, LOTES, MOVIMIENTOS, CONTEO, DEVOLUCIONES
  const [activeTab, setActiveTab] = useState<'CATALOGO' | 'STOCK' | 'LOTES' | 'MOVIMIENTOS' | 'CONTEO' | 'DEVOLUCIONES'>('CATALOGO');

  // Filtros generales
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('Todas');
  const [selectedMarca, setSelectedMarca] = useState('Todas');
  const [selectedEstado, setSelectedEstado] = useState('todos'); // 'todos', 'activo', 'inactivo'
  const [stockLevelFilter, setStockLevelFilter] = useState<'TODOS' | 'NORMAL' | 'BAJO' | 'CRITICO'>('TODOS');
  const [stockMinFilter, setStockMinFilter] = useState('');
  const [stockMaxFilter, setStockMaxFilter] = useState('');

  // Categorías y Marcas de la API
  const [categorias, setCategorias] = useState<Categoria[]>([]);
  const [marcas, setMarcas] = useState<Marca[]>([]);
  const [nuevaCatNombre, setNuevaCatNombre] = useState('');
  const [nuevaMarcaNombre, setNuevaMarcaNombre] = useState('');
  const [editingCatId, setEditingCatId] = useState<number | null>(null);
  const [editingCatNombre, setEditingCatNombre] = useState('');
  const [editingMarcaId, setEditingMarcaId] = useState<number | null>(null);
  const [editingMarcaNombre, setEditingMarcaNombre] = useState('');

  // S14: Ubicaciones Físicas de Almacenamiento
  const [ubicaciones, setUbicaciones] = useState<any[]>([]);
  const [showUbicacionesModal, setShowUbicacionesModal] = useState(false);
  const [nuevaUbicacionDesc, setNuevaUbicacionDesc] = useState('');

  // S03: Ingreso de Mercadería de Proveedores
  const [proveedores, setProveedores] = useState<any[]>([]);
  const [showIngresoModal, setShowIngresoModal] = useState(false);
  const [ingresoProveedorId, setIngresoProveedorId] = useState<number | ''>('');
  const [ingresoFecha, setIngresoFecha] = useState<string>('');
  const [ingresoItems, setIngresoItems] = useState<Array<{ id_producto: number; cantidad: number; nro_lote?: string; fecha_vencimiento?: string; selected_mode?: 'existente' | 'nuevo' }>>([{ id_producto: 0, cantidad: 1, selected_mode: 'nuevo' }]);

  // S08: Unidades y Equivalencias
  const [showUnidadesModal, setShowUnidadesModal] = useState(false);
  const [selectedProdForUnidades, setSelectedProdForUnidades] = useState<Product | null>(null);
  const [unidadesProd, setUnidadesProd] = useState<any[]>([]);
  const [nuevaUnidadNombre, setNuevaUnidadNombre] = useState('');
  const [nuevaUnidadEquiv, setNuevaUnidadEquiv] = useState(12);
  const [nuevaUnidadDesc, setNuevaUnidadDesc] = useState('');

  // Modales
  const [showProductModal, setShowProductModal] = useState(false);
  const [showAumentoMasivoModal, setShowAumentoMasivoModal] = useState(false);
  const [showHistorialPreciosModal, setShowHistorialPreciosModal] = useState(false);
  const [showCategoriasModal, setShowCategoriasModal] = useState(false);
  const [showMarcasModal, setShowMarcasModal] = useState(false);
  const [showAdjustmentModal, setShowAdjustmentModal] = useState(false);
  const [showLoteModal, setShowLoteModal] = useState(false);
  const [showConteoModal, setShowConteoModal] = useState(false);
  const [showDevolucionModal, setShowDevolucionModal] = useState(false);

  // Estados de Producto en Edición / Ficha
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);
  const [selectedProductForHistory, setSelectedProductForHistory] = useState<Product | null>(null);
  const [historialPreciosData, setHistorialPreciosData] = useState<any[]>([]);
  const [historialAuditoriaData, setHistorialAuditoriaData] = useState<any[]>([]);
  const [historyModalTab, setHistoryModalTab] = useState<'PRECIOS' | 'CAMBIOS'>('PRECIOS');
  const [loadingHistory, setLoadingHistory] = useState(false);

  // Formulario de Producto
  const [pCode, setPCode] = useState('');
  const [pName, setPName] = useState('');
  const [pDescription, setPDescription] = useState('');
  const [pCategoryId, setPCategoryId] = useState<number>(96);
  const [pMarcaId, setPMarcaId] = useState<number>(88);
  const [pUbicacionId, setPUbicacionId] = useState<number | ''>('');
  const [pPrice, setPPrice] = useState(0);
  const [pPriceMayorista, setPPriceMayorista] = useState(0);
  const [pCost, setPCost] = useState(0);
  const [pStock, setPStock] = useState(0);
  const [pMinStock, setPMinStock] = useState(10);
  const [formError, setFormError] = useState('');

  // Formulario Aumento Masivo
  const [aumPorcentaje, setAumPorcentaje] = useState(10);
  const [aumAlcance, setAumAlcance] = useState<'TODOS' | 'CATEGORIA' | 'MARCA'>('TODOS');
  const [aumCatId, setAumCatId] = useState<number>(96);
  const [aumMarcaId, setAumMarcaId] = useState<number>(88);
  const [aumRedondeo, setAumRedondeo] = useState(true);
  const [aumError, setAumError] = useState('');

  // Formulario Ajuste de Stock
  const [adjProduct, setAdjProduct] = useState<Product | null>(null);
  const [adjType, setAdjType] = useState<'Entrada' | 'Salida'>('Entrada');
  const [adjQuantity, setAdjQuantity] = useState(1);
  const [adjReason, setAdjReason] = useState('Ajuste de inventario');

  // Lotes y Vencimientos
  const [lotes, setLotes] = useState<any[]>([]);
  const [loteProdId, setLoteProdId] = useState<number>(0);
  const [loteNro, setLoteNro] = useState('');
  const [loteCantidad, setLoteCantidad] = useState(1);
  const [loteVto, setLoteVto] = useState('');
  const [loteSearchTerm, setLoteSearchTerm] = useState('');
  const [loteSoloPorVencer, setLoteSoloPorVencer] = useState(false);
  const [loteDiasVencer, setLoteDiasVencer] = useState(30);
  const [loteSortAsc, setLoteSortAsc] = useState(true);

  // Conteo Físico
  const [conteoProd, setConteoProd] = useState<Product | null>(null);
  const [conteoCantidadFisica, setConteoCantidadFisica] = useState(0);
  const [conteoObs, setConteoObs] = useState('');

  // Devolución
  const [devProdId, setDevProdId] = useState<string>('');
  const [devCantidad, setDevCantidad] = useState(1);
  const [devTipo, setDevTipo] = useState<'cliente' | 'proveedor'>('cliente');
  const [devMotivo, setDevMotivo] = useState('Mercadería en mal estado');

  // Historial de Movimientos
  const [movimientos, setMovements] = useState<any[]>([]);
  const [movimientoProdFilter, setMovimientoProdFilter] = useState<string>('todos');

  useEffect(() => {
    cargarCategoriasYMarcas();
    cargarUbicaciones();
    cargarProveedores();
    cargarLotes();
    cargarMovimientos();
  }, []);

  const cargarCategoriasYMarcas = async () => {
    try {
      const [cats, marcs] = await Promise.all([
        productosService.getCategorias(),
        productosService.getMarcas(),
      ]);
      setCategorias(cats);
      setMarcas(marcs);
      if (cats.length > 0) setPCategoryId(cats[0].id_categoria);
      if (marcs.length > 0) setPMarcaId(marcs[0].id_marca);
    } catch (e) {
      console.warn('Error al cargar categorias/marcas', e);
    }
  };

  const cargarUbicaciones = async () => {
    try {
      const ubics = await stockService.getUbicaciones();
      setUbicaciones(ubics);
    } catch (e) {
      console.warn('Error al cargar ubicaciones', e);
    }
  };

  const cargarProveedores = async () => {
    try {
      const provs = await proveedoresService.getAll({ estado: 'activo' });
      setProveedores(provs);
      if (provs.length > 0) {
        setIngresoProveedorId(provs[0].id_proveedor || Number(provs[0].id));
      }
    } catch (e) {
      console.warn('Error al cargar proveedores', e);
    }
  };

  const cargarLotes = async () => {
    try {
      const l = await stockService.getLotes();
      setLotes(l);
    } catch (e) {
      console.warn('Error al cargar lotes', e);
    }
  };

  const cargarMovimientos = async () => {
    try {
      const data = await stockService.getTodosLosMovimientos();
      if (Array.isArray(data) && data.length > 0) {
        setMovements(data.map((m: any) => ({
          id: m.id_movimiento ? `MV-${m.id_movimiento}` : `MV-${Date.now()}`,
          date: formatDateTime(m.fecha),
          productName: `${m.producto_codigo} - ${m.producto_nombre}`,
          productId: String(m.id_producto),
          id_producto: m.id_producto,
          type: m.tipo === 'ingreso' ? 'Ingreso' : m.tipo === 'venta' ? 'Venta' : m.tipo === 'devolucion' ? 'Devolución' : 'Ajuste',
          quantity: m.cantidad,
          unit: m.unidad || 'u.',
          reason: m.motivo || '-',
          user: m.usuario_nombre || 'Sistema',
        })));
        return;
      }
    } catch (e) {
      console.warn('Error al cargar movimientos desde API', e);
    }
    const saved = localStorage.getItem('erp_distribuidora_stock_movements');
    if (saved) {
      try {
        setMovements(JSON.parse(saved));
      } catch (e) {
        console.error(e);
      }
    }
  };

  const [isRefreshing, setIsRefreshing] = useState(false);

  const handleRefrescar = async () => {
    setIsRefreshing(true);
    try {
      await Promise.allSettled([
        cargarCategoriasYMarcas(),
        cargarUbicaciones(),
        cargarProveedores(),
        cargarLotes(),
        cargarMovimientos(),
        (async () => {
          const prods = await productosService.getAll({ estado: 'todos' });
          if (prods && prods.length > 0) {
            onUpdateProducts(prods);
          }
        })(),
      ]);
    } catch (e) {
      console.warn('Error al refrescar inventario', e);
    } finally {
      setTimeout(() => setIsRefreshing(false), 300);
    }
  };

  // Helper para consistencia de estado activo/inactivo (soporta tanto 'estado' como 'status')
  const isProdInactivo = (p: Product) => (p.estado || p.status) === 'inactivo';
  const isProdActivo = (p: Product) => !isProdInactivo(p);

  // S02: Cálculo dinámico de unidades comprometidas (a partir de pedidos activos o propiedad directa)
  const getCommittedUnits = (p: Product): number => {
    let fromOrders = 0;
    const currentOrders = orders || (typeof window !== 'undefined' ? loadOrders() : []);
    if (Array.isArray(currentOrders)) {
      for (const order of currentOrders) {
        if (order.status !== 'Entregado' && order.status !== 'Cancelado' && Array.isArray(order.items)) {
          for (const item of order.items) {
            const matches =
              (item.productId !== undefined && item.productId !== null && (
                String(item.productId) === String(p.id) ||
                (p.id_producto !== undefined && String(item.productId) === String(p.id_producto)) ||
                (p.code && String(item.productId).toLowerCase() === p.code.toLowerCase()) ||
                (p.codigo && String(item.productId).toLowerCase() === p.codigo.toLowerCase())
              ));
            if (matches) {
              fromOrders += Number(item.quantity || 0);
            }
          }
        }
      }
    }
    const direct = Number(p.committedStock ?? (p as any).stock_comprometido ?? (p as any).comprometido ?? 0);
    return Math.max(direct, fromOrders);
  };

  // --- FILTROS DE PRODUCTOS ---
  const filteredProducts = products.filter(p => {
    const matchesSearch = searchTerm === '' || 
      p.name.toLowerCase().includes(searchTerm.toLowerCase()) || 
      p.code.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesCat = selectedCategory === 'Todas' || p.category === selectedCategory || p.categoria_nombre === selectedCategory;
    const matchesMarca = selectedMarca === 'Todas' || p.marca_nombre === selectedMarca;
    
    const matchesEstado = selectedEstado === 'todos' || 
      (selectedEstado === 'activo' && isProdActivo(p)) ||
      (selectedEstado === 'inactivo' && isProdInactivo(p));

    let matchesStockLevel = true;
    if (stockLevelFilter === 'CRITICO') {
      matchesStockLevel = p.stock <= 5;
    } else if (stockLevelFilter === 'BAJO') {
      matchesStockLevel = p.stock > 5 && p.stock <= p.minStock;
    } else if (stockLevelFilter === 'NORMAL') {
      matchesStockLevel = p.stock > p.minStock;
    }

    // S15: Filtrado por rango de nivel de stock disponible
    const matchesStockMin = stockMinFilter === '' || p.stock >= Number(stockMinFilter);
    const matchesStockMax = stockMaxFilter === '' || p.stock <= Number(stockMaxFilter);

    return matchesSearch && matchesCat && matchesMarca && matchesEstado && matchesStockLevel && matchesStockMin && matchesStockMax;
  });

  // Filtrado y ordenamiento de lotes y alertas de vencimiento
  const filteredLotes = lotes
    .filter(l => {
      const term = loteSearchTerm.trim().toLowerCase();
      const prodText = `${l.producto_nombre || ''} ${l.descripcion_producto || ''} ${l.codigo_producto || ''}`.toLowerCase();
      const matchesSearch = term === '' ||
        (l.nro_lote && l.nro_lote.toLowerCase().includes(term)) ||
        prodText.includes(term);

      if (!matchesSearch) return false;

      if (loteSoloPorVencer) {
        if (!l.fecha_vencimiento) return false;
        const vtoDate = new Date(l.fecha_vencimiento);
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        const diffDays = Math.ceil((vtoDate.getTime() - hoy.getTime()) / (1000 * 60 * 60 * 24));
        return diffDays >= 0 && diffDays <= Number(loteDiasVencer);
      }
      return true;
    })
    .sort((a, b) => {
      if (!a.fecha_vencimiento) return 1;
      if (!b.fecha_vencimiento) return -1;
      const dateA = new Date(a.fecha_vencimiento).getTime();
      const dateB = new Date(b.fecha_vencimiento).getTime();
      return loteSortAsc ? dateA - dateB : dateB - dateA;
    });

  // S12: Filtrado de movimientos de stock
  const filteredMovimientos = movimientos.filter(m => {
    if (movimientoProdFilter === 'todos') return true;
    return String(m.productId) === String(movimientoProdFilter) || 
           String(m.id_producto) === String(movimientoProdFilter);
  });

  // --- ACCIONES DE PRODUCTO ---
  const handleOpenAddProduct = () => {
    setEditingProduct(null);
    setPCode('');
    setPName('');
    setPDescription('');
    setPPrice(0);
    setPPriceMayorista(0);
    setPCost(0);
    setPStock(0);
    setPMinStock(10);
    setPUbicacionId('');
    setFormError('');
    setShowProductModal(true);
  };

  const handleOpenEditProduct = (prod: Product) => {
    setEditingProduct(prod);
    setPCode(prod.code);
    setPName(prod.name);
    setPDescription(prod.descripcion || prod.name);
    setPPrice(prod.price);
    setPPriceMayorista(prod.priceWholesale || prod.price);
    setPCost(prod.cost);
    setPStock(prod.stock);
    setPMinStock(prod.minStock);
    setPUbicacionId(prod.id_ubicacion ?? '');
    if (prod.id_categoria) setPCategoryId(prod.id_categoria);
    if (prod.id_marca) setPMarcaId(prod.id_marca);
    setFormError('');
    setShowProductModal(true);
  };

  const handleSaveProduct = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormError('');

    if (!pCode.trim() || !pName.trim()) {
      setFormError('El código y el nombre son obligatorios.');
      return;
    }

    try {
      let saved: Product;
      if (editingProduct) {
        const idProd = editingProduct.id_producto || Number(editingProduct.id);
        if (!isNaN(idProd) && idProd > 0) {
          saved = await productosService.update(idProd, {
            codigo: pCode.trim().toUpperCase(),
            nombre: pName.trim(),
            descripcion: pDescription.trim() || pName.trim(),
            precio_unitario: Number(pPrice),
            precio_minorista: Number(pPrice),
            precio_mayorista: Number(pPriceMayorista || pPrice),
            stock_minimo: Number(pMinStock),
            id_categoria: pCategoryId,
            id_marca: pMarcaId,
            id_ubicacion: pUbicacionId !== '' ? Number(pUbicacionId) : undefined,
          });
        } else {
          saved = {
            ...editingProduct,
            code: pCode.trim().toUpperCase(),
            name: pName.trim(),
            price: Number(pPrice),
            cost: Number(pCost),
            minStock: Number(pMinStock),
            id_ubicacion: pUbicacionId !== '' ? Number(pUbicacionId) : undefined,
            ubicacion_nombre: ubicaciones.find(u => u.id_ubicacion === Number(pUbicacionId))?.descripcion,
          };
        }
        onUpdateProducts(products.map(p => p.id === editingProduct.id ? saved : p));
      } else {
        saved = await productosService.create({
          codigo: pCode.trim().toUpperCase(),
          nombre: pName.trim(),
          descripcion: pDescription.trim() || pName.trim(),
          precio_unitario: Number(pPrice),
          precio_minorista: Number(pPrice),
          id_categoria: pCategoryId,
          id_marca: pMarcaId,
          stock_disponible: Number(pStock),
          stock_minimo: Number(pMinStock),
          id_ubicacion: pUbicacionId !== '' ? Number(pUbicacionId) : undefined,
        });
        onUpdateProducts([saved, ...products]);
      }
      setShowProductModal(false);
      setEditingProduct(null);
      await handleRefrescar();
    } catch (err: any) {
      setFormError(err.message || 'Error al guardar el producto.');
    }
  };

  const handleToggleEstado = async (prod: Product) => {
    const isActivo = isProdActivo(prod);
    const msg = isActivo 
      ? `¿Desea desactivar el producto "${prod.name}"?` 
      : `¿Desea reactivar el producto "${prod.name}"?`;
    
    if (!confirm(msg)) return;

    try {
      const idProd = prod.id_producto || Number(prod.id);
      if (!isNaN(idProd) && idProd > 0) {
        if (isActivo) {
          await productosService.desactivar(idProd);
        } else {
          await productosService.activar(idProd);
        }
      }
      onUpdateProducts(products.map(p => p.id === prod.id ? { ...p, estado: isActivo ? 'inactivo' : 'activo', status: isActivo ? 'inactivo' : 'activo' } : p));
      await handleRefrescar();
    } catch (e: any) {
      alert(e.message || 'Error al cambiar estado del producto.');
    }
  };

  // --- HISTORIAL DE PRECIOS Y AUDITORÍA DE CAMBIOS ---
  const handleVerHistorialPrecios = async (prod: Product) => {
    setSelectedProductForHistory(prod);
    setLoadingHistory(true);
    setHistoryModalTab('PRECIOS');
    setShowHistorialPreciosModal(true);
    try {
      const idProd = prod.id_producto || Number(prod.id);
      const [dataPrecios, dataAuditoria] = await Promise.all([
        productosService.getHistorialPrecios(idProd),
        productosService.getAuditoria(idProd),
      ]);
      setHistorialPreciosData(dataPrecios);
      setHistorialAuditoriaData(dataAuditoria);
    } catch (e) {
      console.warn('Error al cargar historial o auditoria', e);
      setHistorialPreciosData([]);
      setHistorialAuditoriaData([]);
    } finally {
      setLoadingHistory(false);
    }
  };

  // --- AUMENTO MASIVO ---
  const handleEjecutarAumentoMasivo = async (e: React.FormEvent) => {
    e.preventDefault();
    setAumError('');

    if (aumPorcentaje <= 0) {
      setAumError('El porcentaje debe ser mayor a 0%.');
      return;
    }

    try {
      const payload: any = { porcentaje: aumPorcentaje };
      if (aumAlcance === 'CATEGORIA') payload.id_categoria = aumCatId;
      if (aumAlcance === 'MARCA') payload.id_marca = aumMarcaId;

      try {
        await productosService.aumentoMasivo(payload);
        const updated = await productosService.getAll();
        if (updated && updated.length > 0) {
          onUpdateProducts(updated);
          setShowAumentoMasivoModal(false);
          alert(`Aumento masivo del ${aumPorcentaje}% aplicado exitosamente.`);
          return;
        }
      } catch (apiErr) {
        console.warn('API aumento masivo no disponible, aplicando localmente:', apiErr);
      }

      // Fallback local garantizado para offline / pruebas
      const factor = 1 + (aumPorcentaje / 100);
      const updatedProducts = products.map(p => {
        let aplicar = false;
        if (aumAlcance === 'TODOS') aplicar = true;
        if (aumAlcance === 'CATEGORIA' && (p.category === String(aumCatId) || p.categoria_id === aumCatId)) aplicar = true;
        if (aumAlcance === 'MARCA' && (p.brand === String(aumMarcaId) || p.id_marca === aumMarcaId)) aplicar = true;

        if (aplicar) {
          const nuevoPrecio = Math.round(p.price * factor);
          return { ...p, price: nuevoPrecio, precio_minorista: nuevoPrecio };
        }
        return p;
      });

      onUpdateProducts(updatedProducts);
      setShowAumentoMasivoModal(false);
      alert(`Aumento masivo del ${aumPorcentaje}% aplicado exitosamente.`);
    } catch (err: any) {
      setAumError(err.message || 'Error al aplicar aumento masivo.');
    }
  };

  // --- CATEGORÍAS Y MARCAS ---
  const handleCrearCategoria = async () => {
    if (!nuevaCatNombre.trim()) return;
    try {
      await productosService.createCategoria(nuevaCatNombre.trim());
      setNuevaCatNombre('');
      await handleRefrescar();
    } catch (e: any) {
      alert(e.message || 'Error al crear categoría.');
    }
  };

  const handleGuardarEditarCategoria = async (id: number) => {
    if (!editingCatNombre.trim()) return;
    try {
      await productosService.updateCategoria(id, editingCatNombre.trim());
      setEditingCatId(null);
      setEditingCatNombre('');
      await handleRefrescar();
    } catch (e: any) {
      alert(e.message || 'Error al actualizar categoría.');
    }
  };

  const handleEliminarCategoria = async (cat: Categoria) => {
    if (!window.confirm(`¿Está seguro de que desea eliminar la categoría "${cat.nombre}"?`)) {
      return;
    }
    try {
      await productosService.deleteCategoria(cat.id_categoria);
      setCategorias(prev => prev.filter(c => c.id_categoria !== cat.id_categoria));
      await handleRefrescar();
    } catch (e: any) {
      alert(e.message || `No se puede eliminar la categoría "${cat.nombre}" porque tiene productos asociados o ocurrió un error.`);
    }
  };

  const handleCrearMarca = async () => {
    if (!nuevaMarcaNombre.trim()) return;
    try {
      await productosService.createMarca(nuevaMarcaNombre.trim());
      setNuevaMarcaNombre('');
      await handleRefrescar();
    } catch (e: any) {
      alert(e.message || 'Error al crear marca.');
    }
  };

  const handleGuardarEditarMarca = async (id: number) => {
    if (!editingMarcaNombre.trim()) return;
    try {
      await productosService.updateMarca(id, editingMarcaNombre.trim());
      setEditingMarcaId(null);
      setEditingMarcaNombre('');
      await handleRefrescar();
    } catch (e: any) {
      alert(e.message || 'Error al actualizar marca.');
    }
  };

  const handleEliminarMarca = async (m: Marca) => {
    if (!window.confirm(`¿Está seguro de que desea eliminar la marca "${m.nombre}"?`)) {
      return;
    }
    try {
      await productosService.deleteMarca(m.id_marca);
      setMarcas(prev => prev.filter(item => item.id_marca !== m.id_marca));
      await handleRefrescar();
    } catch (e: any) {
      alert(e.message || `No se puede eliminar la marca "${m.nombre}" porque tiene productos asociados o ocurrió un error.`);
    }
  };

  // --- AJUSTE MANUAL DE INVENTARIO ---
  const handleGuardarAjuste = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!adjProduct) return;
    if (isProdInactivo(adjProduct)) {
      alert('No se pueden realizar ajustes sobre productos inactivos.');
      return;
    }
    if (adjQuantity <= 0) {
      alert('La cantidad debe ser mayor a 0');
      return;
    }

    try {
      const idProd = adjProduct.id_producto || Number(adjProduct.id);
      if (!isNaN(idProd) && idProd > 0) {
        await stockService.registrarAjuste({
          id_producto: idProd,
          cantidad: adjQuantity,
          tipo: adjType === 'Entrada' ? 'aumentar' : 'disminuir',
          motivo: adjReason,
        });
      }

      const diff = adjType === 'Entrada' ? adjQuantity : -adjQuantity;
      const updatedStock = adjProduct.stock + diff;

      // Registrar movimiento
      const newMv = {
        id: 'MV-' + Date.now(),
        productId: adjProduct.id,
        productName: adjProduct.name,
        type: adjType,
        quantity: adjQuantity,
        reason: adjReason,
        date: formatDateTime(new Date()),
        user: currentUser.name,
      };
      const updatedMvs = [newMv, ...movimientos];
      setMovements(updatedMvs);
      localStorage.setItem('erp_distribuidora_stock_movements', JSON.stringify(updatedMvs));

      onUpdateProducts(products.map(p => p.id === adjProduct.id ? { ...p, stock: updatedStock } : p));
      setShowAdjustmentModal(false);
      setAdjProduct(null);
      await handleRefrescar();
    } catch (e: any) {
      alert(e.message || 'Error al registrar ajuste.');
    }
  };

  // --- REGISTRO DE LOTES ---
  const handleCrearLote = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!loteProdId || !loteNro.trim() || !loteVto) {
      alert('Complete todos los campos del lote.');
      return;
    }

    const prodLote = products.find(p => (p.id_producto || Number(p.id)) === Number(loteProdId));
    if (prodLote && isProdInactivo(prodLote)) {
      alert('No se pueden registrar lotes sobre productos inactivos.');
      return;
    }

    try {
      await stockService.registrarLote({
        id_producto: loteProdId,
        nro_lote: loteNro.trim(),
        cantidad: loteCantidad,
        fecha_vencimiento: loteVto,
      });
      setShowLoteModal(false);
      setLoteNro('');
      setLoteVto('');
      await handleRefrescar();
    } catch (e: any) {
      alert(e.message || 'Error al registrar lote.');
    }
  };

  // --- CONTEO FÍSICO ---
  const handleGuardarConteo = async () => {
    if (!conteoProd) return;
    if (isProdInactivo(conteoProd)) {
      alert('No se pueden realizar conteos ni conciliaciones sobre productos inactivos.');
      return;
    }
    const diferencia = conteoCantidadFisica - conteoProd.stock;

    if (diferencia !== 0) {
      const tipo = diferencia > 0 ? 'aumentar' : 'disminuir';
      const motivo = `Ajuste por conteo físico: Diferencia de ${diferencia} u. Obs: ${conteoObs}`;
      try {
        const idProd = conteoProd.id_producto || Number(conteoProd.id);
        if (!isNaN(idProd) && idProd > 0) {
          await stockService.registrarAjuste({
            id_producto: idProd,
            cantidad: Math.abs(diferencia),
            tipo,
            motivo,
          });
        }
        onUpdateProducts(products.map(p => p.id === conteoProd.id ? { ...p, stock: conteoCantidadFisica } : p));
      } catch (e: any) {
        alert(e.message || 'Error al aplicar conciliación.');
        return;
      }
    }

    alert(`Conteo registrado para ${conteoProd.name}. Diferencia: ${diferencia} u.`);
    setShowConteoModal(false);
    setConteoProd(null);
    setConteoObs('');
    await handleRefrescar();
  };

  // --- REGISTRO DE DEVOLUCIÓN ---
  const handleGuardarDevolucion = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!devProdId || devCantidad <= 0) return;

    try {
      const prod = products.find(p => p.id === devProdId || String(p.id_producto) === devProdId);
      if (!prod) return;

      if (isProdInactivo(prod)) {
        alert('No se pueden registrar devoluciones sobre productos inactivos.');
        return;
      }

      if (devTipo === 'proveedor' && prod.stock < devCantidad) {
        alert(`Stock insuficiente para devolver al proveedor. Stock actual disponible: ${prod.stock}, solicitado: ${devCantidad}.`);
        return;
      }

      const idProd = prod.id_producto || Number(prod.id);
      if (!isNaN(idProd) && idProd > 0) {
        await stockService.registrarDevolucion({
          id_producto: idProd,
          cantidad: devCantidad,
          motivo: devMotivo,
          tipo_devolucion: devTipo,
        });
      }

      const delta = devTipo === 'cliente' ? devCantidad : -devCantidad;
      const updatedStock = Math.max(0, prod.stock + delta);

      onUpdateProducts(products.map(p => p.id === prod.id ? { ...p, stock: updatedStock } : p));
      setShowDevolucionModal(false);
      const detalle = devTipo === 'cliente'
        ? `Devolución de cliente registrada: se sumaron +${devCantidad} u. al stock.`
        : `Devolución a proveedor registrada: se descontaron -${devCantidad} u. del stock.`;
      alert(detalle);
      setDevMotivo('');
      setDevCantidad(1);
      await handleRefrescar();
    } catch (e: any) {
      alert(e.message || 'Error al registrar devolución.');
    }
  };

  // --- GESTIÓN DE UNIDADES Y EQUIVALENCIAS ---
  const handleAbrirUnidades = async (prod: Product) => {
    if (isProdInactivo(prod)) {
      alert('No se pueden gestionar unidades de medida sobre productos inactivos.');
      return;
    }
    setSelectedProdForUnidades(prod);
    setShowUnidadesModal(true);
    setNuevaUnidadNombre('');
    setNuevaUnidadEquiv(12);
    setNuevaUnidadDesc('');
    try {
      const idProd = prod.id_producto || Number(prod.id);
      const units = await stockService.getUnidades(idProd);
      setUnidadesProd(units);
    } catch (e) {
      console.warn('Error al cargar unidades del producto', e);
      setUnidadesProd([]);
    }
  };

  const handleCrearUnidad = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedProdForUnidades || !nuevaUnidadNombre.trim() || nuevaUnidadEquiv <= 0) return;
    try {
      const idProd = selectedProdForUnidades.id_producto || Number(selectedProdForUnidades.id);
      await stockService.crearUnidad(idProd, {
        nombre_unidad: nuevaUnidadNombre.trim(),
        equivalencia_base: Number(nuevaUnidadEquiv),
        descripcion: nuevaUnidadDesc.trim() || undefined,
      });
      const units = await stockService.getUnidades(idProd);
      setUnidadesProd(units);
      setNuevaUnidadNombre('');
      setNuevaUnidadEquiv(12);
      setNuevaUnidadDesc('');
      alert('Unidad de medida y equivalencia registrada exitosamente.');
    } catch (e: any) {
      alert(e.message || 'Error al registrar unidad de medida.');
    }
  };

  const handleEliminarUnidad = async (idUnidad: number) => {
    if (!selectedProdForUnidades) return;
    if (!confirm('¿Desea eliminar esta unidad de medida?')) return;
    try {
      const idProd = selectedProdForUnidades.id_producto || Number(selectedProdForUnidades.id);
      await stockService.eliminarUnidad(idProd, idUnidad);
      const units = await stockService.getUnidades(idProd);
      setUnidadesProd(units);
    } catch (e: any) {
      alert(e.message || 'Error al eliminar unidad de medida.');
    }
  };

  // --- GESTIÓN DE UBICACIONES FÍSICAS ---
  const handleCrearUbicacion = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!nuevaUbicacionDesc.trim()) return;
    try {
      await stockService.crearUbicacion({ descripcion: nuevaUbicacionDesc.trim() });
      setNuevaUbicacionDesc('');
      await cargarUbicaciones();
      alert('Ubicación física creada exitosamente.');
    } catch (e: any) {
      alert(e.message || 'Error al registrar ubicación física.');
    }
  };

  // --- REGISTRO DE INGRESO DE MERCADERÍA ---
  const getTodayLocal = () => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  const handleOpenIngresoModal = () => {
    cargarLotes();
    if (proveedores.length > 0 && !ingresoProveedorId) {
      setIngresoProveedorId(proveedores[0].id_proveedor || Number(proveedores[0].id));
    }
    const prodsActivos = products.filter(isProdActivo);
    const defaultProd = prodsActivos.length > 0 ? prodsActivos[0] : null;
    const defaultProdId = defaultProd ? (defaultProd.id_producto !== undefined ? defaultProd.id_producto : defaultProd.id) : 0;
    const prodLotes = lotes.filter(l => 
      String(l.id_producto) === String(defaultProdId) ||
      (defaultProd && String(l.id_producto) === String(defaultProd.id)) ||
      (defaultProd && defaultProd.id_producto !== undefined && String(l.id_producto) === String(defaultProd.id_producto))
    );
    let initialLote = '';
    let initialVto = '';
    let initialMode: 'existente' | 'nuevo' = 'nuevo';
    if (prodLotes.length > 0) {
      initialLote = prodLotes[0].nro_lote;
      initialVto = prodLotes[0].fecha_vencimiento ? String(prodLotes[0].fecha_vencimiento).substring(0, 10) : '';
      initialMode = 'existente';
    }
    setIngresoItems([{ 
      id_producto: defaultProdId, 
      cantidad: 10, 
      nro_lote: initialLote, 
      fecha_vencimiento: initialVto,
      selected_mode: initialMode
    }]);
    setIngresoFecha(getTodayLocal());
    setShowIngresoModal(true);
  };

  const handleAddIngresoItem = () => {
    const prodsActivos = products.filter(isProdActivo);
    const defaultProd = prodsActivos.length > 0 ? prodsActivos[0] : null;
    const defaultProdId = defaultProd ? (defaultProd.id_producto !== undefined ? defaultProd.id_producto : defaultProd.id) : 0;
    const prodLotes = lotes.filter(l => 
      String(l.id_producto) === String(defaultProdId) ||
      (defaultProd && String(l.id_producto) === String(defaultProd.id)) ||
      (defaultProd && defaultProd.id_producto !== undefined && String(l.id_producto) === String(defaultProd.id_producto))
    );
    let initialLote = '';
    let initialVto = '';
    let initialMode: 'existente' | 'nuevo' = 'nuevo';
    if (prodLotes.length > 0) {
      initialLote = prodLotes[0].nro_lote;
      initialVto = prodLotes[0].fecha_vencimiento ? String(prodLotes[0].fecha_vencimiento).substring(0, 10) : '';
      initialMode = 'existente';
    }
    setIngresoItems([...ingresoItems, { 
      id_producto: defaultProdId, 
      cantidad: 1, 
      nro_lote: initialLote, 
      fecha_vencimiento: initialVto,
      selected_mode: initialMode
    }]);
  };

  const handleRemoveIngresoItem = (idx: number) => {
    if (ingresoItems.length <= 1) return;
    setIngresoItems(ingresoItems.filter((_, i) => i !== idx));
  };

  const handleGuardarIngreso = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!ingresoProveedorId) {
      alert('Debe seleccionar un proveedor.');
      return;
    }

    const hoy = getTodayLocal();
    if (!ingresoFecha) {
      alert('Debe indicar una fecha de recepción válida.');
      return;
    }
    if (ingresoFecha > hoy) {
      alert('No se pueden registrar ingresos con fechas posteriores a la fecha actual.');
      setIngresoFecha(hoy);
      return;
    }

    const itemsValidos = ingresoItems.filter(it => (it.id_producto !== 0 && it.id_producto !== '') && it.cantidad > 0);
    if (itemsValidos.length === 0) {
      alert('Debe ingresar al menos un producto con cantidad mayor a cero.');
      return;
    }

    for (const it of itemsValidos) {
      const p = products.find(prod => 
        String(prod.id) === String(it.id_producto) || 
        (prod.id_producto !== undefined && String(prod.id_producto) === String(it.id_producto))
      );
      if (p && isProdInactivo(p)) {
        alert(`El producto "${p.name}" se encuentra inactivo. No se pueden realizar acciones ni ingresos para productos inactivos.`);
        return;
      }
      if (!it.nro_lote || !it.nro_lote.trim()) {
        alert(`Debe indicar el número de lote para el producto "${p ? p.name : 'seleccionado'}". Todo ingreso de mercadería debe asociarse obligatoriamente a un lote.`);
        return;
      }
      if (!it.fecha_vencimiento) {
        alert(`Debe indicar la fecha de vencimiento del lote para el producto "${p ? p.name : 'seleccionado'}".`);
        return;
      }
    }

    try {
      await stockService.registrarIngreso({
        id_proveedor: Number(ingresoProveedorId),
        fecha: ingresoFecha || undefined,
        items: itemsValidos.map(it => ({
          id_producto: Number(it.id_producto),
          cantidad: Number(it.cantidad),
          nro_lote: it.nro_lote!.trim(),
          fecha_vencimiento: it.fecha_vencimiento!,
          motivo: 'Ingreso de mercadería',
        })),
      });
      alert('Ingreso de mercadería registrado exitosamente con lote asociado y stock actualizado.');
      setShowIngresoModal(false);
      await handleRefrescar();
    } catch (e: any) {
      alert(e.message || 'Error al registrar ingreso de mercadería.');
    }
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-300">
      
      {/* HEADER DE MÓDULO */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b-2 border-slate-200 pb-4">
        <div>
          <h1 className="text-3xl font-black text-slate-800 tracking-tight flex items-center gap-3">
            <Package className="w-8 h-8 text-blue-600" />
            <span>Módulo de Productos y Stock</span>
          </h1>
          <p className="text-sm font-semibold text-slate-500 mt-1">
            Gestión completa de Catálogo, Stock e Inventario, Lotes y Trazabilidad.
          </p>
        </div>

        {/* SELECTOR DE PESTAÑAS */}
        <div className="flex flex-wrap p-1 bg-slate-200/80 rounded-2xl gap-1">
          <button
            onClick={() => setActiveTab('CATALOGO')}
            className={`px-3.5 py-2 rounded-xl font-black text-xs flex items-center gap-1.5 transition-all cursor-pointer ${
              activeTab === 'CATALOGO' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <Tag className="w-4 h-4" />
            <span>Catálogo</span>
          </button>
          
          <button
            onClick={() => setActiveTab('STOCK')}
            className={`px-3.5 py-2 rounded-xl font-black text-xs flex items-center gap-1.5 transition-all cursor-pointer ${
              activeTab === 'STOCK' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <Package className="w-4 h-4" />
            <span>Stock y Alertas</span>
          </button>

          <button
            onClick={() => setActiveTab('LOTES')}
            className={`px-3.5 py-2 rounded-xl font-black text-xs flex items-center gap-1.5 transition-all cursor-pointer ${
              activeTab === 'LOTES' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <Calendar className="w-4 h-4" />
            <span>Lotes y Vencimientos</span>
          </button>

          <button
            onClick={() => setActiveTab('MOVIMIENTOS')}
            className={`px-3.5 py-2 rounded-xl font-black text-xs flex items-center gap-1.5 transition-all cursor-pointer ${
              activeTab === 'MOVIMIENTOS' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <History className="w-4 h-4" />
            <span>Movimientos</span>
          </button>

          <button
            onClick={() => setActiveTab('CONTEO')}
            className={`px-3.5 py-2 rounded-xl font-black text-xs flex items-center gap-1.5 transition-all cursor-pointer ${
              activeTab === 'CONTEO' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <ClipboardList className="w-4 h-4" />
            <span>Conteo Físico</span>
          </button>

          <button
            onClick={() => setActiveTab('DEVOLUCIONES')}
            className={`px-3.5 py-2 rounded-xl font-black text-xs flex items-center gap-1.5 transition-all cursor-pointer ${
              activeTab === 'DEVOLUCIONES' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <RotateCcw className="w-4 h-4" />
            <span>Devoluciones</span>
          </button>
        </div>
      </div>

      {/* ========================================================= */}
      {/* PESTAÑA 1: CATÁLOGO DE PRODUCTOS                         */}
      {/* ========================================================= */}
      {activeTab === 'CATALOGO' && (
        <div className="space-y-4">
          
          {/* 1. RESUMEN RÁPIDO DE CATÁLOGO */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3.5">
              <div className="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <Package className="w-5 h-5" />
              </div>
              <div>
                <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Catálogo</span>
                <span className="text-xl font-black text-slate-800">{products.length} artículos</span>
              </div>
            </div>

            <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3.5">
              <div className="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <Check className="w-5 h-5" />
              </div>
              <div>
                <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Artículos Activos</span>
                <span className="text-xl font-black text-emerald-700">
                  {products.filter(isProdActivo).length} activos
                </span>
              </div>
            </div>

            <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3.5">
              <div className="p-3 bg-rose-50 text-rose-600 rounded-xl">
                <ShieldAlert className="w-5 h-5" />
              </div>
              <div>
                <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Stock Crítico (≤ 5)</span>
                <span className="text-xl font-black text-rose-700">
                  {products.filter(p => p.stock <= 5).length} artículos
                </span>
              </div>
            </div>

            <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3.5">
              <div className="p-3 bg-purple-50 text-purple-600 rounded-xl">
                <MapPin className="w-5 h-5" />
              </div>
              <div>
                <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Ubicaciones Físicas</span>
                <span className="text-xl font-black text-purple-700">{ubicaciones.length} sectores</span>
              </div>
            </div>
          </div>

          {/* 2. BARRA DE GESTIÓN Y ACCIONES */}
          <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
            <div>
              <h2 className="text-lg font-black text-slate-800 tracking-tight flex items-center gap-2.5">
                <span>Gestión de Catálogo y Artículos</span>
                <span className="text-xs font-black bg-blue-50 text-blue-700 border border-blue-200/80 px-2.5 py-0.5 rounded-full">
                  {filteredProducts.length} {filteredProducts.length === 1 ? 'producto' : 'productos'}
                </span>
              </h2>
              <p className="text-xs text-slate-500 mt-0.5 font-medium">
                Altas de productos, ingresos de mercadería, ajustes de precios y administración de marcas/categorías.
              </p>
            </div>

            <div className="flex flex-wrap items-center gap-2 w-full md:w-auto">
              {/* ACCIONES OPERATIVAS PRIMARIAS */}
              <button
                onClick={handleOpenAddProduct}
                className="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs rounded-xl flex items-center gap-1.5 shadow-sm cursor-pointer transition-colors"
              >
                <Plus className="w-4 h-4" />
                <span>Nuevo Producto</span>
              </button>

              <div className="hidden xl:block h-6 w-px bg-slate-200 mx-1" />

              {/* HERRAMIENTAS ADMINISTRATIVAS */}
              <button
                onClick={() => setShowAumentoMasivoModal(true)}
                className="px-3.5 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-bold text-xs rounded-xl flex items-center gap-1.5 cursor-pointer transition-colors"
                title="Aplicar aumento porcentual masivo de precios"
              >
                <TrendingUp className="w-4 h-4 text-emerald-600" />
                <span>Aumento Masivo</span>
              </button>

              <button
                onClick={() => setShowCategoriasModal(true)}
                className="px-3 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 font-bold text-xs rounded-xl flex items-center gap-1.5 cursor-pointer transition-colors"
              >
                <Tag className="w-4 h-4 text-blue-600" />
                <span>Categorías</span>
              </button>

              <button
                onClick={() => setShowMarcasModal(true)}
                className="px-3 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 font-bold text-xs rounded-xl flex items-center gap-1.5 cursor-pointer transition-colors"
              >
                <Star className="w-4 h-4 text-amber-500" />
                <span>Marcas</span>
              </button>

              <button
                onClick={() => setShowUbicacionesModal(true)}
                className="px-3 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 font-bold text-xs rounded-xl flex items-center gap-1.5 cursor-pointer transition-colors"
                title="Gestionar ubicaciones físicas del depósito"
              >
                <MapPin className="w-4 h-4 text-rose-500" />
                <span>Ubicaciones</span>
              </button>

              <button
                onClick={handleRefrescar}
                disabled={isRefreshing}
                className="p-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl cursor-pointer transition-colors"
                title="Refrescar catálogo y stock"
              >
                <RefreshCw className={`w-4 h-4 text-blue-600 ${isRefreshing ? 'animate-spin' : ''}`} />
              </button>
            </div>
          </div>

          {/* 3. PANEL DE BÚSQUEDA Y FILTRADO AVANZADO */}
          <div className="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
            {/* BUSCADOR */}
            <div className="relative flex-1 min-w-[240px]">
              <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
              <input
                type="text"
                placeholder="Buscar por código, nombre o descripción..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-9 pr-8 py-2 bg-slate-50/80 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
              />
              {searchTerm && (
                <button
                  onClick={() => setSearchTerm('')}
                  className="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 p-0.5 cursor-pointer"
                  title="Borrar búsqueda"
                >
                  <X className="w-3.5 h-3.5" />
                </button>
              )}
            </div>

            {/* FILTROS DESPLEGABLES */}
            <div className="flex flex-wrap items-center gap-2">
              <select
                value={selectedCategory}
                onChange={(e) => setSelectedCategory(e.target.value)}
                className="px-3 py-2 bg-slate-50/80 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 cursor-pointer focus:outline-none transition-colors"
              >
                <option value="Todas">Todas las categorías</option>
                {categorias.map(c => <option key={c.id_categoria} value={c.nombre}>{c.nombre}</option>)}
              </select>

              <select
                value={selectedMarca}
                onChange={(e) => setSelectedMarca(e.target.value)}
                className="px-3 py-2 bg-slate-50/80 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 cursor-pointer focus:outline-none transition-colors"
              >
                <option value="Todas">Todas las marcas</option>
                {marcas.map(m => <option key={m.id_marca} value={m.nombre}>{m.nombre}</option>)}
              </select>

              <select
                value={selectedEstado}
                onChange={(e) => setSelectedEstado(e.target.value)}
                className="px-3 py-2 bg-slate-50/80 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 cursor-pointer focus:outline-none transition-colors"
              >
                <option value="todos">Todos los estados</option>
                <option value="activo">Solo Activos</option>
                <option value="inactivo">Solo Inactivos</option>
              </select>

              {/* FILTRO POR RANGO DE STOCK DISPONIBLE */}
              <div className="flex items-center gap-1.5 bg-slate-50/80 border border-slate-200 rounded-xl px-2.5 py-1" title="Filtrar por rango de stock disponible">
                <span className="text-[11px] font-bold text-slate-500 whitespace-nowrap">Stock:</span>
                <input
                  type="number"
                  placeholder="Mín"
                  value={stockMinFilter}
                  onChange={(e) => setStockMinFilter(e.target.value)}
                  className="w-12 px-1.5 py-1 bg-white border border-slate-200 rounded text-xs font-bold text-center focus:outline-none"
                />
                <span className="text-slate-400 text-xs font-bold">-</span>
                <input
                  type="number"
                  placeholder="Máx"
                  value={stockMaxFilter}
                  onChange={(e) => setStockMaxFilter(e.target.value)}
                  className="w-12 px-1.5 py-1 bg-white border border-slate-200 rounded text-xs font-bold text-center focus:outline-none"
                />
                {(stockMinFilter !== '' || stockMaxFilter !== '') && (
                  <button
                    onClick={() => { setStockMinFilter(''); setStockMaxFilter(''); }}
                    className="text-slate-400 hover:text-rose-500 p-0.5 cursor-pointer"
                    title="Limpiar filtro de stock"
                  >
                    <X className="w-3 h-3" />
                  </button>
                )}
              </div>

              {/* BOTÓN RESTABLECER FILTROS */}
              {(searchTerm !== '' || selectedCategory !== 'Todas' || selectedMarca !== 'Todas' || selectedEstado !== 'todos' || stockMinFilter !== '' || stockMaxFilter !== '') && (
                <button
                  onClick={() => {
                    setSearchTerm('');
                    setSelectedCategory('Todas');
                    setSelectedMarca('Todas');
                    setSelectedEstado('todos');
                    setStockMinFilter('');
                    setStockMaxFilter('');
                  }}
                  className="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-bold flex items-center gap-1 cursor-pointer transition-colors"
                  title="Restablecer todos los filtros"
                >
                  <RotateCcw className="w-3 h-3" />
                  <span>Limpiar</span>
                </button>
              )}
            </div>
          </div>

          {/* 4. TABLA DE PRODUCTOS ESTILIZADA */}
          <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="bg-slate-50/90 border-b border-slate-200 text-slate-500 font-extrabold text-[11px] uppercase tracking-wider">
                  <tr>
                    <th className="p-4">Código</th>
                    <th className="p-4">Nombre / Descripción</th>
                    <th className="p-4">Categoría</th>
                    <th className="p-4">Marca</th>
                    <th className="p-4">Ubicación</th>
                    <th className="p-4">Precio Unitario</th>
                    <th className="p-4">Stock</th>
                    <th className="p-4">Estado</th>
                    <th className="p-4 text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 font-medium">
                  {filteredProducts.length === 0 ? (
                    <tr>
                      <td colSpan={9} className="p-12 text-center text-slate-400 font-bold">
                        <Package className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                        <span>No se encontraron productos con los filtros seleccionados.</span>
                      </td>
                    </tr>
                  ) : (
                    filteredProducts.map((p) => {
                      const isActivo = isProdActivo(p);
                      const compStock = getCommittedUnits(p);
                      const disponible = Math.max(0, p.stock - compStock);
                      const tieneUbicacion = !!p.ubicacion_nombre;

                      return (
                        <tr key={p.id} className="hover:bg-blue-50/20 transition-colors">
                          <td className="p-4">
                            <span className="font-mono font-black text-xs text-slate-700 bg-slate-100 px-2 py-1 rounded-md border border-slate-200/60 inline-block">
                              {p.code}
                            </span>
                          </td>
                          <td className="p-4">
                            <span className="font-bold text-slate-900 block">{p.name}</span>
                            {p.descripcion && p.descripcion !== p.name && (
                              <span className="text-xs text-slate-400 block truncate max-w-xs">{p.descripcion}</span>
                            )}
                          </td>
                          <td className="p-4 text-slate-600 font-medium">
                            <span className="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/60 text-xs">
                              {p.category || p.categoria_nombre || '-'}
                            </span>
                          </td>
                          <td className="p-4 text-slate-600 font-medium">
                            <span className="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/60 text-xs">
                              {p.marca_nombre || '-'}
                            </span>
                          </td>
                          <td className="p-4">
                            <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold border ${
                              tieneUbicacion
                                ? 'bg-rose-50 text-rose-700 border-rose-200/70'
                                : 'bg-slate-50 text-slate-500 border-slate-200/70'
                            }`}>
                              <MapPin className={`w-3 h-3 ${tieneUbicacion ? 'text-rose-500' : 'text-slate-400'}`} />
                              <span>{p.ubicacion_nombre || 'Sin asignar'}</span>
                            </span>
                          </td>
                          <td className="p-4 font-black text-emerald-600 text-sm">
                            ${p.price.toLocaleString()}
                          </td>
                          <td className="p-4">
                            <div className="flex items-baseline gap-1.5">
                              <span className="font-black text-slate-800 text-sm">{p.stock} u.</span>
                              <span className={`text-[11px] font-black ${disponible > 5 ? 'text-emerald-600' : 'text-rose-600'}`}>
                                (Disp: {disponible})
                              </span>
                            </div>
                            <span className="text-[10px] font-semibold text-slate-400 block mt-0.5">
                              Físico: {p.stock} | Comp: {compStock}
                              {(() => {
                                const prodLotes = lotes.filter(l => Number(l.id_producto) === (p.id_producto || Number(p.id)));
                                const prox = prodLotes
                                  .filter(l => (l.cantidad_actual > 0 || l.cantidad > 0) && l.fecha_vencimiento)
                                  .sort((a, b) => String(a.fecha_vencimiento).localeCompare(String(b.fecha_vencimiento)))[0];
                                return prox ? ` (Vto: ${String(prox.fecha_vencimiento).substring(0, 10)})` : '';
                              })()}
                            </span>
                          </td>
                          <td className="p-4">
                            <span className={`px-2.5 py-1 rounded-full text-xs font-black inline-flex items-center gap-1.5 ${
                              isActivo ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'
                            }`}>
                              <span className={`w-1.5 h-1.5 rounded-full ${isActivo ? 'bg-emerald-500' : 'bg-rose-500'}`} />
                              {isActivo ? 'Activo' : 'Inactivo'}
                            </span>
                          </td>
                          <td className="p-4 text-right">
                            <div className="flex justify-end items-center gap-1">
                              <button
                                onClick={() => {
                                  if (!isActivo) {
                                    alert('No se pueden gestionar unidades de medida sobre productos inactivos.');
                                    return;
                                  }
                                  handleAbrirUnidades(p);
                                }}
                                disabled={!isActivo}
                                className={`p-1.5 border rounded-lg text-xs font-bold transition-colors ${
                                  isActivo
                                    ? 'bg-slate-50 hover:bg-purple-50 text-slate-600 hover:text-purple-600 border-slate-200/70 cursor-pointer'
                                    : 'bg-slate-100 text-slate-300 border-slate-200 cursor-not-allowed opacity-50'
                                }`}
                                title={isActivo ? 'Unidades de Medida y Equivalencias' : 'Producto inactivo: gestión de unidades no permitida'}
                              >
                                <Layers className="w-4 h-4" />
                              </button>
                              <button
                                onClick={() => handleVerHistorialPrecios(p)}
                                className="p-1.5 bg-slate-50 hover:bg-blue-50 text-slate-600 hover:text-blue-600 border border-slate-200/70 rounded-lg text-xs font-bold cursor-pointer transition-colors"
                                title="Consultar Historial de Precios"
                              >
                                <History className="w-4 h-4" />
                              </button>
                              <button
                                onClick={() => handleOpenEditProduct(p)}
                                className="p-1.5 bg-slate-50 hover:bg-slate-100 text-slate-600 hover:text-slate-900 border border-slate-200/70 rounded-lg text-xs font-bold cursor-pointer transition-colors"
                                title="Modificar Producto"
                              >
                                <Edit className="w-4 h-4" />
                              </button>
                              <button
                                onClick={() => handleToggleEstado(p)}
                                className={`p-1.5 border rounded-lg text-xs font-bold cursor-pointer transition-colors ${
                                  isActivo 
                                    ? 'bg-rose-50/60 hover:bg-rose-100 text-rose-600 border-rose-200' 
                                    : 'bg-emerald-50/60 hover:bg-emerald-100 text-emerald-600 border-emerald-200'
                                }`}
                                title={isActivo ? 'Desactivar Producto' : 'Reactivar Producto'}
                              >
                                <Archive className="w-4 h-4" />
                              </button>
                            </div>
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>

            {/* PIE DE TABLA / RESUMEN */}
            {filteredProducts.length > 0 && (
              <div className="p-3.5 bg-slate-50/80 border-t border-slate-200 text-xs font-bold text-slate-500 flex flex-col sm:flex-row justify-between items-center gap-2">
                <span>
                  Mostrando <strong className="text-slate-800">{filteredProducts.length}</strong> de <strong className="text-slate-800">{products.length}</strong> productos registrados
                </span>
                <span className="text-[11px] text-slate-400">
                  Precios e inventario sincronizados en tiempo real
                </span>
              </div>
            )}
          </div>
        </div>
      )}

      {/* ========================================================= */}
      {/* PESTAÑA 2: CONTROL DE STOCK Y ALERTAS                     */}
      {/* ========================================================= */}
      {activeTab === 'STOCK' && (
        <div className="space-y-4">
          {/* BARRA SUPERIOR DE STOCK Y ACCIÓN DE INGRESO */}
          <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
            <div>
              <h2 className="text-lg font-black text-slate-800 tracking-tight flex items-center gap-2">
                <Package className="w-5 h-5 text-blue-600" />
                <span>Control de Stock y Alertas de Inventario</span>
              </h2>
              <p className="text-xs text-slate-500 mt-0.5 font-medium">
                Monitoreo de disponibilidad física y registro de ingresos de mercadería de proveedores.
              </p>
            </div>

            <button
              onClick={handleOpenIngresoModal}
              className="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-black text-xs rounded-xl flex items-center gap-1.5 shadow-sm cursor-pointer transition-colors"
              title="Registrar ingreso de mercadería de proveedores"
            >
              <Truck className="w-4 h-4" />
              <span>+ Ingreso Mercadería</span>
            </button>
          </div>

          {/* SEMÁFORO DE STOCK Y FILTROS POR NIVEL */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
            <button
              onClick={() => setStockLevelFilter('TODOS')}
              className={`p-4 rounded-2xl border text-left cursor-pointer transition-all ${
                stockLevelFilter === 'TODOS' ? 'bg-blue-50/50 border-blue-300 ring-2 ring-blue-500/20' : 'bg-white border-slate-200'
              }`}
            >
              <span className="text-xs font-bold text-slate-500 uppercase block">Total Productos</span>
              <span className="text-2xl font-black text-slate-900 mt-1 block">{products.length}</span>
            </button>

            <button
              onClick={() => setStockLevelFilter('NORMAL')}
              className={`p-4 rounded-2xl border text-left cursor-pointer transition-all ${
                stockLevelFilter === 'NORMAL' ? 'bg-emerald-50 border-emerald-300 ring-2 ring-emerald-500/20' : 'bg-white border-slate-200'
              }`}
            >
              <span className="text-xs font-bold text-emerald-600 uppercase flex items-center gap-1 block">
                <span className="w-2 h-2 rounded-full bg-emerald-500" />
                Stock Normal (Verde)
              </span>
              <span className="text-2xl font-black text-emerald-700 mt-1 block">
                {products.filter(p => p.stock > p.minStock).length}
              </span>
            </button>

            <button
              onClick={() => setStockLevelFilter('BAJO')}
              className={`p-4 rounded-2xl border text-left cursor-pointer transition-all ${
                stockLevelFilter === 'BAJO' ? 'bg-amber-50 border-amber-300 ring-2 ring-amber-500/20' : 'bg-white border-slate-200'
              }`}
            >
              <span className="text-xs font-bold text-amber-600 uppercase flex items-center gap-1 block">
                <span className="w-2 h-2 rounded-full bg-amber-500" />
                Stock Bajo (Amarillo)
              </span>
              <span className="text-2xl font-black text-amber-700 mt-1 block">
                {products.filter(p => p.stock > 5 && p.stock <= p.minStock).length}
              </span>
            </button>

            <button
              onClick={() => setStockLevelFilter('CRITICO')}
              className={`p-4 rounded-2xl border text-left cursor-pointer transition-all ${
                stockLevelFilter === 'CRITICO' ? 'bg-rose-50 border-rose-300 ring-2 ring-rose-500/20' : 'bg-white border-slate-200'
              }`}
            >
              <span className="text-xs font-bold text-rose-600 uppercase flex items-center gap-1 block">
                <span className="w-2 h-2 rounded-full bg-rose-500 animate-pulse" />
                Stock Crítico (Rojo)
              </span>
              <span className="text-2xl font-black text-rose-700 mt-1 block">
                {products.filter(p => p.stock <= 5).length}
              </span>
            </button>
          </div>

          {/* TABLA DE STOCK DISPONIBLE */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 text-slate-500 font-extrabold text-[11px] uppercase">
                  <tr>
                    <th className="p-4">Producto</th>
                    <th className="p-4 text-center">Físico</th>
                    <th 
                      className="p-4 text-center cursor-help"
                      title="Stock comprometido en pedidos de clientes pendientes de entrega. Stock Disponible = Físico - Comprometido"
                    >
                      <div className="inline-flex items-center justify-center gap-1">
                        <span>Comprometido</span>
                        <Info className="w-3.5 h-3.5 text-slate-400" />
                      </div>
                    </th>
                    <th className="p-4 text-center">Disponible</th>
                    <th className="p-4 text-center">Stock Mínimo</th>
                    <th className="p-4">Estado Visual</th>
                    <th className="p-4 text-right">Ajuste Manual</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 font-medium">
                  {filteredProducts.map((p) => {
                    const isCritico = p.stock <= 5;
                    const isBajo = !isCritico && p.stock <= p.minStock;
                    const prodLotes = lotes.filter(l => Number(l.id_producto) === (p.id_producto || Number(p.id)));
                    const proxLote = prodLotes
                      .filter(l => (l.cantidad_actual > 0 || l.cantidad > 0) && l.fecha_vencimiento)
                      .sort((a, b) => String(a.fecha_vencimiento).localeCompare(String(b.fecha_vencimiento)))[0];
                    const compStock = getCommittedUnits(p);
                    return (
                      <tr key={p.id} className="hover:bg-slate-50">
                        <td className="p-4">
                          <span className="font-black text-slate-800 block">{p.name}</span>
                          <div className="flex items-center gap-1.5 mt-0.5">
                            <span className="text-xs text-slate-400">{p.code}</span>
                            {proxLote && (
                              <span 
                                className="text-[10px] font-semibold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200/50 inline-flex items-center gap-1"
                                title={`Lote activo: ${proxLote.nro_lote} (${proxLote.cantidad_actual ?? proxLote.cantidad ?? 0} u.). Vencimiento: ${String(proxLote.fecha_vencimiento).substring(0, 10)}`}
                              >
                                <Clock className="w-2.5 h-2.5 text-indigo-500" />
                                Lote: {proxLote.nro_lote} (Vto: {String(proxLote.fecha_vencimiento).substring(0, 10)})
                              </span>
                            )}
                          </div>
                        </td>
                        <td className="p-4 text-center font-black text-base text-slate-900">{p.stock} u.</td>
                        <td className="p-4 text-center">
                          <span className={`font-bold text-base block ${compStock > 0 ? 'text-amber-600' : 'text-slate-400'}`}>
                            {compStock} u.
                          </span>
                          {compStock > 0 ? (
                            <span 
                              className="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded-md border border-amber-200 mt-0.5"
                              title="Unidades reservadas en pedidos pendientes de entrega a clientes"
                            >
                              Pedidos activos
                            </span>
                          ) : (
                            <span className="text-[10px] text-slate-400 block mt-0.5">Sin pedidos</span>
                          )}
                        </td>
                        <td className="p-4 text-center font-black text-base text-emerald-600">{Math.max(0, p.stock - compStock)} u.</td>
                        <td className="p-4 text-center text-slate-500">{p.minStock} u.</td>
                        <td className="p-4">
                          <span className={`px-3 py-1 rounded-full text-xs font-black inline-flex items-center gap-1.5 ${
                            isCritico ? 'bg-rose-100 text-rose-800' : isBajo ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'
                          }`}>
                            <span className={`w-2 h-2 rounded-full ${
                              isCritico ? 'bg-rose-600' : isBajo ? 'bg-amber-600' : 'bg-emerald-600'
                            }`} />
                            {isCritico ? 'CRÍTICO' : isBajo ? 'BAJO' : 'SUFICIENTE'}
                          </span>
                        </td>
                        <td className="p-4 text-right">
                          {isProdInactivo(p) ? (
                            <span
                              className="px-3.5 py-1.5 bg-slate-100 text-slate-400 font-bold rounded-xl text-xs cursor-not-allowed inline-block"
                              title="Producto inactivo: no se permiten ajustes de stock"
                            >
                              Inactivo
                            </span>
                          ) : (
                            <button
                              onClick={() => {
                                setAdjProduct(p);
                                setAdjQuantity(1);
                                setShowAdjustmentModal(true);
                              }}
                              className="px-3.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 font-black rounded-xl text-xs cursor-pointer"
                            >
                              Ajustar Stock
                            </button>
                          )}
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

      {/* ========================================================= */}
      {/* PESTAÑA 3: LOTES Y VENCIMIENTOS                           */}
      {/* ========================================================= */}
      {activeTab === 'LOTES' && (
        <div className="space-y-6">
          <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-3">
            <div>
              <h3 className="text-base font-black text-slate-800 flex items-center gap-2">
                <Calendar className="w-5 h-5 text-indigo-600" />
                <span>Trazabilidad de Lotes y Control de Vencimientos</span>
              </h3>
              <p className="text-xs text-slate-500">Alertas automáticas de vencimiento y ordenamiento configurable por días.</p>
            </div>
            <div className="text-xs font-semibold text-slate-500 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200 flex items-center gap-1.5 shrink-0">
              <Info className="w-4 h-4 text-indigo-500 shrink-0" />
              <span>Los lotes se registran en <strong>Ingreso de Mercadería</strong></span>
            </div>
          </div>

          {/* BARRA DE FILTROS Y ALERTAS DE LOTES */}
          <div className="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
            <div className="flex flex-wrap items-center gap-2.5">
              <div className="relative min-w-[200px]">
                <Search className="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" />
                <input
                  type="text"
                  placeholder="Buscar lote o producto..."
                  value={loteSearchTerm}
                  onChange={(e) => setLoteSearchTerm(e.target.value)}
                  className="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold"
                />
              </div>

              <label className="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-bold cursor-pointer hover:bg-slate-100">
                <input
                  type="checkbox"
                  checked={loteSoloPorVencer}
                  onChange={(e) => setLoteSoloPorVencer(e.target.checked)}
                  className="rounded text-indigo-600"
                />
                <span className={loteSoloPorVencer ? 'text-indigo-700' : 'text-slate-600'}>
                  Solo próximos a vencer
                </span>
              </label>

              {loteSoloPorVencer && (
                <div className="flex items-center gap-1.5 bg-indigo-50 border border-indigo-200 rounded-xl px-2.5 py-1">
                  <span className="font-bold text-indigo-800">Plazo:</span>
                  <select
                    value={loteDiasVencer}
                    onChange={(e) => setLoteDiasVencer(Number(e.target.value))}
                    className="bg-white border border-indigo-200 rounded px-2 py-0.5 font-bold text-indigo-700 text-xs"
                  >
                    <option value={15}>Próximos 15 días</option>
                    <option value={30}>Próximos 30 días</option>
                    <option value={60}>Próximos 60 días</option>
                    <option value={90}>Próximos 90 días</option>
                  </select>
                </div>
              )}
            </div>

            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => setLoteSortAsc(!loteSortAsc)}
                className="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-xl font-bold text-slate-700 flex items-center gap-1 cursor-pointer"
                title="Cambiar orden por fecha de vencimiento"
              >
                <ArrowUpDown className="w-3.5 h-3.5 text-indigo-600" />
                <span>{loteSortAsc ? 'Vencen primero (ASC)' : 'Más lejanos (DESC)'}</span>
              </button>
            </div>
          </div>

          {/* Aclaración sobre Stock del Lote */}
          <div className="bg-gradient-to-r from-indigo-50/90 to-blue-50/90 border border-indigo-200/80 rounded-2xl p-3.5 text-xs flex items-start gap-3 shadow-xs">
            <Info className="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" />
            <div className="text-slate-600 leading-relaxed">
              <strong className="text-indigo-950 block mb-0.5">💡 ¿Qué significa la columna "Stock del Lote"?</strong>
              Un producto puede ingresar en distintas partidas o remesas de compra. Esta columna muestra las <strong>unidades físicas remanentes que aún quedan disponibles de ese lote específico</strong> en el depósito (con su vencimiento particular), a diferencia del stock global del producto que agrupa la suma de todos sus lotes.
            </div>
          </div>

          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 text-slate-500 font-extrabold text-[11px] uppercase">
                  <tr>
                    <th className="p-4">N° de Lote</th>
                    <th className="p-4">Producto</th>
                    <th className="p-4 text-center">
                      <div className="flex flex-col items-center">
                        <span className="font-black text-indigo-950">Stock del Lote</span>
                        <span className="text-[9px] font-semibold text-slate-500 normal-case">(u. disponibles de esta tanda)</span>
                      </div>
                    </th>
                    <th className="p-4">Fecha de Vencimiento</th>
                    <th className="p-4 text-center">Días Restantes</th>
                    <th className="p-4">Estado de Criticidad</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 font-medium">
                  {filteredLotes.length === 0 ? (
                    <tr>
                      <td colSpan={6} className="p-8 text-center text-slate-400 font-bold">
                        No se encontraron lotes con los filtros seleccionados.
                      </td>
                    </tr>
                  ) : (
                    filteredLotes.map((lote) => {
                      const vtoDate = lote.fecha_vencimiento ? new Date(lote.fecha_vencimiento) : null;
                      const hoy = new Date();
                      hoy.setHours(0, 0, 0, 0);
                      const diffDays = vtoDate ? Math.ceil((vtoDate.getTime() - hoy.getTime()) / (1000 * 60 * 60 * 24)) : null;
                      const isVencido = diffDays !== null && diffDays < 0;
                      const isCritico = diffDays !== null && diffDays >= 0 && diffDays <= 15;
                      const isProximo = diffDays !== null && diffDays > 15 && diffDays <= 30;

                      const prodLabel = lote.producto_nombre ||
                        (lote.codigo_producto && lote.descripcion_producto
                          ? `${lote.codigo_producto} - ${lote.descripcion_producto}`
                          : (lote.descripcion_producto || lote.codigo_producto || `Producto #${lote.id_producto}`));

                      const cantidadActual = lote.cantidad_actual !== undefined && lote.cantidad_actual !== null
                        ? Number(lote.cantidad_actual)
                        : (lote.cantidad !== undefined && lote.cantidad !== null ? Number(lote.cantidad) : 0);

                      const cantidadInicial = lote.cantidad_inicial !== undefined && lote.cantidad_inicial !== null
                        ? Number(lote.cantidad_inicial)
                        : null;

                      const prod = products.find(p => (p.id_producto || Number(p.id)) === Number(lote.id_producto));
                      const compStock = prod ? getCommittedUnits(prod) : 0;

                      return (
                        <tr key={lote.id_lote || lote.id} className="hover:bg-slate-50">
                          <td className="p-4 font-black text-slate-800">{lote.nro_lote}</td>
                          <td className="p-4 font-bold text-slate-700">{prodLabel}</td>
                          <td className="p-4 text-center font-black">
                            <span className={`text-sm ${cantidadActual === 0 ? 'text-rose-600 font-bold' : 'text-slate-900 font-black'}`}>
                              {cantidadActual} u.
                            </span>
                            <span className="block text-[10px] font-medium text-slate-400">
                              {cantidadActual === 0
                                ? (cantidadInicial !== null ? `Agotado (de ${cantidadInicial} u.)` : 'Agotado')
                                : (cantidadInicial !== null && cantidadInicial !== cantidadActual
                                  ? `de ${cantidadInicial} u. iniciales`
                                  : 'disponibles')}
                            </span>
                            {compStock > 0 && (
                              <span 
                                className="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded-md border border-amber-200 mt-1 shadow-2xs" 
                                title={`${compStock} u. de este producto comprometidas para pedidos con vencimiento ${lote.fecha_vencimiento}`}
                              >
                                Comprometido: {compStock} u.
                              </span>
                            )}
                          </td>
                          <td className="p-4 text-slate-600 font-semibold">{lote.fecha_vencimiento}</td>
                          <td className="p-4 text-center">
                            {diffDays === null ? (
                              <span className="text-slate-400 font-semibold">-</span>
                            ) : isVencido ? (
                              <span className="text-rose-700 font-black">Hace {Math.abs(diffDays)} d</span>
                            ) : (
                              <span className={`font-black ${isCritico ? 'text-rose-600' : isProximo ? 'text-amber-600' : 'text-emerald-600'}`}>
                                {diffDays} días
                              </span>
                            )}
                          </td>
                          <td className="p-4">
                            {cantidadActual === 0 ? (
                              <span className="px-2.5 py-1 bg-slate-100 text-slate-600 border border-slate-300 rounded-full text-xs font-black inline-flex items-center gap-1">
                                <span className="w-1.5 h-1.5 rounded-full bg-slate-400" />
                                CONSUMIDO / AGOTADO
                              </span>
                            ) : isVencido ? (
                              <span className="px-2.5 py-1 bg-rose-100 text-rose-800 border border-rose-300 rounded-full text-xs font-black inline-flex items-center gap-1">
                                <span className="w-1.5 h-1.5 rounded-full bg-rose-600" />
                                VENCIDO
                              </span>
                            ) : isCritico ? (
                              <span className="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full text-xs font-black inline-flex items-center gap-1">
                                <span className="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping" />
                                CRÍTICO (≤15d)
                              </span>
                            ) : isProximo ? (
                              <span className="px-2.5 py-1 bg-amber-100 text-amber-800 border border-amber-300 rounded-full text-xs font-black inline-flex items-center gap-1">
                                <span className="w-1.5 h-1.5 rounded-full bg-amber-500" />
                                PRÓXIMO A VENCER
                              </span>
                            ) : (
                              <span className="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-xs font-black inline-flex items-center gap-1">
                                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                                VIGENTE
                              </span>
                            )}
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
      {/* PESTAÑA 4: HISTORIAL DE MOVIMIENTOS                       */}
      {/* ========================================================= */}
      {activeTab === 'MOVIMIENTOS' && (
        <div className="space-y-6">
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
              <div>
                <h3 className="font-black text-slate-800 text-base flex items-center gap-2">
                  <History className="w-5 h-5 text-blue-600" />
                  <span>Historial de Movimientos de Stock (Kardex)</span>
                </h3>
                <p className="text-xs text-slate-500">Trazabilidad detallada con fecha, hora completa y usuario responsable.</p>
              </div>

              {/* FILTRO POR PRODUCTO */}
              <div className="flex items-center gap-2">
                <span className="text-xs font-bold text-slate-500 whitespace-nowrap">Filtrar por Producto:</span>
                <select
                  value={movimientoProdFilter}
                  onChange={(e) => setMovimientoProdFilter(e.target.value)}
                  className="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold"
                >
                  <option value="todos">Todos los productos</option>
                  {products.map(p => (
                    <option key={p.id} value={p.id_producto || p.id}>
                      {p.code} - {p.name}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 text-slate-500 font-extrabold text-[11px] uppercase">
                  <tr>
                    <th className="p-4">Fecha y Hora</th>
                    <th className="p-4">Producto</th>
                    <th className="p-4">Tipo</th>
                    <th className="p-4 text-center">Cantidad</th>
                    <th className="p-4">Motivo</th>
                    <th className="p-4">Usuario Responsable</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 font-medium">
                  {filteredMovimientos.length === 0 ? (
                    <tr>
                      <td colSpan={6} className="p-8 text-center text-slate-400 font-bold">
                        No hay movimientos registrados con los filtros seleccionados.
                      </td>
                    </tr>
                  ) : (
                    filteredMovimientos.map((m, idx) => {
                      const isDevolucionProveedor = m.type === 'Devolución' && (m.reason?.toLowerCase().includes('proveedor'));
                      const isPositive = m.type === 'Ingreso' || m.type === 'Entrada' || (m.type === 'Ajuste' && m.quantity > 0) || (m.type === 'Devolución' && !isDevolucionProveedor);
                      const tipoLabel = m.type === 'Devolución'
                        ? (isDevolucionProveedor ? 'Devolución Proveedor (-)' : 'Devolución Cliente (+)')
                        : m.type;
                      return (
                        <tr key={m.id || idx} className="hover:bg-slate-50">
                          <td className="p-4 text-slate-500 font-semibold text-xs whitespace-nowrap">{m.date}</td>
                          <td className="p-4 font-black text-slate-800">{m.productName}</td>
                          <td className="p-4">
                            <span className={`px-2.5 py-1 rounded-full text-xs font-black ${
                              isPositive ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'
                            }`}>
                              {tipoLabel}
                            </span>
                          </td>
                          <td className="p-4 text-center font-black text-slate-900">
                            {m.quantity} {m.unit || 'u.'}
                          </td>
                          <td className="p-4 text-slate-600 text-xs">{m.reason}</td>
                          <td className="p-4 font-bold text-slate-700 text-xs">
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 rounded-md">
                              {m.user}
                            </span>
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
      {/* PESTAÑA 5: CONTEO FÍSICO DE INVENTARIO                    */}
      {/* ========================================================= */}
      {activeTab === 'CONTEO' && (
        <div className="space-y-6">
          <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm max-w-xl mx-auto space-y-4">
            <h3 className="text-xl font-black text-slate-800 flex items-center gap-2">
              <ClipboardList className="w-6 h-6 text-blue-600" />
              <span>Conteo Físico y Conciliación</span>
            </h3>
            <p className="text-xs text-slate-500">
              Permite auditar el stock físico real y contrastarlo con el saldo en sistema, calculando faltantes o sobrantes.
            </p>

            <div className="space-y-3">
              <div>
                <label className="text-xs font-bold text-slate-500 uppercase">Seleccionar Producto</label>
                <select
                  value={conteoProd?.id || ''}
                  onChange={(e) => {
                    const p = products.find(prod => prod.id === e.target.value);
                    if (p) {
                      setConteoProd(p);
                      setConteoCantidadFisica(p.stock);
                    }
                  }}
                  className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                >
                  <option value="">Seleccione producto</option>
                  {products.filter(isProdActivo).map(p => (
                    <option key={p.id} value={p.id}>{p.name} (Sistema: {p.stock} u.)</option>
                  ))}
                </select>
              </div>

              {conteoProd && (
                <>
                  <div className="grid grid-cols-2 gap-3 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <div>
                      <span className="text-xs text-slate-400 font-bold block">Stock en Sistema</span>
                      <span className="text-2xl font-black text-slate-800">{conteoProd.stock} u.</span>
                    </div>
                    <div>
                      <span className="text-xs text-slate-400 font-bold block">Diferencia Calculada</span>
                      <span className={`text-2xl font-black ${
                        conteoCantidadFisica - conteoProd.stock === 0 
                          ? 'text-slate-700' 
                          : conteoCantidadFisica - conteoProd.stock > 0 
                          ? 'text-emerald-600' 
                          : 'text-rose-600'
                      }`}>
                        {conteoCantidadFisica - conteoProd.stock > 0 ? `+${conteoCantidadFisica - conteoProd.stock}` : conteoCantidadFisica - conteoProd.stock} u.
                      </span>
                    </div>
                  </div>

                  <div>
                    <label className="text-xs font-bold text-slate-500 uppercase">Cantidad Física Contada</label>
                    <input
                      type="number"
                      min={0}
                      value={conteoCantidadFisica}
                      onChange={(e) => setConteoCantidadFisica(Number(e.target.value))}
                      className="w-full mt-1 px-3 py-2 bg-white border border-slate-200 rounded-xl text-lg font-black text-center"
                    />
                  </div>

                  <div>
                    <label className="text-xs font-bold text-slate-500 uppercase">Observaciones del Conteo</label>
                    <input
                      type="text"
                      placeholder="Ej: Auditoría quincenal depósito pasillo 3"
                      value={conteoObs}
                      onChange={(e) => setConteoObs(e.target.value)}
                      className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold"
                    />
                  </div>

                  <button
                    onClick={handleGuardarConteo}
                    className="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-black rounded-2xl shadow-md cursor-pointer"
                  >
                    Confirmar Conciliación de Inventario
                  </button>
                </>
              )}
            </div>
          </div>
        </div>
      )}

      {/* ========================================================= */}
      {/* PESTAÑA 6: DEVOLUCIONES AL DEPÓSITO                       */}
      {/* ========================================================= */}
      {activeTab === 'DEVOLUCIONES' && (() => {
        const selectedDevProd = products.find(p => p.id === devProdId || String(p.id_producto) === devProdId);
        const stockInsuficiente = devTipo === 'proveedor' && !!selectedDevProd && devCantidad > selectedDevProd.stock;

        return (
          <div className="space-y-6">
            <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm max-w-xl mx-auto space-y-4">
              <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 className="text-xl font-black text-slate-800 flex items-center gap-2">
                  <RotateCcw className="w-6 h-6 text-amber-500" />
                  <span>Registro de Devoluciones al Depósito</span>
                </h3>
              </div>

              {/* Tarjeta explicativa de impacto de Devolución */}
              <div className={`p-4 rounded-2xl border text-xs flex items-start gap-3 transition-colors ${
                devTipo === 'cliente'
                  ? 'bg-emerald-50/90 border-emerald-200 text-emerald-950'
                  : 'bg-rose-50/90 border-rose-200 text-rose-950'
              }`}>
                <div className={`p-2 rounded-xl shrink-0 mt-0.5 ${devTipo === 'cliente' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`}>
                  {devTipo === 'cliente' ? <Plus className="w-4 h-4" /> : <RotateCcw className="w-4 h-4" />}
                </div>
                <div className="space-y-1">
                  <span className="font-black text-sm block">
                    {devTipo === 'cliente' ? 'Devolución de Cliente: Suma al Stock (+)' : 'Devolución a Proveedor: Descuenta del Stock (-)'}
                  </span>
                  <p className="text-xs leading-relaxed text-slate-700">
                    {devTipo === 'cliente'
                      ? 'El cliente regresa mercadería que le fue vendida previamente. Al reingresar físicamente al almacén, las unidades se suman al inventario disponible.'
                      : 'La distribuidora regresa mercadería (por falla, daño o devolución acordada) al proveedor donde se compró. Las unidades salen físicamente del almacén y se descuentan del inventario.'}
                  </p>
                </div>
              </div>

              <form onSubmit={handleGuardarDevolucion} className="space-y-4">
                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase">Producto Devuelto *</label>
                  <select
                    value={devProdId}
                    onChange={(e) => setDevProdId(e.target.value)}
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                    required
                  >
                    <option value="">Seleccione producto</option>
                    {products.filter(isProdActivo).map(p => (
                      <option key={p.id} value={p.id}>
                        {p.code} - {p.name} (Stock actual: {p.stock} u.)
                      </option>
                    ))}
                  </select>
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="text-xs font-bold text-slate-500 uppercase">Cantidad *</label>
                    <input
                      type="number"
                      min={1}
                      value={devCantidad}
                      onChange={(e) => setDevCantidad(Number(e.target.value))}
                      className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                      required
                    />
                  </div>
                  <div>
                    <label className="text-xs font-bold text-slate-500 uppercase">Tipo / Origen *</label>
                    <select
                      value={devTipo}
                      onChange={(e) => setDevTipo(e.target.value as any)}
                      className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                    >
                      <option value="cliente">Cliente (Reingreso → + Suma a Stock)</option>
                      <option value="proveedor">Proveedor (Salida → - Descuenta de Stock)</option>
                    </select>
                  </div>
                </div>

                {selectedDevProd && (
                  <div className="p-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs space-y-1.5">
                    <div className="flex justify-between items-center font-bold text-slate-700">
                      <span>Stock actual en depósito:</span>
                      <span>{selectedDevProd.stock} u.</span>
                    </div>
                    <div className="flex justify-between items-center font-bold">
                      <span>Impacto de esta operación:</span>
                      <span className={devTipo === 'cliente' ? 'text-emerald-600' : 'text-rose-600'}>
                        {devTipo === 'cliente' ? `+${devCantidad} u. (Entrada / Suma)` : `-${devCantidad} u. (Salida / Descuento)`}
                      </span>
                    </div>
                    <div className="flex justify-between items-center font-black pt-1 border-t border-slate-200 text-slate-900">
                      <span>Stock final proyectado:</span>
                      <span>
                        {devTipo === 'cliente'
                          ? selectedDevProd.stock + devCantidad
                          : Math.max(0, selectedDevProd.stock - devCantidad)} u.
                      </span>
                    </div>
                    {stockInsuficiente && (
                      <div className="p-2 bg-rose-100 text-rose-800 rounded-xl font-bold text-[11px] mt-1">
                        ⚠️ Stock insuficiente en depósito para realizar esta devolución al proveedor.
                      </div>
                    )}
                  </div>
                )}

                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase">Motivo de la Devolución *</label>
                  <input
                    type="text"
                    value={devMotivo}
                    onChange={(e) => setDevMotivo(e.target.value)}
                    placeholder="Ej: Embalaje dañado, fecha corta, mercadería defectuosa"
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold"
                    required
                  />
                </div>

                <button
                  type="submit"
                  disabled={stockInsuficiente}
                  className={`w-full py-3.5 text-white font-black rounded-2xl shadow-md cursor-pointer transition-colors flex items-center justify-center gap-2 ${
                    devTipo === 'cliente'
                      ? 'bg-emerald-600 hover:bg-emerald-700'
                      : 'bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed'
                  }`}
                >
                  {devTipo === 'cliente' ? (
                    <>
                      <Plus className="w-4 h-4" />
                      <span>Registrar Devolución de Cliente (+ Sumar {devCantidad} u. a Stock)</span>
                    </>
                  ) : (
                    <>
                      <RotateCcw className="w-4 h-4" />
                      <span>Registrar Devolución a Proveedor (- Descontar {devCantidad} u. de Stock)</span>
                    </>
                  )}
                </button>
              </form>
            </div>
          </div>
        );
      })()}

      {/* MODAL ALTA / EDICIÓN PRODUCTO */}
      {showProductModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div className="flex justify-between items-center border-b border-slate-100 pb-3 mb-4">
              <h3 className="text-xl font-black text-slate-800">
                {editingProduct ? `Modificar Producto - ${editingProduct.code}` : 'Registrar Nuevo Producto'}
              </h3>
              <button onClick={() => setShowProductModal(false)} className="text-slate-400 hover:text-slate-600">
                <X className="w-6 h-6" />
              </button>
            </div>

            {formError && (
              <div className="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold rounded-xl">
                {formError}
              </div>
            )}

            <form onSubmit={handleSaveProduct} className="space-y-4">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase">Código Único *</label>
                  <input
                    type="text"
                    value={pCode}
                    onChange={(e) => setPCode(e.target.value)}
                    placeholder="Ej: C-3301"
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold uppercase"
                    required
                  />
                </div>
                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase">Nombre Comercial *</label>
                  <input
                    type="text"
                    value={pName}
                    onChange={(e) => setPName(e.target.value)}
                    placeholder="Ej: Gaseosa Cola 2.25L"
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                    required
                  />
                </div>
              </div>

              <div className="grid grid-cols-3 gap-3">
                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase">Categoría</label>
                  <select
                    value={pCategoryId}
                    onChange={(e) => setPCategoryId(Number(e.target.value))}
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                  >
                    {categorias.map(c => <option key={c.id_categoria} value={c.id_categoria}>{c.nombre}</option>)}
                  </select>
                </div>
                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase">Marca</label>
                  <select
                    value={pMarcaId}
                    onChange={(e) => setPMarcaId(Number(e.target.value))}
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                  >
                    {marcas.map(m => <option key={m.id_marca} value={m.id_marca}>{m.nombre}</option>)}
                  </select>
                </div>
                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase">Ubicación Física</label>
                  <select
                    value={pUbicacionId}
                    onChange={(e) => setPUbicacionId(e.target.value === '' ? '' : Number(e.target.value))}
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                  >
                    <option value="">(Sin asignar)</option>
                    {ubicaciones.map(u => (
                      <option key={u.id_ubicacion} value={u.id_ubicacion}>
                        {u.descripcion}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-3 gap-3">
                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase">Precio Unitario ($)</label>
                  <input
                    type="number"
                    step="0.01"
                    value={pPrice}
                    onChange={(e) => setPPrice(Number(e.target.value))}
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                    required
                  />
                </div>
                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase">Stock Inicial</label>
                  <input
                    type="number"
                    min={0}
                    value={pStock}
                    onChange={(e) => setPStock(Number(e.target.value))}
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                  />
                </div>
                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase">Stock Mínimo</label>
                  <input
                    type="number"
                    min={0}
                    value={pMinStock}
                    onChange={(e) => setPMinStock(Number(e.target.value))}
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                  />
                </div>
              </div>

              {editingProduct && (
                <div className="p-3.5 bg-slate-50 border border-slate-200/80 rounded-2xl space-y-1.5 text-xs">
                  <div className="font-extrabold text-slate-700 flex items-center gap-1.5 mb-1">
                    <Clock className="w-3.5 h-3.5 text-blue-600" />
                    <span>Auditoría y Registro</span>
                  </div>
                  <div className="flex flex-wrap items-center justify-between text-slate-600 font-medium">
                    <span>
                      <strong className="text-slate-700">Fecha y Hora de Alta:</strong> {formatDateTime(editingProduct.fecha_alta, 'Registrada en alta')}
                    </span>
                    <span>
                      <strong className="text-slate-700">Cargado por:</strong> {editingProduct.usuario_carga_nombre || (editingProduct.id_usuario_carga ? `Usuario #${editingProduct.id_usuario_carga}` : 'Don Alberto')}
                    </span>
                  </div>
                  {editingProduct.fecha_modificacion && (
                    <div className="flex flex-wrap items-center justify-between text-slate-600 font-medium pt-1 border-t border-slate-200/60">
                      <span>
                        <strong className="text-slate-700">Última Modificación:</strong> {formatDateTime(editingProduct.fecha_modificacion)}
                      </span>
                      <span>
                        <strong className="text-slate-700">Modificado por:</strong> {editingProduct.usuario_modificacion_nombre || 'Don Alberto'}
                      </span>
                    </div>
                  )}
                  {editingProduct.fecha_desactivacion && (
                    <div className="text-rose-600 font-bold pt-1 border-t border-slate-200/60">
                      <span>Desactivado el: {formatDateTime(editingProduct.fecha_desactivacion)}</span>
                    </div>
                  )}
                </div>
              )}

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onClick={() => setShowProductModal(false)} className="px-4 py-2 text-slate-500 font-bold text-xs">
                  Cancelar
                </button>
                <button type="submit" className="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs rounded-xl shadow-md">
                  Guardar Producto
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL AUMENTO MASIVO DE PRECIOS */}
      {showAumentoMasivoModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl">
            <h3 className="text-lg font-black text-slate-800 mb-1 flex items-center gap-2">
              <TrendingUp className="w-5 h-5 text-emerald-600" />
              <span>Aumento Masivo de Precios</span>
            </h3>
            <p className="text-xs text-slate-500 mb-4">
              Se actualizarán los precios y quedará registrado automáticamente en el historial de precios.
            </p>

            {aumError && <div className="p-2 mb-3 bg-rose-50 text-rose-600 text-xs font-bold rounded-lg">{aumError}</div>}

            <form onSubmit={handleEjecutarAumentoMasivo} className="space-y-3">
              <div>
                <label className="text-xs font-bold text-slate-500 uppercase">Porcentaje de Aumento (%)</label>
                <input
                  type="number"
                  step="0.1"
                  min={0.1}
                  value={aumPorcentaje}
                  onChange={(e) => setAumPorcentaje(Number(e.target.value))}
                  className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-black text-emerald-600 text-center text-lg"
                  required
                />
              </div>

              <div>
                <label className="text-xs font-bold text-slate-500 uppercase">Alcance del Aumento</label>
                <select
                  value={aumAlcance}
                  onChange={(e) => setAumAlcance(e.target.value as any)}
                  className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                >
                  <option value="TODOS">Todos los productos activos</option>
                  <option value="CATEGORIA">Por Categoría</option>
                  <option value="MARCA">Por Marca</option>
                </select>
              </div>

              {aumAlcance === 'CATEGORIA' && (
                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase">Seleccionar Categoría</label>
                  <select
                    value={aumCatId}
                    onChange={(e) => setAumCatId(Number(e.target.value))}
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                  >
                    {categorias.map(c => <option key={c.id_categoria} value={c.id_categoria}>{c.nombre}</option>)}
                  </select>
                </div>
              )}

              {aumAlcance === 'MARCA' && (
                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase">Seleccionar Marca</label>
                  <select
                    value={aumMarcaId}
                    onChange={(e) => setAumMarcaId(Number(e.target.value))}
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                  >
                    {marcas.map(m => <option key={m.id_marca} value={m.id_marca}>{m.nombre}</option>)}
                  </select>
                </div>
              )}

              <div className="flex justify-end gap-2 pt-3">
                <button type="button" onClick={() => setShowAumentoMasivoModal(false)} className="px-4 py-2 font-bold text-xs text-slate-500">
                  Cancelar
                </button>
                <button type="submit" className="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl shadow-md">
                  Aplicar Aumento Masivo
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL HISTORIAL DE PRECIOS */}
      {showHistorialPreciosModal && selectedProductForHistory && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-3xl max-w-3xl w-full p-6 shadow-2xl max-h-[85vh] overflow-y-auto">
            <div className="flex justify-between items-center border-b border-slate-100 pb-3 mb-3">
              <div>
                <h3 className="text-lg font-black text-slate-800 flex items-center gap-2">
                  <Clock className="w-5 h-5 text-blue-600" />
                  <span>Historial y Auditoría del Producto</span>
                </h3>
                <span className="text-xs font-bold text-slate-500">{selectedProductForHistory.name} ({selectedProductForHistory.code})</span>
              </div>
              <button onClick={() => setShowHistorialPreciosModal(false)} className="text-slate-400 hover:text-slate-600">
                <X className="w-6 h-6" />
              </button>
            </div>

            {/* PESTAÑAS DENTRO DEL MODAL */}
            <div className="flex gap-2 border-b border-slate-100 pb-3 mb-4">
              <button
                type="button"
                onClick={() => setHistoryModalTab('PRECIOS')}
                className={`px-4 py-2 rounded-xl text-xs font-black flex items-center gap-1.5 transition-all cursor-pointer ${
                  historyModalTab === 'PRECIOS'
                    ? 'bg-blue-600 text-white shadow'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                }`}
              >
                <DollarSign className="w-3.5 h-3.5" />
                <span>Historial de Precios</span>
                <span className={`px-1.5 py-0.2 rounded-full text-[10px] ${
                  historyModalTab === 'PRECIOS' ? 'bg-blue-700 text-white' : 'bg-slate-200 text-slate-700'
                }`}>
                  {historialPreciosData.length}
                </span>
              </button>

              <button
                type="button"
                onClick={() => setHistoryModalTab('CAMBIOS')}
                className={`px-4 py-2 rounded-xl text-xs font-black flex items-center gap-1.5 transition-all cursor-pointer ${
                  historyModalTab === 'CAMBIOS'
                    ? 'bg-blue-600 text-white shadow'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                }`}
              >
                <History className="w-3.5 h-3.5" />
                <span>Historial de Cambios / Auditoría</span>
                <span className={`px-1.5 py-0.2 rounded-full text-[10px] ${
                  historyModalTab === 'CAMBIOS' ? 'bg-blue-700 text-white' : 'bg-slate-200 text-slate-700'
                }`}>
                  {historialAuditoriaData.length}
                </span>
              </button>
            </div>

            {loadingHistory ? (
              <div className="p-8 text-center text-slate-400 font-bold">Cargando registros...</div>
            ) : historyModalTab === 'PRECIOS' ? (
              historialPreciosData.length === 0 ? (
                <div className="p-8 text-center text-slate-400 font-bold">No registra cambios históricos de precio aún.</div>
              ) : (
                <table className="w-full text-xs text-left">
                  <thead className="bg-slate-50 text-slate-500 font-black uppercase">
                    <tr>
                      <th className="p-3">Fecha y Hora</th>
                      <th className="p-3 text-center">Precio Anterior</th>
                      <th className="p-3 text-center">Precio Nuevo</th>
                      <th className="p-3 text-center">Variación</th>
                      <th className="p-3 text-right">Usuario Responsable</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 font-bold">
                    {historialPreciosData.map((h, i) => (
                      <tr key={i} className="hover:bg-slate-50">
                        <td className="p-3 text-slate-600 font-mono">{formatDateTime(h.fecha_cambio || h.fecha)}</td>
                        <td className="p-3 text-center text-slate-500 font-mono">
                          {h.precio_anterior !== null && h.precio_anterior !== undefined
                            ? `$${Number(h.precio_anterior).toLocaleString('es-AR', { minimumFractionDigits: 2 })}`
                            : '-'}
                        </td>
                        <td className="p-3 text-center text-slate-900 font-mono">
                          ${Number(h.precio_nuevo || h.precio || 0).toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                        </td>
                        <td className="p-3 text-center">
                          {(() => {
                            const varPct = h.porcentaje_variacion !== null && h.porcentaje_variacion !== undefined
                              ? Number(h.porcentaje_variacion)
                              : (h.precio_anterior && Number(h.precio_anterior) > 0
                                  ? ((Number(h.precio_nuevo) - Number(h.precio_anterior)) / Number(h.precio_anterior)) * 100
                                  : null);

                            if (varPct === null || isNaN(varPct)) {
                              return (
                                <span className="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black bg-blue-50 text-blue-700 border border-blue-200">
                                  Alta Inicial
                                </span>
                              );
                            }

                            if (varPct > 0) {
                              return (
                                <span className="inline-flex items-center gap-0.5 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                  <TrendingUp className="w-3 h-3" />
                                  +{varPct.toFixed(2)}%
                                </span>
                              );
                            }

                            if (varPct < 0) {
                              return (
                                <span className="inline-flex items-center gap-0.5 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-200">
                                  {varPct.toFixed(2)}%
                                </span>
                              );
                            }

                            return (
                              <span className="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                0.00%
                              </span>
                            );
                          })()}
                        </td>
                        <td className="p-3 text-right text-slate-700 font-extrabold">
                          {h.usuario_nombre || (h.id_usuario ? `Usuario #${h.id_usuario}` : 'Don Alberto')}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )
            ) : (
              historialAuditoriaData.length === 0 ? (
                <div className="p-8 text-center text-slate-400 font-bold">
                  No se registran modificaciones de datos para este producto aún.
                </div>
              ) : (
                <table className="w-full text-xs text-left">
                  <thead className="bg-slate-50 text-slate-500 font-black uppercase">
                    <tr>
                      <th className="p-3">Fecha y Hora</th>
                      <th className="p-3">Operación</th>
                      <th className="p-3">Campos Alterados</th>
                      <th className="p-3 text-right">Usuario Responsable</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 font-medium">
                    {historialAuditoriaData.map((log, i) => {
                      const accionLabel = log.accion === 'INSERT' 
                        ? 'Alta Inicial' 
                        : (log.accion === 'DELETE' ? 'Desactivación' : 'Modificación');
                      
                      const accionColor = log.accion === 'INSERT'
                        ? 'bg-blue-50 text-blue-700 border-blue-200'
                        : (log.accion === 'DELETE' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200');

                      const detalles: string[] = [];
                      if (log.valores_nuevos && log.valores_anteriores) {
                        Object.keys(log.valores_nuevos).forEach((key) => {
                          const ant = log.valores_anteriores[key];
                          const nue = log.valores_nuevos[key];
                          if (ant != nue && nue !== undefined) {
                            detalles.push(`${key}: "${ant ?? '-'}" ➔ "${nue}"`);
                          }
                        });
                      }

                      return (
                        <tr key={i} className="hover:bg-slate-50">
                          <td className="p-3 text-slate-600 font-mono text-[11px]">{formatDateTime(log.fecha_hora)}</td>
                          <td className="p-3">
                            <span className={`px-2 py-0.5 rounded-full text-[10px] font-black border ${accionColor}`}>
                              {accionLabel}
                            </span>
                          </td>
                          <td className="p-3 text-slate-700">
                            {detalles.length > 0 ? (
                              <ul className="list-disc list-inside space-y-0.5 text-[11px]">
                                {detalles.map((d, dIdx) => (
                                  <li key={dIdx} className="font-mono text-slate-800">{d}</li>
                                ))}
                              </ul>
                            ) : (
                              <span className="text-slate-400 italic text-[11px]">
                                {log.accion === 'INSERT' ? 'Registro inicial de datos' : 'Actualización de ficha'}
                              </span>
                            )}
                          </td>
                          <td className="p-3 text-right text-slate-800 font-black">
                            {log.usuario_nombre}
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              )
            )}
          </div>
        </div>
      )}

      {/* MODAL CATEGORÍAS */}
      {showCategoriasModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl">
            <div className="flex justify-between items-center border-b border-slate-100 pb-3 mb-4">
              <h3 className="text-lg font-black text-slate-800">Administrar Categorías</h3>
              <button onClick={() => setShowCategoriasModal(false)} className="text-slate-400 hover:text-slate-600">
                <X className="w-6 h-6" />
              </button>
            </div>

            <div className="flex gap-2 mb-4">
              <input
                type="text"
                placeholder="Nueva categoría..."
                value={nuevaCatNombre}
                onChange={(e) => setNuevaCatNombre(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') handleCrearCategoria();
                }}
                className="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
              />
              <button
                onClick={handleCrearCategoria}
                className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs rounded-xl shadow-sm cursor-pointer"
              >
                Agregar
              </button>
            </div>

            <div className="max-h-60 overflow-y-auto divide-y divide-slate-100 border border-slate-200 rounded-xl">
              {categorias.length === 0 ? (
                <div className="p-4 text-center text-xs text-slate-400 font-bold">No hay categorías registradas</div>
              ) : (
                categorias.map(c => (
                  <div key={c.id_categoria} className="p-3 flex justify-between items-center text-sm font-bold">
                    {editingCatId === c.id_categoria ? (
                      <div className="flex items-center gap-2 flex-1 mr-2">
                        <input
                          type="text"
                          value={editingCatNombre}
                          onChange={(e) => setEditingCatNombre(e.target.value)}
                          className="flex-1 px-2.5 py-1 text-xs bg-slate-50 border border-blue-400 rounded-lg focus:outline-none"
                          autoFocus
                          onKeyDown={(e) => {
                            if (e.key === 'Enter') handleGuardarEditarCategoria(c.id_categoria);
                            if (e.key === 'Escape') setEditingCatId(null);
                          }}
                        />
                        <button
                          onClick={() => handleGuardarEditarCategoria(c.id_categoria)}
                          className="p-1 text-emerald-600 hover:bg-emerald-50 rounded-lg cursor-pointer"
                          title="Guardar cambios"
                        >
                          <Check className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => setEditingCatId(null)}
                          className="p-1 text-slate-400 hover:bg-slate-100 rounded-lg cursor-pointer"
                          title="Cancelar"
                        >
                          <X className="w-4 h-4" />
                        </button>
                      </div>
                    ) : (
                      <>
                        <div className="flex items-center gap-2">
                          <span className="text-slate-800">{c.nombre}</span>
                          <span className="text-[10px] text-slate-400 font-normal">ID: {c.id_categoria}</span>
                        </div>
                        <div className="flex items-center gap-1">
                          <button
                            onClick={() => {
                              setEditingCatId(c.id_categoria);
                              setEditingCatNombre(c.nombre);
                            }}
                            className="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                            title="Editar categoría"
                          >
                            <Edit className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => handleEliminarCategoria(c)}
                            className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                            title="Eliminar categoría"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </>
                    )}
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      )}

      {/* MODAL MARCAS */}
      {showMarcasModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl">
            <div className="flex justify-between items-center border-b border-slate-100 pb-3 mb-4">
              <h3 className="text-lg font-black text-slate-800">Administrar Marcas</h3>
              <button onClick={() => setShowMarcasModal(false)} className="text-slate-400 hover:text-slate-600">
                <X className="w-6 h-6" />
              </button>
            </div>

            <div className="flex gap-2 mb-4">
              <input
                type="text"
                placeholder="Nueva marca..."
                value={nuevaMarcaNombre}
                onChange={(e) => setNuevaMarcaNombre(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') handleCrearMarca();
                }}
                className="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
              />
              <button
                onClick={handleCrearMarca}
                className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs rounded-xl shadow-sm cursor-pointer"
              >
                Agregar
              </button>
            </div>

            <div className="max-h-60 overflow-y-auto divide-y border border-slate-200 rounded-xl">
              {marcas.length === 0 ? (
                <div className="p-4 text-center text-xs text-slate-400 font-bold">No hay marcas registradas</div>
              ) : (
                marcas.map(m => (
                  <div key={m.id_marca} className="p-3 flex justify-between items-center text-sm font-bold">
                    {editingMarcaId === m.id_marca ? (
                      <div className="flex items-center gap-2 flex-1 mr-2">
                        <input
                          type="text"
                          value={editingMarcaNombre}
                          onChange={(e) => setEditingMarcaNombre(e.target.value)}
                          className="flex-1 px-2.5 py-1 text-xs bg-slate-50 border border-blue-400 rounded-lg focus:outline-none"
                          autoFocus
                          onKeyDown={(e) => {
                            if (e.key === 'Enter') handleGuardarEditarMarca(m.id_marca);
                            if (e.key === 'Escape') setEditingMarcaId(null);
                          }}
                        />
                        <button
                          onClick={() => handleGuardarEditarMarca(m.id_marca)}
                          className="p-1 text-emerald-600 hover:bg-emerald-50 rounded-lg cursor-pointer"
                          title="Guardar cambios"
                        >
                          <Check className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => setEditingMarcaId(null)}
                          className="p-1 text-slate-400 hover:bg-slate-100 rounded-lg cursor-pointer"
                          title="Cancelar"
                        >
                          <X className="w-4 h-4" />
                        </button>
                      </div>
                    ) : (
                      <>
                        <div className="flex items-center gap-2">
                          <span className="text-slate-800">{m.nombre}</span>
                          <span className="text-[10px] text-slate-400 font-normal">ID: {m.id_marca}</span>
                        </div>
                        <div className="flex items-center gap-1">
                          <button
                            onClick={() => {
                              setEditingMarcaId(m.id_marca);
                              setEditingMarcaNombre(m.nombre);
                            }}
                            className="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                            title="Editar marca"
                          >
                            <Edit className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => handleEliminarMarca(m)}
                            className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                            title="Eliminar marca"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </>
                    )}
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      )}

      {/* MODAL AJUSTE MANUAL */}
      {showAdjustmentModal && adjProduct && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl">
            <h3 className="text-lg font-black text-slate-800 mb-1">Ajuste Manual de Inventario</h3>
            <p className="text-xs text-slate-500 mb-3">{adjProduct.name} (Stock actual: {adjProduct.stock} u.)</p>

            <form onSubmit={handleGuardarAjuste} className="space-y-3">
              <div>
                <label className="text-xs font-bold text-slate-500 uppercase">Tipo de Ajuste</label>
                <div className="grid grid-cols-2 gap-2 mt-1">
                  <button
                    type="button"
                    onClick={() => setAdjType('Entrada')}
                    className={`py-2 rounded-xl font-black text-xs cursor-pointer border ${
                      adjType === 'Entrada' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-50 text-slate-600 border-slate-200'
                    }`}
                  >
                    Aumentar (＋)
                  </button>
                  <button
                    type="button"
                    onClick={() => setAdjType('Salida')}
                    className={`py-2 rounded-xl font-black text-xs cursor-pointer border ${
                      adjType === 'Salida' ? 'bg-rose-600 text-white border-rose-600' : 'bg-slate-50 text-slate-600 border-slate-200'
                    }`}
                  >
                    Disminuir (－)
                  </button>
                </div>
              </div>

              <div>
                <label className="text-xs font-bold text-slate-500 uppercase">Cantidad</label>
                <input
                  type="number"
                  min={1}
                  value={adjQuantity}
                  onChange={(e) => setAdjQuantity(Number(e.target.value))}
                  className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-black text-center"
                  required
                />
              </div>

              <div>
                <label className="text-xs font-bold text-slate-500 uppercase">Motivo del Ajuste *</label>
                <input
                  type="text"
                  value={adjReason}
                  onChange={(e) => setAdjReason(e.target.value)}
                  placeholder="Ej: Rotura en depósito, recuento físico"
                  className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold"
                  required
                />
              </div>

              <div className="flex justify-end gap-2 pt-3">
                <button type="button" onClick={() => setShowAdjustmentModal(false)} className="px-4 py-2 font-bold text-xs text-slate-500">
                  Cancelar
                </button>
                <button type="submit" className="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs rounded-xl shadow-md">
                  Confirmar Ajuste
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL REGISTRAR LOTE */}
      {showLoteModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl">
            <h3 className="text-lg font-black text-slate-800 mb-3">Registrar Lote de Mercadería</h3>
            <form onSubmit={handleCrearLote} className="space-y-3">
              <div>
                <label className="text-xs font-bold text-slate-500 uppercase">Producto</label>
                <select
                  value={loteProdId}
                  onChange={(e) => setLoteProdId(Number(e.target.value))}
                  className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                >
                  {products.filter(isProdActivo).map(p => (
                    <option key={p.id} value={p.id_producto || p.id}>{p.name}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="text-xs font-bold text-slate-500 uppercase">N° de Lote</label>
                <input
                  type="text"
                  value={loteNro}
                  onChange={(e) => setLoteNro(e.target.value)}
                  placeholder="Ej: LOTE-2026-X12"
                  className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase">Cantidad</label>
                  <input
                    type="number"
                    min={1}
                    value={loteCantidad}
                    onChange={(e) => setLoteCantidad(Number(e.target.value))}
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                  />
                </div>
                <div>
                  <div className="flex items-center justify-between">
                    <label className="text-xs font-bold text-slate-500 uppercase">Fecha Vencimiento *</label>
                    <span className="text-[10px] text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded font-semibold">Caducidad</span>
                  </div>
                  <input
                    type="date"
                    value={loteVto}
                    onChange={(e) => setLoteVto(e.target.value)}
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                    required
                  />
                  <p className="text-[11px] text-slate-500 mt-1">Fecha de caducidad del producto indicada por el fabricante.</p>
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-3">
                <button type="button" onClick={() => setShowLoteModal(false)} className="px-4 py-2 font-bold text-xs text-slate-500">
                  Cancelar
                </button>
                <button type="submit" className="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs rounded-xl shadow-md">
                  Guardar Lote
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL REGISTRAR INGRESO DE MERCADERÍA */}
      {showIngresoModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-3xl max-w-4xl w-full p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div className="flex justify-between items-center border-b border-slate-100 pb-3 mb-4">
              <h3 className="text-xl font-black text-slate-800 flex items-center gap-2">
                <Truck className="w-6 h-6 text-emerald-600" />
                <span>Registrar Ingreso de Mercadería</span>
              </h3>
              <button onClick={() => setShowIngresoModal(false)} className="text-slate-400 hover:text-slate-600 cursor-pointer">
                <X className="w-6 h-6" />
              </button>
            </div>

            {/* Guía explicativa para evitar confusiones de fechas */}
            <div className="mb-4 bg-gradient-to-r from-blue-50/90 to-indigo-50/90 border border-blue-200/80 rounded-2xl p-3.5 text-xs shadow-sm">
              <div className="flex items-center gap-1.5 font-black text-blue-950 mb-2">
                <Info className="w-4 h-4 text-blue-600 shrink-0" />
                <span>¿Cómo diferenciar las fechas en este formulario?</span>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                <div className="bg-white/90 p-2.5 rounded-xl border border-blue-100 flex items-start gap-2 shadow-xs">
                  <span className="p-1 bg-emerald-100 text-emerald-700 rounded-lg shrink-0 mt-0.5">
                    <Calendar className="w-3.5 h-3.5" />
                  </span>
                  <div>
                    <span className="font-bold text-slate-800 block text-xs">Fecha de Recepción / Entrada:</span>
                    <span className="text-slate-600 text-[11px] leading-relaxed block">
                      Cuándo <strong>llega físicamente la mercadería</strong> al depósito. Debe ser <strong>hoy o una fecha anterior</strong> (no admite fechas futuras).
                    </span>
                  </div>
                </div>
                <div className="bg-white/90 p-2.5 rounded-xl border border-blue-100 flex items-start gap-2 shadow-xs">
                  <span className="p-1 bg-amber-100 text-amber-700 rounded-lg shrink-0 mt-0.5">
                    <Clock className="w-3.5 h-3.5" />
                  </span>
                  <div>
                    <span className="font-bold text-slate-800 block text-xs">Fecha de Vencimiento de Lote *:</span>
                    <span className="text-slate-600 text-[11px] leading-relaxed block">
                      Cuándo <strong>caduca el lote ingresado</strong>. Es una <strong>fecha futura obligatoria</strong> para garantizar la trazabilidad de stock.
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <form onSubmit={handleGuardarIngreso} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label htmlFor="ingresoProveedor" className="text-xs font-bold text-slate-500 uppercase">Proveedor *</label>
                  <select
                    id="ingresoProveedor"
                    value={ingresoProveedorId}
                    onChange={(e) => setIngresoProveedorId(Number(e.target.value))}
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                    required
                  >
                    <option value="">Seleccione proveedor</option>
                    {proveedores.map(pr => (
                      <option key={pr.id_proveedor || pr.id} value={pr.id_proveedor || pr.id}>
                        {pr.razon_social || pr.nombre} ({pr.cuit || 'CUIT'})
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <div className="flex items-center justify-between">
                    <label htmlFor="ingresoFecha" className="text-xs font-bold text-slate-700 uppercase">Fecha de Recepción *</label>
                    <span className="text-[10px] font-bold text-emerald-700 bg-emerald-100/90 px-2 py-0.5 rounded-full">
                      Entrada a depósito (≤ Hoy)
                    </span>
                  </div>
                  <input
                    id="ingresoFecha"
                    type="date"
                    value={ingresoFecha}
                    max={getTodayLocal()}
                    onChange={(e) => {
                      const val = e.target.value;
                      const hoy = getTodayLocal();
                      if (val && val > hoy) {
                        alert('No se pueden registrar ingresos con fechas posteriores a la fecha actual.');
                        setIngresoFecha(hoy);
                        return;
                      }
                      setIngresoFecha(val);
                    }}
                    className="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                    required
                  />
                  <p className="text-[11px] text-slate-500 mt-1">
                    Día en que se recibió físicamente el pedido. Debe ser igual o anterior al día de hoy.
                  </p>
                </div>
              </div>

              <div>
                <div className="flex justify-between items-center mb-2">
                  <div>
                    <label className="text-xs font-bold text-slate-700 uppercase">Productos a Ingresar *</label>
                    <p className="text-[11px] text-slate-500">Todo ingreso de mercadería debe incluir obligatoriamente su N° de Lote y Fecha de Vencimiento.</p>
                  </div>
                  <button
                    type="button"
                    onClick={handleAddIngresoItem}
                    className="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs rounded-xl flex items-center gap-1 cursor-pointer transition-colors"
                  >
                    <Plus className="w-3.5 h-3.5" />
                    <span>Agregar Producto</span>
                  </button>
                </div>

                {/* Cabecera explicativa de columnas */}
                <div className="hidden md:flex items-center gap-2 px-3 py-1.5 bg-slate-100 rounded-xl text-[10px] font-black text-slate-600 uppercase tracking-wider mb-2">
                  <div className="flex-1">Producto Recibido *</div>
                  <div className="w-24 text-center">Cantidad *</div>
                  <div className="w-56 text-center text-indigo-800">Lote * (Existente o Nuevo)</div>
                  <div className="w-44 text-center text-amber-800">Vencimiento Lote * (Obligatorio)</div>
                  {ingresoItems.length > 1 && <div className="w-7"></div>}
                </div>

                <div className="space-y-3 max-h-72 overflow-y-auto pr-1">
                  {ingresoItems.map((item, idx) => {
                    const prod = products.find(p => 
                      String(p.id) === String(item.id_producto) || 
                      (p.id_producto !== undefined && String(p.id_producto) === String(item.id_producto))
                    );
                    const prodLotes = lotes.filter(l => 
                      String(l.id_producto) === String(item.id_producto) ||
                      (prod && String(l.id_producto) === String(prod.id)) ||
                      (prod && prod.id_producto !== undefined && String(l.id_producto) === String(prod.id_producto))
                    );
                    const hasLotes = prodLotes.length > 0;
                    const isNuevoMode = item.selected_mode === 'nuevo' || (!hasLotes);
                    const currentLote = prodLotes.find(l => l.nro_lote === item.nro_lote);
                    const isLoteAuto = item.selected_mode === 'existente' && !!currentLote;

                    const vtoProximoLote = prodLotes
                      .filter(l => (l.cantidad_actual > 0 || l.cantidad > 0) && l.fecha_vencimiento)
                      .sort((a, b) => String(a.fecha_vencimiento).localeCompare(String(b.fecha_vencimiento)))[0];
                    const comp = prod ? getCommittedUnits(prod) : 0;
                    const disp = prod ? Math.max(0, prod.stock - comp) : 0;

                    return (
                      <div key={idx} className="p-3 bg-slate-50 border border-slate-200/90 rounded-2xl flex flex-col gap-2 shadow-2xs">
                        <div className="flex flex-col md:flex-row items-start md:items-center gap-2 w-full">
                          {/* Producto */}
                          <div className="flex-1 w-full">
                            <div className="md:hidden text-[10px] font-bold text-slate-500 uppercase mb-0.5">Producto Recibido *</div>
                            <select
                              value={item.id_producto}
                              onChange={(e) => {
                                const val = e.target.value;
                                const newProdId = isNaN(Number(val)) ? val : Number(val);
                                const newItems = [...ingresoItems];
                                newItems[idx].id_producto = newProdId;
                                const chosenProd = products.find(p => 
                                  String(p.id) === String(newProdId) || 
                                  (p.id_producto !== undefined && String(p.id_producto) === String(newProdId))
                                );
                                const newProdLotes = lotes.filter(l => 
                                  String(l.id_producto) === String(newProdId) ||
                                  (chosenProd && String(l.id_producto) === String(chosenProd.id)) ||
                                  (chosenProd && chosenProd.id_producto !== undefined && String(l.id_producto) === String(chosenProd.id_producto))
                                );
                                if (newProdLotes.length > 0) {
                                  newItems[idx].selected_mode = 'existente';
                                  newItems[idx].nro_lote = newProdLotes[0].nro_lote;
                                  newItems[idx].fecha_vencimiento = newProdLotes[0].fecha_vencimiento
                                    ? String(newProdLotes[0].fecha_vencimiento).substring(0, 10)
                                    : '';
                                } else {
                                  newItems[idx].selected_mode = 'nuevo';
                                  newItems[idx].nro_lote = '';
                                  newItems[idx].fecha_vencimiento = '';
                                }
                                setIngresoItems(newItems);
                              }}
                              className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-bold"
                              required
                            >
                              <option value={0}>Seleccione producto...</option>
                              {products.filter(isProdActivo).map(p => (
                                <option key={p.id} value={p.id_producto || p.id}>
                                  {p.code} - {p.name} (Stock: {p.stock})
                                </option>
                              ))}
                            </select>
                          </div>

                          {/* Cantidad */}
                          <div className="w-full md:w-24">
                            <div className="md:hidden text-[10px] font-bold text-slate-500 uppercase mb-0.5">Cantidad *</div>
                            <input
                              type="number"
                              min={1}
                              placeholder="Cantidad"
                              value={item.cantidad}
                              onChange={(e) => {
                                const newItems = [...ingresoItems];
                                newItems[idx].cantidad = Number(e.target.value);
                                setIngresoItems(newItems);
                              }}
                              className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-center"
                              required
                              title="Cantidad recibida"
                            />
                          </div>

                          {/* Lote */}
                          <div className="w-full md:w-56">
                            <div className="md:hidden text-[10px] font-bold text-indigo-700 uppercase mb-0.5 flex items-center justify-between">
                              <span>Lote *</span>
                              {hasLotes && (
                                <span className="text-[9px] text-indigo-500 font-semibold">
                                  {isNuevoMode ? 'Nuevo' : 'Existente'}
                                </span>
                              )}
                            </div>
                            {hasLotes ? (
                              <div className="space-y-1">
                                <select
                                  value={isNuevoMode ? '__NUEVO__' : (item.nro_lote || '')}
                                  onChange={(e) => {
                                    const val = e.target.value;
                                    const newItems = [...ingresoItems];
                                    if (val === '__NUEVO__') {
                                      newItems[idx].selected_mode = 'nuevo';
                                      newItems[idx].nro_lote = '';
                                      newItems[idx].fecha_vencimiento = '';
                                    } else {
                                      const chosenLot = prodLotes.find(l => l.nro_lote === val);
                                      newItems[idx].selected_mode = 'existente';
                                      newItems[idx].nro_lote = val;
                                      if (chosenLot && chosenLot.fecha_vencimiento) {
                                        // Rellenar automáticamente el vencimiento con el vencimiento del lote mismo
                                        newItems[idx].fecha_vencimiento = String(chosenLot.fecha_vencimiento).substring(0, 10);
                                      }
                                    }
                                    setIngresoItems(newItems);
                                  }}
                                  className="w-full px-2 py-1.5 bg-white border border-indigo-200 rounded-xl text-xs font-bold text-indigo-950 focus:ring-1 focus:ring-indigo-500"
                                  title="Seleccionar lote existente o ingresar nuevo"
                                >
                                  <option value="">Seleccione lote...</option>
                                  <option value="__NUEVO__">✏️ + Ingresar nuevo lote...</option>
                                  <optgroup label="Lotes existentes">
                                    {prodLotes.map(l => (
                                      <option key={l.id_lote || l.nro_lote} value={l.nro_lote}>
                                        Lote {l.nro_lote} (Stock: {l.cantidad_actual ?? l.cantidad ?? 0} u. | Vto: {String(l.fecha_vencimiento).substring(0, 10)})
                                      </option>
                                    ))}
                                  </optgroup>
                                </select>
                                {isNuevoMode && (
                                  <input
                                    type="text"
                                    placeholder="N° Nuevo Lote *"
                                    value={item.nro_lote || ''}
                                    onChange={(e) => {
                                      const newItems = [...ingresoItems];
                                      newItems[idx].nro_lote = e.target.value;
                                      setIngresoItems(newItems);
                                    }}
                                    className="w-full px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs font-semibold"
                                    title="Escriba número de nuevo lote (obligatorio)"
                                    required
                                  />
                                )}
                              </div>
                            ) : (
                              <div>
                                <input
                                  type="text"
                                  placeholder="N° Lote *"
                                  value={item.nro_lote || ''}
                                  onChange={(e) => {
                                    const newItems = [...ingresoItems];
                                    newItems[idx].nro_lote = e.target.value;
                                    setIngresoItems(newItems);
                                  }}
                                  className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold"
                                  title="Número o código de lote del fabricante (obligatorio)"
                                  required
                                />
                                <span className="text-[9px] text-slate-400 block mt-0.5">
                                  Primer lote para este producto
                                </span>
                              </div>
                            )}
                          </div>

                          {/* Vencimiento */}
                          <div className="w-full md:w-44">
                            <div className="md:hidden text-[10px] font-bold text-amber-700 uppercase mb-0.5 flex items-center justify-between">
                              <span>Vencimiento Lote *</span>
                              {isLoteAuto && (
                                <span className="text-[9px] font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.2 rounded">
                                  Auto (del lote)
                                </span>
                              )}
                            </div>
                            <input
                              type="date"
                              value={item.fecha_vencimiento || ''}
                              onChange={(e) => {
                                const newItems = [...ingresoItems];
                                newItems[idx].fecha_vencimiento = e.target.value;
                                setIngresoItems(newItems);
                              }}
                              className="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold"
                              title="Fecha de vencimiento / caducidad del lote (obligatoria)"
                              required
                            />
                            <span className="hidden md:block text-[9px] text-amber-700/90 font-medium text-center mt-0.5 truncate">
                              {isLoteAuto
                                ? `Autocompletado con lote ${currentLote.nro_lote}`
                                : 'Caducidad (obligatoria)'}
                            </span>
                          </div>

                          {/* Eliminar */}
                          {ingresoItems.length > 1 && (
                            <button
                              type="button"
                              onClick={() => handleRemoveIngresoItem(idx)}
                              className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg cursor-pointer self-center"
                              title="Eliminar fila"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          )}
                        </div>

                        {/* Tarjeta de Stock Comprometido y Vencimiento del Producto */}
                        {prod && (
                          <div className="w-full pt-2 border-t border-slate-200/70 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <div className="flex flex-wrap items-center gap-2">
                              <span className="text-slate-600 font-semibold">
                                📦 Stock Físico: <strong className="text-slate-900">{prod.stock} u.</strong>
                              </span>
                              <span className="text-slate-300">|</span>
                              <span className="font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200/80 inline-flex items-center gap-1">
                                🔒 Comprometido: <strong>{comp} u.</strong>
                              </span>
                              <span className="text-slate-300">|</span>
                              <span className="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200/80">
                                Disp. libre: <strong>{disp} u.</strong>
                              </span>
                            </div>

                            <div className="flex items-center gap-1.5 text-xs">
                              <Clock className="w-3.5 h-3.5 text-indigo-600 shrink-0" />
                              <span className="text-slate-700">
                                <strong>Vencimiento Comprometido / Próximo:</strong>{' '}
                                {comp > 0 ? (
                                  vtoProximoLote ? (
                                    <span className="font-extrabold text-indigo-800 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-200">
                                      {String(vtoProximoLote.fecha_vencimiento).substring(0, 10)} (Lote {vtoProximoLote.nro_lote} • {vtoProximoLote.cantidad_actual ?? vtoProximoLote.cantidad ?? 0} u.)
                                    </span>
                                  ) : (
                                    <span className="text-amber-800 font-bold bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
                                      {item.fecha_vencimiento ? `Vence: ${item.fecha_vencimiento}` : 'Sin lote asignado'}
                                    </span>
                                  )
                                ) : (
                                  <span className="text-slate-400 italic">Sin unidades comprometidas</span>
                                )}
                              </span>
                            </div>
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowIngresoModal(false)}
                  className="px-4 py-2 font-bold text-xs text-slate-500 cursor-pointer"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl shadow-md cursor-pointer flex items-center gap-1.5"
                >
                  <Check className="w-4 h-4" />
                  <span>Confirmar Ingreso a Depósito</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL GESTIÓN DE UNIDADES Y EQUIVALENCIAS */}
      {showUnidadesModal && selectedProdForUnidades && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl max-h-[85vh] overflow-y-auto">
            <div className="flex justify-between items-center border-b border-slate-100 pb-3 mb-3">
              <div>
                <h3 className="text-lg font-black text-slate-800 flex items-center gap-2">
                  <Layers className="w-5 h-5 text-purple-600" />
                  <span>Unidades y Equivalencias</span>
                </h3>
                <span className="text-xs font-bold text-slate-500">
                  {selectedProdForUnidades.name} ({selectedProdForUnidades.code})
                </span>
              </div>
              <button onClick={() => setShowUnidadesModal(false)} className="text-slate-400 hover:text-slate-600 cursor-pointer">
                <X className="w-6 h-6" />
              </button>
            </div>

            {/* UNIDAD BASE */}
            <div className="p-3 bg-slate-50 border border-slate-200/80 rounded-2xl mb-4 flex items-center justify-between">
              <div>
                <span className="text-[11px] font-extrabold text-slate-400 uppercase block">Unidad Base de Venta</span>
                <span className="text-sm font-black text-slate-800">Unidad Simple (1 u.)</span>
              </div>
              <span className="px-2.5 py-1 bg-purple-100 text-purple-700 font-black text-xs rounded-full">
                Base Primaria
              </span>
            </div>

            {/* FORMULARIO AGREGAR UNIDAD */}
            <form onSubmit={handleCrearUnidad} className="space-y-3 mb-4 p-3.5 bg-purple-50/50 border border-purple-100 rounded-2xl">
              <span className="text-xs font-black text-purple-900 block">Registrar Unidad Equivalente</span>
              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="text-[10px] font-bold text-slate-500 uppercase">Nombre Unidad</label>
                  <input
                    type="text"
                    placeholder="Ej: Caja x12, Pack x6"
                    value={nuevaUnidadNombre}
                    onChange={(e) => setNuevaUnidadNombre(e.target.value)}
                    className="w-full mt-1 px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-bold"
                    required
                  />
                </div>
                <div>
                  <label className="text-[10px] font-bold text-slate-500 uppercase">Equivalencia (Unidades Base)</label>
                  <input
                    type="number"
                    min={1}
                    value={nuevaUnidadEquiv}
                    onChange={(e) => setNuevaUnidadEquiv(Number(e.target.value))}
                    className="w-full mt-1 px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-black text-center"
                    required
                  />
                </div>
              </div>
              <div>
                <label className="text-[10px] font-bold text-slate-500 uppercase">Descripción (opcional)</label>
                <input
                  type="text"
                  placeholder="Ej: Caja cerrada de fábrica x 12 botellas"
                  value={nuevaUnidadDesc}
                  onChange={(e) => setNuevaUnidadDesc(e.target.value)}
                  className="w-full mt-1 px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold"
                />
              </div>
              <button
                type="submit"
                className="w-full py-2 bg-purple-600 hover:bg-purple-700 text-white font-black text-xs rounded-xl shadow-sm cursor-pointer flex items-center justify-center gap-1.5"
              >
                <Plus className="w-3.5 h-3.5" />
                <span>Agregar Equivalencia</span>
              </button>
            </form>

            {/* LISTA DE UNIDADES REGISTRADAS */}
            <div className="space-y-2">
              <span className="text-xs font-bold text-slate-500 uppercase block">Unidades Registradas</span>
              {unidadesProd.length === 0 ? (
                <div className="p-4 text-center text-xs text-slate-400 font-bold border border-dashed border-slate-200 rounded-xl">
                  No hay unidades adicionales configuradas.
                </div>
              ) : (
                <div className="divide-y border border-slate-200 rounded-2xl overflow-hidden">
                  {unidadesProd.map((u) => (
                    <div key={u.id_unidad} className="p-3 flex justify-between items-center bg-white hover:bg-slate-50 text-xs">
                      <div>
                        <span className="font-black text-slate-800 block text-sm">{u.nombre_unidad}</span>
                        <span className="text-slate-500 font-semibold">
                          1 {u.nombre_unidad} = <strong className="text-purple-700 font-black">{u.equivalencia_base}</strong> unidades base
                        </span>
                        {u.descripcion && (
                          <span className="text-[11px] text-slate-400 block mt-0.5">{u.descripcion}</span>
                        )}
                      </div>
                      <button
                        onClick={() => handleEliminarUnidad(u.id_unidad)}
                        className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg cursor-pointer transition-colors"
                        title="Eliminar unidad"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div className="flex justify-end pt-3 mt-4 border-t border-slate-100">
              <button
                onClick={() => setShowUnidadesModal(false)}
                className="px-4 py-2 font-bold text-xs text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl cursor-pointer"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL GESTIÓN DE UBICACIONES FÍSICAS */}
      {showUbicacionesModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl max-h-[85vh] overflow-y-auto">
            <div className="flex justify-between items-center border-b border-slate-100 pb-3 mb-3">
              <div>
                <h3 className="text-lg font-black text-slate-800 flex items-center gap-2">
                  <MapPin className="w-5 h-5 text-rose-500" />
                  <span>Ubicaciones Físicas de Almacenamiento</span>
                </h3>
                <span className="text-xs text-slate-500">
                  Estantes, pasillos y sectores del depósito para localizar mercadería.
                </span>
              </div>
              <button onClick={() => setShowUbicacionesModal(false)} className="text-slate-400 hover:text-slate-600 cursor-pointer">
                <X className="w-6 h-6" />
              </button>
            </div>

            {/* FORMULARIO AGREGAR UBICACIÓN */}
            <form onSubmit={handleCrearUbicacion} className="flex gap-2 mb-4">
              <input
                type="text"
                placeholder="Ej: Pasillo 3 - Estante B"
                value={nuevaUbicacionDesc}
                onChange={(e) => setNuevaUbicacionDesc(e.target.value)}
                className="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold"
                required
              />
              <button
                type="submit"
                className="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-black text-xs rounded-xl shadow-sm cursor-pointer whitespace-nowrap"
              >
                + Crear Ubicación
              </button>
            </form>

            {/* LISTA DE UBICACIONES */}
            <div className="space-y-2">
              <span className="text-xs font-bold text-slate-500 uppercase block">Ubicaciones Existentes</span>
              {ubicaciones.length === 0 ? (
                <div className="p-4 text-center text-xs text-slate-400 font-bold border border-slate-200 rounded-xl">
                  No hay ubicaciones registradas en el depósito.
                </div>
              ) : (
                <div className="max-h-60 overflow-y-auto divide-y border border-slate-200 rounded-2xl">
                  {ubicaciones.map(u => {
                    const countProds = products.filter(p => p.id_ubicacion === u.id_ubicacion).length;
                    return (
                      <div key={u.id_ubicacion} className="p-3 flex justify-between items-center text-sm font-bold bg-white hover:bg-slate-50">
                        <div className="flex items-center gap-2">
                          <MapPin className="w-4 h-4 text-rose-500 shrink-0" />
                          <span className="text-slate-800">{u.descripcion}</span>
                          <span className="text-[10px] text-slate-400 font-normal">ID: {u.id_ubicacion}</span>
                        </div>
                        <span className="px-2 py-0.5 bg-slate-100 text-slate-600 text-xs rounded-full font-bold">
                          {countProds} {countProds === 1 ? 'producto' : 'productos'}
                        </span>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>

            <div className="flex justify-end pt-3 mt-4 border-t border-slate-100">
              <button
                onClick={() => setShowUbicacionesModal(false)}
                className="px-4 py-2 font-bold text-xs text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl cursor-pointer"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
