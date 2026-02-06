---
stepsCompleted:
  - step-01-init
  - step-02-discovery
  - step-03-success
  - step-04-journeys
  - step-05-domain
  - step-06-innovation
  - step-07-project-type
  - step-08-scoping
  - step-09-functional
  - step-10-nonfunctional
  - step-11-polish
  - step-12-complete
inputDocuments:
  - _bmad-output/planning-artifacts/product-brief-rollun-datastore-2026-02-06.md
  - docs/index.md
  - docs/datastore-schema.md
  - docs/datastore_methods.md
  - docs/request-logs.md
  - docs/rql.md
  - docs/typecasting.md
documentCounts:
  productBrief: 1
  research: 0
  brainstorming: 0
  projectDocs: 6
  projectContext: 0
classification:
  projectType: developer_tool
  domain: general
  complexity: low
  projectContext: brownfield
workflowType: 'prd'
workflow: 'edit'
date: 2026-02-06
lastEdited: 2026-02-06
editHistory:
  - date: 2026-02-06
    changes: "Added Executive Summary, made NFRs measurable, added developer_tool code examples and migration guide."
---

# Product Requirements Document - rollun-datastore

**Author:** Iliya
**Date:** 2026-02-06

## Executive Summary

`rollun-datastore` requires a read-only Elasticsearch adapter to keep storage access consistent across the platform.
Today, Elasticsearch logs are accessed via direct client calls, while other storages are consumed through `DataStore` abstractions.

This PRD defines an MVP that delivers `read(id)` through a datastore contract, preserves full heterogeneous log payloads, and rejects unsupported operations with `Not Implemented`.
The differentiator is architectural consistency: Elasticsearch becomes a first-class backend behind the same interface used across existing modules.

## Success Criteria

### User Success

- Developer retrieves Elasticsearch logs through the `DataStore` interface using `read(id)` only.
- The returned result contains the full log payload required by consumers.
- Consumer code no longer depends on direct Elasticsearch client usage for the migrated read path.
- "Done" for the user is achieved when read-by-id works through datastore abstraction in daily development flow.

### Business Success

- Elasticsearch integration becomes consistent with the platform-wide `DataStore` architecture.
- Integration debt is reduced by removing one-off ES access patterns in the read path.
- Teams can adopt Elasticsearch reads using the same abstraction model as other storages.
- No fixed delivery date is defined at this stage; success is evaluated by functional and architectural outcomes.

### Technical Success

- Read-only adapter behavior is clear and predictable.
- `read(id)` works reliably with configured Elasticsearch client and index via existing DI/config.
- Unsupported operations return `Not Implemented` consistently.
- Relevant existing datastore unit tests pass, and adapter-specific tests are added by analogy with existing test patterns.
- Metrics are currently defined qualitatively for MVP (without hard numeric thresholds).

### Measurable Outcomes

- Read path migration is observable: target consumer scenarios use datastore adapter instead of direct ES client.
- Full payload fidelity is preserved for heterogeneous log documents.
- Unsupported-method behavior is consistently enforced across adapter surface.
- Test signal is green for required existing suites plus new adapter tests.

## Product Scope

### MVP - Minimum Viable Product

- Read-only Elasticsearch datastore adapter.
- Support only `read(id)`.
- Use existing configuration/DI for Elasticsearch client and index.
- Preserve original log payload without fixed schema assumptions.
- Return `Not Implemented` for unsupported operations.
- Keep scope implementation-focused with essential test coverage.

### Growth Features (Post-MVP)

- Potential expansion to `query`/RQL support.
- Additional ergonomics and broader integration capabilities after MVP validation.

### Vision (Future)

- Longer-term datastore parity for Elasticsearch beyond read-only usage, if validated by adoption and technical fit.
- Future expansion remains intentionally open without a detailed horizon at this stage.

## User Journeys

### Journey 1 - Primary Developer (Success Path)

**Opening Scene**
Iliya is adding a new log-reading flow in an existing backend module. The project standard is to use `DataStore` abstractions, but Elasticsearch has been a special-case integration.

**Rising Action**
He configures the Elasticsearch datastore adapter through existing DI/config, sets the target index, and calls `read(id)` from service code that already expects datastore-style access.

**Climax**
`read(id)` returns the full log payload from Elasticsearch, and consumer code works without direct ES client logic.

**Resolution**
The feature is delivered with architecture consistency preserved. Elasticsearch reads now match the same integration model used for other storages.

### Journey 2 - Primary Developer (Edge Case)

**Opening Scene**
During implementation, Iliya receives an ID that does not exist in the target index.

**Rising Action**
He runs the same datastore call path and checks behavior for not-found and unsupported operations (`query`, `create`, etc.).

**Climax**
The adapter behavior is predictable: read path handles missing records consistently, and unsupported methods return `Not Implemented`.

**Resolution**
He can safely handle edge conditions in application logic without introducing ES-specific branching across the codebase.

### Journey 3 - Operations/Admin (Configuration & Environment)

**Opening Scene**
An operations-focused engineer prepares environment-specific settings for the module.

