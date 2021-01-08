<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\ConditionBuilder;

use rollun\datastore\Rql\Node\BinaryNode\BinaryOperatorNodeAbstract;
use Xiag\Rql\Parser\DataType\Glob;
use Xiag\Rql\Parser\Node\AbstractQueryNode;
use Xiag\Rql\Parser\Node\Query\AbstractArrayOperatorNode;
use Xiag\Rql\Parser\Node\Query\AbstractLogicOperatorNode;
use Xiag\Rql\Parser\Node\Query\AbstractScalarOperatorNode;
use rollun\datastore\DataStore\DataStoreException;

/**
 * Transform rql query nodes to sql, php and rql
 *
 * Class ConditionBuilderAbstract
 * @package rollun\datastore\DataStore\ConditionBuilder
 */
abstract class ConditionBuilderAbstract
{
    protected $literals = [
        'LogicOperator' => [],
        'ArrayOperator' => [
            'in' => ['before' => '(', 'between' => ',(', 'delimiter' => ',', 'after' => '))'],
        ],
        'ScalarOperator' => [
            'ge' => ['before' => '(', 'between' => '>=', 'after' => ')'],
            'gt' => ['before' => '(', 'between' => '>', 'after' => ')'],
            'le' => ['before' => '(', 'between' => '<=', 'after' => ')'],
            'lt' => ['before' => '(', 'between' => '<', 'after' => ')'],
        ],
    ];

    /**
     * @var string Condition if Query === null
     */
    protected $emptyCondition = ' true ';

    /**
     * Make string with conditions for any supported Query
     *
     * @param AbstractQueryNode $rootQueryNode
     * @return string
     */
    public function __invoke(AbstractQueryNode $rootQueryNode = null)
    {
        if (isset($rootQueryNode)) {
            return $this->makeAbstractQueryOperator($rootQueryNode);
        } else {
            return $this->emptyCondition;
        }
    }

    /**
     * Make string with conditions for not null Query
     *
     * @param AbstractQueryNode $queryNode
     * @return string
     * @throws DataStoreException
     */
    public function makeAbstractQueryOperator(AbstractQueryNode $queryNode)
    {
        switch (true) {
            case ($queryNode instanceof AbstractScalarOperatorNode):
                return $this->makeScalarOperator($queryNode);
            case ($queryNode instanceof AbstractLogicOperatorNode):
                return $this->makeLogicOperator($queryNode);
            case ($queryNode instanceof AbstractArrayOperatorNode):
                return $this->makeArrayOperator($queryNode);
            case ($queryNode instanceof BinaryOperatorNodeAbstract):
                return $this->makeBinaryOperator($queryNode);
            default:
                throw new DataStoreException('The Node type not supported: ' . $queryNode->getNodeName());
        }
    }

    /**
     * Make string with conditions for binary operators
     *
     * @param BinaryOperatorNodeAbstract $node
     * @return string
     */
    public function makeBinaryOperator(BinaryOperatorNodeAbstract $node)
    {
        $nodeName = $node->getNodeName();

        if (!isset($this->literals['BinaryOperator'][$nodeName])) {
            throw new DataStoreException(
                'The Binary Operator not supported: ' . $nodeName
            );
        }

        $strQuery = $this->literals['BinaryOperator'][$nodeName]['before']
            . $this->prepareFieldName($node->getField())
            . $this->literals['BinaryOperator'][$nodeName]['after'];

        return $strQuery;
    }

