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

    <!-- Estilos Claymorfismo PyB y Módulos G1 -->
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pigue-claymorphism.css') }}">
    <link rel="stylesheet" href="{{ asset('css/claymorfismo.css') }}">

    <!-- Lucide Icons & FontAwesome -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @yield('styles')
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/navigation.css') }}">
</head>
<body class="erp-shell bg-[#EBF4FC] text-slate-800 font-sans min-h-screen antialiased">

    @include('partials.sidebar')

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="erp-workspace flex flex-col min-w-0">

        <!-- TOPBAR DESKTOP -->
        <header class="hidden md:flex items-center justify-between px-8 py-4 bg-white/40 backdrop-blur-md border-b border-slate-200/50 sticky top-0 z-10">
            <div class="flex items-center gap-4">
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
                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-black flex items-center justify-center text-xs shadow-sm uppercase">
                        {{ strtoupper(substr(auth()->user()?->nombre ?? (auth()->user()?->usuario ?? 'US'), 0, 2)) }}
                    </div>
                    <div class="text-left">
                        <span class="text-xs font-black text-slate-800 block leading-tight">{{ auth()->user()?->nombre ?? (auth()->user()?->usuario ?? 'Usuario') }}</span>
                        <span class="text-[9px] font-extrabold text-blue-600 uppercase tracking-wider capitalize">{{ auth()->user()?->rol?->value ?? (auth()->user()?->rol ?? 'Activo') }}</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- CONTENIDO DE LA PÁGINA -->
        <main class="flex-1 p-4 md:p-8 overflow-y-auto">
            @include('partials.messages')
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

        document.addEventListener('DOMContentLoaded', () => {
if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
    <!-- Bootstrap 5 JS Bundle (para soporte de modales y tabs en submódulos) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
    @stack('scripts')
    <script src="{{ asset('assets/js/navigation.js') }}" defer></script>
</body>
</html>
