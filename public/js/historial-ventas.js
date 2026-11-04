const normalizeText = (value) => String(value ?? '')
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .toLocaleLowerCase();

function filterSales(sales, filters) {
    const search = normalizeText(filters.search).trim();
    const product = normalizeText(filters.producto).trim();
    const productIds = filters.productos
        .filter((option) => normalizeText(option.nombre).includes(product))
        .map((option) => String(option.id));

    return sales.filter((sale) => {
        const matchesSearch = !search
            || normalizeText(`${sale.cliente} ${sale.factura}`).includes(search);
        const matchesClient = filters.clienteIds.length === 0
            || filters.clienteIds.includes(String(sale.cliente_id));
        const matchesProduct = !product
            || sale.detalle.some((item) => productIds.includes(String(item.id_producto)));
        const matchesLocality = !filters.localidad
            || sale.localidad === filters.localidad;
        const matchesCancelled = filters.showCancelled || sale.estado !== 'cancelada';
        const saleDate = String(sale.fecha_iso ?? sale.fecha ?? '').slice(0, 10);
        const matchesStartDate = !filters.fechaDesde || saleDate >= filters.fechaDesde;
        const matchesEndDate = !filters.fechaHasta || saleDate <= filters.fechaHasta;

        return matchesSearch
            && matchesClient
            && matchesProduct
            && matchesLocality
            && matchesCancelled
            && matchesStartDate
            && matchesEndDate;
    });
}

