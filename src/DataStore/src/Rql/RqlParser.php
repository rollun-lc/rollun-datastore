<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql;

use Graviton\RqlParser\AbstractNode;
use Graviton\RqlParser\Lexer;
use Graviton\RqlParser\Node\SortNode;
use Graviton\RqlParser\NodeParser\QueryNodeParser;
use Graviton\RqlParser\NodeParserChain;
use Graviton\RqlParser\Parser;
use Graviton\RqlParser\Query;
use Graviton\RqlParser\ValueParser;
use Graviton\RqlParser\TypeCaster;
use Graviton\RqlParser\ValueParser\ArrayParser;
use Graviton\RqlParser\ValueParser\FieldParser;
use Graviton\RqlParser\ValueParser\GlobParser;
use Graviton\RqlParser\ValueParser\IntegerParser;
use Graviton\RqlParser\ValueParser\ScalarParser;
use rollun\datastore\Rql\TokenParser\GroupbyTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator\EqfTokenParser as BasicEqfTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator\EqnTokenParser as BasicEqnTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator\EqtTokenParser as BasicEqtTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator\IeTokenParser as BasicIeTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Fiql\BinaryOperator\EqfTokenParser as FiqlEqfTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Fiql\BinaryOperator\EqnTokenParser as FiqlEqnTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Fiql\BinaryOperator\EqtTokenParser as FiqlEqtTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Fiql\BinaryOperator\IeTokenParser as FiqlIeTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Basic\ScalarOperator\MatchTokenParser as BasicMatchTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Basic\ScalarOperator\ContainsTokenParser as BasicContainsTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Basic\ScalarOperator\LikeGlobTokenParser as BasicLikeGlobTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Basic\ScalarOperator\AlikeGlobTokenParser as BasicAlikeGlobTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Fiql\ScalarOperator\MatchTokenParser as FiqlMatchTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Fiql\ScalarOperator\ContainsTokenParser as FiqlContainsTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Fiql\ScalarOperator\LikeGlobTokenParser as FiqlLikeGlobTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Fiql\ScalarOperator\AlikeGlobTokenParser as FiqlAlikeGlobTokenParser;
use Graviton\RqlParser\NodeParser;
use rollun\datastore\DataStore\ConditionBuilder\ConditionBuilderAbstract;
use rollun\datastore\DataStore\ConditionBuilder\RqlConditionBuilder;
use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\datastore\Rql\TokenParser\SelectTokenParser;

class RqlParser
{
    private $allowedAggregateFunction;

    /**
     * @var RqlConditionBuilder
     */
    private $conditionBuilder;

    protected static $nodes = [
        'select',
        'sort',
        'limit',
    ];

    public function __construct(
        array $allowedAggregateFunction = null,
        ConditionBuilderAbstract $conditionBuilder = null
    ) {
        if (isset($allowedAggregateFunction)) {
            $this->allowedAggregateFunction = $allowedAggregateFunction;
        } else {
            $this->allowedAggregateFunction = ['count', 'max', 'min', 'sum', 'avg'];
        }

        if (isset($conditionBuilder)) {
            $this->conditionBuilder = $conditionBuilder;
        } else {
            $this->conditionBuilder = new RqlConditionBuilder();
        }
    }

    /**
     * Static method for decode qrl string. Work without rawurlencode str
     *
     * @param $rqlQueryString .
     * @return Query
     */
    public static function rqlDecode($rqlQueryString)
    {
        $rqlQueryString = RqlParser::encodedStrQuery($rqlQueryString);
        $rqlQueryString = RqlParser::prepareStringRql($rqlQueryString);
        $parser = new self();
        $result = $parser->decode($rqlQueryString);
        unset($parser);

        return $result;
    }

