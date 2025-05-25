<?php
namespace Src\JsonSQL;

trait JS_Query
{

    
    /**
     * from
     * ====
     *
     * Wählt die Tabelle (JSON-Datei) aus, mit der die weitere Abfrage arbeiten soll.
     * Diese Methode muss vor dem Aufruf von `get()`, `where()`, `select()`, `insert()` usw. erfolgen.
     *
     * Parameter:
     * ----------
     * @param string $table   Der Name der Tabelle (Dateiname ohne ".json"-Endung)
     *
     * Rückgabe:
     * ---------
     * @return self           Gibt das Objekt zurück, um Method-Chaining zu ermöglichen
     *
     * Verhalten:
     * ----------
     * - Die Methode prüft, ob eine Datenbank ausgewählt wurde (`$this->currentDbPath` muss gesetzt sein).
     *   Falls nicht, wird eine Exception geworfen.
     *
     * - Der Dateipfad zur Tabelle wird zusammengesetzt: `$this->currentDbPath . '/' . $table . '.json'`
     * - Falls die Datei noch nicht existiert, wird sie automatisch mit leerem Array (`[]`) angelegt.
     *
     * - Anschließend wird die Tabelle geladen (via `$this->loadTableData()`),
     *   sodass die Daten im Arbeitsspeicher verfügbar sind.
     *
     * Intern:
     * -------
     * - `$this->currentTableName` speichert den Tabellennamen
     * - `$this->currentTableFile` enthält den vollständigen Dateipfad
     * - `$this->tableData` wird durch `loadTableData()` mit den Inhalten der Datei gefüllt
     *
     * Beispiel:
     * ---------
     * $db->from('produkte');  // verwendet die Datei "produkte.json" in der aktuellen Datenbank
     *
     * Hinweis:
     * --------
     * - Diese Methode ist Voraussetzung für alle Operationen mit Tabellendaten.
     * - Beim Datenbankwechsel (`useDatabase()`) muss `from()` erneut aufgerufen werden.
     */

    public function from(string $table): self {
        if (!$this->currentDbPath) {
            throw new \Exception("Keine Datenbank ausgewählt.");
        }

        $this->currentTableName = $table; // 👈 aktuelle Tabelle merken
        $file = $this->currentDbPath . DIRECTORY_SEPARATOR . $table . '.json';
        if (!file_exists($file)) {
            file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
        }

        $this->currentTableFile = $file;
        $this->loadTableData();
        return $this;
    }


    /**
     * select
     * ======
     *
     * Definiert die Spalten (Felder), die aus den Datensätzen selektiert werden sollen.
     * Unterstützt dabei optional auch Aliasnamen zur Umbenennung einzelner Felder.
     *
     * Der Aufruf dieser Methode ist optional. Wenn sie nicht verwendet wird,
     * oder mit `'*'` aufgerufen wird, werden alle Felder unverändert zurückgegeben.
     *
     * Parameter:
     * ----------
     * @param string|array $args  Liste der gewünschten Felder, entweder:
     *                            - als String (z. B. "name, preis AS cost")
     *                            - oder als Array (z. B. ["name", "preis AS cost"])
     *                            Standard: '*' (alle Felder)
     *
     * Rückgabe:
     * ---------
     * @return self    Ermöglicht Method-Chaining (Fluent Interface)
     *
     * Verhalten:
     * ----------
     * - Ein mehrfacher Aufruf von `select()` innerhalb derselben Abfrage ist nicht erlaubt.
     *   Ein zweiter Aufruf löst eine Exception aus.
     * - Felder mit "AS"-Alias (case-insensitive) werden korrekt erkannt:
     *     "preis AS cost" wird zu `['field' => 'preis', 'alias' => 'cost']`
     * - Falls kein Alias angegeben ist, wird das Feld unverändert als `alias` verwendet.
     *
     * Interne Speicherung:
     * --------------------
     * - `$this->select`:     Enthält alle Feld-Alias-Paare für die spätere Auswahl.
     * - `$this->aliasMap`:   Optional verwendbare Zuordnung von alias => original für spätere Referenzen.
     *
     * Beispiel:
     * ---------
     * $db->select('product, price AS kosten')->get();
     *
     * Ergebnis:
     * [
     *   ['product' => 'Toaster', 'kosten' => 29.99],
     *   ...
     * ]
     *
     * Hinweis:
     * --------
     * Diese Auswahl wirkt sich auf die Ausgabe von `get()` aus.
     * Felder, die nicht in `select()` angegeben sind, werden nicht zurückgegeben.
     */
    public function select($args = '*'): self {

        // Mehrfaches select erlaubt, letzte Auswahl gewinnt:
        $this->selectCalled = true;        
        $this->aliasMap = [];
        $this->select = [];
        $this->filters = []; // Bug fixed 14.04.2025
    
        if ($args === '*') {
            $this->select[] = ['field' => '*', 'alias' => '*'];
            return $this;
        }
    
        $fields = is_array($args) ? $args : explode(',', $args);
    
        foreach ($fields as $field) {
            $field = trim($field);
            $parts = preg_split('/\s+AS\s+/i', $field);
    
            if (count($parts) === 2) {
                $orig = trim($parts[0]);
                $alias = trim($parts[1]);
            } else {
                $orig = $alias = trim($parts[0]);
            }
    
            $this->select[] = ['field' => $orig, 'alias' => $alias];
            $this->aliasMap[$alias] = $orig;
        }
    
        return $this;
    }


