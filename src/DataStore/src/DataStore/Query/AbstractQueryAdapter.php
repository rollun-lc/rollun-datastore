<?php

namespace rollun\datastore\DataStore\Query;

use RuntimeException;
use Xiag\Rql\Parser\Node\AbstractQueryNode;
use Xiag\Rql\Parser\Node\Query\AbstractComparisonOperatorNode;
use Xiag\Rql\Parser\Node\Query\AbstractLogicOperatorNode;
use Xiag\Rql\Parser\Node\Query\AbstractScalarOperatorNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\NotNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\OrNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;

class AbstractQueryAdapter implements QueryAdapter
{
    public function adapt(Query $query): Query
    {
        if ($node = $this->processQuery($query->getQuery())) {
            $query->setQuery($node);
        }

        if ($sort =$this->processSort($query->getSort())) {
            $query->setSort($sort);
        }

        return $query;
    }

    protected function processQuery(AbstractQueryNode $queryNode = null): ?AbstractQueryNode
    {
        if (!$queryNode) {
            return null;
        }

        switch (true) {
            case ($queryNode instanceof AbstractComparisonOperatorNode):
                return $this->processComparisonOperator($queryNode);
            case ($queryNode instanceof AbstractLogicOperatorNode):
                return $this->processLogicOperator($queryNode);
            default:
                throw new RuntimeException('The Node type not supported: ' . $queryNode->getNodeName());
        }
    }

    protected function processSort(?SortNode $sortNode = null): ?SortNode
    {
        if (!$sortNode) {
            return null;
        }

        $fields = [];
        foreach ($sortNode->getFields() as $field => $order) {
            $field = $this->prepareFieldName($field);
            $fields[$field] = $order;
        }

        return new SortNode($fields);
    }


    protected function processComparisonOperator(AbstractComparisonOperatorNode $node): AbstractComparisonOperatorNode
    {
        $field = $this->prepareFieldName($node->getField());
        $node->setField($field);

        if ($node instanceof AbstractScalarOperatorNode) {
            $value = $this->prepareFieldValue($node->getValue());
            $node->setValue($value);
        }

        return $node;
    }


    protected function processLogicOperator(AbstractLogicOperatorNode $node): AbstractLogicOperatorNode
    {
        $queries = array_map(function (AbstractQueryNode $node) {
            return $this->processQuery($node);
        }, $node->getQueries());

        switch (true) {
            case ($node instanceof NotNode):
                return new NotNode($queries);
            case ($node instanceof AndNode):
                return new AndNode($queries);
            case ($node instanceof OrNode):
                return new OrNode($queries);
            default:
                throw new RuntimeException('The LogicNode type not supported: ' . $node->getNodeName());
        }
    }


    protected function prepareFieldName(string $fieldName): string
    {
        return $fieldName;
    }

    protected function prepareFieldValue($value)
    {
        return $value;
    }
}