<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\test\datastore\DataStore\ConditionBuilder;

use Xiag\Rql\Parser\DataType\Glob;
use Xiag\Rql\Parser\Node;
use Xiag\Rql\Parser\QueryBuilder;
use rollun\datastore\DataStore\ConditionBuilder\PhpConditionBuilder;

class PhpConditionBuilderTest extends ConditionBuilderTest
{

    public function providerPrepareFieldName()
    {
        return array(
            array('fieldName', '$item[\'fieldName\']'),
            array('FieldName', '$item[\'FieldName\']'),
            array('Field_Name', '$item[\'Field_Name\']'),
        );
    }

    public function providerGetValueFromGlob()
    {
        return array(
            array('abc', '^abc$'),
            array('*abc', 'abc$'),
            array('abc*', '^abc'),
            array('a*b?c', '^a.*b.c$'),
            array('?abc', '^.abc$'),
            array('abc?', '^abc.$'),
            array(rawurlencode('Шщ +-*._'), '^Шщ \+\-\*\._$'),
        );
    }

    public function provider__invoke()
    {
        return array(
            array(null, ' true '),
            array(
                (new QueryBuilder())
                    ->addQuery(new Node\Query\ScalarOperator\EqNode('name', 'value'))
                    ->getQuery()->getQuery(),
                '($item[\'name\']==\'value\')'
            ),
            array(
                (new QueryBuilder())
                    ->addQuery(new Node\Query\ScalarOperator\EqNode('a', 1))
                    ->addQuery(new Node\Query\ScalarOperator\NeNode('b', 2))
                    ->addQuery(new Node\Query\ScalarOperator\LtNode('c', 3))
                    ->addQuery(new Node\Query\ScalarOperator\GtNode('d', 4))
                    ->addQuery(new Node\Query\ScalarOperator\LeNode('e', 5))
                    ->addQuery(new Node\Query\ScalarOperator\GeNode('f', 6))
                    ->addQuery(new Node\Query\ScalarOperator\LikeNode('g', new Glob('*abc?')))
                    ->getQuery()->getQuery(),
                '(($item[\'a\']==1) && ($item[\'b\']!=2) && ($item[\'c\']<3) && ($item[\'d\']>4) && ($item[\'e\']<=5) && ($item[\'f\']>=6) && ( ($_field = $item[\'g\']) !==\'\' && preg_match(\'/\' . trim(\'abc.$\',"\'"). \'/i\', $_field) ))'
            ),
            array(
                (new QueryBuilder())
                    ->addQuery(new Node\Query\LogicOperator\AndNode([
                        new Node\Query\ScalarOperator\EqNode('a', 'b'),
                        new Node\Query\ScalarOperator\LtNode('c', 'd'),
                        new Node\Query\LogicOperator\OrNode([
                            new Node\Query\ScalarOperator\LtNode('g', 5),
                            new Node\Query\ScalarOperator\GtNode('g', 2),
                        ])
                    ]))
                    ->addQuery(new Node\Query\LogicOperator\NotNode([
                        new Node\Query\ScalarOperator\NeNode('h', 3),
                    ]))
                    ->getQuery()->getQuery(),
                '(($item[\'a\']==\'b\') && ($item[\'c\']<\'d\') && (($item[\'g\']<5) || ($item[\'g\']>2)) && ( !(($item[\'h\']!=3)) ))'
            ),
            array(
                (new QueryBuilder())
                    ->addQuery(new Node\Query\LogicOperator\AndNode([
                        new Node\Query\ScalarOperator\EqNode('a', null),
                        new Node\Query\ScalarOperator\LtNode('c', 'd'),
                        new Node\Query\LogicOperator\OrNode([
                            new Node\Query\ScalarOperator\LtNode('g', 5),
                            new Node\Query\ScalarOperator\GtNode('g', 2),
                        ])
                    ]))
                    ->addQuery(new Node\Query\LogicOperator\NotNode([
                        new Node\Query\ScalarOperator\NeNode('h', 3),
                    ]))
                    ->getQuery()->getQuery(),
                '(($item[\'a\']==null) && ($item[\'c\']<\'d\') && (($item[\'g\']<5) || ($item[\'g\']>2)) && ( !(($item[\'h\']!=3)) ))'
            ),
        );
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->object = new PhpConditionBuilder();
    }

}
