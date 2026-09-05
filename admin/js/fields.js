document.addEventListener('DOMContentLoaded', () => {

    let fields = [];
    let fieldsTable = null;
    let editMode = false;
    let editId = null;
    const canCreate = Auth.can('fields.create');
    const canEdit = Auth.can('fields.edit');
    const canDelete = Auth.can('fields.delete');
    const alertFilter = new URLSearchParams(window.location.search).get('alert');

    const modal = document.getElementById('fieldModal');
    const openModal = document.getElementById('openFieldModal');
    const closeModal = document.getElementById('closeFieldModal');
    const fieldForm = document.getElementById('fieldForm');

    const fieldName = document.getElementById('field_name');
    const fieldAddress = document.getElementById('field_address');
    const fieldCity = document.getElementById('field_city');
    const fieldProvince = document.getElementById('field_province');
    const fieldPostalCode = document.getElementById('field_postal_code');
    const fieldCountry = document.getElementById('field_country');
    const geocodeButton = document.getElementById('geocodeField');
    const geocodeResult = document.getElementById('fieldGeocodeResult');
    const canHost11 = document.getElementById('can_host_11');
    const canHost7 = document.getElementById('can_host_7');
    const canHost5 = document.getElementById('can_host_5');
    const isActive = document.getElementById('is_active');
    const fieldNotes = document.getElementById('field_notes');

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

    function clearErrors() {
        document.querySelectorAll('.wrapper-error')
            .forEach(el => el.classList.remove('wrapper-error'));

        document.querySelectorAll('.error-msg')
            .forEach(el => el.textContent = '');
    }

    function fieldWrapper(field) {
        const map = {
            name: 'wrapName',
            address: 'wrapAddress',
            city: 'wrapCity',
            province: 'wrapProvince',
            postal_code: 'wrapPostalCode',
            country: 'wrapCountry',
            football_types: 'wrapFootballTypes',
            is_active: 'wrapStatus',
            notes: 'wrapNotes'
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

    function resetForm() {
        fieldForm.reset();
        clearErrors();
        editMode = false;
        editId = null;
        canHost11.checked = true;
        canHost7.checked = true;
        canHost5.checked = true;
        isActive.checked = true;
        fieldCountry.value = 'Italia';
        updateGeocodeResult(null);
    }

    function showModal() {
        if (window.$ && $.fn.modal) {
            $('#fieldModal').modal('show');
            return;
        }

        modal.style.display = 'block';
    }

    function hideModal() {
        if (window.$ && $.fn.modal) {
            $('#fieldModal').modal('hide');
            return;
        }

        modal.style.display = 'none';
    }

    function resetFieldsTable() {
        if (window.$ && $.fn.DataTable && $.fn.DataTable.isDataTable('#fields')) {
            $('#fields').DataTable().clear().destroy();
        }
    }

    function initFieldsTable() {
        if (!(window.$ && $.fn.DataTable)) {
            return;
        }

        fieldsTable = $('#fields').DataTable({
            pageLength: 10,
            ordering: false,
            searching: true,
            paging: true,
            lengthChange: false,
            pagingType: 'full_numbers',
            autoWidth: false,
            columnDefs: [
                { targets: 0, width: '180px' },
                { targets: 4, width: '100px', className: 'text-center' },
                { targets: 5, width: '100px', className: 'text-center' },
                { targets: 6, width: '100px', className: 'text-center' },
                { targets: 7, width: '90px', className: 'text-center' },
                { targets: 8, width: '140px', className: 'text-center', orderable: false }
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/it-IT.json'
            }
        });
    }

    function appendCell(row, text) {
        const cell = document.createElement('td');
        cell.textContent = text ?? '';
        row.appendChild(cell);
        return cell;
    }

    function appendBoolCell(row, value, yes = 'Si', no = 'No') {
        const cell = document.createElement('td');
        cell.innerHTML = Number(value) === 1
            ? `<span class="label label-success">${yes}</span>`
            : `<span class="label label-default">${no}</span>`;
        row.appendChild(cell);
        return cell;
    }

    function updateGeocodeResult(field) {
        if (!geocodeResult) return;

        if (field?.latitude && field?.longitude) {
            geocodeResult.textContent = `Coordinate: ${field.latitude}, ${field.longitude}`;
            return;
        }

        geocodeResult.textContent = 'Coordinate non verificate';
    }

    function loadFields() {
        return fetchJSON('../api/v1/fields')
            .then(json => {
                if (!json || !json.success || !Array.isArray(json.data)) {
                    showToast(json?.error || 'Impossibile caricare gli stadi', 'error');
                    return;
                }

                fields = json.data;

                const tbody = document.querySelector('#fields tbody');
                resetFieldsTable();
                tbody.innerHTML = '';

                const visibleFields = alertFilter === 'without_geocode'
                    ? fields.filter(field => !field.latitude || !field.longitude)
                    : fields;

                visibleFields.forEach(field => {
                    const row = document.createElement('tr');

                    appendCell(row, field.name);
                    appendCell(row, field.address || '-');
                    appendCell(row, field.city || '-');
                    appendCell(row, field.province || '-').className = 'text-center';
                    appendBoolCell(row, field.can_host_11);
                    appendBoolCell(row, field.can_host_7);
                    appendBoolCell(row, field.can_host_5);
                    appendBoolCell(row, field.is_active, 'Attivo', 'No');

                    const actionsCell = document.createElement('td');
                    actionsCell.className = 'text-center';
                    if (!canEdit && !canDelete) {
                        actionsCell.textContent = '-';
                    } else {
                        if (canEdit) {
                            const editButton = document.createElement('button');
                            editButton.type = 'button';
                            editButton.className = 'btn btn-xs btn-primary editFieldBtn';
                            editButton.dataset.id = field.id;
                            editButton.textContent = 'Modifica';
                            actionsCell.appendChild(editButton);
                        }

                        if (canDelete) {
                            const deleteButton = document.createElement('button');
                            deleteButton.type = 'button';
                            deleteButton.className = 'btn btn-xs btn-danger deleteFieldBtn';
                            deleteButton.dataset.id = field.id;
                            deleteButton.textContent = 'Elimina';
                            deleteButton.style.marginLeft = canEdit ? '5px' : '0';
                            actionsCell.appendChild(deleteButton);
                        }
                    }
                    row.appendChild(actionsCell);

                    tbody.appendChild(row);
                });

                initFieldsTable();
            });
    }

    function payload() {
        return {
            name: fieldName.value.trim(),
            address: fieldAddress.value.trim(),
            city: fieldCity.value.trim(),
            province: fieldProvince.value.trim(),
            postal_code: fieldPostalCode.value.trim(),
            country: fieldCountry.value.trim() || 'Italia',
            can_host_11: canHost11.checked ? 1 : 0,
            can_host_7: canHost7.checked ? 1 : 0,
            can_host_5: canHost5.checked ? 1 : 0,
            is_active: isActive.checked ? 1 : 0,
            notes: fieldNotes.value.trim()
        };
    }

    function openEdit(id) {
        const field = fields.find(item => String(item.id) === String(id));
        if (!field) return;

        resetForm();
        editMode = true;
        editId = id;

        fieldName.value = field.name || '';
        fieldAddress.value = field.address || '';
        fieldCity.value = field.city || '';
        fieldProvince.value = field.province || '';
        fieldPostalCode.value = field.postal_code || '';
        fieldCountry.value = field.country || 'Italia';
        updateGeocodeResult(field);
        canHost11.checked = Number(field.can_host_11) === 1;
        canHost7.checked = Number(field.can_host_7) === 1;
        canHost5.checked = Number(field.can_host_5) === 1;
        isActive.checked = Number(field.is_active) === 1;
        fieldNotes.value = field.notes || '';

        showModal();
    }

    openModal.addEventListener('click', () => {
        if (!canCreate) return;

        resetForm();
        showModal();
    });

    closeModal.addEventListener('click', () => hideModal());

    geocodeButton.addEventListener('click', async () => {
        if (!canEdit) return;

        if (!editMode || !editId) {
            showToast('Salva prima lo stadio, poi verifica l\'indirizzo', 'error');
            return;
        }

        geocodeButton.disabled = true;

        const json = await fetchJSON(`../api/v1/fields-geocode?id=${encodeURIComponent(editId)}`, {
            method: 'PUT',
            body: JSON.stringify({})
        });

        geocodeButton.disabled = false;

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Geocodifica non riuscita', 'error');
            return;
        }

        geocodeResult.textContent = `Coordinate: ${json.data.latitude}, ${json.data.longitude}`;
        showToast('Indirizzo verificato', 'success');
        loadFields();
    });

    fieldForm.addEventListener('submit', async e => {
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

        const url = editMode
            ? `../api/v1/fields?id=${encodeURIComponent(editId)}`
            : '../api/v1/fields';

        const json = await fetchJSON(url, {
            method: editMode ? 'PUT' : 'POST',
            body: JSON.stringify(payload())
        });

        if (!json) return;

        if (!json.success) {
            showErrors(json.errors);
            showToast(json.error || 'Salvataggio non riuscito', 'error');
            return;
        }

        const wasEdit = editMode;

        hideModal();
        resetForm();
        loadFields();
        showToast(wasEdit ? 'Stadio aggiornato' : 'Stadio creato', 'success');
    });

    document.addEventListener('click', async e => {
        const editButton = e.target.closest('.editFieldBtn');
        if (editButton) {
            if (!canEdit) return;

            openEdit(editButton.dataset.id);
            return;
        }

        const deleteButton = e.target.closest('.deleteFieldBtn');
        if (!deleteButton || !canDelete) return;

        const field = fields.find(item => String(item.id) === String(deleteButton.dataset.id));
        const label = field?.name || 'questo stadio';

        if (!await AppDialog.confirm(`Eliminare ${label}?`)) {
            return;
        }

        const json = await fetchJSON(`../api/v1/fields?id=${encodeURIComponent(deleteButton.dataset.id)}`, {
            method: 'DELETE'
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Eliminazione non riuscita', 'error');
            return;
        }

        loadFields();
        showToast('Stadio eliminato', 'success');
    });

    loadFields();
});
