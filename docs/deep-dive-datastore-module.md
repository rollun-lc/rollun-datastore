# DataStore Module - Deep Dive Documentation

**Generated:** 2025-12-05
**Scope:** src/DataStore/src (excluding Rql/)
**Files Analyzed:** 146
**Lines of Code:** ~12,537
**Workflow Mode:** Exhaustive Deep-Dive

## Overview

The DataStore module is the **core domain module** of the rollun-datastore library. It implements a sophisticated data abstraction layer supporting multiple storage backends (MySQL, CSV, HTTP, Memory) with a rich feature set including RQL query language integration, aspect-oriented programming for cross-cutting concerns, REST API middleware, and comprehensive type system.

**Purpose:** Provide unified CRUD interface for multiple data storage backends with advanced querying, type safety, and HTTP REST API.

**Key Responsibilities:**
- Unified data access interface (DataStoreInterface)
- Multiple storage backend implementations (DbTable, Memory, CSV, HTTP)
- RQL query language integration for advanced filtering/sorting/aggregation
- Aspect-oriented programming for cross-cutting concerns (typing, validation, read-only)
- REST API middleware for HTTP access to datastores
- Type system with conversion and validation
- Schema management and validation

**Integration Points:**
- RQL Module (query parsing and execution)
- Laminas Framework (database, HTTP, DI, middleware)
- External datastores via HTTP client
- File system for CSV storage

---

## File Distribution & Architecture

### Module Statistics

```
DataSource Module:    4 files
DataStore Module:   109 files
Middleware Module:   22 files
TableGateway Module:  8 files
Root Level:           3 files
------------------------
Total:              146 files (~12,537 LOC)
```

### Component Breakdown

#### Core DataStore Implementations (8 files)

- **DataStoreAbstract.php** (628 LOC) - Base implementation with query processing
- **DbTable.php** (750 LOC) - MySQL/database backed datastore
- **Memory.php** (214 LOC) - In-memory array-backed datastore
- **HttpClient.php** (494 LOC) - HTTP REST client datastore
- **CsvBase.php** (581 LOC) - CSV file-backed datastore with file locking
- **CsvIntId.php** - CSV with integer ID support
- **SerializedDbTable.php** (48 LOC) - Serializable DbTable wrapper
- **Cacheable.php** (225 LOC) - Caching decorator for datastores

#### Aspect System (11 files)

