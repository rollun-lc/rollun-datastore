## 11.0.0

- Removed `rollun\datastore\Cleaner` because it wasn't used anywhere and, furthermore, it depended on
  `rollun\utils\Cleaner`, which has been removed in the latest version of 'rollun-com/rollun-utils'.
- Removed rollun-com/rollun-installer as it was unused
- Removed `rollun\files` as it was moved to another package
- Make "count(): int" and "getIterator(): \Traversable" functions typed in DataStoreAbstract