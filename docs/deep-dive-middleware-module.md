# Middleware Module - Deep Dive Documentation

**Generated:** 2025-12-05
**Scope:** src/DataStore/src/Middleware/
**Files Analyzed:** 22
**Lines of Code:** ~1,401
**Workflow Mode:** Exhaustive Deep-Dive

## Overview

The Middleware Module provides the **HTTP REST API layer** for DataStore access. It implements a PSR-15 compliant middleware pipeline with a handler chain of responsibility pattern, enabling full CRUD operations via HTTP methods (GET, POST, PUT, PATCH, DELETE, HEAD) with RQL query support.

**Purpose:** Transform HTTP requests into DataStore operations and format responses as JSON.

**Key Responsibilities:**
- URL path parsing and resource resolution
- RQL query parsing from URL parameters
- JSON request body decoding
- HTTP method routing to appropriate handlers
- DataStore operation execution
- JSON response formatting with proper status codes
- Error handling and exception management

**Integration Points:**
- PSR-7 HTTP messages (ServerRequest/Response)
- PSR-15 Middleware standard
- Laminas Stratigility (middleware pipeline)
- DataStore interfaces
- RQL Module (query parsing)

---

## File Distribution & Architecture

### Module Statistics

```
Core Middleware:     8 files  (541 LOC)
Handler Classes:    10 files  (697 LOC)
Factory Classes:     2 files   (73 LOC)
Utilities:           2 files   (90 LOC)
------------------------------------
Total:              22 files (1,401 LOC)
```

### Component Breakdown

#### Core Middleware (8 files - 541 LOC)

- **DataStoreApi.php** (66 LOC) - Main middleware pipeline orchestrator
- **DataStoreAbstract.php** (40 LOC) - Abstract base for middleware with injected DataStore
- **DataStoreRest.php** (56 LOC) - Handler chain orchestrator
- **Determinator.php** (67 LOC) - DataStore plugin routing middleware
- **ResourceResolver.php** (92 LOC) - URL path parsing for resource name & ID extraction
- **RequestDecoder.php** (151 LOC) - Request body/header parsing (JSON, RQL, headers)
- **JsonRenderer.php** (53 LOC) - JSON response formatting
- **RestException.php** (16 LOC) - Custom exception for REST errors

#### Handler Classes (10 files - 697 LOC)

- **AbstractHandler.php** (60 LOC) - Base handler with canHandle/handle pattern
- **CreateHandler.php** (76 LOC) - POST single record creation (201 Created)
- **MultiCreateHandler.php** (95 LOC) - POST bulk record creation with fallback
- **ReadHandler.php** (43 LOC) - GET single record by ID
- **QueryHandler.php** (90 LOC) - GET collection with RQL filtering
- **UpdateHandler.php** (74 LOC) - PUT record replacement
- **QueriedUpdateHandler.php** (89 LOC) - PATCH bulk update via RQL query
- **DeleteHandler.php** (49 LOC) - DELETE single record
- **RefreshHandler.php** (43 LOC) - PATCH datastore refresh
- **DownloadCsvHandler.php** (103 LOC) - GET CSV export with pagination
- **ErrorHandler.php** (34 LOC) - Fallback error handler
- **HeadHandler.php** (31 LOC) - HEAD request metadata response

#### Factory Classes (2 files - 73 LOC)

- **DataStoreApiFactory.php** (38 LOC) - Service factory for DataStoreApi middleware
- **DeterminatorFactory.php** (35 LOC) - Service factory for Determinator middleware

---

## Architecture & Design Patterns

### Middleware Pipeline Architecture

The module implements a **PSR-15 compliant middleware pipeline** with two distinct layers:

```
HTTP REQUEST
    ↓
┌─────────────────────────────────────────┐
│     DataStoreApi (Main Pipeline)        │
├─────────────────────────────────────────┤
│                                         │
│  1. ResourceResolver                    │
│     • Parses: /api/datastore/{resource}/{id}
│     • Extracts: resourceName, primaryKeyValue
│     • Sets request attributes          │
│                                         │
│  2. RequestDecoder                      │
│     • Parses JSON body                 │
│     • Extracts RQL query from URL      │
│     • Processes headers:               │
│       - If-Match (overwrite mode)      │
│       - With-Content-Range             │
│       - Range (deprecated)             │
│                                         │
│  3. Determinator                        │
│     • Looks up DataStore by name       │
│     • Returns 404 if not found         │
│     • Creates DataStoreRest handler    │
│     • Adds response headers:           │
│       - Datastore-Scheme               │
│       - X_DATASTORE_IDENTIFIER         │
│                                         │
└─────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────┐
│   DataStoreRest (Handler Chain)         │
├─────────────────────────────────────────┤
│                                         │
│  Handler Chain (execution order):       │
│                                         │
│   1. HeadHandler                        │
│      • HEAD requests → metadata         │
│                                         │
│   2. DownloadCsvHandler                 │
│      • GET + download:csv header        │
│                                         │
│   3. QueryHandler                       │
│      • GET + RQL query → collection     │
│                                         │
│   4. ReadHandler                        │
│      • GET + ID → single record         │
│                                         │
│   5. MultiCreateHandler                 │
│      • POST + array → bulk create       │
│                                         │
│   6. CreateHandler                      │
│      • POST + object → single create    │
│                                         │
│   7. UpdateHandler                      │
│      • PUT + ID → update/upsert         │
│                                         │
│   8. RefreshHandler                     │
│      • PATCH (no RQL) → refresh cache   │
│                                         │
│   9. DeleteHandler                      │
│      • DELETE + ID → delete record      │
│                                         │
│  10. QueriedUpdateHandler               │
│      • PATCH + RQL → bulk update        │
│                                         │
│  11. ErrorHandler                       │
│      • Fallback → throws exception      │
│                                         │
└─────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────┐
│   DataStore Operation                   │
│   (create/read/update/delete/query)     │
└─────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────┐
│   JSON Response                         │
│   (with HTTP status code)               │
└─────────────────────────────────────────┘
```

