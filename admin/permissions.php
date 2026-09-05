<?php
    include '../admin/includes/topbar.php';
?>

<section class="content container-fluid">

    <div class="row" id="permissions-header" style="margin-bottom:15px;">

        <div class="col-md-12 header-item">
            <h3 style="margin:0; line-height:34px;">
                Permessi
            </h3>
        </div>

    </div>

    <div class="row">

        <div class="col-md-3">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Profili</h3>
                </div>

                <div class="box-body no-padding">
                    <div class="list-group permissions-profile-list" id="profilesList"></div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title" id="permissionsTitle">Permessi profilo</h3>
                </div>

                <div class="box-body">
                    <div id="permissionsNotice" class="alert alert-info" style="display:none;"></div>

                    <div id="permissionsMatrix"></div>
                </div>

                <div class="box-footer text-right">
                    <button type="button"
                            id="savePermissions"
                            class="btn btn-primary btn-sm"
                            data-permission="permissions.manage">
                        <i class="fa fa-save"></i>
                        Salva Permessi
                    </button>
                </div>
            </div>
        </div>

    </div>

</section>

<script src="js/permissions.js"></script>

<?php
    include '../admin/includes/footer.php';
?>
