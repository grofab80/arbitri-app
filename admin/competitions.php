<?php
    include '../admin/includes/topbar.php';
?>

<section class="content container-fluid">

    <div class="row" id="competitions-header" style="margin-bottom:15px;">

        <div class="col-md-1 header-item">
            <h3 style="margin:0; line-height:34px;">
                Competizioni
            </h3>
        </div>

        <div class="col-md-11 header-item header-actions">
            <button id="openCompetitionModal" class="btn btn-primary btn-sm" data-permission="competitions.create">
                <i class="fa fa-plus"></i>
                Nuova Competizione
            </button>
        </div>

    </div>

    <div class="box box-primary">

        <div class="box-body">

            <table id="competitions" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Stagione</th>
                        <th>Tipo</th>
                        <th>Calcio</th>
                        <th>Creata il</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>

    </div>

</section>

<div class="modal fade" id="competitionModal" tabindex="-1" role="dialog">

    <div class="modal-dialog" role="document">

        <div class="modal-content">

            <form id="competitionForm" novalidate>

                <div class="modal-header bg-primary">

                    <button type="button"
                            class="close"
                            id="closeCompetitionModal"
                            data-dismiss="modal"
                            aria-label="Chiudi">
                        <span aria-hidden="true">&times;</span>
                    </button>

                    <h4 class="modal-title">
                        <i class="fa fa-shield"></i>
                        Gestione Competizione
                    </h4>

                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-xs-12">

                            <div class="box box-primary">

                                <div class="box-header with-border">
                                    <h3 class="box-title">
                                        Dati competizione
                                    </h3>
                                </div>

                                <div class="box-body competition-box-body">

                                    <div class="row">

                                        <div class="col-md-6">
                                            <div id="wrapName" class="form-group">
                                                <label>Nome <span class="required">*</span></label>
                                                <input type="text"
                                                       id="competition_name"
                                                       class="form-control"
                                                       placeholder="Nome competizione">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div id="wrapSeason" class="form-group">
                                                <label>Stagione <span class="required">*</span></label>
                                                <select id="season_id" class="form-control"></select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                    </div>

                                    <br>

                                    <div class="row">

                                        <div class="col-md-6">
                                            <div id="wrapType" class="form-group">
                                                <label>Tipo <span class="required">*</span></label>
                                                <select id="competition_type" class="form-control">
                                                    <option value="">Seleziona</option>
                                                    <option value="campionato">Campionato</option>
                                                    <option value="torneo">Torneo</option>
                                                </select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div id="wrapFootballType" class="form-group">
                                                <label>Tipologia calcio <span class="required">*</span></label>
                                                <select id="football_type" class="form-control">
                                                    <option value="">Seleziona</option>
                                                    <option value="11">Calcio a 11</option>
                                                    <option value="7">Calcio a 7</option>
                                                    <option value="5">Calcio a 5</option>
                                                </select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-default"
                            data-dismiss="modal">
                        Chiudi
                    </button>

                    <button type="submit"
                            id="submitCompetition"
                            class="btn btn-primary">
                        Salva
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>

<div class="modal fade" id="standingModal" tabindex="-1" role="dialog">

    <div class="modal-dialog modal-lg" role="document">

        <div class="modal-content">

            <div class="modal-header bg-primary">

                <button type="button"
                        class="close"
                        id="closeStandingModal"
                        data-dismiss="modal"
                        aria-label="Chiudi">
                    <span aria-hidden="true">&times;</span>
                </button>

                <h4 class="modal-title">
                    <i class="fa fa-list-ol"></i>
                    <span id="standingTitle">Classifica</span>
                </h4>

            </div>

            <div class="modal-body">

                <div id="standingNotice" class="alert alert-info" style="display:none;"></div>

                <div class="table-responsive">
                    <table id="standings" class="table table-bordered table-striped standings-table">
                        <thead>
                            <tr>
                                <th>Pos</th>
                                <th>Squadra</th>
                                <th>G</th>
                                <th>V</th>
                                <th>N</th>
                                <th>P</th>
                                <th>GF</th>
                                <th>GS</th>
                                <th>DR</th>
                                <th>Pen</th>
                                <th>Punti</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-default"
                        data-dismiss="modal">
                    Chiudi
                </button>

                <button type="button"
                        id="saveStandings"
                        class="btn btn-primary"
                        data-permission="competitions.standings.manage">
                    <i class="fa fa-save"></i>
                    Salva Classifica
                </button>

                <button type="button"
                        id="recalculateStandings"
                        class="btn btn-warning"
                        data-permission="competitions.standings.manage">
                    <i class="fa fa-refresh"></i>
                    Ricalcola da Partite
                </button>
            </div>

        </div>

    </div>

</div>

<script src="js/competitions.js"></script>

<?php
    include '../admin/includes/footer.php';
?>
