# RQL Module - Deep Dive Documentation

**Generated:** 2025-12-05
**Scope:** `/src/DataStore/src/Rql`
**Files Analyzed:** 38
**Lines of Code:** ~1,350
**Workflow Mode:** Exhaustive Deep-Dive

## Overview

The RQL (Resource Query Language) Module is the query parsing and encoding engine for rollun-datastore library. It provides a unified query language interface for querying any datastore implementation (DbTable, CsvBase, Memory, HttpClient) using a URL-friendly syntax.

**Purpose:** Parse RQL query strings into typed query objects and encode query objects back to RQL strings. Supports both Basic and FIQL (Feed Item Query Language) syntax variants.

**Key Responsibilities:**
- Parse RQL query strings (e.g., `eq(name,John)&limit(10)`) into structured Query objects
- Encode Query objects back to RQL string format
- Support custom operators: eqf, eqn, eqt, ie (binary field operators)
- Support string operators: alike, contains, match, like with glob patterns
- Handle aggregate functions: count, sum, avg, max, min
- Provide GROUP BY extension to standard RQL
- Manage dual syntax support (Basic and FIQL)

**Integration Points:**
- **Upstream:** Used by all DataStore implementations (DbTable, Memory, CsvBase, HttpClient)
- **Upstream:** Used by Middleware layer for HTTP request parsing
- **Upstream:** Used by ConditionBuilder for SQL generation
- **Downstream:** Depends on xiag/rql-parser (base RQL library)
- **Downstream:** Depends on ConditionBuilder for encoding WHERE clauses

---

## Architecture & Design Patterns

### Code Organization

The RQL Module follows a layered architecture:

```
Layer 1: Facade/Entry Points
├── RqlParser - Main parsing/encoding facade
└── RqlQuery - Extended Query object with GroupBy

Layer 2: Parser Pipeline
├── QueryParser - Custom parser with RqlQueryBuilder
└── RqlQueryBuilder - Builder that handles custom nodes

Layer 3: Data Model (Nodes)
├── Custom Nodes (12 files)
│   ├── Aggregate: AggregateFunctionNode, AggregateSelectNode
│   ├── Extensions: GroupbyNode
│   ├── String Operators: AlikeNode, AlikeGlobNode, ContainsNode, LikeGlobNode
│   └── Binary Operators: BinaryOperatorNodeAbstract, EqfNode, EqnNode, EqtNode, IeNode
└── xiag Nodes (inherited): EqNode, NeNode, LtNode, GtNode, etc.

Layer 4: Parsing Strategy (TokenParsers)
├── SelectTokenParser - Handles aggregate functions in SELECT
├── GroupbyTokenParser - Handles GROUP BY syntax
├── Basic Syntax (11 files) - Parses operator(field[,value]) format
└── FIQL Syntax (11 files) - FIQL-style parsing (parallel implementation)
```

### Design Patterns

- **Facade Pattern**: `RqlParser` provides simple interface (`decode()/encode()`) hiding complex parsing pipeline
- **Template Method**: `BinaryTokenParserAbstract` defines parsing algorithm, subclasses provide operator name
- **Builder Pattern**: `RqlQueryBuilder` constructs complex `RqlQuery` objects step-by-step
- **Strategy Pattern**: Dual parsing strategies (Basic vs FIQL syntax) with identical node output
- **Adapter Pattern**: `QueryParser` adapts xiag Parser to use custom `RqlQueryBuilder`

### State Management Strategy

**Stateless Design**: All parsers are stateless - each parse operation is independent.

- `RqlParser` instance stores configuration (allowedAggregateFunction, conditionBuilder) but no parse state
- TokenParsers are pure functions - take TokenStream, return Node
- Query objects (`RqlQuery`) are immutable value objects after construction

### Error Handling Philosophy

**Fail-Fast with Exceptions**:
- `SyntaxErrorException` thrown by TokenParsers for malformed syntax
- No silent failures or fallbacks
- Errors propagate immediately to caller

**Type Safety**:
- PHP 8.0+ constructor property promotion for type safety
- Method signatures use type hints where possible
- Note: Many methods still use `mixed` due to xiag library constraints

---

## Data Flow

### Decoding Flow (String → Query Object)

