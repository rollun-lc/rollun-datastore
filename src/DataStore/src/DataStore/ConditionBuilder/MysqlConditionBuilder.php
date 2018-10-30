<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\ConditionBuilder;

use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\Rql\Node\BinaryNode\BinaryOperatorNodeAbstract;
use Xiag\Rql\Parser\DataType\Glob;
use Xiag\Rql\Parser\Node\Query\AbstractScalarOperatorNode;
use Zend\Db\Adapter\Platform\PlatformInterface;

class MysqlConditionBuilder extends ConditionBuilderAbstract
{
    protected $literals = [
        'LogicOperator' => [
            'and' => ['before' => '(', 'between' => ' AND ', 'after' => ')'],
            'or' => ['before' => '(', 'between' => ' OR ', 'after' => ')'],
            'not' => ['before' => '( NOT (', 'between' => ' error ', 'after' => ') )'],
        ],
        'ArrayOperator' => [
            'in' => ['before' => '(', 'between' => ' IN (', 'delimiter' => ',', 'after' => '))'],
            'out' => ['before' => '(', 'between' => ' NOT IN (', 'delimiter' => ',', 'after' => '))']
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
            'eqn' => ['before' => '(', 'after' => ' is NULL)'],
            'eqt' => ['before' => '(', 'after' => ' is TRUE)'],
            'eqf' => ['before' => '(', 'after' => ' is FALSE)'],
            'ie' => ['before' => '(', 'after' => ')'],
        ]
    ];

    /**
     *
     * @var PlatformInterface
     */
    protected $platform;

    protected $tableName;

    /**
     *
     * @param PlatformInterface $platform
     * @param $tableName
     */
    public function __construct(PlatformInterface $platform, $tableName)
    {
        $this->platform = $platform;
        $this->emptyCondition = $this->prepareFieldValue(1)
            . ' = '
            . $this->prepareFieldValue(1);
        $this->tableName = $tableName;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function prepareFieldValue($fieldValue)
    {
        $fieldValue = parent::prepareFieldValue($fieldValue);
        return $this->platform->quoteValue($fieldValue);
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function getValueFromGlob(Glob $globNode)
    {
        $constStar = 'star_hjc7vjHg6jd8mv8hcy75GFt0c67cnbv74FegxtEDJkcucG64frblmkb';
        $constQuestion = 'question_hjc7vjHg6jd8mv8hcy75GFt0c67cnbv74FegxtEDJkcucG64frblmkb';

        $glob = parent::getValueFromGlob($globNode);

        $regexSQL = strtr(
            preg_quote(rawurldecode(strtr($glob, ['*' => $constStar, '?' => $constQuestion])), '/'), [$constStar => '%', $constQuestion => '_']
        );

        return $regexSQL;
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
        $value = $node->getValue() instanceof \DateTime ? $node->getValue()->format("Y-m-d") : $node->getValue();
        $strQuery = $this->literals['ScalarOperator'][$nodeName]['before']
            . $this->prepareFieldName($node->getField());

        if ($nodeName === 'contains') {
            $strQuery .= $this->literals['ScalarOperator'][$nodeName]['between'] .
                trim($this->prepareFieldValue($value), '\'');
        } else {
            $strQuery .= $this->literals['ScalarOperator'][$nodeName]['between']
                . $this->prepareFieldValue($value);
        }

        $strQuery .= $this->literals['ScalarOperator'][$nodeName]['after'];

        return $strQuery;
    }

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
            $strQuery .= $field . 'IS NULL OR ' . $field . ' IS FALSE';
        } else {
            $strQuery .= $field;
        }

        $strQuery .= $this->literals['BinaryOperator'][$nodeName]['after'];

        return $strQuery;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function prepareFieldName($fieldName)
    {
        if (!strpos($fieldName, '.')) {
            if ($fieldName == 'id') {
                $fieldName = $this->platform->quoteIdentifierInFragment("{$this->tableName}.{$fieldName}");
                /*$fieldName = $this->db->platform->quoteIdentifier($this->tableName) .  '.' .$this->db->platform->quoteIdentifier($fieldName);*/
            } else {
                $fieldName = $this->platform->quoteIdentifierInFragment("{$fieldName}");
            }
        } else {
            //TODO: force set tableName!!!!!
            $name = explode('.', $fieldName);
            $fieldName = $this->platform->quoteIdentifierInFragment("{$name[0]}.{$name[1]}");
            //$fieldName = $this->db->platform->quoteIdentifier($name[0]) . '.' . $this->db->platform->quoteIdentifier($name[1]);
        }
        return $fieldName;

    }
}