### Handler Chain of Responsibility Pattern

Each handler implements the **Chain of Responsibility** pattern:

```php
// AbstractHandler pattern
public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
{
    if ($this->canHandle($request)) {
        return $this->handle($request);  // Process and return
    }
    return $handler->handle($request);   // Pass to next handler
}
```

**Key Characteristics:**
- `canHandle()`: Checks if handler should process request
- `handle()`: Executes the business logic
- If `canHandle()` returns false, passes to next handler
- ErrorHandler at end throws exception if no handler matched

### PSR Standards Compliance

**PSR-15 (HTTP Server Request Handlers)**:
- All middleware implement `MiddlewareInterface`
- All handlers implement `RequestHandlerInterface`
- Signature: `process(ServerRequestInterface, RequestHandlerInterface): ResponseInterface`
- Immutable request/response pattern

**PSR-7 (HTTP Message Interfaces)**:
- `ServerRequestInterface` - Request with attributes, parsed body, headers
- `ResponseInterface` - HTTP responses (JsonResponse, TextResponse)
- Request attributes for inter-middleware communication

**PSR-3 (Logger Interface)**:
- Optional logger in DataStoreApi
- Logs exceptions with context

### Design Patterns Used

1. **Middleware Pipeline** (Stratigility)
   - Sequential processing of request through multiple stages
   - Each middleware can modify request/response

2. **Chain of Responsibility** (Handler Chain)
   - Each handler decides if it can process request
   - Automatic delegation to next handler

3. **Factory Pattern** (Service Factories)
   - DataStoreApiFactory creates configured pipeline
   - DeterminatorFactory resolves DataStore plugin manager

4. **Strategy Pattern** (Handler Selection)
   - Different handlers for different HTTP methods
   - Runtime selection based on request properties

5. **Template Method** (AbstractHandler)
   - Base class defines algorithm structure
   - Subclasses implement specific steps

---

## HTTP Method Routing Table

| HTTP Method | URL Pattern | Handler | Condition | Status | Response |
|-------------|-------------|---------|-----------|--------|----------|
| **GET** | `/resource?rql` | QueryHandler | RQL query present | 200 | Collection with optional Content-Range |
| **GET** | `/resource/{id}` | ReadHandler | ID in URL, no RQL | 200 | Single record |
| **GET** | `/resource` | DownloadCsvHandler | `download: csv` header | 200 | CSV file stream (paginated) |
| **HEAD** | `/resource` | HeadHandler | Any HEAD request | 200 | Metadata headers only, no body |
| **POST** | `/resource` | CreateHandler | Single object `{}` | 201/200 | Created record + Location header |
| **POST** | `/resource` | MultiCreateHandler | Array `[{}, {}]` | 201 | Array of created IDs |
| **PUT** | `/resource/{id}` | UpdateHandler | ID in URL or body | 200/201 | Updated record (201 if created) |
| **PATCH** | `/resource?rql` | QueriedUpdateHandler | RQL + fields | 200 | Array of updated IDs |
| **PATCH** | `/resource` | RefreshHandler | No RQL, no ID | 200 | Success message |
| **DELETE** | `/resource/{id}` | DeleteHandler | ID in URL | 200/204 | Deleted record (204 if none) |
| **ANY** | Any | ErrorHandler | No handler matched | 400 | RestException details |

### Special Headers

**Request Headers:**
- **If-Match: \*** - Enable overwrite mode (upsert)
  - CreateHandler: Overwrites existing records
  - UpdateHandler: Creates new if not exists (returns 201)
- **With-Content-Range: \*** - Request Content-Range in response
- **Range: items=0-9** - (Deprecated) Pagination range
- **download: csv** - Trigger CSV export instead of JSON

**Response Headers:**
- **Datastore-Scheme** - JSON schema of datastore fields
- **X_DATASTORE_IDENTIFIER** - Name of ID field
- **X_MULTI_CREATE** - Indicates multiCreate support
- **X_QUERIED_UPDATE** - Indicates queriedUpdate support
- **Content-Range** - Range info for paginated responses
- **Location** - URL of created resource (201 status)

---

## Core Component Deep-Dive

### 1. DataStoreApi (66 LOC)

**Location:** `src/DataStore/src/Middleware/DataStoreApi.php`

**Purpose:** Main entry point for all DataStore HTTP requests.

**Responsibilities:**
- Create middleware pipeline (ResourceResolver → RequestDecoder → Determinator)
- Global exception handling
- Content negotiation for error responses (JSON vs Text)
- Optional logging

**Key Code:**

```php
public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
{
    try {
        return $this->middlewarePipe->process($request, $handler);
    } catch (\Throwable $e) {
        // Log exception
        if ($this->logger) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }

        // Return JSON or Text based on Accept header
        if ($this->isJsonResponse($request)) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
        return new TextResponse($e->getMessage(), 500);
    }
}
```

