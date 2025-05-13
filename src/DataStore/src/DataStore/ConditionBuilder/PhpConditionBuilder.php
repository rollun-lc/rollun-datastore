<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\ConditionBuilder;

use Xiag\Rql\Parser\DataType\Glob;
use rollun\datastore\DataStore\DataStoreException;

/**
 * Class PhpConditionBuilder
 * @package rollun\datastore\DataStore\ConditionBuilder
 */
class PhpConditionBuilder extends ConditionBuilderAbstract
{
    protected $literals = [
        'LogicOperator' => [
            'and' => ['before' => '(', 'between' => ' && ', 'after' => ')'],
            'or' => ['before' => '(', 'between' => ' || ', 'after' => ')'],
            'not' => ['before' => '( !(', 'between' => ' error ', 'after' => ') )'],
        ],
        'ArrayOperator' => [
            'in' => ['before' => '(in_array(', 'between' => ',[', 'delimiter' => ',', 'after' => ']))'],
            'out' => ['before' => '(!in_array(', 'between' => ',[', 'delimiter' => ',', 'after' => ']))'],
        ],
        'ScalarOperator' => [
            'eq' => ['before' => '(', 'between' => '==', 'after' => ')'],
            'ne' => ['before' => '(', 'between' => '!=', 'after' => ')'],
            'ge' => ['before' => '(', 'between' => '>=', 'after' => ')'],
            'gt' => ['before' => '(', 'between' => '>', 'after' => ')'],
            'le' => ['before' => '(', 'between' => '<=', 'after' => ')'],
            'lt' => ['before' => '(', 'between' => '<', 'after' => ')'],
            'like' => [
                'before' => '( ($_field = ',
                'between' => ") !=='' && preg_match(",
                'after' => ', $_field) )',
            ],
            'alike' => [
                'before' => '( ($_field = ',
                'between' => ") !=='' && preg_match(",
                'after' => '. \'i\', $_field) )',
            ],
            'contains' => [
                'before' => '( ($_field = ',
                'between' => ") !=='' && preg_match('/' . trim(",
                'after' => ',"\'"). \'/i\', $_field) )',
            ],
        ],
        'BinaryOperator' => [
            'eqn' => ['before' => 'is_null(', 'after' => ')'],
            // TODO: make strict comparison (to implement it need to make data stores typed)
            'eqt' => ['before' => '(', 'after' => '==true)'],
            'eqf' => ['before' => '(', 'after' => '==false)'],
            'ie' => ['before' => 'empty(', 'after' => ')'],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function prepareFieldName($fieldName)
    {
        return '$item[\'' . addslashes($fieldName) . '\']';
    }

    /**
     * {@inheritdoc}
     */
    public function prepareFieldValue($fieldValue)
    {
        $fieldValue = parent::prepareFieldValue($fieldValue);

        switch (true) {
            case is_bool($fieldValue):
                $fieldValue = (bool) $fieldValue ? true : false;

                return $fieldValue;
            case is_string($fieldValue):
                return "'" . addslashes($fieldValue) . "'";
            case is_numeric($fieldValue):
                return $fieldValue;
            case is_null($fieldValue):
                return 'null';
            default:
                throw new DataStoreException('Type ' . gettype($fieldValue) . ' is not supported');
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
        $anchorStart = true;

        if (str_starts_with($glob, '*')) {
            $anchorStart = false;
            $glob = ltrim($glob, '*');
        }

        $anchorEnd = true;

        if (str_ends_with($glob, '*')) {
            $anchorEnd = false;
            $glob = rtrim($glob, '*');
        }

        $regex = strtr(
            preg_quote(rawurldecode(strtr($glob, ['*' => $constStar, '?' => $constQuestion])), '/'),
            [$constStar => '.*', $constQuestion => '.']
        );

        if ($anchorStart) {
            $regex = '^' . $regex;
        }

        if ($anchorEnd) {
            $regex .= '$';
        }

        return '/' . $regex . '/';
    }
}
