<?php
namespace Src\JsonSQL;

trait JS_Database
{


    public function clearDatabase(): int
    {
        if (!$this->currentDbPath || !is_dir($this->currentDbPath)) {
            throw new \Exception("❌ Kein gültiger Datenbankpfad gesetzt.");
        }
    
        $deleted = 0;
    
        foreach (glob($this->currentDbPath . '/*.json') as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }
    
        return $deleted;
    }
    

    /**
     * Gibt Infos zur aktuellen Datenbank zurück (z. B. Tabellen, Größe etc.).
     */
    public function getDatabaseInfo(): array
    {
        if (!$this->currentDbPath || !is_dir($this->currentDbPath)) {
            throw new \Exception("❌ Datenbankpfad ist ungültig oder nicht vorhanden.");
        }

        $files = glob($this->currentDbPath . '/*.json');
        $tables = [];
        $totalSize = 0;

        foreach ($files as $file) {
            $size = filesize($file);
            $tables[] = [
                'name' => basename($file),
                'size' => $size,
                'last_modified' => date("Y-m-d H:i:s", filemtime($file))
            ];
            $totalSize += $size;
        }

        return [
            'path' => $this->currentDbPath,
            'table_count' => count($tables),
            'total_size' => $totalSize,
            'tables' => $tables,
        ];
    }




    public function clear(): void {
        if (!$this->currentDbPath) {
            throw new \Exception("Keine Datenbank ausgewählt.");
        }

        // Lösche alle JSON-Dateien im Verzeichnis
        $files = glob($this->currentDbPath . DIRECTORY_SEPARATOR . '*.json');
        foreach ($files as $file) {
            unlink($file);
        }
    }


    public function use(string $dbAlias): self {
        if (!isset($this->databases[$dbAlias])) {
            throw new \Exception("❌ Datenbankalias '$dbAlias' ist nicht definiert.");
        }
    
        $this->currentDbPath = rtrim($this->databases[$dbAlias], DIRECTORY_SEPARATOR);
        $this->tableLoaded = false;
    
        // 🛠️ Automatisch Verzeichnis anlegen, falls nicht vorhanden
        if (!is_dir($this->currentDbPath)) {
            if (!mkdir($this->currentDbPath, 0777, true)) {
                throw new \Exception("❌ Verzeichnis '{$this->currentDbPath}' konnte nicht erstellt werden.");
            }
        }
    
        return $this;
    }    


    /**
     * Gibt den Pfad zur .system.json-Datei zurück – entweder für eine angegebene Tabelle
     * oder die aktuell gesetzte Tabelle (via setTable()).
     *
     * @param string|null $table Optional: Tabellenname. Wenn nicht angegeben, wird die aktuelle verwendet.
     * @return string Pfad zur .system.json-Datei
     * @throws \Exception Wenn keine Datenbank oder Tabelle gesetzt ist
     */
    public function getTableSystemFilePath(?string $table = null): string {
        if (!$this->currentDbPath) {
            throw new \Exception("❌ Keine Datenbank ausgewählt.");
        }

        $tableName = $table ?? $this->currentTableName;

        if (!$tableName) {
            throw new \Exception("❌ Kein Tabellenname angegeben und keine aktuelle Tabelle gesetzt.");
        }

        return $this->currentDbPath . DIRECTORY_SEPARATOR . $tableName . '.system.json';
    }











}
