---
stepsCompleted: [1, 2, 3, 4, 5, 6, 7, 8]
inputDocuments:
  - _bmad-output/planning-artifacts/product-brief-rollun-datastore-2026-02-06.md
  - _bmad-output/planning-artifacts/prd.md
  - _bmad-output/planning-artifacts/validation-report-prd.md
  - docs/index.md
  - docs/datastore-schema.md
  - docs/datastore_methods.md
  - docs/request-logs.md
  - docs/rql.md
  - docs/typecasting.md
workflowType: 'architecture'
project_name: 'rollun-datastore'
user_name: 'Iliya'
date: '2026-02-06'
lastStep: 8
status: 'complete'
completedAt: '2026-02-06'
---

# Architecture Decision Document

_This document builds collaboratively through step-by-step discovery. Sections are appended as we work through each architectural decision together._

## Project Context Analysis

### Requirements Overview

**Functional Requirements:**
The PRD defines a focused capability set around a read-only Elasticsearch adapter behind the existing `DataStore` contract. The core architecture must support: read-by-id, strict read-only enforcement, DI/config-based wiring, troubleshooting workflows, incremental adoption in consumer modules, and robust automated verification.

**Non-Functional Requirements:**
Architecture decisions are constrained by measurable requirements for read-path performance, security boundary compliance, scalability of read traffic without contract breakage, and integration compatibility with existing datastore patterns and CI test gates.

**Scale & Complexity:**
The product scope is narrow (MVP read-only), but architectural quality expectations are high due to compatibility and consistency requirements across an existing ecosystem.

- Primary domain: backend developer tool / datastore integration layer
- Complexity level: medium
- Estimated architectural components: 5-7

### Technical Constraints & Dependencies

- Must integrate with existing DI/config and service container patterns.
- Must use existing Elasticsearch client configuration and credential handling model.
- Must preserve heterogeneous payload structures (no rigid schema requirement in MVP).
- Must return deterministic `Not Implemented` for unsupported operations.
- Must remain compatible with existing datastore testing conventions and CI expectations.
- Must support migration away from direct Elasticsearch client read paths.

### Cross-Cutting Concerns Identified

- Contract stability for `read(id)` across MVP and post-MVP evolution
- Error semantics consistency (not-found vs unsupported operation behavior)
- Payload fidelity guarantees for diagnostic and support use cases
- Testability and regression safety in existing datastore suites
- Integration consistency across multiple consumer modules

## Starter Template Evaluation

### Primary Technology Domain

Backend PHP library / datastore integration (developer tool, brownfield)

### Starter Options Considered

- Existing repository baseline only (no external starter templates)

### Selected Starter: Existing Repository Baseline

**Rationale for Selection:**
- Project is brownfield with established architecture, CI, and coding conventions.
- Objective is targeted integration of Elasticsearch read-only datastore adapter into the current codebase.
- External starters are intentionally excluded to avoid structural divergence and migration overhead.

**Initialization Command:**

```bash
composer install
```

**Required Elasticsearch Client Library:**

```json
"elasticsearch/elasticsearch"
```

**DataStore Contract Requirement:**
- Elasticsearch adapter must implement `rollun\datastore\DataStore\Interfaces\DataStoreInterface`.
- Contract source: `src/DataStore/src/DataStore/Interfaces/DataStoreInterface.php`.
- Read-path behavior must comply with repository `DataStore` semantics.
- MVP scope is read-focused; unsupported operations return `Not Implemented`.

**Library Usage Constraint:**
- All Elasticsearch communication in this adapter must go through `elasticsearch/elasticsearch`.
- Existing project DI/config must provide and manage the Elasticsearch client instance.

**Architectural Decisions Provided by Baseline:**

**Language & Runtime:**
- Existing PHP runtime and package conventions are reused as-is.

**Build Tooling:**
- Existing repository tooling and scripts remain source of truth.

**Testing Framework:**
- Existing datastore-oriented unit testing patterns and CI checks are reused.

**Code Organization:**
- Current project structure is the baseline; new adapter follows established module patterns.

**Development Experience:**
- Current team workflow is preserved; no additional scaffolding layer introduced.

**Note:** First implementation story should verify baseline environment and run existing tests before adapter implementation.

## Core Architectural Decisions

### Decision Priority Analysis

**Critical Decisions (Block Implementation):**
- Use `DataStoreInterface` as primary contract (`src/DataStore/src/DataStore/Interfaces/DataStoreInterface.php`)
- Implement read-only adapter behavior with strict `Not Implemented` for unsupported operations
- Use official Elasticsearch client package `elasticsearch/elasticsearch`
- Define deterministic read semantics for found/not-found/error outcomes
- Preserve heterogeneous payload fidelity (no rigid schema mapping in MVP)

