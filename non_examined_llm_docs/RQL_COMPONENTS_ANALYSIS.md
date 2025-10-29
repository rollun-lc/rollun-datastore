# Детальный анализ RQL компонентов

## RqlParser - основной парсер

**Класс**: `rollun\datastore\Rql\RqlParser`
**Файл**: `src/DataStore/src/Rql/RqlParser.php`

```php
class RqlParser
{
    private $allowedAggregateFunction;
    private $conditionBuilder;
    protected static $nodes = ['select', 'sort', 'limit'];
    
    public function __construct(
        array $allowedAggregateFunction = null,
        ConditionBuilderAbstract $conditionBuilder = null
    ) {
        $this->allowedAggregateFunction = $allowedAggregateFunction ?? [
            'count', 'max', 'min', 'sum', 'avg'
        ];
        $this->conditionBuilder = $conditionBuilder ?? new RqlConditionBuilder();
    }
    
    public static function rqlDecode($rqlQueryString): Query
    {
        $rqlQueryString = self::prepareStringRql($rqlQueryString);
        
        $lexer = new Lexer();
        $subLexerChain = new SubLexerChain();
        
        // Добавление кастомных TokenParser
        $subLexerChain->addSubLexer(new GroupbyTokenParser());
        $subLexerChain->addSubLexer(new SelectTokenParser());
        
        // Добавление Basic TokenParser
        $subLexerChain->addSubLexer(new BinaryOperator\EqfTokenParser());
        $subLexerChain->addSubLexer(new BinaryOperator\EqnTokenParser());
        $subLexerChain->addSubLexer(new BinaryOperator\EqtTokenParser());
        $subLexerChain->addSubLexer(new BinaryOperator\IeTokenParser());
        $subLexerChain->addSubLexer(new ScalarOperator\AlikeGlobTokenParser());
        $subLexerChain->addSubLexer(new ScalarOperator\AlikeTokenParser());
        $subLexerChain->addSubLexer(new ScalarOperator\ContainsTokenParser());
        $subLexerChain->addSubLexer(new ScalarOperator\LikeGlobTokenParser());
        $subLexerChain->addSubLexer(new ScalarOperator\MatchTokenParser());
        
        // Добавление Fiql TokenParser
        $subLexerChain->addSubLexer(new Fiql\BinaryOperator\EqfTokenParser());
        $subLexerChain->addSubLexer(new Fiql\BinaryOperator\EqnTokenParser());
        $subLexerChain->addSubLexer(new Fiql\BinaryOperator\EqtTokenParser());
        $subLexerChain->addSubLexer(new Fiql\BinaryOperator\IeTokenParser());
        $subLexerChain->addSubLexer(new Fiql\ScalarOperator\AlikeGlobTokenParser());
        $subLexerChain->addSubLexer(new Fiql\ScalarOperator\AlikeTokenParser());
        $subLexerChain->addSubLexer(new Fiql\ScalarOperator\ContainsTokenParser());
        $subLexerChain->addSubLexer(new Fiql\ScalarOperator\LikeGlobTokenParser());
        $subLexerChain->addSubLexer(new Fiql\ScalarOperator\MatchTokenParser());
        
        $lexer->setSubLexerChain($subLexerChain);
        
        $tokenParserGroup = new TokenParserGroup();
        
        // Добавление парсеров токенов
        $tokenParserGroup->addTokenParser(new GroupbyTokenParser());
        $tokenParserGroup->addTokenParser(new SelectTokenParser());
        
        $expressionParser = new ExpressionParser($lexer, $tokenParserGroup);
        $typeCaster = new TypeCaster();
        
        $query = $expressionParser->parse($rqlQueryString);
        $typeCaster->typeCast($query);
        
        return $query;
    }
    
    protected static function prepareStringRql($rqlQueryString)
    {
        // Обработка специальных символов в RQL строке
        $rqlQueryString = str_replace(['%2B', '%2F', '%3D'], ['+', '/', '='], $rqlQueryString);
        return $rqlQueryString;
    }
    
    public function decode($rqlQueryString): Query
    {
        return self::rqlDecode($rqlQueryString);
    }
    
    public static function rqlEncode(Query $query): string
    {
        $rqlQueryString = '';
        
        if ($query->getQuery()) {
            $rqlQueryString .= $this->conditionBuilder->buildQuery($query->getQuery());
        }
        
        if ($query->getSort()) {
            $rqlQueryString .= '&' . $this->encodeSort($query);
        }
        
        if ($query->getLimit()) {
            $rqlQueryString .= '&' . $this->encodeLimit($query);
        }
        
        if ($query->getSelect()) {
            $rqlQueryString .= '&' . $this->encodeSelect($query);
        }
        
        if ($query instanceof RqlQuery && $query->getGroupBy()) {
            $rqlQueryString .= '&' . $this->encodeGroupby($query);
        }
        
        return ltrim($rqlQueryString, '&');
    }
    
    public function encode(Query $query): string
    {
        return self::rqlEncode($query);
    }
    
    protected function encodeLimit(Query $query): string
    {
        $limitNode = $query->getLimit();
        $limit = $limitNode->getLimit();
        $offset = $limitNode->getOffset();
        
        if ($offset) {
            return "limit({$limit},{$offset})";
        } else {
            return "limit({$limit})";
        }
    }
    
    protected function encodeSort(Query $query): string
    {
        $sortNode = $query->getSort();
        $fields = [];
        
        foreach ($sortNode->getFields() as $field => $direction) {
            $prefix = $direction === 'asc' ? '+' : '-';
            $fields[] = $prefix . $field;
        }
        
        return 'sort(' . implode(',', $fields) . ')';
    }
    
    protected function encodeSelect(Query $query): string
    {
        $selectNode = $query->getSelect();
        $fields = $selectNode->getFields();
        
        return 'select(' . implode(',', $fields) . ')';
    }
    
    protected function encodeGroupby(RqlQuery $query): string
    {
        $groupByNode = $query->getGroupBy();
        $fields = $groupByNode->getFields();
        
        return 'groupby(' . implode(',', $fields) . ')';
    }
    
    protected static function encodedStrQuery($rqlQueryString): string
    {
        return rawurlencode($rqlQueryString);
    }
}
```

