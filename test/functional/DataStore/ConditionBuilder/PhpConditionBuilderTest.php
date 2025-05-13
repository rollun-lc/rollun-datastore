<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\ConditionBuilder;

use rollun\datastore\DataStore\ConditionBuilder\ConditionBuilderAbstract;
use rollun\datastore\DataStore\ConditionBuilder\PhpConditionBuilder;
use rollun\datastore\Rql\Node\AlikeGlobNode;
use rollun\datastore\Rql\Node\BinaryNode\EqfNode;
use rollun\datastore\Rql\Node\BinaryNode\EqnNode;
use rollun\datastore\Rql\Node\BinaryNode\EqtNode;
use rollun\datastore\Rql\Node\BinaryNode\IeNode;
use rollun\datastore\Rql\Node\LikeGlobNode;
use Xiag\Rql\Parser\DataType\Glob;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\NotNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\OrNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\NeNode;
use Xiag\Rql\Parser\QueryBuilder;

class PhpConditionBuilderTest extends ConditionBuilderTest
{
    protected function createObject(): ConditionBuilderAbstract
    {
        return new PhpConditionBuilder();
    }

    public function providerPrepareFieldName()
    {
        return [
            ['fieldName', '$item[\'fieldName\']'],
            ['FieldName', '$item[\'FieldName\']'],
            ['Field_Name', '$item[\'Field_Name\']'],
        ];
    }

    public function providerGetValueFromGlob()
    {
        return [
            ['abc', '/^abc$/'],
            ['*abc', '/abc$/'],
            ['abc*', '/^abc/'],
            ['a*b?c', '/^a.*b.c$/'],
            ['?abc', '/^.abc$/'],
            ['abc?', '/^abc.$/'],
            [rawurlencode('Шщ +-*._'), '/^Шщ \+\-\*\._$/'],
        ];
    }

    public function providerInvoke()
    {
        return [
            [null, ' true '],
            [
                (new QueryBuilder())->addQuery(new EqNode('name', 'value'))
                    ->getQuery()
                    ->getQuery(),
                '($item[\'name\']==\'value\')',
            ],
            [
                (new QueryBuilder())->addQuery(new EqNode('a', 1))
                    ->addQuery(new NeNode('b', 2))
                    ->addQuery(new LtNode('c', 3))
                    ->addQuery(new GtNode('d', 4))
                    ->addQuery(new LeNode('e', 5))
                    ->addQuery(new GeNode('f', 6))
                    ->addQuery(new LikeGlobNode('g', new Glob('*abc?')))
                    ->addQuery(new AlikeGlobNode('k', new Glob('*dc?')))
                    ->getQuery()
                    ->getQuery(),
                '(($item[\'a\']==1) && ($item[\'b\']!=2) && ($item[\'c\']<3) && ($item[\'d\']>4) && ($item[\'e\']<=5) && '
                . '($item[\'f\']>=6) && ( ($_field = $item[\'g\']) !==\'\' && preg_match(\'/abc.$/\', $_field) )'
                . ' && ( ($_field = $item[\'k\']) !==\'\' && preg_match(\'/dc.$/\'. \'i\', $_field) ))',
            ],
            [
                (new QueryBuilder())->addQuery(new AndNode([
                    new EqNode('a', 'b'),
                    new LtNode('c', 'd'),
                    new OrNode([
                        new LtNode('g', 5),
                        new GtNode('g', 2),
                    ]),
                ]))
                    ->addQuery(new NotNode([
                        new NeNode('h', 3),
                    ]))
                    ->getQuery()
                    ->getQuery(),
                '(($item[\'a\']==\'b\') && ($item[\'c\']<\'d\') && (($item[\'g\']<5) || ($item[\'g\']>2)) && ( !(($item[\'h\']!=3)) ))',
            ],
            [
                (new QueryBuilder())->addQuery(new AndNode([
                    new EqNode('a', null),
                    new LtNode('c', 'd'),
                    new OrNode([
                        new LtNode('g', 5),
                        new GtNode('g', 2),
                    ]),
                ]))
                    ->addQuery(new NotNode([
                        new NeNode('h', 3),
                    ]))
                    ->getQuery()
                    ->getQuery(),
                '(($item[\'a\']==null) && ($item[\'c\']<\'d\') && (($item[\'g\']<5) || ($item[\'g\']>2)) && ( !(($item[\'h\']!=3)) ))',
            ],
            [
                (new QueryBuilder())->addQuery(new AndNode([
                    new EqnNode('a'),
                    new EqtNode('b'),
                    new EqfNode('c'),
                    new IeNode('d'),
                ]))
                    ->getQuery()
                    ->getQuery(),
                '(is_null($item[\'a\']) && ($item[\'b\']==true) && ($item[\'c\']==false) && empty($item[\'d\']))',
            ],
            [
                (new QueryBuilder())->addQuery(new AlikeGlobNode('a', '*abc?'))
                    ->getQuery()
                    ->getQuery(),
                '( ($_field = $item[\'a\']) !==\'\' && preg_match(\'/abc.$/\'. \'i\', $_field) )',
            ],
        ];
    }
}
