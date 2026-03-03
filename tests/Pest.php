<?php

use ilsawn\LaravelIlsawn\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

// ---------------------------------------------------------------------------
// Shared helpers
// ---------------------------------------------------------------------------

/**
 * Write rows to a CSV file using the given delimiter.
 * Each item in $rows is a plain array of column values (including the header).
 *
 * @param array<int, array<int, string>> $rows
 */
function writeCsvFile(string $path, array $rows, string $delimiter = ';'): void
{
    $handle = fopen($path, 'w');

    if ($handle === false) {
        throw new \RuntimeException("Cannot open for writing: {$path}");
    }

    foreach ($rows as $row) {
        fputcsv($handle, $row, $delimiter);
    }

    fclose($handle);
}
