<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 06.06.16
 * Time: 16:34
 */

namespace rollun\datastore\Rql;

use rollun\datastore\Rql\TokenParser\GroupbyTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator\EqfNodeParser;
use rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator\EqnNodeParser;
use rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator\EqtNodeParser;
use Xiag\Rql\Parser\ExpressionParser;
use Xiag\Rql\Parser\Lexer;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Parser;
use Xiag\Rql\Parser\Query;
use Xiag\Rql\Parser\TokenParser;
use Xiag\Rql\Parser\TokenParserGroup;
use Xiag\Rql\Parser\TypeCaster;
use rollun\datastore\DataStore\ConditionBuilder\ConditionBuilderAbstract;
use rollun\datastore\DataStore\ConditionBuilder\RqlConditionBuilder;
use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\datastore\Rql\TokenParser\Query\Basic\ScalarOperator\MatchTokenParser as BasicMatchTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Basic\ScalarOperator\ContainsTokenParser as BasicContainsTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Basic\ScalarOperator\LikeGlobTokenParser as BasicLikeGlobTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Fiql\ScalarOperator\MatchTokenParser as FiqlMatchTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Fiql\ScalarOperator\ContainsTokenParser as FiqlContainsTokenParser;
use rollun\datastore\Rql\TokenParser\Query\Fiql\ScalarOperator\LikeGlobTokenParser as FiqlLikeGlobTokenParser;
use rollun\datastore\Rql\TokenParser\SelectTokenParser;

class RqlParser
{

    private $allowedAggregateFunction;
    private $conditionBuilder;
    protected static $nodes = [
        'select',
        'sort',
        'limit',
    ];

    public function __construct(
    array $allowedAggregateFunction = null, ConditionBuilderAbstract $conditionBuilder = null
    )
    {
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
     * @param $rqlQueryString . Static method for decode qrl string. Work without rawurlencode str.
     * @return RqlQuery|Query
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
        //$sortFieldPattern = '/([-|+]?[\w]+\,?)/g';
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
     * @return Query
     */
    public function decode($rqlQueryString)
    {
        $rqlQueryString = RqlParser::prepareStringRql($rqlQueryString);
        $queryTokenParser = new TokenParserGroup();
        $queryTokenParser
                ->addTokenParser(new TokenParser\Query\GroupTokenParser($queryTokenParser))
                ->addTokenParser(new TokenParser\Query\Basic\LogicOperator\AndTokenParser($queryTokenParser))
                ->addTokenParser(new TokenParser\Query\Basic\LogicOperator\OrTokenParser($queryTokenParser))
                ->addTokenParser(new TokenParser\Query\Basic\LogicOperator\NotTokenParser($queryTokenParser))
                ->addTokenParser(new TokenParser\Query\Basic\ArrayOperator\InTokenParser())
                ->addTokenParser(new TokenParser\Query\Basic\ArrayOperator\OutTokenParser())
                ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\EqTokenParser())
                ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\NeTokenParser())
                ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\LtTokenParser())
                ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\GtTokenParser())
                ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\LeTokenParser())
                ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\GeTokenParser())
                ->addTokenParser(new BasicLikeGlobTokenParser())
                //->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\LikeTokenParser())
                ->addTokenParser(new TokenParser\Query\Fiql\ArrayOperator\InTokenParser())
                ->addTokenParser(new TokenParser\Query\Fiql\ArrayOperator\OutTokenParser())
                ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\EqTokenParser())
                ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\NeTokenParser())
                ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\LtTokenParser())
                ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\GtTokenParser())
                ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\LeTokenParser())
                ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\GeTokenParser())
                ->addTokenParser(new FiqlLikeGlobTokenParser())
                //->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\LikeTokenParser())
                ->addTokenParser(new FiqlMatchTokenParser())
                ->addTokenParser(new BasicContainsTokenParser())
                ->addTokenParser(new FiqlContainsTokenParser())
                ->addTokenParser(new EqtNodeParser())
                ->addTokenParser(new EqnNodeParser())
                ->addTokenParser(new EqfNodeParser())
                ->addTokenParser(new BasicMatchTokenParser());

        $parser = (new QueryParser((new ExpressionParser())
                ->registerTypeCaster('string', new TypeCaster\StringTypeCaster())
                ->registerTypeCaster('integer', new TypeCaster\IntegerTypeCaster())
                ->registerTypeCaster('float', new TypeCaster\FloatTypeCaster())
                ->registerTypeCaster('boolean', new TypeCaster\BooleanTypeCaster())
                ))
                ->addTokenParser(new SelectTokenParser($this->allowedAggregateFunction))
                ->addTokenParser($queryTokenParser)
                ->addTokenParser(new TokenParser\SortTokenParser())
                ->addTokenParser(new TokenParser\LimitTokenParser())
                ->addTokenParser(new GroupbyTokenParser());

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
        $conditionBuilder = $this->conditionBuilder;
        $rqlQueryString = $conditionBuilder($query->getQuery());
        $rqlQueryString = $rqlQueryString . $this->makeLimit($query);
        $rqlQueryString = $rqlQueryString . $this->makeSort($query);
        $rqlQueryString = $rqlQueryString . $this->makeSelect($query);
        if ($query instanceof RqlQuery) {
            $rqlQueryString = $rqlQueryString . $this->makeGroupby($query);
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
        if ($query->getGroupby() != null) {
            $fields = $query->getGroupby()->getFields();
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

        switch (true) {
            case (!isset($limitNode)):
                $rqlString = '';
                break;
            case $limitNode->getLimit() == DataStoreAbstract::LIMIT_INFINITY && $limitNode->getOffset() == 0:
                $rqlString = '';
                break;
            case empty($limitNode->getOffset()):
                $rqlString = sprintf('&limit(%s)', $limitNode->getLimit());
                break;
            default:
                $rqlString = sprintf('&limit(%s,%s)', $limitNode->getLimit(), $limitNode->getOffset());
        }

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
        $selectNode = $query->getSelect();  //What fields will be return
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
                    $node = preg_replace_callback(['/\\\\([\w\W])/', '/\\@/', '/\\$/'], function (array $matches) {
                        $value = isset($matches[1]) ? $matches[1] : $matches[0];
                        return RqlConditionBuilder::encodeString($value);
                    }, $node);
                }
                $escapedRqlString .= $node . '&';
            }
        }
        $escapedRqlString = rtrim($escapedRqlString, '&');
        return $escapedRqlString;
    }

}
