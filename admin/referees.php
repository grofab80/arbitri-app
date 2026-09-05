<?php
    include '../admin/includes/topbar.php';
?>

<section class="content container-fluid">

    <div class="row" id="referees-header" style="margin-bottom:15px;">

        <div class="col-md-1 header-item">
            <h3 style="margin:0; line-height:34px;">
                Arbitri
            </h3>
        </div>

        <div class="col-md-11 header-item header-actions">
            <button id="openRefereeModal" class="btn btn-primary btn-sm" data-permission="referees.create">
                <i class="fa fa-plus"></i>
                Nuovo Arbitro
            </button>
        </div>

    </div>

    <div class="box box-primary">

        <div class="box-body">

            <table id="referees" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Residenza</th>
                        <th>Rating</th>
                        <th>Calcio a 11</th>
                        <th>Calcio a 7</th>
                        <th>Calcio a 5</th>
                        <th>Creato il</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>

    </div>

</section>

<div class="modal fade" id="refereeModal" tabindex="-1" role="dialog">

    <div class="modal-dialog" role="document">

        <div class="modal-content">

            <form id="refereeForm" novalidate>

                <div class="modal-header bg-primary">

                    <button type="button"
                            class="close"
                            id="closeRefereeModal"
                            data-dismiss="modal"
                            aria-label="Chiudi">
                        <span aria-hidden="true">&times;</span>
                    </button>

                    <h4 class="modal-title">
                        <i class="fa fa-id-card"></i>
                        Gestione Arbitro
                    </h4>

                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-xs-12">

                            <div class="box box-primary">

                                <div class="box-header with-border">
                                    <h3 class="box-title">
                                        Dati arbitro
                                    </h3>
                                </div>

                                <div class="box-body referee-box-body">

                                    <div class="row">

                                        <div class="col-md-6">
                                            <div id="wrapName" class="form-group">
                                                <label>Nome <span class="required">*</span></label>
                                                <input type="text"
                                                       id="referee_name"
                                                       class="form-control"
                                                       placeholder="Nome arbitro">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div id="wrapRating" class="form-group">
                                                <label>Rating</label>
                                                <select id="rating" class="form-control">
                                                    <option value="1">1 - Base</option>
                                                    <option value="2">2 - Sufficiente</option>
                                                    <option value="3" selected>3 - Buono</option>
                                                    <option value="4">4 - Ottimo</option>
                                                    <option value="5">5 - Eccellente</option>
                                                </select>
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                    </div>

                                    <br>

                                    <div class="row">

                                        <div class="col-md-12">
                                            <div id="wrapAddress" class="form-group">
                                                <label>Indirizzo residenza</label>
                                                <input type="text"
                                                       id="address"
                                                       class="form-control"
                                                       placeholder="Via e numero civico">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                    </div>

                                    <br>

                                    <div class="row">

                                        <div class="col-md-3">
                                            <div id="wrapPostalCode" class="form-group">
                                                <label>CAP</label>
                                                <input type="text"
                                                       id="postal_code"
                                                       class="form-control"
                                                       placeholder="CAP">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div id="wrapCity" class="form-group">
                                                <label>Citta</label>
                                                <input type="text"
                                                       id="city"
                                                       class="form-control"
                                                       placeholder="Citta">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <div id="wrapProvince" class="form-group">
                                                <label>Provincia</label>
                                                <input type="text"
                                                       id="province"
                                                       class="form-control"
                                                       placeholder="TO">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div id="wrapCountry" class="form-group">
                                                <label>Nazione</label>
                                                <input type="text"
                                                       id="country"
                                                       class="form-control"
                                                       placeholder="Italia"
                                                       value="Italia">
                                                <div class="error-msg"></div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row">

                                        <div class="col-md-12">
                                            <button type="button"
                                                    id="geocodeReferee"
                                                    class="btn btn-default btn-sm"
                                                    data-permission="referees.edit">
                                                <i class="fa fa-map-marker"></i>
                                                Verifica indirizzo
                                            </button>

                                            <span id="geocodeResult"
                                                  class="text-muted referee-geocode-result">
                                                Coordinate non verificate
                                            </span>
                                        </div>

                                    </div>

                                    <br>

                                    <div class="row">

                                        <div class="col-md-6">
                                            <div id="wrapFootballTypes" class="form-group">
                                            <label>Abilitazioni</label>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" id="can_referee_11" checked>
                                                    Calcio a 11
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" id="can_referee_7" checked>
                                                    Calcio a 7
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" id="can_referee_5" checked>
                                                    Calcio a 5
                                                </label>
                                            </div>
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
                            id="submitReferee"
                            class="btn btn-primary">
                        Salva
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>

