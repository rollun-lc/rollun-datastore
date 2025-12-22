# Component Inventory

## DataStore Module

### Core

- DataStore interfaces (DataStoreInterface, DataStoresInterface, ReadInterface, etc.)
- DataStore implementations (DbTable, CsvBase, CsvIntId, Memory, HttpClient, SerializedDbTable)
- DataSource implementations (DbTableDataSource, MemoryConfig)
- Query & RQL (RqlParser, RqlQuery, QueryAdapters, TokenParsers)

### Middleware & Handlers

- Middleware: DataStoreApi, DataStoreRest, ResourceResolver, RequestDecoder, JsonRenderer
- Handlers: Create, Read, Update, Delete, Query, MultiCreate, Refresh, Head, DownloadCsv, Error, QueriedUpdate

### Schema / Types / Formatters

- Schema: ArraySchemaRepository, SchemaApiRequestHandler, Scheme classes
- Types: TypeInt, TypeFloat, TypeString, TypeBoolean, TypeJson, TypeDateTimeImmutable
- Formatters: JsonFormatter, StringFormatter, IntFormatter, FloatFormatter, BooleanFormatter, DateTimeOrNullFormatter

### Aspects / Eventing

- Aspect* classes with Laminas EventManager integration

## Repository Module

- ModelRepository + ModelAbstract
- Casting: JsonCasting, DateCasting, ArrayCasting, ObjectCasting, SerializeCasting
- Traits: ModelCastingTrait, ModelArrayAccess, ModelDataTime

## Uploader Module

- Uploader
- Iterator: DataStorePack
- Abstract factories for uploader