**Design Pattern:** Pipeline Pattern (Laminas Stratigility MiddlewarePipe)

---

### 2. ResourceResolver (92 LOC)

**Location:** `src/DataStore/src/Middleware/ResourceResolver.php`

**Purpose:** Parse URL path to extract resource name and optional ID.

**URL Patterns:**
```
/api/datastore/{resourceName}       → Collection endpoint
/api/datastore/{resourceName}/{id}  → Single record endpoint
```

**Regex Pattern:**
```php
$pattern = "/(base)\/([\w\~\-\_]+)([\/]([-%_A-Za-z0-9]+))?\/?$/";
//             └─┬─┘  └───┬───────┘       └──────┬────────┘
//            base    resourceName              id (optional)
```

**Sets Request Attributes:**
- `resourceName` - DataStore identifier (e.g., "users")
- `primaryKeyValue` - Record ID from URL (rawurldecoded)

**Example:**
```
URL: /api/datastore/users/john%40example.com
→ resourceName: "users"
→ primaryKeyValue: "john@example.com"
```

**Technical Note:** Uses `rawurldecode()` for percent-encoded IDs (supports special characters)

---

### 3. RequestDecoder (151 LOC - LARGEST)

**Location:** `src/DataStore/src/Middleware/RequestDecoder.php`

**Purpose:** Parse request body, query parameters, and headers.

**Parsing Operations:**

#### 3.1 JSON Body Parsing
```php
if ($contentType === 'application/json') {
    $body = Serializer::jsonUnserialize($request->getBody()->__toString());
    $request = $request->withParsedBody($body);
}
```

#### 3.2 RQL Query Parsing
```php
$queryString = $request->getUri()->getQuery();
// Remove XDEBUG params
$queryString = preg_replace('/XDEBUG_SESSION_START[^&]*&?/', '', $queryString);

$query = $this->rqlParser->rqlDecode($queryString);
$request = $request->withAttribute('rqlQueryObject', $query);
```

#### 3.3 Header Processing

**If-Match Header** (Upsert Mode):
```php
$ifMatch = $request->getHeaderLine('If-Match');
$overwriteMode = ($ifMatch === '*');
$request = $request->withAttribute('overwriteMode', $overwriteMode);
```

**With-Content-Range Header**:
```php
$withContentRange = $request->getHeaderLine('With-Content-Range');
$request = $request->withAttribute('withContentRange', !empty($withContentRange));
```

**Range Header** (Deprecated):
```php
if ($request->hasHeader('Range')) {
    trigger_error("Header 'Range' is deprecated", E_USER_DEPRECATED);
    // Parse: Range: items=0-9
    preg_match('/(\d+)-(\d+)/', $range, $matches);
    $limit = ($matches[2] - $matches[1] + 1);
    $offset = $matches[1];
}
```

**Technical Debt:**
- **Deprecated** (Line 103): Range header should be removed
- Complex header parsing logic
- XDEBUG param stripping is workaround

---

### 4. Determinator (67 LOC)

**Location:** `src/DataStore/src/Middleware/Determinator.php`

**Purpose:** Resolve DataStore instance and create handler chain.

**Workflow:**

```php
public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
{
    $resourceName = $request->getAttribute('resourceName');

    // 1. Get DataStore from plugin manager
    if (!$this->dataStorePluginManager->has($resourceName)) {
        return new EmptyResponse(404); // Not Found
    }
    $dataStore = $this->dataStorePluginManager->get($resourceName);

    // 2. Create handler chain
    $dataStoreRest = new DataStoreRest($dataStore);

    // 3. Process request
    $response = $dataStoreRest->process($request, $handler);

    // 4. Add response headers
    $response = $this->addDataStoreHeaders($response, $dataStore);

    return $response;
}
```

**Response Headers Added:**
```php
// Schema information (if SchemableInterface)
$response = $response->withHeader('Datastore-Scheme', json_encode($schema));

// Identifier field name
$response = $response->withHeader('X_DATASTORE_IDENTIFIER', $dataStore->getIdentifier());
```

**Technical Debt:**
- **TODO** (Line 61): Empty TODO comment
- Creates new DataStoreRest per request (no caching)

---

### 5. DataStoreRest (56 LOC)

**Location:** `src/DataStore/src/Middleware/DataStoreRest.php`

**Purpose:** Handler chain orchestrator.

**Handler Pipeline (Order Matters!):**

```php
$this->middlewarePipe = new MiddlewarePipe();
$this->middlewarePipe->pipe(new HeadHandler($dataStore));              // 1
$this->middlewarePipe->pipe(new DownloadCsvHandler($dataStore));        // 2
$this->middlewarePipe->pipe(new QueryHandler($dataStore));              // 3
$this->middlewarePipe->pipe(new ReadHandler($dataStore));               // 4
$this->middlewarePipe->pipe(new MultiCreateHandler($dataStore));        // 5
$this->middlewarePipe->pipe(new CreateHandler($dataStore));             // 6
$this->middlewarePipe->pipe(new UpdateHandler($dataStore));             // 7
$this->middlewarePipe->pipe(new RefreshHandler($dataStore));            // 8
$this->middlewarePipe->pipe(new DeleteHandler($dataStore));             // 9
$this->middlewarePipe->pipe(new QueriedUpdateHandler($dataStore));      // 10
$this->middlewarePipe->pipe(new ErrorHandler($dataStore));              // 11 (fallback)
```

