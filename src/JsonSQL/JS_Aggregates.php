<?php
namespace Src\JsonSQL;


trait JS_Aggregates
{


    /**
     * Gruppiert die aktuell gefilterten Daten anhand der aktiven `groupBy`-Spalte(n).
     *
     * Der Gruppenschlüssel wird bei mehreren Gruppierungsspalten durch `|` getrennt erzeugt.
     *
     * @param string $column Die Spalte, auf der Aggregationen später basieren (für Kontext, derzeit nicht verwendet).
     * @return array Ein assoziatives Array mit Gruppenschlüssel => Array von Datensätzen.
     */    
    protected function getGroupedData(string $column): array {
        $data = $this->applyFilters($this->currentData);
        $groups = [];
    
        foreach ($data as $row) {
            $key = is_array($this->groupBy) ? implode('|', array_map(fn($g) => $row[$g] ?? '', $this->groupBy)) : ($row[$this->groupBy] ?? 'ungrouped');
            $groups[$key][] = $row;
        }
    
        return $groups;
    }

    /**
     * Gibt alle eindeutigen Werte einer Spalte zurück – optional sortiert.
     *
     * @param string $column Der Spaltenname, dessen eindeutige Werte ermittelt werden sollen.
     * @param bool $sort Ob die Rückgabe sortiert werden soll (Standard: true).
     * @param string $direction Sortierrichtung: 'asc' (aufsteigend) oder 'desc' (absteigend).
     * @return array Liste der eindeutigen Werte (ggf. sortiert, reindiziert).
     */
    public function distinct(string $column, bool $sort = true, string $direction = 'asc'): array {
        $data = $this->applyFilters($this->currentData);
        $values = array_column($data, $column);
        $unique = array_unique($values);
    
        if ($sort) {
            $direction = strtolower($direction);
            $direction === 'desc' ? rsort($unique) : sort($unique);
        }
    
        return array_values($unique); // reindizieren
    }
    
    
    