## RqlQuery - расширенный Query

**Класс**: `rollun\datastore\Rql\RqlQuery`
**Файл**: `src/DataStore/src/Rql/RqlQuery.php`

```php
class RqlQuery extends Query
{
    /** @var GroupbyNode */
    protected $groupBy;
    
    public function __construct($query = null)
    {
        if (is_string($query)) {
            /** @var RqlQuery $query */
            $query = RqlParser::rqlDecode($query);
        }
        
        if ($query instanceof Query) {
            $this->query = $query->query;
            $this->sort = $query->sort;
            $this->limit = $query->limit;
            $this->select = $query->select;
        }
        
        if ($query instanceof RqlQuery) {
            $this->groupBy = $query->groupBy;
        }
    }
    
    public function setGroupBy(GroupbyNode $groupBy)
    {
        $this->groupBy = $groupBy;
        return $this;
    }
    
    public function getGroupBy()
    {
        return $this->groupBy;
    }
}
```

## RQL Nodes - узлы запросов

### AggregateFunctionNode - узел агрегатной функции

**Класс**: `rollun\datastore\Rql\Node\AggregateFunctionNode`
**Файл**: `src/DataStore/src/Rql/Node/AggregateFunctionNode.php`

```php
class AggregateFunctionNode extends NodeAbstract
{
    protected $function;
    protected $field;
    
    public function __construct($function, $field)
    {
        $this->function = $function;
        $this->field = $field;
    }
    
    public function getFunction()
    {
        return $this->function;
    }
    
    public function getField()
    {
        return $this->field;
    }
    
    public function getNodeName()
    {
        return 'aggregateFunction';
    }
    
    public function __toString()
    {
        return $this->function . '(' . $this->field . ')';
    }
}
```

### AggregateSelectNode - узел агрегатного SELECT

**Класс**: `rollun\datastore\Rql\Node\AggregateSelectNode`
**Файл**: `src/DataStore/src/Rql/Node/AggregateSelectNode.php`

