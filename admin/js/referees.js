document.addEventListener('DOMContentLoaded', () => {

    let referees = [];
    let refereesTable = null;
    let editMode = false;
    let editId = null;
    const canCreate = Auth.can('referees.create');
    const canEdit = Auth.can('referees.edit');
    const canDelete = Auth.can('referees.delete');
    const canViewAvailability = Auth.can('referee_availabilities.view');
    const canCreateAvailability = Auth.can('referee_availabilities.create');
    const canEditAvailability = Auth.can('referee_availabilities.edit');
    const canDeleteAvailability = Auth.can('referee_availabilities.delete');
    const alertFilter = new URLSearchParams(window.location.search).get('alert');

    const modal = document.getElementById('refereeModal');
    const availabilityModal = document.getElementById('availabilityModal');
    const openModal = document.getElementById('openRefereeModal');
    const closeModal = document.getElementById('closeRefereeModal');
    const closeAvailabilityModal = document.getElementById('closeAvailabilityModal');
    const refereeForm = document.getElementById('refereeForm');
    const recurringAvailabilityForm = document.getElementById('recurringAvailabilityForm');
    const specificAvailabilityForm = document.getElementById('specificAvailabilityForm');

    const refereeName = document.getElementById('referee_name');
    const rating = document.getElementById('rating');
    const address = document.getElementById('address');
    const city = document.getElementById('city');
    const province = document.getElementById('province');
    const postalCode = document.getElementById('postal_code');
    const country = document.getElementById('country');
    const geocodeButton = document.getElementById('geocodeReferee');
    const geocodeResult = document.getElementById('geocodeResult');
    const canReferee11 = document.getElementById('can_referee_11');
    const canReferee7 = document.getElementById('can_referee_7');
    const canReferee5 = document.getElementById('can_referee_5');
    const availabilityRefereeName = document.getElementById('availabilityRefereeName');
    const recurringAvailabilityRows = document.getElementById('recurringAvailabilityRows');
    const specificAvailabilityRows = document.getElementById('specificAvailabilityRows');
    const recurringAvailabilityId = document.getElementById('recurringAvailabilityId');
    const recurringWeekday = document.getElementById('recurringWeekday');
    const recurringStartTime = document.getElementById('recurringStartTime');
    const recurringEndTime = document.getElementById('recurringEndTime');
    const recurringIsAvailable = document.getElementById('recurringIsAvailable');
    const recurringNotes = document.getElementById('recurringNotes');
    const specificAvailabilityId = document.getElementById('specificAvailabilityId');
    const specificDate = document.getElementById('specificDate');
    const specificStartTime = document.getElementById('specificStartTime');
    const specificEndTime = document.getElementById('specificEndTime');
    const specificIsAvailable = document.getElementById('specificIsAvailable');
    const specificNotes = document.getElementById('specificNotes');
    const resetRecurringAvailability = document.getElementById('resetRecurringAvailability');
    const resetSpecificAvailability = document.getElementById('resetSpecificAvailability');
    let availabilityRefereeId = null;
    let refereeAvailabilities = [];

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
            rating: 'wrapRating',
            address: 'wrapAddress',
            city: 'wrapCity',
            province: 'wrapProvince',
            postal_code: 'wrapPostalCode',
            country: 'wrapCountry',
            football_types: 'wrapFootballTypes'
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
        refereeForm.reset();
        clearErrors();
        editMode = false;
        editId = null;
        rating.value = '3';
        country.value = 'Italia';
        canReferee11.checked = true;
        canReferee7.checked = true;
        canReferee5.checked = true;
        updateGeocodeResult(null);
    }

    function showModal() {
        if (window.$ && $.fn.modal) {
            $('#refereeModal').modal('show');
            return;
        }

        modal.style.display = 'block';
    }

    function hideModal() {
        if (window.$ && $.fn.modal) {
            $('#refereeModal').modal('hide');
            return;
        }

        modal.style.display = 'none';
    }

    function showAvailabilityModal() {
        if (window.$ && $.fn.modal) {
            $('#availabilityModal').modal('show');
            return;
        }

        availabilityModal.style.display = 'block';
    }

    function hideAvailabilityModal() {
        if (window.$ && $.fn.modal) {
            $('#availabilityModal').modal('hide');
            return;
        }

        availabilityModal.style.display = 'none';
    }

    function applyAvailabilityPermissions() {
        const canWrite = canCreateAvailability || canEditAvailability;

        if (recurringAvailabilityForm) {
            recurringAvailabilityForm.style.display = canWrite ? '' : 'none';
        }

        if (specificAvailabilityForm) {
            specificAvailabilityForm.style.display = canWrite ? '' : 'none';
        }
    }

    function weekdayLabel(value) {
        const labels = {
            1: 'Lunedi',
            2: 'Martedi',
            3: 'Mercoledi',
            4: 'Giovedi',
            5: 'Venerdi',
            6: 'Sabato',
            7: 'Domenica'
        };

        return labels[Number(value)] || '-';
    }

    function formatDate(value) {
        if (!value) return '-';

        const [year, month, day] = String(value).split('-');

        return year && month && day ? `${day}/${month}/${year}` : value;
    }

    function formatTime(value) {
        return String(value || '').slice(0, 5) || '-';
    }

    function resetRefereesTable() {
        if (window.$ && $.fn.DataTable && $.fn.DataTable.isDataTable('#referees')) {
            $('#referees').DataTable().clear().destroy();
        }
    }

    function initRefereesTable() {
        if (!(window.$ && $.fn.DataTable)) {
            return;
        }

        refereesTable = $('#referees').DataTable({
            pageLength: 10,
            ordering: false,
            searching: true,
            paging: true,
            lengthChange: false,
            pagingType: 'full_numbers',
            autoWidth: false,
            columnDefs: [
                { targets: 0, width: '220px' },
                { targets: 1, width: '180px' },
                { targets: 2, width: '120px', className: 'text-center' },
                { targets: 3, width: '110px', className: 'text-center' },
                { targets: 4, width: '110px', className: 'text-center' },
                { targets: 5, width: '110px', className: 'text-center' },
                { targets: 6, width: '150px', className: 'text-center' },
                { targets: 7, width: '140px', className: 'text-center', orderable: false }
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

    function appendBoolCell(row, value) {
        const cell = document.createElement('td');
        cell.className = 'text-center';
        cell.innerHTML = Number(value) === 1
            ? '<span class="label label-success">Si</span>'
            : '<span class="label label-default">No</span>';
        row.appendChild(cell);
        return cell;
    }

    function appendRatingCell(row, value) {
        const cell = document.createElement('td');
        const ratingValue = Math.max(1, Math.min(5, Number(value || 3)));
        cell.className = 'referee-rating-cell text-center';
        cell.textContent = '\u2605'.repeat(ratingValue) + '\u2606'.repeat(5 - ratingValue);
        row.appendChild(cell);
        return cell;
    }

    function appendAvailabilityStatusCell(row, value) {
        const cell = document.createElement('td');
        cell.className = 'text-center';
        const label = document.createElement('span');
        label.className = Number(value) === 1 ? 'label label-success' : 'label label-danger';
        label.textContent = Number(value) === 1 ? 'Disponibile' : 'Non disponibile';
        cell.appendChild(label);
        row.appendChild(cell);
        return cell;
    }

    function availabilityFieldWrapper(field, type) {
        const recurringMap = {
            weekday: 'wrapRecurringWeekday',
            start_time: 'wrapRecurringStartTime',
            end_time: 'wrapRecurringEndTime',
            notes: 'wrapRecurringNotes'
        };

        const specificMap = {
            available_date: 'wrapSpecificDate',
            start_time: 'wrapSpecificStartTime',
            end_time: 'wrapSpecificEndTime',
            notes: 'wrapSpecificNotes'
        };

        const map = type === 'specific' ? specificMap : recurringMap;

        return document.getElementById(map[field] || '');
    }

    function showAvailabilityErrors(errors = {}, type = 'recurring') {
        Object.entries(errors).forEach(([field, message]) => {
            const wrapper = availabilityFieldWrapper(field, type);
            if (!wrapper) return;

            wrapper.classList.add('wrapper-error');

            const error = wrapper.querySelector('.error-msg');
            if (error) error.textContent = message;
        });
    }

    function resetAvailabilityForm(type) {
        if (type === 'specific') {
            specificAvailabilityId.value = '';
            specificDate.value = '';
            specificStartTime.value = '19:00';
            specificEndTime.value = '23:59';
            specificIsAvailable.value = '1';
            specificNotes.value = '';
            return;
        }

        recurringAvailabilityId.value = '';
        recurringWeekday.value = '1';
        recurringStartTime.value = '19:00';
        recurringEndTime.value = '23:59';
        recurringIsAvailable.value = '1';
        recurringNotes.value = '';
    }

    function availabilityPayload(type) {
        if (type === 'specific') {
            return {
                referee_id: availabilityRefereeId,
                type: 'specific',
                available_date: specificDate.value,
                start_time: specificStartTime.value,
                end_time: specificEndTime.value,
                is_available: specificIsAvailable.value,
                notes: specificNotes.value.trim()
            };
        }

        return {
            referee_id: availabilityRefereeId,
            type: 'recurring',
            weekday: recurringWeekday.value,
            start_time: recurringStartTime.value,
            end_time: recurringEndTime.value,
            is_available: recurringIsAvailable.value,
            notes: recurringNotes.value.trim()
        };
    }

    function fillAvailabilityForm(availability) {
        clearErrors();

        if (availability.type === 'specific') {
            specificAvailabilityId.value = availability.id;
            specificDate.value = availability.available_date || '';
            specificStartTime.value = formatTime(availability.start_time);
            specificEndTime.value = formatTime(availability.end_time);
            specificIsAvailable.value = String(availability.is_available ?? 1);
            specificNotes.value = availability.notes || '';

            if (window.$) {
                $('a[href="#specificAvailabilityTab"]').tab('show');
            }

            return;
        }

        recurringAvailabilityId.value = availability.id;
        recurringWeekday.value = String(availability.weekday || 1);
        recurringStartTime.value = formatTime(availability.start_time);
        recurringEndTime.value = formatTime(availability.end_time);
        recurringIsAvailable.value = String(availability.is_available ?? 1);
        recurringNotes.value = availability.notes || '';

        if (window.$) {
            $('a[href="#recurringAvailabilityTab"]').tab('show');
        }
    }

    function updateGeocodeResult(referee) {
        if (!geocodeResult) return;

        if (referee?.latitude && referee?.longitude) {
            geocodeResult.textContent = `Coordinate: ${referee.latitude}, ${referee.longitude}`;
            return;
        }

        geocodeResult.textContent = 'Coordinate non verificate';
    }

    function residenceLabel(referee) {
        const location = [
            referee.city,
            referee.province
        ].filter(Boolean).join(' ');

        return location || referee.address || '-';
    }

    function renderAvailabilityActions(row, availability) {
        const actionsCell = document.createElement('td');
        actionsCell.className = 'text-center referee-availability-actions';

        if (!canEditAvailability && !canDeleteAvailability) {
            actionsCell.textContent = '-';
            row.appendChild(actionsCell);
            return;
        }

        if (canEditAvailability) {
            const editButton = document.createElement('button');
            editButton.type = 'button';
            editButton.className = 'btn btn-xs btn-primary editAvailabilityBtn';
            editButton.dataset.id = availability.id;
            editButton.title = 'Modifica disponibilita';
            editButton.setAttribute('aria-label', 'Modifica disponibilita');
            editButton.innerHTML = '<i class="fa fa-pencil"></i>';
            actionsCell.appendChild(editButton);
        }

        if (canDeleteAvailability) {
            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'btn btn-xs btn-danger deleteAvailabilityBtn';
            deleteButton.dataset.id = availability.id;
            deleteButton.title = 'Elimina disponibilita';
            deleteButton.setAttribute('aria-label', 'Elimina disponibilita');
            deleteButton.innerHTML = '<i class="fa fa-trash"></i>';
            actionsCell.appendChild(deleteButton);
        }

        row.appendChild(actionsCell);
    }

    function renderAvailabilities() {
        const recurring = refereeAvailabilities.filter(item => item.type === 'recurring');
        const specific = refereeAvailabilities.filter(item => item.type === 'specific');

        recurringAvailabilityRows.innerHTML = '';
        specificAvailabilityRows.innerHTML = '';

        if (!recurring.length) {
            const row = document.createElement('tr');
            appendCell(row, 'Nessuna disponibilita ricorrente').colSpan = 6;
            row.firstChild.className = 'text-center text-muted';
            recurringAvailabilityRows.appendChild(row);
        }

        recurring.forEach(item => {
            const row = document.createElement('tr');
            appendCell(row, weekdayLabel(item.weekday));
            appendCell(row, formatTime(item.start_time)).className = 'text-center text-nowrap';
            appendCell(row, formatTime(item.end_time)).className = 'text-center text-nowrap';
            appendAvailabilityStatusCell(row, item.is_available);
            appendCell(row, item.notes || '-');
            renderAvailabilityActions(row, item);
            recurringAvailabilityRows.appendChild(row);
        });

        if (!specific.length) {
            const row = document.createElement('tr');
            appendCell(row, 'Nessuna disponibilita puntuale').colSpan = 6;
            row.firstChild.className = 'text-center text-muted';
            specificAvailabilityRows.appendChild(row);
        }

        specific.forEach(item => {
            const row = document.createElement('tr');
            appendCell(row, formatDate(item.available_date)).className = 'text-center text-nowrap';
            appendCell(row, formatTime(item.start_time)).className = 'text-center text-nowrap';
            appendCell(row, formatTime(item.end_time)).className = 'text-center text-nowrap';
            appendAvailabilityStatusCell(row, item.is_available);
            appendCell(row, item.notes || '-');
            renderAvailabilityActions(row, item);
            specificAvailabilityRows.appendChild(row);
        });
    }

    function loadAvailabilities(refereeId) {
        return fetchJSON(`../api/v1/referee-availabilities?referee_id=${encodeURIComponent(refereeId)}`)
            .then(json => {
                if (!json || !json.success || !Array.isArray(json.data)) {
                    showToast(json?.error || 'Impossibile caricare le disponibilita', 'error');
                    return;
                }

                refereeAvailabilities = json.data;
                renderAvailabilities();
            });
    }

    function openAvailability(refereeId) {
        if (!canViewAvailability) return;

        const referee = referees.find(item => String(item.id) === String(refereeId));
        if (!referee) return;

        availabilityRefereeId = referee.id;
        refereeAvailabilities = [];
        availabilityRefereeName.textContent = ` - ${referee.name || ''}`;
        resetAvailabilityForm('recurring');
        resetAvailabilityForm('specific');
        clearErrors();
        applyAvailabilityPermissions();
        renderAvailabilities();
        showAvailabilityModal();
        loadAvailabilities(referee.id);
    }

    function loadReferees() {
        return fetchJSON('../api/v1/referees')
            .then(json => {
                if (!json || !json.success || !Array.isArray(json.data)) {
                    showToast(json?.error || 'Impossibile caricare gli arbitri', 'error');
                    return;
                }

                referees = json.data;

                const tbody = document.querySelector('#referees tbody');
                resetRefereesTable();
                tbody.innerHTML = '';

                const visibleReferees = alertFilter === 'without_address'
                    ? referees.filter(referee => !String(referee.address || '').trim() || !String(referee.city || '').trim())
                    : referees;

                visibleReferees.forEach(referee => {
                    const row = document.createElement('tr');

                    appendCell(row, referee.name);
                    appendCell(row, residenceLabel(referee));
                    appendRatingCell(row, referee.rating);
                    appendBoolCell(row, referee.can_referee_11);
                    appendBoolCell(row, referee.can_referee_7);
                    appendBoolCell(row, referee.can_referee_5);
                    appendCell(row, referee.created_at).className = 'text-center';

                    const actionsCell = document.createElement('td');
                    actionsCell.className = 'text-center';
                    if (!canViewAvailability && !canEdit && !canDelete) {
                        actionsCell.textContent = '-';
                    } else {
                        if (canViewAvailability) {
                            const availabilityButton = document.createElement('button');
                            availabilityButton.type = 'button';
                            availabilityButton.className = 'btn btn-xs btn-info availabilityRefereeBtn';
                            availabilityButton.dataset.id = referee.id;
                            availabilityButton.title = 'Disponibilita arbitro';
                            availabilityButton.setAttribute('aria-label', 'Disponibilita arbitro');
                            availabilityButton.innerHTML = '<i class="fa fa-calendar"></i>';
                            actionsCell.appendChild(availabilityButton);
                        }

                        if (canEdit) {
                            const editButton = document.createElement('button');
                            editButton.type = 'button';
                            editButton.className = 'btn btn-xs btn-primary editRefereeBtn';
                            editButton.dataset.id = referee.id;
                            editButton.title = 'Modifica arbitro';
                            editButton.setAttribute('aria-label', 'Modifica arbitro');
                            editButton.innerHTML = '<i class="fa fa-pencil"></i>';
                            actionsCell.appendChild(editButton);
                        }

                        if (canDelete) {
                            const deleteButton = document.createElement('button');
                            deleteButton.type = 'button';
                            deleteButton.className = 'btn btn-xs btn-danger deleteRefereeBtn';
                            deleteButton.dataset.id = referee.id;
                            deleteButton.title = 'Elimina arbitro';
                            deleteButton.setAttribute('aria-label', 'Elimina arbitro');
                            deleteButton.innerHTML = '<i class="fa fa-trash"></i>';
                            actionsCell.appendChild(deleteButton);
                        }
                    }
                    row.appendChild(actionsCell);

                    tbody.appendChild(row);
                });

                initRefereesTable();
            });
    }

    function payload() {
        return {
            name: refereeName.value.trim(),
            rating: rating.value,
            address: address.value.trim(),
            city: city.value.trim(),
            province: province.value.trim(),
            postal_code: postalCode.value.trim(),
            country: country.value.trim() || 'Italia',
            can_referee_11: canReferee11.checked ? 1 : 0,
            can_referee_7: canReferee7.checked ? 1 : 0,
            can_referee_5: canReferee5.checked ? 1 : 0
        };
    }

    function openEdit(id) {
        const referee = referees.find(item => String(item.id) === String(id));
        if (!referee) return;

        resetForm();
        editMode = true;
        editId = id;

        refereeName.value = referee.name || '';
        rating.value = String(referee.rating || 3);
        address.value = referee.address || '';
        city.value = referee.city || '';
        province.value = referee.province || '';
        postalCode.value = referee.postal_code || '';
        country.value = referee.country || 'Italia';
        updateGeocodeResult(referee);
        canReferee11.checked = Number(referee.can_referee_11) === 1;
        canReferee7.checked = Number(referee.can_referee_7) === 1;
        canReferee5.checked = Number(referee.can_referee_5) === 1;

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
            showToast('Salva prima l\'arbitro, poi verifica l\'indirizzo', 'error');
            return;
        }

        geocodeButton.disabled = true;

        const json = await fetchJSON(`../api/v1/referees-geocode?id=${encodeURIComponent(editId)}`, {
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
        loadReferees();
    });

    refereeForm.addEventListener('submit', async e => {
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
            ? `../api/v1/referees?id=${encodeURIComponent(editId)}`
            : '../api/v1/referees';

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
        loadReferees();
        showToast(wasEdit ? 'Arbitro aggiornato' : 'Arbitro creato', 'success');
    });

    async function saveAvailability(type) {
        if (!availabilityRefereeId) {
            showToast('Arbitro non selezionato', 'error');
            return;
        }

        const availabilityId = type === 'specific'
            ? specificAvailabilityId.value
            : recurringAvailabilityId.value;

        if (availabilityId && !canEditAvailability) {
            showToast('Permesso di modifica disponibilita mancante', 'error');
            return;
        }

        if (!availabilityId && !canCreateAvailability) {
            showToast('Permesso di creazione disponibilita mancante', 'error');
            return;
        }

        clearErrors();

        const url = availabilityId
            ? `../api/v1/referee-availabilities?id=${encodeURIComponent(availabilityId)}`
            : '../api/v1/referee-availabilities';

        const json = await fetchJSON(url, {
            method: availabilityId ? 'PUT' : 'POST',
            body: JSON.stringify(availabilityPayload(type))
        });

        if (!json) return;

        if (!json.success) {
            showAvailabilityErrors(json.errors || {}, type);
            showToast(json.error || 'Salvataggio disponibilita non riuscito', 'error');
            return;
        }

        resetAvailabilityForm(type);
        await loadAvailabilities(availabilityRefereeId);
        showToast('Disponibilita salvata', 'success');
    }

    recurringAvailabilityForm.addEventListener('submit', e => {
        e.preventDefault();
        saveAvailability('recurring');
    });

    specificAvailabilityForm.addEventListener('submit', e => {
        e.preventDefault();
        saveAvailability('specific');
    });

    resetRecurringAvailability.addEventListener('click', () => {
        clearErrors();
        resetAvailabilityForm('recurring');
    });

    resetSpecificAvailability.addEventListener('click', () => {
        clearErrors();
        resetAvailabilityForm('specific');
    });

    closeAvailabilityModal.addEventListener('click', () => hideAvailabilityModal());

    document.addEventListener('click', async e => {
        const availabilityButton = e.target.closest('.availabilityRefereeBtn');
        if (availabilityButton) {
            openAvailability(availabilityButton.dataset.id);
            return;
        }

        const editAvailabilityButton = e.target.closest('.editAvailabilityBtn');
        if (editAvailabilityButton && canEditAvailability) {
            const availability = refereeAvailabilities.find(item => String(item.id) === String(editAvailabilityButton.dataset.id));
            if (availability) {
                fillAvailabilityForm(availability);
            }
            return;
        }

        const deleteAvailabilityButton = e.target.closest('.deleteAvailabilityBtn');
        if (deleteAvailabilityButton && canDeleteAvailability) {
            const availability = refereeAvailabilities.find(item => String(item.id) === String(deleteAvailabilityButton.dataset.id));
            const label = availability?.type === 'specific'
                ? `la disponibilita del ${formatDate(availability.available_date)}`
                : `la disponibilita del ${weekdayLabel(availability?.weekday)}`;

            if (!await AppDialog.confirm(`Eliminare ${label}?`)) {
                return;
            }

            const json = await fetchJSON(`../api/v1/referee-availabilities?id=${encodeURIComponent(deleteAvailabilityButton.dataset.id)}`, {
                method: 'DELETE'
            });

            if (!json) return;

            if (!json.success) {
                showToast(json.error || 'Eliminazione disponibilita non riuscita', 'error');
                return;
            }

            await loadAvailabilities(availabilityRefereeId);
            showToast('Disponibilita eliminata', 'success');
            return;
        }

        const editButton = e.target.closest('.editRefereeBtn');
        if (editButton) {
            if (!canEdit) return;

            openEdit(editButton.dataset.id);
            return;
        }

        const deleteButton = e.target.closest('.deleteRefereeBtn');
        if (!deleteButton || !canDelete) return;

        const referee = referees.find(item => String(item.id) === String(deleteButton.dataset.id));
        const label = referee?.name || 'questo arbitro';

        if (!await AppDialog.confirm(`Eliminare ${label}?`)) {
            return;
        }

        const json = await fetchJSON(`../api/v1/referees?id=${encodeURIComponent(deleteButton.dataset.id)}`, {
            method: 'DELETE'
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Eliminazione non riuscita', 'error');
            return;
        }

        loadReferees();
        showToast('Arbitro eliminato', 'success');
    });

    loadReferees();
});
