<?php

namespace App\Services\Migration;

use RuntimeException;

class LegacySqlParser
{
    /**
     * Locate and return the contents of the legacy SQL dump file.
     */
    public function findSqlFilePath(?string $customPath = null): string
    {
        if ($customPath && file_exists($customPath)) {
            return $customPath;
        }

        $candidates = [
            base_path('public/old-application/u967843851_examtube.sql'),
            base_path('public/old-examtube/u967843851_examtube.sql'),
            base_path('public/old-application/examtube.sql'),
            base_path('public/old-examtube/examtube.sql'),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Glob fallback inside public/old-application or public/old-examtube
        $directories = [base_path('public/old-application'), base_path('public/old-examtube')];
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*.sql');
                if (! empty($files)) {
                    return $files[0];
                }
            }
        }

        throw new RuntimeException("Legacy SQL dump file not found in 'public/old-application/' or 'public/old-examtube/'.");
    }

    /**
     * Parse SQL file into an associative array of table data rows.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function parseFile(string $sqlFilePath): array
    {
        if (! file_exists($sqlFilePath)) {
            throw new RuntimeException("SQL file does not exist: {$sqlFilePath}");
        }

        $content = file_get_contents($sqlFilePath);
        if ($content === false) {
            throw new RuntimeException("Unable to read SQL file: {$sqlFilePath}");
        }

        return $this->parseSqlContent($content);
    }

    /**
     * Parse raw SQL string and extract table data rows using quote-aware parsing.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function parseSqlContent(string $sqlContent): array
    {
        $tables = [];
        $offset = 0;
        $length = strlen($sqlContent);

        while ($offset < $length) {
            $matched = preg_match('/INSERT INTO `([^`]+)` \(([^)]+)\) VALUES/i', $sqlContent, $match, PREG_OFFSET_CAPTURE, $offset);
            if (! $matched) {
                break;
            }

            $tableName = $match[1][0];
            $colString = $match[2][0];
            $matchStart = $match[0][1];
            $valuesStart = $matchStart + strlen($match[0][0]);

            preg_match_all('/`([^`]+)`/', $colString, $colMatches);
            $columns = $colMatches[1];

            // Find the real terminating semicolon outside string literals
            $inString = false;
            $quoteChar = null;
            $i = $valuesStart;
            $valuesBlock = '';

            while ($i < $length) {
                $char = $sqlContent[$i];

                if ($inString) {
                    if ($char === '\\') {
                        $valuesBlock .= $char . ($sqlContent[$i + 1] ?? '');
                        $i += 2;
                        continue;
                    }
                    if ($char === $quoteChar) {
                        if (($sqlContent[$i + 1] ?? '') === $quoteChar) {
                            $valuesBlock .= $quoteChar . $quoteChar;
                            $i += 2;
                            continue;
                        }
                        $inString = false;
                        $quoteChar = null;
                    }
                    $valuesBlock .= $char;
                    $i++;
                    continue;
                }

                if ($char === "'" || $char === '"') {
                    $inString = true;
                    $quoteChar = $char;
                    $valuesBlock .= $char;
                    $i++;
                    continue;
                }

                if ($char === ';') {
                    $i++; // move past ';'
                    break;
                }

                $valuesBlock .= $char;
                $i++;
            }

            $offset = $i;

            $rows = $this->parseValuesTuples($valuesBlock);
            if (! isset($tables[$tableName])) {
                $tables[$tableName] = [];
            }

            foreach ($rows as $rowValues) {
                if (count($rowValues) === count($columns)) {
                    $tables[$tableName][] = array_combine($columns, $rowValues);
                }
            }
        }

        return $tables;
    }

    /**
     * Parse SQL VALUES tuple string into array of rows with array of values.
     *
     * @return list<list<mixed>>
     */
    protected function parseValuesTuples(string $valuesBlock): array
    {
        $rows = [];
        $length = strlen($valuesBlock);
        $inTuple = false;
        $inString = false;
        $quoteChar = null;
        $currentValue = '';
        $currentRow = [];
        $i = 0;

        while ($i < $length) {
            $char = $valuesBlock[$i];

            if (! $inTuple) {
                if ($char === '(') {
                    $inTuple = true;
                    $currentRow = [];
                    $currentValue = '';
                    $inString = false;
                    $quoteChar = null;
                }
                $i++;
                continue;
            }

            if ($inString) {
                if ($char === '\\') {
                    $nextChar = $valuesBlock[$i + 1] ?? '';
                    $currentValue .= match ($nextChar) {
                        'n' => "\n",
                        'r' => "\r",
                        't' => "\t",
                        "'" => "'",
                        '"' => '"',
                        '\\' => '\\',
                        default => $nextChar,
                    };
                    $i += 2;
                    continue;
                }

                if ($char === $quoteChar) {
                    if (($valuesBlock[$i + 1] ?? '') === $quoteChar) {
                        $currentValue .= $quoteChar;
                        $i += 2;
                        continue;
                    }
                    $inString = false;
                    $quoteChar = null;
                    $i++;
                    continue;
                }

                $currentValue .= $char;
                $i++;
                continue;
            }

            if ($char === "'" || $char === '"') {
                $inString = true;
                $quoteChar = $char;
                $i++;
                continue;
            }

            if ($char === ',') {
                $currentRow[] = $this->castSqlValue($currentValue);
                $currentValue = '';
                $i++;
                continue;
            }

            if ($char === ')') {
                $currentRow[] = $this->castSqlValue($currentValue);
                $rows[] = $currentRow;
                $inTuple = false;
                $currentRow = [];
                $currentValue = '';
                $i++;
                continue;
            }

            $currentValue .= $char;
            $i++;
        }

        return $rows;
    }

    /**
     * Cast raw SQL string value to PHP scalar/null.
     */
    protected function castSqlValue(string $val): mixed
    {
        $trimmed = trim($val);
        if ($trimmed === 'NULL' || $trimmed === 'null') {
            return null;
        }

        if (is_numeric($trimmed)) {
            if (str_contains($trimmed, '.')) {
                return (float) $trimmed;
            }
            return (int) $trimmed;
        }

        return $val;
    }
}