    /**
     * Berechnet die Summe einer oder mehrerer Spalten.
     *
     * Wird `groupBy()` verwendet, gibt die Methode ein Array mit Gruppenschlüssel => Summenwert(e) zurück.
     * Ohne Gruppierung wird die Gesamtsumme berechnet.
     * 
     * Bei mehreren Spalten werden die Summen je Spalte separat berechnet.
     *
     * Beispiel:
     * - `sum('price')` → float oder ['group' => float]
     * - `sum('price', 'stock')` → ['price' => ..., 'stock' => ...] oder ['group' => ['price' => ..., 'stock' => ...]]
     *
     * @param string ...$columns Eine oder mehrere Spaltennamen, deren Werte summiert werden sollen.
     * @return array|float Summenwert(e) – als einzelner float, Array pro Spalte oder gruppiert je nach Kontext.
     */    
    public function sum(string ...$columns): array|float {
        $data = $this->applyFilters($this->currentData);
    
        $isGrouped = !empty($this->groupBy);
        $result = $isGrouped ? [] : 0;
    
        $grouped = $isGrouped ? $this->getGroupedData() : ['__all__' => $data];
    
        foreach ($grouped as $groupKey => $rows) {
            $groupSum = 0;
    
            foreach ($rows as $row) {
                $rowSum = 0;
    
                foreach ($columns as $col) {
                    if (is_numeric($col)) {
                        $rowSum += (float) $col;
                    } elseif (isset($row[$col]) && is_numeric($row[$col])) {
                        $rowSum += (float) $row[$col];
                    }
                }
    
                $groupSum += $rowSum;
            }
    
            if ($isGrouped) {
                $result[$groupKey] = $groupSum;
            } else {
                $result = $groupSum;
            }
        }
    
        return $result;
    }
    
    
    /**
     * Berechnet den Durchschnitt (arithmetisches Mittel) einer Spalte.
     *
     * Wenn eine Gruppierung (`groupBy`) aktiv ist, wird der Durchschnitt je Gruppe
     * als assoziatives Array zurückgegeben. Ohne Gruppierung wird ein einzelner Durchschnittswert berechnet.
     *
     * @param string $column Die Spalte, deren Durchschnitt berechnet werden soll (numerisch).
     * @return array|float Durchschnittswert(e), je nach Gruppierung als einzelner Wert oder Array. Gibt 0 bei leeren Gruppen zurück.
     */   
    public function avg(string $column): array|float {
        $data = $this->applyFilters($this->currentData);
    
        $isGrouped = !empty($this->groupBy);
        $result = $isGrouped ? [] : 0;
    
        $grouped = $isGrouped ? $this->getGroupedData() : ['__all__' => $data];
    
        foreach ($grouped as $groupKey => $rows) {
            $sum = 0;
            $count = 0;
    
            foreach ($rows as $row) {
                if (isset($row[$column]) && is_numeric($row[$column])) {
                    $sum += (float) $row[$column];
                    $count++;
                }
            }
    
            $average = $count > 0 ? $sum / $count : 0;
    
            if ($isGrouped) {
                $result[$groupKey] = $average;
            } else {
                $result = $average;
            }
        }
    
        return $result;
    }
    
    
    /**
     * Ermittelt den kleinsten Wert einer Spalte.
     *
     * Gibt bei gruppierten Daten (`groupBy`) ein Array mit Gruppenschlüssel => Minimalwert zurück.
     * Bei ungefilterten Daten wird der Minimalwert der gesamten Spalte berechnet.
     *
     * @param string $column Die zu untersuchende Spalte (numerisch oder vergleichbar).
     * @return array|float|int|null Minimalwert(e), null bei leeren Daten.
     */    
    public function min(string $column): array|float|int|null {
        if (!empty($this->groupBy)) {
            $groups = $this->groupBy($this->groupBy);
            $result = [];
            foreach ($groups as $groupKey => $rows) {
                $result[$groupKey] = min(array_column($rows, $column));
            }
            return $result;
        }
    
        // Ohne groupBy
        $data = $this->applyFilters($this->currentData);
        $values = array_column($data, $column);
        return empty($values) ? null : min($values);
    }
    
    
    /**
     * Ermittelt den größten Wert einer Spalte.
     *
     * Gibt bei gruppierten Daten (`groupBy`) ein Array mit Gruppenschlüssel => Maximalwert zurück.
     * Bei ungefilterten Daten wird der Maximalwert der gesamten Spalte berechnet.
     *
     * @param string $column Die zu untersuchende Spalte (numerisch oder vergleichbar).
     * @return array|float|int|null Maximalwert(e), null bei leeren Daten.
     */    
    public function max(string $column): array|float|int|null {
        if (!empty($this->groupBy)) {
            $groups = $this->groupBy($this->groupBy);
            $result = [];
            foreach ($groups as $groupKey => $rows) {
                $result[$groupKey] = max(array_column($rows, $column));
            }
            return $result;
        }
    
        // Ohne groupBy
        $data = $this->applyFilters($this->currentData);
        $values = array_column($data, $column);
        return empty($values) ? null : max($values);
    }
    
    
    /**
     * Zählt die Anzahl unterschiedlicher Werte in einer Spalte.
     *
     * Wenn `groupBy` aktiv ist, wird pro Gruppe gezählt.
     * Ohne Gruppierung wird die Gesamtanzahl eindeutiger Werte berechnet.
     *
     * @param string $column Die Spalte, in der die eindeutigen Werte gezählt werden sollen.
     * @return array|int Anzahl unterschiedlicher Werte – als Integer oder Array je Gruppe.
     */
    public function countDistinct(string $column): array|int {
        if (!empty($this->groupBy)) {
            $groups = $this->getGroupedData($column);
            $result = [];

            foreach ($groups as $groupKey => $rows) {
                $values = array_column($rows, $column);
                $result[$groupKey] = count(array_unique($values));
            }

            return $result;
        }

        // Ohne Gruppierung
        $data = $this->applyFilters($this->currentData);
        $values = array_column($data, $column);
        return count(array_unique($values));
    }