- **AspectAbstract.php** (562 LOC) - Decorator pattern base with pre/post hooks
- **AspectReadOnly.php** - Read-only enforcement aspect
- **AspectTyped.php** - Type enforcement with DTO conversion
- **AspectSchema.php** - Schema validation aspect
- **AspectEntityMapper.php** - Entity mapping aspect
- **AspectModifyTable.php** - Table modification aspect
- **AbstractMapperAspect.php** - Base mapper aspect
- **AspectWithEventManagerAbstract.php** - Event manager integration
- **AbstractAspectListener.php** - Event listener support
- **Aspect/Factory/** (2 factories)

#### Interfaces (10 files)

- **DataStoreInterface.php** - Full CRUD interface with RQL queries
- **DataStoresInterface.php** - Legacy CRUD interface
- **ReadInterface.php** - Read-only operations (extends Countable, IteratorAggregate)
- **DbTableInterface.php** - Database-specific interface
- **SchemableInterface.php** - Schema support
- **RefreshableInterface.php** - Cache refresh capability
- **DateTimeInterface.php** - DateTime handling
- **SqlQueryGetterInterface.php** - SQL query access
- **TreeReadInterface.php** - Hierarchical data reading
- **WithEventManagerInterface.php** - Event manager contract

#### Condition Builders (5 files)

- **ConditionBuilderAbstract.php** - Base for query condition building
- **PhpConditionBuilder.php** - PHP eval-based conditions
- **RqlConditionBuilder.php** - RQL to condition converter
- **SqlConditionBuilder.php** - RQL to SQL WHERE converter
- **SqlConditionBuilderAbstractFactory.php** - Factory

#### Type System (12 files)

- **TypeInterface.php** - Type conversion interface
- **TypeAbstract.php** - Base type implementation
- Concrete types: **TypeInt**, **TypeFloat**, **TypeString**, **TypeBoolean**, **TypeChar**, **TypeJson**, **TypeDateTimeImmutable**
- **TypeFactory.php**, **TypePluginManager.php**
- **TypeException.php**

#### Formatter System (12 files)

- **FormatterInterface.php** - Data formatting contract
- **AbstractFormatter.php** - Base formatter
- Concrete formatters: **IntFormatter**, **FloatFormatter**, **StringFormatter**, **BooleanFormatter**, **CharFormatter**, **JsonFormatter**, **JsonOrNullFormatter**, **DateTimeOrNullFormatter**, **NullFormatter**
- **FormatterPluginManager.php**

#### Middleware (22 files)

**Core Middleware:**
- **DataStoreApi.php** (67 LOC) - Main API middleware pipeline
- **DataStoreRest.php** (57 LOC) - REST endpoint routing
- **DataStoreAbstract.php** - Base middleware class
- **Determinator.php** - Request routing logic
- **ResourceResolver.php** - Resource name resolution
- **RequestDecoder.php** - Request payload decoding
- **JsonRenderer.php** - JSON response rendering
- **RestException.php** - REST-specific exceptions

**HTTP Handlers (12 files):**
- **AbstractHandler.php** - Base handler
- **QueryHandler.php** - GET with RQL query
- **ReadHandler.php** - GET by ID
- **CreateHandler.php** - POST create
- **UpdateHandler.php** - PUT update
- **DeleteHandler.php** - DELETE
- **MultiCreateHandler.php** - Batch create
- **QueriedUpdateHandler.php** - PATCH queried update
- **HeadHandler.php** - HEAD metadata
- **RefreshHandler.php** - Cache refresh
- **DownloadCsvHandler.php** - CSV export
- **ErrorHandler.php** - Error handling

**Factories (2 files):**
- **DataStoreApiFactory.php**
- **DeterminatorFactory.php**

#### TableGateway (8 files)

- **TableManagerMysql.php** (593 LOC) - MySQL table creation/management
- **SqlQueryBuilder.php** (179 LOC) - RQL to SQL SELECT converter
- **DbSql/MultiInsert.php** - Batch insert support
- **DbSql/MultiInsertSql.php** - Multi-insert SQL generation
- **Column/Json.php** - JSON column type
- **Factory/TableGatewayAbstractFactory.php**
- **Factory/SqlQueryBuilderAbstractFactory.php**
- **Factory/TableManagerMysqlFactory.php**

#### Supporting Components

**Schema System (6 files):**
- **SchemasRepositoryInterface.php** - Schema storage contract
- **ArraySchemaRepository.php** - Array-based schema repository
- **SchemaApiRequestHandler.php** - HTTP API for schemas

**Scheme System (7 files):**
- **Scheme.php** - Schema definition class
- **FieldInfo.php** - Field metadata
- **Getter.php**, **MethodGetter.php**, **PropertyGetter.php** - Value extraction
- **TypeFactory.php**, **PluginManagerTypeFactory.php**

**Query Adapters (4 files):**
- **AbstractQueryAdapter.php** - Query adaptation base
- **QueryAdapter.php** - Standard query adapter
- **MultipleQueryAdapter.php** - Multiple query handling
- **NullQueryAdapter.php** - Null object pattern

**Traits (14 files):**
- **NoSupport*Trait.php** (9 files) - Disable specific CRUD operations
- **AutoIdGeneratorTrait.php** - Auto ID generation
- **DateTimeTrait.php** - DateTime utilities
- **FieldsTrait.php** - Field management
- **MappingFieldsTrait.php** - Field mapping
- **PrepareFieldsTrait.php** - Field preparation (deprecated)
- **JsonAutoSerializer.php** - JSON serialization

**Exceptions (7 files):**
- **DataStoreException.php** - Base exception
- **DataStoreServerException.php** - Server error exception
- **ConnectionException.php** - Connection failures
- **OperationTimedOutException.php** - Timeout errors
- **LaminasDbExceptionDetector.php** - Exception classification

---

## Architecture & Design Patterns

### Interface Hierarchy

```
ReadInterface (Countable, IteratorAggregate)
├── getIdentifier(): string
├── read($id): ?array
├── has($id): bool
├── query(Query): array
└── count(): int

DataStoresInterface extends ReadInterface
├── create($itemData, $rewriteIfExist): array
├── update($itemData, $createIfAbsent): array
├── delete($id): array|null
└── deleteAll(): int|null

DataStoreInterface extends ReadInterface
├── create($record): array
├── multiCreate($records): array
├── update($record): array
├── multiUpdate($records): array
├── queriedUpdate($record, Query): array
├── rewrite($record): array
├── delete($id): array
└── queriedDelete(Query): array
```

**⚠️ Technical Debt**: Two parallel interface hierarchies coexist:
- **DataStoresInterface** - Legacy interface with optional rewrite/create flags
- **DataStoreInterface** - Modern interface with explicit operations

### Class Hierarchy

```
DataStoreAbstract (implements both DataStoresInterface & DataStoreInterface)
├── DbTable - MySQL/database backend
│   └── SerializedDbTable - Serializable version
├── Memory - In-memory array backend
├── HttpClient - HTTP REST client backend
└── CsvBase - CSV file backend
    └── CsvIntId - CSV with integer IDs

AspectAbstract (Decorator pattern)
├── AspectReadOnly - Disable write operations
├── AspectTyped - Type enforcement + DTO conversion
├── AspectSchema - Schema validation
├── AspectEntityMapper - Entity mapping
├── AspectModifyTable - Table structure changes
└── AspectWithEventManagerAbstract - Event integration

Cacheable (implements DataStoresInterface, RefreshableInterface)
└── Wraps DataSource with Memory cache
```

### Design Patterns Used

1. **Abstract Factory Pattern**
   - Multiple abstract factories for different datastore types
   - Integration with Laminas ServiceManager for DI
   - Files: `Factory/DbTableAbstractFactory.php`, `Factory/MemoryAbstractFactory.php`, etc.

2. **Decorator Pattern** (Aspect system)
   - `AspectAbstract` wraps any DataStore
   - Pre/post hooks for all CRUD operations
   - Composable cross-cutting concerns
   - Files: `Aspect/AspectAbstract.php`, `Aspect/AspectTyped.php`, etc.

3. **Strategy Pattern** (ConditionBuilder)
   - `PhpConditionBuilder` - Generates eval'd PHP conditions
   - `SqlConditionBuilder` - Generates SQL WHERE clauses
   - `RqlConditionBuilder` - RQL to string conversion
   - Files: `ConditionBuilder/*.php`

4. **Template Method Pattern**
   - `DataStoreAbstract` provides query processing template
   - Subclasses implement abstract create/update/delete
   - File: `DataStoreAbstract.php`

5. **Plugin Manager Pattern**
   - `TypePluginManager` - Type converters
   - `FormatterPluginManager` - Data formatters
   - `DataStorePluginManager` - DataStore instances
   - Files: `Type/TypePluginManager.php`, `Formatter/FormatterPluginManager.php`

6. **Middleware Pipeline Pattern**
   - `DataStoreApi` - Sequential middleware processing
   - `DataStoreRest` - Handler chain of responsibility
   - Files: `Middleware/DataStoreApi.php`, `Middleware/DataStoreRest.php`

7. **Null Object Pattern**
   - `NullQueryAdapter` - No-op query adapter
   - File: `Query/NullQueryAdapter.php`

8. **Iterator Pattern**
   - `DataStoreIterator` - Iterates over datastore items
   - `CsvIterator` - Lazy CSV file iteration
   - Files: `Iterators/DataStoreIterator.php`, `Iterators/CsvIterator.php`

### Layering Structure

```
┌─────────────────────────────────────────────────┐
│  HTTP Layer (Middleware)                        │
│  - DataStoreApi, DataStoreRest                  │
│  - Handlers (CRUD operations)                   │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│  Aspect Layer (Cross-cutting concerns)          │
│  - AspectTyped, AspectReadOnly, AspectSchema    │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│  DataStore Layer (Business Logic)               │
│  - DataStoreAbstract                            │
│  - Query processing, aggregate functions        │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│  Storage Layer (Persistence)                    │
│  - DbTable, Memory, CsvBase, HttpClient         │
│  - ConditionBuilders, QueryBuilders             │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│  Infrastructure Layer                           │
│  - TableGateway (Laminas DB)                    │
│  - Type/Formatter systems                       │
└─────────────────────────────────────────────────┘
```

---

## Core Components Deep-Dive

### 1. DataStoreAbstract (628 LOC)

**Location:** `src/DataStore/src/DataStoreAbstract.php`

**Purpose:** Base implementation providing query processing, aggregate functions, and CRUD template methods.

**Key Responsibilities:**
- Query processing (WHERE, SORT, LIMIT, OFFSET, SELECT, GROUP BY)
- Aggregate functions (count, max, min, sum, avg)
- Multi-record operations (multiCreate, multiUpdate)
- Queried operations (queriedUpdate, queriedDelete)
- Iterator interface (deprecated)

**Key Methods:**

```php
// Template method - subclasses implement
abstract protected function createBasic(array $itemData, $rewriteIfExist = false);
abstract protected function updateBasic(array $itemData, $createIfAbsent = false);
abstract protected function deleteBasic($id);

// Query processing pipeline
public function query(Query $query): array {
    1. queryWhere() - Filter by conditions
    2. querySort() - Sort results
    3. queryGroupBy() - Group if RqlQuery (optional)
    4. querySelect() - Project fields / aggregates
    5. array_slice() - Apply limit/offset
    6. Fill missing fields with null
    7. Return results
}
```

**Query Processing Flow:**

1. **queryWhere()** - Uses ConditionBuilder to filter items
2. **querySort()** - Sorts by specified fields (ASC/DESC)
3. **queryGroupBy()** - Groups by fields (for aggregates)
4. **querySelect()** - Projects fields and calculates aggregates
5. **array_slice()** - Applies LIMIT and OFFSET

**Aggregate Functions:**
- `count()` - Count values
- `max()` - Maximum value
- `min()` - Minimum value
- `sum()` - Sum values
- `avg()` - Average value

**Design Patterns:**
- Template Method (createBasic/updateBasic/deleteBasic)
- Strategy (ConditionBuilder for WHERE clause)

**Technical Debt:**
- **TODO** (Line 405): "need to log record that was not created"
- **TODO** (Line 433): "need to log record that was not updated"
- **TODO** (Line 464): "log failed queried updated record"
- **TODO** (Line 555): "need to log record that was not rewrote"
- **TODO** (Line 585): "need to log record that was not deleted"
- **Deprecated** (Line 613): "Datastore is no more iterable"
- **Security Risk**: Uses `eval()` via PhpConditionBuilder (Line 168)

**Dependencies:**
- `ConditionBuilder/PhpConditionBuilder` - For in-memory filtering
- `Iterators/DataStoreIterator` - For iteration support
- `Interfaces/DataStoreInterface` - Interface implementation
- RQL Module (Query, Node classes)

---

### 2. DbTable (750 LOC)

**Location:** `src/DataStore/src/DbTable.php`

**Purpose:** Database-backed datastore using Laminas TableGateway with transaction support.

**Key Features:**
- Transaction support with automatic rollback
- Multi-insert optimization using `MultiInsertSql`
- Query logging capability
- Connection exception handling with retry
- Queried update with `FOR UPDATE` locking
- SqlQueryBuilder integration for RQL→SQL translation

**Key Methods:**

```php
// CRUD operations with transaction support
protected function createBasic(array $itemData, $rewriteIfExist = false): array {
    BEGIN TRANSACTION
    try {
        INSERT INTO table
        COMMIT
        return inserted record
    } catch (Exception $e) {
        ROLLBACK
        throw ConnectionException
    }
}

// Queried update with row-level locking
public function queriedUpdate(array $record, Query $query): array {
    BEGIN TRANSACTION
    1. SELECT ... FOR UPDATE (lock matching rows)
    2. UPDATE ... WHERE id IN (update locked rows)
    COMMIT
    return updated IDs
}

// Batch insert optimization
public function multiCreate(array $records, $rewriteIfExist = false): array {
    if (count($records) > 1) {
        Use MultiInsertSql for batch insert
    } else {
        Use regular INSERT
    }
}
```

**Transaction Flow:**

```
BEGIN TRANSACTION
    ↓
Operation (INSERT/UPDATE/DELETE)
    ↓
Success? → COMMIT
    ↓
Failure? → ROLLBACK + throw Exception
```

**SQL Query Building:**

Uses `SqlQueryBuilder` to translate RQL queries to SQL:

```php
$sqlQuery = $this->sqlQueryBuilder->buildSql($query);
// Produces: SELECT * FROM table WHERE ... ORDER BY ... LIMIT ...
```

**Exception Handling:**

- Detects connection errors via `LaminasDbExceptionDetector`
- Throws `ConnectionException` for connection failures
- Throws `OperationTimedOutException` for timeouts
- Automatic rollback on any exception

**Design Patterns:**
- Template Method (extends DataStoreAbstract)
- Facade (wraps Laminas TableGateway)
- Strategy (SqlConditionBuilder for WHERE clauses)

**Dependencies:**
- `Laminas\Db\TableGateway\TableGateway` - Database operations
- `SqlQueryBuilder` - RQL to SQL translation
- `SqlConditionBuilder` - WHERE clause generation
- `LaminasDbExceptionDetector` - Exception classification
- `MultiInsertSql` - Batch insert optimization

**Technical Debt:**
- **Deprecated** (Line 156): "Autoincrement 'id' is not allowed"
- Tight coupling to Laminas\Db framework

---

### 3. Memory (214 LOC)

**Location:** `src/DataStore/src/Memory.php`

**Purpose:** In-memory array-backed datastore for testing and caching.

**Key Features:**
- Fast read/write operations (O(1) for read by ID)
- Column schema validation
- Auto-increment ID generation
- No persistence between requests
- Full RQL query support via DataStoreAbstract

**Data Structure:**

```php
private array $items = [
    1 => ['id' => 1, 'name' => 'foo', 'value' => 100],
    2 => ['id' => 2, 'name' => 'bar', 'value' => 200],
];
```

**Key Methods:**

```php
// CRUD operations on in-memory array
protected function createBasic(array $itemData, $rewriteIfExist = false): array {
    $id = $itemData[$this->identifier] ?? $this->generateId();
    $this->items[$id] = $itemData;
    return $this->items[$id];
}

protected function readBasic($id): ?array {
    return $this->items[$id] ?? null;
}

// Auto-increment ID generation
private function generateId(): int {
    return max(array_keys($this->items)) + 1;
}
```

**Use Cases:**
- Unit testing datastores without database
- Temporary caching of query results
- Fast prototyping
- In-memory session storage

**Design Patterns:**
- Template Method (extends DataStoreAbstract)

**Dependencies:**
- `DataStoreAbstract` - Base query processing

**Technical Debt:**
- **Deprecated** (Line 57): "Array of required columns is not specified"
- **Deprecated** (Line 181): "Datastore is no more iterable"
- No validation of column types (only checks required columns exist)

---

### 4. HttpClient (494 LOC)

**Location:** `src/DataStore/src/HttpClient.php`

**Purpose:** Remote datastore accessed via HTTP REST API.

**Key Features:**
- REST client for remote datastores
- Basic authentication support
- LifeCycleToken propagation for distributed tracing
- Auto-discovery of identifier via `Content-Location` header
- Graceful fallback for unsupported operations
- Timeout and connection exception handling

**HTTP Method Mapping:**

```
GET /?query=...     → query()
GET /{id}           → read()
POST /              → create() or multiCreate()
PUT /{id}           → update()
PATCH /?query=...   → queriedUpdate()
DELETE /{id}        → delete()
HEAD /?query=...    → has(), count()
```

**Key Methods:**

```php
// Query remote datastore
public function query(Query $query): array {
    $url = $this->url . '?' . $query->__toString();
    $response = $this->client->get($url);
    return json_decode($response->getBody(), true);
}

// Create on remote datastore
protected function createBasic(array $itemData, $rewriteIfExist = false): array {
    $response = $this->client->post($this->url, json_encode($itemData));
    // Extract ID from Content-Location header
    $location = $response->getHeader('Content-Location');
    $id = basename($location);
    return $this->read($id);
}
```

**Authentication:**

```php
// Basic Auth support
$client->setAuth($username, $password, Client::AUTH_BASIC);
```

**LifeCycleToken Propagation:**

```php
// Distributed tracing support
$headers['LifeCycleToken'] = $lifeCycleToken;
```

**Error Handling:**

- Detects connection failures
- Throws `ConnectionException` for network errors
- Graceful fallback: returns `null` or empty array on failure

**Design Patterns:**
- Template Method (extends DataStoreAbstract)
- Adapter (adapts HTTP REST to DataStore interface)

**Dependencies:**
- `Laminas\Http\Client` - HTTP client
- `RqlConditionBuilder` - RQL query string generation

**Technical Debt:**
- No retry logic for failed requests
- Limited error reporting (network errors not detailed)
- Hard-coded timeout values

---

### 5. CsvBase (581 LOC)

**Location:** `src/DataStore/src/CsvBase.php`

**Purpose:** CSV file-backed datastore with file locking and atomic writes.

**Key Features:**
- File locking (`LOCK_SH` for read, `LOCK_EX` for write)
- Header row as column names
- Type inference (numeric conversion)
- Atomic file replacement via temp file
- Special handling for empty strings vs null
- SplFileObject integration for efficient reading

**File Structure:**

```csv
id;name;value
1;foo;100
2;bar;200
```

**Key Methods:**

```php
// Read with shared lock
public function read($id): ?array {
    $this->getFileObject(LOCK_SH);
    return $this->findInFile($id);
}

// Write with exclusive lock
protected function createBasic(array $itemData, $rewriteIfExist = false): array {
    $this->getFileObject(LOCK_EX);
    $this->items[] = $itemData;
    $this->flush(); // Atomic file replacement
    return $itemData;
}

// Atomic file replacement
protected function flush(): void {
    1. Create temp file
    2. Write all rows to temp
    3. Copy temp to original (atomic on most filesystems)
    4. Delete temp
}
```

**File Locking:**

```
Read operations:  LOCK_SH (shared lock, multiple readers)
Write operations: LOCK_EX (exclusive lock, single writer)
```

**Atomic Write Flow:**

```
1. Open original file with LOCK_EX
2. Create temp file
3. Write headers to temp
4. Write all data rows to temp
5. Close temp
6. Copy temp → original (atomic)
7. Delete temp
8. Release lock
```

**Type Conversion:**

```php
// Automatic numeric type inference
$value = is_numeric($value) ? $value + 0 : $value;
// "100" → 100 (int)
// "100.5" → 100.5 (float)
// "foo" → "foo" (string)
```

**Design Patterns:**
- Template Method (extends DataStoreAbstract)
- Iterator (CsvIterator for lazy reading)

**Dependencies:**
- `SplFileObject` - File I/O
- `CsvIterator` - Lazy iteration

**Technical Debt:**
- **Performance**: `flush()` rewrites entire file on every change (O(n))
- **Deprecated** (Line 110): Commented out "Datastore is no more iterable"
- Empty string vs null handling inconsistent
- No compression support for large files

**Optimization Opportunities:**
- Append-only mode for inserts (avoid full rewrite)
- Delta logging for updates
- File compression

---

### 6. AspectAbstract (562 LOC)

**Location:** `src/DataStore/src/Aspect/AspectAbstract.php`

**Purpose:** Base decorator providing pre/post hooks for all DataStore operations.

**Key Features:**
- Decorator pattern for cross-cutting concerns
- Pre/post hooks for every CRUD operation
- Composable (can stack multiple aspects)
- Transparent pass-through to wrapped datastore

**Hook Pattern:**

```php
public function create($itemData, $rewriteIfExist = false) {
    // Pre-processing hook
    $newData = $this->preCreate($itemData, $rewriteIfExist);

    // Delegate to wrapped datastore
    $result = $this->dataStore->create($newData, $rewriteIfExist);

    // Post-processing hook
    return $this->postCreate($result, $newData, $rewriteIfExist);
}

// Override in subclass to customize
protected function preCreate(array $itemData, $rewriteIfExist = false): array {
    return $itemData; // Default: pass-through
}

protected function postCreate(array $result, array $itemData, $rewriteIfExist = false): array {
    return $result; // Default: pass-through
}
```

**Available Hooks:**

- **Create**: `preCreate()`, `postCreate()`
- **Read**: `preRead()`, `postRead()`
- **Update**: `preUpdate()`, `postUpdate()`
- **Delete**: `preDelete()`, `postDelete()`
- **Query**: `preQuery()`, `postQuery()`
- **Count**: `preCount()`, `postCount()`
- **Has**: `preHas()`, `postHas()`
- And all other operations...

**Composition Example:**

```php
$dataStore = new DbTable(...);
$dataStore = new AspectTyped($dataStore);     // Add type enforcement
$dataStore = new AspectReadOnly($dataStore);  // Make read-only
$dataStore = new AspectSchema($dataStore);    // Add schema validation
```

**Design Patterns:**
- Decorator (wraps DataStore)
- Template Method (hooks as extension points)

**Dependencies:**
- `Interfaces/DataStoresInterface` - Interface implementation

**Technical Debt:**
- **TODO** (Line implied): Inheritance-based, could use composition
- Verbose (must implement hooks for all operations)

---

### 7. AspectTyped

**Location:** `src/DataStore/src/Aspect/AspectTyped.php`

**Purpose:** Type enforcement and DTO conversion using Type and Formatter systems.

**Key Features:**
- Converts arrays to `BaseDto` objects (post hooks)
- Converts `BaseDto` to arrays (pre hooks)
- Uses `TypePluginManager` for type conversion
- Uses `FormatterPluginManager` for output formatting
- Implements `SchemableInterface` for schema support

**Type Conversion Flow:**

```
Input (Array) → preCreate → Type Conversion → DataStore → postCreate → Output (BaseDto)
```

**Scheme Format:**

```php
$scheme = [
    'id' => [
        'type' => TypeInt::class,
        'formatter' => IntFormatter::class,
    ],
    'name' => [
        'type' => TypeString::class,
        'formatter' => StringFormatter::class,
    ],
    'created_at' => [
        'type' => TypeDateTimeImmutable::class,
        'formatter' => DateTimeOrNullFormatter::class,
    ],
];
```

**Example Usage:**

```php
// Without AspectTyped
$data = ['id' => '123', 'name' => 'foo', 'created_at' => '2025-12-05'];
$result = $dataStore->create($data);
// Result: ['id' => '123', 'name' => 'foo', 'created_at' => '2025-12-05']

// With AspectTyped
$typedStore = new AspectTyped($dataStore, $scheme);
$result = $typedStore->create($data);
// Result: BaseDto {
//   id: 123 (int),
//   name: 'foo' (string),
//   created_at: DateTimeImmutable(2025-12-05)
// }
```

**Design Patterns:**
- Decorator (extends AspectAbstract)
- Strategy (Type/Formatter plugins)

**Dependencies:**
- `Type/TypePluginManager` - Type conversion
- `Formatter/FormatterPluginManager` - Formatting
- `BaseDto` - DTO class

---

### 8. Middleware System

**Location:** `src/DataStore/src/Middleware/`

**Purpose:** HTTP REST API layer for DataStore access.

**Architecture:**

```
HTTP Request
    ↓
DataStoreApi Pipeline
    ↓
ResourceResolver → Extract datastore name from URL
    ↓
RequestDecoder → Parse JSON body + RQL query
    ↓
Determinator → Route to appropriate DataStoreRest
    ↓
DataStoreRest Handler Chain
    ↓
Handler → Execute CRUD operation
    ↓
JsonResponse
```

#### DataStoreApi (67 LOC)

**Purpose:** Main API middleware pipeline.

```php
public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
    $pipeline = new MiddlewarePipe();
    $pipeline->pipe($this->resourceResolver);
    $pipeline->pipe($this->requestDecoder);
    $pipeline->pipe($this->determinator);
    return $pipeline->process($request, $handler);
}
```

#### ResourceResolver

**Purpose:** Extracts datastore name from URL path.

```
URL: /api/users?eq(status,active)
→ Extract: 'users'
→ Set request attribute: 'resourceName' = 'users'
```

#### RequestDecoder

**Purpose:** Parses JSON body and RQL query string.

```php
// Parse RQL query from URL
$queryString = $request->getQueryParams()['query'] ?? '';
$query = $this->rqlParser->parse($queryString);
$request = $request->withAttribute('query', $query);

// Parse JSON body (POST/PUT/PATCH)
$body = json_decode($request->getBody(), true);
$request = $request->withAttribute('body', $body);
```

**Technical Debt:**
- **Deprecated** (Line 103): "Header 'Range' is deprecated"

#### Determinator

**Purpose:** Routes request to appropriate DataStoreRest middleware.

```php
public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
    $resourceName = $request->getAttribute('resourceName');
    $dataStore = $this->container->get($resourceName);
    $restMiddleware = new DataStoreRest($dataStore);
    return $restMiddleware->process($request, $handler);
}
```

**Technical Debt:**
- **TODO** (Line 61): Empty TODO comment

#### DataStoreRest (57 LOC)

**Purpose:** Handler chain of responsibility for CRUD operations.

**Handler Chain:**

```
HeadHandler
  ↓
DownloadCsvHandler
  ↓
QueryHandler
  ↓
ReadHandler
  ↓
MultiCreateHandler
  ↓
CreateHandler
  ↓
UpdateHandler
  ↓
RefreshHandler
  ↓
DeleteHandler
  ↓
QueriedUpdateHandler
  ↓
ErrorHandler (catches all unhandled)
```

**Each Handler:**

1. Checks if it can handle the request
2. If yes: processes and returns response
3. If no: passes to next handler

**HTTP Method Mapping:**

```
GET /?query=...      → QueryHandler
GET /{id}            → ReadHandler
POST / (array)       → MultiCreateHandler
POST / (object)      → CreateHandler
PUT /{id}            → UpdateHandler
PATCH /?query=...    → QueriedUpdateHandler
DELETE /{id}         → DeleteHandler
HEAD /?query=...     → HeadHandler
GET /csv             → DownloadCsvHandler
POST /refresh        → RefreshHandler
```

**Design Patterns:**
- Chain of Responsibility (handler chain)
- Middleware Pipeline (PSR-15)

---

### 9. Condition Builders

**Location:** `src/DataStore/src/ConditionBuilder/`

**Purpose:** Translate RQL queries to executable conditions.

#### PhpConditionBuilder

**Purpose:** Generate PHP conditions for in-memory filtering using `eval()`.

**Example:**

```php
// RQL: eq(name,foo)
// Output: $item['name'] == 'foo'

// RQL: and(eq(status,active),gt(age,18))
// Output: ($item['status'] == 'active' && $item['age'] > 18)
```

**Usage in DataStoreAbstract:**

```php
$conditionBuilder = new PhpConditionBuilder();
$whereFunctionBody = $conditionBuilder->__invoke($query);
$whereFunction = (fn($item) => eval($whereFunctionBody));
$filteredItems = array_filter($items, $whereFunction);
```

**⚠️ CRITICAL SECURITY RISK:**

- Uses `eval()` to execute generated code
- **Vulnerability**: Code injection if RQL parsing has bugs
- **Impact**: Arbitrary code execution
- **Mitigation**: Replace with AST interpreter or compiled expressions

**Technical Debt:**
- **TODO** (Line 54): "make strict comparison"
- **TODO** (Line 119): "fix hardcode datetime format"

#### SqlConditionBuilder

**Purpose:** Generate SQL WHERE clauses from RQL queries.

**Example:**

```php
// RQL: and(eq(name,foo),gt(age,18))
// Output: `name` = 'foo' AND `age` > 18

// RQL: like(email,%@example.com)
// Output: `email` LIKE '%@example.com'
```

**Key Features:**
- Proper SQL escaping via Laminas Adapter
- Supports all RQL operators
- Handles LIKE patterns
- DateTime formatting

**Operator Mapping:**

```
eq   → =
ne   → !=
gt   → >
ge   → >=
lt   → <
le   → <=
like → LIKE
```

**Technical Debt:**
- **TODO** (Line 128): "hardcode format"
- **TODO** (Line 153): "Make same encoding for like & alike"
- **TODO** (Line 211): "force set table"

---

### 10. TableGateway System

**Location:** `src/DataStore/src/TableGateway/`

**Purpose:** MySQL table management and SQL query building.

#### TableManagerMysql (593 LOC)

**Purpose:** MySQL table creation and management.

**Key Features:**
- Create tables from schema definitions
- Add/remove columns dynamically
- Create indexes
- Validate table structure
- Support for JSON columns

**Schema Format:**

```php
$tableSchema = [
    'id' => [
        'field_type' => 'Integer',
        'field_params' => ['autoincrement' => true],
    ],
    'name' => [
        'field_type' => 'Varchar',
        'field_params' => ['length' => 255, 'nullable' => false],
    ],
    'data' => [
        'field_type' => 'Json',
    ],
];
```

**Key Methods:**

```php
// Create table
public function createTable(array $tableSchema): void {
    CREATE TABLE IF NOT EXISTS tableName (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        data JSON
    )
}

// Add column
public function addColumn(string $columnName, array $columnConfig): void {
    ALTER TABLE tableName ADD COLUMN columnName type
}

// Create index
public function createIndex(array $columns, bool $unique = false): void {
    CREATE [UNIQUE] INDEX idx_name ON tableName (columns)
}
```

**Technical Debt:**
- **Deprecated** (Line 500): "Autoincrement field is deprecated"

#### SqlQueryBuilder (179 LOC)

**Purpose:** Translate RQL Query to SQL SELECT statement.

**Key Features:**
- RQL to SQL translation
- WHERE clause generation via SqlConditionBuilder
- ORDER BY, LIMIT, OFFSET support
- GROUP BY support
- Column selection

**Translation Flow:**

```
RQL Query
    ↓
SqlQueryBuilder
    ↓
setWhere()     → WHERE clause
setOrder()     → ORDER BY clause
setLimit()     → LIMIT clause
setColumns()   → SELECT columns
setGroupBy()   → GROUP BY clause
    ↓
Laminas\Db\Sql\Select
    ↓
SQL SELECT statement
```

**Example:**

```php
// RQL: select(id,name)&eq(status,active)&sort(+name)&limit(10,5)

$sqlQuery = $sqlQueryBuilder->buildSql($query);

// Produces SQL:
SELECT id, name
FROM tableName
WHERE status = 'active'
ORDER BY name ASC
LIMIT 10 OFFSET 5
```

**Design Patterns:**
- Builder (builds SQL SELECT)
- Adapter (adapts RQL to SQL)

**Dependencies:**
- `SqlConditionBuilder` - WHERE clause generation
- `Laminas\Db\Sql\Select` - SQL query object

---

## Data Flow Analysis

### Create Operation Flow

```
┌─────────────────────┐
│ HTTP POST /users    │
│ Body: {name: "foo"} │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ Middleware Pipeline │
│ - ResourceResolver  │
│   Extract: 'users'  │
│ - RequestDecoder    │
│   Parse body        │
│ - Determinator      │
│   Get DataStore     │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ CreateHandler       │
│ - Validate request  │
│ - Extract body      │
│ - Call create()     │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ AspectTyped         │
│ preCreate:          │
│ - DTO → Array       │
│ - Type validation   │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ DbTable.create()    │
│ - BEGIN TRANSACTION │
│ - INSERT INTO table │
│ - COMMIT            │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ AspectTyped         │
│ postCreate:         │
│ - Array → DTO       │
│ - Format output     │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ JsonResponse        │
│ Status: 201         │
│ Body: {...}         │
└─────────────────────┘
```

### Query Operation Flow (DbTable)

```
┌─────────────────────┐
│ HTTP GET /users?    │
│ eq(status,active)   │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ RequestDecoder      │
│ - Parse RQL string  │
│ - Build Query AST   │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ QueryHandler        │
│ - Extract Query     │
│ - Call query()      │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ DbTable.query()     │
│ - SqlQueryBuilder   │
│   RQL → SQL SELECT  │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ SqlQueryBuilder     │
│ - setWhere          │
│ - setOrder          │
│ - setLimit          │
│ - setColumns        │
│ - setGroupBy        │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ Execute SQL         │
│ SELECT * FROM users │
│ WHERE status=active │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ Return array[]      │
└─────────────────────┘
```

### Query Operation Flow (Memory)

```
┌─────────────────────┐
│ Memory.query()      │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ DataStoreAbstract   │
│ query() template    │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ 1. queryWhere()     │
│ PhpConditionBuilder │
│ → eval() filter     │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ 2. querySort()      │
│ usort() by fields   │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ 3. queryGroupBy()   │
│ Array grouping      │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ 4. querySelect()    │
│ Field projection    │
│ + aggregates        │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ 5. array_slice()    │
│ LIMIT + OFFSET      │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│ Return array[]      │
└─────────────────────┘
```

### Queried Update Flow

```
┌───────────────────────────┐
│ HTTP PATCH /users?        │
│ eq(status,pending)        │
│ Body: {status: "active"}  │
└──────────┬────────────────┘
           │
           ↓
┌───────────────────────────┐
│ QueriedUpdateHandler      │
│ - Parse RQL query         │
│ - Extract body            │
└──────────┬────────────────┘
           │
           ↓
┌─────────────────────────────────┐
│ DbTable.queriedUpdate()         │
│ 1. BEGIN TRANSACTION            │
│ 2. SELECT * FROM users          │
│    WHERE status='pending'       │
│    FOR UPDATE (lock rows)       │
│ 3. UPDATE users                 │
│    SET status='active'          │
│    WHERE id IN (locked IDs)     │
│ 4. COMMIT                       │
└──────────┬──────────────────────┘
           │
           ↓
┌───────────────────────────┐
│ Return array of IDs       │
│ [1, 5, 12]                │
└───────────────────────────┘
```

**Key Feature**: `FOR UPDATE` locking prevents race conditions in concurrent updates.

---

## Dependency Graph

### Internal Dependencies

```
DataStoreAbstract
├── ConditionBuilder/PhpConditionBuilder
├── Iterators/DataStoreIterator
└── Interfaces/DataStoreInterface

DbTable
├── DataStoreAbstract
├── ConditionBuilder/SqlConditionBuilder
├── TableGateway/SqlQueryBuilder
├── TableGateway/TableGateway
├── LaminasDbExceptionDetector
└── Exceptions (ConnectionException, OperationTimedOutException)

Memory
├── DataStoreAbstract
└── Traits/AutoIdGeneratorTrait

HttpClient
├── DataStoreAbstract
├── ConditionBuilder/RqlConditionBuilder
└── Exceptions/ConnectionException

CsvBase
├── DataStoreAbstract
└── Iterators/CsvIterator

AspectAbstract
└── Interfaces/DataStoresInterface

AspectTyped
├── AspectAbstract
├── Type/TypePluginManager
├── Formatter/FormatterPluginManager
└── BaseDto

Middleware/DataStoreApi
├── Determinator
├── ResourceResolver
└── RequestDecoder

Middleware/DataStoreRest
└── Handler/* (12 handlers)

TableGateway/SqlQueryBuilder
├── SqlConditionBuilder
└── Laminas\Db\Sql

Cacheable
├── DataSource/DataSourceInterface
└── Memory
```

### External Dependencies (Laminas Framework)

**54 files use Laminas components** (37% of module):

1. **Laminas\Db** (Database abstraction)
   - `Laminas\Db\Adapter\Adapter` - Database connection
   - `Laminas\Db\TableGateway\TableGateway` - Table operations
   - `Laminas\Db\Sql\*` - SQL query building
   - `Laminas\Db\Metadata\*` - Database introspection
   - Used by: DbTable, TableGateway, SqlQueryBuilder

2. **Laminas\ServiceManager** (Dependency injection)
   - Used by all PluginManagers
   - Abstract factories integration
   - Used by: All Factory classes

3. **Laminas\Diactoros** (PSR-7 HTTP)
   - `ServerRequestInterface`, `ResponseInterface`
   - `JsonResponse`, `TextResponse`
   - Used by: All Middleware classes

4. **Laminas\Stratigility** (Middleware)
   - `MiddlewarePipe` - Pipeline composition
   - `MiddlewareInterface` - Middleware contract
   - Used by: DataStoreApi, DataStoreRest

5. **Laminas\Http** (HTTP client)
   - `Laminas\Http\Client` - HTTP requests
   - Used by: HttpClient

### Integration with RQL Module

**RQL Module provides:**
- `Xiag\Rql\Parser\Query` - Query AST
- `Xiag\Rql\Parser\Node\*` - Query nodes
- Custom nodes: `AggregateFunctionNode`, `AggregateSelectNode`, `GroupbyNode`
- `RqlParser` - RQL string parsing

**DataStore Module consumes:**
- All `query()` methods accept `Query` objects
- ConditionBuilders translate Query nodes
- SqlQueryBuilder translates to SQL SELECT
- Middleware/RequestDecoder parses RQL from URL

**Dependency Direction**: DataStore → RQL (clean unidirectional, no circular dependency)

---

## Testing Analysis

### Test Coverage Summary

- **Total test files**: 128 test files found in `/test/unit/`
- **Coverage**: Unknown (requires coverage report)
- **Test execution**: 890 tests passing (confirmed in previous runs)

### Test Files by Component

**RQL Module**: Well-tested
- Multiple test files for RQL parser
- Token parsers have dedicated tests

**Core DataStore classes**: Moderately tested
- CRUD operations likely covered
- Test files in `/test/unit/DataStore/`

**Middleware**: Unknown coverage
- No dedicated middleware test directory found

**Aspect system**: Unknown coverage
- No aspect-specific tests discovered

**TableGateway**: Likely tested
- Integration tests for SQL generation

### Testing Gaps (Estimated)

#### Low/No Coverage

1. **Aspect System**
   - AspectTyped DTO conversion
   - AspectReadOnly enforcement
   - Aspect composition (stacking multiple aspects)

2. **Middleware Handlers**
   - Handler routing logic
   - Error handling in handlers
   - Request/response transformation

3. **Type/Formatter Systems**
   - Edge cases in type conversion
   - Null handling in formatters
   - Custom type registration

4. **Exception Handling**
   - Connection exception scenarios
   - Transaction rollback paths
   - Timeout handling

#### Moderate Coverage

1. **Core DataStore Implementations**
   - Basic CRUD operations (likely tested)
   - Complex queries (partially tested)
   - Edge cases (unknown)

2. **RQL Parsing**
   - Basic operators (well tested)
   - Complex nested queries (partially tested)

#### High-Risk Areas Needing Tests

1. **DbTable.queriedUpdate()** - Complex transaction logic with `FOR UPDATE`
2. **CsvBase.flush()** - Atomic file replacement
3. **PhpConditionBuilder** - eval() security (CRITICAL)
4. **AspectTyped** - DTO conversion edge cases
5. **Middleware pipeline** - Request routing and error handling

### Testing Recommendations

**Before Refactoring:**

1. Add integration tests for each DataStore type
2. Add contract tests for DataStoreInterface compliance
3. Add unit tests for Aspect decorators
4. Add middleware handler tests
5. Add security tests for PhpConditionBuilder

**Test Priorities:**

- **P0**: Security tests for eval() usage
- **P1**: Integration tests for DbTable transactions
- **P2**: Contract tests for DataStoreInterface
- **P3**: Unit tests for Type/Formatter systems

---

## Technical Debt & Known Issues

### Critical Issues

#### 1. Security: eval() Usage in PhpConditionBuilder

**Location:** `DataStoreAbstract.php:168`, `ConditionBuilder/PhpConditionBuilder.php`

**Issue:**
```php
$whereFunction = (fn($item) => eval($whereFunctionBody));
```

**Risk:**
- Code injection if RQL parsing has vulnerabilities
- Arbitrary code execution
- **Severity**: CRITICAL

**Mitigation:**
- Replace with AST interpreter
- Or compile to closure without eval()
- Add input validation and sanitization

#### 2. Dual Interface Hierarchy

**Issue:** Two parallel interfaces for same concept:
- `DataStoresInterface` - Old API with `create($item, $rewriteIfExist)`
- `DataStoreInterface` - New API with separate `create($item)` and `rewrite($item)`

**Impact:**
- Confusing API surface
- Difficult to understand which to implement
- `DataStoreAbstract` implements BOTH, adding complexity

**Evidence:**
- Deprecated warnings when using `$rewriteIfExist` or `$createIfAbsent` options
- trigger_error E_USER_DEPRECATED throughout codebase

**Mitigation:**
- Remove `DataStoresInterface` in v2
- Keep only `DataStoreInterface`
- Provide adapter for legacy code

### TODO Comments (17 found)

**Logging TODOs** (most common):
1. `DataStoreAbstract.php:405` - "TODO: need to log record that was not created"
2. `DataStoreAbstract.php:433` - "TODO: need to log record that was not updated"
3. `DataStoreAbstract.php:464` - "TODO: log failed queried updated record"
4. `DataStoreAbstract.php:555` - "TODO: need to log record that was not rewrote"
5. `DataStoreAbstract.php:585` - "TODO: need to log record that was not deleted"

**Implementation TODOs:**
6. `Rql/Node/AggregateFunctionNode.php:56` - "TODO: Implement toRql() method"
7. `Rql/Node/GroupbyNode.php:38` - "TODO: Implement toRql() method"
8. `Rql/Node/BinaryNode/BinaryOperatorNodeAbstract.php:25` - "TODO: Implement toRql() method"

**Type System TODOs:**
9. `ConditionBuilder/PhpConditionBuilder.php:54` - "TODO: make strict comparison"
10. `ConditionBuilder/ConditionBuilderAbstract.php:119` - "TODO: fix hardcode datetime format"
11. `ConditionBuilder/SqlConditionBuilder.php:128` - "TODO hardcode format"
12. `ConditionBuilder/SqlConditionBuilder.php:153` - "TODO: Make same encoding for like & alike"
13. `ConditionBuilder/SqlConditionBuilder.php:211` - "TODO: force set table"

**Code Smell TODOs:**
14. `Traits/MappingFieldsTrait.php:101` - "TODO" (empty)
15. `Middleware/Determinator.php:61` - "TODO" (empty)
16. `Aspect/AbstractMapperAspect.php:36` - "TODO: Remove to another aspect"

**Exception Handling:**
17. `DataStoreServerException.php:7` - "TODO: implement recognizing this exception in DbTable"

### Deprecated Code (20+ instances)

#### Deprecated Options (Most Critical)

1. **rewriteIfExist** option in `create()`
   - Used throughout all datastores
   - Triggers E_USER_DEPRECATED
   - Should use separate `rewrite()` method instead

2. **createIfAbsent** option in `update()`
   - Used throughout all datastores
   - Triggers E_USER_DEPRECATED
   - Should use `create()` or `update()` explicitly

#### Iterator Deprecation

3. `DataStoreAbstract.php:613` - "Datastore is no more iterable"
4. `Memory.php:181` - Same message
5. `CsvBase.php:110` - Commented out deprecation

#### Trait Deprecations

6. All `NoSupport*Trait` classes are deprecated
7. `PrepareFieldsTrait` is deprecated

#### Other Deprecations

8. `Memory.php:57` - "Array of required columns is not specified"
9. `DbTable.php:156` - "Autoincrement 'id' is not allowed"
10. `RequestDecoder.php:103` - "Header 'Range' is deprecated"
11. `TableManagerMysql.php:500` - "Autoincrement field is deprecated"

### Code Smells

#### 1. God Object: DataStoreAbstract

**Issue:**
- 628 lines with 40+ methods
- Implements query processing, aggregation, iteration, multi-CRUD
- Violates Single Responsibility Principle

**Impact:**
- Hard to test
- Hard to understand
- Hard to modify

#### 2. Primitive Obsession

**Issue:** Arrays used everywhere instead of rich domain objects

```php
// Current
$item = ['id' => 1, 'name' => 'foo', 'value' => 100];

// Should be
class Entity {
    private EntityId $id;
    private EntityName $name;
    private EntityValue $value;
}
```

**Impact:**
- No type safety
- No validation
- No business logic encapsulation

#### 3. Tight Coupling to Laminas

**Issue:** 54 files (37%) depend directly on Laminas components

**Impact:**
- Hard to test in isolation
- Difficult to swap implementations
- Framework version lock-in
- Cannot use with other frameworks

#### 4. Mixed Responsibilities

**Issue:** DataStore classes mix business logic with infrastructure

Examples:
- `DbTable` has both CRUD logic AND SQL generation
- `CsvBase` has both CRUD logic AND file I/O
- `HttpClient` has both CRUD logic AND HTTP client

**Should be:**
- Domain layer: CRUD logic
- Infrastructure layer: SQL/file/HTTP

#### 5. Hard-coded String Building

**Issue:**
- `PhpConditionBuilder` builds PHP code strings
- `SqlConditionBuilder` builds SQL strings
- Risk of injection and maintenance difficulty

**Better:**
- Use proper SQL builder (Laminas\Db\Sql already used, but not consistently)
- AST interpreter instead of eval()

#### 6. Missing Type Hints

**Issue:** Many methods accept/return `mixed` or lack return types

```php
public function create($itemData, $rewriteIfExist = false)
public function read($id)
```

**Impact:**
- Runtime errors
- Poor IDE support
- Harder to refactor

#### 7. Plugin Manager Overhead

**Issue:** Multiple PluginManagers for simple type conversion

- `TypePluginManager`
- `FormatterPluginManager`
- `DataStorePluginManager`

**May be over-engineering** for simple type conversion

### Anti-patterns

1. **God Object**: DataStoreAbstract (628 LOC, 40+ methods)
2. **Anemic Domain Model**: DataStore classes are mostly data manipulation, no rich domain logic
3. **Magic Strings**: Column names as strings everywhere, no type-safe field references
4. **Inheritance over Composition**: Aspect system uses inheritance, could use composition
5. **Service Locator**: PluginManagers are anti-pattern for dependency injection

### Performance Issues

#### 1. CsvBase File Rewrite

**Issue:** `flush()` rewrites entire file on every change

```php
protected function flush(): void {
    1. Create temp file
    2. Write ALL rows (O(n))
    3. Copy to original
    4. Delete temp
}
```

**Impact:**
- O(n) for every write operation
- Slow for large files
- High I/O

**Mitigation:**
- Append-only mode for inserts
- Delta logging for updates
- File compression

#### 2. PhpConditionBuilder eval()

**Issue:** Every query executes eval()

**Impact:**
- Slow (eval is expensive)
- Security risk

---

## Clean Architecture Assessment

### Current Architecture Violations

```
Current: HTTP → DataStore → Database
         (No domain layer)

Should be:
HTTP → Controllers → Use Cases → Domain ← Repositories → Infrastructure
```

### Dependency Rule Violations

1. **Domain depends on infrastructure**
   - DataStore classes import Laminas\Db
   - No inward dependencies

2. **No Entities**
   - Arrays used instead of rich domain objects
   - No business logic encapsulation

3. **No Use Cases**
   - CRUD operations directly in DataStore
   - No application service layer

4. **No Repository Abstraction**
   - DataStore is both abstraction AND implementation
   - Persistence logic mixed with business logic

5. **Infrastructure in Core**
   - `DbTable` is infrastructure, but central to module
   - `CsvBase` has file I/O in domain layer

### Missing DDD Patterns

1. **Aggregates**: None
   - No aggregate roots
   - No consistency boundaries

2. **Value Objects**: None
   - Primitive obsession (IDs, names as strings/ints)
   - No validation in domain objects

3. **Domain Events**: Partial
   - Event manager support in aspects
   - Not consistently used

4. **Repositories**: Partial
   - DataStore acts like repository
   - But mixed with infrastructure concerns

5. **Domain Services**: None
   - No orchestration of complex operations

6. **Factories**: Present but infrastructure-focused
   - Abstract factories for DI container
   - No domain model factories

---

## Modification Guidance for Clean Architecture Refactoring

### Phase 1: Establish Domain Layer

#### Step 1: Extract Domain Entities

**Current:**
```php
$item = ['id' => 1, 'name' => 'foo', 'email' => 'foo@example.com'];
```

**Target:**
```php
namespace Domain\Entity;

class Entity {
    private EntityId $id;
    private EntityName $name;
    private Email $email;

    private function __construct(
        EntityId $id,
        EntityName $name,
        Email $email
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
    }

    public static function create(EntityName $name, Email $email): self {
        return new self(
            EntityId::generate(),
            $name,
            $email
        );
    }

    public function rename(EntityName $newName): void {
        // Business logic here
        $this->name = $newName;
    }
}
```

#### Step 2: Create Value Objects

```php
namespace Domain\ValueObject;

final readonly class EntityId {
    private function __construct(private int $value) {
        if ($value <= 0) {
            throw new InvalidArgumentException('ID must be positive');
        }
    }

    public static function fromInt(int $value): self {
        return new self($value);
    }

    public static function generate(): self {
        // Auto-increment or UUID logic
        return new self(/* generated ID */);
    }

    public function toInt(): int {
        return $this->value;
    }
}

