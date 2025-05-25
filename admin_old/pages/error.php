<?php

// Wenn keine Fehler übergeben wurden, leiten wir auf die Hauptseite weiter
if (!isset($_SESSION['error_code']) || !isset($_SESSION['error_message'])) {
 //   header("Location: /JsonSQL/admin/");
 //   exit;
}

$error_code = $_SESSION['error_code'];
$error_message = $_SESSION['error_message'];

// Fehler aus der Session löschen, damit sie nicht erneut angezeigt wird
// unset($_SESSION['error_code']);
// unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fehler - JsonSQL Admin</title>
    <link rel="stylesheet" href="/JsonSQL/admin/includes/assets/css/bootstrap/bootstrap.min.css">
</head>
<body class="bg-light text-dark">
<div class="container mt-5">
    <div class="alert_ alert-danger align-items-center justify-content-center text-center">


        <img class="nav-logo" src="includes/assets/images/nav_team.webp" alt="">' 

        <h3 style="font-size: 70px;margin-bottom:60px;">Fehler <?= htmlspecialchars($error_code) ?>:</h3>
        <p><?= html_entity_decode($error_message) ?></p>
        <a href="/JsonSQL/admin" class="btn btn-primary">Zurück zur Startseite</a>
    </div>
</div>
</body>
</html>