**Why Order Matters:**
- **HeadHandler** first - handles all HEAD requests regardless of other conditions
- **DownloadCsvHandler** before **QueryHandler** - CSV export takes precedence
- **QueryHandler** before **ReadHandler** - collection queries before single reads
- **MultiCreateHandler** before **CreateHandler** - array before single object
- **ErrorHandler** last - catches unhandled requests

---

## Handler Details

### QueryHandler (90 LOC)

**Purpose:** GET requests with RQL filtering.

**Conditions:**
```php
public function canHandle(ServerRequestInterface $request): bool
{
    return $request->getMethod() === 'GET'
        && $request->getAttribute('primaryKeyValue') === null
        && !$this->isRqlQueryEmpty($request);
}
```

**Response:**
```php
$query = $request->getAttribute('rqlQueryObject');
$items = $this->dataStore->query($query);

$response = new JsonResponse($items, 200);

// Add Content-Range header if requested
if ($request->getAttribute('withContentRange')) {
    $total = $this->getTotalItems($query);
    $response = $response->withHeader('Content-Range', "items 0-{$count}/{$total}");
}

return $response;
```

**getTotalItems() Implementation:**
```php
// Execute query again with count() aggregate
$countQuery = new Query();
$countQuery->setSelect(new AggregateSelectNode([
    new AggregateFunctionNode('count', 'id')
]));
// Copy WHERE conditions from original query
$result = $this->dataStore->query($countQuery);
return $result[0]['count_id'];
```

**Performance Issue:** Executes query **twice** (once for data, once for count)

---

### CreateHandler (76 LOC)

**Purpose:** POST requests for single record creation.

**Conditions:**
```php
public function canHandle(ServerRequestInterface $request): bool
{
    return $request->getMethod() === 'POST'
        && is_array($request->getParsedBody())
        && !$this->isMultiCreate($request->getParsedBody())
        && $this->isRqlQueryEmpty($request);
}
```

**Workflow:**
```php
$data = $request->getParsedBody();
$overwriteMode = $request->getAttribute('overwriteMode', false);

// Create or overwrite
$item = $this->dataStore->create($data, $overwriteMode);

$id = $item[$this->dataStore->getIdentifier()];
$location = $request->getUri()->getPath() . '/' . $id;

// 201 if new, 200 if exists
$status = $overwriteMode && $existsBefore ? 200 : 201;

return (new JsonResponse($item, $status))
    ->withHeader('Location', $location);
```

**Status Codes:**
- **201 Created** - New record created
- **200 OK** - Existing record overwritten (If-Match: *)

---

### MultiCreateHandler (95 LOC)

**Purpose:** POST requests for bulk record creation.

**Conditions:**
```php
public function canHandle(ServerRequestInterface $request): bool
{
    $body = $request->getParsedBody();
    return $request->getMethod() === 'POST'
        && is_array($body)
        && $this->isMultiCreate($body)  // Array of objects
        && $this->isRqlQueryEmpty($request);
}
```

**Fallback Pattern:**
```php
if ($this->dataStore instanceof DataStoreInterface) {
    // Native multiCreate
    $result = $this->dataStore->multiCreate($rows);
} else {
    // Fallback: loop with 10ms delay
    $result = [];
    foreach ($rows as $row) {
        try {
            $created = $this->dataStore->create($row);
            $result[] = $created[$this->dataStore->getIdentifier()];
            usleep(10000);  // 10ms delay
        } catch (DataStoreException $e) {
            // Silently ignore failed items
        }
    }
}

return new JsonResponse($result, 201);
```

**Performance Issues:**
- **usleep(10000)** - 10ms delay per item (N items = N×10ms)
- **Silent failures** - Catch exception but don't report to client

---

### QueriedUpdateHandler (89 LOC)

**Purpose:** PATCH requests for bulk updates via RQL filter.

**Conditions:**
```php
public function canHandle(ServerRequestInterface $request): bool
{
    $query = $request->getAttribute('rqlQueryObject');

    return $request->getMethod() === 'PATCH'
        && $request->getAttribute('primaryKeyValue') === null
        && !$this->isRqlQueryEmpty($request)
        && $this->hasRequiredLimit($query)
        && !$this->hasUnsupportedNodes($query)  // No groupBy, select
        && $this->isAssociativeArray($request->getParsedBody());
}
```

**Validation:**
```php
// MUST have limit
if ($query->getLimit() === null) {
    throw new RestException('RQL query must have limit for queriedUpdate');
}

// CANNOT have groupBy or select
if ($query->getGroupby() || $query->getSelect()) {
    throw new RestException('groupBy and select not supported in queriedUpdate');
}

// Body must be field updates (not array of records)
if (!$this->isAssociativeArray($body)) {
    throw new RestException('Body must be associative array of fields');
}
```

**Execution with Fallback:**
```php
$fields = $request->getParsedBody();  // e.g., {"status": "active"}
$query = $request->getAttribute('rqlQueryObject');

if ($this->dataStore instanceof DataStoreInterface) {
    // Native queriedUpdate
    $ids = $this->dataStore->queriedUpdate($fields, $query);
} else {
    // Fallback: query + update loop
    $items = $this->dataStore->query($query);
    $ids = [];
    foreach ($items as $item) {
        try {
            $payload = array_merge($item, $fields);
            $this->dataStore->update($payload);
            $ids[] = $item[$this->dataStore->getIdentifier()];
            usleep(10000);  // 10ms delay
        } catch (DataStoreException $e) {
            // Silently ignore
        }
    }
}

return new JsonResponse($ids, 200);
```

