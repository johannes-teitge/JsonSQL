<?php

// Session löschen
$_SESSION = [];
session_destroy();

// Cookie löschen
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

// Jetzt kommt die Ausgabe – HTML/JS Redirect
?>
<script>
  window.location.href = 'index.php';
</script>
<noscript>
  <meta http-equiv="refresh" content="0;url=index.php">
</noscript>
