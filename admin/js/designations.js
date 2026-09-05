document.addEventListener('DOMContentLoaded', async () => {

    let designationsTable = null;
    let blacklistTable = null;
    let competitions = [];
    let referees = [];
    let blacklistReferees = [];
    let teams = [];

    const canEdit = Auth.can('designations.edit');
    const canGenerate = Auth.can('designations.generate');
    const canConfirm = Auth.can('designations.confirm');
    const canManageBlacklist = Auth.can('designations.blacklist.manage');
    const footballType = document.getElementById('designationFootballType');
    const competition = document.getElementById('designationCompetition');
    const matchDay = document.getElementById('designationMatchDay');
    const generateButton = document.getElementById('generateDesignations');
    const confirmFilteredButton = document.getElementById('confirmFilteredDesignations');
    const clearAutomaticButton = document.getElementById('clearAutomaticDesignations');
    const applyFilters = document.getElementById('applyDesignationFilters');
    const resetFilters = document.getElementById('resetDesignationFilters');
    const blacklistSection = document.getElementById('designationBlacklistSection');
    const blacklistReferee = document.getElementById('blacklistReferee');
    const blacklistTeam = document.getElementById('blacklistTeam');
    const blacklistReason = document.getElementById('blacklistReason');
    const addBlacklistRule = document.getElementById('addBlacklistRule');

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

    function resetDesignationsTable() {
        if (window.$ && $.fn.DataTable && $.fn.DataTable.isDataTable('#designations')) {
            $('#designations').DataTable().clear().destroy();
        }
    }

    function initDesignationsTable() {
        if (!(window.$ && $.fn.DataTable)) {
            return;
        }

        designationsTable = $('#designations').DataTable({
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
                { targets: 6, width: '1%', className: 'text-center text-nowrap' },
                { targets: 8, width: '1%', className: 'text-center text-nowrap' },
                { targets: 9, width: '110px', className: 'text-center' },
                { targets: 10, width: '1%', className: 'text-center text-nowrap', orderable: false }
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/it-IT.json'
            }
        });
    }

    function resetBlacklistTable() {
        if (window.$ && $.fn.DataTable && $.fn.DataTable.isDataTable('#designationBlacklist')) {
            $('#designationBlacklist').DataTable().clear().destroy();
        }
    }

    function initBlacklistTable() {
        if (!(window.$ && $.fn.DataTable)) {
            return;
        }

        blacklistTable = $('#designationBlacklist').DataTable({
            pageLength: 5,
            ordering: false,
            searching: true,
            paging: true,
            lengthChange: false,
            pagingType: 'full_numbers',
            autoWidth: false,
            columnDefs: [
                { targets: 3, width: '1%', className: 'text-center text-nowrap', orderable: false }
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/it-IT.json'
            }
        });
    }

    function appendCell(row, text, className = '') {
        const cell = document.createElement('td');
        cell.textContent = text ?? '';
        if (className) {
            cell.className = className;
        }
        row.appendChild(cell);
        return cell;
    }

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = value;
        }
    }

    function normalizeTime(value) {
        return String(value || '').slice(0, 5) || '-';
    }

    function ratingLabel(value) {
        const ratingValue = Math.max(1, Math.min(5, Number(value || 3)));
        return '\u2605'.repeat(ratingValue) + '\u2606'.repeat(5 - ratingValue);
    }

    function statusLabel(value) {
        const labels = {
            proposta: 'Proposta',
            confermata: 'Confermata',
            modificata: 'Modificata'
        };

        return labels[value] || 'Da designare';
    }

    function refereeAvailabilityState(match, refereeId) {
        if (!match.availability_checked) {
            return 'unchecked';
        }

        const availableIds = (match.available_referee_ids || []).map(Number);

        return availableIds.includes(Number(refereeId)) ? 'available' : 'unavailable';
    }

    function refereeOptionLabel(referee, match) {
        const state = refereeAvailabilityState(match, referee.id);
        const suffixes = {
            available: ' - disponibile',
            unavailable: ' - non disponibile',
            unchecked: ' - disponibilita n.d.'
        };

        return `${referee.name} - ${ratingLabel(referee.rating)}${suffixes[state] || ''}`;
    }

    function scoreLabel(value) {
        if (value === null || value === undefined || value === '') {
            return '-';
        }

        return Number(value).toFixed(2);
    }

    function scoreDetailsMessage(rawDetails) {
        if (!rawDetails) {
            return 'Dettaglio score non disponibile.';
        }

        let details = null;

        try {
            details = JSON.parse(rawDetails);
        } catch (e) {
            return 'Dettaglio score non leggibile.';
        }

        const penalties = details.penalties || {};
        const weights = details.score_weights || {};
        const timeConflict = details.time_conflict || null;
        const lines = [
            `Score base: ${weights.base ?? 100}`,
            `Rating partita: ${details.rating_match ?? '-'}`,
            `Rating arbitro: ${details.rating_referee ?? '-'}`,
            `Distanza: ${details.distance_km !== null && details.distance_km !== undefined ? `${details.distance_km} km` : 'non disponibile'}`,
            `Designazioni stagione: ${details.assignment_load ?? 0}`,
            `Finestra minima tra partite: ${details.minimum_minutes_between_matches ?? '-'} minuti`,
            `Peso gap rating: ${weights.rating_gap_penalty ?? '-'}`,
            `Peso carico: ${weights.assignment_load_penalty ?? '-'}`,
            `Penalita stessa squadra: ${weights.consecutive_team_penalty ?? '-'}`,
            `Stessa squadra consecutiva: ${details.consecutive_team_conflict ? 'si' : 'no'}`,
            `Stessa squadra in casa consecutiva: ${details.consecutive_home_team_conflict ? 'si' : 'no'}`,
            `Esclusione: ${details.excluded_reason || 'nessuna'}`,
            `Conflitto orario: ${timeConflict ? `${timeConflict.match_time || 'senza ora'} (${timeConflict.minutes_diff ?? 'n.d.'} min)` : 'nessuno'}`,
            `Penalita rating: ${penalties.rating_gap ?? 0}`,
            `Penalita carico: ${penalties.assignment_load ?? 0}`,
            `Penalita squadra consecutiva: ${penalties.consecutive_team ?? 0}`,
            `Penalita casa consecutiva: ${penalties.consecutive_home_team ?? 0}`,
            `Penalita distanza: ${penalties.distance ?? 0}`
        ];

        return lines.join(' | ');
    }

    function renderRefereeSelect(match) {
        const select = document.createElement('select');
        select.className = 'form-control input-sm designation-referee';
        select.dataset.matchId = match.match_id;
        select.disabled = !canEdit;
        select.appendChild(new Option('Nessun arbitro', ''));

        referees.forEach(referee => {
            const option = new Option(refereeOptionLabel(referee, match), referee.id);
            option.dataset.availability = refereeAvailabilityState(match, referee.id);
            select.appendChild(option);
        });

        select.value = match.designation_referee_id || '';

        return select;
    }

    function renderAvailabilityHint(match, select) {
        const selected = select.value;

        if (!selected || !match.availability_checked) {
            return null;
        }

        if (refereeAvailabilityState(match, selected) !== 'unavailable') {
            return null;
        }

        const hint = document.createElement('small');
        hint.className = 'text-red designation-availability-hint';
        hint.textContent = 'Arbitro non disponibile nello slot partita';

        return hint;
    }

    function queryString() {
        const params = new URLSearchParams();

        if (footballType.value) {
            params.set('football_type', footballType.value);
        }

        if (competition.value) {
            params.set('competition_id', competition.value);
        }

        if (matchDay.value) {
            params.set('match_day', matchDay.value);
        }

        const query = params.toString();
        return query ? `?${query}` : '';
    }

    function currentFilters() {
        return {
            football_type: footballType.value || null,
            competition_id: competition.value || null,
            match_day: matchDay.value || null
        };
    }

    function renderCompetitionOptions() {
        const selected = competition.value;
        competition.innerHTML = '';
        competition.appendChild(new Option('Tutte', ''));

        competitions
            .filter(item => String(item.football_type) === String(footballType.value))
            .forEach(item => {
                competition.appendChild(new Option(item.name, item.id));
            });

        competition.value = selected;
        if (competition.value !== selected) {
            competition.value = '';
        }
    }

    function renderOptionList(select, rows, placeholder) {
        select.innerHTML = '';
        select.appendChild(new Option(placeholder, ''));

        rows.forEach(row => {
            select.appendChild(new Option(row.name, row.id));
        });
    }

    async function loadCompetitions() {
        const json = await fetchJSON('../api/v1/competitions');

        if (!json || !json.success || !Array.isArray(json.data)) {
            showToast(json?.error || 'Impossibile caricare le competizioni', 'error');
            return;
        }

        competitions = json.data;
        renderCompetitionOptions();
    }

    async function loadBlacklistOptions() {
        if (!canManageBlacklist) return;

        const [refereeJson, teamJson] = await Promise.all([
            fetchJSON('../api/v1/referees'),
            fetchJSON('../api/v1/teams')
        ]);

        if (!refereeJson || !refereeJson.success || !Array.isArray(refereeJson.data)) {
            showToast(refereeJson?.error || 'Impossibile caricare gli arbitri', 'error');
            return;
        }

        if (!teamJson || !teamJson.success || !Array.isArray(teamJson.data)) {
            showToast(teamJson?.error || 'Impossibile caricare le squadre', 'error');
            return;
        }

        blacklistReferees = refereeJson.data;
        teams = teamJson.data;

        renderOptionList(blacklistReferee, blacklistReferees, 'Seleziona arbitro');
        renderOptionList(blacklistTeam, teams, 'Seleziona squadra');
    }

    async function loadDesignations() {
        const json = await fetchJSON(`../api/v1/designations${queryString()}`);

        if (!json || !json.success || !json.data) {
            showToast(json?.error || 'Impossibile caricare le designazioni', 'error');
            return;
        }

        referees = json.data.referees || [];
        const rows = json.data.matches || [];
        const tbody = document.querySelector('#designations tbody');

        resetDesignationsTable();
        tbody.innerHTML = '';

        rows.forEach(match => {
            const row = document.createElement('tr');
            const matchLabel = `${match.home_team_name} - ${match.away_team_name}`;

            appendCell(row, match.match_date, 'text-center text-nowrap');
            appendCell(row, normalizeTime(match.match_time), 'text-center text-nowrap');
            appendCell(row, match.match_day, 'text-center text-nowrap');
            appendCell(row, match.competition_name);
            appendCell(row, matchLabel);
            appendCell(row, match.field_name || '-');
            appendCell(row, ratingLabel(match.difficulty_rating), 'text-center text-nowrap');

            const refereeCell = document.createElement('td');
            const refereeSelect = renderRefereeSelect(match);
            const availabilityHint = renderAvailabilityHint(match, refereeSelect);
            refereeCell.appendChild(refereeSelect);

            if (availabilityHint) {
                refereeCell.appendChild(availabilityHint);
            }

            row.appendChild(refereeCell);

            const scoreCell = document.createElement('td');
            scoreCell.className = 'text-center text-nowrap';
            scoreCell.appendChild(document.createTextNode(scoreLabel(match.score)));

            if (match.score_details_json) {
                const infoButton = document.createElement('button');
                infoButton.type = 'button';
                infoButton.className = 'btn btn-xs btn-default showScoreDetailsBtn';
                infoButton.dataset.details = match.score_details_json;
                infoButton.title = 'Dettaglio score';
                infoButton.setAttribute('aria-label', 'Dettaglio score');
                infoButton.innerHTML = '<i class="fa fa-info-circle"></i>';
                scoreCell.appendChild(document.createTextNode(' '));
                scoreCell.appendChild(infoButton);
            }

            row.appendChild(scoreCell);

            appendCell(row, statusLabel(match.designation_status), 'text-center');

            const actionCell = document.createElement('td');
            actionCell.className = 'text-center text-nowrap';

            if (canEdit) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-xs btn-primary saveDesignationBtn';
                button.dataset.matchId = match.match_id;
                button.title = 'Salva designazione';
                button.setAttribute('aria-label', 'Salva designazione');
                button.innerHTML = '<i class="fa fa-save"></i>';
                actionCell.appendChild(button);
            }

            if (canConfirm && match.designation_referee_id && match.designation_status !== 'confermata') {
                const confirmButton = document.createElement('button');
                confirmButton.type = 'button';
                confirmButton.className = 'btn btn-xs btn-success confirmDesignationBtn';
                confirmButton.dataset.matchId = match.match_id;
                confirmButton.title = 'Conferma designazione';
                confirmButton.setAttribute('aria-label', 'Conferma designazione');
                confirmButton.innerHTML = '<i class="fa fa-check"></i>';
                actionCell.appendChild(document.createTextNode(' '));
                actionCell.appendChild(confirmButton);
            }

            if (!actionCell.childNodes.length) {
                actionCell.textContent = '-';
            }

            row.appendChild(actionCell);
            tbody.appendChild(row);
        });

        initDesignationsTable();
    }

    async function loadSummary() {
        const json = await fetchJSON(`../api/v1/designations/summary${queryString()}`);

        if (!json || !json.success || !json.data) {
            showToast(json?.error || 'Impossibile caricare il riepilogo designazioni', 'error');
            return;
        }

        const data = json.data;
        setText('designation-total-matches', data.total_matches || 0);
        setText('designation-to-designate', data.to_designate || 0);
        setText('designation-proposed', data.proposed || 0);
        setText('designation-confirmed', data.confirmed || 0);
        setText('designation-manual', data.manual || 0);

        const tbody = document.querySelector('#designationTopReferees tbody');
        if (!tbody) return;

        tbody.innerHTML = '';
        const rows = Array.isArray(data.top_referees) ? data.top_referees : [];

        if (!rows.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 5;
            cell.className = 'text-center text-muted';
            cell.textContent = 'Nessun arbitro assegnato';
            row.appendChild(cell);
            tbody.appendChild(row);
            return;
        }

        rows.forEach(item => {
            const row = document.createElement('tr');
            appendCell(row, item.referee_name);
            appendCell(row, item.total, 'text-center text-nowrap');
            appendCell(row, item.confirmed, 'text-center text-nowrap');
            appendCell(row, item.proposed, 'text-center text-nowrap');
            appendCell(row, item.manual, 'text-center text-nowrap');
            tbody.appendChild(row);
        });
    }

    async function loadBlacklist() {
        if (!canManageBlacklist) return;

        const json = await fetchJSON('../api/v1/designation-blacklist');

        if (!json || !json.success || !Array.isArray(json.data)) {
            showToast(json?.error || 'Impossibile caricare la blacklist', 'error');
            return;
        }

        const tbody = document.querySelector('#designationBlacklist tbody');

        resetBlacklistTable();
        tbody.innerHTML = '';

        json.data.forEach(rule => {
            const row = document.createElement('tr');

            appendCell(row, rule.referee_name);
            appendCell(row, rule.team_name);
            appendCell(row, rule.reason || '-');

            const actionCell = document.createElement('td');
            actionCell.className = 'text-center text-nowrap';

            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'btn btn-xs btn-danger deleteBlacklistRule';
            deleteButton.dataset.id = rule.id;
            deleteButton.dataset.label = `${rule.referee_name} / ${rule.team_name}`;
            deleteButton.title = 'Rimuovi blacklist';
            deleteButton.setAttribute('aria-label', 'Rimuovi blacklist');
            deleteButton.innerHTML = '<i class="fa fa-trash"></i>';
            actionCell.appendChild(deleteButton);

            row.appendChild(actionCell);
            tbody.appendChild(row);
        });

        initBlacklistTable();
    }

    async function saveDesignation(matchId) {
        const select = document.querySelector(`.designation-referee[data-match-id="${matchId}"]`);
        if (!select) return;

        const selectedOption = select.options[select.selectedIndex];
        if (select.value && selectedOption?.dataset.availability === 'unavailable') {
            const confirmed = await AppDialog.open({
                title: 'Arbitro non disponibile',
                message: 'L\'arbitro selezionato non risulta disponibile nello slot della partita. Vuoi salvarlo comunque come modifica manuale?',
                confirmText: 'Salva comunque',
                confirmClass: 'btn-warning',
                showCancel: true
            });

            if (!confirmed) return;
        }

        const json = await fetchJSON('../api/v1/designations', {
            method: 'PUT',
            body: JSON.stringify({
                match_id: matchId,
                referee_id: select.value || null
            })
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Salvataggio designazione non riuscito', 'error');
            return;
        }

        showToast('Designazione salvata', 'success');
        await loadDesignations();
        await loadSummary();
    }

    async function generateDesignations() {
        if (!canGenerate) return;

        const confirmed = await AppDialog.open({
            title: 'Rigenera proposte',
            message: 'Rigenerare le proposte automatiche per le partite filtrate non confermate? Le designazioni manuali o confermate non verranno sovrascritte.',
            confirmText: 'Rigenera',
            confirmClass: 'btn-success',
            showCancel: true
        });

        if (!confirmed) return;

        const json = await fetchJSON('../api/v1/designations/regenerate', {
            method: 'POST',
            body: JSON.stringify(currentFilters())
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Generazione proposta non riuscita', 'error');
            return;
        }

        const generated = json.data?.generated || 0;
        const total = json.data?.total || 0;
        const skipped = Array.isArray(json.data?.skipped) ? json.data.skipped.length : 0;

        showToast(`Proposte generate: ${generated}/${total}. Saltate: ${skipped}.`, 'success');
        await loadDesignations();
        await loadSummary();
    }

    async function confirmFilteredDesignations() {
        if (!canConfirm) return;

        const confirmed = await AppDialog.open({
            title: 'Conferma proposte filtrate',
            message: 'Confermare tutte le proposte filtrate con arbitro assegnato?',
            confirmText: 'Conferma',
            confirmClass: 'btn-success',
            showCancel: true
        });

        if (!confirmed) return;

        const json = await fetchJSON('../api/v1/designations/confirm-filtered', {
            method: 'POST',
            body: JSON.stringify(currentFilters())
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Conferma massiva non riuscita', 'error');
            return;
        }

        showToast(`Proposte confermate: ${json.data?.confirmed || 0}. Saltate: ${json.data?.skipped || 0}.`, 'success');
        await loadDesignations();
        await loadSummary();
    }

    async function clearAutomaticDesignations() {
        if (!canGenerate) return;

        const confirmed = await AppDialog.open({
            title: 'Pulisci proposte automatiche',
            message: 'Eliminare le proposte automatiche filtrate non confermate? Le designazioni manuali o confermate non verranno toccate.',
            confirmText: 'Pulisci',
            confirmClass: 'btn-danger',
            showCancel: true
        });

        if (!confirmed) return;

        const json = await fetchJSON('../api/v1/designations/clear-automatic', {
            method: 'POST',
            body: JSON.stringify(currentFilters())
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Pulizia proposte non riuscita', 'error');
            return;
        }

        showToast(`Proposte pulite: ${json.data?.cleared || 0}. Saltate: ${json.data?.skipped || 0}.`, 'success');
        await loadDesignations();
        await loadSummary();
    }

    async function confirmDesignation(matchId) {
        if (!canConfirm) return;

        const confirmed = await AppDialog.open({
            title: 'Conferma designazione',
            message: 'Confermare la designazione selezionata?',
            confirmText: 'Conferma',
            confirmClass: 'btn-success',
            showCancel: true
        });

        if (!confirmed) return;

        const json = await fetchJSON('../api/v1/designations/confirm', {
            method: 'POST',
            body: JSON.stringify({
                match_id: matchId
            })
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Conferma designazione non riuscita', 'error');
            return;
        }

        showToast('Designazione confermata', 'success');
        await loadDesignations();
        await loadSummary();
    }

    async function saveBlacklistRule() {
        if (!canManageBlacklist) return;

        if (!blacklistReferee.value || !blacklistTeam.value) {
            showToast('Seleziona arbitro e squadra', 'warning');
            return;
        }

        const json = await fetchJSON('../api/v1/designation-blacklist', {
            method: 'POST',
            body: JSON.stringify({
                referee_id: blacklistReferee.value,
                team_id: blacklistTeam.value,
                reason: blacklistReason.value || null
            })
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Salvataggio blacklist non riuscito', 'error');
            return;
        }

        blacklistReferee.value = '';
        blacklistTeam.value = '';
        blacklistReason.value = '';

        showToast('Regola blacklist salvata', 'success');
        await loadBlacklist();
    }

    async function deleteBlacklistRule(id, label) {
        if (!canManageBlacklist) return;

        const confirmed = await AppDialog.confirm(`Rimuovere la blacklist ${label}?`);
        if (!confirmed) return;

        const json = await fetchJSON(`../api/v1/designation-blacklist?id=${encodeURIComponent(id)}`, {
            method: 'DELETE'
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Rimozione blacklist non riuscita', 'error');
            return;
        }

        showToast('Regola blacklist rimossa', 'success');
        await loadBlacklist();
    }

    async function showScoreDetails(rawDetails) {
        await AppDialog.message(scoreDetailsMessage(rawDetails), 'Dettaglio score');
    }

    footballType.addEventListener('change', () => {
        renderCompetitionOptions();
        loadDesignations();
        loadSummary();
    });

    applyFilters.addEventListener('click', () => {
        loadDesignations();
        loadSummary();
    });

    resetFilters.addEventListener('click', () => {
        footballType.value = '11';
        competition.value = '';
        matchDay.value = '';
        renderCompetitionOptions();
        loadDesignations();
        loadSummary();
    });

    if (generateButton) {
        generateButton.style.display = canGenerate ? '' : 'none';
        generateButton.addEventListener('click', generateDesignations);
    }

    if (confirmFilteredButton) {
        confirmFilteredButton.style.display = canConfirm ? '' : 'none';
        confirmFilteredButton.addEventListener('click', confirmFilteredDesignations);
    }

    if (clearAutomaticButton) {
        clearAutomaticButton.style.display = canGenerate ? '' : 'none';
        clearAutomaticButton.addEventListener('click', clearAutomaticDesignations);
    }

    if (blacklistSection) {
        blacklistSection.style.display = canManageBlacklist ? '' : 'none';
    }

    if (addBlacklistRule) {
        addBlacklistRule.addEventListener('click', saveBlacklistRule);
    }

    document.addEventListener('click', e => {
        const saveButton = e.target.closest('.saveDesignationBtn');
        if (saveButton && canEdit) {
            saveDesignation(saveButton.dataset.matchId);
            return;
        }

        const confirmButton = e.target.closest('.confirmDesignationBtn');
        if (confirmButton && canConfirm) {
            confirmDesignation(confirmButton.dataset.matchId);
            return;
        }

        const scoreButton = e.target.closest('.showScoreDetailsBtn');
        if (scoreButton) {
            showScoreDetails(scoreButton.dataset.details);
            return;
        }

        const deleteBlacklistButton = e.target.closest('.deleteBlacklistRule');
        if (deleteBlacklistButton && canManageBlacklist) {
            deleteBlacklistRule(deleteBlacklistButton.dataset.id, deleteBlacklistButton.dataset.label);
        }
    });

    await loadCompetitions();
    await loadBlacklistOptions();
    await loadSummary();
    await loadDesignations();
    await loadBlacklist();
});