    /**
     * applySelect
     * ===========
     *
     * Wendet die in der `select()`-Methode definierten Feld-Auswahlen und Aliasnamen
     * auf die übergebenen Datensätze an. Dabei wird jedes Datensatz-Array so reduziert,
     * dass nur die angegebenen Felder (Spalten) mit optionalen Aliasnamen enthalten sind.
     *
     * Diese Methode wird intern von `get()` aufgerufen, nachdem Filter, Sortierung und Gruppierung
     * angewendet wurden.
     *
     * Parameter:
     * ----------
     * @param array $data   Ein Array von assoziativen Arrays (Datenzeilen), wie z. B. aus einer JSON-Tabelle geladen.
     *
     * Rückgabe:
     * ---------
     * @return array        Ein Array von Arrays, bei dem pro Datensatz nur die ausgewählten Felder vorhanden sind.
     *
     * Verhalten:
     * ----------
     * - Falls keine Felder ausgewählt wurden (`select()` nicht aufgerufen oder `['field' => '*']`), 
     *   wird das Original-Array ohne Änderungen zurückgegeben.
     * - Jedes Feld kann über einen Alias umbenannt werden (`'alias' => 'neuerName'`).
     * - Nicht vorhandene Felder im Original-Datensatz werden mit `null` zurückgegeben.
     *
     * Beispiel-Aufruf:
     * ----------------
     * $db->select([
     *     ['field' => 'product', 'alias' => 'Artikel'],
     *     ['field' => 'price', 'alias' => 'Preis']
     * ])->get();
     *
     * Beispiel-Ausgabe:
     * -----------------
     * [
     *   ['Artikel' => 'Wasserkocher', 'Preis' => 42.99],
     *   ['Artikel' => 'Toaster',      'Preis' => 29.90],
     * ]
     *
     * Hinweis:
     * --------
     * - Diese Methode wird typischerweise am Ende der Abfragekette aufgerufen.
     * - Unterstützt keine verschachtelten Felder oder Berechnungen – nur direkte Zuordnung.
     */
    protected function applySelect(array $data): array {
        if (empty($this->select) || ($this->select[0]['field'] ?? '') === '*') return $data;
    
        return array_map(function ($row) {
            $selected = [];
    
            foreach ($this->select as $sel) {
                $field = $sel['field'];
                $alias = $sel['alias'];
    
                $selected[$alias] = $row[$field] ?? null;
            }
    
            return $selected;
        }, $data);
    }
        
    





    
    
