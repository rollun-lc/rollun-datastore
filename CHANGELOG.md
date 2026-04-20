## 10.4.0 and 6.12.0

- Added data-store JSON schem**a** feauture: [docs](docs/datastore-schema.md)
- [Deprecated](/src/DataStore/src/DataStore/Scheme/README.md) rollun\datastore\DataStore\Schem**e**

## 11.0.0

- Support php 8.1
- Removed `rollun\datastore\Cleaner` because it wasn't used anywhere and, furthermore, it depended on
  `rollun\utils\Cleaner`, which has been removed in the latest version of 'rollun-com/rollun-utils'.
- Removed rollun-com/rollun-installer as it was unused
- Removed `rollun\files` as it was moved to another package
- Make "count(): int" and "getIterator(): \Traversable" functions typed in DataStoreAbstract

## 12.0.0

CSV datastore overhaul: RFC 4180 compliance, atomicity, embedded-newline support,
BOM handling, PHP 8.1+ deprecation cleanup, and merge of capabilities previously
in the separate `rollun-files` package. Full updated contract:
[docs/index.md#2-csvbase](docs/index.md#2-csvbase).

### Breaking changes

- **CSV files now use RFC 4180 escape format on write** (`""` doubling) instead
  of the PHP `fputcsv` default backslash escape (`\"`). Files written by older
  versions are still readable IF they don't contain a literal `"` in any field.
  Files that DO contain literal quotes must be migrated once via
  `php bin/migrate-csv-escape.php <file.csv>` (supports `--dry-run`).
- `CsvBase::getTrueRow` now throws `DataStoreException` when a row's field count
  doesn't match the column count (previously: untyped PHP `ValueError` from
  `array_combine`).
- `Iterators\CsvIterator` Iterator method signatures now declare proper return
  types (`mixed` / `void` / `bool`). Subclasses must use compatible signatures.
- Classic Mac (`\r`-only) line endings are explicitly **unsupported**. Pre-convert
  with `tr '\r' '\n' < in.csv > out.csv`.

### Fixes

- **Embedded newlines (`\n` / `\r\n`) inside quoted fields are now preserved on
  read.** `CsvBase` no longer sets `SplFileObject::DROP_NEW_LINE`, which
  previously stripped row terminators AND newlines inside quoted values, silently
  corrupting multi-line cells.
- **Atomic writes via `rename(2)` instead of `copy()`.** `CsvBase::flush`,
  `CsvBase::deleteAll` and the (now-deduplicated) `CsvIntId` write path build
  the replacement file as a sibling of the destination and rename it over the
  original — POSIX-atomic on the same filesystem. Original file mode is
  preserved.
- **UTF-8 BOM (`\xEF\xBB\xBF`) is now stripped from the first column header on
  read.** Excel-Win and several other CSV producers add this — without
  stripping, the first column key became `"\xEF\xBB\xBFid"` and broke every
  subsequent column lookup.
- `CsvBase::getTrueRow` no longer emits PHP 8.1+ `Passing null to strlen()`
  deprecation when reading rows containing empty fields.
- `Iterators\CsvIterator` and `CsvBase::read` now use the same RFC escape mode,
  so they return identical values for any well-formed input. Previously the
  iterator and the direct read path diverged on quoted fields.