    /**
     * Berechnet den Median (Zentralwert) einer Spalte.
     *
     * Wenn `groupBy` aktiv ist, wird der Median je Gruppe berechnet.
     * Ohne Gruppierung wird der Median der gesamten (gefilterten) Spalte berechnet.
     *
     * Der Median ist der mittlere Wert in einer sortierten Zahlenreihe. Bei gerader Anzahl
     * wird der Mittelwert der beiden mittleren Werte zurückgegeben.
     *
     * Beispiel:
     * - Werte: [4, 1, 5] → Median = 4
     * - Werte: [1, 3, 5, 9] → Median = (3 + 5) / 2 = 4
     *
     * @param string $column Die Spalte, deren Median berechnet werden soll (numerisch).
     * @return array|float|null Medianwert(e) – entweder als einzelner Wert, gruppiertes Array oder null bei leerem Datensatz.
     */
    public function median(string $column): array|float|null {
        if (!empty($this->groupBy)) {
            $groups = $this->getGroupedData($column);
            $result = [];

            foreach ($groups as $groupKey => $rows) {
                $values = array_column($rows, $column);
                sort($values);
                $count = count($values);

                if ($count === 0) {
                    $result[$groupKey] = null;
                } elseif ($count % 2 === 0) {
                    $middle1 = $values[$count / 2 - 1];
                    $middle2 = $values[$count / 2];
                    $result[$groupKey] = ($middle1 + $middle2) / 2;
                } else {
                    $result[$groupKey] = $values[floor($count / 2)];
                }
            }

            return $result;
        }

        // Ohne Gruppierung
        $data = $this->applyFilters($this->currentData);
        $values = array_column($data, $column);
        sort($values);
        $count = count($values);

        if ($count === 0) return null;
        if ($count % 2 === 0) {
            $middle1 = $values[$count / 2 - 1];
            $middle2 = $values[$count / 2];
            return ($middle1 + $middle2) / 2;
        } else {
            return $values[floor($count / 2)];
        }
    }

        

    /**
     * Bestimmt den häufigsten (modalen) Wert einer Spalte.
     *
     * Gibt bei Gruppierung (`groupBy`) die häufigsten Werte je Gruppe zurück.
     * Ohne Gruppierung wird der häufigste Gesamtwert zurückgegeben.
     *
     * @param string $column Die Spalte, deren häufigster Wert bestimmt werden soll.
     * @return array|int|string|null Modus-Wert(e) – als einzelner Wert, Array je Gruppe oder null bei leerem Datensatz.
     */
    public function mode(string $column): array|int|string|null {
        // Wenn Gruppierung aktiv ist
        if (!empty($this->groupBy)) {
            $groups = $this->groupBy($this->groupBy);
            $result = [];
    
            foreach ($groups as $groupKey => $rows) {
                $values = array_column($rows, $column);
    
                // Nur skalare, nicht-null Werte
                $values = array_filter($values, fn($v) => is_scalar($v) && $v !== null);
    
                // Floats in Strings konvertieren, um Vergleich zu stabilisieren
                $values = array_map(fn($v) => is_float($v) ? number_format($v, 2, '.', '') : (string) $v, $values);
    
                if (empty($values)) {
                    $result[$groupKey] = null;
                    continue;
                }
    
                $counts = array_count_values($values);
                arsort($counts);
                $result[$groupKey] = array_keys($counts)[0] ?? null;
            }
    
            return $result;
        }
    
        // Einzelmodus (ohne Grouping)
        $data = $this->applyFilters($this->currentData);
        $values = array_column($data, $column);
    
        $values = array_filter($values, fn($v) => is_scalar($v) && $v !== null);
        $values = array_map(fn($v) => is_float($v) ? number_format($v, 2, '.', '') : (string) $v, $values);
    
        if (empty($values)) return null;
    
        $counts = array_count_values($values);
        arsort($counts);
        return array_keys($counts)[0] ?? null;
    }
    

    