**Same Issues as MultiCreateHandler:**
- usleep() loops
- Silent failure swallowing

---

### DownloadCsvHandler (103 LOC)

**Purpose:** CSV export with pagination.

**Conditions:**
```php
public function canHandle(ServerRequestInterface $request): bool
{
    return $request->getMethod() === 'GET'
        && strtolower($request->getHeaderLine('download')) === 'csv';
}
```

**Pagination Strategy:**
```php
const LIMIT = 8000;  // Records per page
const DELIMITER = ',';
const ENCLOSURE = '"';

$offset = 0;
$csvContent = '';

// Add CSV header row
$firstItem = $this->dataStore->read($this->getFirstId());
$csvContent .= implode(self::DELIMITER, array_keys($firstItem)) . "\n";

// Paginated query loop
while (true) {
    $query = new Query();
    $query->setLimit(new LimitNode(self::LIMIT, $offset));

    $items = $this->dataStore->query($query);
    if (empty($items)) {
        break;
    }

    foreach ($items as $item) {
        $csvContent .= $this->formatCsvRow($item);
    }

    $offset += self::LIMIT;
}

return new TextResponse($csvContent, 200, [
    'Content-Type' => 'text/csv',
    'Content-Disposition' => 'attachment; filename="export.csv"'
]);
```

**Security Issue:**
- **CSV Injection** - Values not escaped; potential for formula injection in Excel
- Example: `=1+2` in CSV → Excel executes formula

**Performance:**
- Loads entire dataset into memory before streaming
- Should use streaming response instead

---

### ErrorHandler (34 LOC)

**Purpose:** Fallback handler for unmatched requests.

**Always Matches:**
```php
public function canHandle(ServerRequestInterface $request): bool
{
    return true;  // Always matches (fallback)
}
```

**Throws Detailed Exception:**
```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    throw new RestException(sprintf(
        "Unknown operation: %s %s\nBody: %s\nAttributes: %s",
        $request->getMethod(),
        $request->getUri()->getPath(),
        json_encode($request->getParsedBody()),
        json_encode($request->getAttributes())
    ));
}
```

**Security Concern:** Exposes full request attributes (potential information leakage)

---

## Request/Response Flow Examples

### Example 1: Simple GET by ID

```
CLIENT REQUEST
GET /api/datastore/users/123
Accept: application/json

↓ DataStoreApi.process()
  ↓ ResourceResolver.process()
    • resourceName = "users"
    • primaryKeyValue = "123"

  ↓ RequestDecoder.process()
    • rqlQueryObject = Query{} (empty)

  ↓ Determinator.process()
    • dataStore = DataStorePluginManager->get("users")
    • Creates DataStoreRest

    ↓ DataStoreRest.process()
      ↓ Handler Chain
      1. HeadHandler.canHandle() → false (GET ≠ HEAD)
      2. DownloadCsvHandler.canHandle() → false (no download header)
      3. QueryHandler.canHandle() → false (has primaryKeyValue)
      4. ReadHandler.canHandle() → true ✓

         ↓ ReadHandler.handle()
           $item = dataStore->read(123)
           return JsonResponse($item, 200)

    ↓ addHeaders()
      response.withHeader('X_DATASTORE_IDENTIFIER', 'id')

↓ DataStoreApi (exception handling - none)

SERVER RESPONSE
HTTP/1.1 200 OK
Content-Type: application/json
X_DATASTORE_IDENTIFIER: id

{"id": 123, "name": "John", "email": "john@example.com"}
```

---

### Example 2: POST Bulk Create

```
CLIENT REQUEST
POST /api/datastore/users
Content-Type: application/json

[
  {"id": 1, "name": "Alice"},
  {"id": 2, "name": "Bob"}
]

↓ ResourceResolver
  • resourceName = "users"
  • primaryKeyValue = null

↓ RequestDecoder
  • parsedBody = [{id:1, name:"Alice"}, {id:2, name:"Bob"}]
  • rqlQueryObject = Query{} (empty)

↓ Determinator → DataStoreRest
  ↓ Handler Chain
  1-4: canHandle() → false
  5. MultiCreateHandler.canHandle() → true ✓
     • POST ✓
     • Array ✓
     • Array of objects ✓
     • Empty RQL ✓

     ↓ MultiCreateHandler.handle()
       if (DataStoreInterface) {
         $ids = dataStore->multiCreate($rows)
       } else {
         // Fallback loop with 10ms delays
         foreach ($rows as $row) {
           $created = dataStore->create($row)
           $ids[] = $created['id']
           usleep(10000)
         }
       }
       return JsonResponse($ids, 201)

SERVER RESPONSE
HTTP/1.1 201 Created
Content-Type: application/json

[1, 2]
```

---

### Example 3: PATCH Bulk Update with RQL

