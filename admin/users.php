<?php
    include '../admin/includes/topbar.php';
?>

<section class="content container-fluid">

    <div class="row" id="users-header" style="margin-bottom:15px;">

        <div class="col-md-6 header-item">
            <h3 style="margin:0; line-height:34px;">
                Utenti
            </h3>
        </div>

        <div class="col-md-6 header-item header-actions">
            <button id="openUserModal" class="btn btn-primary btn-sm" data-permission="users.create">
                <i class="fa fa-plus"></i>
                Nuovo Utente
            </button>
        </div>

    </div>

    <div class="box box-primary">

        <div class="box-body">

            <table id="users" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Nome</th>
                        <th>Cognome</th>
                        <th>Email</th>
                        <th>Profilo</th>
                        <th>Creato il</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>

    </div>

</section>

<div class="modal fade" id="userModal" tabindex="-1" role="dialog">

    <div class="modal-dialog" role="document">

        <div class="modal-content">

            <form id="userForm" novalidate>

                <div class="modal-header bg-primary">

                    <button type="button"
                            class="close"
                            id="closeUserProfileModal"
                            data-dismiss="modal"
                            aria-label="Chiudi">
                        <span aria-hidden="true">&times;</span>
                    </button>

                    <h4 class="modal-title">
                        <i class="fa fa-user-circle"></i>
                        Gestione Utente
                    </h4>

                </div>

                <div class="modal-body">

                    <div class="box box-primary">

                        <div class="box-header with-border">
                            <h3 class="box-title" id="userModalTitle">Utente</h3>
                        </div>

                        <div class="box-body user-box-body">

                            <div class="row">
                                <div class="col-md-6">
                                    <div id="wrapUsername" class="form-group">
                                        <label>Username <span class="required">*</span></label>
                                        <input type="text"
                                               id="username"
                                               class="form-control"
                                               placeholder="Username">
                                        <div class="error-msg"></div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div id="wrapEmail" class="form-group">
                                        <label>Email</label>
                                        <input type="email"
                                               id="email"
                                               class="form-control"
                                               placeholder="Email">
                                        <div class="error-msg"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div id="wrapFirstName" class="form-group">
                                        <label>Nome <span class="required">*</span></label>
                                        <input type="text"
                                               id="first_name"
                                               class="form-control"
                                               placeholder="Nome">
                                        <div class="error-msg"></div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div id="wrapLastName" class="form-group">
                                        <label>Cognome <span class="required">*</span></label>
                                        <input type="text"
                                               id="last_name"
                                               class="form-control"
                                               placeholder="Cognome">
                                        <div class="error-msg"></div>
                                    </div>
                                </div>
                            </div>

                            <div id="wrapProfile" class="form-group">
                                <label>Profilo <span class="required">*</span></label>
                                <select id="profile_id" class="form-control"></select>
                                <div class="error-msg"></div>
                            </div>

                            <div id="wrapPassword" class="form-group">
                                <label>Password <span id="passwordRequired" class="required">*</span></label>
                                <input type="password"
                                       id="password"
                                       class="form-control"
                                       autocomplete="new-password"
                                       placeholder="Password">
                                <div class="help-block" id="passwordHelp">
                                    In modifica lascia vuoto per non cambiarla.
                                </div>
                                <div class="error-msg"></div>
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
                            id="submitUserProfile"
                            class="btn btn-primary">
                        Salva
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>

<script src="js/users.js"></script>

<?php
    include '../admin/includes/footer.php';
?>
