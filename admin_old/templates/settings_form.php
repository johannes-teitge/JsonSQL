<h2 class="h3 mb-4">Einstellungen</h2>

<?php if (isset($msg)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Formular für Lokalmode, Passwortänderung und Datenbankverzeichnis -->
<form method="post" class="w-50">
    <!-- Lokalmode -->
    <div class="mb-3">
        <label for="localmode" class="form-label">Lokalmode aktivieren</label>
        <input type="checkbox" name="localmode" id="localmode" class="form-check-input" <?= isset($settings['localmode']) && $settings['localmode'] ? 'checked' : '' ?>>
        <p class="text-sm text-gray-600 mt-1">Aktiviere den lokalen Modus für Entwicklungsumgebungen.</p>
    </div>

    <!-- Passwortänderung -->
    <div class="mb-3">
        <label for="new_password" class="form-label">Neues Passwort (optional)</label>
        <input type="password" name="new_password" id="new_password" class="form-control">
        <p class="text-sm text-gray-600 mt-1">Ändere das Passwort für den Zugang.</p>
    </div>

    <!-- Datenbankverzeichnis -->
    <div class="mb-3">
        <label for="database_path" class="form-label">Datenbankverzeichnis</label>
        <input type="text" name="database_path" id="database_path" class="form-control" value="<?= htmlspecialchars($settings['database_path']) ?>" required>
        <p class="text-sm text-gray-600 mt-1">Gib den Pfad zum Verzeichnis ein, in dem deine Datenbanken gespeichert sind.</p>
    </div>

    <!-- Speichern Button -->
    <button type="submit" class="btn btn-primary">Speichern</button>
</form>


<?php
// Falls der Benutzer auf den Button klickt, löschen wir die Cookies
if (isset($_POST['clear_cookies'])) {
    // Setze die Cookies auf ein Ablaufdatum in der Vergangenheit, um sie zu löschen
    setcookie('remember_me', '', time() - 3600, '/'); // Löscht das 'remember_me'-Cookie
    // Optional: Weitere Cookies hier löschen, falls du noch andere verwendest
    session_destroy(); // Zerstöre die Session, falls nötig
    header("Location: {$_SERVER['PHP_SELF']}"); // Lade die Seite neu
    exit; // Stoppe den weiteren Code
}
?>

<!-- Button zum Löschen der Cookies -->
<form method="post">
    <button type="submit" name="clear_cookies" class="btn btn-danger">
        Cookies löschen (Logout)
    </button>
</form>

