<?php
/**
 * JS_Base.php
 *
 * Basis-Klasse für das JsonSQL-System.
 * Beinhaltet zentrale Konfigurations- und Verwaltungsfunktionen für den Umgang mit JSON-basierten Datenbanken.
 *
 * @package     JsonSQL
 * @subpackage  Core
 * @author      Johannes Teitge
 * @copyright   Copyright (c) 2025
 * @license     MIT License
 */

namespace Src\JsonSQL;



class JS_Base {
    protected string $jsonSQLVersion = '1.1.1'; 
    protected string $jsonSQLVersionDate = '2025-25-05';     
    protected array $databases = [];
    protected ?string $currentDbPath = null;
    protected ?string $currentTableFile = null;
    protected array $currentData = [];
    protected bool $tableLoaded = false;
    protected array $filters = [];
    protected array $select = [];
    protected array $orderBy = [];
    protected string $mergeCondition = 'OR';
    protected array $joinedTables = []; // für gespeicherte gejointe Tabellen
    protected int $limit = 0;
    protected int $offset = 0;    
    protected ?\Closure $having = null;    
    protected ?array $systemConfig = null;   
    protected ?string $encryptionKey = null;    
    protected ?int $lastInsertId = null;   
    protected ?string $lastInsertHash = null;      
    protected array $aliasMap = [];
    protected ?array $groupBy = null; 
    protected $selectCalled = false; // Flag für select-Aufruf    
    
    protected ?string $currentTableName = null;    
    protected ?array $tableInfo = null;  // Die Tabelle-Info wird hier gespeichert  
    
    // Transaktions-Unterstützung, added 08-04-2025
    protected bool $isTransaction = false;
    protected array $transactionBuffer = [];    
    protected ?array $lastError = null;  // Fehlerbehandlung    
    protected ?array $lastMessage = null;  // Fehlerbehandlung          

    // ============================================================================
    // 🔧 Variablen-Platzhalter für erlaubte Datentypen und Feldoptionen
    // ============================================================================
    // Diese Variablen werden im Trait JS_System befüllt und zentral verwaltet.
    // Sie dienen der Validierung von system.json-Feldern und stehen allen Modulen zur Verfügung.
    //
    // added: 2025-04-18 by Dscho
    // ============================================================================
    protected static array $allowedDataTypes = [];         // Wird in JS_System gesetzt
    protected static array $allowedFieldProperties = [];   // Wird in JS_System gesetzt

    // ============================================================================
    // 📌 Übersprungene Inserts (z. B. wegen UNIQUE)
    // ============================================================================
    // Diese Variable sammelt alle Datensätze, die beim Insert-Vorgang übersprungen
    // wurden – etwa weil sie gegen ein UNIQUE-Feld verstoßen haben.
    // Kann später ausgewertet oder im UI angezeigt werden.
    //
    // added: 2025-04-19 by Dscho
    // ============================================================================
    protected array $skippedInserts = [];

    /**
     * @var bool $useBackup
     *
     * 🛡️ Aktiviert oder deaktiviert das automatische Backup beim Speichern von JSON-Daten.
     *
     * Wenn diese Option aktiviert ist (`true`), wird bei jedem Schreibvorgang an einer JSON-Tabelle
     * automatisch eine Sicherungskopie der Originaldatei angelegt. Die Backups werden unter
     * `*.json.bak.YYYYMMDD-HHMMSS` gespeichert und ermöglichen eine Wiederherstellung bei Fehlern
     * oder Datenverlust.
     *
     * Wenn deaktiviert (`false`), erfolgt keine Sicherung – nützlich z. B. in Performance-kritischen
     * Umgebungen oder bei bewusstem Verzicht auf Versionierung.
     *
     * 💡 Diese Einstellung kann global gesetzt oder dynamisch geändert werden:
     * ```php
     * $db->setUseBackup(true);  // aktivieren
     * $db->setUseBackup(false); // deaktivieren
     * ```
     *
     * @default true
     * @since 1.0.4
     * @author Dscho
     * @see setUseBackup(), writeTableData()
     */    
    protected bool $useBackup = false; // Standard: Backup aktiv    


