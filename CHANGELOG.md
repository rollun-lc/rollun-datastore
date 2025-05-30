## 11.4.0 and 6.12.0

- Added data-store JSON schem**a** feauture: [docs](docs/datastore-schema.md)
- [Deprecated](/src/DataStore/src/DataStore/Scheme/README.md) rollun\datastore\DataStore\Schem**e**

## 11.0.0

- Support php 8.1
- Removed `rollun\datastore\Cleaner` because it wasn't used anywhere and, furthermore, it depended on
  `rollun\utils\Cleaner`, which has been removed in the latest version of 'rollun-com/rollun-utils'.
- Removed rollun-com/rollun-installer as it was unused
- Removed `rollun\files` as it was moved to another package
- Make "count(): int" and "getIterator(): \Traversable" functions typed in DataStoreAbstract