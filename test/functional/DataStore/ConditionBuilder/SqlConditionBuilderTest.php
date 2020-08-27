<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\ConditionBuilder;

use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilder;
use rollun\datastore\DataStore\ConditionBuilder\ConditionBuilderAbstract;
use rollun\datastore\Rql\Node\AlikeGlobNode;
use rollun\datastore\Rql\Node\BinaryNode\EqfNode;
use rollun\datastore\Rql\Node\BinaryNode\EqnNode;
use rollun\datastore\Rql\Node\BinaryNode\EqtNode;
use rollun\datastore\Rql\Node\BinaryNode\IeNode;
use Xiag\Rql\Parser\DataType\Glob;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\NotNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\OrNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LikeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\NeNode;
use Xiag\Rql\Parser\QueryBuilder;

class SqlConditionBuilderTest extends ConditionBuilderTest
{
    protected function createObject(): ConditionBuilderAbstract
    {
        $tableName = 'tableName';
        $container = require 'config/container.php';

        return new SqlConditionBuilder($container->get('db'), $tableName);
    }

    public function providerPrepareFieldName()
    {
        return [
            ['fieldName', '`fieldName`'],
            ['FieldName', '`FieldName`'],
            ['Field_Name', '`Field_Name`'],
        ];
    }

    public function providerGetValueFromGlob()
    {
        return [
            ['abc', 'abc'],
            ['*abc', '%abc'],
            ['abc*', 'abc%'],
            ['a*b?c', 'a%b_c'],
            ['?abc', '_abc'],
            ['abc?', 'abc_'],
            [rawurlencode('Шщ +-*._~'), 'Шщ +-*._~'],
        ];
    }

    public function providerInvoke()
    {
        return [
            [null, "'1' = '1'"],
            [
                (new QueryBuilder())->addQuery(new EqNode('name', 'value'))
                    ->getQuery()
                    ->getQuery(),
                '(`name`=\'value\')',
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
                "((`a`='1') AND (`b`<>'2') AND (`c`<'3') AND (`d`>'4') AND (`e`<='5') AND (`f`>='6') AND (`g` LIKE BINARY '%abc_'))",
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
                "((`a`='b') AND (`c`<'d') AND ((`g`<'5') OR (`g`>'2')) AND ( NOT ((`h`<>'3')) ))",
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
                "((`a`='') AND (`c`<'d') AND ((`g`<'5') OR (`g`>'2')) AND ( NOT ((`h`<>'3')) ))",
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
                "((`a` IS NULL) AND (`b` IS TRUE) AND (`c` IS FALSE) AND (`d` IS NULL OR `d` IS FALSE OR `d` = '') AND (`a` LIKE '%abc_'))",
            ],
        ];
    }
}
