<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\ConditionBuilder;

use rollun\datastore\DataStore\ConditionBuilder\ConditionBuilderAbstract;
use rollun\datastore\DataStore\ConditionBuilder\RqlConditionBuilder;
use rollun\datastore\Rql\Node\AlikeGlobNode;
use rollun\datastore\Rql\Node\BinaryNode\EqfNode;
use rollun\datastore\Rql\Node\BinaryNode\EqnNode;
use rollun\datastore\Rql\Node\BinaryNode\EqtNode;
use rollun\datastore\Rql\Node\BinaryNode\IeNode;
use Graviton\RqlParser\Parser\DataType\Glob;
use Graviton\RqlParser\Parser\Node\Query\LogicOperator\AndNode;
use Graviton\RqlParser\Parser\Node\Query\LogicOperator\NotNode;
use Graviton\RqlParser\Parser\Node\Query\LogicOperator\OrNode;
use Graviton\RqlParser\Parser\Node\Query\ScalarOperator\EqNode;
use Graviton\RqlParser\Parser\Node\Query\ScalarOperator\GeNode;
use Graviton\RqlParser\Parser\Node\Query\ScalarOperator\GtNode;
use Graviton\RqlParser\Parser\Node\Query\ScalarOperator\LeNode;
use Graviton\RqlParser\Parser\Node\Query\ScalarOperator\LikeNode;
use Graviton\RqlParser\Parser\Node\Query\ScalarOperator\LtNode;
use Graviton\RqlParser\Parser\Node\Query\ScalarOperator\NeNode;
use Graviton\RqlParser\Parser\QueryBuilder;

class RqlConditionBuilderTest extends ConditionBuilderTest
{
    protected function createObject(): ConditionBuilderAbstract
    {
        return new RqlConditionBuilder();
    }

    public function providerPrepareFieldName()
    {
        return [
            ['fieldName', 'fieldName'],
            ['FieldName', 'FieldName'],
            ['Field_Name', 'Field_Name'],
        ];
    }

    public function providerGetValueFromGlob()
    {

        return [
            ['abc', 'abc'],
            ['*abc', 'starhjc7vjHg6jd8mv8hcy75GFt0c67cnbv74FegxtEDJkcucG64frblmkbabc'],
            ['abc*', 'abcstarhjc7vjHg6jd8mv8hcy75GFt0c67cnbv74FegxtEDJkcucG64frblmkb'],
            [
                'a*b?c',
                'astarhjc7vjHg6jd8mv8hcy75GFt0c67cnbv74FegxtEDJkcucG64frblmkbbquestionhjc7vjHg6jd8mv8hcy75GFt0c67cnbv74FegxtEDJkcucG64frblmkbc',
            ],
            ['?abc', 'questionhjc7vjHg6jd8mv8hcy75GFt0c67cnbv74FegxtEDJkcucG64frblmkbabc'],
            ['abc?', 'abcquestionhjc7vjHg6jd8mv8hcy75GFt0c67cnbv74FegxtEDJkcucG64frblmkb'],
            [rawurlencode('Шщ +-*._'), 'Шщ +-*._'],
        ];
    }

    public function providerInvoke()
    {
        return [
            [null, ''],
            [
                (new QueryBuilder())->addQuery(new EqNode('name', 'value'))
                    ->getQuery()
                    ->getQuery(),
                'eq(name,string:value)',
            ],
            [
                (new QueryBuilder())->addQuery(new EqNode('a', 1))
                    ->addQuery(new NeNode('b', 2))
                    ->addQuery(new LtNode('c', 3))
                    ->addQuery(new GtNode('d', 4))
                    ->addQuery(new LeNode('e', 5))
                    ->addQuery(new GeNode('f', 6))
                    ->addQuery(new LikeNode('g', new Glob('*abc?')))
                    ->getQuery()
                    ->getQuery(),
                'and(eq(a,1),ne(b,2),lt(c,3),gt(d,4),le(e,5),ge(f,6),like(g,string:*abc?))',
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
                'and(eq(a,string:b),lt(c,string:d),or(lt(g,5),gt(g,2)),not(ne(h,3)))',
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
                'and(eq(a,null()),lt(c,string:d),or(lt(g,5),gt(g,2)),not(ne(h,3)))',
            ],
            [
                (new QueryBuilder())->addQuery(new AndNode([
                    new EqnNode('a'),
                    new EqtNode('b'),
                    new EqfNode('c'),
                    new IeNode('d'),
                    new AlikeGlobNode('a', '*abc?'),
                ]))
                    ->getQuery()
                    ->getQuery(),
                'and(eqn(a),eqt(b),eqf(c),ie(d),alike(a,string:*abc?))',
            ],
        ];
    }
}
