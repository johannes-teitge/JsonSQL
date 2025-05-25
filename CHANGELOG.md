# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden in diesem Dokument festgehalten.

Dieses Changelog folgt den Richtlinien von [Keep a Changelog](https://keepachangelog.com/de/1.0.0/)
und verwendet [Semantische Versionierung](https://semver.org/lang/de/).


---

## [1.1.1] – 2025-05-25
### Hinzugefügt
- **Recordset-Navigation wie in Delphi/ADO:**
  - Neue Methoden: `first()`, `last()`, `next()`, `prev()`, `current()`, `seek($idx)`, `eof()`, `bof()`, `totalCount()`/`fullCount()`
  - Nach jedem `get()`, `insert()` oder `update()` kannst du jetzt bequem durch das aktuell geladene Datenset blättern, ohne neue Abfrage – ideal für Editoren, Blätter-Buttons, Pagination oder Master-Detail-Ansichten.
  - Intern verwaltet über `$currentData` und `$currentRecordIndex` (automatisch gesetzt).
  - Die klassische Einzelabfrage heißt nun `firstRecord()` – für gezielte Einzel-Queries mit `limit(1)`.

- **Start des Admin-Backends:**
  - Erste Version der Admin-Oberfläche integriert
  - Übersicht aller Tabellen, Detailansicht, TableRepair-Demo und Navigation
  - Grundlage für kommende Features: Tabellen-Management, System- und Benutzerverwaltung

### Geändert
- **Doku und Beispiele:**
  - Umfangreiche Dokumentation und Praxisbeispiele zur neuen Recordset-Navigation
  - Erste Doku zum Admin-Bereich (Work in Progress)

### Sonstiges
- Bugfix: Kleinere Fehlerbehebungen bei der internen Pufferverwaltung
- Kommentierung und Refactoring für bessere Lesbarkeit

---


## [1.1.0] – 2025-05-23  
### Hinzugefügt
- **TableRepair-Funktion erweitert:**
  - Ergänzt fehlende Pflichtfelder, entfernt unerlaubte Felder und passt die Feldreihenfolge an die `system.json` an
  - Repair-Logik in Demo und Admin-UI integriert (`demo_analyzeRepairTable.php`)
  - Zusätzliche Felder werden alphabetisch am Ende des Datensatzes sortiert

- **Konsistente Feldreihenfolge bei Insert & Update:**
  - Alle Datensätze werden beim Einfügen und Aktualisieren automatisch sortiert:
    - Systemfelder entsprechend der Reihenfolge in `system.json`
    - Nicht definierte Felder alphabetisch hinten

- **Hilfsfunktion `sortRecordFields()`:**
  - Zentrale Utility-Methode für die Feldsortierung in allen Operationen (Insert, Update, Repair)

- **Erweitertes Fehler- und Prüfprotokoll:**
  - Reihenfolgenfehler werden zusätzlich zu fehlenden und unerlaubten Feldern erkannt und ausgegeben
  - Verbesserte Darstellung der Ergebnisse im Admin-UI mit Hinweis „sortiert nach Systemdefinition“

### Geändert
- **Einheitliche Speicherung:**
  - Insert, Update und Repair nutzen jetzt dieselbe Sortierlogik
  - Fehler- und Demoseiten spiegeln die neue Feldreihenfolge wider
  - TableRepair prüft und korrigiert explizit die Feldreihenfolge

- **UI/UX-Verbesserungen:**
  - Hinweise zur Feldreihenfolge und Reparaturstatus ergänzt

- **Dokumentation:**
  - Neue Doku-Abschnitte und Beispiele zu TableRepair, Feldsortierung und Validierung

### Sonstiges
- Refactoring und Code-Cleanup in Kernfunktionen und Beispiel-Demos
- Verbesserte Kommentierung für Utility- und Repair-Methoden
- Kleinere Optimierungen bei der Handhabung komplexer Datenstrukturen



---

## [1.0.7] – 2025-04-24  
### Hinzugefügt
- **Backup-Funktionen integriert:**
  - `setBackupMode(bool $mode)`, `getBackupMode()`
  - `setMaxBackupFiles(int $max)`, `getMaxBackupFiles()`
  - `rotateBackups(string $filepath)`  
  → Automatische Sicherung und Rotation von Tabellen-Dateien

- **Validierungssteuerung beim Update:**
  - `.system.json` unterstützt `"allowAdditionalFields": true|false`
  - Optionale Prüfung auf unerlaubte Felder bei `update()`

- **Snackbar-Logik zur Benutzerinteraktion:**
  - Erfolgs- und Fehlermeldungen bei Aktionen
  - Eingebaut in AutoFields- und Fahrzeug-Demo

- **Fahrzeug-Demo deutlich erweitert:**
  - Neue Marken: Larifari, Eleantrix, Zentoro, Worsche, Solarix, Nordex
  - Realistische Fahrzeugdaten: Leistung, Beschreibung, Preis, Bilder
  - Frontend-Features:
    - Swiper.js für große Bildslider
    - Auffälliges Preisschild
    - Editierbare Felder mit Live-Speicherung
    - Snackbar-Rückmeldungen bei Änderungen

- **UI-Erweiterung:**
  - Blaue Aktionsleiste mit „Speichern“ / „Abbrechen“-Buttons
  - Bootstrap-Icons verwendet

### Geändert
- `setTable()` setzt `autoload` nun standardmäßig auf `true` → weniger Anwendungsfehler
- `insert()` bricht korrekt ab, wenn `required`-Felder fehlen
- Demos vereinheitlicht: Struktur, Header/Footer, Modularisierung

### Dokumentation
- Neue Abschnitte:
  - **MySQL-Export**
  - **Datenfelder** (system.json)
  - **Validierung bei Update**
  - **Snackbar-Integration**

### Sonstiges
- `listTables()` blendet `.system.json`-Dateien optional aus
- Code- und UI-Aufräumarbeiten, Vorbereitung auf weitere Module

---

## [1.0.6] – 2025-04-21
### Hinzugefügt
- **MySQL-Exportfunktionen erweitert:**
  - Neue Methode `ExportMySQLCreate()` zur Generierung von SQL-Tabellen aus `.system.json`
  - In `view_mysql.php` (N:M-Demo) werden nun alle Tabellen dynamisch angezeigt inkl.:
    - Dateipfad
    - Verlinkter Systemdefinition
    - Exportbutton für einzelne Tabellen (nur mit Systemdefinition)
    - Gesamtexport aller `.system.json`-basierten Tabellen möglich
- **Export-Sicherheit verbessert:**
  - Nur Tabellen mit gültiger `.system.json` können als SQL (INSERT) exportiert werden  
    → Sicherstellung korrekter Typen, Validierungen und Autowerte

### Geändert
- `listTables()`-Methode erhält neue Option zur Ausblendung von `.system.json`-Dateien
- `view_mysql.php` modernisiert:
  - Übersichtliche Darstellung mit Tabellennamen, Verlinkungen und Aktionen
  - Neue Exportbuttons eingebaut (je Tabelle & global)

### Sonstiges
- Dokumentation um Abschnitt *MySQL-Export* ergänzt (inkl. Anwendungsbeispiele und Einschränkungen)

---

## [1.0.5] – 2025-04-20
### Hinzugefügt
- Demo `nm_students` fertiggestellt:
  - Kurs-, Dozenten-, Klassen-, Studenten- und Belegungsverwaltung
  - Ansicht `view_overview.php` mit animierten Flip-Zählern (via @pqina/flip)
  - Kursansicht `view_courses.php` mit Dozenten-Link und Teilnehmerzählung
- Umfangreiche Dokumentation zur `where()`-Methode ergänzt:
  - Unterstützte Operatoren (`=`, `!=`, `like`, `in`, `not`, etc.)
  - Negierte Bedingungen mit `['not', [...]]`
  - Kombinierte Bedingungen mit `AND` oder `OR`
  - Hinweise zur Erweiterbarkeit und Nutzung mit `append = true`

### Geändert
- `JsonSQL::setTable()` setzt `autoload = true` nun standardmäßig  
  - reduziert Fehlerquellen durch vergessene `true`-Angabe beim Setzen der Tabelle
- Flip-Integration vereinheitlicht, nicht mehr mit `FlipCounter`, sondern mit `@pqina/flip` über `data-did-init`
- Datenanzeige und Navigation über Tabs modernisiert

### Sonstiges
- Dokumentation und Changelog erweitert


---


## [1.0.4] – 2025-04-19
### Hinzugefügt
- Unique-Validierung für `insert()` mit neuer Methode `recordExistsByUniqueFields()`
- Neuer Mechanismus zur **Duplikatprüfung bei Insert**
  - `$this->skippedInserts` speichert übersprungene Datensätze
  - Methoden: `getSkippedInserts()`, `getSkippedInsertCount()`, `clearSkippedInserts()`
- Unterstützung für Masseninserts mit nur einmaligem Ladevorgang der Tabelle
- Helper-Funktion `dump()` für strukturierte Debug-Ausgabe
- Erweiterung `create_classes()` zur Verwendung von Masseninsert und Reporting
- Fallback-Logik in `setTable()` für komplett leere JSON-Dateien (0 Byte)

### Geändert
- `insert()` aktualisiert: lädt Daten nur einmal, prüft Duplikate gegen geplante Inserts
- Unique-Feldprüfung erfolgt nun speichereffizient und korrekt ohne erneutes Laden
- Dokumentation der Methode `recordExistsByUniqueFields()` mit vollständigem DocBlock

---

## [1.0.3] – 2025-04-18
### Hinzugefügt
- Neue Demo: `demo_required.php` zur Validierung von Pflichtfeldern
  - Zeigt Fehlermeldung bei fehlendem Pflichtfeld
  - Erfolgreicher Insert nach Fehlerbehandlung
- Sicherheits-Checkliste vorbereitet für sicheres Setup mit Login & Zugriffskontrolle
- Neue Demo „MiniShop“ gestartet
  - Kategorien, Produkte, n:m-Verknüpfung
  - Warenkorb-Logik (Basisversion)
- Neue Funktion `analyzeTable()` in Modul `JS_Tables` integriert
- Vorbereitung für `tableRepair()` zur automatischen Reparatur basierend auf Systemdefinition
- Einführung `JS_META`-Trait zur Analyse von `@change`-Blöcken für Changelog-Generierung
- Neue Übersicht `overview.php` für strukturierte Demosammlung erstellt

### Geändert
- Demos in `index.php` modularisiert – Auslagerung in `overview.php`
- Demoübersicht überarbeitet: Cards, Suchfunktion, Themenfilter (vorerst deaktiviert)
- Demo-Startseiten nach Themen (AutoFields, Security, Performance etc.) gruppiert

---

## [1.0.2] – 2025-04-17
### Hinzugefügt
- Erste Dokumentation der `insert()`-Funktion mit Beschreibung interner Mechanismen:
  - `applyAutoFields`, automatische Felder
  - Validierung, Verschlüsselung, Hashing
- Einheitliches Format für Methodensignaturen in der Doku:
  - `<ul>`-Blöcke mit `Trait`, Rückgabetyp und Parametern
- Neuer Menüpunkt „Datenfelder“ in der Dokumentation mit Beschreibung aller verfügbaren Feldoptionen

### Geändert
- Version auf `1.0.2` aktualisiert
- Dokumentation strukturell überarbeitet

---

## [1.0.1] – 2025-04-10
### Hinzugefügt
- Admin-Oberfläche mit Bootstrap-Interface und Navigation
- Erste Demos zu:
  - AutoFields
  - CD-Verwaltung mit Genres (n:m-Beziehung)
  - Passwortverwaltung mit verschlüsselten Feldern
  - Statistikmodul inkl. Aggregaten (AVG, STDDEV, etc.)

### Geändert
- `system.json` wird nun tabellenweise verarbeitet
- Verschlüsselung und `autoHash` integriert in `insert()` und `update()`

---

## [1.0.0] – 2025-04-01
### Initial
- Projektstart mit `JsonSQL`-Kernklasse
- Unterstützte SQL-ähnliche Funktionen:
  - `select()`, `insert()`, `update()`, `delete()`, `where()`, `join()`, `groupBy()`, `orderBy()`, `limit()`, `pluck()`, `exists()`, `paginate()`
- Filelocking und Multiuser-Handling
- Erste Testdatenbanken und strukturierte Projektmappe
