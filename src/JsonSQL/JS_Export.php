<?php
namespace Src\JsonSQL;

trait JS_Export
{


    public function exportTable(string $tableName): array {
        $this->setTable($tableName, true);
        $this->loadSystemConfig();
    
        $data = json_decode(file_get_contents($this->currentTableFile), true);
    
        return [
            'table'         => $tableName,
            'folder'        => $this->currentDbPath,
            'filename'      => $this->currentTableFile,          
            'system'        => $this->systemConfig,
            'data'          => $data,
            'count'         => count($data),
            'last_modified' => file_exists($this->currentTableFile)
                ? date('Y-m-d H:i:s', filemtime($this->currentTableFile))
                : null,
        ];
    }
    

    
    public function exportDatabase(): array {
        if (!$this->currentDbPath) {
            throw new \Exception("Keine Datenbank ausgewählt.");
        }
    
        $tables = $this->listTables();
        $export = ['database' => basename($this->currentDbPath), 'tables' => []];
    
        foreach ($tables as $table) {
            if (str_ends_with($table, '.system')) continue; // überspringe *.system.json
    
            $this->setTable($table);
            $this->loadSystemConfig();
    
            $export['tables'][$table] = [
                'system' => $this->systemConfig,
                'data' => json_decode(file_get_contents($this->currentTableFile), true),
            ];
        }
    
        return $export;
    }
    



    public function ExportMySQLCreateAll(): string {
        if (!$this->currentDbPath) {
            throw new \Exception("❌ Es wurde keine Datenbank ausgewählt.");
        }
    
        $tables = [];
        $files = glob($this->currentDbPath . DIRECTORY_SEPARATOR . '*.system.json');
    
        foreach ($files as $file) {
            $basename = basename($file, '.system.json');
            $tables[] = $this->ExportMySQLCreate($basename);
        }
    
        return implode("\n\n", $tables);
    }

    public function ExportMySQLDataAll(int $rowsPerBlock = 5, bool $withTransaction = true): string {
        if (!$this->currentDbPath) {
            throw new \Exception("❌ Es wurde keine Datenbank ausgewählt.");
        }
    
        $output = [];
        $files = glob($this->currentDbPath . DIRECTORY_SEPARATOR . '*.system.json');
    
        foreach ($files as $file) {
            $basename = basename($file, '.system.json');
            $dataFile = $this->getTableFilePath($basename);
            if (!file_exists($dataFile)) continue;
    
            try {
                $output[] = $this->ExportMySQLData($basename, $rowsPerBlock, $withTransaction);
            } catch (\Throwable $e) {
                $output[] = "-- ❌ Fehler beim Export der Tabelle `$basename`: " . $e->getMessage();
            }
        }
    
        return implode("\n\n", $output);
    }

    public function ExportMySQLFullAll(int $rowsPerBlock = 5, bool $withTransaction = true): string {
        if (!$this->currentDbPath) {
            throw new \Exception("❌ Es wurde keine Datenbank ausgewählt.");
        }
    
        $output = [];
        $files = glob($this->currentDbPath . DIRECTORY_SEPARATOR . '*.system.json');
    
        foreach ($files as $file) {
            $basename = basename($file, '.system.json');
            $dataFile = $this->getTableFilePath($basename);
            $hasData = file_exists($dataFile);
    
            try {
                $create = $this->ExportMySQLCreate($basename);
                $data   = $hasData ? $this->ExportMySQLData($basename, $rowsPerBlock, $withTransaction) : '';
    
                $output[] = $create . "\n\n" . $data;
            } catch (\Throwable $e) {
                $output[] = "-- ❌ Fehler beim Export der Tabelle `$basename`: " . $e->getMessage();
            }
        }
    
        return implode("\n\n", $output);
    }
    
    



    private function buildTableOptions(array $options): string {
        $sqlOptions = [];
    
        foreach ($options as $key => $value) {
            $key = strtoupper(str_replace('_', ' ', $key));
    
            if (in_array($key, ['COMMENT', 'PASSWORD', 'CONNECTION', 'COMPRESSION', 'ENCRYPTION', 'ENGINE_ATTRIBUTE', 'SECONDARY_ENGINE_ATTRIBUTE'])) {
                $sqlOptions[] = "$key='" . addslashes($value) . "'";
            } elseif (is_numeric($value) || in_array(strtoupper($value), ['DEFAULT', 'DYNAMIC', 'FIXED', 'COMPRESSED', 'REDUNDANT', 'COMPACT', 'NO', 'FIRST', 'LAST'])) {
                $sqlOptions[] = "$key=$value";
            } elseif (is_bool($value)) {
                $sqlOptions[] = "$key=" . ($value ? '1' : '0');
            } else {
                $sqlOptions[] = "$key=$value";
            }
        }
    
        return implode(' ', $sqlOptions);
    }
    

    private array $defaultTableOptions = [
        'ENGINE' => 'InnoDB',
        'CHARACTER SET' => 'utf8mb4',
    ];    
    

