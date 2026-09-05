<?php
    include '../admin/includes/topbar.php';
?>

<section class="content container-fluid">

    <div class="row" id="seasons-header" style="margin-bottom:15px;">

        <div class="col-md-1 header-item">
            <h3 style="margin:0; line-height:34px;">
                Stagioni
            </h3>
        </div>

        <div class="col-md-11 header-item header-actions">
            <button id="openSeasonModal" class="btn btn-primary btn-sm" data-permission="seasons.create">
                <i class="fa fa-plus"></i>
                Nuova Stagione
            </button>
        </div>

    </div>

    <div class="box box-primary">

        <div class="box-body">

            <table id="seasons" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Inizio</th>
                        <th>Fine</th>
                        <th>Stato</th>
                        <th>Creata il</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>

    </div>

</section>

<div class="modal fade" id="seasonModal" tabindex="-1" role="dialog">

    <div class="modal-dialog" role="document">

        <div class="modal-content">

            <form id="seasonForm" novalidate>

                <div class="modal-header bg-primary">

                    <button type="button"
                            class="close"
                            id="closeSeasonModal"
                            data-dismiss="modal"
                            aria-label="Chiudi">
                        <span aria-hidden="true">&times;</span>
                    </button>

                    <h4 class="modal-title">
                        <i class="fa fa-calendar"></i>
                        Gestione Stagione
                    </h4>

                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-xs-12">

                            <div class="box box-primary">

                                <div class="box-header with-border">
                                    <h3 class="box-title">
                                        Dati stagione
                                    </h3>
                                </div>

                                <div class="box-body season-box-body">

                                    <div class="row">

                                        <div class="col-md-4">
                                            <div id="wrapName" class="form-group">
                                                <label>Nome <span class="required">*</span></label>
                                                <input type="text"
                                                       id="season_name"
                                                       class="form-control"
                                                       placeholder="2026/2027">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div id="wrapStartsOn" class="form-group">
                                                <label>Data inizio <span class="required">*</span></label>
                                                <input type="date"
                                                       id="starts_on"
                                                       class="form-control">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div id="wrapEndsOn" class="form-group">
                                                <label>Data fine <span class="required">*</span></label>
                                                <input type="date"
                                                       id="ends_on"
                                                       class="form-control">
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
                            id="submitSeason"
                            class="btn btn-primary">
                        Salva
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>

<script src="js/seasons.js"></script>

<?php
    include '../admin/includes/footer.php';
?>
