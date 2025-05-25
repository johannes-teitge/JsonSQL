<?php


// Fehlerbericht aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Basis-URL definieren
$baseUrl = dirname($_SERVER['PHP_SELF']);

// Standardpasswort definieren
$default_password = 'meinSicheresPasswort';
$hashed_password = null;

require_once __DIR__ . '/../includes/load-settings.php';
$settings = get_settings();


// Wenn ein Passwort in den Einstellungen gespeichert ist, laden wir den Hash
$settings = get_settings();
if (isset($settings['password']) && !empty($settings['password'])) {
    // Wenn das Passwort gesetzt ist, speichern wir den Hash
    $hashed_password = $settings['password'];
} else {
    // Wenn kein Passwort gesetzt ist, verwenden wir das Standardpasswort
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
}

// Wenn der Benutzer das Passwort eingibt und es korrekt ist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_password = $_POST['password'] ?? '';

    // Überprüfe, ob das eingegebene Passwort mit dem gespeicherten Hash übereinstimmt
    if (password_verify($input_password, $hashed_password)) {
        $_SESSION['authenticated'] = true; // Authentifizierung per Session speichern

        // Wenn der Benutzer die Option "Angemeldet bleiben" gewählt hat
        if (isset($_POST['remember_me']) && $_POST['remember_me'] == 'on') {
            // Cookie für 7 Tage setzen
            setcookie('remember_me', 'true', time() + (86400 * 7), "/"); // 7 Tage lang
        }
    } else {
        $error_message = 'Falsches Passwort.';
    }

    // Wenn die Einstellungen (Lokalmode, Passwort) geändert wurden
    if (isset($_POST['localmode'])) {
        // Ändere den Lokalmode
        $localmode = true;
    }

    // Passwort ändern
    if (isset($_POST['new_password']) && !empty($_POST['new_password'])) {
        // Erstelle den neuen Hash
        $new_password = $_POST['new_password'];
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Speichere den neuen Hash in den Einstellungen
        $settings['password'] = $new_password_hash;
        save_settings($settings);

        $msg = 'Passwort erfolgreich geändert.';
    }

    // Lokalmode ändern
    if (isset($_POST['localmode']) && $_POST['localmode'] == 'on') {
        $settings['localmode'] = true;
        save_settings($settings);
        $msg = 'Einstellungen erfolgreich gespeichert.';
    }
}

// Überprüfen, ob der Benutzer schon authentifiziert ist, entweder über Session oder Cookie
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true || isset($_COOKIE['remember_me']) && $_COOKIE['remember_me'] === 'true') {
    // Wenn der Benutzer authentifiziert ist, gehe zum Inhalt

    // Template für die Anzeige laden
    include 'templates/settings_form.php';
} else {
    // Wenn der Benutzer nicht authentifiziert ist, Passwortabfrage anzeigen
    include 'templates/login_form.php';
    exit; // Stoppe den weiteren Code, wenn der Benutzer nicht authentifiziert ist
}