```
CLIENT REQUEST
PATCH /api/datastore/users?eq(status,pending)&limit(100,0)
Content-Type: application/json

{"status": "active"}

↓ ResourceResolver
  • resourceName = "users"
  • primaryKeyValue = null

↓ RequestDecoder
  • parsedBody = {status: "active"}
  • rqlQueryObject = Query{
      where: eq(status, "pending"),
      limit: LimitNode(100, 0)
    }

↓ Determinator → DataStoreRest
  ↓ Handler Chain
  1-9: canHandle() → false
  10. QueriedUpdateHandler.canHandle() → true ✓
      • PATCH ✓
      • No primaryKeyValue ✓
      • Has RQL query ✓
      • Has limit ✓
      • No groupBy/select ✓
      • Associative array body ✓

      ↓ QueriedUpdateHandler.handle()
        $fields = {status: "active"}
        $query = Query{eq(status,"pending"), limit(100)}

        if (DataStoreInterface) {
          $ids = dataStore->queriedUpdate($fields, $query)
        } else {
          // Fallback
          $items = dataStore->query($query)
          foreach ($items as $item) {
            $payload = array_merge($item, $fields)
            dataStore->update($payload)
            usleep(10000)
          }
        }

        return JsonResponse($ids, 200)

SERVER RESPONSE
HTTP/1.1 200 OK
Content-Type: application/json

[5, 12, 18, 23, ...]
```

---

### Example 4: Error Flow

```
CLIENT REQUEST
POST /api/datastore/users?limit(10)
Content-Type: application/json

[{"id": 1, "name": "Alice"}]

↓ ResourceResolver
  • resourceName = "users"
  • primaryKeyValue = null

↓ RequestDecoder
  • parsedBody = [{id:1, name:"Alice"}]
  • rqlQueryObject = Query{limit: 10}  ← RQL not empty!

↓ Determinator → DataStoreRest
  ↓ Handler Chain
  1-4: canHandle() → false
  5. MultiCreateHandler.canHandle() → false
     • POST ✓
     • Array ✓
     • Array of objects ✓
     • Empty RQL ✗ (has limit!)
  6-10: canHandle() → false
  11. ErrorHandler.canHandle() → true (always)

      ↓ ErrorHandler.handle()
        throw new RestException(
          "Unknown operation: POST /api/datastore/users
          Body: [{\"id\":1,\"name\":\"Alice\"}]
          Attributes: {resourceName:users, rqlQueryObject:Query{...}}"
        )

↓ DataStoreApi exception handler
  if (Accept: application/json) {
    return JsonResponse({error: "Unknown operation..."}, 500)
  }

SERVER RESPONSE
HTTP/1.1 500 Internal Server Error
Content-Type: application/json

{
  "error": "Unknown operation: POST /api/datastore/users\nBody: [...]\nAttributes: {...}"
}
```

**Issue:** RQL query incompatible with POST array - MultiCreateHandler rejects requests with RQL

---

## Technical Debt & Issues

### Critical Issues

#### 1. Performance: usleep() Loops

**Location:** MultiCreateHandler, QueriedUpdateHandler

**Issue:**
```php
foreach ($items as $item) {
    $this->dataStore->update($item);
    usleep(10000);  // 10ms delay per item
}
```

**Impact:**
- 100 items = 1000ms (1 second) of artificial delay
- 1000 items = 10 seconds delay
- No reason for delay in fallback code

**Mitigation:** Remove usleep() calls; use batching instead

---

#### 2. Silent Failures in Bulk Operations

**Location:** MultiCreateHandler, QueriedUpdateHandler

**Issue:**
```php
try {
    $created = $this->dataStore->create($row);
    $result[] = $created['id'];
} catch (DataStoreException $e) {
    // Silent failure - client gets partial success with no warning
}
```

**Impact:**
- Client receives `[1, 5, 12]` but doesn't know that items 2, 3, 4, 6-11 failed
- No way to detect partial success
- Cannot retry failed items

**Mitigation:** Return detailed response with success/failure breakdown:
```json
{
  "success": [1, 5, 12],
  "failed": [
    {"id": 2, "error": "Duplicate key"},
    {"id": 3, "error": "Validation failed"}
  ]
}
```

---

#### 3. CSV Injection Vulnerability

**Location:** DownloadCsvHandler

**Issue:**
```php
foreach ($items as $item) {
    $csvContent .= implode(self::DELIMITER, $item) . "\n";
}
// No escaping of values like =1+2, @SUM(A1:A10), etc.
```

**Risk:**
- Excel/LibreOffice execute formulas starting with `=`, `+`, `-`, `@`
- Attacker can inject: `=cmd|'/c calc'!A1` (Windows calculator)

**Mitigation:** Escape CSV values:
```php
function escapeCsv($value) {
    if (preg_match('/^[=+\-@]/', $value)) {
        return "'" . $value;  // Prefix with single quote
    }
    return $value;
}
```

---

#### 4. Memory Exhaustion in CSV Export

**Location:** DownloadCsvHandler

**Issue:**
```php
$csvContent = '';  // Entire CSV in memory

while (true) {
    $items = $this->dataStore->query($query);
    foreach ($items as $item) {
        $csvContent .= $this->formatCsvRow($item);  // Append to string
    }
}

return new TextResponse($csvContent, 200);  // Load entire string into response
```

**Impact:**
- 1 million rows × 1KB each = 1GB memory
- PHP memory_limit exceeded

**Mitigation:** Use streaming response:
```php
return new StreamedResponse(function() {
    $offset = 0;
    while (true) {
        $items = $this->dataStore->query($query);
        foreach ($items as $item) {
            echo $this->formatCsvRow($item);
        }
        flush();
    }
});
```