**Important Decisions (Shape Architecture):**
- Adapter is integrated as datastore service via existing DI/config patterns
- Keep existing repository structure and testing patterns
- Introduce adapter-focused tests alongside existing datastore suites
- Add migration-safe integration path from direct ES client calls

**Deferred Decisions (Post-MVP):**
- `query`/RQL support
- write-path operations
- advanced caching/resilience patterns

### Data Architecture

- **Primary data source:** existing Elasticsearch indices (external system of record for log documents)
- **Adapter data model approach:** pass-through payload model with minimal normalization for datastore compatibility
- **Identifier strategy:** `read(id)` uses datastore identifier semantics mapped to Elasticsearch document lookup
- **Schema strategy:** no fixed schema enforcement in MVP; support variable log structures
- **Caching strategy:** no adapter-level cache in MVP (explicitly deferred)

### Authentication & Security

- **Auth model:** reuse existing Elasticsearch client auth setup from project configuration (no hardcoded secrets)
- **Authorization boundary:** adapter stays inside existing service-container boundaries; no direct secret handling in consumer code
- **Security behavior:** unsupported write operations always fail with `Not Implemented` (no implicit fallback behavior)

### API & Communication Patterns

- **Contract surface:** datastore-style methods, MVP read path centered on `read(id)`
- **Error semantics:** deterministic behavior for not-found and unsupported-operation paths
- **Internal communication:** all ES calls routed through `elasticsearch/elasticsearch` client instance provided by DI/config
- **Versioning strategy:** align client major version with Elasticsearch cluster major version (8.x with 8.x, 9.x with 9.x)

### Frontend Architecture

- N/A for this scope (backend library integration only)

### Infrastructure & Deployment

- **Deployment model:** no new runtime platform; integrate into existing package lifecycle
- **Build/init baseline:** `composer install` with current repository pipeline
- **CI strategy:** keep existing unit suites, add adapter tests as merge gate for read, not-found, and unsupported-method behavior

### Decision Impact Analysis

**Implementation Sequence:**
1. Add dependency and DI/config wiring for Elasticsearch client-backed datastore service
2. Implement adapter contract behavior (`DataStoreInterface`) with read-only enforcement
3. Implement payload mapping and deterministic error semantics
4. Add adapter-specific unit tests and run existing datastore regression suites
5. Migrate selected consumer read paths from direct ES client to datastore adapter

**Cross-Component Dependencies:**
- Contract decisions drive middleware/handler compatibility
- Error semantics affect consumer modules and troubleshooting flows
- Payload fidelity influences diagnostics and downstream log processing
- Test strategy gates rollout safety and migration confidence

## Implementation Patterns & Consistency Rules

### Pattern Categories Defined

**Critical Conflict Points Identified:**
8 areas where AI agents could diverge: adapter naming, service keys, identifier mapping, read/not-found semantics, unsupported-method behavior, payload mapping, exception/logging conventions, test layout and assertions.

### Naming Patterns

**DataStore Adapter Naming:**
- Class naming: `*DataStore` suffix for datastore implementations.
- Elasticsearch adapter canonical name: `ElasticsearchDataStore`.
- Namespace follows existing datastore module layout.

**Service Naming Conventions:**
- Service names in config are lowercase snake_case (for example: `elasticsearch_datastore`).
- Avoid ambiguous aliases (`es`, `elastic`) unless mapped explicitly in one place.

**Identifier Naming:**
- Internal datastore identifier key remains `id`.
- Elasticsearch document identity mapping is explicit and centralized in adapter logic.

### Structure Patterns

**Project Organization:**
- New adapter code goes under existing datastore source tree, aligned with current module conventions.
- No new top-level module/package introduced for MVP.

**Test Organization:**
- Adapter tests follow existing datastore test structure under `test/unit/DataStore/DataStore/`.
- Separate tests for: success read, not-found, unsupported operations, payload fidelity.

### Format Patterns

**Read Result Format:**
- `read(id)` returns datastore-compatible record payload.
- No forced schema normalization for heterogeneous log structures in MVP.

**Error/Unsupported Format:**
- Unsupported operations always produce `Not Implemented`.
- Not-found handling remains deterministic and consistent across consumer flows.

### Communication Patterns

**Logging Patterns:**
- Adapter logs use existing project logger conventions.
- Log messages include operation context (`read`, identifier, index when available) without leaking secrets.

