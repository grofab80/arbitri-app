<?php
    include '../admin/includes/topbar.php';
?>

<section class="content container-fluid">

    <div class="row" id="designations-header" style="margin-bottom:15px;">

        <div class="col-md-12 header-item">
            <h3 style="margin:0; line-height:34px;">
                Designazioni
            </h3>
        </div>

    </div>

    <div class="nav-tabs-custom">

        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#designations-main-tab" data-toggle="tab">
                    <i class="fa fa-random"></i>
                    Designazioni
                </a>
            </li>
            <li data-permission="designations.blacklist.manage">
                <a href="#designations-blacklist-tab" data-toggle="tab">
                    <i class="fa fa-ban"></i>
                    Blacklist
                </a>
            </li>
        </ul>

        <div class="tab-content">

            <div class="tab-pane active" id="designations-main-tab">

    <div class="row" id="designations-filters" style="margin-bottom:15px;">

        <div class="col-md-12">

            <div class="box box-primary" style="margin-bottom:10px;">

                <div class="box-body" style="padding:10px;">
                    <div class="row">

                        <div class="col-md-3">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Disciplina</label>
                                <select id="designationFootballType" class="form-control">
                                    <option value="11">Calcio a 11</option>
                                    <option value="7">Calcio a 7</option>
                                    <option value="5">Calcio a 5</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Competizione</label>
                                <select id="designationCompetition" class="form-control">
                                    <option value="">Tutte</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Giornata</label>
                                <input type="number" id="designationMatchDay" class="form-control" min="1" step="1" placeholder="Tutte">
                            </div>
                        </div>

                        <div class="col-md-3 header-actions" style="padding-top:25px;">
                            <button type="button" id="generateDesignations" class="btn btn-success btn-sm" title="Rigenera proposte non confermate" aria-label="Rigenera proposte non confermate">
                                <i class="fa fa-magic"></i>
                            </button>

                            <button type="button" id="confirmFilteredDesignations" class="btn btn-success btn-sm" title="Conferma proposte filtrate" aria-label="Conferma proposte filtrate">
                                <i class="fa fa-check-square-o"></i>
                            </button>

                            <button type="button" id="clearAutomaticDesignations" class="btn btn-danger btn-sm" title="Pulisci proposte automatiche" aria-label="Pulisci proposte automatiche">
                                <i class="fa fa-eraser"></i>
                            </button>

                            <button type="button" id="applyDesignationFilters" class="btn btn-primary btn-sm" title="Applica filtri" aria-label="Applica filtri">
                                <i class="fa fa-filter"></i>
                            </button>

                            <button type="button" id="resetDesignationFilters" class="btn btn-default btn-sm" title="Reset filtri" aria-label="Reset filtri">
                                <i class="fa fa-undo"></i>
                            </button>
                        </div>

                    </div>
                </div>

            </div>

        </div>

    </div>

    <div class="row" id="designations-summary-row">

        <div class="col-md-5">
            <div class="box box-primary designation-summary-box">
                <div class="box-header with-border">
                    <h3 class="box-title">Riepilogo</h3>
                </div>
                <div class="box-body">
                    <div class="designation-kpi-strip">
                        <div class="designation-kpi-item kpi-aqua">
                            <i class="fa fa-calendar"></i>
                            <div>
                                <span id="designation-total-matches">0</span>
                                <small>Partite totali</small>
                            </div>
                        </div>

                        <div class="designation-kpi-item kpi-yellow">
                            <i class="fa fa-user-plus"></i>
                            <div>
                                <span id="designation-to-designate">0</span>
                                <small>Da designare</small>
                            </div>
                        </div>

                        <div class="designation-kpi-item kpi-blue">
                            <i class="fa fa-magic"></i>
                            <div>
                                <span id="designation-proposed">0</span>
                                <small>Proposte</small>
                            </div>
                        </div>

                        <div class="designation-kpi-item kpi-green">
                            <i class="fa fa-check"></i>
                            <div>
                                <span id="designation-confirmed">0</span>
                                <small>Confermate</small>
                            </div>
                        </div>

                        <div class="designation-kpi-item kpi-purple">
                            <i class="fa fa-pencil"></i>
                            <div>
                                <span id="designation-manual">0</span>
                                <small>Modificate manualmente</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="box box-primary designation-summary-box">
                <div class="box-header with-border">
                    <h3 class="box-title">Arbitri pi&ugrave; utilizzati</h3>
                </div>
                <div class="box-body table-responsive" style="padding-top:0;">
                    <table id="designationTopReferees" class="table table-bordered table-striped" style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th>Arbitro</th>
                                <th>Totale</th>
                                <th>Confermate</th>
                                <th>Proposte</th>
                                <th>Manuali</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="box box-primary">

        <div class="box-body table-responsive">

            <table id="designations" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Ora</th>
                        <th>Giornata</th>
                        <th>Competizione</th>
                        <th>Partita</th>
                        <th>Stadio</th>
                        <th>Difficolta</th>
                        <th>Arbitro</th>
                        <th>Score</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>

    </div>

            </div>

            <div class="tab-pane" id="designations-blacklist-tab">

    <div class="box box-primary" id="designationBlacklistSection" style="margin-bottom:0;">

        <div class="box-header with-border">
            <h3 class="box-title">Blacklist arbitro/squadra</h3>
        </div>

        <div class="box-body">

            <div class="row" style="margin-bottom:10px;">

                <div class="col-md-3">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Arbitro</label>
                        <select id="blacklistReferee" class="form-control">
                            <option value="">Seleziona arbitro</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Squadra</label>
                        <select id="blacklistTeam" class="form-control">
                            <option value="">Seleziona squadra</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Motivo</label>
                        <input type="text" id="blacklistReason" class="form-control" maxlength="255">
                    </div>
                </div>

                <div class="col-md-2 header-actions" style="padding-top:25px;">
                    <button type="button" id="addBlacklistRule" class="btn btn-primary btn-sm" title="Aggiungi blacklist" aria-label="Aggiungi blacklist">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>

            </div>

            <div class="table-responsive">
                <table id="designationBlacklist" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Arbitro</th>
                            <th>Squadra</th>
                            <th>Motivo</th>
                            <th>Azioni</th>
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

</section>

<script src="js/designations.js"></script>

<?php
    include '../admin/includes/footer.php';
?>