final readonly class EntityName {
    private function __construct(private string $value) {
        if (strlen($value) === 0) {
            throw new InvalidArgumentException('Name cannot be empty');
        }
        if (strlen($value) > 255) {
            throw new InvalidArgumentException('Name too long');
        }
    }

    public static function fromString(string $value): self {
        return new self($value);
    }

    public function toString(): string {
        return $this->value;
    }
}
```

#### Step 3: Define Aggregate Roots

Identify consistency boundaries and encapsulate validation rules.

### Phase 2: Implement Repository Pattern

#### Step 1: Extract Repository Interface (in Domain)

```php
namespace Domain\Repository;

use Domain\Entity\Entity;
use Domain\ValueObject\EntityId;

interface EntityRepository {
    public function nextId(): EntityId;
    public function findById(EntityId $id): ?Entity;
    public function findByCriteria(Criteria $criteria): EntityCollection;
    public function save(Entity $entity): void;
    public function remove(Entity $entity): void;
}
```

#### Step 2: Implement in Infrastructure

```php
namespace Infrastructure\Persistence\Database;

use Domain\Repository\EntityRepository;
use Domain\Entity\Entity;
use Domain\ValueObject\EntityId;

class DbTableEntityRepository implements EntityRepository {
    public function __construct(private DbTable $dbTable) {}

