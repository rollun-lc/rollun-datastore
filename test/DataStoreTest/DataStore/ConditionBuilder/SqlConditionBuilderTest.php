<?php

namespace rollun\test\datastore\DataStore\ConditionBuilder;

use Psr\Container\ContainerInterface;
use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilder;
use PHPUnit\Framework\TestCase;
use Xiag\Rql\Parser\DataType\Glob;
use Xiag\Rql\Parser\Node;
use Xiag\Rql\Parser\QueryBuilder;
use Zend\Db\Adapter\AdapterInterface;

/**
 * TODO: extended from condition builder
 * Class SqlConditionBuilderTest
 * @package rollun\test\datastore\DataStore\ConditionBuilder
 */
class SqlConditionBuilderTest extends ConditionBuilderTest
{
    /**
     * @var SqlConditionBuilder
     */
    protected $object;
    /** @var ContainerInterface */
    private $container;
    /** @var AdapterInterface */
    private $adapter;

    public function setUp()
    {
        parent::setUp();
        $this->container = require "config/container.php";
        $this->adapter = $this->container->get(AdapterInterface::class);
        $this->object = new SqlConditionBuilder($this->adapter, "undefined_test_table");
    }


    public function providerPrepareFieldName()
    {
        return [
            ["name", "`name`"],
            ["name_surname", "`name_surname`"],
            ["NameName", "`NameName`"],
        ];
    }

    public function providerGetValueFromGlob()
    {
        return [
            ["*asd*", "%asd%",],
            ["?asd?", "_asd_",],
            ["%asd*", "\%asd%",],
            ["_a%d_", "\_a\%d\_",],
            ["?>a*d<_", "_>a%d<\_",],
        ];
    }

    public function provider__invoke()
    {
        return [
            [null, '1 = 1'],
            [
                (new QueryBuilder())
                    ->addQuery(new Node\Query\ScalarOperator\EqNode('name', 'value'))
                    ->getQuery()->getQuery(),
                '(`name`=\'value\')'
            ],
            [
                (new QueryBuilder())
                    ->addQuery(new Node\Query\ScalarOperator\EqNode('a', 1))
                    ->addQuery(new Node\Query\ScalarOperator\NeNode('b', 2))
                    ->addQuery(new Node\Query\ScalarOperator\LtNode('c', 3))
                    ->addQuery(new Node\Query\ScalarOperator\GtNode('d', 4))
                    ->addQuery(new Node\Query\ScalarOperator\LeNode('e', 5))
                    ->addQuery(new Node\Query\ScalarOperator\GeNode('f', 6))
                    ->addQuery(new Node\Query\ScalarOperator\LikeNode('g', new Glob('*abc?')))
                    ->getQuery()->getQuery(),
                '((`a`=1) AND (`b`<>2) AND (`c`<3) AND (`d`>4) AND (`e`<=5) AND (`f`>=6) AND (`g` LIKE \'%abc_\'))'
            ],
            [
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
                '((`a`=\'b\') AND (`c`<\'d\') AND ((`g`<5) OR (`g`>2)) AND ( NOT ((`h`<>3)) ))'
            ],
            [
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
                '((`a`IS NULL) AND (`c`<\'d\') AND ((`g`<5) OR (`g`>2)) AND ( NOT ((`h`<>3)) ))'
            ],
        ];
    }
}
