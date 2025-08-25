<?php

namespace rollun\datastore\DataStore\Query;

use Graviton\RqlParser\Node\AbstractQueryNode;
use Graviton\RqlParser\Node\Query\AbstractComparisonOperatorNode;
use Graviton\RqlParser\Node\Query\AbstractLogicalOperatorNode;
use Graviton\RqlParser\Node\Query\AbstractScalarOperatorNode;
use Graviton\RqlParser\Node\Query\LogicalOperator\AndNode;
use Graviton\RqlParser\Node\Query\LogicalOperator\NotNode;
use Graviton\RqlParser\Node\Query\LogicalOperator\OrNode;
use Graviton\RqlParser\Node\SortNode;
use Graviton\RqlParser\Query;
use RuntimeException;

class AbstractQueryAdapter implements QueryAdapter
{
    public function adapt(Query $query): Query
    {
        if ($node = $this->processQuery($query->getQuery())) {
            $query->setQuery($node);
        }

        if ($sort = $this->processSort($query->getSort())) {
            $query->setSort($sort);
        }

        return $query;
    }

    protected function processQuery(AbstractQueryNode $queryNode = null): ?AbstractQueryNode
    {
        if (!$queryNode) {
            return null;
        }

        return match (true) {
            $queryNode instanceof AbstractComparisonOperatorNode => $this->processComparisonOperator($queryNode),
            $queryNode instanceof AbstractLogicalOperatorNode => $this->processLogicOperator($queryNode),
            default => throw new RuntimeException('The Node type not supported: ' . $queryNode->getNodeName()),
        };
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


    protected function processLogicOperator(AbstractLogicalOperatorNode $node): AbstractLogicalOperatorNode
    {
        $queries = array_map(fn(AbstractQueryNode $node) => $this->processQuery($node), $node->getQueries());

        return match (true) {
            $node instanceof NotNode => new NotNode($queries),
            $node instanceof AndNode => new AndNode($queries),
            $node instanceof OrNode => new OrNode($queries),
            default => throw new RuntimeException('The LogicNode type not supported: ' . $node->getNodeName()),
        };
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
