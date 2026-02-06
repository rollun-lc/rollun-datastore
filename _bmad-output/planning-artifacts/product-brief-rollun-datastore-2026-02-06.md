---
stepsCompleted: [1, 2, 3, 4, 5]
inputDocuments:
  - docs/index.md
  - docs/datastore-schema.md
  - docs/datastore_methods.md
  - docs/request-logs.md
  - docs/typecasting.md
  - docs/rql.md
date: 2026-02-06
author: Iliya
---

# Product Brief: rollun-datastore

<!-- Content will be appended sequentially through collaborative workflow steps -->

## Executive Summary

`rollun-datastore` needs a read-only Elasticsearch adapter to keep storage access consistent across the platform.
Today Elasticsearch logs are read directly via the ES client, while the rest of the system works through `DataStore` abstractions.

The MVP introduces a dedicated `DataStore` implementation for Elasticsearch index reads. It focuses on one core capability: read log content by identifier (for example, `id`) and return the full record in the same shape expected by existing datastore consumers.
All non-MVP operations (write operations and advanced querying) are explicitly unsupported and must throw clear exceptions.

This enables teams to consume Elasticsearch through the same architectural contract as other storages, reducing integration differences and making ES a first-class storage backend in the platform.

---

## Core Vision

### Problem Statement

Elasticsearch is currently an outlier in the architecture: logs are read through a direct ES client integration instead of the platform-standard `DataStore` interface.

### Problem Impact

- Breaks the "single storage abstraction" principle used across the system.
- Increases cognitive load for teams integrating log reads.
- Creates a special-case path for Elasticsearch that does not align with existing datastore-based workflows.

### Why Existing Solutions Fall Short

- Direct usage of `elasticsearch/elasticsearch` works technically, but bypasses the shared `DataStore` contract.
- Existing datastore consumers cannot reuse the same integration style for Elasticsearch logs without custom handling.
- Current approach does not provide architectural consistency across storage backends.

### Proposed Solution

Implement a read-only Elasticsearch adapter that conforms to the project's `DataStore` interface and uses the existing configured ES client under the hood.

MVP scope:
- Support record read by identifier from a configured Elasticsearch index.
- Return full log content via the datastore contract.
- Explicitly reject unsupported operations (`query`/RQL, create, update, delete, etc.) with clear exceptions.

### Key Differentiators

- Unified storage contract across all backends, including Elasticsearch.
- Minimal MVP scope focused on immediate value (read logs by id).
- Smooth adoption in codebases already built around `DataStore` patterns.

## Target Users

### Primary Users

Primary user is a backend developer integrating data access through the platform `DataStore` abstraction.

**Persona: Iliya (Backend Developer)**
- Works in a codebase where storage integrations are expected to follow `DataStore` patterns.
- Wants Elasticsearch logs to be consumed the same way as other storages, without custom one-off adapters per use case.
- Main motivation is implementation speed and architectural consistency.
- Current friction: Elasticsearch is accessed directly via client code, which breaks the usual datastore workflow.

**Success criteria for this user**
- Can read a log from Elasticsearch index by identifier (`id`) using a datastore-style interface.
- Does not need to write custom integration glue for each consumer.
- Gets predictable behavior aligned with existing datastore usage in the project.

### Secondary Users

N/A for MVP.
Current scope identifies only one direct user segment: backend developer implementing and consuming the adapter.

### User Journey

**Discovery**
- Developer needs Elasticsearch-backed logs to be accessed in the same architectural style as other storages.
- Direct ES client usage is identified as an inconsistency in current integration flow.

**Onboarding**
- Configure adapter with existing Elasticsearch client and target index.
- Start using read path by passing log identifier (`id`).

**Core Usage**
- Request log record by `id` through datastore adapter.
- Receive full log content in a consistent contract for downstream application logic.

**Success Moment**
- The same integration style used for other datastores now works for Elasticsearch reads, without direct ES-specific handling in business code.

**Long-term**
- Adapter becomes standard entry point for ES reads in this module.
- Foundation is prepared for possible future write support, while MVP remains strictly read-only.

## Success Metrics

### User Success Metrics

- Developer can read Elasticsearch log by identifier through `DataStore` interface (`read(id)`) without direct ES client usage.
- Read operation returns full source payload of the log document for downstream consumers.
- Read-only contract is explicit: unsupported operations are rejected with clear exceptions.

### Business Objectives

- Make Elasticsearch access consistent with existing platform storage architecture (`DataStore`-first approach).
- Eliminate one-off integration style for ES reads in modules using this adapter.
- Deliver an MVP foundation for future expansion (possible write operations later), while keeping current scope strictly read-only.

### Key Performance Indicators

- **Read Path Adoption:** for migrated read scenarios, log retrieval is performed via `DataStore` adapter, not direct ES client calls.
- **Read Success Rate:** 100% successful retrieval on agreed MVP test cases for existing log IDs.
- **Payload Fidelity:** returned record preserves the original Elasticsearch document payload (`_source`) without schema hardcoding, so heterogeneous log structures are supported.
- **Contract Clarity:** 100% of unsupported operations return documented "not supported in read-only MVP" exceptions.

**Example payload fields and sample values (illustrative only):**
- `@timestamp`: `2026-02-06 13:33:57.313`
- `_id`: `mmAoM5wBaGKzpx8EyxU6`
- `_index`: `all_logs-2026.06`
- `_type`: `_doc`
- `context_str`: `[]`
- `index_name`: `carriers`
- `level`: `debug`
- `lifecycle_token`: `21934_385OQQZIF8LJ13PCYANH4YX6`
- `message`: `Converting csv file to ZipCode`
- `parent_lifecycle_token`: `12273_LSNBI3YIEFIY1TWTA63M4YNV`
- `port`: `45,502`
- `priority`: `7`

## MVP Scope

### Core Features

- Implement Elasticsearch `DataStore` adapter with read-only behavior.
- Support only record retrieval by identifier: `read(id)`.
- Use existing project configuration/DI to provide Elasticsearch client and index settings.
- Return log data in datastore-compatible structure for existing consumers.
- For unsupported methods, return standard `Not Implemented` behavior (no custom exception hierarchy in MVP).

### Out of Scope for MVP

- `query`/RQL support.
- Any write operations: `create`, `update`, `delete`, `rewrite`, bulk operations.
- Caching layer.
- Circuit breaker and resilience add-ons.
- Advanced optimization and performance tuning beyond basic working behavior.

### MVP Success Criteria

- Developer can read a log by ID from Elasticsearch through `DataStore` interface, without direct ES client usage in consumer code.
- Adapter is wired through existing config/DI and works with current environment setup.
- Unsupported operations consistently return `Not Implemented`.
- Existing relevant datastore unit tests pass (for multiple implementations, not just one example), including examples such as:
  - `test/unit/DataStore/DataStore/CsvBaseTestCase.php`
  - `test/unit/DataStore/DataStore/MemoryTest.php`
- New Elasticsearch adapter unit tests are added by analogy with existing datastore test patterns.

### Future Vision

- Extend adapter capabilities after MVP with `query`/RQL support.
- Evaluate write support (`create`/`update`/`delete`) after read-only path is proven stable.
- Consider batch operations and advanced resilience patterns only after core adoption.