    /**
     * limit
     * =====
     *
     * Setzt die maximale Anzahl an Ergebnissen (Limit) sowie den Startindex (Offset),
     * ab dem die Ergebnisse zurückgegeben werden sollen.
     * Diese Werte werden später von der Methode `applyLimit()` verwendet.
     *
     * Parameter:
     * ----------
     * @param int $limit   Die maximale Anzahl der zurückzugebenden Einträge.
     *                     - Bei 0 wird keine Limitierung vorgenommen (alle Einträge werden zurückgegeben).
     *
     * @param int $offset  (Optional) Der Startindex innerhalb der Ergebnismenge.
     *                     - Standardwert: 0 (Beginne mit dem ersten Datensatz)
     *                     - Werte > 0 überspringen entsprechend viele Einträge
     *
     * Rückgabe:
     * ---------
     * @return self        Gibt die Instanz des aktuellen Objekts zurück (Fluent Interface),
     *                     um Methoden wie `->limit()->get()` zu ermöglichen.
     *
     * Beispiel:
     * ---------
     * $db->select()->from('produkte')->limit(10, 20)->get();
     * → gibt maximal 10 Ergebnisse ab dem 21. Datensatz zurück (offset = 20)
     *
     * Typischer Anwendungsfall:
     * --------------------------
     * - Paginierung von Ergebnissen (z. B. Seite 2 mit je 10 Einträgen → limit(10, 10))
     *
     * Hinweis:
     * --------
     * - Die eigentliche Begrenzung wird erst in `applyLimit()` ausgeführt,
     *   die intern von `get()` aufgerufen wird.
     */
    public function limit(int $limit, int $offset = 0): self {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }
    
    /**
     * applyLimit
     * ==========
     *
     * Wendet die Limitierung auf die übergebene Datenmenge an.
     * Diese Methode schneidet das übergebene Array gemäß gesetztem Offset und Limit zu.
     *
     * Parameter:
     * ----------
     * @param array $data   Die vollständige Datenmenge (z. B. Ergebnis aus einer Selektion).
     *
     * Rückgabe:
     * ---------
     * @return array        Ein Array mit maximal `$this->limit` Einträgen, beginnend ab `$this->offset`.
     *
     * Logik:
     * ------
     * - Wenn `limit === 0`, wird kein Slice vorgenommen und die originalen Daten zurückgegeben.
     * - Ansonsten wird mit `array_slice()` ein Ausschnitt aus dem Array gebildet.
     *   Dabei bestimmt:
     *   - `$this->offset` den Startindex (Standard meist 0),
     *   - `$this->limit` die maximale Anzahl an Datensätzen.
     *
     * Beispiel:
     * ---------
     * $this->limit  = 10;
     * $this->offset = 20;
     * => gibt die Datensätze 20 bis 29 (insgesamt 10) zurück
     *
     * Typisches Einsatzszenario:
     * ---------------------------
     * - Wird intern in der `get()`-Methode oder nach der Filterung/Sortierung/Gruppierung aufgerufen,
     *   um das finale Ergebnis für z. B. Pagination zu erzeugen.
     *
     * Hinweis:
     * --------
     * - Die Methode verändert die Originaldaten nicht, sondern gibt eine begrenzte Kopie zurück.
     */
    protected function applyLimit(array $data): array {
        if ($this->limit === 0) return $data;
        return array_slice($data, $this->offset, $this->limit);
    }
    
 
    /**
     * Fügt Filterbedingungen für die Datenbankabfrage hinzu.
     *
     * Standardmäßig überschreibt ein neuer Aufruf von `where()` alle bisherigen Bedingungen.
     * Wenn `$append = true` gesetzt ist, werden die neuen Bedingungen an bestehende angehängt.
     *
     * Beispiel (Überschreibt Standardmäßig):
     *   $db->where([['vendor', '=', 'Aldi']])
     *      ->where([['product', '=', 'Toaster']], 'AND'); // Nur letzte Bedingung gilt
     *
     * Beispiel (Kombiniert Bedingungen):
     *   $db->where([['vendor', '=', 'Aldi']])
     *      ->where([['product', '=', 'Toaster']], 'AND', true); // Beide Bedingungen werden geprüft (mit AND)
     *
     * @param array $columns Ein Array von Bedingungen: [['feld', 'operator', 'wert'], …]
     * @param string $merge Logischer Operator zwischen Bedingungen: 'AND' oder 'OR'
     * @param bool $append Wenn true, werden die Bedingungen an bestehende Filter angehängt
     * @return self
     */