```
User Input: "eq(name,John)&select(id,name)&limit(10)"
    ↓
[1] RqlParser::rqlDecode() - Static entry point
    ↓
[2] prepareStringRql() - Fix sort node patterns (add '+' prefix)
    ↓
[3] encodedStrQuery() - Encode special characters (@, $, \)
    ↓
[4] Lexer::tokenize() - Create token stream
    ↓
[5] QueryParser::parse() - Use TokenParserGroup
    ↓
[6] TokenParsers Process Tokens:
    - EqTokenParser → EqNode('name', 'John')
    - SelectTokenParser → SelectNode(['id', 'name'])
    - LimitTokenParser → LimitNode(10)
    ↓
[7] RqlQueryBuilder::addNode() - Build query object
    - Regular nodes → delegate to parent
    - GroupbyNode → call setGroupBy()
    ↓
[8] Return: RqlQuery object
    - query: EqNode
    - select: SelectNode
    - limit: LimitNode
    - groupBy: null (if no groupby)
```

### Encoding Flow (Query Object → String)

```
RqlQuery object
    ↓
[1] RqlParser::encode()
    ↓
[2] ConditionBuilder::__invoke() - Encode WHERE clause
    - Traverses query node tree
    - Converts nodes to RQL syntax
    → "eq(name,John)"
    ↓
[3] makeLimit() - Encode LIMIT node
    - Handle LIMIT_INFINITY special case
    - Format: "limit(10)" or "limit(10,5)" with offset
    → "&limit(10)"
    ↓
[4] makeSort() - Encode SORT node
    - Format: "sort(+field1,-field2)"
    - 1 = ascending (+), -1 = descending (-)
    → "&sort(+created)"
    ↓
[5] makeSelect() - Encode SELECT node
    - Format: "select(field1,field2)"
    → "&select(id,name)"
    ↓
[6] makeGroupby() - Encode GROUPBY node (if RqlQuery)
    - Format: "groupby(field1,field2)"
    → "&groupby(category)"
    ↓
[7] Concatenate all parts, trim trailing '&'
    ↓
Return: "eq(name,John)&select(id,name)&limit(10)"
```

### Data Entry Points

- **RqlParser::decode(string)**: Main decoding entry point (instance method)
- **RqlParser::rqlDecode(string)**: Static decoding entry (convenience)
- **RqlParser::encode(Query)**: Main encoding entry point (instance method)
- **RqlParser::rqlEncode(Query)**: Static encoding entry (convenience)
- **RqlQuery constructor**: Accepts string, Query, or RqlQuery

### Data Transformations

1. **String Preparation** (`prepareStringRql`):
   - Fixes sort node syntax: `sort(field)` → `sort(+field)`
   - Regex pattern matching for sort nodes

2. **Character Encoding** (`encodedStrQuery`):
   - Escapes special chars: `\`, `@`, `$`
   - Uses `RqlConditionBuilder::encodeString()`
   - Preserves protected nodes: select, sort, limit

3. **Token Parsing** (TokenParsers):
   - Lexical analysis: string → tokens
   - Syntax analysis: tokens → abstract syntax tree (nodes)
   - Type casting: string values → typed values (int, float, bool)

4. **Node Building** (RqlQueryBuilder):
   - Assembles nodes into Query structure
   - Groups query/select/limit/groupBy

5. **Node Encoding** (ConditionBuilder):
   - Traverses node tree
   - Converts each node type to RQL syntax
   - Concatenates with '&' delimiter

### Data Exit Points

- **RqlParser::decode()**: Returns `Xiag\Rql\Parser\Query` or `RqlQuery` object
- **RqlParser::encode()**: Returns RQL string
- **RqlQuery::getGroupBy()**: Returns `GroupbyNode` or null
- **Nodes**: Various getter methods for field/value access

---

## Integration Points

### APIs Consumed

**External Library: xiag/rql-parser**
- **Classes Used:**
  - `Xiag\Rql\Parser\Lexer` - Tokenization
  - `Xiag\Rql\Parser\Parser` - Base parser
  - `Xiag\Rql\Parser\Query` - Query object
  - `Xiag\Rql\Parser\QueryBuilder` - Builder
  - `Xiag\Rql\Parser\TokenParser\*` - Base token parsers
  - `Xiag\Rql\Parser\TypeCaster\*` - Type casting
  - `Xiag\Rql\Parser\Node\*` - Base node classes

**Internal APIs:**
- `rollun\datastore\DataStore\ConditionBuilder\RqlConditionBuilder`
  - Method: `__invoke(AbstractNode): string` - Encode query node to RQL
  - Method: `encodeString(string): string` - Encode special characters
- `rollun\datastore\DataStore\DataStoreAbstract`
  - Constant: `LIMIT_INFINITY` - Special limit value for "no limit"

### APIs Exposed

**Public API (RqlParser):**

```php
// Decoding
public function decode(string $rqlQueryString): Query
public static function rqlDecode(string $rqlQueryString): Query

