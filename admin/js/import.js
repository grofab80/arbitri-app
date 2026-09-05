document.addEventListener('DOMContentLoaded', () => {
    let source = null;
    let overrideSeasons = [];
    let syncBusy = false;
    let syncRequestActive = false;
    let progressPollTimer = null;
    let progressPollPending = false;
    let observedRunningSync = false;
    let progressResponseComplete = false;
    let progressHideTimer = null;
    const canManage = Auth.can('import.manage');
    const canRun = Auth.can('import.run');
    const form = document.getElementById('syncSourceForm');
    const runButtons = [document.getElementById('runFullSync'), document.getElementById('runIncrementalSync')];

    function toast(message, type = 'info') {
        if (window.$ && $.toast) {
            $.toast({ text: message, icon: type, position: 'bottom-left', hideAfter: 3500 });
            return;
        }
        AppDialog.notify(message, type);
    }

    function clearErrors() {
        document.querySelectorAll('.wrapper-error').forEach(el => el.classList.remove('wrapper-error'));
        document.querySelectorAll('.error-msg').forEach(el => { el.textContent = ''; });
    }

    function showErrors(errors = {}) {
        const wrappers = {
            name: 'wrapSyncName', base_url: 'wrapSyncBaseUrl', api_key: 'wrapSyncApiKey', request_timeout: 'wrapSyncTimeout'
        };
        Object.entries(errors).forEach(([field, message]) => {
            const wrapper = document.getElementById(wrappers[field] || '');
            if (!wrapper) return;
            wrapper.classList.add('wrapper-error');
            const error = wrapper.querySelector('.error-msg');
            if (error) error.textContent = message;
        });
    }

    function sourcePayload() {
        return {
            name: document.getElementById('syncSourceName').value.trim(),
            base_url: document.getElementById('syncBaseUrl').value.trim(),
            api_key: document.getElementById('syncApiKey').value.trim(),
            enabled: document.getElementById('syncEnabled').checked,
            verify_ssl: document.getElementById('syncVerifySsl').checked,
            request_timeout: Number(document.getElementById('syncTimeout').value || 20)
        };
    }

    function connectionStatusLabel(status) {
        const labels = {
            never: ['Non verificata', 'label-default'],
            success: ['Connessa', 'label-success'],
            failed: ['Non raggiungibile', 'label-danger']
        };
        return labels[status] || labels.never;
    }

    function syncStatusPresentation(status) {
        const presentations = {
            never: ['Mai eseguita', 'label-default'],
            running: ['In corso', 'label-info'],
            success: ['Completata', 'label-success'],
            partial: ['Completata con anomalie', 'label-warning'],
            failed: ['Fallita', 'label-danger']
        };
        return presentations[status] || presentations.never;
    }

    function renderSyncStatus(status) {
        const [label, cssClass] = syncStatusPresentation(status);
        const badge = document.getElementById('syncLastStatusBadge');
        badge.className = `label ${cssClass} pull-right`;
        badge.textContent = label;
        document.getElementById('syncLastStatus').textContent = label;
    }

    function formatSyncDateTime(value, emptyValue = '-') {
        if (!value) return emptyValue;
        const normalized = String(value).includes('T')
            ? String(value)
            : String(value).replace(' ', 'T');
        const date = new Date(normalized);
        if (Number.isNaN(date.getTime())) return String(value);
        return new Intl.DateTimeFormat('it-IT', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        }).format(date);
    }

    function renderSource(data) {
        source = data;
        document.getElementById('syncSourceName').value = data.name || '';
        document.getElementById('syncBaseUrl').value = data.base_url || '';
        document.getElementById('syncApiKey').value = '';
        document.getElementById('syncEnabled').checked = Number(data.enabled) === 1;
        document.getElementById('syncVerifySsl').checked = Number(data.verify_ssl) === 1;
        document.getElementById('syncTimeout').value = data.request_timeout || 20;
        document.getElementById('syncApiKeyHint').textContent = data.api_key_configured
            ? 'Chiave gia configurata. Lascia vuoto per mantenerla.'
            : 'La chiave viene cifrata prima del salvataggio.';

        const isEnabled = Number(data.enabled) === 1;
        const [connectionLabel, connectionClass] = connectionStatusLabel(data.connection_status);
        const label = isEnabled ? connectionLabel : 'Disabilitata';
        const cssClass = isEnabled ? connectionClass : 'label-default';
        const badge = document.getElementById('syncSourceStatus');
        badge.className = `label ${cssClass} pull-right`;
        badge.textContent = label;
        const lastConnectionAt = formatSyncDateTime(data.last_connection_at, '');
        badge.title = [
            data.last_connection_message || '',
            lastConnectionAt ? `Ultima verifica: ${lastConnectionAt}` : ''
        ].filter(Boolean).join(' ');
        renderSyncStatus(data.last_status);
        document.getElementById('syncLastAt').textContent = formatSyncDateTime(data.last_execution_at);
        document.getElementById('syncLastCursor').textContent = formatSyncDateTime(
            data.last_sync_cursor,
            'Nessun sync completato'
        );
        document.getElementById('syncLastMessage').textContent = data.last_message || '-';

        setBusy(syncBusy);
    }

    async function loadSource() {
        const json = await fetchJSON('../api/v1/football-sync/source');
        if (!json || !json.success) {
            toast(json?.error || 'Impossibile caricare la sorgente', 'error');
            return;
        }
        renderSource(json.data);
    }

    function destroyTable(selector) {
        if (window.$ && $.fn.DataTable && $.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().clear().destroy();
        }
    }

    function appendCell(row, value, className = '') {
        const cell = document.createElement('td');
        cell.textContent = value ?? '';
        cell.className = className;
        row.appendChild(cell);
        return cell;
    }

    function option(value, label, selectedValue) {
        const item = document.createElement('option');
        item.value = value;
        item.textContent = label;
        item.selected = String(value) === String(selectedValue ?? '');
        return item;
    }

    function fillSeasonSelect(select, selectedValue = '', emptyLabel = 'Seleziona') {
        select.innerHTML = '';
        select.appendChild(option('', emptyLabel, selectedValue));
        overrideSeasons.forEach(season => {
            const suffix = season.status === 'in_corso' ? ' (in corso)' : '';
            select.appendChild(option(season.id, `${season.name}${suffix}`, selectedValue));
        });
    }

    function overrideOrigin(item) {
        const context = item.source_context || {};
        if (item.entity_type === 'competitions') {
            const league = context.league_name || 'Lega non indicata';
            return context.league_external_id ? `${league} (#${context.league_external_id})` : league;
        }
        const dates = [context.starts_on, context.ends_on].filter(Boolean);
        return dates.length ? dates.join(' / ') : 'Date non disponibili';
    }

    function initOverridesTable() {
        if (!(window.$ && $.fn.DataTable)) return;
        $('#syncOverrides').DataTable({
            pageLength: 25,
            lengthChange: false,
            autoWidth: false,
            order: [[1, 'asc'], [3, 'asc']],
            columnDefs: [
                { targets: [0, 1, 2, 6, 7, 8], className: 'text-center' },
                { targets: [0, 6, 7, 8], orderable: false }
            ],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/it-IT.json' }
        });
    }

    function renderOverrides(data) {
        overrideSeasons = Array.isArray(data.seasons) ? data.seasons : [];
        const items = Array.isArray(data.items) ? data.items : [];
        const bulkSeason = document.getElementById('bulkSyncSeason');
        fillSeasonSelect(bulkSeason, bulkSeason.value, 'Non modificare');

        destroyTable('#syncOverrides');
        const body = document.querySelector('#syncOverrides tbody');
        body.innerHTML = '';

        items.forEach(item => {
            const row = document.createElement('tr');
            row.dataset.entityType = item.entity_type;
            row.dataset.externalId = item.external_id;

            const selectedCell = document.createElement('td');
            selectedCell.className = 'text-center';
            const selected = document.createElement('input');
            selected.type = 'checkbox';
            selected.className = 'sync-override-selected';
            selected.disabled = !canManage;
            selectedCell.appendChild(selected);
            row.appendChild(selectedCell);

            appendCell(row, item.entity_type === 'competitions' ? 'Competizione' : 'Stagione', 'text-left');
            appendCell(row, item.external_id, 'text-center');
            appendCell(row, item.external_name || `ID ${item.external_id}`, 'text-left');
            appendCell(row, overrideOrigin(item), 'text-left');

            const issueCell = appendCell(row, item.issue_message || 'Correzione configurata', 'text-left sync-override-issue');
            if (item.issue_message) issueCell.classList.add('text-warning');

            const footballCell = document.createElement('td');
            footballCell.className = 'text-center';
            const football = document.createElement('select');
            football.className = 'form-control input-sm sync-override-football';
            football.appendChild(option('', 'Automatico', item.football_type));
            football.appendChild(option('11', 'Calcio a 11', item.football_type));
            football.appendChild(option('7', 'Calcio a 7', item.football_type));
            football.appendChild(option('5', 'Calcio a 5', item.football_type));
            football.disabled = !canManage || item.entity_type !== 'competitions';
            footballCell.appendChild(football);
            row.appendChild(footballCell);

            const seasonCell = document.createElement('td');
            seasonCell.className = 'text-center';
            const season = document.createElement('select');
            season.className = 'form-control input-sm sync-override-season';
            fillSeasonSelect(season, item.season_local_id, 'Automatico');
            season.disabled = !canManage;
            seasonCell.appendChild(season);
            row.appendChild(seasonCell);

            const ignoredCell = document.createElement('td');
            ignoredCell.className = 'text-center';
            const ignored = document.createElement('input');
            ignored.type = 'checkbox';
            ignored.className = 'sync-override-ignored';
            ignored.checked = Number(item.ignored) === 1;
            ignored.disabled = !canManage;
            ignoredCell.appendChild(ignored);
            row.appendChild(ignoredCell);

            body.appendChild(row);
        });

        const issueCount = items.filter(item => item.issue_message).length;
        const badge = document.getElementById('syncOverrideCount');
        badge.textContent = `${issueCount} ${issueCount === 1 ? 'anomalia' : 'anomalie'}`;
        badge.className = `label ${issueCount ? 'label-warning' : 'label-success'} pull-right`;
        document.getElementById('syncOverridesEmpty').classList.toggle('hidden', items.length !== 0);
        document.getElementById('syncOverrides').classList.toggle('hidden', items.length === 0);
        document.getElementById('selectAllSyncOverrides').checked = false;
        if (items.length) initOverridesTable();
        setBusy(syncBusy);
    }

    async function loadOverrides() {
        const json = await fetchJSON('../api/v1/football-sync/overrides');
        if (!json || !json.success) {
            toast(json?.error || 'Impossibile caricare le correzioni mapping', 'error');
            return;
        }
        renderOverrides(json.data);
    }

    function overrideRows() {
        if (window.$ && $.fn.DataTable && $.fn.DataTable.isDataTable('#syncOverrides')) {
            return Array.from($('#syncOverrides').DataTable().rows().nodes());
        }
        return Array.from(document.querySelectorAll('#syncOverrides tbody tr'));
    }

    function overridePayload() {
        return overrideRows().map(row => ({
            entity_type: row.dataset.entityType,
            external_id: row.dataset.externalId,
            football_type: row.querySelector('.sync-override-football').value || null,
            season_local_id: row.querySelector('.sync-override-season').value || null,
            ignored: row.querySelector('.sync-override-ignored').checked
        }));
    }

    function runStatus(status) {
        const labels = {
            running: ['In corso', 'label-info'], success: ['Completata', 'label-success'],
            partial: ['Parziale', 'label-warning'], failed: ['Fallita', 'label-danger']
        };
        return labels[status] || [status || '-', 'label-default'];
    }

    function initRunsTable() {
        if (!(window.$ && $.fn.DataTable)) return;
        $('#syncRuns').DataTable({
            pageLength: 10,
            lengthChange: false,
            autoWidth: false,
            order: [[0, 'desc']],
            columnDefs: [
                { targets: [0, 1, 2, 3, 4, 5, 6, 7, 8, 10], className: 'text-center' },
                { targets: 10, width: '60px', orderable: false }
            ],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/it-IT.json' }
        });
    }

    async function loadRuns() {
        const json = await fetchJSON('../api/v1/football-sync/runs?limit=100');
        if (!json || !json.success) {
            toast(json?.error || 'Impossibile caricare lo storico', 'error');
            return [];
        }

        destroyTable('#syncRuns');
        const body = document.querySelector('#syncRuns tbody');
        body.innerHTML = '';
        json.data.forEach(run => {
            const row = document.createElement('tr');
            appendCell(row, run.started_at || '-', 'text-center');
            appendCell(row, run.sync_mode === 'full' ? 'Completo' : 'Incrementale', 'text-center');

            const statusCell = document.createElement('td');
            statusCell.className = 'text-center';
            const [label, cssClass] = runStatus(run.status);
            const badge = document.createElement('span');
            badge.className = `label ${cssClass}`;
            badge.textContent = label;
            statusCell.appendChild(badge);
            row.appendChild(statusCell);

            appendCell(row, run.created_count, 'text-center');
            appendCell(row, run.updated_count, 'text-center');
            appendCell(row, run.skipped_count, 'text-center');
            appendCell(row, run.ignored_count || 0, 'text-center');
            appendCell(row, run.failed_count, 'text-center');
            appendCell(row, run.deleted_count, 'text-center');
            appendCell(row, run.user_name || '-', 'text-left');

            const actionCell = document.createElement('td');
            actionCell.className = 'text-center';
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-default btn-xs syncRunDetails';
            button.title = 'Dettaglio esecuzione';
            button.dataset.id = run.id;
            const icon = document.createElement('i');
            icon.className = 'fa fa-search';
            button.appendChild(icon);
            actionCell.appendChild(button);
            row.appendChild(actionCell);
            body.appendChild(row);
        });
        initRunsTable();
        return json.data;
    }

    async function showRunItems(runId) {
        const json = await fetchJSON(`../api/v1/football-sync/run-items?run_id=${encodeURIComponent(runId)}`);
        if (!json || !json.success) {
            toast(json?.error || 'Impossibile caricare il dettaglio', 'error');
            return;
        }

        destroyTable('#syncRunItems');
        const body = document.querySelector('#syncRunItems tbody');
        body.innerHTML = '';
        json.data.forEach(item => {
            const row = document.createElement('tr');
            appendCell(row, item.entity_type, 'text-left');
            appendCell(row, item.external_id, 'text-center');
            appendCell(row, item.local_id || '-', 'text-center');
            appendCell(row, item.action, 'text-center');
            appendCell(row, item.message || '-', 'text-left');
            body.appendChild(row);
        });

        if (window.$ && $.fn.DataTable) {
            $('#syncRunItems').DataTable({
                pageLength: 10, lengthChange: false, autoWidth: false, order: [],
                columnDefs: [{ targets: [1, 2, 3], className: 'text-center' }],
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/it-IT.json' }
            });
            $('#syncRunItemsModal').modal('show');
        }
    }

    function setBusy(busy) {
        syncBusy = Boolean(busy);
        const sourceReady = source
            && Number(source.enabled) === 1
            && source.api_key_configured;

        runButtons.forEach(button => {
            if (!button) return;
            button.disabled = syncBusy || !canRun || !sourceReady;
            button.title = syncBusy
                ? 'Sincronizzazione in corso'
                : (!source || Number(source.enabled) !== 1
                    ? 'Attiva la sincronizzazione e salva la configurazione'
                    : (!source.api_key_configured ? 'Configura prima la chiave API' : ''));
        });

        form.querySelectorAll('input, select, button').forEach(control => {
            control.disabled = syncBusy || !canManage;
        });

        const testButton = document.getElementById('testSyncConnection');
        if (testButton) testButton.disabled = syncBusy || !canManage;

        document.querySelectorAll('.sync-overrides-toolbar button, .sync-overrides-toolbar select').forEach(control => {
            control.disabled = syncBusy || !canManage;
        });
        document.getElementById('selectAllSyncOverrides').disabled = syncBusy || !canManage;
        overrideRows().forEach(row => {
            const selected = row.querySelector('.sync-override-selected');
            const football = row.querySelector('.sync-override-football');
            const season = row.querySelector('.sync-override-season');
            const ignored = row.querySelector('.sync-override-ignored');
            if (selected) selected.disabled = syncBusy || !canManage;
            if (football) football.disabled = syncBusy || !canManage || row.dataset.entityType !== 'competitions';
            if (season) season.disabled = syncBusy || !canManage;
            if (ignored) ignored.disabled = syncBusy || !canManage;
        });
    }

    function formatDuration(seconds) {
        const total = Math.max(0, Number(seconds) || 0);
        const hours = Math.floor(total / 3600);
        const minutes = Math.floor((total % 3600) / 60);
        const remainingSeconds = Math.floor(total % 60);
        const parts = [minutes, remainingSeconds].map(value => String(value).padStart(2, '0'));
        return hours > 0 ? `${String(hours).padStart(2, '0')}:${parts.join(':')}` : parts.join(':');
    }

    function entityLabel(entityType) {
        const labels = {
            seasons: 'Stagioni', competitions: 'Competizioni', stadiums: 'Stadi',
            teams: 'Squadre', referees: 'Arbitri', matches: 'Partite'
        };
        return labels[entityType] || 'Preparazione indice remoto';
    }

    function resetProgressPanelState() {
        const panel = document.getElementById('syncProgressPanel');
        panel.classList.remove('sync-progress-success', 'sync-progress-warning', 'sync-progress-danger');
        const icon = document.getElementById('syncProgressIcon');
        icon.className = 'fa fa-refresh fa-spin';
        const track = document.getElementById('syncProgressTrack');
        track.classList.add('active');
        const bar = document.getElementById('syncProgressBar');
        bar.className = 'progress-bar progress-bar-info progress-bar-striped';
    }

    function showPreparingProgress(mode) {
        if (progressHideTimer) window.clearTimeout(progressHideTimer);
        resetProgressPanelState();
        renderSyncStatus('running');
        document.getElementById('syncProgressPanel').classList.remove('hidden');
        document.getElementById('syncProgressTitle').textContent = mode === 'full'
            ? 'Sincronizzazione completa in corso'
            : 'Sincronizzazione incrementale in corso';
        document.getElementById('syncProgressPercentage').textContent = 'Preparazione';
        document.getElementById('syncProgressPhase').textContent = 'Lettura indice remoto';
        document.getElementById('syncProgressCount').textContent = 'Totale in calcolo';
        document.getElementById('syncProgressElapsed').textContent = '00:00';
        const bar = document.getElementById('syncProgressBar');
        bar.style.width = '100%';
        bar.setAttribute('aria-valuenow', '0');
    }

    function renderProgress(run) {
        if (progressHideTimer) window.clearTimeout(progressHideTimer);
        resetProgressPanelState();
        renderSyncStatus('running');
        document.getElementById('syncProgressPanel').classList.remove('hidden');
        document.getElementById('syncProgressTitle').textContent = run.sync_mode === 'full'
            ? 'Sincronizzazione completa in corso'
            : 'Sincronizzazione incrementale in corso';
        document.getElementById('syncProgressPhase').textContent = entityLabel(run.current_entity);
        document.getElementById('syncProgressElapsed').textContent = formatDuration(run.elapsed_seconds);

        const total = Number(run.total_count) || 0;
        const processed = Number(run.processed_count) || 0;
        const percentage = run.percentage === null ? null : Number(run.percentage);
        const bar = document.getElementById('syncProgressBar');
        if (percentage === null) {
            document.getElementById('syncProgressPercentage').textContent = 'Preparazione';
            document.getElementById('syncProgressCount').textContent = 'Totale in calcolo';
            bar.style.width = '100%';
            bar.setAttribute('aria-valuenow', '0');
            return;
        }

        const percentageLabel = Number.isInteger(percentage) ? percentage.toFixed(0) : percentage.toFixed(1);
        document.getElementById('syncProgressPercentage').textContent = `${percentageLabel}%`;
        document.getElementById('syncProgressCount').textContent = `${processed.toLocaleString('it-IT')} di ${total.toLocaleString('it-IT')} record`;
        bar.style.width = `${Math.max(0, Math.min(100, percentage))}%`;
        bar.setAttribute('aria-valuenow', String(percentage));
    }

    function renderProgressResult(data, fallbackMessage = '') {
        const status = data?.status || 'failed';
        renderSyncStatus(status);
        const panel = document.getElementById('syncProgressPanel');
        panel.classList.remove('hidden');
        resetProgressPanelState();
        document.getElementById('syncProgressTrack').classList.remove('active');
        document.getElementById('syncProgressIcon').className = status === 'success'
            ? 'fa fa-check-circle'
            : (status === 'partial' ? 'fa fa-exclamation-triangle' : 'fa fa-times-circle');

        const presentation = {
            success: ['Sincronizzazione completata', 'sync-progress-success', 'progress-bar-success'],
            partial: ['Sincronizzazione completata con anomalie', 'sync-progress-warning', 'progress-bar-warning'],
            failed: ['Sincronizzazione non riuscita', 'sync-progress-danger', 'progress-bar-danger']
        }[status] || ['Sincronizzazione terminata', 'sync-progress-warning', 'progress-bar-warning'];
        panel.classList.add(presentation[1]);
        document.getElementById('syncProgressTitle').textContent = presentation[0];
        document.getElementById('syncProgressPercentage').textContent = status === 'failed' ? 'Errore' : '100%';
        document.getElementById('syncProgressPhase').textContent = data?.message || fallbackMessage || presentation[0];
        const counts = data?.counts || {};
        const processed = ['created', 'updated', 'skipped', 'ignored', 'failed', 'deleted_at_source']
            .reduce((sum, key) => sum + (Number(counts[key]) || 0), 0);
        document.getElementById('syncProgressCount').textContent = processed
            ? `${processed.toLocaleString('it-IT')} record elaborati`
            : 'Elaborazione terminata';
        const bar = document.getElementById('syncProgressBar');
        bar.className = `progress-bar ${presentation[2]}`;
        bar.style.width = '100%';
        bar.setAttribute('aria-valuenow', '100');

        if (progressHideTimer) window.clearTimeout(progressHideTimer);
        progressHideTimer = window.setTimeout(() => panel.classList.add('hidden'), 6000);
    }

    function stopProgressPolling() {
        if (progressPollTimer) window.clearInterval(progressPollTimer);
        progressPollTimer = null;
    }

    async function pollProgress() {
        if (progressPollPending) return observedRunningSync;
        progressPollPending = true;
        try {
            const json = await fetchJSON('../api/v1/football-sync/progress');
            if (!json || !json.success) {
                return observedRunningSync;
            }

            const run = json.data?.run || null;
            if (run && !progressResponseComplete) {
                observedRunningSync = true;
                setBusy(true);
                renderProgress(run);
                return true;
            }

            if (observedRunningSync && !syncRequestActive) {
                observedRunningSync = false;
                progressResponseComplete = false;
                stopProgressPolling();
                setBusy(false);
                const [, runs] = await Promise.all([loadSource(), loadRuns(), loadOverrides()]);
                const latest = Array.isArray(runs) ? runs[0] : null;
                if (latest) {
                    renderProgressResult({
                        status: latest.status,
                        message: latest.message,
                        counts: {
                            created: latest.created_count,
                            updated: latest.updated_count,
                            skipped: latest.skipped_count,
                            ignored: latest.ignored_count,
                            failed: latest.failed_count,
                            deleted_at_source: latest.deleted_count
                        }
                    });
                } else {
                    document.getElementById('syncProgressPanel').classList.add('hidden');
                }
            }
            return false;
        } finally {
            progressPollPending = false;
        }
    }

    function startProgressPolling() {
        if (!progressPollTimer) {
            progressPollTimer = window.setInterval(pollProgress, 1200);
        }
        pollProgress();
    }

    async function runSync(mode) {
        if (!canRun || syncBusy) return;
        const confirmed = await AppDialog.open({
            title: mode === 'full' ? 'Sincronizzazione completa' : 'Sincronizzazione incrementale',
            message: mode === 'full'
                ? 'Rileggere tutti i record disponibili dalla sorgente WordPress?'
                : 'Importare le modifiche disponibili dalla sorgente WordPress?',
            confirmText: 'Avvia sincronizzazione', confirmClass: 'btn-primary', showCancel: true
        });
        if (!confirmed) return;

        syncRequestActive = true;
        observedRunningSync = true;
        progressResponseComplete = false;
        setBusy(true);
        showPreparingProgress(mode);
        startProgressPolling();
        const json = await fetchJSON('../api/v1/football-sync/run', {
            method: 'POST', body: JSON.stringify({ mode })
        });
        syncRequestActive = false;
        progressResponseComplete = Boolean(json);
        stopProgressPolling();
        const stillRunning = !json?.success && await pollProgress();
        if (stillRunning) {
            toast('La richiesta si e interrotta, ma la sincronizzazione risulta ancora in corso.', 'warning');
            startProgressPolling();
            return;
        }

        observedRunningSync = false;
        setBusy(false);
        if (!json || !json.success) {
            renderProgressResult(null, json?.error || 'Sincronizzazione non riuscita');
            toast(json?.error || 'Sincronizzazione non riuscita', 'error');
            await Promise.all([loadSource(), loadRuns(), loadOverrides()]);
            return;
        }
        renderProgressResult(json.data);
        toast(json.data.message || 'Sincronizzazione completata', json.data.status === 'success' ? 'success' : 'warning');
        await Promise.all([loadSource(), loadRuns(), loadOverrides()]);
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (!canManage || syncBusy) return;
        clearErrors();
        const json = await fetchJSON('../api/v1/football-sync/source', {
            method: 'PUT', body: JSON.stringify(sourcePayload())
        });
        if (!json || !json.success) {
            showErrors(json?.errors || {});
            toast(json?.error || 'Configurazione non salvata', 'error');
            return;
        }
        renderSource(json.data);
        toast('Configurazione salvata', 'success');
    });

    document.getElementById('testSyncConnection').addEventListener('click', async () => {
        if (!canManage || syncBusy) return;
        setBusy(true);
        const json = await fetchJSON('../api/v1/football-sync/test', { method: 'POST', body: '{}' });
        setBusy(false);
        if (!json || !json.success) {
            toast(json?.error || 'Connessione non riuscita', 'error');
            await loadSource();
            return;
        }
        toast(`Connessione riuscita. Plugin ${json.data.info.version || ''}`, 'success');
        await loadSource();
    });

    document.getElementById('runIncrementalSync').addEventListener('click', () => runSync('incremental'));
    document.getElementById('runFullSync').addEventListener('click', () => runSync('full'));
    document.getElementById('selectAllSyncOverrides').addEventListener('change', event => {
        overrideRows().forEach(row => {
            const checkbox = row.querySelector('.sync-override-selected');
            if (!checkbox.disabled) checkbox.checked = event.target.checked;
        });
    });
    document.getElementById('applySyncOverrides').addEventListener('click', () => {
        if (!canManage || syncBusy) return;
        const footballType = document.getElementById('bulkSyncFootballType').value;
        const seasonId = document.getElementById('bulkSyncSeason').value;
        const ignored = document.getElementById('bulkSyncIgnored').value;
        let changed = 0;

        overrideRows().forEach(row => {
            if (!row.querySelector('.sync-override-selected').checked) return;
            if (footballType && row.dataset.entityType === 'competitions') {
                row.querySelector('.sync-override-football').value = footballType;
            }
            if (seasonId) row.querySelector('.sync-override-season').value = seasonId;
            if (ignored !== '') row.querySelector('.sync-override-ignored').checked = ignored === '1';
            changed++;
        });

        toast(changed ? `Correzione applicata a ${changed} record` : 'Seleziona almeno un record', changed ? 'info' : 'warning');
    });
    document.getElementById('saveSyncOverrides').addEventListener('click', async () => {
        if (!canManage || syncBusy) return;
        const json = await fetchJSON('../api/v1/football-sync/overrides', {
            method: 'PUT', body: JSON.stringify({ items: overridePayload() })
        });
        if (!json || !json.success) {
            toast(json?.error || 'Correzioni non salvate', 'error');
            return;
        }
        renderOverrides(json.data);
        toast('Correzioni salvate. Esegui nuovamente il sync completo.', 'success');
    });
    document.addEventListener('click', event => {
        const button = event.target.closest('.syncRunDetails');
        if (button) showRunItems(button.dataset.id);
    });

    Promise.all([loadSource(), loadRuns(), loadOverrides(), pollProgress()]).then(() => {
        if (observedRunningSync) startProgressPolling();
    });
});