```php
class AggregateSelectNode extends NodeAbstract
{
    protected $fields;
    
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }
    
    public function getFields()
    {
        return $this->fields;
    }
    
    public function getNodeName()
    {
        return 'aggregateSelect';
    }
}
```

### GroupbyNode - узел GROUP BY

**Класс**: `rollun\datastore\Rql\Node\GroupbyNode`
**Файл**: `src/DataStore/src/Rql/Node/GroupbyNode.php`

```php
class GroupbyNode extends NodeAbstract
{
    protected $fields;
    
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }
    
    public function getFields()
    {
        return $this->fields;
    }
    
    public function getNodeName()
    {
        return 'groupby';
    }
}
```

### Binary Nodes - бинарные операторы

#### EqfNode - равенство с форматированием

**Класс**: `rollun\datastore\Rql\Node\BinaryNode\EqfNode`
**Файл**: `src/DataStore/src/Rql/Node/BinaryNode/EqfNode.php`

```php
class EqfNode extends BinaryOperatorNodeAbstract
{
    protected $field;
    protected $value;
    protected $format;
    
    public function __construct($field, $value, $format = null)
    {
        $this->field = $field;
        $this->value = $value;
        $this->format = $format;
    }
    
    public function getField()
    {
        return $this->field;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    public function getFormat()
    {
        return $this->format;
    }
    
    public function getNodeName()
    {
        return 'eqf';
    }
}
```

#### EqnNode - равенство с null

**Класс**: `rollun\datastore\Rql\Node\BinaryNode\EqnNode`
**Файл**: `src/DataStore/src/Rql/Node/BinaryNode/EqnNode.php`

```php
class EqnNode extends BinaryOperatorNodeAbstract
{
    protected $field;
    protected $value;
    
    public function __construct($field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }
    
    public function getField()
    {
        return $this->field;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    public function getNodeName()
    {
        return 'eqn';
    }
}
```

#### EqtNode - равенство с типом

**Класс**: `rollun\datastore\Rql\Node\BinaryNode\EqtNode`
**Файл**: `src/DataStore/src/Rql/Node/BinaryNode/EqtNode.php`

```php
class EqtNode extends BinaryOperatorNodeAbstract
{
    protected $field;
    protected $value;
    protected $type;
    
    public function __construct($field, $value, $type = null)
    {
        $this->field = $field;
        $this->value = $value;
        $this->type = $type;
    }
    
    public function getField()
    {
        return $this->field;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    public function getType()
    {
        return $this->type;
    }
    
    public function getNodeName()
    {
        return 'eqt';
    }
}
```

#### IeNode - is empty

**Класс**: `rollun\datastore\Rql\Node\BinaryNode\IeNode`
**Файл**: `src/DataStore/src/Rql/Node/BinaryNode/IeNode.php`

```php
class IeNode extends BinaryOperatorNodeAbstract
{
    protected $field;
    protected $value;
    
    public function __construct($field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }
    
    public function getField()
    {
        return $this->field;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    public function getNodeName()
    {
        return 'ie';
    }
}
```

### Scalar Nodes - скалярные операторы

#### AlikeGlobNode - ALIKE GLOB

**Класс**: `rollun\datastore\Rql\Node\AlikeGlobNode`
**Файл**: `src/DataStore/src/Rql/Node/AlikeGlobNode.php`

```php
class AlikeGlobNode extends NodeAbstract
{
    protected $field;
    protected $value;
    
    public function __construct($field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }
    
    public function getField()
    {
        return $this->field;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    public function getNodeName()
    {
        return 'alikeGlob';
    }
}
```

#### AlikeNode - ALIKE

**Класс**: `rollun\datastore\Rql\Node\AlikeNode`
**Файл**: `src/DataStore/src/Rql/Node/AlikeNode.php`

```php
class AlikeNode extends NodeAbstract
{
    protected $field;
    protected $value;
    
    public function __construct($field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }
    
    public function getField()
    {
        return $this->field;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    public function getNodeName()
    {
        return 'alike';
    }
}
```