// Encoding
public function encode(Query $query): string
public static function rqlEncode(Query $query): string

// Configuration
public function __construct(
    array $allowedAggregateFunction = null,  // Default: ['count','max','min','sum','avg']
    ConditionBuilderAbstract $conditionBuilder = null  // Default: RqlConditionBuilder
)
```

**Public API (RqlQuery):**

```php
// Construction
public function __construct($query = null)  // Accepts string, Query, or RqlQuery

// Fluent interface
public function setGroupBy(GroupbyNode $groupBy): RqlQuery
public function getGroupBy(): GroupbyNode

// Inherited from Query
public function getQuery(): ?AbstractNode
public function getSelect(): ?SelectNode
public function getSort(): ?SortNode
public function getLimit(): ?LimitNode
```

**Node API (Custom Nodes):**

All custom nodes expose:
- `getNodeName(): string` - Returns operator name
- `getField(): string` - Returns field name (where applicable)
- `getValue(): mixed` - Returns value (for scalar operators)
- `getFunction(): string` - Returns function name (AggregateFunctionNode)
- `getFields(): array` - Returns field array (GroupbyNode, AggregateSelectNode)

### Shared State

**No Shared Mutable State**: Module is stateless.

- **Configuration State** (immutable after construction):
  - `RqlParser::$allowedAggregateFunction` - Array of allowed aggregate functions
  - `RqlParser::$conditionBuilder` - ConditionBuilder instance

### Events

**No Event System**: This is a pure functional module with no events/observers.

### Database Access

**No Direct Database Access**: RQL Module only handles parsing/encoding. DataStore implementations handle database operations.

---

## Dependency Graph

### Visualization

```
Entry Points (Not Imported by Others in Module):
├── RqlParser (main facade)
├── RqlQuery (extended query)
└── QueryParser (custom parser)

Core Dependencies:
RqlParser
├── depends on → QueryParser
├── depends on → RqlConditionBuilder
├── depends on → xiag\Lexer
├── depends on → xiag\TokenParsers (all standard)
├── depends on → Custom TokenParsers (32 files)
│   ├── SelectTokenParser
│   ├── GroupbyTokenParser
│   ├── Basic/BinaryOperator (4 files)
│   ├── Basic/ScalarOperator (5 files)
│   ├── Fiql/BinaryOperator (4 files)
│   └── Fiql/ScalarOperator (5 files)
└── depends on → TypeCasters (4 types)

QueryParser
├── extends → xiag\Parser
└── depends on → RqlQueryBuilder

RqlQueryBuilder
├── extends → xiag\QueryBuilder
└── depends on → GroupbyNode

TokenParsers (32 files)
├── depend on → Node classes (12 files)
│   ├── AggregateFunctionNode
│   ├── AggregateSelectNode
│   ├── GroupbyNode
│   ├── AlikeNode, AlikeGlobNode
│   ├── ContainsNode, LikeGlobNode
│   └── BinaryNodes (5 files)
└── depend on → xiag\AbstractNode

