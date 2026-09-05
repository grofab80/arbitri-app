document.addEventListener('DOMContentLoaded', async () => {

    let competitions = [];
    let competitionsTable = null;
    let editMode = false;
    let editId = null;
    const canCreate = Auth.can('competitions.create');
    const canEdit = Auth.can('competitions.edit');
    const canDelete = Auth.can('competitions.delete');
    const canManageStandings = Auth.can('competitions.standings.manage');
    const alertFilter = new URLSearchParams(window.location.search).get('alert');

    const modal = document.getElementById('competitionModal');
    const openModal = document.getElementById('openCompetitionModal');
    const closeModal = document.getElementById('closeCompetitionModal');
    const competitionForm = document.getElementById('competitionForm');
    const standingModal = document.getElementById('standingModal');
    const closeStandingModal = document.getElementById('closeStandingModal');
    const standingTitle = document.getElementById('standingTitle');
    const standingNotice = document.getElementById('standingNotice');
    const saveStandings = document.getElementById('saveStandings');
    const recalculateStandings = document.getElementById('recalculateStandings');

    const competitionName = document.getElementById('competition_name');
    const seasonId = document.getElementById('season_id');
    const competitionType = document.getElementById('competition_type');
    const footballType = document.getElementById('football_type');
    let standingCompetitionId = null;

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

    function resetSelect(select, placeholder = 'Seleziona') {
        select.innerHTML = '';
        select.appendChild(new Option(placeholder, ''));
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
            season_id: 'wrapSeason',
            type: 'wrapType',
            football_type: 'wrapFootballType'
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
        competitionForm.reset();
        clearErrors();
        editMode = false;
        editId = null;
    }

    function showModal() {
        if (window.$ && $.fn.modal) {
            $('#competitionModal').modal('show');
            return;
        }

        modal.style.display = 'block';
    }

    function hideModal() {
        if (window.$ && $.fn.modal) {
            $('#competitionModal').modal('hide');
            return;
        }

        modal.style.display = 'none';
    }

    function showStandingModal() {
        if (window.$ && $.fn.modal) {
            $('#standingModal').modal('show');
            return;
        }

        standingModal.style.display = 'block';
    }

    function hideStandingModal() {
        if (window.$ && $.fn.modal) {
            $('#standingModal').modal('hide');
            return;
        }

        standingModal.style.display = 'none';
    }

    function resetCompetitionsTable() {
        if (window.$ && $.fn.DataTable && $.fn.DataTable.isDataTable('#competitions')) {
            $('#competitions').DataTable().clear().destroy();
        }
    }

    function initCompetitionsTable() {
        if (!(window.$ && $.fn.DataTable)) {
            return;
        }

        competitionsTable = $('#competitions').DataTable({
            pageLength: 10,
            ordering: false,
            searching: true,
            paging: true,
            lengthChange: false,
            pagingType: 'full_numbers',
            autoWidth: false,
            columnDefs: [
                { targets: 0, width: '240px' },
                { targets: 1, width: '130px' },
                { targets: 2, width: '120px' },
                { targets: 3, width: '100px' },
                { targets: 4, width: '150px', className: 'text-center' },
                { targets: 5, width: '220px', className: 'text-center', orderable: false }
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

    function typeLabel(value) {
        const labels = {
            campionato: 'Campionato',
            torneo: 'Torneo'
        };

        return labels[value] || value || '';
    }

    function footballTypeLabel(value) {
        const labels = {
            11: 'Calcio a 11',
            7: 'Calcio a 7',
            5: 'Calcio a 5'
        };

        return labels[value] || value || '';
    }

    async function loadSeasons() {
        const json = await fetchJSON('../api/v1/seasons');
        resetSelect(seasonId, 'Seleziona');

        if (!json || !json.success || !Array.isArray(json.data)) {
            showToast(json?.error || 'Impossibile caricare le stagioni', 'error');
            return;
        }

        json.data.forEach(season => {
            const option = new Option(season.name, season.id);
            if (Number(season.is_current) === 1) {
                option.selected = true;
            }
            seasonId.appendChild(option);
        });
    }

    function loadCompetitions() {
        return fetchJSON('../api/v1/competitions')
            .then(json => {
                if (!json || !json.success || !Array.isArray(json.data)) {
                    showToast(json?.error || 'Impossibile caricare le competizioni', 'error');
                    return;
                }

                competitions = json.data;

                const tbody = document.querySelector('#competitions tbody');
                resetCompetitionsTable();
                tbody.innerHTML = '';

                const visibleCompetitions = alertFilter === 'without_teams'
                    ? competitions.filter(competition => Number(competition.teams_count || 0) === 0)
                    : competitions;

                visibleCompetitions.forEach(competition => {
                    const row = document.createElement('tr');

                    appendCell(row, competition.name);
                    appendCell(row, competition.season_name || competition.season || '-');
                    appendCell(row, typeLabel(competition.type));
                    appendCell(row, footballTypeLabel(competition.football_type));
                    appendCell(row, competition.created_at).className = 'text-center';

                    const actionsCell = document.createElement('td');
                    actionsCell.className = 'text-center';
                    if (competition.type === 'campionato') {
                        const standingButton = document.createElement('button');
                        standingButton.type = 'button';
                        standingButton.className = 'btn btn-xs btn-default standingCompetitionBtn';
                        standingButton.dataset.id = competition.id;
                        standingButton.textContent = 'Classifica';
                        actionsCell.appendChild(standingButton);
                    }

                    if (!canEdit && !canDelete && competition.type !== 'campionato') {
                        actionsCell.textContent = '-';
                    } else {
                        if (canEdit) {
                            const editButton = document.createElement('button');
                            editButton.type = 'button';
                            editButton.className = 'btn btn-xs btn-primary editCompetitionBtn';
                            editButton.dataset.id = competition.id;
                            editButton.textContent = 'Modifica';
                            editButton.style.marginLeft = competition.type === 'campionato' ? '5px' : '0';
                            actionsCell.appendChild(editButton);
                        }

                        if (canDelete) {
                            const deleteButton = document.createElement('button');
                            deleteButton.type = 'button';
                            deleteButton.className = 'btn btn-xs btn-danger deleteCompetitionBtn';
                            deleteButton.dataset.id = competition.id;
                            deleteButton.textContent = 'Elimina';
                            deleteButton.style.marginLeft = canEdit || competition.type === 'campionato' ? '5px' : '0';
                            actionsCell.appendChild(deleteButton);
                        }
                    }
                    row.appendChild(actionsCell);

                    tbody.appendChild(row);
                });

                initCompetitionsTable();
            });
    }

    function payload() {
        return {
            name: competitionName.value.trim(),
            season_id: seasonId.value,
            type: competitionType.value,
            football_type: footballType.value
        };
    }

    function openEdit(id) {
        const competition = competitions.find(item => String(item.id) === String(id));
        if (!competition) return;

        resetForm();
        editMode = true;
        editId = id;

        competitionName.value = competition.name || '';
        seasonId.value = competition.season_id || '';
        competitionType.value = competition.type || '';
        footballType.value = competition.football_type || '';

        showModal();
    }

    function standingInput(row, field, type = 'number') {
        const input = document.createElement('input');
        input.type = type;
        input.className = 'form-control input-sm standing-input';
        input.dataset.field = field;
        input.value = row[field] ?? '';
        input.disabled = !canManageStandings;

        if (type === 'number') {
            input.step = '1';
        }

        if (['played', 'won', 'drawn', 'lost', 'goals_for', 'goals_against'].includes(field)) {
            input.min = '0';
        }

        if (field === 'rank_position') {
            input.min = '1';
        }

        return input;
    }

    function renderStandings(rows = []) {
        const tbody = document.querySelector('#standings tbody');
        tbody.innerHTML = '';

        rows.forEach(row => {
            const tr = document.createElement('tr');
            tr.dataset.teamId = row.team_id;

            const rankCell = document.createElement('td');
            rankCell.appendChild(standingInput(row, 'rank_position'));

            const teamCell = document.createElement('td');
            teamCell.textContent = row.team_name || '-';

            const playedCell = document.createElement('td');
            playedCell.appendChild(standingInput(row, 'played'));

            const wonCell = document.createElement('td');
            wonCell.appendChild(standingInput(row, 'won'));

            const drawnCell = document.createElement('td');
            drawnCell.appendChild(standingInput(row, 'drawn'));

            const lostCell = document.createElement('td');
            lostCell.appendChild(standingInput(row, 'lost'));

            const goalsForCell = document.createElement('td');
            goalsForCell.appendChild(standingInput(row, 'goals_for'));

            const goalsAgainstCell = document.createElement('td');
            goalsAgainstCell.appendChild(standingInput(row, 'goals_against'));

            const goalDifferenceCell = document.createElement('td');
            goalDifferenceCell.className = 'text-center standing-goal-difference';
            goalDifferenceCell.textContent = row.goal_difference ?? 0;

            const penaltyPointsCell = document.createElement('td');
            penaltyPointsCell.appendChild(standingInput(row, 'penalty_points'));

            const pointsCell = document.createElement('td');
            pointsCell.appendChild(standingInput(row, 'points'));

            const notesCell = document.createElement('td');
            notesCell.appendChild(standingInput(row, 'notes', 'text'));

            tr.append(
                rankCell,
                teamCell,
                playedCell,
                wonCell,
                drawnCell,
                lostCell,
                goalsForCell,
                goalsAgainstCell,
                goalDifferenceCell,
                penaltyPointsCell,
                pointsCell,
                notesCell
            );

            tbody.appendChild(tr);
        });
    }

    function updateStandingNotice() {
        standingNotice.style.display = canManageStandings ? 'none' : 'block';
        standingNotice.textContent = canManageStandings
            ? ''
            : 'Hai accesso in sola lettura alla classifica.';
        saveStandings.style.display = canManageStandings ? '' : 'none';
        recalculateStandings.style.display = canManageStandings ? '' : 'none';
    }

    async function openStandings(id) {
        const competition = competitions.find(item => String(item.id) === String(id));
        if (!competition || competition.type !== 'campionato') return;

        standingCompetitionId = id;
        standingTitle.textContent = `Classifica - ${competition.name}`;
        updateStandingNotice();

        const json = await fetchJSON(`../api/v1/competition-standings?competition_id=${encodeURIComponent(id)}`);

        if (!json || !json.success || !json.data) {
            showToast(json?.error || 'Impossibile caricare la classifica', 'error');
            return;
        }

        renderStandings(json.data.standings || []);
        showStandingModal();
    }

    function standingPayload() {
        return {
            standings: Array.from(document.querySelectorAll('#standings tbody tr')).map(row => {
                const value = field => row.querySelector(`[data-field="${field}"]`)?.value ?? '';

                return {
                    team_id: Number(row.dataset.teamId),
                    rank_position: value('rank_position'),
                    played: value('played'),
                    won: value('won'),
                    drawn: value('drawn'),
                    lost: value('lost'),
                    goals_for: value('goals_for'),
                    goals_against: value('goals_against'),
                    penalty_points: value('penalty_points'),
                    points: value('points'),
                    notes: value('notes').trim()
                };
            })
        };
    }

    openModal.addEventListener('click', () => {
        if (!canCreate) return;

        resetForm();
        showModal();
    });

    closeModal.addEventListener('click', () => hideModal());
    closeStandingModal.addEventListener('click', () => hideStandingModal());

    competitionForm.addEventListener('submit', async e => {
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
            ? `../api/v1/competitions?id=${encodeURIComponent(editId)}`
            : '../api/v1/competitions';

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
        loadCompetitions();
        showToast(wasEdit ? 'Competizione aggiornata' : 'Competizione creata', 'success');
    });

    document.addEventListener('click', async e => {
        const editButton = e.target.closest('.editCompetitionBtn');
        if (editButton) {
            if (!canEdit) return;

            openEdit(editButton.dataset.id);
            return;
        }

        const standingButton = e.target.closest('.standingCompetitionBtn');
        if (standingButton) {
            openStandings(standingButton.dataset.id);
            return;
        }

        const deleteButton = e.target.closest('.deleteCompetitionBtn');
        if (!deleteButton || !canDelete) return;

        const competition = competitions.find(item => String(item.id) === String(deleteButton.dataset.id));
        const label = competition?.name || 'questa competizione';

        if (!await AppDialog.confirm(`Eliminare ${label}?`)) {
            return;
        }

        const json = await fetchJSON(`../api/v1/competitions?id=${encodeURIComponent(deleteButton.dataset.id)}`, {
            method: 'DELETE'
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Eliminazione non riuscita', 'error');
            return;
        }

        loadCompetitions();
        showToast('Competizione eliminata', 'success');
    });

    saveStandings.addEventListener('click', async () => {
        if (!standingCompetitionId || !canManageStandings) return;

        const json = await fetchJSON(`../api/v1/competition-standings?competition_id=${encodeURIComponent(standingCompetitionId)}`, {
            method: 'PUT',
            body: JSON.stringify(standingPayload())
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Salvataggio classifica non riuscito', 'error');
            return;
        }

        renderStandings(json.data.standings || []);
        showToast('Classifica aggiornata', 'success');
    });

    recalculateStandings.addEventListener('click', async () => {
        if (!standingCompetitionId || !canManageStandings) return;

        const confirmed = await AppDialog.open({
            title: 'Ricalcola classifica',
            message: 'Ricalcolare la classifica dalle partite giocate? Le statistiche manuali verranno sovrascritte, mentre le penalizzazioni saranno mantenute.',
            confirmText: 'Ricalcola',
            confirmClass: 'btn-warning',
            showCancel: true
        });

        if (!confirmed) return;

        const json = await fetchJSON(`../api/v1/competition-standings-recalculate?competition_id=${encodeURIComponent(standingCompetitionId)}`, {
            method: 'PUT',
            body: JSON.stringify({})
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Ricalcolo classifica non riuscito', 'error');
            return;
        }

        renderStandings(json.data.standings || []);
        showToast('Classifica ricalcolata', 'success');
    });

    await loadSeasons();
    loadCompetitions();
});
