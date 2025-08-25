<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\ConditionBuilder;

use Graviton\RqlParser\Glob;
use Graviton\RqlParser\Node\Query\AbstractScalarOperatorNode;
use Laminas\Db\Adapter\Exception\RuntimeException;
use rollun\datastore\DataStore\ConnectionException;
use rollun\datastore\DataStore\LaminasDbExceptionDetector;
use rollun\datastore\DataStore\OperationTimedOutException;
use rollun\datastore\Rql\Node\BinaryNode\BinaryOperatorNodeAbstract;
use rollun\datastore\DataStore\DataStoreException;
use Laminas\Db\Adapter\AdapterInterface;

/**
 * Class SqlConditionBuilder
 * @package rollun\datastore\DataStore\ConditionBuilder
 */
class SqlConditionBuilder extends ConditionBuilderAbstract
{
    protected $literals = [
        'LogicOperator' => [
            'and' => ['before' => '(', 'between' => ' AND ', 'after' => ')'],
            'or' => ['before' => '(', 'between' => ' OR ', 'after' => ')'],
            'not' => ['before' => '( (', 'between' => ' error ', 'after' => ') ) IS NOT TRUE'],
        ],
        'ArrayOperator' => [
            'in' => ['before' => '(', 'between' => ' IN (', 'delimiter' => ',', 'after' => '))'],
            'out' => ['before' => '(', 'between' => ' NOT IN (', 'delimiter' => ',', 'after' => '))'],
        ],
        'ScalarOperator' => [
            'eq' => ['before' => '(', 'between' => '=', 'after' => ')'],
            'ne' => ['before' => '(', 'between' => '<>', 'after' => ')'],
            'ge' => ['before' => '(', 'between' => '>=', 'after' => ')'],
            'gt' => ['before' => '(', 'between' => '>', 'after' => ')'],
            'le' => ['before' => '(', 'between' => '<=', 'after' => ')'],
            'lt' => ['before' => '(', 'between' => '<', 'after' => ')'],
            'like' => ['before' => '(', 'between' => ' LIKE BINARY ', 'after' => ')'],
            'alike' => ['before' => '(', 'between' => ' LIKE ', 'after' => ')'],
            'contains' => ['before' => '(', 'between' => ' LIKE \'%', 'after' => '%\')'],
        ],
        'BinaryOperator' => [
            'eqn' => ['before' => '(', 'after' => ' IS NULL)'],
            'eqt' => ['before' => '(', 'after' => ' IS TRUE)'],
            'eqf' => ['before' => '(', 'after' => ' IS FALSE)'],
            'ie' => ['before' => '(', 'after' => ')'],
        ],
    ];

    /**
     *
     * @var AdapterInterface
     */
    protected $db;

    /**
     *
     * @param AdapterInterface $dbAdapter
     * @param $tableName
     * @param string $tableName
     */
    public function __construct(AdapterInterface $dbAdapter, protected $tableName)
    {
        $this->db = $dbAdapter;
        $this->emptyCondition = $this->prepareFieldValue(1) . ' = ' . $this->prepareFieldValue(1);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareFieldValue($fieldValue)
    {
        $fieldValue = parent::prepareFieldValue($fieldValue);

        if (is_int($fieldValue)) {
            return $fieldValue;
        }

        try {
            return $this->db->platform->quoteValue($fieldValue);
        } catch (RuntimeException $e) {
            if (LaminasDbExceptionDetector::isConnectionException($e)) {
                throw new ConnectionException($e->getMessage(), $e->getCode(), $e);
            }
            if (LaminasDbExceptionDetector::isOperationTimedOutException($e)) {
                throw new OperationTimedOutException($e->getMessage(), $e->getCode(), $e);
            }
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getValueFromGlob(Glob $globNode)
    {
        $constStar = 'star_hjc7vjHg6jd8mv8hcy75GFt0c67cnbv74FegxtEDJkcucG64frblmkb';
        $constQuestion = 'question_hjc7vjHg6jd8mv8hcy75GFt0c67cnbv74FegxtEDJkcucG64frblmkb';

        $glob = parent::getValueFromGlob($globNode);

        $regexSQL = strtr(
            rawurldecode(strtr($glob, ['*' => $constStar, '?' => $constQuestion])),
            [$constStar => '%', $constQuestion => '_']
        );

        return $regexSQL;
    }

    /**
     * {@inheritdoc}
     */
    public function makeScalarOperator(AbstractScalarOperatorNode $node)
    {
        $nodeName = $node->getNodeName();

        if (!isset($this->literals['ScalarOperator'][$nodeName])) {
            throw new DataStoreException(
                'The Scalar Operator not suppoted: ' . $nodeName
            );
        }

        // TODO hardcode format
        $value = $node->getValue() instanceof \DateTime ? $node->getValue()
            ->format("Y-m-d") : $node->getValue();

        $strQuery = $this->literals['ScalarOperator'][$nodeName]['before'] . $this->prepareFieldName($node->getField());

        if ($nodeName === 'contains') {
            $strQuery .= $this->literals['ScalarOperator'][$nodeName]['between']
                . trim($this->prepareFieldValue($value), '\'');
        } else {
            $strQuery .= $this->literals['ScalarOperator'][$nodeName]['between'] . $this->prepareFieldValue($value);
        }

        $strQuery .= $this->literals['ScalarOperator'][$nodeName]['after'];

        return $strQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function makeBinaryOperator(BinaryOperatorNodeAbstract $node)
    {
        $nodeName = $node->getNodeName();

        if (!isset($this->literals['BinaryOperator'][$nodeName])) {
            throw new DataStoreException(
                'The Binary Operator not suppoted: ' . $nodeName
            );
        }

        $strQuery = $this->literals['BinaryOperator'][$nodeName]['before'];
        $field = $this->prepareFieldName($node->getField());

        if ($nodeName === 'ie') {
            $strQuery .= $field . ' IS NULL OR ' . $field . ' IS FALSE OR ' . $field . ' = \'\'';
        } else {
            $strQuery .= $field;
        }

        $strQuery .= $this->literals['BinaryOperator'][$nodeName]['after'];

        return $strQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareFieldName($fieldName)
    {
        if (!strpos($fieldName, '.')) {
            if ($fieldName == 'id') {
                $fieldName = $this->db->getPlatform()->quoteIdentifierInFragment("{$this->tableName}.{$fieldName}");
            } else {
                $fieldName = $this->db->getPlatform()->quoteIdentifierInFragment("{$fieldName}");
            }
        } else {
            // TODO: force set table
            $name = explode('.', $fieldName);
            $fieldName = $this->db->getPlatform()->quoteIdentifierInFragment("{$name[0]}.{$name[1]}");
        }

        return $fieldName;
    }
}