    /**
     * Filterabfragen mit where()
     * ============================
     * 
     * Die `where()`-Methode dient dazu, Daten zu filtern.
     * Sie akzeptiert eine Liste von Bedingungen (als Array) sowie optional
     * eine Verknüpfungslogik ("AND" oder "OR", Standard: "OR").
     *
     * Die Bedingungen können in zwei Formaten übergeben werden:
     *
     * 1. Standardbedingungen (3 Elemente):
     *    [Feld, Operator, Wert]
     *
     * 2. Negierte Bedingungen:
     *    ['not', [Feld, Operator, Wert]]
     *
     * 3. Mehrere Bedingungen:
     *    Übergib mehrere solcher Arrays in einer Liste, z. B.:
     *    [
     *      ['vendor', '=', 'Aldi'],
     *      ['rating', '>', 3]
     *    ]
     *
     * ============================
     * Unterstützte Operatoren:
     * ----------------------------
     * - '=' oder '==': Gleichheit
     * - '!='         : Ungleichheit
     * - '>'          : Größer als
     * - '>='         : Größer oder gleich
     * - '<'          : Kleiner als
     * - '<='         : Kleiner oder gleich
     * 
     * - 'like'       : Textsuche (mit % als Platzhalter)
     *   - '%text%'   → enthält
     *   - 'text%'    → beginnt mit
     *   - '%text'    → endet mit
     *   - 'text'     → muss exakt enthalten sein
     * 
     * - 'in'         : Wert ist in Liste
     * - 'not in'     : Wert ist nicht in Liste
     *   → Übergib als Array oder Komma-getrennten String
     * 
     * - 'not'        : Negiert eine Bedingung (s.u.)
     *
     * ============================
     * Verknüpfung von Bedingungen:
     * ----------------------------
     * - Standardmäßig werden Bedingungen mit ODER verknüpft:
     *     ->where([...])        // OR
     *
     * - Für UND-Verknüpfung:
     *     ->where([...], 'AND')
     *
     * Beispiel:
     * ---------
     * $db->where([
     *     ['vendor', '=', 'Aldi'],
     *     ['product', 'like', '%Wasser%']
     * ], 'AND');
     *
     * ============================
     * Negation (NOT):
     * ---------------
     * Um eine einzelne Bedingung zu negieren, nutze:
     *   ['not', [Feld, Operator, Wert]]
     *
     * Beispiel:
     * ---------
     * $db->where([
     *     ['not', ['rating', '=', 2]],
     *     ['vendor', '=', 'Lidl']
     * ], 'AND');
     *
     * Hinweis: Negation funktioniert bei allen Operatoren!
     *
     * ============================
     * Beispiele:
     * ----------
     * 1. Einfache Gleichheit:
     *    $db->where([['vendor', '=', 'Aldi']]);
     *
     * 2. Mehrere Bedingungen mit AND:
     *    $db->where([
     *        ['vendor', '=', 'Aldi'],
     *        ['rating', '>=', 4]
     *    ], 'AND');
     *
     * 3. NOT verwenden:
     *    $db->where([
     *        ['not', ['product', 'like', '%Toaster%']],
     *        ['vendor', '=', 'Aldi']
     *    ], 'AND');
     *
     * 4. IN / NOT IN:
     *    $db->where([
     *        ['vendor', 'in', ['Aldi', 'Lidl']],
     *        ['product', 'not in', 'Toaster, Wasserkocher']
     *    ], 'AND');
     *
     * ============================
     * Erweiterungen:
     * --------------
     * - Gruppenklammern / verschachtelte Bedingungen werden in Zukunft unterstützt
     * - Kombinierte Abfragen (mehrere where() mit append) durch erweitertes API möglich
     *
     */


    public function where(array $columns, string $merge = 'AND', bool $append = false): self {
        if ($append) {
            $this->filters = array_merge($this->filters, $columns);
        } else {
            $this->filters = $columns;
        }
        $this->mergeCondition = $merge;
        return $this;
    }



public function where__(array $columns, string $merge = 'OR', bool $append = false): self {
    $this->filters = ['dsfdassadfds'];

    $this->filters = $columns;
    return $this;
}

