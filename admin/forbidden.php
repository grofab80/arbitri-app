<?php
    include '../admin/includes/topbar.php';
?>

<section class="content container-fluid">

    <div class="error-page asdaca-forbidden-page">
        <h2 class="headline text-yellow">403</h2>

        <div class="error-content">
            <h3>
                <i class="fa fa-warning text-yellow"></i>
                Accesso non autorizzato
            </h3>

            <p>
                Il tuo profilo non dispone dei permessi necessari per accedere a questa pagina.
            </p>

            <a href="dashboard" class="btn btn-primary btn-flat" data-permission="dashboard.view">
                <i class="fa fa-dashboard"></i>
                Vai alla dashboard
            </a>
        </div>
    </div>

</section>

<?php
    include '../admin/includes/footer.php';
?>
