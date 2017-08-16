<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore\DataStore\ConditionBuilder;

use Xiag\Rql\Parser\DataType\Glob;
use Xiag\Rql\Parser\Node\AbstractQueryNode;
use Xiag\Rql\Parser\Node\Query\AbstractArrayOperatorNode;
use Xiag\Rql\Parser\Node\Query\AbstractLogicOperatorNode;
use Xiag\Rql\Parser\Node\Query\AbstractScalarOperatorNode;
use rollun\datastore\DataStore\DataStoreException;

/**
 * Make string with conditions for Query
 *
 * Format of this string depends on implementation
 *
 * @todo Data type fore Xiag\Rql\Parser\DataType\DateTime
 * @see PhpConditionBuilder
 * @see PhpConditionBuilder
 * @see SqlConditionBuilder
 * @category   rest
 * @package    zaboy
 */
abstract class ConditionBuilderAbstract
{

    protected $literals = [
        'LogicOperator' => [
        ],
        'ArrayOperator' => [
            'in' => ['before' => '(', 'between' => ',(', 'delimiter' => ',', 'after' => '))']
        ],
        'ScalarOperator' => [
            'ge' => ['before' => '(', 'between' => '>=', 'after' => ')'],
            'gt' => ['before' => '(', 'between' => '>', 'after' => ')'],
            'le' => ['before' => '(', 'between' => '<=', 'after' => ')'],
            'lt' => ['before' => '(', 'between' => '<', 'after' => ')'],
        ]
    ];

    /**
     * @var string Contition if Query === null
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
            case is_a($queryNode, 'Xiag\Rql\Parser\Node\Query\AbstractScalarOperatorNode', true):
                return $this->makeScalarOperator($queryNode);
            case is_a($queryNode, 'Xiag\Rql\Parser\Node\Query\AbstractLogicOperatorNode', true):
                return $this->makeLogicOperator($queryNode);
            case is_a($queryNode, 'Xiag\Rql\Parser\Node\Query\AbstractArrayOperatorNode', true):
                return $this->makeArrayOperator($queryNode);
            default:
                throw new DataStoreException(
                    'The Node type not suppoted: ' . $queryNode->getNodeName()
                );
        }
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
                'The Scalar Operator not suppoted: ' . $nodeName
            );
        }
        $value = $node->getValue() instanceof \DateTime ? $node->getValue()->format("Y-m-d") : $node->getValue();

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
     * @param string $fieldValue
     * @return string
     */
    public function prepareFieldValue($fieldValue)
    {
        if (is_a($fieldValue, 'Xiag\Rql\Parser\DataType\Glob', true)) {
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
                'The Logic Operator not suppoted: ' . $nodeName
            );
        }
        $arrayQueries = $node->getQueries();
        $strQuery = $this->literals['LogicOperator'][$nodeName]['before'];
        foreach ($arrayQueries as $queryNode) {
            /* @var $queryNode AbstractQueryNode */
            $strQuery = $strQuery
                . $this->makeAbstractQueryOperator($queryNode)
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
                'The Array Operator not suppoted: ' . $nodeName
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
