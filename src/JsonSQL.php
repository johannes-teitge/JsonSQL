<?php
namespace Src;
/**
 * Class JsonSQL
 *
 * JsonSQL ist eine leichtgewichtige, dateibasierte Datenbank-Engine auf JSON-Basis mit SQL-ähnlichen Funktionen.
 * 
 * 🔹 Features:
 * - SQL-ähnliche Abfragen (select, where, groupBy, orderBy, limit)
 * - CRUD-Operationen inkl. AutoField-Verwaltung (autoincrement, autohash, autouuid)
 * - Systemfelder: created_at, updated_at
 * - Verschlüsselung sensibler Felder per AES-256-CBC
 * - JOINs: INNER, LEFT, RIGHT, FULL OUTER
 * - Import-/Export inkl. system.json
 * - Statistische Funktionen: avg, sum, min, max, median, mode, stddev, variance, range
 * 
 * 📦 Einsatzgebiete:
 * - Kleine bis mittlere Anwendungen
 * - Portable oder offlinefähige Tools
 * - Embedded-Backends
 * - NoSQL-Lernprojekte & Dev-Tools
 *
 * @package Src
 * @author Johannes
 * @version 1.1.0
 * @date 2025-23-05 12:17:00
 * @createdate 2025-04-07 05:55:55 
 * @license MIT
 * @link https://teitge.de
 * @see JsonSQLHelper für Automatisierungen & Field-Checks
 */

 // ======================================================
 // 📦 JsonSQL: Zentrale Schnittstelle für alle Module
 // ======================================================
 //
 // Diese Datei bindet alle Teilmodule (Traits) ein und stellt die vollständige JsonSQL-Engine über eine
 // einzige Klasse bereit.
 //
 // 👉 Die Moduldateien befinden sich unter /JsonSQL und werden nach Themen sortiert eingebunden:
 //
 //    - JS_Base.php      → Basis-Funktionen & Konstruktor
 //    - JS_Database.php  → Datenbank-Verwaltung (Verzeichnisse, Infos, Clear)
 //    - JS_Tables.php    → Tabellen-Handling (Existenz, Drop, Truncate, Info)
 //
 // ✅ Erweiterbar: Neue Funktionen können jederzeit als eigene Traits ergänzt und hier eingebunden werden.
 //
 // ======================================================

// Lade die einzelnen Modul-Dateien mit require_once ein
require_once __DIR__ . '/JsonSQL/JS_Base.php';        // Basisklasse mit Kern-Properties und Konstruktor
require_once __DIR__ . '/JsonSQL/JS_Helper.php';      // Helperfunktionen und Klassen
require_once __DIR__ . '/JsonSQL/JS_Database.php';    // Verwaltung mehrerer Datenbanken (z. B. use())
require_once __DIR__ . '/JsonSQL/JS_System.php';      // Handling für system.json (AutoFields, Timestamps, Encryption etc.)
require_once __DIR__ . '/JsonSQL/JS_Tables.php';      // Tabellenfunktionen (z. B. createTable, dropTable)
require_once __DIR__ . '/JsonSQL/JS_Query.php';       // Query-Logik (from, where, select, groupBy etc.)
require_once __DIR__ . '/JsonSQL/JS_Aggregates.php';  // Aggregatfunktionen (sum, avg, median, stddev etc.)
require_once __DIR__ . '/JsonSQL/JS_Joins.php';       // Join-Logik (INNER, LEFT, RIGHT, FULL OUTER)
require_once __DIR__ . '/JsonSQL/JS_Crud.php';        // insert, update, delete (CRUD-Logik)
require_once __DIR__ . '/JsonSQL/JS_Encryption.php';  // Verschlüsselung & Entschlüsselung von Feldern
require_once __DIR__ . '/JsonSQL/JS_Export.php';  
require_once __DIR__ . '/JsonSQL/JS_CustomFunctions.php';  


// Die Hauptklasse, die alles vereint
class JsonSQL extends \Src\JsonSQL\JS_Base {

    // ➕ Datenbankverwaltung (use(), Konstruktor etc.)
    // Dieser Trait ermöglicht das Verwalten von Datenbanken, das Wechseln zwischen verschiedenen Datenbanken
    // und enthält Funktionen wie `use()` und die Verwaltung von Datenbank-Verzeichnissen.
    use \Src\JsonSQL\JS_Database;

    // ➕ Hilffunktionen wie generateRandomInt(), generateRandomFloat()... , generateHash() etc.
    // Enthält Hilfsfunktionen zur Erzeugung von Zufallswerten, Hashes und anderen grundlegenden Funktionen,
    // die in verschiedenen Teilen der Engine verwendet werden.
    use \Src\JsonSQL\JS_Helper;      

    // ➕ System-Features wie AutoFields, system.json lesen/schreiben, Timestamps etc.
    // Dieser Trait bietet Funktionen für das Arbeiten mit system.json, der Verwaltung von AutoFields
    // (z.B. auto increment, UUID) und das Setzen von Timestamps.
    use \Src\JsonSQL\JS_System;  
    
    // ➕ Tabellenoperationen wie createTable(), dropTable() etc.
    // Beinhaltet Funktionen zum Erstellen, Löschen und Truncieren von Tabellen sowie zur Abfrage von Tabellendetails.
    use \Src\JsonSQL\JS_Tables;  

    // ➕ Query-Funktionen wie from(), select(), where(), limit(), orderBy(), groupBy() etc.
    // Bietet SQL-ähnliche Query-Funktionen wie `from()`, `select()`, `where()`, `groupBy()` und andere,
    // die für die Datenabfrage verwendet werden.
    use \Src\JsonSQL\JS_Query;  

    // ➕ Aggregatberechnungen (sum, avg, min, max, median, stddev, etc.)
    // Funktionen für statistische Berechnungen wie `sum()`, `avg()`, `min()`, `max()`, `median()`, `stddev()`, usw.
    use \Src\JsonSQL\JS_Aggregates;     

    // ➕ Join-Logik für Datenzusammenführung über Tabellen hinweg
    // Dieser Trait enthält Funktionen für das Ausführen von SQL-ähnlichen Joins zwischen Tabellen, 
    // z.B. INNER, LEFT, RIGHT und FULL OUTER Joins.
    use \Src\JsonSQL\JS_Joins;    

    // ➕ CRUD-Operationen: insert(), update(), delete(), get()
    // Ermöglicht CRUD-Operationen (Create, Read, Update, Delete) für Daten innerhalb der Tabellen.
    use \Src\JsonSQL\JS_Crud;    

    // ➕ Verschlüsselung / Entschlüsselung einzelner Felder
    // Bietet Funktionen zur Verschlüsselung und Entschlüsselung von Feldern unter Verwendung von AES-256-CBC.
    use \Src\JsonSQL\JS_Encryption;   
    
    // ➕ Export von Daten und Tabellen
    // Dieser Trait enthält Funktionen zum Exportieren von Daten und Tabellen, z.B. in JSON oder CSV.
    use \Src\JsonSQL\JS_Export;        
    
    // ➕ Erweiterbare benutzerdefinierte Funktionen
    // Dieser Trait ermöglicht das Hinzufügen benutzerdefinierter Funktionen, die für spezifische Bedürfnisse 
    // entwickelt werden können.
    use \Src\JsonSQL\JS_CustomFunctions;       
}
