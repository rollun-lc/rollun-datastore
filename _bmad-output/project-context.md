---
project_name: 'rollun-datastore'
user_name: 'Iliya'
date: '2026-02-06'
sections_completed: ['technology_stack', 'language_rules', 'framework_rules', 'testing_rules', 'quality_rules', 'workflow_rules', 'anti_patterns']
status: 'complete'
rule_count: 40
optimized_for_llm: true
existing_patterns_found: 14
---

# Project Context for AI Agents

_This file contains critical rules and patterns that AI agents must follow when implementing code in this project. Focus on unobvious details that agents might otherwise miss._

---

## Technology Stack & Versions

- Runtime: `PHP ^8.0`
- Package management: `Composer`
- DI container: `laminas/laminas-servicemanager ^3.10` with Abstract Factory pattern
- Core datastore dependencies: `laminas/laminas-db ^2.13.4`, `laminas/laminas-http ^2.15.1`, `rollun-com/xiag-rql-parser ^1.0.0`
- Testing: `phpunit/phpunit ^9.5.10`
- Code quality: `phpcs` (`PSR12`, max line `120`), `rector/rector ^2.0`, `php-cs-fixer` (`@PER-CS`, `@PHP82Migration`)
- Elasticsearch integration: `elasticsearch/elasticsearch` (required for ES adapter; client major version must match Elasticsearch cluster major)

## Critical Implementation Rules

### Language-Specific Rules

- Preserve backward compatibility with existing legacy-style interfaces and signatures.
- Keep PSR-4 roots exact: `rollun\datastore\` -> `src/DataStore/src`, `rollun\test\` -> `test`.
- Preserve method contracts from `src/DataStore/src/DataStore/Interfaces/DataStoreInterface.php`.
- Preserve read contract from `src/DataStore/src/DataStore/Interfaces/ReadInterface.php`.
- Keep `read($id)` return type semantics as `array|null`.
- Keep default identifier semantics as `id` unless explicitly overridden.
- For unsupported MVP operations, throw `rollun\datastore\DataStore\DataStoreException` with deterministic not-supported message.
- Do not introduce alternative language-level abstractions outside current project patterns.
- Keep code compatible with `PSR12` and `phpcs.xml` max line length `120`.

### Framework-Specific Rules

- Use Laminas ServiceManager conventions: `dependencies.factories` and `dependencies.abstract_factories`.
- Register datastore via existing `dataStore` config conventions and `DataStoreAbstractFactory` descendants.
- Reuse `Utils\Elastic\ElasticSearchClientAbstractFactory` for ES client creation.
- Resolve ES client from container service `ElasticSearchClient`.
- Do not instantiate ES client directly inside datastore implementation.
- Keep adapter implementation inside existing module tree `src/DataStore/src/...`.
- Do not add new top-level module, starter, or parallel DI stack.
- Preserve compatibility with existing middleware/handlers that depend on `DataStoreInterface`.

### Testing Rules

- Use PHPUnit (`phpunit.xml`) and place new unit tests under `test/unit/...` following current folder taxonomy.
- For Elasticsearch adapter MVP, add focused tests under `test/unit/DataStore/DataStore/` by analogy with existing datastore tests (for example `MemoryTest.php` patterns).
- Mandatory case: successful `read(id)` returns full document payload.
- Mandatory case: `read(id)` for missing document returns `null` (or project-approved deterministic behavior).
- Mandatory case: unsupported methods throw deterministic `DataStoreException`.
- Keep tests compatible with existing legacy conventions (including current assertion style and fixture patterns where used).
- Ensure existing core datastore tests still pass; adapter changes must not regress shared behavior.

### Code Quality & Style Rules

- Follow `PSR12` and project `phpcs.xml` constraints (line length <= 120).
- Use repository checks: `composer code-sniffer`, `composer code-beautiful`, `composer rector`.
- Keep naming and placement aligned with datastore patterns.
- Place datastore classes in `src/DataStore/src/DataStore/`.
- Place factories in `src/DataStore/src/DataStore/Factory/`.
- Place tests in `test/unit/DataStore/DataStore/`.
- Prefer minimal, targeted changes; do not refactor unrelated legacy code when adding the ES adapter.
- Keep comments concise and only where behavior is non-obvious.
- Preserve backward compatibility of public contracts and service names used by existing consumers.

### Development Workflow Rules

- Work inside current repository only; do not introduce external starters or parallel project scaffolds.
- Run focused checks before merge: new adapter unit tests plus critical existing datastore regression tests.
- Keep integration config-driven: datastore service in config, ES client from `ElasticSearchClient`.
- Keep MVP scope strict: read-only only, no RQL/query support, no caching, no circuit-breaker.
- For unsupported methods, fail fast with deterministic exception behavior to avoid ambiguous runtime states.
- Document only essential integration notes in planning artifacts; avoid over-documentation in MVP.

### Critical Don't-Miss Rules

- Do not bypass `DataStoreInterface` for consumer reads.
- Do not use direct Elasticsearch client reads in consumer code for this flow.
- Do not instantiate `ClientBuilder` in adapter code.
- Do not implement partial write/query behavior in MVP.
- Always return full document payload on successful `read(id)` without rigid schema enforcement.
- Keep logs/documents heterogeneous; avoid hard field contracts for payload.
- Keep mapping from datastore `id` to Elasticsearch `_id` explicit in adapter logic/config.
- Keep index selection configuration-driven.
- Do not add custom exception hierarchy in MVP.
- Do not add caching, retries policy changes, or resilience layers beyond existing client config in MVP.
- Do not break current DataStore tests when adding adapter tests.
- Do not rename existing services/contracts relied on by consumers.

---

## Usage Guidelines

**For AI Agents:**

- Read this file before implementing any code in this repository.
- Follow all rules as constraints, especially contract and DI rules.
- Prefer the stricter option when behavior is ambiguous.
- Update this file when new stable patterns emerge.

**For Humans:**

- Keep this file lean and focused on non-obvious agent guidance.
- Update when stack versions or architectural patterns change.
- Review periodically and remove obsolete rules.

Last Updated: 2026-02-06