    /**
     * Make string with conditions for ScalarOperatorNode
     *
     * @param AbstractScalarOperatorNode $node
     * @return string
     * @throws DataStoreException
     */
    public function makeScalarOperator(AbstractScalarOperatorNode $node)
    {
        $nodeName = $node->getNodeName();

        if (!isset($this->literals['ScalarOperator'][$nodeName])) {
            throw new DataStoreException(
                'The Scalar Operator not supported: ' . $nodeName
            );
        }

        // TODO: fix hardcode datetime format
        // (see rollun\test\functional\DataStore\DataStore\QueryDateTimeTest::testDateTime)
        $value = $node->getValue() instanceof \DateTime ? $node->getValue()
            ->format("Y-m-d") : $node->getValue();

        $strQuery = $this->literals['ScalarOperator'][$nodeName]['before']
            . $this->prepareFieldName($node->getField())
            . $this->literals['ScalarOperator'][$nodeName]['between']
            . $this->prepareFieldValue($value)
            . $this->literals['ScalarOperator'][$nodeName]['after'];

        return $strQuery;
    }

    /**
     * Prepare field name for using in condition
     *
     * It may be quoting for example
     *
     * @param string $fieldName
     * @return string
     */
    public function prepareFieldName($fieldName)
    {
        return $fieldName;
    }

    /**
     * Prepare field value for using in condition
     *
     * It may be quoting for example
     *
     * @param mixed $fieldValue
     * @return string
     */
    public function prepareFieldValue($fieldValue)
    {
        if ($fieldValue instanceof Glob) {
            return $this->getValueFromGlob($fieldValue);
        } else {
            return $fieldValue;
        }
    }

    /**
     * Return value from Glob
     *
     * I have no idea why, but Xiag\Rql\Parser\DataType\Glob
     * have not method getValue(). We fix it/
     *
     * @see Glob
     * @param Glob $globNode
     * @return string
     */
    public function getValueFromGlob(Glob $globNode)
    {
        $reflection = new \ReflectionClass($globNode);
        $globProperty = $reflection->getProperty('glob');
        $globProperty->setAccessible(true);
        $glob = $globProperty->getValue($globNode);
        $globProperty->setAccessible(false);

        return $glob;
    }

    /**
     * Make string with conditions for LogicOperatorNode
     *
     * @param AbstractLogicOperatorNode $node
     * @return string
     * @throws DataStoreException
     */
    public function makeLogicOperator(AbstractLogicOperatorNode $node)
    {
        $nodeName = $node->getNodeName();

        if (!isset($this->literals['LogicOperator'][$nodeName])) {
            throw new DataStoreException(
                'The Logic Operator not supported: ' . $nodeName
            );
        }

        $arrayQueries = $node->getQueries();
        $strQuery = $this->literals['LogicOperator'][$nodeName]['before'];

        foreach ($arrayQueries as $queryNode) {
            /* @var $queryNode AbstractQueryNode */
            $strQuery = $strQuery . $this->makeAbstractQueryOperator($queryNode)
                . $this->literals['LogicOperator'][$nodeName]['between'];
        }

        $strQuery = rtrim($strQuery, $this->literals['LogicOperator'][$nodeName]['between']);
        $strQuery = $strQuery . $this->literals['LogicOperator'][$nodeName]['after'];

        return $strQuery;
    }

    /**
     * Make string with conditions for ArrayOperatorNode
     *
     * @param AbstractArrayOperatorNode $node
     * @return string
     * @throws DataStoreException
     */
    public function makeArrayOperator(AbstractArrayOperatorNode $node)
    {
        $nodeName = $node->getNodeName();

        if (!isset($this->literals['ArrayOperator'][$nodeName])) {
            throw new DataStoreException(
                'The Array Operator not supported: ' . $nodeName
            );
        }

        $arrayValues = $node->getValues();
        $strQuery = $this->literals['ArrayOperator'][$nodeName]['before']
            . $this->prepareFieldName($node->getField())
            . $this->literals['ArrayOperator'][$nodeName]['between'];

        foreach ($arrayValues as $value) {
            $strQuery = $strQuery
                . $this->prepareFieldValue($value)
                . $this->literals['ArrayOperator'][$nodeName]['delimiter'];
        }

        $strQuery = rtrim($strQuery, $this->literals['ArrayOperator'][$nodeName]['delimiter']);
        $strQuery = $strQuery . $this->literals['ArrayOperator'][$nodeName]['after'];

        return $strQuery;
    }
}
