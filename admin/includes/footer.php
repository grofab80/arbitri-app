    <!-- /.content -->
    </div>
  <!-- /.content-wrapper -->

  <!-- Main Footer -->
  <footer class="main-footer">
    <!-- To the right -->
    <!-- <div class="pull-right hidden-xs">
      Anything you want
    </div> -->
    <!-- Default to the left -->
    <strong>Copyright &copy; 2025-<?php echo date("Y") ?> <a href="https://www.asdaca.it"> | A.s.d. Associazione Canavesana Arbitri</a>.</strong> Tutti i diritti sono riservati.
  </footer>

</div>
<!-- ./wrapper -->
<script>
document.getElementById("logoutBtn")
?.addEventListener("click", () => Auth.logout());

if (typeof loadCurrentSeasonBadge === "function") {
  loadCurrentSeasonBadge();
}
</script>
<!-- REQUIRED JS SCRIPTS -->
<!-- jQuery 3 -->
<script src="../admin/plugins/jquery/dist/jquery.min.js"></script>

<!-- Bootstrap 3.3.7 -->
<script src="../admin/plugins/bootstrap/dist/js/bootstrap.min.js"></script>

<!-- AdminLTE App -->
<script src="../admin/dist/js/adminlte.min.js"></script>

<!-- Keondo Core -->
<!-- <script src="../plugins/kendo-ui-core/dist/js/kendo.ui.core.min.js"></script> -->

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap.min.js"></script>

<!-- CK Editor -->
<!-- <script src="../plugins/ckeditor/ckeditor.js"></script> -->

<!-- Jquery Toast Plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>

<!-- ChartJS -->
<!-- <script src="../plugins/chart.js/Chart.js"></script> -->

<!-- Custom Scripts -->
<!-- <script src="../js/fn-utils.js"></script> -->

</body>
</html>