    public function findById(EntityId $id): ?Entity {
        $data = $this->dbTable->read($id->toInt());
        if ($data === null) {
            return null;
        }
        return $this->hydrate($data);
    }

    public function save(Entity $entity): void {
        $data = $this->extract($entity);
        $this->dbTable->update($data);
    }

    private function hydrate(array $data): Entity {
        // Map array to Entity
    }

    private function extract(Entity $entity): array {
        // Map Entity to array
    }
}
```

### Phase 3: Create Application Layer

#### Step 1: Define Use Cases

```php
namespace Application\UseCase;

use Domain\Repository\EntityRepository;
use Domain\Entity\Entity;
use Domain\ValueObject\EntityName;
use Domain\ValueObject\Email;

class CreateEntityUseCase {
    public function __construct(
        private EntityRepository $repository
    ) {}

    public function execute(CreateEntityCommand $command): EntityId {
        $entity = Entity::create(
            EntityName::fromString($command->name),
            Email::fromString($command->email)
        );

        $this->repository->save($entity);

        return $entity->id();
    }
}
```

#### Step 2: Define Commands

```php
namespace Application\Command;

final readonly class CreateEntityCommand {
    public function __construct(
        public string $name,
        public string $email
    ) {}
}
```

#### Step 3: Update Controllers

```php
namespace Presentation\Http\Handler;

