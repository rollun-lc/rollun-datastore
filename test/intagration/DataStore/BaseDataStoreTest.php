<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\intagration\DataStore;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_Error_Deprecated;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\Node\LikeGlobNode;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\NotNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\OrNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LeNode;
use Xiag\Rql\Parser\Node\SelectNode;

abstract class BaseDataStoreTest extends TestCase
{
    protected $columns = ['id', 'name', 'surname'];

    protected function setUp()
    {
        PHPUnit_Framework_Error_Deprecated::$enabled = false;
    }

    abstract protected function createObject(): DataStoresInterface;

    protected function identifierToType($id)
    {
        return (string)$id;
    }

    public function testMajorCRUD()
    {
        $object = $this->createObject();
        $id = $this->identifierToType(1);

        $item = [
            'id' => $id,
            'name' => 'name',
            'surname' => 'surname',
        ];

        $object->create($item);

        $this->assertEquals($item, $object->read($id));
        $this->assertEquals($item, $object->delete($id));

        $object->create($item);

        $item = [
            'id' => $id,
            'name' => 'name1',
            'surname' => 'surname1',
        ];

        $object->update($item);

        $this->assertEquals($item, $object->read($id));
        $this->assertEquals($item, $object->delete($id));
    }

    public function testCreateItemAlreadyExistException()
    {
        $this->expectException(DataStoreException::class);
        $object = $this->createObject();
        $id = $this->identifierToType(1);

        $item = [
            'id' => $id,
            'name' => 'name',
            'surname' => 'surname',
        ];

        $object->create($item);
        $object->create($item);
    }

    public function testCreateWithRewrite()
    {
        $object = $this->createObject();

        $item1 = [
            'id' => $this->identifierToType(1),
            'name' => 'name1',
            'surname' => 'surname1',
        ];

        $item2 = [
            'id' => $this->identifierToType(1),
            'name' => 'name2',
            'surname' => 'surname2',
        ];

        $object->create($item1);
        $object->create($item2, 1);
        $this->assertEquals($item2, $object->read(1));
    }

    public function testUpdateItemDoesNotExistException()
    {
        $this->expectException(DataStoreException::class);
        $object = $this->createObject();

        $item = [
            'id' => $this->identifierToType(1),
            'name' => 'name',
            'surname' => 'surname',
        ];

        $object->update($item);
    }

    public function testUpdateWithCreate()
    {
        $object = $this->createObject();

        $item = [
            'id' => $this->identifierToType(1),
            'name' => 'name',
            'surname' => 'surname',
        ];

        $object->update($item, 1);
    }

    public function testQueryCombineWhereClauseSuccess()
    {
        $object = $this->createObject();

        foreach (range(1, 6) as $id) {
            $object->create(
                [
                    'id' => $this->identifierToType($id),
                    'name' => "name{$id}",
                    'surname' => "surname{$id}",
                ]
            );
        }

        $rqlQuery = new RqlQuery();
        $rqlQuery->setQuery(
            new AndNode(
                [
                    new OrNode(
                        [
                            new GeNode('id', 3),
                            new LeNode('id', 7),
                        ]
                    ),
                    new NotNode([new EqNode('id', 4)]),
                    new GeNode('id', 3),
                ]
            )
        );

        $rqlQuery->setSelect(
            new SelectNode(
                [
                    'id',
                    'name',
                    'surname',
                ]
            )
        );

        $expectedItems = [
            [
                'id' => 3,
                'name' => "name3",
                'surname' => "surname3",
            ],
            [
                'id' => 5,
                'name' => "name5",
                'surname' => "surname5",
            ],
            [
                'id' => 6,
                'name' => "name6",
                'surname' => "surname6",
            ],
        ];

        $items = $object->query($rqlQuery);
        $this->assertEquals($expectedItems, $items);
    }

