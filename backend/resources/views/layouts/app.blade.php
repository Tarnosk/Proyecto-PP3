<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Distribuidora PyB - Sistema de Gestión Comercial')</title>

    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        app: '#EBF4FC',
                    }
                }
            }
        }
    </script>

    <!-- Estilos Claymorfismo PyB -->
    <link rel="stylesheet" href="{{ asset('css/pigue-claymorphism.css') }}">
    <link rel="stylesheet" href="{{ asset('css/claymorfismo.css') }}">

    <!-- Lucide Icons & FontAwesome -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Script temprano para evitar FOUC (parpadeo) al restaurar sidebar colapsado -->
    <script>
        (function() {
            try {
                if (localStorage.getItem('pigue_sidebar_collapsed') === 'true') {
                    document.documentElement.classList.add('sidebar-collapsed-early');
                }
            } catch(e) {}
        })();
    </script>
    <style>
        /* Transición suave para ancho y paddings del sidebar */
        #desktopSidebar {
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), padding 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        /* Estado colapsado: ancho compacto de 5rem (80px) */
        html.sidebar-collapsed-early #desktopSidebar,
        #desktopSidebar.is-collapsed {
            width: 5rem !important;
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }
        /* Ocultar textos cuando el sidebar está colapsado */
        html.sidebar-collapsed-early #desktopSidebar .sidebar-text,
        #desktopSidebar.is-collapsed .sidebar-text {
            display: none !important;
        }
        /* Centrar los iconos en modo colapsado */
        html.sidebar-collapsed-early #desktopSidebar .sidebar-link,
        #desktopSidebar.is-collapsed .sidebar-link {
            justify-content: center !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        /* Adaptar header de logo en modo colapsado */
        html.sidebar-collapsed-early #desktopSidebar .sidebar-brand-wrapper,
        #desktopSidebar.is-collapsed .sidebar-brand-wrapper {
            justify-content: center !important;
            padding: 0.25rem !important;
        }
        /* Ocultar divisor textual en modo colapsado */
        html.sidebar-collapsed-early #desktopSidebar .sidebar-divider,
        #desktopSidebar.is-collapsed .sidebar-divider {
            display: none !important;
        }
        /* Adaptar footer de usuario en modo colapsado */
        html.sidebar-collapsed-early #desktopSidebar .sidebar-user-card,
        #desktopSidebar.is-collapsed .sidebar-user-card {
            justify-content: center !important;
            padding: 0.35rem !important;
        }
        html.sidebar-collapsed-early #desktopSidebar .sidebar-user-info,
        #desktopSidebar.is-collapsed .sidebar-user-info {
            display: none !important;
        }
    </style>
