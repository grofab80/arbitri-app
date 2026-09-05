<?php
    include '../admin/includes/topbar.php';
?>

<section class="content container-fluid">

    <div class="row" id="matches-header" style="margin-bottom:15px;">

        <div class="col-md-1 header-item">
            <h3 style="margin:0; line-height:34px;">
                Partite
            </h3>
        </div>

        <div class="col-md-11 header-item header-actions">
            <button id="openMatchModal" class="btn btn-primary btn-sm" data-permission="matches.create">
                <i class="fa fa-plus"></i>
                Nuova Partita
            </button>
        </div>

    </div>

    <div class="row" id="matches-filters" style="margin-bottom:15px;">

        <div class="col-md-12">

            <div class="box box-primary" style="margin-bottom:10px;">

                <div class="box-body" style="padding:10px;">
                    <div class="row">

                        <div class="col-md-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Dal</label>
                                <input type="date" id="matchFilterFrom" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Al</label>
                                <input type="date" id="matchFilterTo" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Competizione</label>
                                <select id="matchFilterCompetition" class="form-control">
                                    <option value="">Tutte</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Squadra</label>
                                <select id="matchFilterTeam" class="form-control">
                                    <option value="">Tutte</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Arbitro</label>
                                <select id="matchFilterReferee" class="form-control">
                                    <option value="">Tutti</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2 header-actions" style="padding-top:25px;">
                            <button type="button" id="applyMatchFilters" class="btn btn-primary btn-sm" title="Applica filtri" aria-label="Applica filtri">
                                <i class="fa fa-filter"></i>
                            </button>

                            <button type="button" id="resetMatchFilters" class="btn btn-default btn-sm" title="Reset filtri" aria-label="Reset filtri">
                                <i class="fa fa-undo"></i>
                            </button>
                        </div>

                    </div>
                </div>

            </div>

        </div>

    </div>

    <div class="box box-primary">

        <div class="box-body">

            <table id="matches" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Ora</th>
                        <th>Giornata</th>
                        <th>Difficoltà</th>
                        <th>Competizione</th>
                        <th>Casa</th>
                        <th>Trasferta</th>
                        <th>Arbitro</th>
                        <th>Risultato</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>

    </div>

</section>

<div class="modal fade" id="matchModal" tabindex="-1" role="dialog">

    <div class="modal-dialog" role="document">

        <div class="modal-content">

            <form id="matchForm" novalidate>

                <div class="modal-header bg-primary">

                    <button type="button"
                            class="close"
                            id="closeMatchModal"
                            data-dismiss="modal"
                            aria-label="Chiudi">
                        <span aria-hidden="true">&times;</span>
                    </button>

                    <h4 class="modal-title">
                        <i class="fa fa-futbol-o"></i>
                        Gestione Partita
                    </h4>

                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-xs-12">

                            <div class="box box-primary">

                                <div class="box-header with-border">
                                    <h3 class="box-title">
                                        Dati partita
                                    </h3>
                                </div>

                                <div class="box-body match-box-body">

                                    <div class="row">

                                        <div class="col-md-3">
                                            <div id="wrapCompetition" class="form-group">
                                                <label>Competizione <span class="required">*</span></label>
                                                <select id="competition_id" class="form-control"></select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div id="wrapMatchDay" class="form-group">
                                                <label>Giornata <span class="required">*</span></label>
                                                <input type="number" id="match_day" class="form-control" min="1" step="1" placeholder="1" required>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div id="wrapDifficultyRating" class="form-group">
                                                <label>Difficoltà</label>
                                                <select id="difficulty_rating" class="form-control">
                                                    <option value="1">1 - Bassa</option>
                                                    <option value="2">2</option>
                                                    <option value="3">3 - Media</option>
                                                    <option value="4">4</option>
                                                    <option value="5">5 - Alta</option>
                                                </select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div id="wrapHomeTeam" class="form-group">
                                                <label>Squadra casa <span class="required">*</span></label>
                                                <select id="home_team_id" class="form-control"></select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div id="wrapAwayTeam" class="form-group">
                                                <label>Squadra trasferta <span class="required">*</span></label>
                                                <select id="away_team_id" class="form-control"></select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                    </div>

                                    <br>

                                    <div class="row">

                                        <div class="col-md-3">
                                            <div id="wrapMatchDate" class="form-group">
                                                <label>Data <span class="required">*</span></label>
                                                <input type="date" id="match_date" class="form-control">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div id="wrapMatchTime" class="form-group">
                                                <label>Ora</label>
                                                <input type="time" id="match_time" class="form-control">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div id="wrapStatus" class="form-group">
                                                <label>Stato</label>
                                                <select id="status" class="form-control">
                                                    <option value="scheduled">Programmata</option>
                                                    <option value="played">Giocata</option>
                                                    <option value="cancelled">Annullata</option>
                                                </select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div id="wrapResultType" class="form-group">
                                                <label>Esito</label>
                                                <select id="result_type" class="form-control">
                                                    <option value="played">Regolare</option>
                                                    <option value="walkover_home">Tavolino casa</option>
                                                    <option value="walkover_away">Tavolino trasferta</option>
                                                </select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                    </div>

                                    <br>

                                    <div class="row">

                                        <div class="col-md-3">
                                            <div id="wrapHomeGoals" class="form-group">
                                                <label>Reti casa</label>
                                                <input type="number" id="home_goals" class="form-control" min="0" step="1" placeholder="0">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div id="wrapAwayGoals" class="form-group">
                                                <label>Reti trasferta</label>
                                                <input type="number" id="away_goals" class="form-control" min="0" step="1" placeholder="0">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div id="wrapReferee" class="form-group">
                                                <label>Arbitro</label>
                                                <select id="referee_id" class="form-control"></select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div id="wrapField" class="form-group">
                                                <label>Stadio</label>
                                                <select id="field_id" class="form-control"></select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                    </div>

                                    <br>

                                    <div class="row">

                                        <div class="col-md-6">
                                            <div id="wrapNotes" class="form-group">
                                                <label>Note</label>
                                                <input type="text" id="notes" class="form-control" placeholder="Note">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div id="wrapWalkoverReason" class="form-group">
                                                <label>Motivo tavolino</label>
                                                <input type="text" id="walkover_reason" class="form-control" placeholder="Es. squadra assente">
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
                            id="submitMatch"
                            class="btn btn-primary">
                        Salva
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>

<script src="js/matches.js"></script>

<?php
    include '../admin/includes/footer.php';
?>
