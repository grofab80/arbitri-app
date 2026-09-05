<?php
    include '../admin/includes/topbar.php';
?>

<section class="content container-fluid">

    <div class="row" id="fields-header" style="margin-bottom:15px;">

        <div class="col-md-1 header-item">
            <h3 style="margin:0; line-height:34px;">
                Stadi
            </h3>
        </div>

        <div class="col-md-11 header-item header-actions">
            <button id="openFieldModal" class="btn btn-primary btn-sm" data-permission="fields.create">
                <i class="fa fa-plus"></i>
                Nuovo Stadio
            </button>
        </div>

    </div>

    <div class="box box-primary">

        <div class="box-body">

            <table id="fields" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Indirizzo</th>
                        <th>Citta</th>
                        <th>Provincia</th>
                        <th>Calcio a 11</th>
                        <th>Calcio a 7</th>
                        <th>Calcio a 5</th>
                        <th>Attivo</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>

    </div>

</section>

<div class="modal fade" id="fieldModal" tabindex="-1" role="dialog">

    <div class="modal-dialog" role="document">

        <div class="modal-content">

            <form id="fieldForm" novalidate>

                <div class="modal-header bg-primary">

                    <button type="button"
                            class="close"
                            id="closeFieldModal"
                            data-dismiss="modal"
                            aria-label="Chiudi">
                        <span aria-hidden="true">&times;</span>
                    </button>

                    <h4 class="modal-title">
                        <i class="fa fa-map-marker"></i>
                        Gestione Stadio
                    </h4>

                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-xs-12">

                            <div class="box box-primary">

                                <div class="box-header with-border">
                                    <h3 class="box-title">
                                        Dati stadio
                                    </h3>
                                </div>

                                <div class="box-body field-box-body">

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div id="wrapName" class="form-group">
                                                <label>Nome <span class="required">*</span></label>
                                                <input type="text"
                                                       id="field_name"
                                                       class="form-control"
                                                       placeholder="Nome stadio">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <br>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div id="wrapAddress" class="form-group">
                                                <label>Indirizzo</label>
                                                <input type="text"
                                                       id="field_address"
                                                       class="form-control"
                                                       placeholder="Indirizzo">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <br>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div id="wrapCity" class="form-group">
                                                <label>Citta</label>
                                                <input type="text"
                                                       id="field_city"
                                                       class="form-control"
                                                       placeholder="Citta">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div id="wrapProvince" class="form-group">
                                                <label>Provincia</label>
                                                <input type="text"
                                                       id="field_province"
                                                       class="form-control"
                                                       placeholder="Provincia">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <br>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div id="wrapPostalCode" class="form-group">
                                                <label>CAP</label>
                                                <input type="text"
                                                       id="field_postal_code"
                                                       class="form-control"
                                                       placeholder="CAP">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div id="wrapCountry" class="form-group">
                                                <label>Nazione</label>
                                                <input type="text"
                                                       id="field_country"
                                                       class="form-control"
                                                       placeholder="Nazione">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <br>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div id="wrapStatus" class="form-group">
                                                <label>Stato</label>
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" id="is_active" checked>
                                                        Stadio attivo
                                                    </label>
                                                </div>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">

                                        <div class="col-md-12">
                                            <button type="button"
                                                    id="geocodeField"
                                                    class="btn btn-default btn-sm"
                                                    data-permission="fields.edit">
                                                <i class="fa fa-map-marker"></i>
                                                Verifica indirizzo
                                            </button>

                                            <span id="fieldGeocodeResult"
                                                  class="text-muted field-geocode-result">
                                                Coordinate non verificate
                                            </span>
                                        </div>

                                    </div>

                                    <br>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div id="wrapFootballTypes" class="form-group">
                                                <label>Tipologie ospitabili</label>
                                                <div class="field-checkbox-row">
                                                    <label class="checkbox-inline">
                                                        <input type="checkbox" id="can_host_11" checked>
                                                        Calcio a 11
                                                    </label>
                                                    <label class="checkbox-inline">
                                                        <input type="checkbox" id="can_host_7" checked>
                                                        Calcio a 7
                                                    </label>
                                                    <label class="checkbox-inline">
                                                        <input type="checkbox" id="can_host_5" checked>
                                                        Calcio a 5
                                                    </label>
                                                </div>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <br>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div id="wrapNotes" class="form-group">
                                                <label>Note</label>
                                                <input type="text"
                                                       id="field_notes"
                                                       class="form-control"
                                                       placeholder="Note">
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
                            id="submitField"
                            class="btn btn-primary">
                        Salva
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>

<script src="js/fields.js"></script>

<?php
    include '../admin/includes/footer.php';
?>