**Rising Action**
They wire Elasticsearch client and index in existing config/DI structure and verify that service resolution returns the correct datastore adapter.

**Climax**
Runtime configuration is correct, and the adapter can read logs in the target environment without ad-hoc bootstrapping.

**Resolution**
Deployment remains consistent with existing operational practices for datastore-backed services.

### Journey 4 - Support/Troubleshooting Engineer

**Opening Scene**
A production issue requires fast inspection of a specific log entry by known identifier.

**Rising Action**
Support engineer uses the standard application flow that now reads through datastore adapter, reproduces the scenario, and retrieves the relevant log record.

**Climax**
The full payload is available for diagnosis, including contextual fields needed to trace lifecycle and execution state.

**Resolution**
Troubleshooting is faster because support follows one consistent read path instead of switching between custom ES integrations.

### Journey 5 - Integration Developer (Internal Consumer)

**Opening Scene**
Another backend developer integrates log reading into a separate internal service.

**Rising Action**
They reuse the same datastore contract, call `read(id)`, and avoid introducing direct dependency on Elasticsearch client APIs.

**Climax**
Integration is completed with minimal custom glue code.

**Resolution**
Cross-service adoption improves because Elasticsearch behaves like other datastore backends from the consumer perspective.

### Journey Requirements Summary

These journeys reveal required capability areas:

- **Core Read Capability**
- Support `read(id)` against configured Elasticsearch index.
- Return full payload for heterogeneous log structures.

- **Contract Enforcement**
- Keep strict read-only behavior in MVP.
- Return `Not Implemented` for unsupported operations consistently.

- **Configuration & Wiring**
- Use existing DI/config for Elasticsearch client and index.
- Ensure predictable service resolution in different environments.

- **Error/Edge Handling**
- Deterministic behavior for not-found records and unsupported method calls.
- Clear integration semantics for consumer-side recovery paths.

- **Testability**
- Preserve compatibility with existing datastore testing expectations.
- Add adapter-specific tests covering success path, edge path, and unsupported operations.

## Developer Tool Specific Requirements

### Project-Type Overview

`rollun-datastore` Elasticsearch adapter is a PHP developer-tool extension intended for backend teams already using datastore abstractions.
The MVP prioritizes architectural consistency and read-path enablement over ecosystem expansion.

### Technical Architecture Considerations

- **Language Support:** PHP only.
- **Package Manager:** Composer only.
- **Runtime Integration Style:** Existing project DI/config patterns; no custom bootstrap flow.
- **API Surface (MVP):** Read-only behavior centered on `read(id)`.

### Language & Packaging

- Deliver as PHP-native implementation aligned with existing codebase conventions.
- Composer is the only package distribution/integration path required for MVP.
- No multi-language SDK or cross-language bindings in scope.

### IDE & Tooling Expectations

- No special IDE integration is required in MVP.
- No additional editor plugins, generators, or IDE-specific features are planned.

### Documentation Strategy

- No dedicated new documentation deliverables are required for MVP in this phase.
- Existing team/project context is considered sufficient for initial implementation.

### Examples & Samples

- No runnable examples are required for MVP.
- Implementation validation is handled through test strategy and existing engineering workflows.

### Code Examples

- Canonical read example for MVP:
```php
$record = $elasticsearchDataStore->read($id);
```
- Expected behavior:
- If record exists, return full payload from Elasticsearch document source.
- If record is absent, return datastore-appropriate not-found outcome.
- If unsupported method is called (`query`, `create`, `update`, `delete`), return `Not Implemented`.

### Migration Guide

- Migration target:
- Replace direct Elasticsearch client read paths in consumer modules with datastore `read(id)` calls.
- Minimum migration sequence:
- Identify current direct read usage points by module.
- Wire Elasticsearch datastore adapter via existing DI/config.
- Replace direct read calls with datastore calls in prioritized modules.
- Verify behavior parity on representative log IDs.
- Exit criteria for MVP migration:
- Targeted consumer read paths use datastore adapter.
- No regressions in required existing unit tests.
- Adapter-specific unit tests pass for success, not-found, and unsupported operations.

### Implementation Considerations

- Keep implementation minimal and focused on core adapter behavior.
- Avoid expanding into non-essential developer-experience features in MVP.
- Preserve clean path for post-MVP enhancements if adoption requires broader tooling/docs.

## Project Scoping & Phased Development

### MVP Strategy & Philosophy

**MVP Approach:** problem-solving MVP focused on architectural consistency and immediate log-read value.
**Resource Requirements:** 1 backend developer (minimum) with current project context and existing DI/config access.

### MVP Feature Set (Phase 1)

**Core User Journeys Supported:**
- Primary Developer - success path (`read(id)` through `DataStore`)
- Primary Developer - edge handling (not-found and unsupported methods)
- Operations/Admin - configuration wiring via existing DI/config
- Support/Troubleshooting - retrieve specific log payload for incident analysis
- Integration Developer - consume adapter without direct ES client usage