    protected function evaluateCondition_($fieldValue, $operator, $value): bool {
        if ($fieldValue === null) return false;
    
        switch (strtolower($operator)) {
            case 'like':
                $valStr = strtolower((string) $value);
                $fieldStr = strtolower((string) $fieldValue);
    
                if (str_starts_with($valStr, '%') && str_ends_with($valStr, '%')) {
                    return str_contains($fieldStr, trim($valStr, '%'));
                } elseif (str_starts_with($valStr, '%')) {
                    return str_ends_with($fieldStr, ltrim($valStr, '%'));
                } elseif (str_ends_with($valStr, '%')) {
                    return str_starts_with($fieldStr, rtrim($valStr, '%'));
                } else {
                    return str_contains($fieldStr, $valStr);
                }
    
            case '=':
            case '==': return $fieldValue == $value;
            case '!=': return $fieldValue != $value;
            case '>': return $fieldValue > $value;
            case '>=': return $fieldValue >= $value;
            case '<': return $fieldValue < $value;
            case '<=': return $fieldValue <= $value;
    
            case 'in':
                if (!is_array($value)) $value = array_map('trim', explode(',', $value));
                return in_array($fieldValue, $value, true);
    
            case 'not in':
                if (!is_array($value)) $value = array_map('trim', explode(',', $value));
                return !in_array($fieldValue, $value, true);
    
            default: return false;
        }
    }
    

    /**
     * applyFilters
     * ============
     * 
     * Wendet alle gesetzten Filterbedingungen (`$this->filters`) auf die übergebenen Daten an.
     * Die Bedingungen werden in der Methode `where()` gesetzt.
     *
     * Funktionsweise:
     * ---------------
     * - Durchläuft alle Datensätze und überprüft jede definierte Bedingung.
     * - Für jeden Datensatz werden die Bedingungen geprüft und entsprechend
     *   der Verknüpfungslogik (`AND` oder `OR`) kombiniert.
     * - Rückgabe ist ein gefiltertes Array mit allen Datensätzen, die die Bedingungen erfüllen.
     *
     * Unterstützte Operatoren:
     * ------------------------
     * - '=' / '==': Gleichheit
     * - '!='      : Ungleichheit
     * - '>'       : Größer als
     * - '>='      : Größer oder gleich
     * - '<'       : Kleiner als
     * - '<='      : Kleiner oder gleich
     * - 'like'    : Textsuche (Platzhalter mit %)
     *               Beispiel: '%foo%' (enthält), 'foo%' (beginnt mit), '%foo' (endet mit)
     * - 'in'      : Wert in Liste (Array oder Komma-getrennter String)
     * - 'not in'  : Wert NICHT in Liste
     * - 'not'     : Negiert eine Bedingung, z. B. ['not', ['rating', '=', 2]]
     *
     * Verknüpfung von Bedingungen:
     * ----------------------------
     * - Standardmäßig OR-Verknüpfung: Ein Treffer reicht
     * - Bei 'AND': Alle Bedingungen müssen erfüllt sein
     * 
     * Beispielstruktur:
     * -----------------
     * $this->filters = [
     *     ['vendor', '=', 'Aldi'],
     *     ['rating', '>=', 3],
     *     ['not', ['product', 'like', '%Toaster%']]
     * ];
     * 
     * $this->mergeCondition = 'AND'; // oder 'OR'
     *
     * Rückgabe:
     * ---------
     * - Array mit allen Einträgen, die die Bedingungen erfüllen
     *
     * Besonderheiten:
     * ---------------
     * - Wird `$this->filters` nicht gesetzt, wird das Original-Array zurückgegeben.
     * - Nicht vorhandene Felder im Datensatz werden als `null` behandelt.
     *
     * @param array $data  Das Eingabearray (z. B. die komplette Tabelle)
     * @return array       Gefiltertes Array gemäß den gesetzten Bedingungen
     */
    protected function applyFilters_(array $data): array {
        if (empty($this->filters)) return $data;
    
        return array_filter($data, function ($row) {
            $results = [];
    
            foreach ($this->filters as $condition) {
    
                // Prüfe auf NOT-Bedingung
                if (is_array($condition) && strtolower($condition[0]) === 'not') {
                    $sub = $condition[1] ?? null;
                    if (!is_array($sub) || count($sub) !== 3) {
                        $results[] = false; // ungültig
                        continue;
                    }
    
                    [$field, $operator, $value] = $sub;
                    $match = !$this->evaluateCondition($row[$field] ?? null, $operator, $value);
    
                } elseif (is_array($condition) && count($condition) === 3) {
                    [$field, $operator, $value] = $condition;
                    $match = $this->evaluateCondition($row[$field] ?? null, $operator, $value);
                } else {
                    $match = false;
                }
    
                $results[] = $match;
            }
    
            return $this->mergeCondition === 'AND'
                ? !in_array(false, $results, true)
                : in_array(true, $results, true);
        });
    }
    


