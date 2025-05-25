<?php
function load_tables($database_path) {
    // Alle JSON-Dateien im Ordner holen, außer `.system.json`
    $tables = glob($database_path . "*.json");

    $tableList = [];
    foreach ($tables as $table) {
        $tableName = basename($table);

        // `.system.json` Dateien auslassen
        if (strpos($tableName, '.system.json') === false) {
            $tableList[] = $tableName;
        }
    }

    // Tabellen alphabetisch sortieren
    sort($tableList, SORT_NATURAL | SORT_FLAG_CASE);

    return $tableList;
}