**Must-Have Capabilities:**
- Read-only Elasticsearch datastore adapter
- `read(id)` support only
- Existing config/DI-based ES client + index wiring
- Payload fidelity for heterogeneous log structures
- Consistent `Not Implemented` behavior for unsupported operations
- Required existing unit tests passing + new adapter tests

### Post-MVP Features

**Phase 2 (Post-MVP):**
- `query`/RQL support for filtered and broader read scenarios
- Additional read ergonomics where required by adoption feedback

**Phase 3 (Expansion):**
- Potential write-path capabilities if justified (`create`/`update`/`delete`)
- Broader datastore parity features and advanced capabilities after stable adoption

### Risk Mitigation Strategy

**Technical Risks:**
Primary risk is payload fidelity across variable log schemas.
Mitigation: preserve source payload as-is in MVP, avoid rigid schema mapping, validate with adapter tests over heterogeneous fixtures.

**Market Risks:**
Risk that adapter solves architecture consistency but not the most urgent consumer pain.
Mitigation: validate with immediate real usage in current log-read paths and iterate based on developer adoption feedback.

**Resource Risks:**
Risk of scope creep beyond read-only MVP with limited capacity.
Mitigation: strict boundary enforcement (`read(id)` only), defer non-essential capabilities to post-MVP phases.

## Functional Requirements

### Datastore Read Capability

- FR1: Backend developer can configure an Elasticsearch-backed datastore resource in existing project configuration.
- FR2: Backend developer can read a log record by identifier through datastore `read(id)` operation.
- FR3: System can return a full log payload for an existing identifier from the configured Elasticsearch index.
- FR4: System can preserve heterogeneous log field structures in read responses without requiring a fixed schema.
- FR5: Consumer service can receive datastore read results in a format compatible with existing datastore usage patterns.

### Read-Only Contract Enforcement

- FR6: Backend developer can call unsupported datastore methods and receive an explicit `Not Implemented` response.
- FR7: System can enforce read-only behavior consistently across all non-read datastore operations.
- FR8: Consumer service can distinguish supported read capability from unsupported operations through deterministic behavior.

### Configuration and Environment Management

- FR9: Operations/admin user can provide Elasticsearch client and index settings through existing DI/config mechanisms.
- FR10: Operations/admin user can resolve the datastore adapter from the service container using standard project patterns.
- FR11: System can apply environment-specific index and client settings without changing consumer business logic.

### Troubleshooting and Support Workflows

- FR12: Support engineer can retrieve a specific log by identifier for incident investigation.
- FR13: Support engineer can use the same datastore read path as application services for diagnosis.
- FR14: System can provide complete diagnostic payload content needed for lifecycle and context analysis.

### Integration and Adoption

- FR15: Integration developer can replace direct Elasticsearch client reads with datastore-based reads in consumer modules.
- FR16: Integration developer can integrate the adapter without introducing custom ES-specific abstraction layers.
- FR17: System can support incremental migration of existing read paths to datastore usage.

### Testability and Verification

- FR18: Developer can validate successful datastore read behavior through automated tests.
- FR19: Developer can validate not-found read scenarios through automated tests.
- FR20: Developer can validate unsupported-method behavior through automated tests.
- FR21: Developer can add adapter tests following existing datastore test patterns in the repository.
- FR22: Team can run relevant existing datastore unit tests to verify no regression in expected datastore behavior.

## Non-Functional Requirements

### Performance

- NFR1: For a baseline dataset in MVP environment, p95 datastore `read(id)` response time shall be <= 500 ms, measured by automated integration test runs.
- NFR2: Adapter-side processing overhead (excluding Elasticsearch round trip) shall be <= 50 ms p95 per `read(id)`, measured with application-level timing instrumentation.
- NFR3: For payload sizes between 1 KB and 200 KB, p95 response time variance shall remain within 25% across test fixtures, measured in repeatable performance test suites.

### Security

- NFR4: 100% of adapter runtime access to Elasticsearch shall use DI-provided client configuration; zero hardcoded credentials are permitted, verified by code review and static scanning.
- NFR5: 100% of adapter resolution in application code shall occur through the configured service container entry, verified by integration tests and module wiring checks.
- NFR6: 100% of unsupported write-path method invocations (`create`, `update`, `delete`, `query` in MVP scope) shall return `Not Implemented`, verified by adapter unit tests.

### Scalability

- NFR7: Adapter API contract for `read(id)` shall remain unchanged while scaling from baseline load to 5x baseline read throughput in test scenarios, verified by contract and load tests.
- NFR8: In post-MVP iterations, any added capabilities shall not change `read(id)` request/response shape for existing consumers, verified by backward-compatibility test suite.

### Integration

- NFR9: Adapter integration shall require no custom bootstrap scripts; configuration through existing DI/config files shall be sufficient in all target environments, verified by deployment smoke tests.
- NFR10: For each migrated consumer module in MVP scope, direct Elasticsearch read usage shall be reduced to zero and replaced by datastore calls, verified by code search and module tests.
- NFR11: Existing relevant datastore unit suites and new adapter unit suites shall pass at 100% in CI for merge eligibility.