    /**
     * evaluateCondition
     * =================
     * 
     * Diese Methode prüft, ob eine einzelne Bedingung auf ein gegebenes Feld zutrifft.
     * Sie wird von `applyFilters()` verwendet, um die Filterlogik umzusetzen.
     *
     * Parameter:
     * ----------
     * @param mixed  $fieldValue  Der tatsächliche Wert im Datensatz für das betreffende Feld.
     * @param string $operator    Der Vergleichsoperator (z. B. '=', 'like', 'in', etc.).
     * @param mixed  $value       Der Vergleichswert, gegen den geprüft wird.
     *
     * Unterstützte Operatoren:
     * -------------------------
     * - '=' / '==':     Prüft Gleichheit (lose)
     * - '!=':           Prüft Ungleichheit
     * - '>':            Prüft, ob Feldwert größer als Vergleichswert ist
     * - '>=':           Prüft, ob Feldwert größer oder gleich Vergleichswert ist
     * - '<':            Prüft, ob Feldwert kleiner als Vergleichswert ist
     * - '<=':           Prüft, ob Feldwert kleiner oder gleich Vergleichswert ist
     * - 'like':         Führt eine unscharfe Textsuche durch
     *                   Platzhalter mit % erlaubt:
     *                   - '%foo%' → enthält "foo"
     *                   - 'foo%'  → beginnt mit "foo"
     *                   - '%foo'  → endet mit "foo"
     * - 'in':           Prüft, ob der Feldwert in einer Liste von Werten enthalten ist
     *                   (Array oder Komma-getrennter String)
     * - 'not in':       Prüft, ob der Feldwert NICHT in einer Liste enthalten ist
     * - 'not':          Negiert eine einzelne Bedingung. Muss als verschachtelter Ausdruck übergeben werden,
     *                   z. B. ['not', ['vendor', '=', 'Aldi']]
     *
     * Rückgabe:
     * ---------
     * @return bool  Gibt `true` zurück, wenn die Bedingung erfüllt ist, sonst `false`.
     *
     * Hinweise:
     * ---------
     * - Diese Methode behandelt sowohl einfache Werte als auch Strings.
     * - Bei 'like', 'in' und 'not in' werden Strings zu Arrays aufgeteilt, falls nötig.
     * - Alle Vergleiche erfolgen mit einem defensiven Null-Check (null führt zu false).
     */
    protected function applyFilters(array $data): array {
        if (empty($this->filters)) return $data;
    
        return array_filter($data, function ($row) {
            $results = [];
    
            foreach ($this->filters as $condition) {
                [$field, $operator, $value] = $condition;
    
                $fieldValue = $row[$field] ?? null;
                $match = false;
    
                if ($fieldValue !== null) {
                    switch (strtolower($operator)) {
                        case 'like':
                            $valStr = strtolower((string) $value);
                            $fieldStr = strtolower((string) $fieldValue);
    
                            if (str_starts_with($valStr, '%') && str_ends_with($valStr, '%')) {
                                $match = str_contains($fieldStr, trim($valStr, '%'));
                            } elseif (str_starts_with($valStr, '%')) {
                                $match = str_ends_with($fieldStr, ltrim($valStr, '%'));
                            } elseif (str_ends_with($valStr, '%')) {
                                $match = str_starts_with($fieldStr, rtrim($valStr, '%'));
                            } else {
                                $match = str_contains($fieldStr, $valStr);
                            }
                            break;
    
                        case '=':
                        case '==':
                            $match = $fieldValue == $value;
                            break;
                        case '!=':
                            $match = $fieldValue != $value;
                            break;
                        case '>':
                            $match = $fieldValue > $value;
                            break;
                        case '>=':
                            $match = $fieldValue >= $value;
                            break;
                        case '<':
                            $match = $fieldValue < $value;
                            break;
                        case '<=':
                            $match = $fieldValue <= $value;
                            break;
    
                        case 'in':
                            if (!is_array($value) && is_string($value)) {
                                $value = array_map('trim', explode(',', $value));
                            }
                            $match = in_array($fieldValue, $value, true);
                            break;
    
                        case 'not in':
                            if (!is_array($value) && is_string($value)) {
                                $value = array_map('trim', explode(',', $value));
                            }
                            $match = !in_array($fieldValue, $value, true);
                            break;
    
                        default:
                            $match = false;
                    }
                }
    
                $results[] = $match;
            }
    
            return $this->mergeCondition === 'AND'
                ? !in_array(false, $results, true)
                : in_array(true, $results, true);
        });
    }    
    
