<?php
    include '../admin/includes/topbar.php';
?>

<section class="content container-fluid">

    <div class="row" id="dashboard-header" style="margin-bottom:15px;">

        <div class="col-md-12 header-item">
            <h3 style="margin:0; line-height:34px;">
                Dashboard
            </h3>
        </div>

    </div>

    <div class="dashboard-kpi-grid" id="dashboard-main-kpi">

        <div class="dashboard-kpi-cell">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="kpi-income">&euro; 0,00</h3>
                    <p>Entrate</p>
                </div>
                <div class="icon">
                    <i class="fa fa-arrow-up"></i>
                </div>
            </div>
        </div>

        <div class="dashboard-kpi-cell">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="kpi-expenses">&euro; 0,00</h3>
                    <p>Uscite</p>
                </div>
                <div class="icon">
                    <i class="fa fa-arrow-down"></i>
                </div>
            </div>
        </div>

        <div class="dashboard-kpi-cell">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3 id="kpi-profit">&euro; 0,00</h3>
                    <p>Utile</p>
                </div>
                <div class="icon">
                    <i class="fa fa-line-chart"></i>
                </div>
            </div>
        </div>

        <div class="dashboard-kpi-cell">
            <div class="small-box bg-teal">
                <div class="inner">
                    <h3 id="kpi-competitions">0</h3>
                    <p>Competizioni</p>
                </div>
                <div class="icon">
                    <i class="fa fa-shield"></i>
                </div>
            </div>
        </div>

        <div class="dashboard-kpi-cell">
            <div class="small-box bg-maroon">
                <div class="inner">
                    <h3 id="kpi-teams">0</h3>
                    <p>Squadre iscritte</p>
                </div>
                <div class="icon">
                    <i class="fa fa-users"></i>
                </div>
            </div>
        </div>

    </div>

    <div class="row" id="dashboard-designation-kpi">

        <div class="col-md-12">
            <div class="dashboard-designation-kpi-strip">
                <div class="dashboard-designation-kpi-item kpi-aqua">
                    <i class="fa fa-random"></i>
                    <div>
                        <span id="kpi-designation-total">0</span>
                        <small>Partite gestite</small>
                    </div>
                </div>

                <div class="dashboard-designation-kpi-item kpi-yellow">
                    <i class="fa fa-user-plus"></i>
                    <div>
                        <span id="kpi-designation-to-designate">0</span>
                        <small>Da designare</small>
                    </div>
                </div>

                <div class="dashboard-designation-kpi-item kpi-blue">
                    <i class="fa fa-magic"></i>
                    <div>
                        <span id="kpi-designation-proposed">0</span>
                        <small>Proposte</small>
                    </div>
                </div>

                <div class="dashboard-designation-kpi-item kpi-green">
                    <i class="fa fa-check"></i>
                    <div>
                        <span id="kpi-designation-confirmed">0</span>
                        <small>Confermate</small>
                    </div>
                </div>

                <div class="dashboard-designation-kpi-item kpi-purple">
                    <i class="fa fa-pencil"></i>
                    <div>
                        <span id="kpi-designation-manual">0</span>
                        <small>Manuali</small>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row">

        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Andamento economico mensile</h3>
                </div>

                <div class="box-body">
                    <div class="dashboard-chart-wrapper">
                        <canvas id="monthlyFinancialChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">Alert operativi</h3>
                </div>

                <div class="box-body no-padding">
                    <div class="dashboard-alert-list" id="dashboardOperationalAlerts">
                        <div class="dashboard-alert-item">
                            <span>Nessun alert operativo</span>
                            <strong>0</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row">

        <div class="col-md-7">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">Saldo per competizione</h3>
                </div>

                <div class="box-body table-responsive no-padding">
                    <table class="table table-striped dashboard-competition-balances" style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th>Competizione</th>
                                <th>Entrate</th>
                                <th>Uscite</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody id="dashboardCompetitionBalances">
                            <tr>
                                <td colspan="4" class="text-center text-muted">Nessun movimento collegato a competizioni</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Arbitri pi&ugrave; utilizzati</h3>
                </div>

                <div class="box-body table-responsive no-padding">
                    <table class="table table-striped dashboard-top-referees" style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th>Arbitro</th>
                                <th>Totale</th>
                                <th>Conf.</th>
                                <th>Prop.</th>
                            </tr>
                        </thead>
                        <tbody id="dashboardTopReferees">
                            <tr>
                                <td colspan="4" class="text-center text-muted">Nessuna designazione</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="row">

        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">Entrate per categoria</h3>
                </div>

                <div class="box-body">
                    <div class="dashboard-chart-wrapper dashboard-chart-wrapper-small">
                        <canvas id="incomeCategoriesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">Uscite per categoria</h3>
                </div>

                <div class="box-body">
                    <div class="dashboard-chart-wrapper dashboard-chart-wrapper-small">
                        <canvas id="expenseCategoriesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="js/dashboard.js"></script>

<?php
    include '../admin/includes/footer.php';
?>