**Exception Patterns:**
- Do not introduce custom exception taxonomy in MVP for unsupported operations.
- Keep behavior aligned with repository expectations and existing handlers.

### Process Patterns

**Validation & Test Gate:**
- Every adapter change must pass relevant existing datastore unit suites plus new adapter tests.
- No merge without green checks for read-path and unsupported-method cases.

**Migration Pattern:**
- Replace direct ES client reads incrementally per consumer module.
- Preserve behavior parity at each migration step via targeted tests.

### Container & Elasticsearch Client Factory Pattern

- Elasticsearch client is created only through `Utils\Elastic\ElasticSearchClientAbstractFactory`.
- Datastore adapter must not call `ClientBuilder::create()` directly.
- Adapter gets ES client from container service `ElasticSearchClient`.
- Client configuration source:
- `config[ElasticSearchClientAbstractFactory::class]['ElasticSearchClient']`
- Host/auth settings come from environment variables:
- `ELASTIC_HOST_1`, `ELASTIC_HOST_2`, `ELASTIC_USER`, `ELASTIC_PASS`.
- Supported factory options:
- `hosts` (required)
- `logger` (optional)
- `retries` (optional)

### Enforcement Guidelines

**All AI Agents MUST:**
- Implement the adapter against `rollun\datastore\DataStore\Interfaces\DataStoreInterface`.
- Use only `elasticsearch/elasticsearch` for Elasticsearch communication.
- Preserve read-only MVP boundaries and deterministic contract behavior.
- Use container-managed Elasticsearch client from `ElasticSearchClientAbstractFactory`.

**Pattern Enforcement:**
- Code review checklist enforces naming/format/test/container patterns.
- CI test suite enforces behavioral consistency.
- Any deviation must be documented in architecture decision updates.

### Pattern Examples

**Good Examples:**
- `ElasticsearchDataStore::read($id)` returns full payload-compatible record.
- `create/update/delete/query` return `Not Implemented` in MVP.
- Test names explicitly encode expected behavior (`testReadByIdReturnsRecord`, `testUnsupportedMethodThrowsNotImplemented`).
- Adapter receives client via container (`$container->get('ElasticSearchClient')`), not local builder creation.

**Anti-Patterns:**
- Introducing direct ES client calls in consumer modules after adapter introduction.
- Datastore adapter creating ES client with `ClientBuilder::create()` directly.
- Silent fallback behavior for unsupported methods.
- Adding schema-hardcoded field mapping in MVP adapter.

## Project Structure & Boundaries

### Complete Project Directory Structure

```text
rollun-datastore/
├── composer.json
├── composer.lock
├── phpunit.xml
├── phpcs.xml
├── rector.php
├── README.md
├── docs/
│   ├── index.md
│   ├── datastore_methods.md
│   ├── datastore-schema.md
│   ├── request-logs.md
│   ├── rql.md
│   └── typecasting.md
├── config/
│   └── autoload/
│       └── *.php
├── src/
│   ├── DataStore/
│   │   └── src/
│   │       └── DataStore/
│   │           ├── Interfaces/
│   │           │   ├── DataStoreInterface.php
│   │           │   ├── DataStoresInterface.php
│   │           │   └── ReadInterface.php
│   │           ├── Factory/
│   │           │   ├── DataStoreAbstractFactory.php
│   │           │   └── ElasticsearchDataStoreFactory.php            # new
│   │           ├── ElasticsearchDataStore.php                       # new
│   │           ├── Exception/
│   │           │   └── NotImplementedException.php                  # optional reuse if exists
│   │           └── ...
│   ├── Repository/
│   └── Uploader/
├── test/
│   └── unit/
│       └── DataStore/
│           └── DataStore/
│               ├── MemoryTest.php
│               ├── CsvBaseTestCase.php
│               ├── ElasticsearchDataStoreTest.php                   # new
│               └── ...
└── _bmad-output/
    └── planning-artifacts/
        ├── prd.md
        └── architecture.md
```

### Architectural Boundaries

**API Boundaries:**
- Public contract boundary: `DataStoreInterface` (`read`, `has`, `query`, CRUD surface).
- MVP functional boundary: adapter supports read path only; unsupported methods are explicit `Not Implemented`.

**Component Boundaries:**
- `ElasticsearchDataStore` owns mapping from datastore read semantics to Elasticsearch document retrieval.
- Factory layer owns DI/container construction and service wiring.
- Consumer modules interact only through datastore interface, never direct ES client.

**Service Boundaries:**
- Elasticsearch client creation boundary: only via existing `ElasticSearchClientAbstractFactory` + container service `ElasticSearchClient`.
- Adapter boundary: receives ready client instance; does not construct client directly.