#### ContainsNode - CONTAINS

**Класс**: `rollun\datastore\Rql\Node\ContainsNode`
**Файл**: `src/DataStore/src/Rql/Node/ContainsNode.php`

```php
class ContainsNode extends NodeAbstract
{
    protected $field;
    protected $value;
    
    public function __construct($field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }
    
    public function getField()
    {
        return $this->field;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    public function getNodeName()
    {
        return 'contains';
    }
}
```

#### LikeGlobNode - LIKE GLOB

**Класс**: `rollun\datastore\Rql\Node\LikeGlobNode`
**Файл**: `src/DataStore/src/Rql/Node/LikeGlobNode.php`

```php
class LikeGlobNode extends NodeAbstract
{
    protected $field;
    protected $value;
    
    public function __construct($field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }
    
    public function getField()
    {
        return $this->field;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    public function getNodeName()
    {
        return 'likeGlob';
    }
}
```

## RQL Token Parsers - парсеры токенов

### GroupbyTokenParser - парсер GROUP BY

**Класс**: `rollun\datastore\Rql\TokenParser\GroupbyTokenParser`
**Файл**: `src/DataStore/src/Rql/TokenParser/GroupbyTokenParser.php`

```php
class GroupbyTokenParser implements TokenParserInterface
{
    public function parse(TokenStream $tokenStream, QueryBuilderInterface $queryBuilder)
    {
        $tokenStream->expect(Token::T_OPERATOR, 'groupby');
        $tokenStream->expect(Token::T_OPEN_PARENTHESIS);
        
        $fields = [];
        $tokenStream->expect(Token::T_STRING);
        $fields[] = $tokenStream->getCurrentToken()->getValue();
        $tokenStream->next();
        
        while ($tokenStream->isNext(Token::T_COMMA)) {
            $tokenStream->next();
            $tokenStream->expect(Token::T_STRING);
            $fields[] = $tokenStream->getCurrentToken()->getValue();
            $tokenStream->next();
        }
        
        $tokenStream->expect(Token::T_CLOSE_PARENTHESIS);
        
        $queryBuilder->addGroupBy($fields);
    }
    
    public function getNodeName()
    {
        return 'groupby';
    }
}
```

### SelectTokenParser - парсер SELECT

**Класс**: `rollun\datastore\Rql\TokenParser\SelectTokenParser`
**Файл**: `src/DataStore/src/Rql/TokenParser/SelectTokenParser.php`

```php
class SelectTokenParser implements TokenParserInterface
{
    public function parse(TokenStream $tokenStream, QueryBuilderInterface $queryBuilder)
    {
        $tokenStream->expect(Token::T_OPERATOR, 'select');
        $tokenStream->expect(Token::T_OPEN_PARENTHESIS);
        
        $fields = [];
        $tokenStream->expect(Token::T_STRING);
        $fields[] = $tokenStream->getCurrentToken()->getValue();
        $tokenStream->next();
        
        while ($tokenStream->isNext(Token::T_COMMA)) {
            $tokenStream->next();
            $tokenStream->expect(Token::T_STRING);
            $fields[] = $tokenStream->getCurrentToken()->getValue();
            $tokenStream->next();
        }
        
        $tokenStream->expect(Token::T_CLOSE_PARENTHESIS);
        
        $queryBuilder->addSelect($fields);
    }
    
    public function getNodeName()
    {
        return 'select';
    }
}
```

## Condition Builders - построители условий

### RqlConditionBuilder - RQL построитель

**Класс**: `rollun\datastore\DataStore\ConditionBuilder\RqlConditionBuilder`
**Файл**: `src/DataStore/src/DataStore/ConditionBuilder/RqlConditionBuilder.php`