use Application\UseCase\CreateEntityUseCase;
use Application\Command\CreateEntityCommand;

class CreateHandler {
    public function __construct(
        private CreateEntityUseCase $useCase
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface {
        $body = json_decode($request->getBody(), true);

        $command = new CreateEntityCommand(
            name: $body['name'],
            email: $body['email']
        );

        $id = $this->useCase->execute($command);

        return new JsonResponse(['id' => $id->toInt()], 201);
    }
}
```

### Phase 4: Invert Dependencies

#### Step 1: Remove Laminas from Domain

**Current:**
```php
use Laminas\Db\TableGateway\TableGateway;

class DbTable {
    private TableGateway $tableGateway;
}
```

**Target:**
```php
namespace Infrastructure\Persistence\Database;

use Domain\Repository\EntityRepository;
use Infrastructure\Database\Connection;

class DbTableEntityRepository implements EntityRepository {
    private Connection $connection;

    // Laminas used only in Infrastructure, not Domain
}
```

#### Step 2: Adapter Pattern for External Libraries

```php
namespace Infrastructure\Database;

interface Connection {
    public function execute(string $sql, array $params): array;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;
}

class LaminasConnection implements Connection {
    public function __construct(
        private \Laminas\Db\Adapter\Adapter $adapter
    ) {}