    public function ExportMySQLCreate(string $table, bool $withDropIfExists = true, array $tableOptions = []): string {
        $sysFile = $this->getSystemTableFilePath($table);    
    
        if (!file_exists($sysFile)) {
            throw new \Exception("❌ Systemdefinition für '$table' nicht gefunden.");
        }
    
        $tableOptions = array_merge($this->defaultTableOptions, $tableOptions); // Übergabe-Optionen überschreiben Defaults
        $tableOptsSQL = $this->buildTableOptions($tableOptions);
        
        $definition = json_decode(file_get_contents($sysFile), true);
        $fields = $definition['fields'] ?? [];
    
        $sql = "-- CREATE TABLE für `$table`\n";
        if ($withDropIfExists) {
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        }
    
        $sql .= "CREATE TABLE `$table` (\n";
        $lines = [];
    
        foreach ($fields as $name => $def) {
            $type = strtolower($def['dataType'] ?? 'text');          
            $nullable = ($def['allowNull'] ?? true) ? '' : 'NOT NULL';
            $default = isset($def['default']) ? "DEFAULT '" . addslashes($def['default']) . "'" : '';
            $line = "`$name`";
    
            switch ($type) {
                case 'int':
                case 'integer':
                    $line .= " INT";
                    if (($def['autoincrement'] ?? false) || ($def['auto'] ?? '') === 'autoincrement') {
                        $line .= " AUTO_INCREMENT";
                    }
                    break;
    
                case 'float':
                case 'decimal':
                    $line .= " FLOAT";
                    break;
    
                case 'enum':
                    $enumValues = implode("','", $def['enum'] ?? []);
                    $line .= " ENUM('$enumValues')";
                    break;
    
                case 'text':
                    $line .= " TEXT";
                    break;
    
                case 'date':
                    $line .= " DATE";
                    break;
    
                case 'datetime':
                case 'timestamp':
                    $line .= " DATETIME";
                    break;
    
                default:
                    $len = $def['length'] ?? 255;
                    $line .= " VARCHAR($len)";
                    break;
            }
    
            $line .= " $nullable";
            if ($default && !str_contains($line, 'AUTO_INCREMENT')) {
                $line .= " $default";
            }
    
            $lines[] = trim($line);
        }
    
        if (isset($fields['id'])) {
            $lines[] = "PRIMARY KEY (`id`)";
        }
    
        $sql .= "  " . implode(",\n  ", $lines) . "\n) $tableOptsSQL;";
        return $sql;
    }
    




    public function ExportMySQLData(string $table, int $rowsPerBlock = 5, bool $withTransaction = true): string {
        $sysFile = $this->getSystemTableFilePath($table);
        $dataFile = $this->getTableFilePath($table);
    
        if (!file_exists($sysFile)) {
            throw new \Exception("❌ Systemdefinition für '$table' nicht gefunden.");
        }
        if (!file_exists($dataFile)) {
            throw new \Exception("❌ Datendatei für '$table' nicht gefunden.");
        }
    
        $definition = json_decode(file_get_contents($sysFile), true);
        $fields = $definition['fields'] ?? [];
    
        $rows = json_decode(file_get_contents($dataFile), true);
        if (!is_array($rows)) $rows = [];
    
        $sql = "-- Datenexport für Tabelle: `$table`\n";
        if (empty($rows)) return $sql;
    
        $columns = array_map(fn($name) => "`$name`", array_keys($fields));
        $blockRows = ($rowsPerBlock > 0) ? array_chunk($rows, $rowsPerBlock) : [$rows];
    
        foreach ($blockRows as $i => $block) {
            if ($rowsPerBlock > 0) {
                $sql .= "\n-- Block " . ($i + 1) . "\n";
            }
    
            if ($withTransaction) {
                $sql .= "START TRANSACTION;\n";
            }
    
            foreach ($block as $row) {
                $vals = [];
    
                foreach ($fields as $name => $def) {
                    $value = $row[$name] ?? null;
                    $type = strtolower($def['dataType'] ?? 'text');
    
                    if ($value === null) {
                        $vals[] = "NULL";
                    } else {
                        switch ($type) {
                            case 'int':
                            case 'integer':
                            case 'float':
                            case 'decimal':
                                $vals[] = $value;
                                break;
                            case 'bool':
                            case 'boolean':
                                $vals[] = $value ? '1' : '0';
                                break;
                            case 'json':
                            case 'object':
                            case 'array':
                                $vals[] = "'" . addslashes(json_encode($value)) . "'";
                                break;
                            case 'date':
                            case 'datetime':
                            case 'timestamp':
                            case 'text':
                            default:
                                $vals[] = "'" . addslashes((string)$value) . "'";
                                break;
                        }
                    }
                }
    
                $sql .= "INSERT INTO `$table` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $vals) . ");\n";
            }
    
            if ($withTransaction) {
                $sql .= "COMMIT;\n";
            }
        }
    
        return $sql;
    }
    






}    