- `DownloadCsvHandler` writes files in RFC mode too. **BC note:** the public
  constant `DownloadCsvHandler::ESCAPE_CHAR` changed value from `'\\'` to
  `''` (empty string selects PHP's RFC 4180 fputcsv mode). Consumers reading
  this constant to drive their own `fputcsv()` calls will get different byte
  output for fields that contain literal `"` characters.
- `DownloadCsvHandler` now emits a CSV header row (column names) before the
  first data row. Previously the output had no header, which was unusable
  in Excel / Google Sheets. The header is derived from `array_keys()` of
  the first row returned by the underlying datastore. Callers that
  post-processed the header-less output must update their parsers.
- `DownloadCsvHandler` no longer mutates the caller's RQL query object. It
  clones the query before setting its own pagination limit, so middleware
  sharing the same query across handlers is no longer polluted.
- 0-byte CSV files are handled gracefully (`count()` returns `0` instead of
  `-1`; `read()` returns `null`).
- `CsvIntId::generatePrimaryKey` rewinds explicitly and skips the header,
  removing the previous dependency on prior file pointer state.

### Refactor

- `CsvIntId::flush` was deduplicated against `CsvBase::flush` (~50 lines
  removed). Sort-aware insertion is now controlled by a single
  `shouldInsertItemBefore` hook on `CsvBase`, which `CsvIntId` overrides.

### Test scaffolding

- New CSV test suite under `test/unit/DataStore/DataStore/Csv/` covering line
  endings (LF/CRLF/CR/mixed/BOM), quoted fields (delimiter inside, RFC `""`,
  embedded newlines), CsvBase ↔ CsvIterator consistency, atomic writes,
  CsvIntId sort invariants, ragged rows, type round-trips
  (NULL/empty/bool/int/float/leading-zero), UTF-8 multibyte
  (Cyrillic/CJK/emoji), and the abstract factory.
- New `Csv/Support/AssertsNoDeprecationsTrait` for per-test deprecation
  capture (`test/bootstrap.php` silences `E_DEPRECATED` globally).
- Fixed `test/intagration/DataStore/CsvIntIdTest` which was silently running
  `BaseDataStoreTest` against `CsvBase` due to a missing `createObject` override.

### Migration

Run `php bin/migrate-csv-escape.php <file.csv>` once per legacy CSV file that
contains literal `"` characters in any field. Supports `--delimiter=,` and
`--dry-run`. The script writes the converted content atomically and preserves
the original file's mode.

**Operator note:** the migration script does not have an automated test
suite. For small deployments (10-20 files), manual verification via
`--dry-run` followed by an inspect-diff workflow is recommended:

```bash
# 1. Count rows that need migration (those containing literal quotes).
php bin/migrate-csv-escape.php data/file.csv --dry-run

# 2. Migrate, preserving a backup for sanity comparison.
cp data/file.csv data/file.csv.bak
php bin/migrate-csv-escape.php data/file.csv

# 3. Spot-check: the row count must match, and reads via the new CsvBase
#    must produce the same logical content as reads via the old reader on
#    the backup.
```

### Known limitations (12.0.0)

These are deliberate non-goals for this release, documented so operators know
what to avoid:

- **Multi-process concurrent writers to the same CSV file.** The new atomic
  `rename(2)`-based flush protects against crash mid-write but does NOT
  guarantee strict cross-process write serialization, because POSIX `flock`
  is attached to the inode, and `rename()` swaps the inode. In practice this
  is only observable if multiple long-running processes (e.g. cron + web
  workers) write the same file simultaneously — the standard PHP-FPM
  per-request lifecycle is unaffected. If you need strict cross-process
  serialization, add an external lockfile at the application layer. A
  lockfile-based serialization hook in CsvBase itself is planned for a
  future minor release if demand emerges.

- **CSV injection / formula injection in exported files.** Cells whose
  value begins with `=`, `+`, `-`, `@`, `\t`, or `\r` are interpreted as
  formulas when the CSV is opened in Excel / LibreOffice / Google Sheets
  (OWASP "CSV Injection", CWE-1236). `CsvBase` and `DownloadCsvHandler`
  do not sanitize on ingest or export. If you serve CSV downloads to
  untrusted users (public URLs, unauthenticated endpoints, sharing
  outside your organization), add a pre-export sanitizer at your
  application layer that prefixes such cells with a single quote.
  Within-team and authenticated-internal use cases are considered
  in-scope for the current design.

- **Classic Mac (CR-only) line endings** are explicitly unsupported —
  PHP 8.1+ removed `auto_detect_line_endings` and our reader is built on
  `fgetcsv`. Pre-convert with `tr '\r' '\n' < in.csv > out.csv`.

- **Encodings other than UTF-8.** Pre-convert via `iconv` or
  `mb_convert_encoding` before opening the file with `CsvBase`.

- **Unbounded response size in `DownloadCsvHandler`.** Exports stream
  through `php://temp` (spills to disk >2 MB) with no row-count cap. For
  datasets in the millions of rows, add a row budget at the routing /
  handler layer.
