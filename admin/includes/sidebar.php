  <!-- Left side column. contains the logo and sidebar -->
  <aside class="main-sidebar">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">

      <!-- Sidebar Menu -->
      <div class="user-panel">
        <div class="pull-left image">
          <span class="sidebar-logo-circle">
            <img src="../admin/images/logo.png" class="sidebar-logo-img" alt="ASDACA">
          </span>
        </div>
        <div class="pull-left info">
          <p id="sidebarUserName">Utente</p>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <ul class="sidebar-menu" data-widget="tree">
        <li class="header">GESTIONE</li>

        <li id="menu-dashboard" class="icrm-nav-link" data-permission="dashboard.view">
          <a href="dashboard"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a>
        </li>

        <li id="menu-sport" class="treeview icrm-nav-link">
          <a href="#">
            <i class="fa fa-futbol-o"></i>
            <span>Sport</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li id="menu-partite" data-permission="matches.view">
              <a href="matches"><i class="fa fa-futbol-o"></i> <span>Partite</span></a>
            </li>
            <li id="menu-designazioni" data-permission="designations.view">
              <a href="designations"><i class="fa fa-random"></i> <span>Designazioni</span></a>
            </li>
            <li id="menu-competizioni" data-permission="competitions.view">
              <a href="competitions"><i class="fa fa-trophy"></i> <span>Competizioni</span></a>
            </li>
            <li id="menu-squadre" data-permission="teams.view">
              <a href="teams"><i class="fa fa-users"></i> <span>Squadre</span></a>
            </li>
            <li id="menu-arbitri" data-permission="referees.view">
              <a href="referees"><i class="fa fa-id-card"></i> <span>Arbitri</span></a>
            </li>
            <li id="menu-campi" data-permission="fields.view">
              <a href="fields"><i class="fa fa-map-marker"></i> <span>Stadi</span></a>
            </li>
          </ul>
        </li>

        <li id="menu-segreteria" class="treeview icrm-nav-link">
          <a href="#">
            <i class="fa fa-briefcase"></i>
            <span>Segreteria</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li id="menu-transazioni" data-permission="movements.view">
              <a href="movements"><i class="fa fa-book"></i> <span>Prima Nota</span></a>
            </li>
            <li id="menu-bilancio" data-permission="balance.view">
              <a href="balance"><i class="fa fa-balance-scale"></i> <span>Bilancio</span></a>
            </li>
          </ul>
        </li>

        <li id="menu-configurazione" class="treeview icrm-nav-link">
          <a href="#">
            <i class="fa fa-cog"></i>
            <span>Configurazione</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li id="menu-stagioni" data-permission="seasons.view">
              <a href="seasons"><i class="fa fa-calendar"></i> <span>Stagioni</span></a>
            </li>
            <li id="menu-import" data-permission="import.view">
              <a href="import"><i class="fa fa-exchange"></i> <span>Sincronizzazione</span></a>
            </li>
          </ul>
        </li>

        <li id="menu-sicurezza" class="treeview icrm-nav-link">
          <a href="#">
            <i class="fa fa-lock"></i>
            <span>Sicurezza</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li id="menu-utenti" data-permission="users.view">
              <a href="users"><i class="fa fa-user-circle"></i> <span>Utenti</span></a>
            </li>
            <li id="menu-permessi" data-permission="permissions.view">
              <a href="permissions"><i class="fa fa-key"></i> <span>Permessi</span></a>
            </li>
          </ul>
        </li>
      </ul>
      <!-- /.sidebar-menu -->
    </section>
    <!-- /.sidebar -->
  </aside>
