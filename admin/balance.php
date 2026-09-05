<?php
    include '../admin/includes/topbar.php';
?>

<section class="content container-fluid">

    <div class="row" id="balance-header" style="margin-bottom:15px;">

        <div class="col-md-12 header-item">
            <div>
                <h3 style="margin:0; line-height:26px;">
                    Bilancio
                </h3>
                <span id="balanceSeasonSubtitle" class="text-muted">
                    Stagione - · Da prima nota
                </span>
            </div>
        </div>

    </div>

    <div class="row" id="balance-kpi">

        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="balance-income">0,00</h3>
                    <p>Entrate</p>
                </div>
                <div class="icon">
                    <i class="fa fa-arrow-up"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="balance-expenses">0,00</h3>
                    <p>Uscite</p>
                </div>
                <div class="icon">
                    <i class="fa fa-arrow-down"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3 id="balance-profit">0,00</h3>
                    <p>Saldo</p>
                </div>
                <div class="icon">
                    <i class="fa fa-balance-scale"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="balance-total-movements">0</h3>
                    <p>Movimenti</p>
                </div>
                <div class="icon">
                    <i class="fa fa-book"></i>
                </div>
            </div>
        </div>

    </div>

    <div class="nav-tabs-custom">

        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#balance-current-tab" data-toggle="tab">
                    <i class="fa fa-balance-scale"></i>
                    Bilancio stagione
                </a>
            </li>
            <li>
                <a href="#balance-comparison-tab" data-toggle="tab">
                    <i class="fa fa-line-chart"></i>
                    Confronto stagioni
                </a>
            </li>
        </ul>

        <div class="tab-content">

            <div class="tab-pane active" id="balance-current-tab">

    <div class="row" id="balance-filters" style="margin-bottom:15px;">

        <div class="col-md-12">

            <div class="box box-primary" style="margin-bottom:10px;">

                <div class="box-body" style="padding:10px;">
                    <div class="row">

                        <div class="col-md-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Dal</label>
                                <input type="date" id="balanceFrom" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Al</label>
                                <input type="date" id="balanceTo" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Competizione</label>
                                <select id="balanceCompetition" class="form-control">
                                    <option value="">Tutte</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Squadra</label>
                                <select id="balanceTeam" class="form-control">
                                    <option value="">Tutte</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Arbitro</label>
                                <select id="balanceReferee" class="form-control">
                                    <option value="">Tutti</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2 header-actions" style="padding-top:25px;">
                            <button id="applyBalanceFilters" class="btn btn-primary btn-sm" title="Applica filtri" aria-label="Applica filtri">
                                <i class="fa fa-filter"></i>
                            </button>

                            <button id="resetBalanceFilters" class="btn btn-default btn-sm" title="Reset filtri" aria-label="Reset filtri">
                                <i class="fa fa-undo"></i>
                            </button>

                            <button id="exportBalance" class="btn btn-primary btn-sm" data-permission="balance.export" title="Esporta CSV" aria-label="Esporta CSV">
                                <i class="fa fa-download"></i>
                            </button>
                        </div>

                    </div>
                </div>

            </div>

        </div>

    </div>

    <div class="row">

        <div class="col-md-12">

            <div class="box box-primary">

                <div class="box-header with-border">
                    <h3 class="box-title">Andamento mensile</h3>
                </div>

                <div class="box-body">
                    <div class="dashboard-chart-wrapper">
                        <canvas id="balanceMonthlyChart"></canvas>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <div class="row">

        <div class="col-md-6">

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Riepilogo per categoria</h3>
                </div>

                <div class="box-body table-responsive">
                    <table id="balance-categories" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th>Sottocategoria</th>
                                <th>Entrate</th>
                                <th>Uscite</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>

        <div class="col-md-6">

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Riepilogo per competizione</h3>
                </div>

                <div class="box-body table-responsive">
                    <table id="balance-competitions" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Competizione</th>
                                <th>Entrate</th>
                                <th>Uscite</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

            </div>

            <div class="tab-pane" id="balance-comparison-tab">

    <div class="row" id="balance-comparison-filters" style="margin-bottom:15px;">

        <div class="col-md-12">

            <div class="box box-primary" style="margin-bottom:10px;">

                <div class="box-body" style="padding:10px;">
                    <div class="row">

                        <div class="col-md-4">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Stagione base</label>
                                <select id="comparisonBaseSeason" class="form-control">
                                    <option value="">Seleziona</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Stagione confronto</label>
                                <select id="comparisonCompareSeason" class="form-control">
                                    <option value="">Seleziona</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4 header-actions" style="padding-top:25px;">
                            <button type="button" id="applySeasonAnalysis" class="btn btn-primary btn-sm" title="Aggiorna confronto" aria-label="Aggiorna confronto">
                                <i class="fa fa-refresh"></i>
                            </button>

                            <button type="button" id="approveBalanceClosure" class="btn btn-success btn-sm" data-permission="balance.approve" title="Approva bilancio stagione base" aria-label="Approva bilancio stagione base" style="display:none;">
                                <i class="fa fa-check"></i>
                            </button>

                            <button type="button" id="archiveBalanceClosure" class="btn btn-danger btn-sm" data-permission="balance.archive" title="Storicizza bilancio approvato" aria-label="Storicizza bilancio approvato" style="display:none;">
                                <i class="fa fa-archive"></i>
                            </button>
                        </div>

                    </div>
                </div>

            </div>

        </div>

    </div>

    <div id="balanceComparisonMessage" class="alert alert-info" style="display:none;"></div>

    <div class="row">

        <div class="col-md-12">

            <div class="box box-primary">

                <div class="box-header with-border">
                    <h3 class="box-title">Confronto stagioni</h3>
                </div>

                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="dashboard-chart-wrapper">
                                <canvas id="balanceSeasonComparisonChart"></canvas>
                            </div>
                        </div>

                        <div class="col-md-6 table-responsive">
                            <table id="balance-seasons" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Stagione</th>
                                        <th>Stato</th>
                                        <th>Fonte</th>
                                        <th>Entrate</th>
                                        <th>Uscite</th>
                                        <th>Saldo</th>
                                        <th title="Differenza del saldo tra le due stagioni selezionate">Var. saldo</th>
                                        <th>Mov.</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <div class="row">

        <div class="col-md-12">

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Confronto per categoria</h3>
                    <small class="text-muted">ordinate per variazione saldo</small>
                </div>

                <div class="box-body table-responsive">
                    <table id="balance-comparison-categories" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th>Sottocategoria</th>
                                <th>Saldo base</th>
                                <th>Saldo confronto</th>
                                <th>Var. saldo</th>
                                <th>Var. %</th>
                                <th>Mov. base</th>
                                <th>Mov. confronto</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    <div class="row">

        <div class="col-md-12">

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Confronto per competizione</h3>
                    <small class="text-muted">ordinate per variazione saldo</small>
                </div>

                <div class="box-body table-responsive">
                    <table id="balance-comparison-competitions" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Competizione</th>
                                <th>Tipo</th>
                                <th>Calcio</th>
                                <th>Saldo base</th>
                                <th>Saldo confronto</th>
                                <th>Var. saldo</th>
                                <th>Var. %</th>
                                <th>Mov. base</th>
                                <th>Mov. confronto</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

            </div>

        </div>

    </div>


</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="js/balance.js"></script>

<?php
    include '../admin/includes/footer.php';
?>
