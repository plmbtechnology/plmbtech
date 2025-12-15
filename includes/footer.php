  <!-- /.content-wrapper -->

  <!-- Main Footer -->
  <footer class="main-footer">
    <!-- To the right -->
    <div class="float-right d-none d-sm-inline">
      Version 1.0
    </div>
    <!-- Default to the left -->
    <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="../index.php">PLMB Technologie</a>.</strong> Tous droits réservés.
  </footer>
</div>
<!-- ./wrapper -->
<!-- REQUIRED SCRIPTS -->

<!-- jQuery (CDN) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<!-- Bootstrap 4 Bundle JS (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- AdminLTE JS (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<!-- Scripts personnalisés -->
<script src="../Assets/js/admin.js"></script>


<!-- Messages de notification -->
<?php if(isset($_SESSION['success'])): ?>
<script>
$(document).ready(function() {
    var toast = $('<div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">' +
        '<div class="toast-header">' +
        '<i class="fas fa-check-circle text-success mr-2"></i>' +
        '<strong class="mr-auto">Succès</strong>' +
        '<button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">' +
        '<span aria-hidden="true">&times;</span>' +
        '</button>' +
        '</div>' +
        '<div class="toast-body"><?php echo addslashes($_SESSION['success']); ?></div>' +
        '</div>');
    $('.wrapper').append(toast);
    toast.toast('show');
    setTimeout(function() {
        toast.toast('hide');
    }, 5000);
});
</script>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
<script>
$(document).ready(function() {
    var toast = $('<div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">' +
        '<div class="toast-header">' +
        '<i class="fas fa-exclamation-circle text-danger mr-2"></i>' +
        '<strong class="mr-auto">Erreur</strong>' +
        '<button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">' +
        '<span aria-hidden="true">&times;</span>' +
        '</button>' +
        '</div>' +
        '<div class="toast-body"><?php echo addslashes($_SESSION['error']); ?></div>' +
        '</div>');
    $('.wrapper').append(toast);
    toast.toast('show');
    setTimeout(function() {
        toast.toast('hide');
    }, 5000);
});
</script>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

</body>
</html>