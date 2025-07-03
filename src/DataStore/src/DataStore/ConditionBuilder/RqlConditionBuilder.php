<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\ConditionBuilder;

use Xiag\Rql\Parser\DataType\Glob;

/**
 * Class RqlConditionBuilder
 * @package rollun\datastore\DataStore\ConditionBuilder
 */
class RqlConditionBuilder extends ConditionBuilderAbstract
{
    public const TEXT_NULL = 'null()';

    protected $literals = [
        'LogicOperator' => [
            'and' => ['before' => 'and(', 'between' => ',', 'after' => ')'],
            'or' => ['before' => 'or(', 'between' => ',', 'after' => ')'],
            'not' => ['before' => 'not(', 'between' => ',', 'after' => ')'],
        ],
        'ArrayOperator' => [
            'in' => ['before' => 'in(', 'between' => ',(', 'delimiter' => ',', 'after' => '))'],
            'out' => ['before' => 'out(', 'between' => ',(', 'delimiter' => ',', 'after' => '))'],
        ],
        'ScalarOperator' => [
            'eq' => ['before' => 'eq(', 'between' => ',', 'after' => ')'],
            'ne' => ['before' => 'ne(', 'between' => ',', 'after' => ')'],
            'ge' => ['before' => 'ge(', 'between' => ',', 'after' => ')'],
            'gt' => ['before' => 'gt(', 'between' => ',', 'after' => ')'],
            'le' => ['before' => 'le(', 'between' => ',', 'after' => ')'],
            'lt' => ['before' => 'lt(', 'between' => ',', 'after' => ')'],
            'like' => ['before' => 'like(', 'between' => ',', 'after' => ')'],
            'alike' => ['before' => 'alike(', 'between' => ',', 'after' => ')'],
            'contains' => ['before' => 'contains(', 'between' => ',', 'after' => ')'],
        ],
        'BinaryOperator' => [
            'eqn' => ['before' => 'eqn(', 'after' => ')'],
            'eqt' => ['before' => 'eqt(', 'after' => ')'],
            'eqf' => ['before' => 'eqf(', 'after' => ')'],
            'ie' => ['before' => 'ie(', 'after' => ')'],
        ],
    ];

    protected $emptyCondition = '';

    /**
     * {@inheritdoc}
     */
    public static function encodeString($value)
    {
        return strtr(
            rawurlencode($value),
            [
                '-' => '%2D',
                '_' => '%5F',
                '.' => '%2E',
                '~' => '%7E',
                '`' => '%60',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function prepareFieldValue($fieldValue)
    {
        $regexRqlDecoded = parent::prepareFieldValue($fieldValue);

        if (is_null($regexRqlDecoded)) {
            return 'null()';
        }

        if (is_string($regexRqlDecoded)) {
            $constStar = 'starhjc7vjHg6jd8mv8hcy75GFt0c67cnbv74FegxtEDJkcucG64frblmkb';
            $constQuestion = 'questionhjc7vjHg6jd8mv8hcy75GFt0c67cnbv74FegxtEDJkcucG64frblmkb';
            $regexRelEncoded = self::encodeString($regexRqlDecoded);
            $regexRqlPrepared = strtr($regexRelEncoded, [$constStar => '*', $constQuestion => '?']);
            if ($regexRqlPrepared === '' || $regexRqlPrepared === false) {
                $value = "empty";
            } else {
                $value = 'string:' . $regexRqlPrepared;
            }

            return $value;
        }

        return $regexRqlDecoded;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueFromGlob(Glob $globNode)
    {
        $constStar = 'starhjc7vjHg6jd8mv8hcy75GFt0c67cnbv74FegxtEDJkcucG64frblmkb';
        $constQuestion = 'questionhjc7vjHg6jd8mv8hcy75GFt0c67cnbv74FegxtEDJkcucG64frblmkb';

        $glob = parent::getValueFromGlob($globNode);

        $regexRqlPrepared = strtr($glob, ['*' => $constStar, '?' => $constQuestion]);
        $regexRqlDecoded = rawurldecode($regexRqlPrepared);

        return $regexRqlDecoded;
    }
}