    /**
     * Berechnet die Spannweite (Range) einer Spalte.
     *
     * Die Spannweite ist der Unterschied zwischen dem größten und kleinsten Wert.
     * Bei Gruppierung (`groupBy`) wird die Spannweite je Gruppe berechnet.
     *
     * @param string $column Die Spalte, deren Spannweite berechnet werden soll (numerisch).
     * @return array|float|int|null Spannweite(n) – Einzelwert, Array je Gruppe oder null bei leerem Datensatz.
     */
    public function range(string $column): array|float|int|null {
        if (!empty($this->groupBy)) {
            $groups = $this->getGroupedData($column);
            $result = [];

            foreach ($groups as $groupKey => $rows) {
                $values = array_column($rows, $column);
                if (empty($values)) {
                    $result[$groupKey] = null;
                } else {
                    $result[$groupKey] = max($values) - min($values);
                }
            }

            return $result;
        }

        // Ohne Gruppierung
        $data = $this->applyFilters($this->currentData);
        $values = array_column($data, $column);

        if (empty($values)) return null;

        return max($values) - min($values);
    }

    

    /**
     * Berechnet die Varianz einer Spalte.
     *
     * Die Varianz misst die durchschnittliche quadratische Abweichung vom Mittelwert.
     * Bei Gruppierung (`groupBy`) wird die Varianz je Gruppe berechnet.
     *
     * @param string $column Die Spalte, deren Varianz berechnet werden soll (numerisch).
     * @return array|float|null Varianz(en) – Einzelwert, Array je Gruppe oder null bei leerem Datensatz.
     */
    public function variance(string $column): array|float|null {
        if (!empty($this->groupBy)) {
            $groups = $this->getGroupedData($column);
            $result = [];

            foreach ($groups as $groupKey => $rows) {
                $values = array_column($rows, $column);
                $count = count($values);

                if ($count < 2) {
                    $result[$groupKey] = null;
                    continue;
                }

                $mean = array_sum($values) / $count;
                $squaredDiffs = array_map(fn($v) => pow($v - $mean, 2), $values);
                $result[$groupKey] = array_sum($squaredDiffs) / ($count - 1); // Stichprobe
            }

            return $result;
        }

        // Ohne Gruppierung
        $data = $this->applyFilters($this->currentData);
        $values = array_column($data, $column);
        $count = count($values);

        if ($count < 2) return null;

        $mean = array_sum($values) / $count;
        $squaredDiffs = array_map(fn($v) => pow($v - $mean, 2), $values);
        return array_sum($squaredDiffs) / ($count - 1);
    }



    /**
     * Berechnet die Standardabweichung einer Spalte.
     *
     * Die Standardabweichung ist die Quadratwurzel der Varianz und misst die durchschnittliche Streuung
     * der Werte um den Mittelwert. Sie ist robuster interpretierbar als die Varianz.
     *
     * @param string $column Die Spalte, deren Standardabweichung berechnet werden soll (numerisch).
     * @return array|float|null Standardabweichung(en) – Einzelwert, Array je Gruppe oder null bei leerem Datensatz.
     */
    public function stddev(string $column): array|float|null {
        $variance = $this->variance($column);

        if (is_array($variance)) {
            return array_map(fn($v) => $v !== null ? sqrt($v) : null, $variance);
        }

        return $variance !== null ? sqrt($variance) : null;
    }

    

    public function count(): int {
        $data = $this->applyFilters($this->currentData);
        return count($data);
    }    



    /**
     * Berechnet eine Sammlung statistischer Kennzahlen für eine Spalte.
     *
     * Gibt wahlweise eine Statistik je Gruppe (`groupBy`) oder eine Gesamtauswertung zurück.
     *
     * @param string $column Die Spalte, auf der die Statistiken basieren sollen (numerisch).
     * @return array|float|null Statistiken als assoziatives Array oder gruppiert nach Schlüssel.
     */
    public function stats(string $column): array|float|null {
        $result = [];

        $result['count']         = $this->count();
        $result['countDistinct'] = $this->countDistinct($column);
        $result['sum']           = $this->sum($column);
        $result['avg']           = $this->avg($column);
        $result['min']           = $this->min($column);
        $result['max']           = $this->max($column);
        $result['range']         = $this->range($column);
        $result['median']        = $this->median($column);
        $result['mode']          = $this->mode($column);
        $result['variance']      = $this->variance($column);
        $result['stddev']        = $this->stddev($column);

        return $result;
    }








}