**Data Boundaries:**
- Source-of-truth: Elasticsearch index documents.
- Mapping boundary: preserve payload fidelity; avoid rigid schema conversion in MVP.
- Identifier boundary: datastore `id` is mapped deterministically to ES document lookup key.

### Requirements to Structure Mapping

**Feature/FR Mapping:**
- Read-by-id capability (FR2/FR3/FR4): `src/.../ElasticsearchDataStore.php`
- Read-only enforcement (FR6/FR7/FR8): `src/.../ElasticsearchDataStore.php`
- DI/config integration (FR9/FR10/FR11): `src/.../Factory/ElasticsearchDataStoreFactory.php` + `config/autoload/*.php`
- Troubleshooting payload behavior (FR12-FR14): adapter read path + tests
- Migration support (FR15-FR17): consumer integration guidelines + tests
- Testability (FR18-FR22): `test/unit/DataStore/DataStore/ElasticsearchDataStoreTest.php`

**Cross-Cutting Concerns:**
- Error semantics: adapter + tests
- Payload fidelity: adapter + fixtures
- Contract stability: interface boundary + regression tests
- Logging conventions: adapter uses existing logger wiring if present

### Integration Points

**Internal Communication:**
- Consumer service -> container-resolved datastore service -> `ElasticsearchDataStore` -> injected ES client.

**External Integrations:**
- `elasticsearch/elasticsearch` client to Elasticsearch cluster endpoints from env-backed config.

**Data Flow:**
1. Consumer calls `read(id)`
2. Adapter performs ES lookup with injected client
3. Adapter maps result to datastore-compatible record
4. Adapter returns full payload or deterministic not-found behavior

### File Organization Patterns

**Configuration Files:**
- Keep ES client host/auth settings in existing config/autoload + env vars (`ELASTIC_HOST_1`, `ELASTIC_HOST_2`, `ELASTIC_USER`, `ELASTIC_PASS`).
- Datastore service registration in existing `dataStore` config section.

**Source Organization:**
- New ES adapter classes stay inside current `DataStore` module tree.
- No new top-level package/module for MVP.

**Test Organization:**
- Unit tests colocated in existing datastore unit-test tree.
- Required suites: adapter tests + selected existing datastore regression suites.

**Asset Organization:**
- N/A for backend library scope.

### Development Workflow Integration

**Development Server Structure:**
- Standard package workflow; no new runtime host required for MVP development.

**Build Process Structure:**
- Existing Composer scripts and QA tooling remain baseline (`test`, `phpcs`, `rector`).

**Deployment Structure:**
- Library release path remains unchanged; adapter ships as part of existing package.

## Architecture Validation Results

### Coherence Validation ✅

**Decision Compatibility:**
Core decisions are compatible: DataStore contract, read-only scope, container-managed ES client, and payload fidelity rules align without contradiction.

**Pattern Consistency:**
Implementation patterns support decisions: naming, error semantics, factory usage, and test gates are consistent and enforceable.

**Structure Alignment:**
Project structure supports architectural boundaries and integration points for a brownfield library extension.

### Requirements Coverage Validation ✅

**Epic/Feature Coverage:**
MVP feature set is covered by adapter, factory wiring, and test structure.

**Functional Requirements Coverage:**
All FR capability areas are covered:
- read-by-id capability
- read-only enforcement
- DI/config integration
- troubleshooting payload access
- integration migration path
- testability

**Non-Functional Requirements Coverage:**
Performance, security, scalability, and integration NFR categories are represented with architectural hooks and measurable validation intent.

### Implementation Readiness Validation ✅

**Decision Completeness:**
Critical decisions are documented, including contract, library usage, and service boundary rules.

**Structure Completeness:**
Directory and file placement for adapter, tests, and config integration are defined.

**Pattern Completeness:**
Conflict-prone areas (naming, errors, client lifecycle, tests) are explicitly constrained for multi-agent consistency.

### Gap Analysis Results

**Critical Gaps:** None

**Important Gaps:**
- Clarify factory layering to avoid duplicate ES client construction:
  datastore factory must consume existing `ElasticSearchClient` container service.

**Nice-to-Have Gaps:**
- Add a compact implementation checklist for first PR.
- Add explicit fixture strategy for heterogeneous payload tests.

### Validation Issues Addressed

- Confirmed architecture does not introduce external starter templates.
- Confirmed Elasticsearch communication must go through `elasticsearch/elasticsearch` using container-managed client.
- Confirmed adapter contract is `DataStoreInterface` at `src/DataStore/src/DataStore/Interfaces/DataStoreInterface.php`.

