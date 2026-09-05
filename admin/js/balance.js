document.addEventListener('DOMContentLoaded', () => {

    let monthlyChart = null;
    let seasonComparisonChart = null;
    let seasonComparisonLoaded = false;
    let currentSeasonAnalysis = null;
    const exportBalance = document.getElementById('exportBalance');
    const balanceFrom = document.getElementById('balanceFrom');
    const balanceTo = document.getElementById('balanceTo');
    const balanceCompetition = document.getElementById('balanceCompetition');
    const balanceTeam = document.getElementById('balanceTeam');
    const balanceReferee = document.getElementById('balanceReferee');
    const applyBalanceFilters = document.getElementById('applyBalanceFilters');
    const resetBalanceFilters = document.getElementById('resetBalanceFilters');
    const comparisonBaseSeason = document.getElementById('comparisonBaseSeason');
    const comparisonCompareSeason = document.getElementById('comparisonCompareSeason');
    const applySeasonAnalysis = document.getElementById('applySeasonAnalysis');
    const approveBalanceClosure = document.getElementById('approveBalanceClosure');
    const archiveBalanceClosure = document.getElementById('archiveBalanceClosure');
    const balanceComparisonMessage = document.getElementById('balanceComparisonMessage');

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

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function appendCell(row, text, className = '', html = false) {
        const cell = document.createElement('td');

        if (html) {
            cell.innerHTML = text ?? '';
        } else {
            cell.textContent = text ?? '';
        }

        if (className) {
            cell.className = className;
        }

        row.appendChild(cell);
        return cell;
    }

    function renderRows(selector, rows, columns) {
        const tbody = document.querySelector(`${selector} tbody`);
        if (!tbody) return;

        tbody.innerHTML = '';

        if (!Array.isArray(rows) || rows.length === 0) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = columns.length;
            td.className = 'text-center text-muted';
            td.textContent = 'Nessun dato disponibile';
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }

        rows.forEach(item => {
            const tr = document.createElement('tr');

            columns.forEach(column => {
                const value = typeof column.value === 'function'
                    ? column.value(item)
                    : item[column.value];

                const className = typeof column.className === 'function'
                    ? column.className(item)
                    : column.className;

                appendCell(tr, value, className || '', Boolean(column.html));
            });

            tbody.appendChild(tr);
        });
    }

    function renderMonthlyChart(data) {
        const canvas = document.getElementById('balanceMonthlyChart');
        if (!canvas || !window.Chart) return;

        if (monthlyChart) {
            monthlyChart.destroy();
        }

        monthlyChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        type: 'bar',
                        label: 'Entrate',
                        data: data.income || [],
                        backgroundColor: 'rgba(0, 166, 90, 0.75)'
                    },
                    {
                        type: 'bar',
                        label: 'Uscite',
                        data: data.expenses || [],
                        backgroundColor: 'rgba(221, 75, 57, 0.75)'
                    },
                    {
                        type: 'line',
                        label: 'Saldo',
                        data: data.profit || [],
                        borderColor: '#00c0ef',
                        backgroundColor: 'rgba(0, 192, 239, 0.12)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                return `${context.dataset.label}: ${formatCurrency(context.raw)}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                }
            }
        });
    }

    function renderSeasonComparisonChart(data) {
        const canvas = document.getElementById('balanceSeasonComparisonChart');
        if (!canvas || !window.Chart) return;

        if (seasonComparisonChart) {
            seasonComparisonChart.destroy();
        }

        seasonComparisonChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        type: 'bar',
                        label: 'Entrate',
                        data: data.income || [],
                        backgroundColor: 'rgba(0, 166, 90, 0.75)'
                    },
                    {
                        type: 'bar',
                        label: 'Uscite',
                        data: data.expenses || [],
                        backgroundColor: 'rgba(221, 75, 57, 0.75)'
                    },
                    {
                        type: 'line',
                        label: 'Saldo',
                        data: data.profit || [],
                        borderColor: '#f39c12',
                        backgroundColor: 'rgba(243, 156, 18, 0.12)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                return `${context.dataset.label}: ${formatCurrency(context.raw)}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                }
            }
        });
    }

    function formatPercent(value) {
        if (value === null || value === undefined) {
            return '-';
        }

        return `${Number(value).toLocaleString('it-IT', {
            minimumFractionDigits: 1,
            maximumFractionDigits: 1
        })}%`;
    }

    function formatProfitChange(item) {
        if (Number(item.total_movements || 0) === 0) {
            return 'N.d.';
        }

        if (item.profit_change === null || item.profit_change === undefined) {
            return '-';
        }

        return `${formatCurrency(item.profit_change)} (${formatPercent(item.profit_change_percent)})`;
    }

    function formatChange(value) {
        const amount = Number(value || 0);
        const sign = amount > 0 ? '+' : '';

        return `${sign}${formatCurrency(amount)}`;
    }

    function formatPercentChange(value) {
        if (value === null || value === undefined) {
            return '-';
        }

        const amount = Number(value || 0);
        const sign = amount > 0 ? '+' : '';

        return `${sign}${formatPercent(amount)}`;
    }

    function amountClass(value) {
        const amount = Number(value || 0);

        if (amount > 0) {
            return 'text-center text-green';
        }

        if (amount < 0) {
            return 'text-center text-red';
        }

        return 'text-center text-muted';
    }

    function labelHtml(text, className) {
        return `<span class="label ${className}">${text}</span>`;
    }

    function closureStatusHtml(item) {
        const closure = item.closure || null;

        if (closure?.movements_deleted_at) {
            return labelHtml('Storicizzato', 'label-primary');
        }

        if (closure?.approval_status === 'approved') {
            return labelHtml('Approvato', 'label-success');
        }

        if (closure?.approval_status === 'draft') {
            return labelHtml('Bozza', 'label-warning');
        }

        if (item.season_status === 'chiuso') {
            return labelHtml('Da chiudere', 'label-default');
        }

        if (item.season_status === 'in_corso') {
            return labelHtml('In corso', 'label-info');
        }

        return labelHtml('Nuovo', 'label-default');
    }

    function sourceLabelHtml(source) {
        return source === 'snapshot'
            ? labelHtml('Snapshot', 'label-primary')
            : labelHtml('Prima nota', 'label-default');
    }

    function sortByProfitChange(rows = []) {
        return [...rows].sort((a, b) => {
            const diff = Math.abs(Number(b.profit_change || 0)) - Math.abs(Number(a.profit_change || 0));

            if (diff !== 0) {
                return diff;
            }

            return String(a.category_name || a.competition_name || '').localeCompare(
                String(b.category_name || b.competition_name || ''),
                'it'
            );
        });
    }

    function comparisonQueryString() {
        const params = new URLSearchParams();

        if (comparisonBaseSeason.value) {
            params.set('base_season_id', comparisonBaseSeason.value);
        }

        if (comparisonCompareSeason.value) {
            params.set('compare_season_id', comparisonCompareSeason.value);
        }

        const query = params.toString();
        return query ? `?${query}` : '';
    }

    function renderSelectOptions(select, options = [], selectedValue = '', placeholder = 'Tutti') {
        if (!select) return;

        const currentValue = selectedValue || select.value || '';
        select.innerHTML = '';
        select.appendChild(new Option(placeholder, ''));

        options.forEach(option => {
            select.appendChild(new Option(option.name, option.id));
        });

        select.value = currentValue;
    }

    function renderFilterOptions(data) {
        const options = data.filter_options || {};
        const filters = data.filters || {};

        renderSelectOptions(balanceCompetition, options.competitions || [], filters.competition_id || '', 'Tutte');
        renderSelectOptions(balanceTeam, options.teams || [], filters.team_id || '', 'Tutte');
        renderSelectOptions(balanceReferee, options.referees || [], filters.referee_id || '', 'Tutti');
    }

    function renderBalance(data) {
        renderFilterOptions(data);

        setText('balanceSeasonSubtitle', `Stagione ${data.season?.name || '-'} - Da prima nota`);

        if (data.filters) {
            balanceFrom.value = data.filters.from || '';
            balanceTo.value = data.filters.to || '';
            balanceCompetition.value = data.filters.competition_id || '';
            balanceTeam.value = data.filters.team_id || '';
            balanceReferee.value = data.filters.referee_id || '';
        }

        setText('balance-income', formatCurrency(data.income));
        setText('balance-expenses', formatCurrency(data.expenses));
        setText('balance-profit', formatCurrency(data.profit));
        setText('balance-total-movements', data.total_movements ?? 0);

        renderMonthlyChart(data.monthly || {});

        renderRows('#balance-categories', data.categories || [], [
            { value: 'category_name' },
            { value: 'subcategory_name' },
            { value: item => formatCurrency(item.income), className: 'text-center' },
            { value: item => formatCurrency(item.expenses), className: 'text-center' },
            { value: item => formatCurrency(item.profit), className: 'text-center' }
        ]);

        renderRows('#balance-competitions', data.competitions || [], [
            { value: 'competition_name' },
            { value: item => formatCurrency(item.income), className: 'text-center' },
            { value: item => formatCurrency(item.expenses), className: 'text-center' },
            { value: item => formatCurrency(item.profit), className: 'text-center' }
        ]);
    }

    function renderSeasonComparison(data) {
        renderSeasonComparisonChart(data.chart || {});

        renderRows('#balance-seasons', data.rows || [], [
            { value: 'season_name' },
            { value: item => closureStatusHtml({ closure: item, season_status: item.status }), className: 'text-center', html: true },
            { value: item => sourceLabelHtml(item.data_source), className: 'text-center', html: true },
            { value: item => formatCurrency(item.income), className: 'text-center' },
            { value: item => formatCurrency(item.expenses), className: 'text-center' },
            { value: item => formatCurrency(item.profit), className: 'text-center' },
            { value: item => formatProfitChange(item), className: 'text-center' },
            { value: 'total_movements', className: 'text-center' }
        ]);
    }

    function renderSeasonOptions(options = [], baseSeason = null, compareSeason = null) {
        renderSelectOptions(
            comparisonBaseSeason,
            options,
            baseSeason?.id ? String(baseSeason.id) : comparisonBaseSeason.value,
            'Seleziona'
        );

        renderSelectOptions(
            comparisonCompareSeason,
            options,
            compareSeason?.id ? String(compareSeason.id) : comparisonCompareSeason.value,
            'Seleziona'
        );
    }

    function renderComparisonMessage(message) {
        if (!balanceComparisonMessage) return;

        if (!message) {
            balanceComparisonMessage.style.display = 'none';
            balanceComparisonMessage.textContent = '';
            return;
        }

        balanceComparisonMessage.textContent = message;
        balanceComparisonMessage.style.display = 'block';
    }

    function updateApproveButton(data) {
        if (!approveBalanceClosure) return;

        const season = data.base_season || null;
        const closure = data.base_closure || null;
        const canApprove = Auth.can('balance.approve')
            && season
            && season.status === 'chiuso'
            && (!closure || closure.approval_status !== 'approved');

        approveBalanceClosure.style.display = Auth.can('balance.approve') ? '' : 'none';
        approveBalanceClosure.disabled = !canApprove;
        approveBalanceClosure.title = canApprove
            ? `Approva bilancio ${season.name}`
            : 'Seleziona una stagione chiusa non ancora approvata';
    }

    function updateArchiveButton(data) {
        if (!archiveBalanceClosure) return;

        const season = data.base_season || null;
        const closure = data.base_closure || null;
        const canArchive = Auth.can('balance.archive')
            && season
            && season.status === 'chiuso'
            && closure
            && closure.approval_status === 'approved'
            && !closure.movements_deleted_at;

        archiveBalanceClosure.style.display = Auth.can('balance.archive') ? '' : 'none';
        archiveBalanceClosure.disabled = !canArchive;
        archiveBalanceClosure.title = canArchive
            ? `Storicizza movimenti ${season.name}`
            : 'Seleziona una stagione chiusa con bilancio approvato e movimenti non ancora storicizzati';
    }

    function renderSeasonAnalysis(data) {
        currentSeasonAnalysis = data;
        renderSeasonOptions(data.season_options || [], data.base_season, data.compare_season);
        renderComparisonMessage(data.message || null);
        renderSeasonComparisonChart(data.chart || {});
        updateApproveButton(data);
        updateArchiveButton(data);

        if (!data.summary) {
            renderRows('#balance-seasons', [], [
                { value: 'season_name' },
                { value: 'status' },
                { value: 'source' },
                { value: 'income' },
                { value: 'expenses' },
                { value: 'profit' },
                { value: 'profit_change' },
                { value: 'total_movements' }
            ]);
            renderRows('#balance-comparison-categories', [], [
                { value: 'category_name' },
                { value: 'subcategory_name' },
                { value: 'base_profit' },
                { value: 'compare_profit' },
                { value: 'profit_change' },
                { value: 'profit_change_percent' },
                { value: 'base_total_movements' },
                { value: 'compare_total_movements' }
            ]);
            renderRows('#balance-comparison-competitions', [], [
                { value: 'competition_name' },
                { value: 'competition_type' },
                { value: 'football_type' },
                { value: 'base_profit' },
                { value: 'compare_profit' },
                { value: 'profit_change' },
                { value: 'profit_change_percent' },
                { value: 'base_total_movements' },
                { value: 'compare_total_movements' }
            ]);
            return;
        }

        const summaryRows = [
            {
                season_name: data.base_season?.name || 'Base',
                season_status: data.base_season?.status || null,
                closure: data.base_closure || null,
                source: data.base_source || 'movements',
                income: data.summary.base.income,
                expenses: data.summary.base.expenses,
                profit: data.summary.base.profit,
                profit_change: '-',
                total_movements: data.summary.base.total_movements
            },
            {
                season_name: data.compare_season?.name || 'Confronto',
                season_status: data.compare_season?.status || null,
                closure: data.compare_closure || null,
                source: data.compare_source || 'movements',
                income: data.summary.compare.income,
                expenses: data.summary.compare.expenses,
                profit: data.summary.compare.profit,
                profit_change: `${formatChange(data.summary.profit_change)} (${formatPercent(data.summary.profit_change_percent)})`,
                profit_change_value: data.summary.profit_change,
                total_movements: data.summary.compare.total_movements
            }
        ];

        renderRows('#balance-seasons', summaryRows, [
            { value: 'season_name' },
            { value: item => closureStatusHtml(item), className: 'text-center', html: true },
            { value: item => sourceLabelHtml(item.source), className: 'text-center', html: true },
            { value: item => formatCurrency(item.income), className: 'text-center' },
            { value: item => formatCurrency(item.expenses), className: 'text-center' },
            { value: item => formatCurrency(item.profit), className: item => amountClass(item.profit) },
            { value: 'profit_change', className: item => amountClass(item.profit_change_value) },
            { value: 'total_movements', className: 'text-center' }
        ]);

        renderRows('#balance-comparison-categories', sortByProfitChange(data.categories || []), [
            { value: 'category_name' },
            { value: 'subcategory_name' },
            { value: item => formatCurrency(item.base.profit), className: item => amountClass(item.base.profit) },
            { value: item => formatCurrency(item.compare.profit), className: item => amountClass(item.compare.profit) },
            { value: item => formatChange(item.profit_change), className: item => amountClass(item.profit_change) },
            { value: item => formatPercentChange(item.profit_change_percent), className: item => amountClass(item.profit_change) },
            { value: item => item.base.total_movements, className: 'text-center' },
            { value: item => item.compare.total_movements, className: 'text-center' }
        ]);

        renderRows('#balance-comparison-competitions', sortByProfitChange(data.competitions || []), [
            { value: 'competition_name' },
            { value: 'competition_type', className: 'text-center' },
            { value: 'football_type', className: 'text-center' },
            { value: item => formatCurrency(item.base.profit), className: item => amountClass(item.base.profit) },
            { value: item => formatCurrency(item.compare.profit), className: item => amountClass(item.compare.profit) },
            { value: item => formatChange(item.profit_change), className: item => amountClass(item.profit_change) },
            { value: item => formatPercentChange(item.profit_change_percent), className: item => amountClass(item.profit_change) },
            { value: item => item.base.total_movements, className: 'text-center' },
            { value: item => item.compare.total_movements, className: 'text-center' }
        ]);
    }

    async function downloadBalanceCsv() {
        const response = await fetch(`../api/v1/balance-export${filterQueryString()}`, {
            headers: authHeaders()
        });

        if (!response.ok) {
            showToast('Esportazione bilancio non riuscita', 'error');
            return;
        }

        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        const disposition = response.headers.get('content-disposition') || '';
        const filenameMatch = disposition.match(/filename="([^"]+)"/);

        link.href = url;
        link.download = filenameMatch ? filenameMatch[1] : 'bilancio.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }

    function filterQueryString() {
        const params = new URLSearchParams();

        if (balanceFrom.value) {
            params.set('from', balanceFrom.value);
        }

        if (balanceTo.value) {
            params.set('to', balanceTo.value);
        }

        if (balanceCompetition.value) {
            params.set('competition_id', balanceCompetition.value);
        }

        if (balanceTeam.value) {
            params.set('team_id', balanceTeam.value);
        }

        if (balanceReferee.value) {
            params.set('referee_id', balanceReferee.value);
        }

        const query = params.toString();
        return query ? `?${query}` : '';
    }

    function loadBalance() {
        return fetchJSON(`../api/v1/balance${filterQueryString()}`)
            .then(json => {
                if (!json || !json.success || !json.data) {
                    showToast(json?.error || 'Impossibile caricare il bilancio', 'error');
                    return;
                }

                renderBalance(json.data);
            });
    }

    function loadSeasonComparison() {
        return fetchJSON(`../api/v1/balance-season-analysis${comparisonQueryString()}`)
            .then(json => {
                if (!json || !json.success || !json.data) {
                    showToast(json?.error || 'Impossibile caricare il confronto stagioni', 'error');
                    return;
                }

                renderSeasonAnalysis(json.data);
                seasonComparisonLoaded = true;
            });
    }

    async function approveSelectedBalanceClosure() {
        const season = currentSeasonAnalysis?.base_season || null;

        if (!season || !season.id) {
            showToast('Seleziona una stagione base da approvare', 'error');
            return;
        }

        const confirmed = await AppDialog.open({
            title: 'Approva bilancio',
            message: `Approvi il bilancio della stagione ${season.name}? Dopo l'approvazione lo snapshot non verra sovrascritto automaticamente.`,
            confirmText: 'Approva',
            confirmClass: 'btn-success',
            showCancel: true
        });

        if (!confirmed) return;

        const json = await fetchJSON(`../api/v1/balance-closure-approve?season_id=${encodeURIComponent(season.id)}`, {
            method: 'PUT'
        });

        if (!json || !json.success) {
            showToast(json?.error || 'Approvazione bilancio non riuscita', 'error');
            return;
        }

        showToast(json.data?.message || 'Bilancio approvato', 'success');
        await loadSeasonComparison();
    }

    async function archiveSelectedBalanceClosure() {
        const season = currentSeasonAnalysis?.base_season || null;
        const closure = currentSeasonAnalysis?.base_closure || null;

        if (!season || !season.id || !closure) {
            showToast('Seleziona una stagione base approvata da storicizzare', 'error');
            return;
        }

        const confirmed = await AppDialog.open({
            title: 'Storicizza bilancio',
            message: `Storicizzare il bilancio ${season.name}? I movimenti della stagione saranno eliminati e i confronti useranno lo snapshot approvato.`,
            confirmText: 'Storicizza',
            confirmClass: 'btn-danger',
            showCancel: true
        });

        if (!confirmed) return;

        const json = await fetchJSON(`../api/v1/balance-closure-archive?season_id=${encodeURIComponent(season.id)}`, {
            method: 'PUT'
        });

        if (!json || !json.success) {
            showToast(json?.error || 'Storicizzazione bilancio non riuscita', 'error');
            return;
        }

        showToast(json.data?.message || 'Bilancio storicizzato', 'success');
        await loadSeasonComparison();
    }

    if (exportBalance) {
        exportBalance.addEventListener('click', () => {
            downloadBalanceCsv();
        });
    }

    if (applyBalanceFilters) {
        applyBalanceFilters.addEventListener('click', () => {
            loadBalance();
        });
    }

    if (resetBalanceFilters) {
        resetBalanceFilters.addEventListener('click', () => {
            balanceFrom.value = '';
            balanceTo.value = '';
            balanceCompetition.value = '';
            balanceTeam.value = '';
            balanceReferee.value = '';
            loadBalance();
        });
    }

    if (applySeasonAnalysis) {
        applySeasonAnalysis.addEventListener('click', () => {
            loadSeasonComparison();
        });
    }

    if (approveBalanceClosure) {
        approveBalanceClosure.addEventListener('click', () => {
            approveSelectedBalanceClosure();
        });
    }

    if (archiveBalanceClosure) {
        archiveBalanceClosure.addEventListener('click', () => {
            archiveSelectedBalanceClosure();
        });
    }

    $('a[href="#balance-comparison-tab"]').on('shown.bs.tab', () => {
        if (!seasonComparisonLoaded) {
            loadSeasonComparison();
            return;
        }

        if (seasonComparisonChart) {
            seasonComparisonChart.resize();
        }
    });

    loadBalance();
});
