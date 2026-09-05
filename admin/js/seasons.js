document.addEventListener('DOMContentLoaded', () => {

    let seasonsTable = null;
    const canCreate = Auth.can('seasons.create');
    const canChangeStatus = Auth.can('seasons.status.change');
    const alertFilter = new URLSearchParams(window.location.search).get('alert');

    const modal = document.getElementById('seasonModal');
    const openModal = document.getElementById('openSeasonModal');
    const closeModal = document.getElementById('closeSeasonModal');
    const seasonForm = document.getElementById('seasonForm');

    const seasonName = document.getElementById('season_name');
    const startsOn = document.getElementById('starts_on');
    const endsOn = document.getElementById('ends_on');

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
            starts_on: 'wrapStartsOn',
            ends_on: 'wrapEndsOn'
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
        seasonForm.reset();
        clearErrors();
    }

    function showModal() {
        if (window.$ && $.fn.modal) {
            $('#seasonModal').modal('show');
            return;
        }

        modal.style.display = 'block';
    }

    function hideModal() {
        if (window.$ && $.fn.modal) {
            $('#seasonModal').modal('hide');
            return;
        }

        modal.style.display = 'none';
    }

    function resetSeasonsTable() {
        if (window.$ && $.fn.DataTable && $.fn.DataTable.isDataTable('#seasons')) {
            $('#seasons').DataTable().clear().destroy();
        }
    }

    function initSeasonsTable() {
        if (!(window.$ && $.fn.DataTable)) {
            return;
        }

        seasonsTable = $('#seasons').DataTable({
            pageLength: 10,
            ordering: false,
            searching: true,
            paging: true,
            lengthChange: false,
            pagingType: 'full_numbers',
            autoWidth: false,
            columnDefs: [
                { targets: 0, width: '160px' },
                { targets: 1, width: '120px', className: 'text-center' },
                { targets: 2, width: '120px', className: 'text-center' },
                { targets: 3, width: '100px', className: 'text-center' },
                { targets: 4, width: '150px', className: 'text-center' },
                { targets: 5, width: '150px', className: 'text-center', orderable: false }
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

    function statusLabel(status, isCurrent) {
        const value = status || (isCurrent == 1 ? 'in_corso' : 'nuovo');
        const labels = {
            nuovo: '<span class="label label-default">Nuovo</span>',
            in_corso: '<span class="label label-success">In corso</span>',
            chiuso: '<span class="label label-danger">Chiuso</span>'
        };

        return labels[value] || labels.nuovo;
    }

    function statusActions(season) {
        const status = season.status || (season.is_current == 1 ? 'in_corso' : 'nuovo');
        const fragment = document.createDocumentFragment();

        if (status === 'nuovo') {
            const startButton = document.createElement('button');
            startButton.type = 'button';
            startButton.className = 'btn btn-xs btn-success setStatusBtn';
            startButton.dataset.id = season.id;
            startButton.dataset.status = 'in_corso';
            startButton.textContent = 'Avvia';
            fragment.appendChild(startButton);

            const closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = 'btn btn-xs btn-danger setStatusBtn';
            closeButton.dataset.id = season.id;
            closeButton.dataset.status = 'chiuso';
            closeButton.dataset.name = season.name;
            closeButton.textContent = 'Chiudi';
            closeButton.style.marginLeft = '5px';
            fragment.appendChild(closeButton);
        }

        if (status === 'in_corso') {
            const closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = 'btn btn-xs btn-danger setStatusBtn';
            closeButton.dataset.id = season.id;
            closeButton.dataset.status = 'chiuso';
            closeButton.dataset.name = season.name;
            closeButton.textContent = 'Chiudi';
            fragment.appendChild(closeButton);
        }

        return fragment;
    }

    function statusToast(status) {
        const labels = {
            nuovo: 'Stagione riportata a nuovo',
            in_corso: 'Stagione avviata',
            chiuso: 'Stagione chiusa'
        };

        return labels[status] || 'Stato stagione aggiornato';
    }

    function isSeasonToClose(season) {
        const status = season.status || (season.is_current == 1 ? 'in_corso' : 'nuovo');
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const endsOn = new Date(`${season.ends_on}T00:00:00`);

        return status !== 'chiuso' && endsOn < today;
    }

    function loadSeasons() {
        return fetchJSON('../api/v1/seasons')
            .then(json => {
                if (!json || !json.success || !Array.isArray(json.data)) {
                    showToast(json?.error || 'Impossibile caricare le stagioni', 'error');
                    return;
                }

                const tbody = document.querySelector('#seasons tbody');
                resetSeasonsTable();
                tbody.innerHTML = '';

                const visibleSeasons = alertFilter === 'to_close'
                    ? json.data.filter(isSeasonToClose)
                    : json.data;

                visibleSeasons.forEach(season => {
                    const row = document.createElement('tr');

                    appendCell(row, season.name);
                    appendCell(row, season.starts_on).className = 'text-center';
                    appendCell(row, season.ends_on).className = 'text-center';

                    const statusCell = document.createElement('td');
                    statusCell.className = 'text-center';
                    statusCell.innerHTML = statusLabel(season.status, season.is_current);
                    row.appendChild(statusCell);

                    appendCell(row, season.created_at).className = 'text-center';

                    const actionsCell = document.createElement('td');
                    actionsCell.className = 'text-center';
                    const actions = statusActions(season);
                    if (!canChangeStatus || !actions.childNodes.length) {
                        actionsCell.textContent = '-';
                    } else {
                        actionsCell.appendChild(actions);
                    }
                    row.appendChild(actionsCell);

                    tbody.appendChild(row);
                });

                initSeasonsTable();
            });
    }

    openModal.addEventListener('click', () => {
        if (!canCreate) return;

        resetForm();
        showModal();
    });

    closeModal.addEventListener('click', () => hideModal());

    seasonForm.addEventListener('submit', async e => {
        e.preventDefault();
        if (!canCreate) {
            showToast('Permesso di creazione mancante', 'error');
            return;
        }
        clearErrors();

        const json = await fetchJSON('../api/v1/seasons', {
            method: 'POST',
            body: JSON.stringify({
                name: seasonName.value.trim(),
                starts_on: startsOn.value,
                ends_on: endsOn.value
            })
        });

        if (!json) return;

        if (!json.success) {
            showErrors(json.errors);
            showToast(json.error || 'Salvataggio non riuscito', 'error');
            return;
        }

        hideModal();
        resetForm();
        loadSeasons();
        showToast('Stagione creata', 'success');
    });

    document.addEventListener('click', async e => {
        const button = e.target.closest('.setStatusBtn');
        if (!button) return;
        if (!canChangeStatus) return;

        if (button.dataset.status === 'chiuso') {
            const seasonName = button.dataset.name || 'questa stagione';
            const confirmed = await AppDialog.open({
                title: 'Conferma chiusura stagione',
                message: `Chiudere ${seasonName}? Verrà salvato lo snapshot ufficiale del bilancio.`,
                confirmText: 'Chiudi stagione',
                confirmClass: 'btn-danger',
                showCancel: true
            });

            if (!confirmed) return;
        }

        const json = await fetchJSON(`../api/v1/seasons-status?id=${encodeURIComponent(button.dataset.id)}`, {
            method: 'PUT',
            body: JSON.stringify({
                status: button.dataset.status
            })
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Operazione non riuscita', 'error');
            return;
        }

        loadSeasons();
        if (typeof loadCurrentSeasonBadge === 'function') {
            loadCurrentSeasonBadge();
        }
        showToast(statusToast(button.dataset.status), 'success');
    });

    loadSeasons();
});