Leaf Nodes (Don't Import Others in Module):
├── All 12 custom Node classes
└── All 32 TokenParser classes
```

### Entry Points (Not Imported by Others in Scope)

Within RQL Module scope:
- `RqlParser.php` - Main entry point
- `RqlQuery.php` - Alternative entry point
- `QueryParser.php` - Used internally by RqlParser

### Leaf Nodes (Don't Import Others in Scope)

All Node classes and TokenParser classes are leaf nodes - they only import from xiag library, not from other RQL module files.

### Circular Dependencies

✓ **No circular dependencies detected** - Clean layered architecture.

**Dependency Flow**:
```
RqlParser → QueryParser → RqlQueryBuilder → GroupbyNode
         → TokenParsers → Nodes
```

---

## Testing Analysis

### Test Coverage Summary

**Based on project-wide test results:**
- **Total Tests:** 890 (19 warnings, 17 skipped)
- **RQL-Specific Tests:** Located in `test/unit/DataStore/Rql/` and `test/functional/`
- **Test Categories:**
  - TokenParser tests (Basic and Fiql variants)
  - Node tests
  - RqlParser encode/decode tests
  - Integration tests with DataStore

### Test Files

**Unit Tests (TokenParsers):**
- `test/unit/DataStore/Rql/TokenParser/Basic/ScalarOperator/AlikeGlobTokenParserTest.php`
- `test/unit/DataStore/Rql/TokenParser/Basic/ScalarOperator/AlikeTokenParserTest.php`
- `test/unit/DataStore/Rql/TokenParser/Basic/ScalarOperator/ContainsTokenParserTest.php`
- `test/unit/DataStore/Rql/TokenParser/Basic/ScalarOperator/LikeGlobTokenParserTest.php`
- `test/unit/DataStore/Rql/TokenParser/Basic/ScalarOperator/MatchTokenParserTest.php`
- Similar tests for Fiql variants
- `test/unit/DataStore/Rql/TokenParser/GroupByTokenParserTest.php`
- `test/unit/DataStore/Rql/TokenParser/SelectTokenParserTest.php`

**Test Approach:**
- **Mocking Strategy:** Tests use PHPUnit mocks for TokenStream and Token objects
- **Assertion Style:** Tests verify correct Node creation from token parsing
- **Coverage:** TokenParsers are well-tested, Node classes have minimal logic to test

**Known Issue:**
- 19 warnings about deprecated `at()` matcher - needs PHPUnit 10 migration

### Testing Gaps

**Identified Gaps:**
1. **Incomplete toRql() methods** - Many Node classes have `toRql(): void` marked as TODO
2. **No performance tests** - No benchmarks for parsing complex queries
3. **Limited edge case testing** - Tests focus on happy path, limited malformed query testing
4. **No property-based testing** - Would benefit from generative testing for encode/decode symmetry

---

## Related Code & Reuse Opportunities

### Similar Features Elsewhere

**Query Adapters** (`src/DataStore/src/DataStore/Query/`):
- **AbstractQueryAdapter** - Base for query adaptation strategies
- **MultipleQueryAdapter** - Handles multiple query strategies
- **NullQueryAdapter** - Null object pattern
- **Similarity:** Alternative query representation strategies
- **Can Reference For:** Understanding query object patterns

**SQL Query Builder** (`src/DataStore/src/TableGateway/SqlQueryBuilder.php`):
- Converts RQL queries to SQL WHERE clauses
- **Similarity:** Query transformation (RQL → SQL)
- **Can Reference For:** How queries are executed against databases

### Reusable Utilities Available

**ConditionBuilder** (`src/DataStore/src/DataStore/ConditionBuilder/RqlConditionBuilder.php`):
- **Purpose:** Encodes AbstractNode trees back to RQL strings
- **How to Use:** `$conditionBuilder->__invoke($queryNode)` returns RQL string
- **Note:** Required dependency for RqlParser encoding

**DataStore Constants** (`src/DataStore/src/DataStore/DataStoreAbstract.php`):
- **Constant:** `LIMIT_INFINITY` - Used to represent "no limit" in queries
- **How to Use:** `$query->setLimit(new LimitNode(DataStoreAbstract::LIMIT_INFINITY))`

### Patterns to Follow

**Token Parser Pattern**: Reference any Basic TokenParser for implementation:
```php
// Template: Create new operator
class MyOperatorTokenParser extends AbstractBasicTokenParser {
    protected function getOperatorName(): string { return 'myop'; }
    protected function createNode($field, $value): MyOperatorNode {
        return new MyOperatorNode($field, $value);
    }
}
```

**Node Pattern**: Reference any Node class:
```php
// Template: Create new node type
class MyNode extends AbstractQueryNode {
    public function __construct(
        private string $field,
        private mixed $value
    ) {}

    public function getNodeName(): string { return 'mynode'; }
    public function getField(): string { return $this->field; }
    public function getValue(): mixed { return $this->value; }
}
```

---

## Implementation Notes

### Code Quality Observations

**Strengths:**
- ✅ Clean layered architecture with clear separation of concerns
- ✅ Extensive use of PHP 8.0+ features (constructor property promotion, match expressions)
- ✅ Comprehensive operator coverage (32 custom operators)
- ✅ Well-documented with PHPDoc comments
- ✅ Strong test coverage for critical paths

**Weaknesses:**
- ⚠️ **No type safety** - Many methods use `mixed` type (xiag library constraint)
- ⚠️ **Incomplete encoding** - Multiple `toRql()` methods marked as TODO
- ⚠️ **Duplication** - Basic and FIQL TokenParsers are nearly identical (32 files for 16 operators)
- ⚠️ **String manipulation** - Heavy use of regex and string operations (fragile)
- ⚠️ **Hidden complexity** - RqlParser has 330 LOC with complex token parser registration

### TODOs and Future Work

**From Code Analysis:**

1. **AggregateFunctionNode:59** - `toRql()` method not implemented
2. **GroupbyNode:41** - `toRql()` method not implemented
3. **BinaryOperatorNodeAbstract:28** - `toRql()` method not implemented
4. **Multiple Node classes** - Incomplete RQL encoding support

**Implications:**
- Encoding may not work for all query types
- Some query objects cannot be serialized back to strings
- Potential bugs when round-tripping complex queries

### Known Issues

1. **Deprecated PHPUnit matcher** - 19 test warnings for `at()` matcher (PHPUnit 9 → 10 migration needed)
2. **Incomplete encoding** - Some nodes lack `toRql()` implementation
3. **Type safety** - Limited type hints due to xiag library constraints
4. **Glob wrapping inconsistency** - Both `AlikeTokenParser` and `AlikeGlobTokenParser` exist, only Glob variant registered

### Optimization Opportunities

1. **Reduce TokenParser duplication**:
   - Basic and FIQL parsers are 99% identical
   - Could use single parser with syntax variant flag
   - Would reduce from 32 files to 16 files

2. **Cache parsed queries**:
   - Frequently used queries could be cached
   - Reduce parsing overhead for repeated queries

3. **Lazy load TokenParsers**:
   - RqlParser constructor registers all 32 parsers
   - Could lazy-load on first use

4. **Optimize string operations**:
   - `prepareStringRql()` and `encodedStrQuery()` use multiple regex passes
   - Could combine into single pass

### Technical Debt

1. **Dual implementation burden** (Basic + FIQL):
   - Every new operator requires 2 TokenParser classes (Basic + FIQL)
   - Maintenance overhead for parallel implementations
   - **Recommendation:** Consider single parser with syntax detection

2. **xiag library dependency**:
   - Heavy coupling to external library
   - Limited by xiag's type system (uses `mixed` everywhere)
   - Difficult to add strong typing without forking library
   - **Recommendation:** Consider abstracting xiag dependency behind interface

3. **Incomplete encoding support**:
   - Multiple `toRql()` methods return `void` (TODO)
   - Encoding may fail for complex queries
   - **Recommendation:** Complete `toRql()` implementations or throw explicit exceptions

4. **String-based parsing fragility**:
   - Heavy reliance on regex patterns
   - No formal grammar definition
   - Edge cases may not be handled
   - **Recommendation:** Consider parser generator (e.g., ANTLR) for robust parsing

---

## Modification Guidance

### To Add New Operator

**For Clean Architecture + DDD Refactoring:**

In new v2 architecture, adding operators should be:

1. **Create Value Object** (Domain Layer):
```php
// Domain/Query/ValueObject/Operators/MyOperator.php
final readonly class MyOperator implements QueryOperator {
    public function __construct(
        private FieldName $field,
        private mixed $value
    ) {}
}
```

2. **Create Parser** (Infrastructure Layer):
```php
// Infrastructure/Query/Parser/MyOperatorParser.php
final class MyOperatorParser implements OperatorParser {
    public function supports(string $operator): bool {
        return $operator === 'myop';
    }

    public function parse(string $field, mixed $value): QueryOperator {
        return new MyOperator(
            new FieldName($field),
            $value
        );
    }
}
```

**Current (Legacy) Approach:**

1. Create Node class (`src/DataStore/src/Rql/Node/MyOperatorNode.php`)
2. Create Basic TokenParser (`src/DataStore/src/Rql/TokenParser/Query/Basic/MyOperatorTokenParser.php`)
3. Create FIQL TokenParser (`src/DataStore/src/Rql/TokenParser/Query/Fiql/MyOperatorTokenParser.php`)
4. Register both parsers in `RqlParser::decode()` (lines 124-160)
5. Add encoding support in `ConditionBuilder`

### To Modify Existing Functionality

**Example: Fix Incomplete toRql() Implementation**

Current:
```php
public function toRql(): string|void {
    // TODO
}
```

Should implement:
```php
public function toRql(): string {
    return sprintf('%s(%s)', $this->getNodeName(), $this->getField());
}
```

**Critical Files to Understand:**
- `RqlParser.php` - Main orchestrator, modify for parser changes
- `ConditionBuilder/RqlConditionBuilder.php` - Modify for encoding changes
- Node classes - Modify for data model changes
- TokenParser classes - Modify for parsing logic changes

### To Remove/Deprecate

**To Remove Custom Operator:**

1. Remove Node class
2. Remove both TokenParser classes (Basic + FIQL)
3. Remove parser registration from `RqlParser::decode()`
4. Remove encoding support from `ConditionBuilder`
5. Update tests
6. Document breaking change in CHANGELOG

**Deprecation Strategy:**
- Keep parsing support (for backward compatibility reading old queries)
- Add deprecation warnings in Node constructor
- Remove encoding support (prevent creation of deprecated queries)
- Document migration path

### Testing Checklist for Changes

When modifying RQL Module:

- [ ] **Unit Tests**: Add/update TokenParser tests
- [ ] **Unit Tests**: Add/update Node tests
- [ ] **Integration Tests**: Test with actual DataStore implementations
- [ ] **Round-trip Testing**: Verify `encode(decode(query)) === query`
- [ ] **Edge Cases**: Test malformed queries, special characters, empty values
- [ ] **Backward Compatibility**: Ensure existing queries still parse
- [ ] **Performance**: Benchmark parsing performance if changing core logic
- [ ] **Documentation**: Update RQL docs (`docs/rql.md`)
- [ ] **CHANGELOG**: Document breaking changes
- [ ] **890 Existing Tests**: Ensure all pass

---

## Contributor Checklist

### Risks & Gotchas

⚠️ **Critical Risks for Refactoring:**

1. **Type Safety**: Current code uses `mixed` extensively - migrating to strict types will be challenging
2. **Incomplete Encoding**: Many nodes don't implement `toRql()` - queries may not round-trip correctly
3. **xiag Dependency**: Heavily coupled to xiag/rql-parser - abstracting this will be major work
4. **Dual Syntax Complexity**: Basic + FIQL duplication makes changes expensive (2x work)
5. **String Parsing Fragility**: Regex-based parsing may have edge cases - comprehensive testing required

⚠️ **Gotchas:**

- **Glob Wrapping**: `AlikeGlobNode` and `LikeGlobNode` automatically wrap values in `Glob` type
- **Sort Prefix**: `prepareStringRql()` adds '+' to sort fields without prefix
- **Protected Nodes**: select, sort, limit are not encoded in `encodedStrQuery()`
- **Static vs Instance**: Both static (`rqlDecode`) and instance (`decode`) methods exist
- **Limit Infinity**: `DataStoreAbstract::LIMIT_INFINITY` used to represent "no limit"

### Pre-change Verification Steps

Before making changes to RQL Module:

1. **Run Full Test Suite**: `composer test` - ensure all 890 tests pass
2. **Check Test Coverage**: `composer test-coverage` (with XDEBUG_MODE=coverage)
3. **Verify Round-Trip**: Test `encode(decode(query)) === query` for representative queries
4. **Check Dependents**: Grep for RQL usage in DataStore implementations
5. **Review Integration Points**: Check Middleware, ConditionBuilder usage
6. **Document Current State**: Capture baseline metrics (LOC, test count, performance)

### Suggested Tests Before PR

**Required Tests:**
```bash
# Unit tests for changed files
vendor/bin/phpunit test/unit/DataStore/Rql/

# Integration tests with DataStore
vendor/bin/phpunit test/functional/DataStore/

# Full test suite
composer test

# Static analysis (if added PHPStan)
vendor/bin/phpstan analyze src/DataStore/src/Rql
```

**Recommended Manual Tests:**
1. Test complex nested queries: `and(eq(a,1),or(eq(b,2),eq(c,3)))&limit(10,5)`
2. Test aggregate queries: `select(id,count(name))&groupby(category)`
3. Test special characters: `eq(name,O'Brien)&like(desc,*test*)`
4. Test encode/decode symmetry for all operator types
5. Test with actual DataStore implementations (DbTable, Memory)

---

_Generated by `document-project` workflow (deep-dive mode)_
_Base Documentation: docs/index.md_
_Scan Date: 2025-12-05_
_Analysis Mode: Exhaustive_