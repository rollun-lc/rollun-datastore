<?php

declare(strict_types=1);

/**
 * One-time migration: rewrite a CSV file from PHP-legacy backslash escape
 * to RFC 4180 double-quote escape.
 *
 * Background
 * ----------
 * Before the CSV merge, rollun-datastore's CsvBase wrote files via the
 * native PHP fputcsv() default, which uses backslash escape:
 *
 *     foo "bar" baz   →   "foo \"bar\" baz"
 *
 * After the merge, CsvBase writes (and reads) RFC 4180 double-quote escape:
 *
 *     foo "bar" baz   →   "foo ""bar"" baz"
 *
 * Files written by the old code are still readable by the new code IF AND
 * ONLY IF they don't contain a literal " character inside any field. If
 * they do, this script must be run once to convert them.
 *
 * Usage
 * -----
 *   php bin/migrate-csv-escape.php <file.csv> [--delimiter=,] [--dry-run]
 *
 *   --delimiter=X   field delimiter (default: ,)
 *   --dry-run       parse and validate but don't overwrite the file
 *
 * The script writes the converted content atomically (sibling tmp + rename).
 * The original file's permissions are preserved.
 *
 * Limitations
 * -----------
 * - Reads the input with PHP's legacy escape interpretation. Fields whose
 *   content happens to look like a backslash-escape sequence will be
 *   interpreted accordingly. If your data legitimately contains a literal
 *   `\"` two-byte sequence (backslash followed by quote), the script will
 *   produce a single `"` after migration. Inspect a sample of converted
 *   rows before deploying.
 * - Single-pass: do not run twice on the same file.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(2);
}

array_shift($argv); // drop script name

$file = null;
$delimiter = ',';
$dryRun = false;

foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $dryRun = true;
    } elseif (str_starts_with($arg, '--delimiter=')) {
        $delimiter = substr($arg, strlen('--delimiter='));
        if (strlen($delimiter) !== 1) {
            fwrite(STDERR, "delimiter must be a single character\n");
            exit(2);
        }
    } elseif ($file === null) {
        $file = $arg;
    } else {
        fwrite(STDERR, "Unexpected argument: $arg\n");
        exit(2);
    }
}

if ($file === null) {
    fwrite(STDERR, "Usage: php bin/migrate-csv-escape.php <file.csv> [--delimiter=,] [--dry-run]\n");
    exit(2);
}

if (!is_file($file) || !is_readable($file)) {
    fwrite(STDERR, "File not found or not readable: $file\n");
    exit(2);
}

$dir = dirname($file);
if (!$dryRun && !is_writable($dir)) {
    fwrite(STDERR, "Directory not writable (needed for atomic rename): $dir\n");
    exit(2);
}

// ---------------------------------------------------------------------------
// Read with legacy backslash escape, write with RFC empty escape.
// ---------------------------------------------------------------------------

$in = fopen($file, 'r');
if ($in === false) {
    fwrite(STDERR, "Failed to open input: $file\n");
    exit(1);
}

if ($dryRun) {
    $out = fopen('php://memory', 'w+');
} else {
    $tmp = tempnam($dir, 'csv_migrate_');
    if ($tmp === false) {
        fwrite(STDERR, "Failed to create tmp file in $dir\n");
        fclose($in);
        exit(1);
    }
    $out = fopen($tmp, 'w');
    if ($out === false) {
        fwrite(STDERR, "Failed to open tmp for writing: $tmp\n");
        @unlink($tmp);
        fclose($in);
        exit(1);
    }
}

$rowsConverted = 0;
$rowsWithQuotes = 0;

while (($row = fgetcsv($in, 0, $delimiter, '"', '\\')) !== false) {
    if ($row === [null]) {
        continue; // skip blank lines
    }

    foreach ($row as $field) {
        if (is_string($field) && str_contains($field, '"')) {
            $rowsWithQuotes++;
            break;
        }
    }

    fputcsv($out, $row, $delimiter, '"', '');
    $rowsConverted++;
}

fclose($in);

if ($dryRun) {
    fclose($out);
    fwrite(STDOUT, sprintf(
        "[dry-run] would convert %d rows (%d contain a literal quote)\n",
        $rowsConverted,
        $rowsWithQuotes,
    ));
    exit(0);
}

fclose($out);

// Preserve original file mode across the atomic replace.
$originalMode = @fileperms($file);
if ($originalMode !== false) {
    @chmod($tmp, $originalMode & 0777);
}

if (!rename($tmp, $file)) {
    @unlink($tmp);
    fwrite(STDERR, "Failed to atomically replace $file\n");
    exit(1);
}

fwrite(STDOUT, sprintf(
    "Converted %d rows (%d contained a literal quote) in %s\n",
    $rowsConverted,
    $rowsWithQuotes,
    $file,
));

exit(0);
