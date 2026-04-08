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
- `DownloadCsvHandler` writes files in RFC mode too (was hardcoded to legacy
  backslash escape via `ESCAPE_CHAR = '\\'`).
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
