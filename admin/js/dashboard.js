document.addEventListener('DOMContentLoaded', () => {

    let monthlyFinancialChart = null;
    let incomeCategoriesChart = null;
    let expenseCategoriesChart = null;
    const chartPalette = ['#00a65a', '#3c8dbc', '#f39c12', '#605ca8', '#39cccc', '#d81b60'];

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

    function loadDashboardKpis() {
        return fetchJSON('../api/v1/dashboard-kpi')
            .then(json => {
                if (!json || !json.success || !json.data) {
                    showToast(json?.error || 'Impossibile caricare i KPI', 'error');
                    return;
                }

                const data = json.data;

                setText('kpi-income', formatCurrency(data.income));
                setText('kpi-expenses', formatCurrency(data.expenses));
                setText('kpi-profit', formatCurrency(data.profit));
                setText('kpi-competitions', data.competitions ?? 0);
                setText('kpi-teams', data.teams ?? 0);

                renderDesignationKpis(data.designation_summary || {});
                renderTopReferees(data.designation_summary?.top_referees || []);
                renderCompetitionBalances(data.competition_balances || []);
                renderOperationalAlerts(data.operational_alerts || []);
            });
    }

    function renderDesignationKpis(summary) {
        setText('kpi-designation-total', summary.total_matches ?? 0);
        setText('kpi-designation-to-designate', summary.to_designate ?? 0);
        setText('kpi-designation-proposed', summary.proposed ?? 0);
        setText('kpi-designation-confirmed', summary.confirmed ?? 0);
        setText('kpi-designation-manual', summary.manual ?? 0);
    }

    function renderTopReferees(referees) {
        const tbody = document.getElementById('dashboardTopReferees');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (!referees.length) {
            const row = document.createElement('tr');
            appendTextCell(row, 'Nessuna designazione', 'text-center text-muted').colSpan = 4;
            tbody.appendChild(row);
            return;
        }

        referees.forEach(referee => {
            const row = document.createElement('tr');
            appendTextCell(row, referee.referee_name || '-');
            appendTextCell(row, referee.total ?? 0, 'text-center text-nowrap');
            appendTextCell(row, referee.confirmed ?? 0, 'text-center text-nowrap');
            appendTextCell(row, referee.proposed ?? 0, 'text-center text-nowrap');
            tbody.appendChild(row);
        });
    }

    function appendTextCell(row, text, className = '') {
        const cell = document.createElement('td');
        cell.textContent = text ?? '';

        if (className) {
            cell.className = className;
        }

        row.appendChild(cell);
        return cell;
    }

    function renderCompetitionBalances(balances) {
        const tbody = document.getElementById('dashboardCompetitionBalances');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (!balances.length) {
            const row = document.createElement('tr');
            appendTextCell(row, 'Nessun movimento collegato a competizioni', 'text-center text-muted').colSpan = 4;
            tbody.appendChild(row);
            return;
        }

        balances.forEach(item => {
            const row = document.createElement('tr');
            appendTextCell(row, item.competition_name || '-');
            appendTextCell(row, formatCurrency(item.income), 'text-right text-nowrap');
            appendTextCell(row, formatCurrency(item.expenses), 'text-right text-nowrap');

            const profitCell = appendTextCell(row, formatCurrency(item.profit), 'text-right text-nowrap');
            profitCell.classList.add(Number(item.profit || 0) >= 0 ? 'text-green' : 'text-red');

            tbody.appendChild(row);
        });
    }

    function renderOperationalAlerts(alerts) {
        const list = document.getElementById('dashboardOperationalAlerts');
        if (!list) return;

        list.innerHTML = '';

        if (!alerts.length) {
            const item = document.createElement('div');
            item.className = 'dashboard-alert-item alert-empty';
            const content = document.createElement('span');
            content.className = 'dashboard-alert-content';
            const icon = document.createElement('i');
            icon.className = 'fa fa-check-circle dashboard-alert-icon';
            const label = document.createElement('span');
            label.className = 'dashboard-alert-label';
            label.textContent = 'Nessun alert operativo';
            content.append(icon, label);
            const count = document.createElement('strong');
            count.className = 'dashboard-alert-count';
            count.textContent = '0';
            item.append(content, count);
            list.appendChild(item);
            return;
        }

        const icons = {
            seasons_to_close: 'fa-calendar-times-o',
            matches_without_designation: 'fa-user-plus',
            teams_without_field: 'fa-home',
            referees_without_address: 'fa-address-card-o',
            fields_without_geocode: 'fa-map-marker',
            competitions_without_teams: 'fa-users'
        };

        alerts.forEach(alert => {
            const item = alert.href ? document.createElement('a') : document.createElement('div');
            const countValue = Number(alert.count || 0);
            item.className = `dashboard-alert-item alert-${alert.severity || 'info'}${countValue === 0 ? ' alert-empty' : ''}`;

            if (alert.href) {
                item.href = alert.href;
                item.title = 'Apri dettaglio';
            }

            const content = document.createElement('span');
            content.className = 'dashboard-alert-content';

            const icon = document.createElement('i');
            icon.className = `fa ${icons[alert.code] || 'fa-info-circle'} dashboard-alert-icon`;

            const label = document.createElement('span');
            label.className = 'dashboard-alert-label';
            label.textContent = alert.label || '-';

            const count = document.createElement('strong');
            count.className = 'dashboard-alert-count';
            count.textContent = countValue;

            content.append(icon, label);
            item.append(content, count);

            if (alert.href) {
                const arrow = document.createElement('i');
                arrow.className = 'fa fa-angle-right dashboard-alert-arrow';
                item.appendChild(arrow);
            }

            list.appendChild(item);
        });
    }

    function renderMonthlyFinancialChart(data) {
        const canvas = document.getElementById('monthlyFinancialChart');
        if (!canvas || !window.Chart) return;

        if (monthlyFinancialChart) {
            monthlyFinancialChart.destroy();
        }

        monthlyFinancialChart = new Chart(canvas, {
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
                        label: 'Utile',
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

    function renderCategoryChart(canvasId, chartRef, data) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !window.Chart) return null;

        if (chartRef) {
            chartRef.destroy();
        }

        return new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        data: data.values || [],
                        backgroundColor: chartPalette
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
                                return `${context.label}: ${formatCurrency(context.raw)}`;
                            }
                        }
                    }
                }
            }
        });
    }

    function loadDashboardCharts() {
        return fetchJSON('../api/v1/dashboard-charts')
            .then(json => {
                if (!json || !json.success || !json.data) {
                    showToast(json?.error || 'Impossibile caricare i grafici', 'error');
                    return;
                }

                renderMonthlyFinancialChart(json.data.monthly_financial || {});
                incomeCategoriesChart = renderCategoryChart(
                    'incomeCategoriesChart',
                    incomeCategoriesChart,
                    json.data.financial_categories?.income || {}
                );
                expenseCategoriesChart = renderCategoryChart(
                    'expenseCategoriesChart',
                    expenseCategoriesChart,
                    json.data.financial_categories?.expenses || {}
                );
            });
    }

    loadDashboardKpis();
    loadDashboardCharts();
});
