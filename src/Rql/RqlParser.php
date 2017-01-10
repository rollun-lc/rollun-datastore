<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 06.06.16
 * Time: 16:34
 */

namespace rolluncom\datastore\Rql;

use Xiag\Rql\Parser\ExpressionParser;
use Xiag\Rql\Parser\Lexer;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Parser;
use Xiag\Rql\Parser\Query;
use Xiag\Rql\Parser\TokenParser;
use Xiag\Rql\Parser\TokenParserGroup;
use Xiag\Rql\Parser\TypeCaster;
use rolluncom\datastore\DataStore\ConditionBuilder\ConditionBuilderAbstract;
use rolluncom\datastore\DataStore\ConditionBuilder\RqlConditionBuilder;
use rolluncom\datastore\DataStore\DataStoreAbstract;
use rolluncom\datastore\Rql\TokenParser\Query\Basic\ScalarOperator\MatchTokenParser as BasicMatchTokenParser;
use rolluncom\datastore\Rql\TokenParser\Query\Fiql\ScalarOperator\MatchTokenParser as FiqlMatchTokenParser;
use rolluncom\datastore\Rql\TokenParser\SelectTokenParser;

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
        array $allowedAggregateFunction = null,
        ConditionBuilderAbstract $conditionBuilder = null
    )
    {
        if (isset($allowedAggregateFunction)) {
            $this->allowedAggregateFunction = $allowedAggregateFunction;
        } else {
            $this->allowedAggregateFunction = ['count', 'max', 'min'];
        }

        if (isset($conditionBuilder)) {
            $this->conditionBuilder = $conditionBuilder;
        } else {
            $this->conditionBuilder = new RqlConditionBuilder();
        }
    }

    /**
     * @param $rqlQueryString. Static method for decode qrl string. Work without rawurlencode str.
     * @return RqlQuery
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
     * @param $rqlQueryString. Prepare rlq string. Fix bug with scope(js Query aggregate). Add '+' char for sort.
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
        $tempRql = preg_replace(['/\%28/', '/\%29/'], ['(', ')'], $rqlQueryString);
        if (isset($tempRql)) {
            $rqlQueryString = $tempRql;
        }
        return $rqlQueryString;
    }

    /**
     * @param $rqlQueryString. Decode rql string with token and lexler.
     * @return RqlQuery
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
            ->addTokenParser(new TokenParser\Query\Basic\ScalarOperator\LikeTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ArrayOperator\InTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ArrayOperator\OutTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\EqTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\NeTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\LtTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\GtTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\LeTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\GeTokenParser())
            ->addTokenParser(new TokenParser\Query\Fiql\ScalarOperator\LikeTokenParser())
            ->addTokenParser(new FiqlMatchTokenParser())
            ->addTokenParser(new BasicMatchTokenParser());

        $parser = (new Parser((new ExpressionParser())
            ->registerTypeCaster('string', new TypeCaster\StringTypeCaster())
            ->registerTypeCaster('integer', new TypeCaster\IntegerTypeCaster())
            ->registerTypeCaster('float', new TypeCaster\FloatTypeCaster())
            ->registerTypeCaster('boolean', new TypeCaster\BooleanTypeCaster())
        ))
            ->addTokenParser(new SelectTokenParser($this->allowedAggregateFunction))
            ->addTokenParser($queryTokenParser)
            ->addTokenParser(new TokenParser\SortTokenParser())
            ->addTokenParser(new TokenParser\LimitTokenParser());

        $rqlQueryObject = $parser->parse((new Lexer())->tokenize($rqlQueryString));

        return $rqlQueryObject;
    }

    /**
     * @param $query. Static method for encode rql obj.
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
     * @param RqlQuery $query. Encode query obj with ConditionBuilder.
     * @return string
     */
    public function encode(Query $query)
    {
        $conditionBuilder = $this->conditionBuilder;
        $rqlQueryString = $conditionBuilder($query->getQuery());
        $rqlQueryString = $rqlQueryString . $this->makeLimit($query);
        $rqlQueryString = $rqlQueryString . $this->makeSort($query);
        $rqlQueryString = $rqlQueryString . $this->makeSelect($query);
        $rqlQueryString = rtrim($rqlQueryString, '&');
        return $rqlQueryString;
    }

    /**
     * @param RqlQuery $query
     * @return string
     */
    protected function makeLimit(Query $query)
    {
        $limitNode = $query->getLimit();
        $limit = !$limitNode ? DataStoreAbstract::LIMIT_INFINITY : $limitNode->getLimit();
        $offset = !$limitNode ? 0 : $limitNode->getOffset();
        if ($limit == DataStoreAbstract::LIMIT_INFINITY && $offset == 0) {
            return '';
        } else {
            return sprintf('&limit(%s,%s)', $limit, $offset);
        }
    }

    /**
     * @param RqlQuery $query
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
     * @param RqlQuery $query
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
     * @param $rqlString. rawurlencode rql string.
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
                    $node = preg_replace_callback(['/\\\\([\w\W])/', '/\\@/', '/\\$/'],
                        function (array $matches) {
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
