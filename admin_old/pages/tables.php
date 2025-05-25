<?php
require_once __DIR__ . '/../includes/load-settings.php';

$settings = get_settings();
$database_path = $settings['database_path'] ?? '';

if (empty($database_path) || !is_dir($database_path)) {
    echo "Kein gültiger Datenbankordner definiert.";
    exit;
}

// Alle JSON-Dateien im Ordner holen
$tables = [];
$system_files = [];

foreach (glob($database_path . "*.json") as $file) {
    $tables[] = basename($file); // Nur den Dateinamen ohne Pfad
    // Suche nach der zugehörigen system.json-Datei
    $system_file = $database_path . basename($file, '.json') . '.system.json';
    if (file_exists($system_file)) {
        $system_files[basename($file)] = basename($system_file);
    }
}
?>

<h2 class="h3 mb-4">Verfügbare Tabellen und Systemdateien</h2>

<?php if (count($tables) > 0): ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tables as $table): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($table) ?>

                        <!-- Überprüfen, ob eine zugehörige Systemdatei existiert -->
                        <?php if (isset($system_files[$table])): ?>
                            <div class="text-muted small mt-2">
                                <strong>Systemdatei:</strong><br>
                                <a href="view_system.php?file=<?= urlencode($system_files[$table]) ?>" class="btn btn-warning btn-sm">Anzeigen der Systemdatei</a>
                                <span class="ml-2">Diese Systemdatei gehört zur Tabelle: <?= htmlspecialchars($table) ?></span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="view_table.php?table=<?= urlencode($table) ?>" class="btn btn-info btn-sm">Anzeigen</a>
                        <a href="delete_table.php?table=<?= urlencode($table) ?>" class="btn btn-danger btn-sm">Löschen</a>
                    </td>
                </tr>

            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Es wurden keine Tabellen (JSON-Dateien) im angegebenen Ordner gefunden.</p>
<?php endif; ?>