```php
class RqlConditionBuilder extends ConditionBuilderAbstract
{
    public function buildQuery(NodeInterface $node): string
    {
        if ($node instanceof EqNode) {
            return $this->buildEqNode($node);
        } elseif ($node instanceof NeNode) {
            return $this->buildNeNode($node);
        } elseif ($node instanceof LtNode) {
            return $this->buildLtNode($node);
        } elseif ($node instanceof GtNode) {
            return $this->buildGtNode($node);
        } elseif ($node instanceof LeNode) {
            return $this->buildLeNode($node);
        } elseif ($node instanceof GeNode) {
            return $this->buildGeNode($node);
        } elseif ($node instanceof InNode) {
            return $this->buildInNode($node);
        } elseif ($node instanceof OutNode) {
            return $this->buildOutNode($node);
        } elseif ($node instanceof LikeNode) {
            return $this->buildLikeNode($node);
        } elseif ($node instanceof ContainsNode) {
            return $this->buildContainsNode($node);
        } elseif ($node instanceof AlikeNode) {
            return $this->buildAlikeNode($node);
        } elseif ($node instanceof AlikeGlobNode) {
            return $this->buildAlikeGlobNode($node);
        } elseif ($node instanceof LikeGlobNode) {
            return $this->buildLikeGlobNode($node);
        } elseif ($node instanceof MatchNode) {
            return $this->buildMatchNode($node);
        } elseif ($node instanceof EqfNode) {
            return $this->buildEqfNode($node);
        } elseif ($node instanceof EqnNode) {
            return $this->buildEqnNode($node);
        } elseif ($node instanceof EqtNode) {
            return $this->buildEqtNode($node);
        } elseif ($node instanceof IeNode) {
            return $this->buildIeNode($node);
        } elseif ($node instanceof AndNode) {
            return $this->buildAndNode($node);
        } elseif ($node instanceof OrNode) {
            return $this->buildOrNode($node);
        } elseif ($node instanceof NotNode) {
            return $this->buildNotNode($node);
        }
        
        throw new \InvalidArgumentException('Unknown node type: ' . get_class($node));
    }
    
    protected function buildEqNode(EqNode $node): string
    {
        return 'eq(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . ')';
    }
    
    protected function buildNeNode(NeNode $node): string
    {
        return 'ne(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . ')';
    }
    
    protected function buildLtNode(LtNode $node): string
    {
        return 'lt(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . ')';
    }
    
    protected function buildGtNode(GtNode $node): string
    {
        return 'gt(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . ')';
    }
    
    protected function buildLeNode(LeNode $node): string
    {
        return 'le(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . ')';
    }
    
    protected function buildGeNode(GeNode $node): string
    {
        return 'ge(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . ')';
    }
    
    protected function buildInNode(InNode $node): string
    {
        $values = array_map([$this, 'encodeValue'], $node->getValues());
        return 'in(' . $node->getField() . ',(' . implode(',', $values) . '))';
    }
    
    protected function buildOutNode(OutNode $node): string
    {
        $values = array_map([$this, 'encodeValue'], $node->getValues());
        return 'out(' . $node->getField() . ',(' . implode(',', $values) . '))';
    }
    
    protected function buildLikeNode(LikeNode $node): string
    {
        return 'like(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . ')';
    }
    
    protected function buildContainsNode(ContainsNode $node): string
    {
        return 'contains(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . ')';
    }
    
    protected function buildAlikeNode(AlikeNode $node): string
    {
        return 'alike(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . ')';
    }
    
    protected function buildAlikeGlobNode(AlikeGlobNode $node): string
    {
        return 'alikeGlob(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . ')';
    }
    
    protected function buildLikeGlobNode(LikeGlobNode $node): string
    {
        return 'likeGlob(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . ')';
    }
    
    protected function buildMatchNode(MatchNode $node): string
    {
        return 'match(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . ')';
    }
    
    protected function buildEqfNode(EqfNode $node): string
    {
        $format = $node->getFormat() ? ',' . $this->encodeValue($node->getFormat()) : '';
        return 'eqf(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . $format . ')';
    }
    
    protected function buildEqnNode(EqnNode $node): string
    {
        return 'eqn(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . ')';
    }
    
    protected function buildEqtNode(EqtNode $node): string
    {
        $type = $node->getType() ? ',' . $this->encodeValue($node->getType()) : '';
        return 'eqt(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . $type . ')';
    }
    
    protected function buildIeNode(IeNode $node): string
    {
        return 'ie(' . $node->getField() . ',' . $this->encodeValue($node->getValue()) . ')';
    }
    
    protected function buildAndNode(AndNode $node): string
    {
        $conditions = array_map([$this, 'buildQuery'], $node->getQueries());
        return 'and(' . implode(',', $conditions) . ')';
    }
    
    protected function buildOrNode(OrNode $node): string
    {
        $conditions = array_map([$this, 'buildQuery'], $node->getQueries());
        return 'or(' . implode(',', $conditions) . ')';
    }
    
    protected function buildNotNode(NotNode $node): string
    {
        return 'not(' . $this->buildQuery($node->getQuery()) . ')';
    }
    
    protected function encodeValue($value): string
    {
        if (is_string($value)) {
            return '"' . addslashes($value) . '"';
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            return 'null';
        } else {
            return (string)$value;
        }
    }
}
```

