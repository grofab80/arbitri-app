<?php
    include '../admin/includes/topbar.php';
?>

<section class="content container-fluid">

    <div class="row" id="teams-header" style="margin-bottom:15px;">

        <div class="col-md-1 header-item">
            <h3 style="margin:0; line-height:34px;">
                Squadre
            </h3>
        </div>

        <div class="col-md-11 header-item header-actions">
            <button id="openTeamModal" class="btn btn-primary btn-sm" data-permission="teams.create">
                <i class="fa fa-plus"></i>
                Nuova Squadra
            </button>
        </div>

    </div>

    <div class="box box-primary">

        <div class="box-body">

            <table id="teams" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Stadio</th>
                        <th>Competizioni</th>
                        <th>Creata il</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>

    </div>

</section>

<div class="modal fade" id="teamModal" tabindex="-1" role="dialog">

    <div class="modal-dialog" role="document">

        <div class="modal-content">

            <form id="teamForm" novalidate>

                <div class="modal-header bg-primary">

                    <button type="button"
                            class="close"
                            id="closeTeamModal"
                            data-dismiss="modal"
                            aria-label="Chiudi">
                        <span aria-hidden="true">&times;</span>
                    </button>

                    <h4 class="modal-title">
                        <i class="fa fa-users"></i>
                        Gestione Squadra
                    </h4>

                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-xs-12">

                            <div class="box box-primary">

                                <div class="box-header with-border">
                                    <h3 class="box-title">
                                        Dati squadra
                                    </h3>
                                </div>

                                <div class="box-body team-box-body">

                                    <div class="row">

                                        <div class="col-md-6">
                                            <div id="wrapName" class="form-group">
                                                <label>Nome <span class="required">*</span></label>
                                                <input type="text"
                                                       id="team_name"
                                                       class="form-control"
                                                       placeholder="Nome squadra">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
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
                                            <div id="wrapCompetitions" class="form-group">
                                                <label>Competizioni <span class="required">*</span></label>
                                                <select id="competition_ids"
                                                        class="form-control"
                                                        multiple
                                                        size="6"></select>
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
                            id="submitTeam"
                            class="btn btn-primary">
                        Salva
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>

<script src="js/teams.js"></script>

<?php
    include '../admin/includes/footer.php';
?>