</head>
<body class="bg-[#EBF4FC] text-slate-800 font-sans min-h-screen flex flex-col md:flex-row antialiased">

    <!-- HEADER PARA MÓVIL -->
    <header class="md:hidden flex items-center justify-between bg-white/85 backdrop-blur-md border-b border-slate-200/80 px-5 py-3.5 shrink-0 shadow-sm z-30 sticky top-0">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-black shadow-md shadow-blue-500/10 shrink-0">
                <i data-lucide="package" class="w-5 h-5 text-white"></i>
            </div>
            <div class="flex flex-col text-left">
                <span class="font-black text-base text-slate-800 tracking-tight">Distribuidora PyB</span>
                <span class="text-[9px] font-extrabold text-blue-600 uppercase tracking-wider">Sistema de Gestión</span>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <button onclick="document.getElementById('mobileSidebar').classList.toggle('hidden')" class="p-2 text-slate-600 hover:text-slate-900 bg-slate-100 rounded-lg">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>
        </div>
    </header>

    <!-- SIDEBAR MÓVIL (Drawer) -->
    <div id="mobileSidebar" class="hidden md:hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm" onclick="this.classList.add('hidden')">
        <div class="w-72 bg-gradient-to-b from-[#1E40AF] to-[#1E3A8A] text-white h-full p-5 flex flex-col justify-between" onclick="event.stopPropagation()">
            <div>
                <div class="flex items-center justify-between pb-6 border-b border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-500 flex items-center justify-center text-white">
                            <i data-lucide="package" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <div class="font-black text-xl tracking-tight leading-tight">PyB</div>
                            <div class="text-[10px] font-extrabold text-blue-200 uppercase tracking-widest leading-none">Distribuidora</div>
                        </div>
                    </div>
                    <button onclick="document.getElementById('mobileSidebar').classList.add('hidden')" class="text-white/70 hover:text-white">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <nav class="mt-6 space-y-1.5">
                    @include('partials.sidebar-links')
                </nav>
            </div>

            <div class="pt-6 border-t border-white/10 text-xs text-blue-200/60 text-center font-bold">
                Distribuidora PyB • 2026
            </div>
        </div>
    </div>

    <!-- SIDEBAR DESKTOP (Claymorphism Sidebar) -->
    <aside id="desktopSidebar" class="hidden md:flex flex-col justify-between w-64 clay-sidebar text-white shrink-0 min-h-screen sticky top-0 h-screen p-5 z-20">
        <div>
            <!-- LOGO / BRAND & TOGGLE BOTÓN -->
            <div class="flex items-center justify-between gap-2">
                <a href="{{ url('/') }}" class="sidebar-brand-wrapper flex items-center gap-3 p-2 rounded-2xl hover:bg-white/5 transition-all text-left flex-1 min-w-0" title="Distribuidora PyB - Inicio">
                    <div class="w-10 h-10 rounded-2xl bg-white/15 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0">
                        <i data-lucide="package" class="w-6 h-6 text-white"></i>
                    </div>
                    <div class="sidebar-text flex flex-col min-w-0">
                        <span class="font-black text-xl tracking-tight leading-tight">PyB</span>
                        <span class="text-[10px] font-extrabold text-blue-200 uppercase tracking-widest leading-none mt-0.5">Distribuidora</span>
                    </div>
                </a>
            </div>

            <!-- NAVEGACIÓN PRINCIPAL -->
            <div class="mt-6">
                <nav class="space-y-1.5">
                    @include('partials.sidebar-links')
                </nav>
            </div>
        </div>

        <!-- FOOTER SIDEBAR / USUARIO -->
        <div class="pt-4 border-t border-white/15">
            <div class="sidebar-user-card flex items-center gap-3 p-2 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10" title="Usuario Administrador">
                <div class="w-9 h-9 rounded-xl bg-blue-400/30 flex items-center justify-center text-white font-black text-sm shrink-0">
                    AD
                </div>
                <div class="sidebar-user-info flex-1 min-w-0">
                    <div class="text-xs font-black text-white truncate">Administrador</div>
                    <div class="text-[10px] text-blue-200 font-bold truncate">Distribuidora PyB</div>
                </div>
                <a href="{{ url('/') }}" class="sidebar-user-info p-1.5 text-white/70 hover:text-white transition-colors" title="Volver al Panel">
                    <i data-lucide="home" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- TOPBAR DESKTOP -->
        <header class="hidden md:flex items-center justify-between px-8 py-4 bg-white/40 backdrop-blur-md border-b border-slate-200/50 sticky top-0 z-10">
            <div class="flex items-center gap-4">
                <!-- Botón Toggle Barra Lateral -->
                <button type="button" onclick="toggleSidebarDesktop()" id="btnToggleSidebar" class="p-2 text-slate-600 hover:text-blue-700 hover:bg-white bg-slate-100/80 rounded-xl border border-slate-200/70 shadow-xs transition-all cursor-pointer flex items-center justify-center" title="Contraer / Expandir Barra Lateral">
                    <i data-lucide="panel-left-close" class="w-4 h-4" id="iconToggleSidebar"></i>
                </button>

                <div>
                    <h2 class="text-lg font-black text-slate-800 leading-tight">@yield('page_title', 'Distribuidora PyB')</h2>
                    <p class="text-xs font-bold text-slate-400">@yield('page_subtitle', 'Módulo de Inventario y Gestión de Compras')</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="window.location.reload()" class="flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-200/80 shadow-sm transition-all" title="Recargar datos">
                    <i data-lucide="rotate-cw" class="w-3.5 h-3.5 text-blue-600"></i>
                    <span>Refrescar</span>
                </button>

                <div class="flex items-center gap-2 pl-3 border-l border-slate-200/80">
                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-black flex items-center justify-center text-xs shadow-sm">
                        AD
                    </div>
                    <div class="text-left">
                        <span class="text-xs font-black text-slate-800 block leading-tight">Admin</span>
                        <span class="text-[9px] font-extrabold text-blue-600 uppercase tracking-wider">Activo</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- CONTENIDO DE LA PÁGINA -->
        <main class="flex-1 p-4 md:p-8 overflow-y-auto">
            @yield('content')
        </main>
    </div>

    <!-- MODALES GLOBALES / PROPIOS DE VISTA -->
    @yield('modals')

    <!-- CONTENEDOR DE TOASTS GLOBALES -->
    <div id="contenedorToastsGlobal" class="fixed top-5 right-5 z-[100] flex flex-col gap-2 pointer-events-none"></div>

    <!-- Inicialización de Iconos Lucide y Notificaciones -->
    <script>
        function mostrarNotificacion(mensaje, tipo = 'info') {
            const cont = document.getElementById('contenedorToastsGlobal');
            if (!cont) {
                alert(mensaje);
                return;
            }
            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto flex items-center gap-2.5 px-4 py-3 rounded-2xl shadow-xl border text-xs font-bold transition-all duration-300 transform -translate-y-2 opacity-0 max-w-sm';

            let iconName = 'info';
            if (tipo === 'success') {
                toast.classList.add('bg-emerald-50', 'text-emerald-900', 'border-emerald-200');
                iconName = 'check-circle-2';
            } else if (tipo === 'error') {
                toast.classList.add('bg-rose-50', 'text-rose-900', 'border-rose-200');
                iconName = 'alert-triangle';
            } else {
                toast.classList.add('bg-sky-50', 'text-sky-900', 'border-sky-200');
                iconName = 'info';
            }

            toast.innerHTML = `
                <i data-lucide="${iconName}" class="w-4 h-4 shrink-0"></i>
                <span>${mensaje}</span>
            `;
            cont.appendChild(toast);
            if (window.lucide) window.lucide.createIcons();

            requestAnimationFrame(() => {
                toast.classList.remove('-translate-y-2', 'opacity-0');
            });

            setTimeout(() => {
                toast.classList.add('opacity-0', '-translate-y-2');
                setTimeout(() => toast.remove(), 350);
            }, 3500);
        }
        window.mostrarNotificacion = mostrarNotificacion;

        // Control de Barra Lateral Desplegable / Colapsable con Persistencia
        function aplicarEstadoSidebarDesktop(colapsado) {
            const sidebar = document.getElementById('desktopSidebar');
            const icon = document.getElementById('iconToggleSidebar');
            if (!sidebar) return;

            if (colapsado) {
                sidebar.classList.add('is-collapsed');
                if (icon) icon.setAttribute('data-lucide', 'panel-left-open');
            } else {
                sidebar.classList.remove('is-collapsed');
                if (icon) icon.setAttribute('data-lucide', 'panel-left-close');
            }

            // Quitar clase temprana de HTML una vez montado
            document.documentElement.classList.remove('sidebar-collapsed-early');

            if (window.lucide) window.lucide.createIcons();
        }

        function toggleSidebarDesktop() {
            const sidebar = document.getElementById('desktopSidebar');
            if (!sidebar) return;
            const nuevoEstado = !sidebar.classList.contains('is-collapsed');
            try {
                localStorage.setItem('pigue_sidebar_collapsed', nuevoEstado ? 'true' : 'false');
            } catch(e) {}
            aplicarEstadoSidebarDesktop(nuevoEstado);
        }
        window.toggleSidebarDesktop = toggleSidebarDesktop;

        document.addEventListener('DOMContentLoaded', () => {
            try {
                const guardado = localStorage.getItem('pigue_sidebar_collapsed') === 'true';
                aplicarEstadoSidebarDesktop(guardado);
            } catch(e) {}

            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
    <!-- Bootstrap 5 JS Bundle (para soporte de modales y tabs en submódulos) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
