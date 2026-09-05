<?php
    include '../admin/includes/topbar.php';
?>

<section class="content container-fluid">

    <div class="row" id="movements-header" style="margin-bottom:15px;">

        <div class="col-md-1 header-item">
            <h3 style="margin:0; line-height:34px;">
                Movimenti
            </h3>
        </div>

        <div class="col-md-11 header-item header-actions">
            <button id="openModal" class="btn btn-primary btn-sm" data-permission="movements.create">
                <i class="fa fa-plus"></i>
                Nuovo Movimento
            </button>
        </div>

    </div>

    <div class="row" id="movements-kpi">

        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="kpi-income">0,00</h3>
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
                    <h3 id="kpi-expenses">0,00</h3>
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
                    <h3 id="kpi-profit">0,00</h3>
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
                    <h3 id="kpi-total">0</h3>
                    <p>Movimenti</p>
                </div>
                <div class="icon">
                    <i class="fa fa-book"></i>
                </div>
            </div>
        </div>

    </div>
    <div class="box box-primary">

        <div class="box-body">

            <table id="movements" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Categoria</th>
                        <th>Sottocategoria</th>
                        <th>Dettaglio</th>
                        <th>Descrizione</th>
                        <th>Importo</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>

    </div>

</section>

<div class="modal fade" id="modal" tabindex="-1" role="dialog">

    <div class="modal-dialog" role="document">

        <div class="modal-content">

            <form id="movementForm" novalidate>

                <div class="modal-header bg-primary">

                    <button type="button"
                            class="close"
                            id="closeModal"
                            data-dismiss="modal"
                            aria-label="Chiudi">
                        <span aria-hidden="true">&times;</span>
                    </button>

                    <h4 class="modal-title">
                        <i class="fa fa-exchange"></i>
                        Gestione Movimento
                    </h4>

                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-xs-12">

                            <div class="box box-primary">

                                <div class="box-header with-border">
                                    <h3 class="box-title">
                                        Classificazione
                                    </h3>
                                </div>

                                <div class="box-body movement-box-body">

                                    <div class="row">

                                        <div class="col-md-3">
                                            <div id="wrapCat1" class="form-group">
                                                <label>Tipo <span class="required">*</span></label>
                                                <select id="cat1" class="form-control"></select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div id="wrapCat2" class="form-group">
                                                <label>Categoria <span class="required">*</span></label>
                                                <select id="cat2" class="form-control" disabled></select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div id="wrapCat3" class="form-group">
                                                <label>Sottocategoria <span class="required">*</span></label>
                                                <select id="cat3" class="form-control" disabled></select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div id="wrapCat4" class="form-group">
                                                <label>Dettaglio <span class="required">*</span></label>
                                                <select id="cat4" class="form-control" disabled></select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="row">

                        <div class="col-xs-12">

                            <div class="box box-primary">

                                <div class="box-header with-border">
                                    <h3 class="box-title">
                                        Dettagli collegati
                                    </h3>
                                </div>

                                <div class="box-body movement-box-body">

                                    <div class="row">

                                        <div class="col-md-4">
                                            <div id="wrapCompetition" class="form-group">
                                                <label>Competizione <span class="required">*</span></label>
                                                <select id="competition_id" class="form-control"></select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div id="wrapTeam" class="form-group">
                                                <label>Squadra <span class="required">*</span></label>
                                                <select id="team_id" class="form-control"></select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div id="wrapReferee" class="form-group">
                                                <label>Arbitro <span class="required">*</span></label>
                                                <select id="referee_id" class="form-control"></select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="row">

                        <div class="col-xs-12">

                            <div class="box box-primary">

                                <div class="box-header with-border">
                                    <h3 class="box-title">
                                        Dati movimento
                                    </h3>
                                </div>

                                <div class="box-body movement-box-body">

                                    <div class="row">

                                        <div class="col-md-3">
                                            <div id="wrapDate" class="form-group">
                                                <label>Data <span class="required">*</span></label>
                                                <input type="date" id="movement_date" class="form-control">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div id="wrapAmount" class="form-group">
                                                <label>Importo <span class="required">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-eur"></i>
                                                    </span>
                                                    <input type="number"
                                                           step="0.01"
                                                           id="amount"
                                                           class="form-control"
                                                           placeholder="Importo">
                                                </div>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div id="wrapDescription" class="form-group">
                                                <label>Descrizione</label>
                                                <input type="text"
                                                       id="description"
                                                       class="form-control"
                                                       placeholder="Descrizione">
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
                            id="submitMovement"
                            class="btn btn-primary">
                        Salva
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>

<script src="js/movements.js"></script>

<?php
    include '../admin/includes/footer.php';
?>