<div class="modal fade" id="availabilityModal" tabindex="-1" role="dialog">

    <div class="modal-dialog modal-lg" role="document">

        <div class="modal-content">

            <div class="modal-header bg-primary">

                <button type="button"
                        class="close"
                        id="closeAvailabilityModal"
                        data-dismiss="modal"
                        aria-label="Chiudi">
                    <span aria-hidden="true">&times;</span>
                </button>

                <h4 class="modal-title">
                    <i class="fa fa-calendar"></i>
                    Disponibilit&agrave; arbitro
                    <small id="availabilityRefereeName" class="text-white"></small>
                </h4>

            </div>

            <div class="modal-body">

                <ul class="nav nav-tabs" role="tablist">
                    <li class="active">
                        <a href="#recurringAvailabilityTab" data-toggle="tab">
                            Ricorrenti
                        </a>
                    </li>
                    <li>
                        <a href="#specificAvailabilityTab" data-toggle="tab">
                            Date specifiche
                        </a>
                    </li>
                </ul>

                <div class="tab-content referee-availability-tabs">

                    <div class="tab-pane active" id="recurringAvailabilityTab">

                        <form id="recurringAvailabilityForm" class="referee-availability-form" novalidate>
                            <input type="hidden" id="recurringAvailabilityId">

                            <div class="row">
                                <div class="col-md-3">
                                    <div id="wrapRecurringWeekday" class="form-group">
                                        <label>Giorno <span class="required">*</span></label>
                                        <select id="recurringWeekday" class="form-control">
                                            <option value="1">Luned&igrave;</option>
                                            <option value="2">Marted&igrave;</option>
                                            <option value="3">Mercoled&igrave;</option>
                                            <option value="4">Gioved&igrave;</option>
                                            <option value="5">Venerd&igrave;</option>
                                            <option value="6">Sabato</option>
                                            <option value="7">Domenica</option>
                                        </select>
                                        <div class="error-msg"></div>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div id="wrapRecurringStartTime" class="form-group">
                                        <label>Dalle <span class="required">*</span></label>
                                        <input type="time" id="recurringStartTime" class="form-control" value="19:00">
                                        <div class="error-msg"></div>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div id="wrapRecurringEndTime" class="form-group">
                                        <label>Alle <span class="required">*</span></label>
                                        <input type="time" id="recurringEndTime" class="form-control" value="23:59">
                                        <div class="error-msg"></div>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Stato</label>
                                        <select id="recurringIsAvailable" class="form-control">
                                            <option value="1">Disponibile</option>
                                            <option value="0">Non disponibile</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div id="wrapRecurringNotes" class="form-group">
                                        <label>Note</label>
                                        <input type="text" id="recurringNotes" class="form-control" maxlength="255">
                                        <div class="error-msg"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right">
                                <button type="button" id="resetRecurringAvailability" class="btn btn-default btn-sm">
                                    Annulla
                                </button>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa fa-save"></i>
                                    Salva disponibilit&agrave;
                                </button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped referee-availability-table">
                                <thead>
                                    <tr>
                                        <th>Giorno</th>
                                        <th>Dalle</th>
                                        <th>Alle</th>
                                        <th>Stato</th>
                                        <th>Note</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody id="recurringAvailabilityRows"></tbody>
                            </table>
                        </div>

                    </div>

                    <div class="tab-pane" id="specificAvailabilityTab">

                        <form id="specificAvailabilityForm" class="referee-availability-form" novalidate>
                            <input type="hidden" id="specificAvailabilityId">

                            <div class="row">
                                <div class="col-md-3">
                                    <div id="wrapSpecificDate" class="form-group">
                                        <label>Data <span class="required">*</span></label>
                                        <input type="date" id="specificDate" class="form-control">
                                        <div class="error-msg"></div>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div id="wrapSpecificStartTime" class="form-group">
                                        <label>Dalle <span class="required">*</span></label>
                                        <input type="time" id="specificStartTime" class="form-control" value="19:00">
                                        <div class="error-msg"></div>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div id="wrapSpecificEndTime" class="form-group">
                                        <label>Alle <span class="required">*</span></label>
                                        <input type="time" id="specificEndTime" class="form-control" value="23:59">
                                        <div class="error-msg"></div>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Stato</label>
                                        <select id="specificIsAvailable" class="form-control">
                                            <option value="1">Disponibile</option>
                                            <option value="0">Non disponibile</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div id="wrapSpecificNotes" class="form-group">
                                        <label>Note</label>
                                        <input type="text" id="specificNotes" class="form-control" maxlength="255">
                                        <div class="error-msg"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right">
                                <button type="button" id="resetSpecificAvailability" class="btn btn-default btn-sm">
                                    Annulla
                                </button>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa fa-save"></i>
                                    Salva disponibilit&agrave;
                                </button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped referee-availability-table">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Dalle</th>
                                        <th>Alle</th>
                                        <th>Stato</th>
                                        <th>Note</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody id="specificAvailabilityRows"></tbody>
                            </table>
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
            </div>

        </div>

    </div>

</div>

<script src="js/referees.js"></script>

<?php
    include '../admin/includes/footer.php';
?>