    /**
     * @param $rqlQueryString . Prepare rlq string. Fix bug with scope(js Query aggregate). Add '+' char for sort.
     * @return mixed
     */
    protected static function prepareStringRql($rqlQueryString)
    {
        $sortNodePattern = '/sort\(([^\(\)\&]+)\)/';
        $match = [];

        if (preg_match($sortNodePattern, $rqlQueryString, $match)) {
            $sortNode = "sort(";
            $fieldsSortType = explode(',', $match[1]);

            foreach ($fieldsSortType as $fieldSortType) {
                if (!preg_match('/^[+|-]([\W\w])/', $fieldSortType)) {
                    $fieldSortType = '+' . $fieldSortType;
                }

                $sortNode .= $fieldSortType . ',';
            }

            $sortNode = trim($sortNode, ",") . ")";
            $rqlQueryString = preg_replace($sortNodePattern, $sortNode, $rqlQueryString);
        }

        return $rqlQueryString;
    }

    /**
     * @param $rqlQueryString . Decode rql string with token and lexler.
     * @return AbstractNode
     */
    public function decode($rqlQueryString)
    {
        $rqlQueryString = RqlParser::prepareStringRql($rqlQueryString);
//        $queryTokenParser = new TokenParserGroup();
//        $queryTokenParser->addTokenParser(new TokenParser\Query\GroupTokenParser($queryTokenParser))
//            ->addTokenParser(new TokenParser\Query\Basic\LogicOperator\AndTokenParser($queryTokenParser))
//            ->addTokenParser(new TokenParser\Query\Basic\LogicOperator\OrTokenParser($queryTokenParser))
//            ->addTokenParser(new TokenParser\Query\Basic\LogicOperator\NotTokenParser($queryTokenParser))
//            ->addTokenParser(new TokenParser\Query\Basic\ArrayOperator\InTokenParser())
//            ->addTokenParser(new TokenParser\Query\Basic\ArrayOperator\OutTokenParser())
//            ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\EqTokenParser())
//            ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\NeTokenParser())
//            ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\LtTokenParser())
//            ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\GtTokenParser())
//            ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\LeTokenParser())
//            ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\GeTokenParser())
//            ->addTokenParser(new BasicLikeGlobTokenParser())
//            ->addTokenParser(new BasicAlikeGlobTokenParser())
//            ->addTokenParser(new BasicContainsTokenParser())
//            ->addTokenParser(new BasicEqtTokenParser())
//            ->addTokenParser(new BasicEqnTokenParser())
//            ->addTokenParser(new BasicEqfTokenParser())
//            ->addTokenParser(new BasicIeTokenParser())
//            ->addTokenParser(new BasicMatchTokenParser())
//            ->addTokenParser(new TokenParser\Query\Fiql\ArrayOperator\InTokenParser())
//            ->addTokenParser(new TokenParser\Query\Fiql\ArrayOperator\OutTokenParser())
//            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\EqTokenParser())
//            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\NeTokenParser())
//            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\LtTokenParser())
//            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\GtTokenParser())
//            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\LeTokenParser())
//            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\GeTokenParser())
//            ->addTokenParser(new FiqlLikeGlobTokenParser())
//            ->addTokenParser(new FiqlAlikeGlobTokenParser())
//            ->addTokenParser(new FiqlMatchTokenParser())
//            ->addTokenParser(new FiqlContainsTokenParser())
//            ->addTokenParser(new FiqlEqtTokenParser())
//            ->addTokenParser(new FiqlEqnTokenParser())
//            ->addTokenParser(new FiqlEqfTokenParser())
//            ->addTokenParser(new FiqlIeTokenParser());
//
//        $parser = (new QueryParser(
//            (new ExpressionParser())->registerTypeCaster('string', new TypeCaster\StringTypeCaster())
//                ->registerTypeCaster('integer', new TypeCaster\IntegerTypeCaster())
//                ->registerTypeCaster('float', new TypeCaster\FloatTypeCaster())
//                ->registerTypeCaster('boolean', new TypeCaster\BooleanTypeCaster())
//        ))->addTokenParser(new SelectTokenParser($this->allowedAggregateFunction))
//            ->addTokenParser($queryTokenParser)
//            ->addTokenParser(new TokenParser\SortTokenParser())
//            ->addTokenParser(new TokenParser\LimitTokenParser())
//            ->addTokenParser(new GroupbyTokenParser());

//        $rqlQueryObject = $parser->parse((new Lexer())->tokenize($rqlQueryString));

        $scalarParser = (new ValueParser\ScalarParser())
            ->registerTypeCaster('string', new TypeCaster\StringTypeCaster())
            ->registerTypeCaster('integer', new TypeCaster\IntegerTypeCaster())
            ->registerTypeCaster('float', new TypeCaster\FloatTypeCaster())
            ->registerTypeCaster('boolean', new TypeCaster\BooleanTypeCaster());
        $arrayParser = new ValueParser\ArrayParser($scalarParser);
        $fieldParser = new ValueParser\FieldParser();
        $integerParser = new ValueParser\IntegerParser();

        $queryNodeParser = new QueryNodeParser();
        $queryNodeParser
            ->addNodeParser(new NodeParser\Query\GroupNodeParser($queryNodeParser))
            ->addNodeParser(new NodeParser\Query\LogicalOperator\AndNodeParser($queryNodeParser))
            ->addNodeParser(new NodeParser\Query\LogicalOperator\OrNodeParser($queryNodeParser))
            ->addNodeParser(new NodeParser\Query\LogicalOperator\NotNodeParser($queryNodeParser))
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Rql\InNodeParser($fieldParser, $arrayParser))
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Rql\OutNodeParser($fieldParser, $arrayParser))
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Rql\EqNodeParser($fieldParser, $scalarParser))
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Rql\NeNodeParser($fieldParser, $scalarParser))
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Rql\LtNodeParser($fieldParser, $scalarParser))
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Rql\GtNodeParser($fieldParser, $scalarParser))
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Rql\LeNodeParser($fieldParser, $scalarParser))
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Rql\GeNodeParser($fieldParser, $scalarParser))
            ->addNodeParser(new BasicLikeGlobTokenParser())
            ->addNodeParser(new BasicAlikeGlobTokenParser())
            ->addNodeParser(new BasicContainsTokenParser())
            ->addNodeParser(new BasicEqtTokenParser())
            ->addNodeParser(new BasicEqnTokenParser())
            ->addNodeParser(new BasicEqfTokenParser())
            ->addNodeParser(new BasicIeTokenParser())
            ->addNodeParser(new BasicMatchTokenParser())
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Fiql\InNodeParser($fieldParser, $arrayParser))
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Fiql\OutNodeParser($fieldParser, $arrayParser))
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Fiql\EqNodeParser($fieldParser, $scalarParser))
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Fiql\NeNodeParser($fieldParser, $scalarParser))
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Fiql\LtNodeParser($fieldParser, $scalarParser))
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Fiql\GtNodeParser($fieldParser, $scalarParser))
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Fiql\LeNodeParser($fieldParser, $scalarParser))
            ->addNodeParser(new NodeParser\Query\ComparisonOperator\Fiql\GeNodeParser($fieldParser, $scalarParser))
            ->addNodeParser(new FiqlLikeGlobTokenParser())
            ->addNodeParser(new FiqlAlikeGlobTokenParser())
            ->addNodeParser(new FiqlMatchTokenParser())
            ->addNodeParser(new FiqlContainsTokenParser())
            ->addNodeParser(new FiqlEqtTokenParser())
            ->addNodeParser(new FiqlEqnTokenParser())
            ->addNodeParser(new FiqlEqfTokenParser())
            ->addNodeParser(new FiqlIeTokenParser());

        $nodeParserChain = (new NodeParserChain())
            ->addNodeParser(new SelectTokenParser($this->allowedAggregateFunction))
            ->addNodeParser($queryNodeParser)
            ->addNodeParser(new NodeParser\SortNodeParser($fieldParser))
            ->addNodeParser(new NodeParser\LimitNodeParser($integerParser))
            ->addNodeParser(new GroupbyTokenParser());

        $parser = new QueryParser($nodeParserChain);
        $rqlQueryObject = $parser->parse((new Lexer())->tokenize($rqlQueryString));
        return $rqlQueryObject;
    }

    /**
     * @param $query . Static method for encode rql obj.
     * @return string
     */
    public static function rqlEncode($query)
    {
        $parser = new self();
        $result = $parser->encode($query);
        unset($parser);

        return $result;
    }

    /**
     * @param Query $query . Encode query obj with ConditionBuilder.
     * @return string
     */
    public function encode(Query $query)
    {
        $rqlQueryString = $this->conditionBuilder->__invoke($query->getQuery());
        $rqlQueryString .= $this->makeLimit($query);
        $rqlQueryString .= $this->makeSort($query);
        $rqlQueryString .= $this->makeSelect($query);

        if ($query instanceof RqlQuery) {
            $rqlQueryString .= $this->makeGroupby($query);
        }

        $rqlQueryString = rtrim($rqlQueryString, '&');

        return $rqlQueryString;
    }

    /**
     * @param RqlQuery $query
     * @return string
     */
    protected function makeGroupby(RqlQuery $query)
    {
        $groupBy = '';

        if ($query->getGroupBy() != null) {
            $fields = $query->getGroupBy()
                ->getFields();
            $groupBy = '&groupby(';

            foreach ($fields as $field) {
                $groupBy .= $field . ',';
            }

            $groupBy = rtrim($groupBy, ',') . ')';
        }

        return $groupBy;
    }

    /**
     * @param Query $query
     * @return string
     */
    protected function makeLimit(Query $query)
    {
        $limitNode = $query->getLimit();

        $rqlString = match (true) {
            !isset($limitNode) => '',
            $limitNode->getLimit() == DataStoreAbstract::LIMIT_INFINITY && $limitNode->getOffset() == 0 => '',
            empty($limitNode->getOffset()) => sprintf('&limit(%s)', $limitNode->getLimit()),
            default => sprintf('&limit(%s,%s)', $limitNode->getLimit(), $limitNode->getOffset()),
        };

        return $rqlString;
    }

    /**
     * @param Query $query
     * @return string
     */
    protected function makeSort(Query $query)
    {
        $sortNode = $query->getSort();
        $sortFields = !$sortNode ? [] : $sortNode->getFields();

        if (empty($sortFields)) {
            return '';
        } else {
            $strSort = '';

            foreach ($sortFields as $key => $value) {
                $prefix = $value == SortNode::SORT_DESC ? '-' : '+';
                $strSort = $strSort . $prefix . $key . ',';
            }

            return '&sort(' . rtrim($strSort, ',') . ')';
        }
    }

    /**
     * @param Query $query
     * @return string
     */
    protected function makeSelect(Query $query)
    {
        $selectNode = $query->getSelect();  // What fields will be return ?
        $selectFields = !$selectNode ? [] : $selectNode->getFields();

        if (empty($selectFields)) {
            return '';
        } else {
            $selectString = '&select(';

            foreach ($selectFields as $field) {
                $selectString = $selectString . $field . ',';
            }

            return rtrim($selectString, ',') . ')';
        }
    }

    /**
     * @param $rqlString . rawurlencode rql string.
     * @return string
     */
    protected static function encodedStrQuery($rqlString)
    {
        $escapedRqlString = '';
        $nodes = preg_split('/\&/', $rqlString, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($nodes as $node) {
            $match = [];

            if (preg_match('/([\w]+)/', $node, $match)) {
                if (!in_array($match[1], RqlParser::$nodes)) {
                    $node = preg_replace_callback(
                        ['/\\\\([\w\W])/', '/\\@/', '/\\$/'],
                        function (array $matches) {
                            $value = $matches[1] ?? $matches[0];

                            return RqlConditionBuilder::encodeString($value);
                        },
                        $node
                    );
                }

                $escapedRqlString .= $node . '&';
            }
        }

        $escapedRqlString = rtrim($escapedRqlString, '&');

        return $escapedRqlString;
    }
}
