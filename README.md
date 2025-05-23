
# 🗂️ JsonSQL

**JsonSQL** ist eine moderne PHP-Bibliothek für SQL-ähnliche Abfragen und Datenmanagement direkt auf **JSON-Dateien**.  
Sie funktioniert **komplett dateibasiert** – ohne MySQL, SQLite oder Datenbankserver.  
**Neu ab Version 1.0.8:** Jetzt mit leistungsstarker **TableRepair-Funktion** zur automatischen Datenbereinigung und Feldsortierung nach Systemdefinition!

[![Latest Stable Version](https://img.shields.io/packagist/v/jsonsql/jsonsql.svg)](https://packagist.org/packages/jsonsql/jsonsql)
[![License](https://img.shields.io/packagist/l/jsonsql/jsonsql.svg)](https://packagist.org/packages/jsonsql/jsonsql)

---

## ✅ Vorteile

- Kein Datenbankserver notwendig
- Läuft überall, auch in Shared-Hosting & Offline-Apps
- Vollständig in PHP geschrieben
- SQL-ähnliche Syntax (`select`, `where`, `groupBy`, `join`, …)
- Transaktionen, Verschlüsselung, Statistikfunktionen
- **TableRepair:** Datenbereinigung & automatische Feldsortierung nach Systemdefinition
- Erweiterbar & verständlich – ideal für Prototypen, Tools & Adminpanels

---

> **Neu in Version 1.0.8:**  
> Automatische **TableRepair**-Funktion: Ergänzt fehlende Pflichtfelder, entfernt unerlaubte Felder und bringt alle Datensätze in die von der `system.json` vorgegebene Feldreihenfolge.  
> **Demo und Utility-Methoden inklusive!**

---

## 🚀 Installation

Mit Composer installieren:

```bash
composer require teitge/jsonsql
```

Oder manuell einbinden:

```php
require_once 'src/JsonSQL/JsonSQL.php';
```

---

## ⚡ Beispiel

```php
use JsonSQL\JsonSQL;

$db = new JsonSQL([
    'path' => __DIR__ . '/data',
    'table' => 'users',
]);

$users = $db->select(['id', 'name'])
            ->where('age', '>=', 18)
            ->orderBy('name')
            ->get();
```

---

## 🧰 Features

| Kategorie                  | Details                                                                 |
|----------------------------|-------------------------------------------------------------------------|
| **Datenquelle**            | JSON-Dateien je Tabelle                                                |
| **Abfragen**               | `select`, `where`, `orderBy`, `groupBy`, `join`, `limit`, `offset`     |
| **Systemlogik**            | `autoincrement`, `autouuid`, `autohash`, `timestamps`, Validierung     |
| **Verschlüsselung**        | Felder können automatisch ver- und entschlüsselt werden (`encrypt`)    |
| **Statistik**              | `sum`, `avg`, `count`, `median`, `mode`, `stddev`, `variance`, …       |
| **Transaktionen**          | `transact()`, `commit()` – sicher & verzögert schreiben                |
| **Import/Export**          | CSV & MySQL (CREATE/INSERT) aus `.system.json` generieren              |
| **TableRepair & Bereinigung** | Fehlende Felder ergänzen, unerlaubte Felder entfernen, automatische Sortierung |
| **Modularer Code**         | PSR-4, eigene Traits & Klassen je Bereich                              |

---

## 📁 Struktur

```
src/
├── JsonSQL.php          // Hauptklasse
├── JS_Base.php          // Gemeinsame Methoden
├── JS_Select.php        // SELECT-Logik
├── JS_Insert.php        // INSERT-Logik
├── JS_System.php        // Automatische Felder, Validierung, Timestamps, ...
└── ...
```

---

## 🔎 Systemfelder (system.json)

| Typ                | Bedeutung                                 |
|--------------------|-------------------------------------------|
| `autoincrement`    | Zählt IDs automatisch hoch                |
| `autouuid`         | Generiert UUIDs bei jedem Insert          |
| `autohash`         | Erzeugt Hash (z. B. md5, sha256)          |
| `timestamp:create` | Zeitstempel bei Erstellung                |
| `timestamp:update` | Zeitstempel bei Änderung                  |
| `encrypt` / `decrypt` | Feldinhalt verschlüsseln / entschlüsseln |

> **Hinweis:**  
> Beim Einfügen, Aktualisieren **und Reparieren** von Datensätzen wird die Feldreihenfolge jetzt automatisch nach der `system.json` gespeichert – für perfekte Konsistenz und Export-Kompatibilität.

---

## 🧪 Demos

👉 Vollständige Demos findest du unter `/examples/demos`:

- 🛠️ **TableRepair & Analyse-Demo** – prüft und repariert Tabellen nach Systemvorgabe  
- 🔐 Passwortmanager
- 🚗 Fahrzeugdatenbank mit n:m-Kategorien
- 📦 Produktverwaltung mit Bildern & CSV-Export
- 📊 Statistiken & Charts
- 🧾 MiniShop mit JSON-Daten und Bestellung

---

## 📌 Roadmap

- [x] Systemfelder & Validierung
- [x] MySQL- & CSV-Export aus JSON
- [x] Transaktionen
- [x] Aggregatfunktionen
- [x] TableRepair & Datenbereinigung
- [ ] Admin-UI zur Datenbearbeitung
- [ ] JsonSQL Plugin-API
- [ ] Dokumentationsgenerator aus system.json
- [ ] Visual Query Builder

---

## 🔐 Lizenz

MIT – kostenlos & offen für private oder kommerzielle Nutzung.

---

## 🤝 Mitwirken

Du hast Ideen, willst mithelfen oder Fehler melden?  
→ Issues & Pull Requests sind willkommen!

---

### 🗂️ Komplett-Download

Du kannst auch den vollständigen Download inklusive Demo von [https://www.teitge.de/JsonSQL/JsonSQL.zip](https://www.teitge.de/JsonSQL/JsonSQL.zip) herunterladen.

---

**© 2024–2025 JsonSQL Team**  
🔗 Projektseite: [https://teitge.de](https://www.teitge.de/JsonSQL/doku/)  
🔧 GitHub: [https://github.com/johannes-teitge/JsonSQL](https://github.com/johannes-teitge/JsonSQL)
