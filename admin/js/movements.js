document.addEventListener('DOMContentLoaded', async () => {

    /* ================== STATO ================== */

    let categories = [];
    let editMode = false;
    let editId = null;
    let movementsTable = null;
    const canCreate = Auth.can('movements.create');
    const canEdit = Auth.can('movements.edit');
    const canDelete = Auth.can('movements.delete');

    /* ================== DOM ================== */

    const modal = document.getElementById('modal');
    const openModal = document.getElementById('openModal');
    const closeModal = document.getElementById('closeModal');
    const movementForm = document.getElementById('movementForm');

    const cat1 = document.getElementById('cat1');
    const cat2 = document.getElementById('cat2');
    const cat3 = document.getElementById('cat3');
    const cat4 = document.getElementById('cat4');

    const competition_id = document.getElementById('competition_id');
    const team_id = document.getElementById('team_id');
    const referee_id = document.getElementById('referee_id');

    const movement_date = document.getElementById('movement_date');
    const amount = document.getElementById('amount');
    const description = document.getElementById('description');

    /* ================== UTIL ================== */

    function resetSelect(sel, placeholder = 'Seleziona') {
        sel.innerHTML = '';
        sel.appendChild(new Option(placeholder, ''));
        sel.disabled = true;
        sel.required = false;
    }

    function hasData(json) {
        return json && json.success && Array.isArray(json.data);
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: 'EUR'
        }).format(Number(value || 0));
    }

    function showToast(message, type = 'info') {
        if (window.$ && $.toast) {
            $.toast({
                text: message,
                icon: type,
                position: 'bottom-left',
                hideAfter: 3000
            });
            return;
        }

        AppDialog.notify(message, type);
    }

    function resetMovementsTable() {
        if (window.$ && $.fn.DataTable && $.fn.DataTable.isDataTable('#movements')) {
            $('#movements').DataTable().clear().destroy();
        }
    }

    function initMovementsTable() {
        if (!(window.$ && $.fn.DataTable)) {
            return;
        }

        movementsTable = $('#movements').DataTable({
            pageLength: 10,
            ordering: false,
            searching: true,
            paging: true,
            lengthChange: false,
            pagingType: 'full_numbers',
            autoWidth: false,
            columnDefs: [
                { targets: 0, width: '100px', className: 'text-center' },
                { targets: 1, width: '110px' },
                { targets: 2, width: '160px' },
                { targets: 3, width: '160px' },
                { targets: 4, width: '180px' },
                { targets: 6, width: '120px', className: 'text-center' },
                { targets: 7, width: '150px', className: 'text-center', orderable: false }
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/it-IT.json'
            }
        });
    }

    function showModal() {
        if (window.$ && $.fn.modal) {
            $('#modal').modal('show');
            return;
        }

        modal.style.display = 'block';
    }

    function hideModal() {
        if (window.$ && $.fn.modal) {
            $('#modal').modal('hide');
            return;
        }

        modal.style.display = 'none';
    }

    function toggleSection(id, show) {
        const el = document.getElementById(id);
        const sel = el.querySelector('select');

        el.style.display = show ? 'block' : 'none';
        sel.disabled = !show;
        sel.required = show;

        if (!show) sel.value = '';
    }

    function resetDynamicFields() {
        ['wrapCompetition', 'wrapTeam', 'wrapReferee']
            .forEach(id => toggleSection(id, false));
    }

    function clearErrors() {
        document.querySelectorAll('.wrapper-error')
            .forEach(el => el.classList.remove('wrapper-error'));

        document.querySelectorAll('.error-msg')
            .forEach(el => el.textContent = '');
    }

    function fieldWrapper(field) {
        const map = {
            category_id: 'wrapCat4',
            competition_id: 'wrapCompetition',
            team_id: 'wrapTeam',
            referee_id: 'wrapReferee',
            movement_date: 'wrapDate',
            amount: 'wrapAmount'
        };

        return document.getElementById(map[field] || '');
    }

    function showErrors(errors = {}) {
        Object.entries(errors).forEach(([field, message]) => {
            const wrapper = fieldWrapper(field);
            if (!wrapper) return;

            wrapper.classList.add('wrapper-error');

            const error = wrapper.querySelector('.error-msg');
            if (error) error.textContent = message;
        });
    }

    function resetModal() {
        movementForm.reset();

        editMode = false;
        editId = null;

        clearErrors();
        resetDynamicFields();

        resetSelect(cat1, 'Entrate / Uscite');
        resetSelect(cat2, 'Categoria');
        resetSelect(cat3, 'Sottocategoria');
        resetSelect(cat4, 'Dettaglio');
    }

    /* ================== CATEGORIES ================== */

    async function loadCategoriesTree() {
        const json = await fetchJSON('../api/v1/categories-tree');
        if (!hasData(json)) return;

        categories = json.data;
    }

    function getChildren(parentId) {
        return categories.filter(c => String(c.parent_id) === String(parentId));
    }

    function populateSelect(select, parentId, placeholder) {

        select.innerHTML = '';
        select.appendChild(new Option(placeholder, ''));

        const children = getChildren(parentId);

        children.forEach(c => {
            select.appendChild(new Option(c.name, c.id));
        });

        select.disabled = false;
    }

    function getCategoryChain(categoryId) {

        const chain = [];

        let current = categories.find(c => String(c.id) === String(categoryId));

        while (current) {
            chain.unshift(current);

            if (!current.parent_id) break;

            current = categories.find(c => String(c.id) === String(current.parent_id));
        }

        return chain;
    }

    /* ================== CHANGE ================== */

    cat1.addEventListener('change', () => {
        populateSelect(cat2, cat1.value, "Categoria");

        resetSelect(cat3, "Sottocategoria");
        resetSelect(cat4, "Dettaglio");

        resetDynamicFields();
    });

    cat2.addEventListener('change', () => {
        populateSelect(cat3, cat2.value, "Sottocategoria");

        resetSelect(cat4, "Dettaglio");

        resetDynamicFields();
    });

    cat3.addEventListener('change', () => {
        populateSelect(cat4, cat3.value, "Dettaglio");
        resetDynamicFields();
    });

    cat4.addEventListener('change', () => {

        const c = categories.find(x => x.id == cat4.value);
        if (!c) return;

        resetDynamicFields();

        if (c.allow_competition) {
            toggleSection('wrapCompetition', true);
            loadCompetitions();
        }

        if (c.allow_team) {
            toggleSection('wrapTeam', true);
            loadTeams(c.allow_competition ? competition_id.value : null);
        }

        if (c.allow_referee) {
            toggleSection('wrapReferee', true);
            loadReferees();
        }
    });

    competition_id.addEventListener('change', () => {
        const selectedCategory = categories.find(x => x.id == cat4.value);

        if (selectedCategory?.allow_team) {
            loadTeams(competition_id.value);
        }
    });

    /* ================== MODALE ================== */

    openModal.addEventListener('click', () => {
        if (!canCreate) return;

        resetModal();
        populateSelect(cat1, null, "Entrate / Uscite");
        showModal();
    });

    closeModal.addEventListener('click', () => hideModal());

    /* ================== EDIT ================== */

    async function openEdit(id) {

        resetModal();

        editMode = true;
        editId = id;

        const res = await fetchJSON(`../api/v1/movements?id=${encodeURIComponent(id)}`);
        if (!res || !res.success) {
            await AppDialog.message(res?.error || "Movimento non trovato");
            return;
        }

        const m = res.data;

        if (!m) {
            await AppDialog.message("Movimento non trovato");
            return;
        }

        movement_date.value = m.movement_date;
        amount.value = m.amount;
        description.value = m.description ?? '';

        const chain = getCategoryChain(m.category_id);

        populateSelect(cat1, null, "Entrate / Uscite");
        cat1.value = chain[0]?.id || '';

        populateSelect(cat2, cat1.value, "Categoria");
        cat2.value = chain[1]?.id || '';

        populateSelect(cat3, cat2.value, "Sottocategoria");
        cat3.value = chain[2]?.id || '';

        populateSelect(cat4, cat3.value, "Dettaglio");
        cat4.value = chain[3]?.id || '';

        const finalCategory = chain.at(-1);

        if (finalCategory?.allow_competition) {
            toggleSection('wrapCompetition', true);
            await loadCompetitions();
            competition_id.value = m.competition_id;
        }

        if (finalCategory?.allow_team) {
            toggleSection('wrapTeam', true);
            await loadTeams(finalCategory.allow_competition ? m.competition_id : null);
            team_id.value = m.team_id;
        }

        if (finalCategory?.allow_referee) {
            toggleSection('wrapReferee', true);
            await loadReferees();
            referee_id.value = m.referee_id;
        }

        showModal();
    }

    /* ================== MOVEMENTS ================== */

    function loadMovements() {

        fetchJSON('../api/v1/movements')
            .then(json => {
                if (!hasData(json)) {
                    showToast(json?.error || 'Impossibile caricare i movimenti', 'error');
                    return;
                }

                const tbody = document.querySelector('#movements tbody');
                resetMovementsTable();
                tbody.innerHTML = '';

                json.data.forEach(m => {

                    const tr = document.createElement('tr');

                    const dateCell = document.createElement('td');
                    dateCell.className = 'text-center';
                    dateCell.textContent = m.movement_date;

                    const typeCell = document.createElement('td');
                    typeCell.textContent = m.type_name ?? '';

                    const categoryCell = document.createElement('td');
                    categoryCell.textContent = m.category_name ?? '';

                    const subcategoryCell = document.createElement('td');
                    subcategoryCell.textContent = m.subcategory_name ?? '';

                    const detailCell = document.createElement('td');
                    detailCell.textContent = m.detail_name ?? '';

                    const descriptionCell = document.createElement('td');
                    descriptionCell.textContent = m.description ?? '';

                    const amountCell = document.createElement('td');
                    amountCell.className = 'text-center';
                    amountCell.textContent = formatCurrency(m.amount);

                    const actionCell = document.createElement('td');
                    actionCell.className = 'text-center text-nowrap';
                    if (canEdit) {
                        const editButton = document.createElement('button');
                        editButton.type = 'button';
                        editButton.className = 'btn btn-xs btn-primary editBtn';
                        editButton.dataset.id = m.id;
                        editButton.textContent = 'Modifica';
                        actionCell.appendChild(editButton);
                    }

                    if (canDelete) {
                        const deleteButton = document.createElement('button');
                        deleteButton.type = 'button';
                        deleteButton.className = 'btn btn-xs btn-danger deleteBtn';
                        deleteButton.dataset.id = m.id;
                        deleteButton.textContent = 'Elimina';
                        deleteButton.style.marginLeft = canEdit ? '5px' : '0';
                        actionCell.appendChild(deleteButton);
                    }

                    if (!canEdit && !canDelete) {
                        actionCell.textContent = '-';
                    }

                    tr.append(
                        dateCell,
                        typeCell,
                        categoryCell,
                        subcategoryCell,
                        detailCell,
                        descriptionCell,
                        amountCell,
                        actionCell
                    );
                    tbody.appendChild(tr);
                });

                initMovementsTable();
            });
    }

    function loadMovementKpis() {
        return fetchJSON('../api/v1/movements-kpi')
            .then(json => {
                if (!json || !json.success || !json.data) {
                    showToast(json?.error || 'Impossibile caricare i KPI', 'error');
                    return;
                }

                document.getElementById('kpi-income').textContent = formatCurrency(json.data.income);
                document.getElementById('kpi-expenses').textContent = formatCurrency(json.data.expenses);
                document.getElementById('kpi-profit').textContent = formatCurrency(json.data.profit);
                document.getElementById('kpi-total').textContent = json.data.total_movements ?? 0;
            });
    }

    document.addEventListener('click', async e => {
        const editButton = e.target.closest('.editBtn');
        if (editButton) {
            if (!canEdit) return;

            openEdit(editButton.dataset.id);
            return;
        }

        const deleteButton = e.target.closest('.deleteBtn');
        if (!deleteButton || !canDelete) return;

        const confirmed = await AppDialog.confirm('Eliminare questo movimento?');
        if (!confirmed) {
            return;
        }

        const json = await fetchJSON(`../api/v1/movements?id=${encodeURIComponent(deleteButton.dataset.id)}`, {
            method: 'DELETE'
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Eliminazione non riuscita', 'error');
            return;
        }

        loadMovements();
        loadMovementKpis();
        showToast('Movimento eliminato', 'success');
    });

    movementForm.addEventListener('submit', async e => {
        e.preventDefault();
        if (editMode && !canEdit) {
            showToast('Permesso di modifica mancante', 'error');
            return;
        }

        if (!editMode && !canCreate) {
            showToast('Permesso di creazione mancante', 'error');
            return;
        }
        clearErrors();

        const payload = {
            category_id: cat4.value,
            amount: amount.value,
            movement_date: movement_date.value,
            description: description.value.trim(),
            competition_id: competition_id.disabled || !competition_id.value ? null : competition_id.value,
            team_id: team_id.disabled || !team_id.value ? null : team_id.value,
            referee_id: referee_id.disabled || !referee_id.value ? null : referee_id.value
        };

        const url = editMode
            ? `../api/v1/movements?id=${encodeURIComponent(editId)}`
            : '../api/v1/movements';

        const json = await fetchJSON(url, {
            method: editMode ? 'PUT' : 'POST',
            body: JSON.stringify(payload)
        });

        if (!json) return;

        if (!json.success) {
            showErrors(json.errors);
            showToast(json.error || 'Salvataggio non riuscito', 'error');
            return;
        }

        const wasEdit = editMode;

        hideModal();
        resetModal();
        loadMovements();
        loadMovementKpis();
        showToast(wasEdit ? 'Movimento aggiornato' : 'Movimento creato', 'success');
    });

    /* ================== LOOKUP ================== */

    function loadCompetitions() {

        return fetchJSON('../api/v1/competitions')
            .then(json => {
                if (!hasData(json)) return;

                resetSelect(competition_id);

                json.data.forEach(c => {
                    competition_id.appendChild(new Option(c.name, c.id));
                });

                competition_id.disabled = false;
                competition_id.required = true;
            });
    }

    function loadTeams(competitionId = null) {

        const url = competitionId
            ? `../api/v1/teams?competition_id=${encodeURIComponent(competitionId)}`
            : '../api/v1/teams';

        return fetchJSON(url)
            .then(json => {
                if (!hasData(json)) return;

                resetSelect(team_id);

                json.data.forEach(t => {
                    team_id.appendChild(new Option(t.name, t.id));
                });

                team_id.disabled = false;
                team_id.required = true;
            });
    }

    function loadReferees() {

        return fetchJSON('../api/v1/referees')
            .then(json => {
                if (!hasData(json)) return;

                resetSelect(referee_id);

                json.data.forEach(rf => {
                    referee_id.appendChild(new Option(rf.name, rf.id));
                });

                referee_id.disabled = false;
                referee_id.required = true;
            });
    }

    /* ================== INIT ================== */

    await loadCategoriesTree();
    loadMovementKpis();
    loadMovements();

});
