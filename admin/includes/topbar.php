<!DOCTYPE html>
<head>
<meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>ASDACA</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="../admin/plugins/bootstrap/dist/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../admin/plugins/font-awesome/css/font-awesome.min.css">
  <!-- Ionicons -->
  <!-- <link rel="stylesheet" href="../plugins/Ionicons/css/ionicons.min.css"> -->
  <!-- Jquery Toast Plugin -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../admin/dist/css/AdminLTE.min.css">
  <!-- AdminLTE Skins. Choose a skin from the css/skins
       folder instead of downloading all of them to reduce the load. -->
  <link rel="stylesheet" href="../admin/dist/css/skins/_all-skins.min.css">
  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

  <!-- Kendo style -->
<!--   <link rel="stylesheet" href="../plugins/kendo-ui-core/dist/styles/web/kendo.common.min.css" />
  <link rel="stylesheet" href="../plugins/kendo-ui-core/dist/styles/web/kendo.bootstrap.min.css" /> -->

  <!-- DataTables style -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap.min.css">

  <!-- Custom style -->
  <link rel="stylesheet" href="../admin/css/style.css">

  <script src="../admin/js/auth.js?v=<?php echo filemtime(__DIR__ . '/../js/auth.js'); ?>"></script>

  <script>
    Auth.requireAuth();
  </script>
</head>
<body class="hold-transition skin-blue fixed sidebar-mini ">

  <div class="wrapper">

    <!-- Main Header -->
    <header class="main-header">

      <!-- Logo -->
      <a href="dashboard" class="logo">
        <!-- mini logo for sidebar mini 50x50 pixels -->
        <span class="logo-mini"><b>ACA</b></span>
        <!-- logo for regular state and mobile devices -->
        <span class="logo-lg"><b>ASDACA</b></span>
      </a>

      <!-- Header Navbar -->
      <nav class="navbar navbar-static-top" role="navigation">
        <!-- Sidebar toggle button-->
        <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
          <span class="sr-only">Toggle navigation</span>
        </a>

        <span class="navbar-text season-badge" id="currentSeasonBadge">
          Stagione -
        </span>

        <!-- Navbar Right Menu -->
        <div class="navbar-custom-menu">
          <ul class="nav navbar-nav">
            <li class="dropdown notifications-menu" id="topbarNotificationsMenu">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-label="Notifiche operative">
                <i class="fa fa-bell-o"></i>
                <span class="label label-warning" id="topbarNotificationsBadge" style="display:none;">0</span>
              </a>
              <ul class="dropdown-menu">
                <li class="header" id="topbarNotificationsHeader">Nessuna notifica</li>
                <li>
                  <ul class="menu" id="topbarNotificationsList">
                    <li>
                      <a href="#">
                        <i class="fa fa-check text-green"></i>
                        Nessuna notifica operativa
                      </a>
                    </li>
                  </ul>
                </li>
                <li class="footer">
                  <a href="#" id="markAllNotificationsRead">Segna tutte come lette</a>
                </li>
              </ul>
            </li>
            <li class="dropdown user user-menu">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-user"></i>
                <span class="hidden-xs" id="topbarUserName">Utente</span>
              </a>

              <ul class="dropdown-menu">
                <li class="user-header">
                  <i class="fa fa-user-circle fa-4x"></i>
                  <p>
                    <span id="topbarUserFullName">Utente</span>
                    <small id="topbarUserEmail">ASDACA</small>
                    <small id="topbarUserRole">Profilo -</small>
                  </p>
                </li>

                <li class="user-footer">
                  <div class="pull-right">
                    <button type="button"
                            class="btn btn-default btn-flat"
                            id="logoutBtn">
                      <i class="fa fa-sign-out"></i>
                      Logout
                    </button>
                  </div>
                </li>
              </ul>
            </li>
          </ul>
        </div>

      </nav>
    </header>

    <!-- Left side column. contains the logo and sidebar -->
    <?php include_once 'sidebar.php'; ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