    public function testQueryWithAggregationFunctionsSuccess()
    {
        $rqlQuery = new RqlQuery();
        $rqlQuery->setSelect(
            new SelectNode(
                [
                    new AggregateFunctionNode('count', 'id'),
                    new AggregateFunctionNode('max', 'id'),
                    new AggregateFunctionNode('min', 'id'),
                    new AggregateFunctionNode('sum', 'id'),
                    new AggregateFunctionNode('avg', 'id'),
                ]
            )
        );

        $object = $this->createObject();
        foreach (range(1, 3) as $id) {
            $object->create(
                [
                    'id' => $this->identifierToType($id),
                    'name' => "name{$id}",
                    'surname' => "surname{$id}",
                ]
            );
        }

        $items = $object->query($rqlQuery);
        $this->assertEquals(
            $items,
            [
                [
                    'count(id)' => 3,
                    'max(id)' => 3,
                    'min(id)' => 1,
                    'sum(id)' => 6,
                    'avg(id)' => 2,
                ],
            ]
        );
    }

    public function testQueryWithLimitAndOffsetSuccess()
    {
        $rqlQuery = new RqlQuery();
        $rqlQuery->setLimit(new LimitNode(2, 2));

        $object = $this->createObject();
        foreach (range(1, 5) as $id) {
            $object->create(
                [
                    'id' => $this->identifierToType($id),
                    'name' => "name{$id}",
                    'surname' => "surname{$id}",
                ]
            );
        }

        $items = $object->query($rqlQuery);
        $this->assertEquals(
            $items,
            [
                [
                    'id' => 3,
                    'name' => "name3",
                    'surname' => "surname3",
                ],
                [
                    'id' => 4,
                    'name' => "name4",
                    'surname' => "surname4",
                ],
            ]
        );
    }

    public function testQueryWithGlobValuesSuccess()
    {
        $rqlQuery = new RqlQuery();
        $rqlQuery->setQuery(
            new AndNode(
                [
                    new LikeGlobNode('surname', '?surname?'),
                    new LikeGlobNode('name', 'name*'),
                ]
            )
        );
        $items = [];

        $object = $this->createObject();

        foreach (range(1, 5) as $id) {
            $item = [
                'id' => $this->identifierToType($id),
                'name' => "name{$id}{$id}{$id}",
                'surname' => "{$id}surname{$id}",
            ];

            $object->create($item);
            $items[] = $item;
        }

        $this->assertEquals($object->query($rqlQuery), $items);
    }

    public function testQueryWithEmptySuccess()
    {
        $this->assertEquals(
            $this->createObject()
                ->query(new RqlQuery()),
            []
        );
    }

    public function testGetIdentifierSuccess()
    {
        $this->assertEquals(
            'id',
            $this->createObject()
                ->getIdentifier()
        );
    }

    public function testHasSuccess()
    {
        $object = $this->createObject();
        $object->create(
            [
                'id' => 1,
                'name' => "name1",
                'surname' => "surname1",
            ]
        );

        $this->assertTrue($object->has(1));
        $this->assertFalse($object->has(2));
    }

    public function testDeleteSuccess()
    {
        $this->assertEquals(null,
            $this->createObject()
                ->delete(1)
        );
    }

    public function testCountSuccess()
    {
        $object = $this->createObject();
        $count = 5;

        foreach (range(1, 5) as $id) {
            $object->create(
                [
                    'id' => $this->identifierToType($id),
                    'name' => "name{$id}{$id}{$id}",
                    'surname' => "{$id}surname{$id}",
                ]
            );
        }

        $this->assertTrue($object instanceof \Countable);
        $this->assertEquals($object->count(), $count);
        $object->deleteAll();
        $this->assertEquals($object->count(), 0);
    }

    public function testIterableSuccess()
    {
        $object = $this->createObject();
        $items = [];

        foreach (range(1, 5) as $id) {
            $item = [
                'id' => $this->identifierToType($id),
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ];

            $object->create($item);
            $items[$id] = $item;
        }

        foreach ($object as $item) {
            $id = $item[$object->getIdentifier()];
            $this->assertEquals($object->read($id), $item);
        }
    }
}
