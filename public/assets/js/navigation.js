(() => {
    const sidebar = document.querySelector('[data-erp-sidebar]');
    const toggle = document.querySelector('[data-erp-sidebar-toggle]');
    if (sidebar && toggle) {
        const collapsedKey = 'erp-navigation-collapsed';
        const setCollapsed = (collapsed, persist = false) => {
            if (collapsed && sidebar.contains(document.activeElement)) toggle.focus();
            document.body.classList.toggle('erp-sidebar-collapsed', collapsed);
            sidebar.inert = collapsed;
            sidebar.setAttribute('aria-hidden', String(collapsed));
            toggle.setAttribute('aria-expanded', String(!collapsed));
            const label = collapsed ? 'Mostrar menú' : 'Ocultar menú';
            toggle.setAttribute('aria-label', label);
            toggle.title = label;
            if (persist) {
                try { sessionStorage.setItem(collapsedKey, String(collapsed)); } catch (_) { /* Opcional. */ }
            }
        };
        let collapsed = false;
        try { collapsed = sessionStorage.getItem(collapsedKey) === 'true'; } catch (_) { /* Abierto por defecto. */ }
        setCollapsed(collapsed);
        toggle.hidden = false;
        toggle.addEventListener('click', () => setCollapsed(!document.body.classList.contains('erp-sidebar-collapsed'), true));
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && sidebar.contains(document.activeElement) && !sidebar.inert) {
                event.preventDefault();
                setCollapsed(true, true);
            }
        });
    }
    const nav = document.querySelector('[data-erp-sidebar-nav]');
    if (!nav) return;
    const storageKey = 'erp-navigation-scroll';
    try {
        const saved = Number(sessionStorage.getItem(storageKey));
        if (Number.isFinite(saved)) nav.scrollTop = saved;
    } catch (_) { /* El menú sigue funcionando si el almacenamiento está bloqueado. */ }

    const current = nav.querySelector('[aria-current="page"]');
    if (current) {
        const item = current.getBoundingClientRect();
        const area = nav.getBoundingClientRect();
        if (item.top < area.top) nav.scrollTop -= area.top - item.top;
        if (item.bottom > area.bottom) nav.scrollTop += item.bottom - area.bottom;
    }
    const saveScroll = () => {
        try { sessionStorage.setItem(storageKey, String(nav.scrollTop)); } catch (_) { /* Opcional. */ }
    };
    nav.addEventListener('scroll', saveScroll, { passive: true });
    nav.addEventListener('click', saveScroll);
})();
