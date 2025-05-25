<?php
// Sicherstellen, dass der Benutzer eingeloggt ist

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    echo "<div class='alert alert-danger'>Bitte logge dich ein, um Tabellen anzuzeigen.</div>";
    exit;
}

// Überprüfen, ob eine Tabelle über die URL übergeben wurde
if (isset($_GET['table'])) {
    $tableName = $_GET['table'];
    require_once __DIR__ . '/../includes/load-settings.php';
    $settings = get_settings();
    $database_path = $settings['database_path'] ?? '';


    // Überprüfen, ob die Tabelle existiert
    $filePath = $database_path . '/' . $tableName;
    if (!file_exists($filePath)) {
        echo "<div class='alert alert-danger'>Tabelle $tableName nicht gefunden.</div>";
        exit;
    }

    // JSON-Datei der Tabelle laden
    $data = json_decode(file_get_contents($filePath), true);

    if (empty($data)) {
        echo "<div class='alert alert-warning'>Keine Daten in dieser Tabelle vorhanden.</div>";
        exit;
    }

    // Tabelle anzeigen
    echo "<h3>Inhalt der Tabelle: $tableName</h3>";
    echo '<table class="table table-striped">';
    echo '<thead><tr>';

    // Dynamische Spaltenüberschriften basierend auf der ersten Zeile
    foreach (array_keys($data[0]) as $column) {
        echo "<th>$column</th>";
    }
    echo '</tr></thead><tbody>';

    // Zeilen anzeigen
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $value) {
            echo "<td>$value</td>";
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo "<div class='alert alert-danger'>Keine Tabelle angegeben.</div>";
}
?>