    // Implement interface using Laminas
}
```

### Phase 5: Address Security & Technical Debt

#### Step 1: Replace eval() in PhpConditionBuilder

**Current:**
```php
$whereFunction = (fn($item) => eval($whereFunctionBody));
```

**Target: AST Interpreter**
```php
class SafeConditionEvaluator {
    public function evaluate(array $item, Query $query): bool {
        return $this->evaluateNode($query->getWhere(), $item);
    }

    private function evaluateNode(AbstractNode $node, array $item): bool {
        return match(get_class($node)) {
            EqNode::class => $item[$node->getField()] == $node->getValue(),
            GtNode::class => $item[$node->getField()] > $node->getValue(),
            AndNode::class => $this->evaluateNode($node->getLeft(), $item)
                           && $this->evaluateNode($node->getRight(), $item),
            // etc.
        };
    }
}
```

#### Step 2: Consolidate Interfaces

1. Remove `DataStoresInterface`
2. Keep only `DataStoreInterface`
3. Create adapter for legacy code:

```php
class LegacyDataStoreAdapter implements DataStoresInterface {
    public function __construct(
        private DataStoreInterface $newDataStore
    ) {}

    public function create($itemData, $rewriteIfExist = false): array {
        if ($rewriteIfExist) {
            return $this->newDataStore->rewrite($itemData);
        }
        return $this->newDataStore->create($itemData);
    }