---

### Medium Priority Issues

#### 5. Duplicate Query Execution

**Location:** QueryHandler.getTotalItems()

**Issue:**
```php
// First query for data
$items = $this->dataStore->query($query);

// Second query for count (if withContentRange)
$total = $this->getTotalItems($query);  // Executes query again with count()
```

**Impact:**
- 2× query cost
- Slow for large datasets

**Mitigation:** Use SQL_CALC_FOUND_ROWS or OVER() window function

---

#### 6. Handler Ordering Fragility

**Location:** DataStoreRest

**Issue:** Handler order determines behavior; wrong order breaks functionality

**Current Order:**
```php
1. HeadHandler
2. DownloadCsvHandler
3. QueryHandler
4. ReadHandler
...
```

**Example Problem:**
If `ReadHandler` comes before `QueryHandler`, then `GET /users?eq(status,active)` would match `ReadHandler` (no primaryKeyValue check) instead of `QueryHandler`

**Mitigation:**
- Document handler order requirements
- Add unit tests for handler precedence
- Consider handler priority metadata

---

#### 7. Request Attribute Pollution

**Issue:** 6+ request attributes for inter-middleware communication

**Attributes:**
- resourceName
- primaryKeyValue
- rqlQueryObject
- overwriteMode
- withContentRange
- Limit (deprecated)

**Impact:**
- Hard to trace data flow
- Risk of attribute name collisions
- Difficult to understand request state

**Mitigation:** Use structured context object:
```php
class DataStoreRequestContext {
    public string $resourceName;
    public ?string $primaryKeyValue;
    public Query $rqlQuery;
    public bool $overwriteMode;
    public bool $withContentRange;
}

$request = $request->withAttribute('datastoreContext', $context);
```

---

### Low Priority Issues

#### 8. Deprecated Range Header

**Location:** RequestDecoder (line 103)

**Issue:**
```php
trigger_error("Header 'Range' is deprecated", E_USER_DEPRECATED);
```

**Action:** Set removal timeline; document migration to `With-Content-Range`

---

#### 9. Empty TODO Comment

**Location:** Determinator (line 61)

**Issue:**
```php
// TODO
```

**Action:** Complete or remove

---

#### 10. Hardcoded Magic Numbers

**Location:** DownloadCsvHandler

**Issue:**
```php
const LIMIT = 8000;      // Why 8000?
const DELIMITER = ',';
const ENCLOSURE = '"';
```

**Action:** Move to configuration or document reasoning

---

#### 11. Inconsistent Header Naming

**Issue:**
- `Datastore-Scheme` (kebab-case)
- `X_DATASTORE_IDENTIFIER` (SCREAMING_SNAKE_CASE)
- `X_MULTI_CREATE` (SCREAMING_SNAKE_CASE)

**Mitigation:** Standardize to one style (e.g., `X-DataStore-Identifier`)

---

#### 12. Unused JsonRenderer

**Location:** JsonRenderer.php

**Issue:** Class exists but not used in pipeline

**Action:** Either integrate or remove

---

## Integration Points

### External Dependencies

| Component | Type | PSR | Purpose |
|-----------|------|-----|---------|
| Psr\Http\Server\MiddlewareInterface | Interface | PSR-15 | Middleware contract |
| Psr\Http\Server\RequestHandlerInterface | Interface | PSR-15 | Request handler contract |
| Psr\Http\Message\ServerRequestInterface | Interface | PSR-7 | HTTP request |
| Psr\Http\Message\ResponseInterface | Interface | PSR-7 | HTTP response |
| Psr\Log\LoggerInterface | Interface | PSR-3 | Logging (optional) |
| Laminas\Stratigility\MiddlewarePipe | Class | - | Pipeline composition |
| Laminas\Diactoros\Response\JsonResponse | Class | PSR-7 | JSON responses |
| Laminas\Diactoros\Response\TextResponse | Class | PSR-7 | Text responses |
| Laminas\Diactoros\Response\EmptyResponse | Class | PSR-7 | No content responses |
| rollun\datastore\DataStore\DataStorePluginManager | Class | - | DataStore registry |
| rollun\datastore\DataStore\Interfaces\DataStoreInterface | Interface | - | DataStore operations (modern) |
| rollun\datastore\DataStore\Interfaces\DataStoresInterface | Interface | - | DataStore operations (legacy) |
| rollun\datastore\Rql\RqlParser | Class | - | RQL parsing |
| rollun\utils\Json\Serializer | Class | - | JSON serialization |
| Xiag\Rql\Parser\Query | Class | - | RQL query AST |

### DataStore Integration

Middleware delegates to DataStore methods:

```php
// CRUD operations
$dataStore->create($record);
$dataStore->read($id);
$dataStore->update($record);
$dataStore->delete($id);
$dataStore->query($rqlQuery);

// Optional methods (checked via instanceof)
if ($dataStore instanceof DataStoreInterface) {
    $dataStore->multiCreate($records);
    $dataStore->queriedUpdate($fields, $query);
}

if ($dataStore instanceof RefreshableInterface) {
    $dataStore->refresh();
}

if ($dataStore instanceof SchemableInterface) {
    $schema = $dataStore->getScheme();
}
```

---

## Modification Guidance

### Adding a New Handler

**Example: Add `PATCH /resource/{id}` (single update)**

1. **Create Handler Class:**