function formatCurrency(value) {
    return `$ ${Number(value).toLocaleString('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

function initializeSalesHistory() {
    const dataElement = document.getElementById('ventasHistorialData');
    const form = document.getElementById('formFiltros');
    if (!dataElement || !form) return;

    const sales = JSON.parse(dataElement.textContent);
    const searchInput = document.getElementById('fTexto');
    const clientInput = document.getElementById('fCliente');
    const clientsContainer = document.getElementById('filtroClientes');
    const selectedClientsContainer = document.getElementById('clientesSeleccionados');
    const clientSuggestions = document.getElementById('opcionesClientes');
    const productInput = document.getElementById('fProducto');
    const clientOptions = JSON.parse(document.getElementById('clientesSugeridos')?.textContent ?? '[]');
    const productOptions = Array.from(document.querySelectorAll('#productosSugeridos option'), (option) => ({
        id: option.dataset.id,
        nombre: option.value,
    }));
    const localitySelect = document.getElementById('fLocalidad');
    const startDateInput = document.getElementById('fDesde');
    const endDateInput = document.getElementById('fHasta');
    const cancelledToggle = document.getElementById('fCanceladas');
    const clearButton = document.getElementById('limpiarFiltros');
    const noResults = document.getElementById('sinResultados');
    const counter = document.getElementById('contadorVentas');
    if (!searchInput || !clientInput || !clientsContainer || !selectedClientsContainer
        || !clientSuggestions || !productInput || !localitySelect
        || !startDateInput || !endDateInput || !cancelledToggle || !clearButton
        || !noResults || !counter) return;
    const saleRows = new Map(
        Array.from(document.querySelectorAll('.hv-grupo'), (row) => [row.dataset.ventaId, row])
    );
    const filters = {
        search: '',
        clienteIds: [],
        producto: '',
        productos: productOptions,
        localidad: '',
        showCancelled: false,
        fechaDesde: '',
        fechaHasta: '',
    };
    const selectedClients = new Map();

    const closeClientSuggestions = () => {
        clientSuggestions.hidden = true;
        clientInput.setAttribute('aria-expanded', 'false');
    };

    const renderSelectedClients = () => {
        selectedClientsContainer.replaceChildren();

        for (const [id, nombre] of selectedClients) {
            const chip = document.createElement('span');
            chip.className = 'hv-cliente-chip';
            chip.append(document.createTextNode(nombre));

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.textContent = '×';
            removeButton.setAttribute('aria-label', `Quitar cliente ${nombre}`);
            removeButton.addEventListener('click', () => {
                selectedClients.delete(id);
                renderSelectedClients();
                updateFilters();
                renderClientSuggestions();
                clientInput.focus();
            });

            chip.append(removeButton);
            selectedClientsContainer.append(chip);
        }
    };

    const renderClientSuggestions = () => {
        const query = normalizeText(clientInput.value).trim();
        const matches = clientOptions
            .filter((client) => !selectedClients.has(String(client.id))
                && normalizeText(client.nombre).includes(query))
            .slice(0, 8);

        clientSuggestions.replaceChildren();
        for (const client of matches) {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'hv-clientes-opcion';
            option.textContent = client.nombre;
            option.setAttribute('role', 'option');
            option.addEventListener('mousedown', (event) => event.preventDefault());
            option.addEventListener('click', () => {
                selectedClients.set(String(client.id), client.nombre);
                clientInput.value = '';
                renderSelectedClients();
                updateFilters();
                renderClientSuggestions();
                clientInput.focus();
            });
            clientSuggestions.append(option);
        }

        clientSuggestions.hidden = matches.length === 0;
        clientInput.setAttribute('aria-expanded', String(matches.length > 0));
    };

    const localities = [...new Set(sales.map((sale) => sale.localidad).filter(Boolean))]
        .sort((first, second) => first.localeCompare(second, 'es'));

    for (const locality of localities) {
        const option = document.createElement('option');
        option.value = locality;
        option.textContent = locality;
        localitySelect.append(option);
    }

    const updateFilters = () => {
        filters.search = searchInput.value;
        filters.clienteIds = Array.from(selectedClients.keys());
        filters.producto = productInput.value;
        filters.localidad = localitySelect.value;
        filters.showCancelled = cancelledToggle.checked;
        filters.fechaDesde = startDateInput.value;
        filters.fechaHasta = endDateInput.value;

        const filteredSales = filterSales(sales, filters);
        const completedSales = filteredSales.filter((sale) => sale.estado !== 'cancelada');
        const visibleIds = new Set(filteredSales.map((sale) => String(sale.id)));

        for (const [saleId, row] of saleRows) {
            row.hidden = !visibleIds.has(saleId);
        }

        noResults.hidden = filteredSales.length > 0;
        counter.textContent = `${filteredSales.length} ventas`;
        document.querySelector('[data-kpi="total-vendido"]').textContent = formatCurrency(
            completedSales.reduce((sum, sale) => sum + Number(sale.total), 0)
        );
        document.querySelector('[data-kpi="descuentos"]').textContent = formatCurrency(
            completedSales.reduce((sum, sale) => sum + Number(sale.descuento), 0)
        );
        document.querySelector('[data-kpi="en-cc"]').textContent = formatCurrency(
            completedSales.reduce((sum, sale) => sum + (sale.cc ? Number(sale.total) : 0), 0)
        );
        document.querySelector('[data-kpi="cantidad"]').textContent = String(completedSales.length);
    };

    searchInput.addEventListener('input', updateFilters);
    clientInput.addEventListener('input', renderClientSuggestions);
    clientInput.addEventListener('focus', renderClientSuggestions);
    clientInput.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeClientSuggestions();
        if (event.key === 'Enter' && !clientSuggestions.hidden) {
            event.preventDefault();
            clientSuggestions.querySelector('button')?.click();
        }
        if (event.key === 'ArrowDown' && !clientSuggestions.hidden) {
            event.preventDefault();
            clientSuggestions.querySelector('button')?.focus();
        }
        if (event.key === 'ArrowUp' && !clientSuggestions.hidden) {
            event.preventDefault();
            clientSuggestions.querySelector('button:last-child')?.focus();
        }
    });
    clientSuggestions.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeClientSuggestions();
            clientInput.focus();
            return;
        }

        const options = Array.from(clientSuggestions.querySelectorAll('button'));
        const currentIndex = options.indexOf(document.activeElement);
        if (event.key === 'ArrowDown' && options.length > 0) {
            event.preventDefault();
            options[(currentIndex + 1) % options.length].focus();
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (currentIndex <= 0) clientInput.focus();
            else options[currentIndex - 1].focus();
        }
    });
    document.addEventListener('click', (event) => {
        if (!clientsContainer.contains(event.target)) closeClientSuggestions();
    });
    productInput.addEventListener('input', updateFilters);
    form.addEventListener('submit', (event) => event.preventDefault());
    form.querySelectorAll('select, input[type="date"]').forEach((control) => {
        control.addEventListener('change', updateFilters);
    });
    cancelledToggle.addEventListener('change', updateFilters);
    clearButton.addEventListener('click', () => {
        form.reset();
        selectedClients.clear();
        renderSelectedClients();
        closeClientSuggestions();
        updateFilters();
    });

    document.querySelectorAll('[data-toggle-detalle]').forEach((row) => {
        row.addEventListener('click', () => {
            const group = row.closest('.hv-grupo');
            const opened = group.classList.toggle('abierto');
            row.classList.toggle('abierta', opened);
        });
    });

    updateFilters();
}

if (typeof document !== 'undefined') {
    initializeSalesHistory();
}