    // Adapt other methods...
}
```

### Testing Checklist for Changes

- [ ] All existing tests pass (890 tests)
- [ ] New unit tests for domain entities
- [ ] New unit tests for value objects
- [ ] New unit tests for use cases
- [ ] Integration tests for repositories
- [ ] Contract tests for repository interface
- [ ] Security tests for condition evaluation (no eval)
- [ ] Performance tests (no regression)
- [ ] Backward compatibility tests (adapter works)

---

## Proposed Module Structure for Clean Architecture

```
src/
├── Domain/                         # NEW - Pure business logic
│   ├── Entity/
│   │   ├── Entity.php
│   │   └── EntityCollection.php
│   ├── ValueObject/
│   │   ├── EntityId.php
│   │   ├── EntityName.php
│   │   └── Email.php
│   ├── Repository/                 # Interfaces only
│   │   └── EntityRepository.php
│   ├── Service/
│   │   └── EntityDomainService.php
│   ├── Event/
│   │   └── EntityCreated.php
│   └── Exception/
│       └── EntityNotFoundException.php
│
├── Application/                    # NEW - Use cases
│   ├── UseCase/
│   │   ├── CreateEntityUseCase.php
│   │   ├── UpdateEntityUseCase.php
│   │   └── QueryEntitiesUseCase.php
│   ├── Command/
│   │   ├── CreateEntityCommand.php
│   │   └── UpdateEntityCommand.php
│   ├── Query/
│   │   └── FindEntitiesByCriteriaQuery.php
│   └── DTO/
│       └── EntityDTO.php
│
├── Infrastructure/                 # REFACTOR existing
│   ├── Persistence/
│   │   ├── Database/
│   │   │   ├── DbTableEntityRepository.php  # Current DbTable
│   │   │   ├── Connection.php
│   │   │   └── LaminasConnection.php
│   │   ├── Csv/
│   │   │   └── CsvEntityRepository.php      # Current CsvBase
│   │   ├── Memory/
│   │   │   └── MemoryEntityRepository.php   # Current Memory
│   │   └── Http/
│   │       └── HttpEntityRepository.php     # Current HttpClient
│   ├── Query/
│   │   ├── Rql/                    # Current RQL integration
│   │   │   ├── RqlToSqlConverter.php
│   │   │   ├── RqlConditionEvaluator.php (replaces eval)
│   │   │   └── RqlParser.php
│   │   └── Criteria/
│   │       └── Criteria.php
│   ├── Type/                       # Current Type system
│   │   └── TypeConverter.php
│   └── Mapping/                    # Current Aspects
│       ├── Mapper.php
│       └── TypeMapper.php
│
└── Presentation/                   # REFACTOR existing
    ├── Http/
    │   ├── Middleware/             # Current Middleware
    │   │   ├── DataStoreApi.php
    │   │   └── ResourceResolver.php
    │   └── Handler/                # Current Handler
    │       ├── CreateHandler.php
    │       ├── QueryHandler.php
    │       └── UpdateHandler.php
    └── Cli/                        # Future CLI support
        └── Commands/
```

---

## Migration Strategy

### Approach: Strangler Pattern

**Goal:** Gradually migrate to Clean Architecture without breaking existing code.

### Steps

1. **Create new structure alongside existing code**
   - Add Domain/, Application/, Infrastructure/ directories
   - Keep existing src/DataStore/ working

2. **Implement new features in new structure**
   - All new features use Clean Architecture
   - Route new endpoints to new structure

3. **Gradually migrate existing features**
   - Start with simplest datastores (Memory)
   - Then CSV, HTTP
   - Finally DbTable (most complex)

4. **Maintain backward compatibility**
   - Adapter pattern for old interfaces
   - Deprecation warnings
   - Migration guide for consumers

5. **Remove old code**
   - After all consumers migrated
   - Remove deprecated interfaces
   - Clean up legacy code

### Migration Priorities

**P0 - Foundation** (Do first):
1. Extract Repository interface
2. Create Domain entities
3. Implement Value Objects
4. Replace eval() with safe interpreter

**P1 - Core Refactoring**:
1. Create Application layer (Use Cases)
2. Implement repositories in Infrastructure
3. Update middleware to use Use Cases
4. Remove Laminas from Domain

**P2 - Cleanup**:
1. Consolidate interfaces (remove DataStoresInterface)
2. Remove deprecated code
3. Improve type hints
4. Optimize performance

**P3 - Optimization**:
1. CSV file performance
2. Query caching
3. Connection pooling

---

## Summary Statistics

### Module Size
- **Total Files**: 146
- **Total LOC**: ~12,537
- **Average File Size**: ~86 LOC
- **Largest File**: `DbTable.php` (750 LOC)

### Component Distribution
- **Core Implementations**: 8 files
- **Aspects**: 11 files
- **Interfaces**: 10 files
- **Condition Builders**: 5 files
- **Type System**: 12 files
- **Formatter System**: 12 files
- **Middleware**: 22 files
- **TableGateway**: 8 files
- **Factories**: 13+ files
- **Traits**: 14 files
- **Exceptions**: 7 files

### External Dependencies
- **Laminas Framework**: 54 files (37% of module)
  - Laminas\Db - Database abstraction
  - Laminas\ServiceManager - DI container
  - Laminas\Http - HTTP client
  - Laminas\Diactoros - PSR-7
  - Laminas\Stratigility - Middleware

### Technical Debt
- **TODO Comments**: 17
- **Deprecated Markers**: 20+
- **Security Issues**: 1 critical (eval())
- **Code Smells**: 10+ identified
- **Anti-patterns**: 5 identified

---

## Conclusion

The DataStore module is a **mature, feature-rich data abstraction layer** with extensive capabilities:

### Strengths

✅ Multi-backend storage abstraction (DbTable, Memory, CSV, HTTP)
✅ RQL query language integration
✅ REST API middleware
✅ Aspect-oriented programming for cross-cutting concerns
✅ Comprehensive type and formatter systems
✅ Transaction support with rollback
✅ File locking for CSV

### Critical Issues

❌ **Security Risk**: eval() usage in PhpConditionBuilder
❌ **No domain layer**: Business logic in infrastructure
❌ **Tight framework coupling**: 37% of files depend on Laminas
❌ **Mixed responsibilities**: God objects, anemic models
❌ **Legacy constraints**: Dual interface hierarchy
❌ **Performance**: CSV file rewrite on every change

### Refactoring Requirements

**To achieve Clean Architecture compliance:**

1. **Domain Layer Extraction** - Entities, Value Objects, Aggregates
2. **Repository Pattern** - Separate persistence abstraction
3. **Application Layer** - Use Cases, Commands, Queries
4. **Dependency Inversion** - Remove framework coupling
5. **Security Fixes** - Replace eval() with safe alternatives

### Effort Estimate

- **Size**: Large refactoring (6-12 months)
- **Risk**: High (limited test coverage)
- **Approach**: Incremental migration with Strangler Pattern

### Next Steps

1. **Phase 0**: Add comprehensive tests (CRITICAL - do this first!)
2. **Phase 1**: Replace eval() security risk
3. **Phase 2**: Extract domain layer
4. **Phase 3**: Implement repository pattern
5. **Phase 4**: Create application layer
6. **Phase 5**: Remove framework coupling

---

_Generated by `document-project` workflow (deep-dive mode)_
_Base Documentation: [docs/index.md](./index.md)_
_Related Deep-Dive: [RQL Module](./deep-dive-rql-module.md)_
_Scan Date: 2025-12-05_
_Analysis Mode: Exhaustive_