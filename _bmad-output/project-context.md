---
project_name: 'rollun-datastore'
user_name: 'Iliya'
date: '2026-02-06'
sections_completed: ['technology_stack', 'language_specific_rules', 'framework_specific_rules', 'testing_rules']
existing_patterns_found: 14
---

# Project Context for AI Agents

_This file contains critical rules and patterns that AI agents must follow when implementing code in this project. Focus on unobvious details that agents might otherwise miss._

---

## Technology Stack & Versions

- Runtime: `PHP ^8.0`
- Package management: `Composer`
- DI container: `laminas/laminas-servicemanager ^3.10` (Abstract Factory pattern)
- Core datastore dependencies: `laminas/laminas-db ^2.13.4`, `laminas/laminas-http ^2.15.1`, `rollun-com/xiag-rql-parser ^1.0.0`
- Testing: `phpunit/phpunit ^9.5.10`
- Code quality: `phpcs` (`PSR12`, line limit `120`), `rector/rector ^2.0`, `php-cs-fixer` (`@PER-CS`, `@PHP82Migration`)
- Elasticsearch integration: `elasticsearch/elasticsearch` (required for ES adapter; client major version must match Elasticsearch cluster major)

## Critical Implementation Rules

### Language-Specific Rules

- Keep compatibility with existing project style: typed signatures are allowed, but many interfaces/classes are legacy-style and must not be modernized in ways that break contract compatibility.
- Follow current namespace and PSR-4 roots exactly:
- `rollun\datastore\` -> `src/DataStore/src`
- `rollun\test\` -> `test`
- Preserve datastore method contracts as declared in:
- `src/DataStore/src/DataStore/Interfaces/DataStoreInterface.php`
- `src/DataStore/src/DataStore/Interfaces/ReadInterface.php`
- For unsupported operations in MVP, throw `rollun\datastore\DataStore\DataStoreException` with deterministic not-supported semantics.
- Do not introduce framework-specific abstractions; use existing Laminas ServiceManager plus AbstractFactory patterns.
- Preserve current datastore behavior conventions:
- `read($id)` returns `array|null`
- identifier is `id` unless explicitly overridden by datastore implementation
- Keep code PSR-12 compatible and line length <= 120 (`phpcs.xml`).

### Framework-Specific Rules

- Project uses Laminas ServiceManager configuration with `dependencies.factories` and `dependencies.abstract_factories`; new datastore services must follow this pattern.
- Register new datastore through existing `dataStore` config conventions and `DataStoreAbstractFactory` descendants; do not introduce a parallel DI mechanism.
- For Elasticsearch client creation, reuse existing `Utils\Elastic\ElasticSearchClientAbstractFactory` and container service `ElasticSearchClient`.
- Adapter must receive ES client from container; do not instantiate ES client directly in datastore classes.
- Keep implementation inside existing module structure (`src/DataStore/src/...`), with no new top-level module/starter.
- Preserve compatibility with existing middleware/handler flows that expect `DataStoreInterface` semantics.

### Testing Rules

- Use PHPUnit (`phpunit.xml`) and place new unit tests under `test/unit/...` following current folder taxonomy.
- For Elasticsearch adapter MVP, add focused tests under `test/unit/DataStore/DataStore/` by analogy with existing datastore tests (for example `MemoryTest.php` patterns).
- Mandatory MVP test cases:
- successful `read(id)` returns full document payload
- `read(id)` for missing document returns `null` (or project-accepted deterministic behavior)
- unsupported operations throw deterministic `DataStoreException` ("Method don't support." or agreed `Not Implemented` text)
- Keep tests compatible with existing legacy conventions (including current assertion style and fixture patterns where used).
- Ensure existing core datastore tests still pass; adapter changes must not regress shared behavior.
