</div> <!-- Ende Sidebar + Content Wrapper -->

<?php
  if ($debugger->getStackCount() > 0) {
    $debugger->dumpOut();
  }
?>    

<script src="<?php echo $baseUrl; ?>/includes/assets/js/bootstrap/bootstrap.bundle.min.js?v=<?= time() ?>"></script>
<script src="<?php echo $baseUrl; ?>/includes/assets/js/navbar_resize.js_?v=<?= time() ?>"></script>

</body>
</html>
