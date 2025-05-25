<?php

// Passwortvorgabe
$default_password = 'password';

$settings = get_settings(); // Funktion zum Abrufen der Einstellungen

// $debugger->dump($settings);

$password_from_settings = $settings['password'] ?? '';


//echo 'Hash: ' . $password_from_settings;



$is_hashed = !empty($password_from_settings);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_password = $_POST['password'] ?? '';

    if ($is_hashed) {
        // Passwort aus Settings prüfen (gehashter Vergleich)
        if (password_verify($input_password, $password_from_settings)) {
            $_SESSION['authenticated'] = true;
        } else {
            $error_message = 'Falsches Passwort.';
        }
    } else {
        // Fallback-Vergleich mit Default-Passwort (Klartext)
        if ($input_password === $default_password) {
            $_SESSION['authenticated'] = true;
        } else {
            $error_message = 'Falsches Passwort.';
        }
    }

    if (!empty($_SESSION['authenticated'])) {
        if (isset($_POST['remember_me']) && $_POST['remember_me'] === 'on') {
            setcookie('remember_me', 'true', time() + (86400 * 7), "/");
        }

        // Redirect
        $redirect_url = $_GET['redirect'] ?? 'dashboard';
        header("Location: ?page=" . urlencode($redirect_url));
        exit;
    }
}

?>

    <div class="login-card text-center">
        <?php
        // Template für die Anzeige laden
        include 'templates/login_form.php';
        ?>
    </div>