```php
namespace rollun\datastore\Middleware\Handler;

class PatchHandler extends AbstractHandler
{
    public function canHandle(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === 'PATCH'
            && $request->getAttribute('primaryKeyValue') !== null
            && $this->isRqlQueryEmpty($request);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('primaryKeyValue');
        $fields = $request->getParsedBody();

        // Get existing record
        $existing = $this->dataStore->read($id);
        if ($existing === null) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        // Merge and update
        $updated = array_merge($existing, $fields);
        $result = $this->dataStore->update($updated);

        return new JsonResponse($result, 200);
    }
}
```

2. **Add to DataStoreRest Pipeline:**

```php
// In DataStoreRest.__construct()
$this->middlewarePipe->pipe(new UpdateHandler($dataStore));     // PUT
$this->middlewarePipe->pipe(new PatchHandler($dataStore));      // NEW: PATCH single
$this->middlewarePipe->pipe(new RefreshHandler($dataStore));
```

**Order Matters:** Put `PatchHandler` BEFORE `QueriedUpdateHandler` so single-record PATCH is handled before bulk PATCH

3. **Add Tests:**

```php
public function testPatchSingleRecord()
{
    $request = (new ServerRequest())
        ->withMethod('PATCH')
        ->withAttribute('resourceName', 'users')
        ->withAttribute('primaryKeyValue', '123')
        ->withParsedBody(['status' => 'active']);

    $response = $this->middleware->process($request, $this->handler);

    $this->assertEquals(200, $response->getStatusCode());
}
```

---

### Adding Request Validation

**Example: Validate required fields in CreateHandler**

```php
class CreateHandler extends AbstractHandler
{
    private array $requiredFields = ['name', 'email'];

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        // Validate required fields
        $missing = array_diff($this->requiredFields, array_keys($data));
        if (!empty($missing)) {
            return new JsonResponse([
                'error' => 'Missing required fields',
                'fields' => $missing
            ], 400);
        }

        // Continue with create...
        $item = $this->dataStore->create($data);
        return new JsonResponse($item, 201);
    }
}
```

---

### Testing Checklist

When modifying middleware:

- [ ] Test all HTTP methods (GET, POST, PUT, PATCH, DELETE, HEAD)
- [ ] Test with and without RQL queries
- [ ] Test with and without resource IDs
- [ ] Test error cases (404, 400, 500)
- [ ] Test headers (If-Match, With-Content-Range, download)
- [ ] Test bulk operations (arrays vs single objects)
- [ ] Test fallback code paths (DataStoresInterface vs DataStoreInterface)
- [ ] Test Content-Range calculation accuracy
- [ ] Test exception handling (DataStoreApi catch block)
- [ ] Test handler order precedence
- [ ] Performance test large datasets (CSV export, bulk updates)
- [ ] Security test (CSV injection, exception message leakage)

---

## Summary Statistics

| Metric | Value |
|--------|-------|
| **Total Files** | 22 |
| **Total LOC** | 1,401 |
| **Core Middleware** | 8 files (541 LOC) |
| **Handlers** | 10 files (697 LOC) |
| **Factories** | 2 files (73 LOC) |
| **Request Attributes** | 6+ |
| **HTTP Methods Supported** | 6 (GET, POST, PUT, PATCH, DELETE, HEAD) |
| **Response Status Codes** | 6 (200, 201, 204, 400, 404, 500) |
| **PSR Standards** | 3 (PSR-7, PSR-15, PSR-3) |
| **Critical Issues** | 4 (usleep loops, silent failures, CSV injection, memory) |
| **Medium Issues** | 4 (duplicate queries, handler ordering, attribute pollution, empty TODO) |
| **Low Issues** | 4 (deprecated header, magic numbers, naming, unused class) |

---

## Conclusion

The Middleware Module provides a **well-architected HTTP REST API gateway** with strong PSR compliance and clean separation of concerns. The middleware pipeline (ResourceResolver → RequestDecoder → Determinator) and handler chain pattern enable elegant request routing and processing.

### Strengths

✅ Full PSR-7/PSR-15 compliance for interoperability
✅ Clean handler chain of responsibility pattern
✅ Comprehensive HTTP method support
✅ RQL query integration
✅ Global exception handling
✅ Content negotiation for error responses

### Critical Issues

❌ **Performance**: usleep() loops add artificial delays
❌ **Reliability**: Silent failures in bulk operations
❌ **Security**: CSV injection vulnerability
❌ **Scalability**: CSV export loads entire dataset into memory

### Recommendations

**Phase 1 (Critical):**
1. Remove usleep() calls from fallback loops
2. Return detailed success/failure breakdown for bulk operations
3. Implement CSV value escaping
4. Use streaming response for CSV export

**Phase 2 (Performance):**
5. Eliminate duplicate query execution in QueryHandler
6. Implement SQL-based counting instead of separate count query

**Phase 3 (Maintainability):**
7. Document handler order requirements
8. Reduce request attribute pollution with context object
9. Standardize header naming conventions

The module is **production-ready** but would benefit significantly from the critical fixes in Phase 1.

---

_Generated by `document-project` workflow (deep-dive mode)_
_Base Documentation: [docs/index.md](./index.md)_
_Related Deep-Dives:_
- _[RQL Module](./deep-dive-rql-module.md)_
- _[DataStore Module](./deep-dive-datastore-module.md)_
_Scan Date: 2025-12-05_
_Analysis Mode: Exhaustive_