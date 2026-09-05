document.addEventListener('DOMContentLoaded', async () => {

    let teams = [];
    let teamsTable = null;
    let editMode = false;
    let editId = null;
    const canCreate = Auth.can('teams.create');
    const canEdit = Auth.can('teams.edit');
    const canDelete = Auth.can('teams.delete');
    const alertFilter = new URLSearchParams(window.location.search).get('alert');

    const modal = document.getElementById('teamModal');
    const openModal = document.getElementById('openTeamModal');
    const closeModal = document.getElementById('closeTeamModal');
    const teamForm = document.getElementById('teamForm');

    const teamName = document.getElementById('team_name');
    const fieldId = document.getElementById('field_id');
    const competitionIds = document.getElementById('competition_ids');

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
            field_id: 'wrapField',
            competition_ids: 'wrapCompetitions'
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

    function selectedCompetitionIds() {
        return Array.from(competitionIds.selectedOptions).map(option => option.value);
    }

    function resetSelect(select, placeholder = 'Seleziona') {
        select.innerHTML = '';
        select.appendChild(new Option(placeholder, ''));
    }

    function resetForm() {
        teamForm.reset();
        clearErrors();
        editMode = false;
        editId = null;
        fieldId.value = '';
        Array.from(competitionIds.options).forEach(option => {
            option.selected = false;
        });
    }

    function showModal() {
        if (window.$ && $.fn.modal) {
            $('#teamModal').modal('show');
            return;
        }

        modal.style.display = 'block';
    }

    function hideModal() {
        if (window.$ && $.fn.modal) {
            $('#teamModal').modal('hide');
            return;
        }

        modal.style.display = 'none';
    }

    function resetTeamsTable() {
        if (window.$ && $.fn.DataTable && $.fn.DataTable.isDataTable('#teams')) {
            $('#teams').DataTable().clear().destroy();
        }
    }

    function initTeamsTable() {
        if (!(window.$ && $.fn.DataTable)) {
            return;
        }

        teamsTable = $('#teams').DataTable({
            pageLength: 10,
            ordering: false,
            searching: true,
            paging: true,
            lengthChange: false,
            pagingType: 'full_numbers',
            autoWidth: false,
            columnDefs: [
                { targets: 0, width: '220px' },
                { targets: 2, width: '260px' },
                { targets: 3, width: '150px', className: 'text-center' },
                { targets: 4, width: '140px', className: 'text-center', orderable: false }
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

    function parseCompetitionIds(value) {
        if (!value) return [];

        return String(value)
            .split(',')
            .map(item => item.trim())
            .filter(Boolean);
    }

    async function loadCompetitions() {
        const json = await fetchJSON('../api/v1/competitions');
        competitionIds.innerHTML = '';

        if (!json || !json.success || !Array.isArray(json.data)) {
            showToast(json?.error || 'Impossibile caricare le competizioni', 'error');
            return;
        }

        json.data.forEach(competition => {
            competitionIds.appendChild(new Option(competition.name, competition.id));
        });
    }

    async function loadFields() {
        const json = await fetchJSON('../api/v1/fields?active=1');
        resetSelect(fieldId, 'Nessuno stadio');

        if (!json || !json.success || !Array.isArray(json.data)) {
            showToast(json?.error || 'Impossibile caricare gli stadi', 'error');
            return;
        }

        json.data.forEach(field => {
            fieldId.appendChild(new Option(field.name, field.id));
        });
    }

    function loadTeams() {
        return fetchJSON('../api/v1/teams')
            .then(json => {
                if (!json || !json.success || !Array.isArray(json.data)) {
                    showToast(json?.error || 'Impossibile caricare le squadre', 'error');
                    return;
                }

                teams = json.data;

                const tbody = document.querySelector('#teams tbody');
                resetTeamsTable();
                tbody.innerHTML = '';

                const visibleTeams = alertFilter === 'without_field'
                    ? teams.filter(team => !team.field_id)
                    : teams;

                visibleTeams.forEach(team => {
                    const row = document.createElement('tr');

                    appendCell(row, team.name);
                    appendCell(row, team.field_name || '-');
                    appendCell(row, team.competitions || '-');
                    appendCell(row, team.created_at).className = 'text-center';

                    const actionsCell = document.createElement('td');
                    actionsCell.className = 'text-center';
                    if (!canEdit && !canDelete) {
                        actionsCell.textContent = '-';
                    } else {
                        if (canEdit) {
                            const editButton = document.createElement('button');
                            editButton.type = 'button';
                            editButton.className = 'btn btn-xs btn-primary editTeamBtn';
                            editButton.dataset.id = team.id;
                            editButton.textContent = 'Modifica';
                            actionsCell.appendChild(editButton);
                        }

                        if (canDelete) {
                            const deleteButton = document.createElement('button');
                            deleteButton.type = 'button';
                            deleteButton.className = 'btn btn-xs btn-danger deleteTeamBtn';
                            deleteButton.dataset.id = team.id;
                            deleteButton.textContent = 'Elimina';
                            deleteButton.style.marginLeft = canEdit ? '5px' : '0';
                            actionsCell.appendChild(deleteButton);
                        }
                    }
                    row.appendChild(actionsCell);

                    tbody.appendChild(row);
                });

                initTeamsTable();
            });
    }

    function payload() {
        return {
            name: teamName.value.trim(),
            field_id: fieldId.value || null,
            competition_ids: selectedCompetitionIds()
        };
    }

    function openEdit(id) {
        const team = teams.find(item => String(item.id) === String(id));
        if (!team) return;

        resetForm();
        editMode = true;
        editId = id;

        teamName.value = team.name || '';
        fieldId.value = team.field_id || '';

        const currentCompetitionIds = parseCompetitionIds(team.competition_ids);
        Array.from(competitionIds.options).forEach(option => {
            option.selected = currentCompetitionIds.includes(String(option.value));
        });

        showModal();
    }

    openModal.addEventListener('click', () => {
        if (!canCreate) return;

        resetForm();
        showModal();
    });

    closeModal.addEventListener('click', () => hideModal());

    teamForm.addEventListener('submit', async e => {
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
            ? `../api/v1/teams?id=${encodeURIComponent(editId)}`
            : '../api/v1/teams';

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
        loadTeams();
        showToast(wasEdit ? 'Squadra aggiornata' : 'Squadra creata', 'success');
    });

    document.addEventListener('click', async e => {
        const editButton = e.target.closest('.editTeamBtn');
        if (editButton) {
            if (!canEdit) return;

            openEdit(editButton.dataset.id);
            return;
        }

        const deleteButton = e.target.closest('.deleteTeamBtn');
        if (!deleteButton || !canDelete) return;

        const team = teams.find(item => String(item.id) === String(deleteButton.dataset.id));
        const label = team?.name || 'questa squadra';

        if (!await AppDialog.confirm(`Eliminare ${label}?`)) {
            return;
        }

        const json = await fetchJSON(`../api/v1/teams?id=${encodeURIComponent(deleteButton.dataset.id)}`, {
            method: 'DELETE'
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Eliminazione non riuscita', 'error');
            return;
        }

        loadTeams();
        showToast('Squadra eliminata', 'success');
    });

    await loadCompetitions();
    await loadFields();
    loadTeams();
});