### Architecture Completeness Checklist

**✅ Requirements Analysis**
- [x] Project context thoroughly analyzed
- [x] Scale and complexity assessed
- [x] Technical constraints identified
- [x] Cross-cutting concerns mapped

**✅ Architectural Decisions**
- [x] Critical decisions documented
- [x] Technology stack specified
- [x] Integration patterns defined
- [x] Performance/security/scalability considerations addressed

**✅ Implementation Patterns**
- [x] Naming conventions established
- [x] Structure patterns defined
- [x] Communication patterns specified
- [x] Process patterns documented

**✅ Project Structure**
- [x] Complete directory structure defined
- [x] Component boundaries established
- [x] Integration points mapped
- [x] Requirements-to-structure mapping complete

### Architecture Readiness Assessment

**Overall Status:** READY FOR IMPLEMENTATION  
**Confidence Level:** High

**Key Strengths:**
- Clear contract-first approach
- Strong read-only boundary discipline
- Existing ecosystem alignment (brownfield-safe)
- Explicit anti-conflict patterns for implementation agents

**Areas for Future Enhancement:**
- Query/RQL architecture extension
- write-path extension strategy
- optional resilience/caching layers post-MVP

### Implementation Handoff

**AI Agent Guidelines:**
- Implement against `DataStoreInterface`.
- Use container-provided `ElasticSearchClient`; do not build client in adapter.
- Keep unsupported methods explicit (`Not Implemented`).
- Preserve payload fidelity and deterministic error semantics.
- Keep tests green in existing datastore suites + new adapter tests.

**First Implementation Priority:**
- Wire adapter service using existing `ElasticSearchClientAbstractFactory` output and validate baseline tests before adapter logic extension.

## Architecture Completion Summary

### Workflow Completion

**Architecture Decision Workflow:** COMPLETED ✅
**Total Steps Completed:** 8
**Date Completed:** 2026-02-06
**Document Location:** _bmad-output/planning-artifacts/architecture.md

### Final Architecture Deliverables

**📋 Complete Architecture Document**

- All core architectural decisions documented and constrained for consistent implementation.
- Implementation patterns defined to prevent multi-agent conflicts.
- Complete project structure and boundaries mapped to repository reality.
- Requirements-to-architecture mapping captured for handoff.
- Validation results included with readiness assessment.

**🏗️ Implementation Ready Foundation**

- Contract-first architecture based on `DataStoreInterface`.
- Explicit read-only MVP boundary with deterministic unsupported-operation behavior.
- Container-managed Elasticsearch client pattern enforced.
- Test-gated integration strategy for safe migration.

**📚 AI Agent Implementation Guide**

- Clear technical boundaries and consistency rules.
- File-level structure guidance for adapter, factory, config, and tests.
- Anti-patterns explicitly documented.

### Implementation Handoff

**For AI Agents:**
This architecture is the source of truth for implementation decisions in `rollun-datastore`.

**First Implementation Priority:**
Use existing repository baseline (`composer install`), wire adapter service with existing `ElasticSearchClientAbstractFactory` output, then validate baseline tests before adapter logic.

**Development Sequence:**

1. Wire datastore adapter service using container-provided `ElasticSearchClient`.
2. Implement `ElasticsearchDataStore` against `DataStoreInterface` with read-only enforcement.
3. Implement deterministic read/not-found/unsupported semantics.
4. Add adapter unit tests and run existing datastore regression suites.
5. Migrate selected consumer read paths from direct ES client to datastore adapter.

### Quality Assurance Checklist

**✅ Architecture Coherence**

- [x] Decisions are compatible and non-contradictory
- [x] Patterns align with decisions
- [x] Structure supports boundaries and integrations

**✅ Requirements Coverage**

- [x] Functional requirements architecturally covered
- [x] NFR categories addressed with architectural hooks
- [x] Cross-cutting concerns included

**✅ Implementation Readiness**

- [x] Decisions are actionable
- [x] Consistency rules are enforceable
- [x] Structure is concrete and repository-aligned

### Project Success Factors

**🎯 Clear Decision Framework**
All critical technical choices are explicit, constrained, and traceable.

**🔧 Consistency Guarantee**
Implementation patterns reduce divergence across AI agents and contributors.

**📋 Complete Coverage**
Architecture maps directly to MVP scope and contractual behavior.

**🏗️ Brownfield Safety**
Decisions preserve existing repository conventions and integration surfaces.

---

**Architecture Status:** READY FOR IMPLEMENTATION ✅

**Next Phase:** Implementation can proceed using this architecture as the primary technical guide.