### PhpConditionBuilder - PHP построитель

**Класс**: `rollun\datastore\DataStore\ConditionBuilder\PhpConditionBuilder`
**Файл**: `src/DataStore/src/DataStore/ConditionBuilder/PhpConditionBuilder.php`

```php
class PhpConditionBuilder extends ConditionBuilderAbstract
{
    public function buildQuery(NodeInterface $node): string
    {
        if ($node instanceof EqNode) {
            return $this->buildEqNode($node);
        } elseif ($node instanceof NeNode) {
            return $this->buildNeNode($node);
        } elseif ($node instanceof LtNode) {
            return $this->buildLtNode($node);
        } elseif ($node instanceof GtNode) {
            return $this->buildGtNode($node);
        } elseif ($node instanceof LeNode) {
            return $this->buildLeNode($node);
        } elseif ($node instanceof GeNode) {
            return $this->buildGeNode($node);
        } elseif ($node instanceof InNode) {
            return $this->buildInNode($node);
        } elseif ($node instanceof OutNode) {
            return $this->buildOutNode($node);
        } elseif ($node instanceof LikeNode) {
            return $this->buildLikeNode($node);
        } elseif ($node instanceof ContainsNode) {
            return $this->buildContainsNode($node);
        } elseif ($node instanceof AlikeNode) {
            return $this->buildAlikeNode($node);
        } elseif ($node instanceof AlikeGlobNode) {
            return $this->buildAlikeGlobNode($node);
        } elseif ($node instanceof LikeGlobNode) {
            return $this->buildLikeGlobNode($node);
        } elseif ($node instanceof MatchNode) {
            return $this->buildMatchNode($node);
        } elseif ($node instanceof EqfNode) {
            return $this->buildEqfNode($node);
        } elseif ($node instanceof EqnNode) {
            return $this->buildEqnNode($node);
        } elseif ($node instanceof EqtNode) {
            return $this->buildEqtNode($node);
        } elseif ($node instanceof IeNode) {
            return $this->buildIeNode($node);
        } elseif ($node instanceof AndNode) {
            return $this->buildAndNode($node);
        } elseif ($node instanceof OrNode) {
            return $this->buildOrNode($node);
        } elseif ($node instanceof NotNode) {
            return $this->buildNotNode($node);
        }
        
        throw new \InvalidArgumentException('Unknown node type: ' . get_class($node));
    }
    
    protected function buildEqNode(EqNode $node): string
    {
        return '$item["' . $node->getField() . '"] == ' . var_export($node->getValue(), true);
    }
    
    protected function buildNeNode(NeNode $node): string
    {
        return '$item["' . $node->getField() . '"] != ' . var_export($node->getValue(), true);
    }
    
    protected function buildLtNode(LtNode $node): string
    {
        return '$item["' . $node->getField() . '"] < ' . var_export($node->getValue(), true);
    }
    
    protected function buildGtNode(GtNode $node): string
    {
        return '$item["' . $node->getField() . '"] > ' . var_export($node->getValue(), true);
    }
    
    protected function buildLeNode(LeNode $node): string
    {
        return '$item["' . $node->getField() . '"] <= ' . var_export($node->getValue(), true);
    }
    
    protected function buildGeNode(GeNode $node): string
    {
        return '$item["' . $node->getField() . '"] >= ' . var_export($node->getValue(), true);
    }
    
    protected function buildInNode(InNode $node): string
    {
        $values = var_export($node->getValues(), true);
        return 'in_array($item["' . $node->getField() . '"], ' . $values . ')';
    }
    
    protected function buildOutNode(OutNode $node): string
    {
        $values = var_export($node->getValues(), true);
        return '!in_array($item["' . $node->getField() . '"], ' . $values . ')';
    }
    
    protected function buildLikeNode(LikeNode $node): string
    {
        $pattern = str_replace(['%', '_'], ['.*', '.'], $node->getValue());
        return 'preg_match("/^' . addslashes($pattern) . '$/i", $item["' . $node->getField() . '"])';
    }
    
    protected function buildContainsNode(ContainsNode $node): string
    {
        return 'strpos($item["' . $node->getField() . '"], ' . var_export($node->getValue(), true) . ') !== false';
    }
    
    protected function buildAlikeNode(AlikeNode $node): string
    {
        $pattern = str_replace(['%', '_'], ['.*', '.'], $node->getValue());
        return 'preg_match("/^' . addslashes($pattern) . '$/i", $item["' . $node->getField() . '"])';
    }
    
    protected function buildAlikeGlobNode(AlikeGlobNode $node): string
    {
        $pattern = str_replace(['*', '?'], ['.*', '.'], $node->getValue());
        return 'preg_match("/^' . addslashes($pattern) . '$/i", $item["' . $node->getField() . '"])';
    }
    
    protected function buildLikeGlobNode(LikeGlobNode $node): string
    {
        $pattern = str_replace(['*', '?'], ['.*', '.'], $node->getValue());
        return 'preg_match("/^' . addslashes($pattern) . '$/i", $item["' . $node->getField() . '"])';
    }
    
    protected function buildMatchNode(MatchNode $node): string
    {
        return 'preg_match(' . var_export($node->getValue(), true) . ', $item["' . $node->getField() . '"])';
    }
    
    protected function buildEqfNode(EqfNode $node): string
    {
        $format = $node->getFormat() ? ', ' . var_export($node->getFormat(), true) : '';
        return 'date(' . var_export($node->getFormat(), true) . ', strtotime($item["' . $node->getField() . '"])) == ' . var_export($node->getValue(), true);
    }
    
    protected function buildEqnNode(EqnNode $node): string
    {
        return 'is_null($item["' . $node->getField() . '"])';
    }
    
    protected function buildEqtNode(EqtNode $node): string
    {
        $type = $node->getType() ? ', ' . var_export($node->getType(), true) : '';
        return 'gettype($item["' . $node->getField() . '"]) == ' . var_export($node->getType(), true);
    }
    
    protected function buildIeNode(IeNode $node): string
    {
        return 'empty($item["' . $node->getField() . '"])';
    }
    
    protected function buildAndNode(AndNode $node): string
    {
        $conditions = array_map([$this, 'buildQuery'], $node->getQueries());
        return '(' . implode(' && ', $conditions) . ')';
    }
    
    protected function buildOrNode(OrNode $node): string
    {
        $conditions = array_map([$this, 'buildQuery'], $node->getQueries());
        return '(' . implode(' || ', $conditions) . ')';
    }
    
    protected function buildNotNode(NotNode $node): string
    {
        return '!(' . $this->buildQuery($node->getQuery()) . ')';
    }
}
```

## Заключение

RQL система в rollun-datastore предоставляет мощный и гибкий язык запросов с поддержкой:

1. **Стандартных операторов**: eq, ne, lt, gt, le, ge, in, out, like, contains
2. **Расширенных операторов**: alike, alikeGlob, likeGlob, match, eqf, eqn, eqt, ie
3. **Логических операторов**: and, or, not
4. **Операторов выборки**: select, sort, limit, groupby
5. **Агрегатных функций**: count, max, min, sum, avg
6. **Множественных форматов**: RQL, FIQL, PHP условия

Все компоненты работают вместе для обеспечения единообразного API запросов к различным хранилищам данных.
