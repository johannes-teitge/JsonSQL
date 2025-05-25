<?php
namespace Src\JsonSQL;

trait JS_Joins
{

// JOIN Method
public function join(string $table, string|array $onColumn, string $joinType = 'INNER'): self {
    $validJoinTypes = ['INNER', 'LEFT', 'RIGHT', 'FULL OUTER'];
    if (!in_array(strtoupper($joinType), $validJoinTypes)) {
        throw new \Exception("Ungültiger JOIN-Typ: $joinType");
    }

    $joinTableFile = $this->currentDbPath . DIRECTORY_SEPARATOR . $table . '.json';
    if (!file_exists($joinTableFile)) {
        throw new \Exception("Tabelle '$table' existiert nicht.");
    }

    $joinData = json_decode(file_get_contents($joinTableFile), true);

    // 🔁 Felder für Vergleich ermitteln
    if (is_array($onColumn)) {
        $localKey = $onColumn['local'] ?? null;
        $foreignKey = $onColumn['foreign'] ?? null;
        if (!$localKey || !$foreignKey) {
            throw new \Exception("Beim JOIN mit Array muss 'local' und 'foreign' gesetzt sein.");
        }
    } else {
        $localKey = $foreignKey = $onColumn;
    }

    $this->joinedTables[] = [
        'table'       => $table,
        'onColumn'    => $localKey,
        'foreignKey'  => $foreignKey,
        'joinData'    => $joinData,
        'joinType'    => strtoupper($joinType),
    ];

    return $this;
}



protected function applyJoins_(array $data): array {
    foreach ($this->joinedTables as $join) {
        $joinData = $join['joinData'];
        $onColumn = $join['onColumn'];
        $foreignKey = $join['foreignKey'] ?? $onColumn;
        $joinType = $join['joinType'];

        $newData = [];

        foreach ($data as $row) {
            $matched = false;

            foreach ($joinData as $joinRow) {
                // Check if there's a match on the join condition
                if (isset($row[$onColumn]) && isset($joinRow[$foreignKey]) && $row[$onColumn] == $joinRow[$foreignKey]) {
                    $newRow = array_merge($row, $joinRow);
                    $newData[] = $newRow;
                    $matched = true;
                    break; // Stop after the first match (for INNER JOIN logic)
                }
            }

            if (!$matched && $joinType !== 'INNER') {
                // If no match found and it's not INNER JOIN, include the row with nulls or defaults
                if ($joinType === 'LEFT' || $joinType === 'FULL OUTER') {
                    $newData[] = array_merge($row, array_fill_keys(array_keys($joinData[0]), null));
                }
            }
        }

        $data = $newData;
    }

    return $data;
}






protected function applyJoins(array $data): array {
    foreach ($this->joinedTables as $join) {
        $joinData   = $join['joinData'];
        $onColumn   = $join['onColumn'];
        $foreignKey = $join['foreignKey'] ?? $onColumn;
        $joinType   = $join['joinType'];

        if ($joinType === 'LEFT') {
            foreach ($data as &$row) {
                $matched = false;
                foreach ($joinData as $joinRow) {
                    if (isset($row[$onColumn]) && isset($joinRow[$foreignKey]) && $row[$onColumn] == $joinRow[$foreignKey]) {
                        $row = array_merge($row, $joinRow);
                        $matched = true;
                        break;
                    }
                }
                if (!$matched && !empty($joinData)) {
                    // Überprüfen, ob $data leer ist, bevor wir auf das erste Element zugreifen
                    $keys = !empty($data) && is_array($data[0]) ? array_keys($data[0]) : [];
                    $row = array_merge($row, array_fill_keys($keys, null));
                }
            }
        } elseif ($joinType === 'RIGHT') {
            $newData = [];
            foreach ($joinData as $joinRow) {
                $matched = false;
                foreach ($data as $row) {
                    if (isset($row[$onColumn]) && isset($joinRow[$foreignKey]) && $row[$onColumn] == $joinRow[$foreignKey]) {
                        $newData[] = array_merge($row, $joinRow);
                        $matched = true;
                        break;
                    }
                }
                if (!$matched && !empty($data)) {
                    // Überprüfen, ob $data leer ist, bevor wir auf das erste Element zugreifen
                    $keys = !empty($data) && is_array($data[0]) ? array_keys($data[0]) : [];
                    $newData[] = array_merge(array_fill_keys($keys, null), $joinRow);
                }
            }
            $data = $newData;
        } elseif ($joinType === 'FULL OUTER') {
            $leftData = [];
            foreach ($data as &$row) {
                $matched = false;
                foreach ($joinData as $joinRow) {
                    if (isset($row[$onColumn]) && isset($joinRow[$foreignKey]) && $row[$onColumn] == $joinRow[$foreignKey]) {
                        $row = array_merge($row, $joinRow);
                        $matched = true;
                        break;
                    }
                }
                if (!$matched && !empty($joinData)) {
                    // Überprüfen, ob $data leer ist, bevor wir auf das erste Element zugreifen
                    $keys = !empty($data) && is_array($data[0]) ? array_keys($data[0]) : [];
                    $row = array_merge($row, array_fill_keys($keys, null));
                }
                $leftData[] = $row;
            }

            $rightData = [];
            foreach ($joinData as $joinRow) {
                $matched = false;
                foreach ($data as $row) {
                    if (isset($row[$onColumn]) && isset($joinRow[$foreignKey]) && $row[$onColumn] == $joinRow[$foreignKey]) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched && !empty($data)) {
                    // Überprüfen, ob $data leer ist, bevor wir auf das erste Element zugreifen
                    $keys = !empty($data) && is_array($data[0]) ? array_keys($data[0]) : [];
                    $rightData[] = array_merge(array_fill_keys($keys, null), $joinRow);
                }
            }

            $data = array_merge($leftData, $rightData);
        } else { // INNER JOIN
            $newData = [];
            foreach ($data as $row) {
                foreach ($joinData as $joinRow) {
                    if (isset($row[$onColumn]) && isset($joinRow[$foreignKey]) && $row[$onColumn] == $joinRow[$foreignKey]) {
                        $newData[] = array_merge($row, $joinRow);
                        break;
                    }
                }
            }
            $data = $newData;
        }
    }

    return $data;
}





}