    /**
     * groupBy
     * =======
     * 
     * Definiert die Spalten, nach denen das Ergebnis gruppiert werden soll – ähnlich dem SQL-Statement `GROUP BY`.
     * Dies ist nützlich, wenn man Aggregatfunktionen wie `count()`, `sum()`, `avg()` etc. pro Gruppe anwenden möchte.
     *
     * Parameter:
     * ----------
     * @param array $columns  Ein Array mit einem oder mehreren Spaltennamen, z. B. ['vendor'] oder ['vendor', 'product']
     *
     * Verwendung:
     * -----------
     * $db->select()
     *    ->from('produkte')
     *    ->groupBy(['vendor']);
     *
     * Rückgabe:
     * ---------
     * @return self  Gibt die aktuelle Instanz von JsonSQL zurück, um Method Chaining zu ermöglichen (Fluent Interface).
     *
     * Hinweise:
     * ---------
     * - Die Gruppierung wirkt sich auf die Struktur des Rückgabewerts bei `get()` oder `aggregate()` aus.
     * - Wenn keine Aggregatfunktion genutzt wird, kann die Gruppierung auch einfach zum Sortieren oder Filtern verwendet werden.
     * - Die Spaltennamen sollten existierende Felder in den Datensätzen sein.
     */
    public function groupBy(array $columns): self {
        // Gruppierung nach mehreren Spalten
        $this->groupBy = $columns;
        return $this; // Wir geben hier das JsonSQL-Objekt zurück, damit 'get' weiterhin funktioniert
    }

    public function applyGroupBy(array $data, array $groupByColumns = []): array {
        // Wenn keine spezifischen Gruppierungsspalten übergeben werden, die Standard-Gruppierung verwenden
        $groupByColumns = !empty($groupByColumns) ? $groupByColumns : $this->groupBy;
        
        $groups = [];
        
        // Durch alle Zeilen iterieren und nach den gruppierten Spalten sammeln
        foreach ($data as $row) {
            // Kombinierte Gruppierungsschlüssel erstellen
            $keyParts = [];
            
            // Die Gruppierung nach den angegebenen Spalten durchführen
            foreach ($groupByColumns as $column) {
                // Den Wert für jede Gruppe erhalten (null falls nicht vorhanden)
                $keyParts[] = $row[$column] ?? null;
            }
            
            // Kombinierte Schlüssel für die Gruppierung erstellen (z. B. '1_2' für mehrere Spalten)
            $key = implode('_', $keyParts);
            
            // Wenn der Schlüssel nicht existiert, eine neue Gruppe erstellen
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            
            // Zeile zur entsprechenden Gruppe hinzufügen
            $groups[$key][] = $row;
        }
        
        // Rückgabe der gruppierten Daten
        return $groups;
    }
    
    

    
    /**
     * orderBy
     * =======
     *
     * Definiert eine Sortierreihenfolge für die Ergebnismenge.
     * Die Methode speichert intern, nach welcher Spalte sortiert werden soll und in welcher Richtung.
     *
     * Parameter:
     * ----------
     * @param string $column     Der Spaltenname, nach dem sortiert werden soll.
     *                           Beispiel: 'price', 'rating', 'date'
     *
     * @param string $direction  Sortierrichtung, entweder 'ASC' (aufsteigend) oder 'DESC' (absteigend).
     *                           Standard: 'ASC'
     *
     * Rückgabe:
     * ---------
     * @return self              Gibt das aktuelle JsonSQL-Objekt zurück, um method chaining zu ermöglichen.
     *
     * Beispiel:
     * ---------
     * $db->select()->from('produkte')
     *    ->orderBy('price', 'DESC')
     *    ->get();
     *
     * Intern:
     * -------
     * - Die eigentliche Sortierung wird später in der Methode `applyOrderBy()` vorgenommen.
     * - Diese Methode dient lediglich zur Übergabe und Speicherung der Sortierinformation.
     *
     * Hinweise:
     * ---------
     * - Groß-/Kleinschreibung der Spaltennamen muss mit den tatsächlichen Daten übereinstimmen.
     * - Falls die angegebene Spalte in einigen Datensätzen fehlt, wird `null` als Vergleichswert verwendet.
     * - Die Richtung wird nicht überprüft – validiere ggf. extern auf gültige Werte ('ASC', 'DESC').
     */
    public function orderBy(string $column, string $direction = 'ASC'): self {
        $this->orderBy = [$column, $direction];
        return $this;
    }