    /**
     * @var int $maxBackupFiles
     *
     * 🌀 Maximale Anzahl an Backup-Dateien, die pro Tabelle aufbewahrt werden.
     *
     * Diese Einstellung definiert, wie viele automatische Backups (`.json.bak.YYYYMMDD-HHMMSS`) pro Tabelle
     * gespeichert bleiben. Sobald die Anzahl überschritten wird, werden die ältesten Backups gelöscht,
     * sodass immer nur die neuesten erhalten bleiben.
     *
     * Beispiel:
     * - `maxBackupFiles = 5` → Nur die letzten 5 Backups bleiben erhalten.
     * - `maxBackupFiles = 0` → Keine Rotation (alle Backups bleiben bestehen).
     *
     * 💡 Diese Einstellung kann global gesetzt oder dynamisch angepasst werden:
     * ```php
     * $db->setMaxBackupFiles(10);  // z. B. max. 10 Backups behalten
     * ```
     *
     * @default 5
     * @since 1.0.6
     * @author Dscho
     * @see rotateBackups(), setMaxBackupFiles()
     */
    protected int $maxBackupFiles = 50;    


    /**
     * @var int $currentRecordIndex
     *
     * 🧭 Aktuelle Position (Index) im Ergebnis-Array für Record-Navigation.
     *
     * Diese Variable steuert die aktuelle "Zeiger-Position" im Array der geladenen Datensätze (`$currentData`).
     * Sie ermöglicht es, durch die Ergebnisse einer Abfrage wie in klassischen Datenbankumgebungen (z.B. Delphi, ADO, Access)
     * zu navigieren – etwa mit Methoden wie `first()`, `next()`, `prev()`, `last()` und `eof()`.
     *
     * Beispiel:
     * ```php
     * $db->from('users')->where('active', true)->get();
     * $record = $db->first();   // Erster Datensatz
     * while (!$db->eof()) {
     *     // Verarbeitung
     *     $record = $db->next();
     * }
     * ```
     *
     * 💡 Hinweis: Nach jedem neuen Ladevorgang (z.B. `get()`) wird der Index automatisch zurückgesetzt.
     * Der Index beginnt immer bei 0 (erster Datensatz).
     *
     * @default 0
     * @since 1.1.1
     * @author Dscho
     * @see first(), next(), prev(), last(), current(), eof(), seek()
     */
    protected int $currentRecordIndex = 0;  

    




    public function __construct(array $databases) {
        $this->databases = [];
        $this->initSystemDefaults();
    
        foreach ($databases as $alias => $path) {
            $cleanPath = rtrim($path, DIRECTORY_SEPARATOR);
    
            // Automatisch Ordner anlegen, wenn nicht vorhanden
            if (!is_dir($cleanPath)) {
                if (!mkdir($cleanPath, 0777, true)) {
                    throw new \Exception("❌ Verzeichnis für Alias '$alias' konnte nicht erstellt werden: $cleanPath");
                }
            }
    
            $this->databases[$alias] = $cleanPath;
        }
    }

    public function getVersion(): string {
        return $this->jsonSQLVersion;
    } 

    public function getVersionDate(): string {
        return $this->jsonSQLVersionDate;
    }     

    public function clearLastError(): void {
        $this->lastError = null;  // Setzt das Fehler-Array zurück
    } 

    // Getter für lastError
    public function getLastError(): ?array {
        return $this->lastError;
    }

    
    public function setLastError(string $function, string $errorMessage): void {
        $this->lastError = [
            'function' => $function,
            'errorMessage' => $errorMessage
        ];
    }

    // Setzt die letzte Erfolgsmeldung
    public function setLastMessage(string $action, string $message, array $values = []): void {
        $this->lastMessage = [
            'action' => $action,  // 'create' oder 'update'
            'message' => $message,
            'values' => $values  // Optional, kann leer bleiben
        ];
    }

    // Gibt die letzte Erfolgsmeldung zurück
    public function getLastMessage(): ?array {
        return $this->lastMessage;
    }

    // Setzt die Erfolgsmeldung zurück
    public function clearLastMessage(): void {
        $this->lastMessage = null;  // Setzt die Erfolgsmeldung zurück
    }

        
    
   
}
