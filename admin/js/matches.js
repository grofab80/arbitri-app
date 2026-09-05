document.addEventListener('DOMContentLoaded', async () => {

    let matches = [];
    let availableTeams = [];
    let matchesTable = null;
    let editMode = false;
    let editId = null;
    const canCreate = Auth.can('matches.create');
    const canEdit = Auth.can('matches.edit');
    const canDelete = Auth.can('matches.delete');
    const alertFilter = new URLSearchParams(window.location.search).get('alert');

    const modal = document.getElementById('matchModal');
    const openModal = document.getElementById('openMatchModal');
    const closeModal = document.getElementById('closeMatchModal');
    const matchForm = document.getElementById('matchForm');

    const competitionId = document.getElementById('competition_id');
    const matchDay = document.getElementById('match_day');
    const difficultyRating = document.getElementById('difficulty_rating');
    const homeTeamId = document.getElementById('home_team_id');
    const awayTeamId = document.getElementById('away_team_id');
    const refereeId = document.getElementById('referee_id');
    const fieldId = document.getElementById('field_id');
    const matchDate = document.getElementById('match_date');
    const matchTime = document.getElementById('match_time');
    const status = document.getElementById('status');
    const resultType = document.getElementById('result_type');
    const homeGoals = document.getElementById('home_goals');
    const awayGoals = document.getElementById('away_goals');
    const notes = document.getElementById('notes');
    const walkoverReason = document.getElementById('walkover_reason');
    const matchFilterFrom = document.getElementById('matchFilterFrom');
    const matchFilterTo = document.getElementById('matchFilterTo');
    const matchFilterCompetition = document.getElementById('matchFilterCompetition');
    const matchFilterTeam = document.getElementById('matchFilterTeam');
    const matchFilterReferee = document.getElementById('matchFilterReferee');
    const applyMatchFilters = document.getElementById('applyMatchFilters');
    const resetMatchFilters = document.getElementById('resetMatchFilters');

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

    function renderSelectOptions(select, rows = [], placeholder = 'Tutti') {
        if (!select) return;

        const currentValue = select.value || '';
        select.innerHTML = '';
        select.appendChild(new Option(placeholder, ''));

        rows.forEach(row => {
            select.appendChild(new Option(row.name, row.id));
        });

        select.value = currentValue;
    }

    function clearErrors() {
        document.querySelectorAll('.wrapper-error')
            .forEach(el => el.classList.remove('wrapper-error'));

        document.querySelectorAll('.error-msg')
            .forEach(el => el.textContent = '');
    }

    function fieldWrapper(field) {
        const map = {
            competition_id: 'wrapCompetition',
            match_day: 'wrapMatchDay',
            difficulty_rating: 'wrapDifficultyRating',
            home_team_id: 'wrapHomeTeam',
            away_team_id: 'wrapAwayTeam',
            referee_id: 'wrapReferee',
            field_id: 'wrapField',
            match_date: 'wrapMatchDate',
            match_time: 'wrapMatchTime',
            status: 'wrapStatus',
            result_type: 'wrapResultType',
            home_goals: 'wrapHomeGoals',
            away_goals: 'wrapAwayGoals',
            notes: 'wrapNotes',
            walkover_reason: 'wrapWalkoverReason'
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

    function showModal() {
        if (window.$ && $.fn.modal) {
            $('#matchModal').modal('show');
            return;
        }

        modal.style.display = 'block';
    }

    function hideModal() {
        if (window.$ && $.fn.modal) {
            $('#matchModal').modal('hide');
            return;
        }

        modal.style.display = 'none';
    }

    function resetForm() {
        matchForm.reset();
        clearErrors();
        editMode = false;
        editId = null;
        availableTeams = [];
        matchDay.value = '1';
        difficultyRating.value = '3';
        resetSelect(homeTeamId, 'Seleziona');
        resetSelect(awayTeamId, 'Seleziona');
        refereeId.value = '';
        fieldId.value = '';
        toggleWalkoverFields();
    }

    function resetMatchesTable() {
        if (window.$ && $.fn.DataTable && $.fn.DataTable.isDataTable('#matches')) {
            $('#matches').DataTable().clear().destroy();
        }
    }

    function initMatchesTable() {
        if (!(window.$ && $.fn.DataTable)) {
            return;
        }

        matchesTable = $('#matches').DataTable({
            pageLength: 10,
            ordering: false,
            searching: true,
            paging: true,
            lengthChange: false,
            pagingType: 'full_numbers',
            autoWidth: false,
            columnDefs: [
                { targets: 0, width: '1%', className: 'text-center text-nowrap' },
                { targets: 1, width: '1%', className: 'text-center text-nowrap' },
                { targets: 2, width: '1%', className: 'text-center text-nowrap' },
                { targets: 3, width: '1%', className: 'text-center text-nowrap' },
                { targets: 8, width: '100px', className: 'text-center' },
                { targets: 9, width: '110px', className: 'text-center' },
                { targets: 10, width: '1%', className: 'text-center text-nowrap', orderable: false }
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

    function statusLabel(value) {
        const labels = {
            scheduled: 'Programmata',
            played: 'Giocata',
            cancelled: 'Annullata'
        };

        return labels[value] || value || '';
    }

    function resultTypeLabel(value) {
        const labels = {
            played: '',
            walkover_home: 'Tavolino casa',
            walkover_away: 'Tavolino trasferta'
        };

        return labels[value] || '';
    }

    function resultLabel(match) {
        const hasHomeGoals = match.home_goals !== null && match.home_goals !== undefined && match.home_goals !== '';
        const hasAwayGoals = match.away_goals !== null && match.away_goals !== undefined && match.away_goals !== '';

        if (!hasHomeGoals || !hasAwayGoals) {
            return '-';
        }

        const result = `${match.home_goals} - ${match.away_goals}`;
        const label = resultTypeLabel(match.result_type);

        return label ? `${result} (${label})` : result;
    }

    function ratingLabel(value) {
        const ratingValue = Math.max(1, Math.min(5, Number(value || 3)));

        return '\u2605'.repeat(ratingValue) + '\u2606'.repeat(5 - ratingValue);
    }

    function toggleWalkoverFields() {
        const isWalkover = resultType.value !== 'played';

        if (!isWalkover) {
            walkoverReason.value = '';
        }

        walkoverReason.disabled = !isWalkover;
    }

    function applyWalkoverDefaultScore() {
        if (resultType.value === 'walkover_home') {
            homeGoals.value = '3';
            awayGoals.value = '0';
            status.value = 'played';
        }

        if (resultType.value === 'walkover_away') {
            homeGoals.value = '0';
            awayGoals.value = '3';
            status.value = 'played';
        }

        toggleWalkoverFields();
    }

    function markPlayedWhenResultComplete() {
        if (homeGoals.value !== '' && awayGoals.value !== '' && status.value !== 'cancelled') {
            status.value = 'played';
        }
    }

    function normalizeTime(value) {
        return String(value || '').slice(0, 5);
    }

    function addTeamOptions(select, teams, selectedValue, excludedValue) {
        resetSelect(select, 'Seleziona');

        teams.forEach(team => {
            const teamId = String(team.id);

            if (teamId === String(excludedValue || '') && teamId !== String(selectedValue || '')) {
                return;
            }

            select.appendChild(new Option(team.name, team.id));
        });

        select.value = selectedValue || '';
    }

    function renderTeamSelects(homeValue = homeTeamId.value, awayValue = awayTeamId.value) {
        if (homeValue && homeValue === awayValue) {
            awayValue = '';
        }

        addTeamOptions(homeTeamId, availableTeams, homeValue, awayValue);
        addTeamOptions(awayTeamId, availableTeams, awayValue, homeValue);
    }

    async function loadCompetitions() {
        const json = await fetchJSON('../api/v1/competitions');
        resetSelect(competitionId, 'Seleziona');

        if (!json || !json.success || !Array.isArray(json.data)) {
            showToast(json?.error || 'Impossibile caricare le competizioni', 'error');
            return;
        }

        json.data.forEach(c => {
            competitionId.appendChild(new Option(c.name, c.id));
        });
    }

    async function loadMatchFilterCompetitions() {
        const json = await fetchJSON('../api/v1/competitions');

        if (!json || !json.success || !Array.isArray(json.data)) {
            showToast(json?.error || 'Impossibile caricare le competizioni', 'error');
            return;
        }

        renderSelectOptions(matchFilterCompetition, json.data, 'Tutte');
    }

    async function loadMatchFilterTeams(competition = null) {
        const url = competition
            ? `../api/v1/teams?competition_id=${encodeURIComponent(competition)}`
            : '../api/v1/teams';

        const json = await fetchJSON(url);

        if (!json || !json.success || !Array.isArray(json.data)) {
            showToast(json?.error || 'Impossibile caricare le squadre', 'error');
            return;
        }

        renderSelectOptions(matchFilterTeam, json.data, 'Tutte');
    }

    async function loadMatchFilterReferees() {
        const json = await fetchJSON('../api/v1/referee-candidates');

        if (!json || !json.success || !Array.isArray(json.data)) {
            showToast(json?.error || 'Impossibile caricare gli arbitri', 'error');
            return;
        }

        renderSelectOptions(matchFilterReferee, json.data, 'Tutti');
    }

    async function loadMatchFilters() {
        await Promise.all([
            loadMatchFilterCompetitions(),
            loadMatchFilterTeams(),
            loadMatchFilterReferees()
        ]);
    }

    function filterQueryString() {
        const params = new URLSearchParams();

        if (matchFilterFrom.value) {
            params.set('from', matchFilterFrom.value);
        }

        if (matchFilterTo.value) {
            params.set('to', matchFilterTo.value);
        }

        if (matchFilterCompetition.value) {
            params.set('competition_id', matchFilterCompetition.value);
        }

        if (matchFilterTeam.value) {
            params.set('team_id', matchFilterTeam.value);
        }

        if (matchFilterReferee.value) {
            params.set('referee_id', matchFilterReferee.value);
        }

        const query = params.toString();
        return query ? `?${query}` : '';
    }

    async function loadTeams(competition = null) {
        const url = competition
            ? `../api/v1/teams?competition_id=${encodeURIComponent(competition)}`
            : '../api/v1/teams';

        const json = await fetchJSON(url);
        availableTeams = [];

        if (!json || !json.success || !Array.isArray(json.data)) {
            resetSelect(homeTeamId, 'Seleziona');
            resetSelect(awayTeamId, 'Seleziona');
            showToast(json?.error || 'Impossibile caricare le squadre', 'error');
            return;
        }

        availableTeams = json.data;
        renderTeamSelects();
    }

    function refereeOptionLabel(referee) {
        const distance = referee.distance_km !== null && referee.distance_km !== undefined
            ? ` - ${referee.distance_km} km`
            : '';

        return `${referee.name} - ${ratingLabel(referee.rating)}${distance}`;
    }

    async function loadReferees(selectedField = null, selectedReferee = '') {
        const url = selectedField
            ? `../api/v1/referee-candidates?field_id=${encodeURIComponent(selectedField)}`
            : '../api/v1/referee-candidates';

        const json = await fetchJSON(url);
        resetSelect(refereeId, 'Nessun arbitro');

        if (!json || !json.success || !Array.isArray(json.data)) {
            showToast(json?.error || 'Impossibile caricare gli arbitri', 'error');
            return;
        }

        json.data.forEach(referee => {
            refereeId.appendChild(new Option(refereeOptionLabel(referee), referee.id));
        });

        refereeId.value = selectedReferee || '';
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

    function loadMatches() {
        return fetchJSON(`../api/v1/matches${filterQueryString()}`)
            .then(json => {
                if (!json || !json.success || !Array.isArray(json.data)) {
                    showToast(json?.error || 'Impossibile caricare le partite', 'error');
                    return;
                }

                matches = json.data;

                const tbody = document.querySelector('#matches tbody');
                resetMatchesTable();
                tbody.innerHTML = '';

                const visibleMatches = alertFilter === 'without_referee'
                    ? matches.filter(match => match.status === 'scheduled' && !match.referee_id)
                    : matches;

                visibleMatches.forEach(match => {
                    const row = document.createElement('tr');

                    appendCell(row, match.match_date).className = 'text-center text-nowrap';
                    appendCell(row, match.match_time ? normalizeTime(match.match_time) : '-').className = 'text-center text-nowrap';
                    appendCell(row, match.match_day).className = 'text-center text-nowrap';
                    appendCell(row, ratingLabel(match.difficulty_rating)).className = 'text-center text-nowrap';
                    appendCell(row, match.competition_name);
                    appendCell(row, match.home_team_name);
                    appendCell(row, match.away_team_name);
                    appendCell(row, match.referee_name || '-');
                    appendCell(row, resultLabel(match)).className = 'text-center';
                    appendCell(row, statusLabel(match.status)).className = 'text-center';

                    const actionCell = document.createElement('td');
                    actionCell.className = 'text-center text-nowrap';
                    if (canEdit) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'btn btn-xs btn-primary editMatchBtn';
                        button.dataset.id = match.id;
                        button.title = 'Modifica';
                        button.setAttribute('aria-label', 'Modifica');
                        button.innerHTML = '<i class="fa fa-pencil"></i>';
                        actionCell.appendChild(button);
                    }

                    if (canDelete) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'btn btn-xs btn-danger deleteMatchBtn';
                        button.dataset.id = match.id;
                        button.title = 'Elimina';
                        button.setAttribute('aria-label', 'Elimina');
                        button.innerHTML = '<i class="fa fa-trash"></i>';
                        button.style.marginLeft = canEdit ? '5px' : '0';
                        actionCell.appendChild(button);
                    }

                    if (!canEdit && !canDelete) {
                        actionCell.textContent = '-';
                    }
                    row.appendChild(actionCell);

                    tbody.appendChild(row);
                });

                initMatchesTable();
            });
    }

    async function openEdit(id) {
        const match = matches.find(item => String(item.id) === String(id));
        if (!match) return;

        resetForm();
        editMode = true;
        editId = id;

        competitionId.value = match.competition_id;
        matchDay.value = match.match_day || '';
        difficultyRating.value = match.difficulty_rating || '3';
        await loadTeams(match.competition_id);

        renderTeamSelects(String(match.home_team_id || ''), String(match.away_team_id || ''));
        fieldId.value = match.field_id || '';
        await loadReferees(match.field_id || null, match.referee_id || '');
        matchDate.value = match.match_date;
        matchTime.value = normalizeTime(match.match_time);
        status.value = match.status || 'scheduled';
        resultType.value = match.result_type || 'played';
        homeGoals.value = match.home_goals ?? '';
        awayGoals.value = match.away_goals ?? '';
        notes.value = match.notes || '';
        walkoverReason.value = match.walkover_reason || '';
        toggleWalkoverFields();

        showModal();
    }

    openModal.addEventListener('click', async () => {
        if (!canCreate) return;

        resetForm();
        await loadReferees();
        await loadTeams(competitionId.value);
        showModal();
    });

    closeModal.addEventListener('click', () => hideModal());

    competitionId.addEventListener('change', () => {
        loadTeams(competitionId.value);
    });

    homeTeamId.addEventListener('change', () => {
        renderTeamSelects(homeTeamId.value, awayTeamId.value);
    });

    awayTeamId.addEventListener('change', () => {
        renderTeamSelects(homeTeamId.value, awayTeamId.value);
    });

    fieldId.addEventListener('change', () => {
        loadReferees(fieldId.value, refereeId.value);
    });

    matchFilterCompetition.addEventListener('change', async () => {
        matchFilterTeam.value = '';
        await loadMatchFilterTeams(matchFilterCompetition.value);
    });

    applyMatchFilters.addEventListener('click', () => {
        loadMatches();
    });

    resetMatchFilters.addEventListener('click', async () => {
        matchFilterFrom.value = '';
        matchFilterTo.value = '';
        matchFilterCompetition.value = '';
        matchFilterTeam.value = '';
        matchFilterReferee.value = '';
        await loadMatchFilterTeams();
        loadMatches();
    });

    resultType.addEventListener('change', applyWalkoverDefaultScore);
    homeGoals.addEventListener('input', markPlayedWhenResultComplete);
    awayGoals.addEventListener('input', markPlayedWhenResultComplete);

    document.addEventListener('click', async e => {
        const editButton = e.target.closest('.editMatchBtn');
        if (editButton) {
            if (!canEdit) return;

            openEdit(editButton.dataset.id);
            return;
        }

        const deleteButton = e.target.closest('.deleteMatchBtn');
        if (!deleteButton || !canDelete) return;

        const match = matches.find(item => String(item.id) === String(deleteButton.dataset.id));
        const label = match
            ? `${match.home_team_name} - ${match.away_team_name} del ${match.match_date}`
            : 'questa partita';

        if (!await AppDialog.confirm(`Eliminare ${label}?`)) {
            return;
        }

        const json = await fetchJSON(`../api/v1/matches?id=${encodeURIComponent(deleteButton.dataset.id)}`, {
            method: 'DELETE'
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Eliminazione non riuscita', 'error');
            return;
        }

        loadMatches();
        showToast('Partita eliminata', 'success');
    });

    matchForm.addEventListener('submit', async e => {
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
        markPlayedWhenResultComplete();

        const payload = {
            competition_id: competitionId.value,
            match_day: matchDay.value,
            difficulty_rating: difficultyRating.value,
            home_team_id: homeTeamId.value,
            away_team_id: awayTeamId.value,
            referee_id: refereeId.value || null,
            field_id: fieldId.value || null,
            match_date: matchDate.value,
            match_time: normalizeTime(matchTime.value),
            status: status.value,
            result_type: resultType.value,
            home_goals: homeGoals.value,
            away_goals: awayGoals.value,
            notes: notes.value.trim(),
            walkover_reason: walkoverReason.value.trim()
        };

        const url = editMode
            ? `../api/v1/matches?id=${encodeURIComponent(editId)}`
            : '../api/v1/matches';

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
        resetForm();
        loadMatches();
        showToast(wasEdit ? 'Partita aggiornata' : 'Partita creata', 'success');
    });

    await loadCompetitions();
    await loadReferees();
    await loadFields();
    await loadMatchFilters();
    loadMatches();
});