    /**
     * applyGroupBy
     * ============
     *
     * Gruppiert ein gegebenes Array von Datensätzen anhand eines oder mehrerer Spalten.
     * Diese Methode wird typischerweise intern nach `groupBy()` aufgerufen, um das tatsächliche Gruppieren umzusetzen.
     *
     * Parameter:
     * ----------
     * @param array $data              Das zu gruppierende Daten-Array (z. B. Ergebnis aus einer JSON-Tabelle)
     * @param array $groupByColumns    Optional: Array mit Spaltennamen, nach denen gruppiert werden soll.
     *                                 Wenn leer, wird auf die vorher definierte `$this->groupBy` zurückgegriffen.
     *
     * Rückgabe:
     * ---------
     * @return array  Ein assoziatives Array mit Gruppierungsschlüsseln als Keys und Arrays von Datensätzen als Werte.
     *                Beispiel:
     *                [
     *                    'Aldi' => [ ...Datensätze mit vendor=Aldi... ],
     *                    'Lidl' => [ ...Datensätze mit vendor=Lidl... ]
     *                ]
     *                Bei Mehrfach-Gruppierung (z. B. ['vendor', 'product']) wird der Key z. B. zu "Aldi_Wasserkocher".
     *
     * Funktionsweise:
     * ---------------
     * - Es wird für jede Zeile im $data-Array ein Gruppierungsschlüssel gebildet, bestehend aus den Werten der gewählten Spalten.
     * - Dieser Schlüssel wird per `implode('_', [...])` als eindeutiger Gruppenname verwendet.
     * - Die zugehörigen Zeilen werden unter diesem Schlüssel im Rückgabe-Array gesammelt.
     *
     * Anwendung:
     * ----------
     * Wird z. B. verwendet in Kombination mit Aggregatfunktionen, um Statistiken pro Gruppe zu berechnen.
     *
     * Beispiel:
     * ---------
     * $db->select()->from('produkte')->groupBy(['vendor']);
     * $groups = $db->applyGroupBy($data);
     *
     * Hinweise:
     * ---------
     * - Falls ein Feld in einem Datensatz fehlt, wird `null` als Teil des Gruppierungsschlüssels verwendet.
     * - Der erzeugte Gruppenschlüssel ist ein einfacher String – für komplexe Schlüssel (z. B. JSON/Hash) kann man das Verfahren anpassen.
     */    
    protected function applyOrderBy(array $data): array {
        if (empty($this->orderBy)) return $data;
    
        $column = $this->orderBy[0];
        $direction = strtoupper($this->orderBy[1]);
        $realColumn = $this->aliasMap[$column] ?? $column;
    
        usort($data, function ($a, $b) use ($realColumn, $direction) {
            $valA = $a[$realColumn] ?? null;
            $valB = $b[$realColumn] ?? null;
            return $direction === 'ASC'
                ? $valA <=> $valB
                : $valB <=> $valA;
        });
    
        return $data;
    }



}    
