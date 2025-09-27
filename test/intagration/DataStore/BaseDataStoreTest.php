<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\intagration\DataStore;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\DataStoreAbstract;
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
    abstract protected function createObject(): DataStoreAbstract;

    protected function getColumns()
    {
        return [DataStoreAbstract::DEF_ID, 'name', 'surname'];
    }

    protected function identifierToType($id)
    {
        return (string)$id;
    }

    public function testMajorCrud()
    {
        $object = $this->createObject();
        $id = $this->identifierToType(1);

        $item = [
            $object->getIdentifier() => $id,
            'name' => 'name',
            'surname' => 'surname',
        ];

        $object->create($item);

        $this->assertEquals($item, $object->read($id));
        $this->assertEquals($item, $object->delete($id));

        $object->create($item);

        $item = [
            $object->getIdentifier() => $id,
            'name' => 'name1',
            'surname' => 'surname1',
        ];

        $object->update($item);

        $this->assertEquals($item, $object->read($id));
        $this->assertEquals($item, $object->delete($id));
    }

    public function testDataIntegritySuccess()
    {
        $object = $this->createObject();
        $object->queriedDelete(new RqlQuery());
        $range13 = range(1, 3);

        foreach ($range13 as $id) {
            $object->create([
                $object->getIdentifier() => $id,
                'name' => 'name',
                'surname' => 'surname',
            ]);
        }

        $this->assertEquals($object->count(), count($range13));
        $range49 = range(4, 9);

        foreach ($range49 as $id) {
            $object->create([
                $object->getIdentifier() => $id,
                'name' => 'name',
                'surname' => 'surname',
            ]);
        }

        $this->assertEquals($object->count(), count($range13) + count($range49));

        $object->delete(2);
        $object->delete(6);
        $this->assertEquals($object->count(), (count($range13) + count($range49)) - 2);

        $object->queriedUpdate(['name' => 'foo', 'surname' => 'bar'], new RqlQuery('ge(id,0)&limit(100)'));
        $this->assertEquals($object->count(), (count($range13) + count($range49)) - 2);

        $object->create([
            $object->getIdentifier() => 10,
            'name' => 'name',
            'surname' => 'surname',
        ]);
        $this->assertEquals($object->count(), (count($range13) + count($range49)) - 1);

        $object->create([
            $object->getIdentifier() => 2,
            'name' => 'name',
            'surname' => 'surname',
        ]);

        $object->create([
            $object->getIdentifier() => 6,
            'name' => 'name',
            'surname' => 'surname',
        ]);
        $this->assertEquals($object->count(), (count($range13) + count($range49)) + 1);

        $object->queriedDelete(new RqlQuery());
        $this->assertEquals(0, $object->count());
    }

    public function testCreateItemAlreadyExistException()
    {
        $this->expectException(DataStoreException::class);
        $object = $this->createObject();
        $id = $this->identifierToType(1);

        $item = [
            $object->getIdentifier() => $id,
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
            $object->getIdentifier() => $this->identifierToType(1),
            'name' => 'name1',
            'surname' => 'surname1',
        ];

        $item2 = [
            $object->getIdentifier() => $this->identifierToType(1),
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
            $object->getIdentifier() => $this->identifierToType(1),
            'name' => 'name',
            'surname' => 'surname',
        ];

        $object->update($item);
    }

    public function testUpdateWithCreate()
    {
        $object = $this->createObject();

        $item = [
            $object->getIdentifier() => $this->identifierToType(1),
            'name' => 'name',
            'surname' => 'surname',
        ];

        $this->assertEquals($item, $object->update($item, 1));
    }

    public function testQueryCombineWhereClauseSuccess()
    {
        $object = $this->createObject();

        foreach (range(1, 6) as $id) {
            $object->create([
                $object->getIdentifier() => $this->identifierToType($id),
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ]);
        }

        $rqlQuery = new RqlQuery();
        $rqlQuery->setQuery(new AndNode([
            new OrNode([
                new GeNode($object->getIdentifier(), 3),
                new LeNode($object->getIdentifier(), 7),
            ]),
            new NotNode([new EqNode($object->getIdentifier(), 4)]),
            new GeNode($object->getIdentifier(), 3),
        ]));

        $rqlQuery->setSelect(new SelectNode([
            $object->getIdentifier(),
            'name',
            'surname',
        ]));

        $expectedItems = [
            [
                $object->getIdentifier() => 3,
                'name' => "name3",
                'surname' => "surname3",
            ],
            [
                $object->getIdentifier() => 5,
                'name' => "name5",
                'surname' => "surname5",
            ],
            [
                $object->getIdentifier() => 6,
                'name' => "name6",
                'surname' => "surname6",
            ],
        ];

        $items = $object->query($rqlQuery);
        $this->assertEquals($expectedItems, $items);
    }

    public function testQueryWithAggregationFunctionsSuccess()
    {
        $object = $this->createObject();
        $rqlQuery = new RqlQuery();
        $rqlQuery->setSelect(new SelectNode([
            new AggregateFunctionNode('count', $object->getIdentifier()),
            new AggregateFunctionNode('max', $object->getIdentifier()),
            new AggregateFunctionNode('min', $object->getIdentifier()),
            new AggregateFunctionNode('sum', $object->getIdentifier()),
            new AggregateFunctionNode('avg', $object->getIdentifier()),
        ]));

        foreach (range(1, 3) as $id) {
            $object->create([
                $object->getIdentifier() => $this->identifierToType($id),
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ]);
        }

        $items = $object->query($rqlQuery);
        $this->assertEquals($items, [
            [
                'count(id)' => 3,
                'max(id)' => 3,
                'min(id)' => 1,
                'sum(id)' => 6,
                'avg(id)' => 2,
            ],
        ]);
    }

    public function testQueryWithLimitAndOffsetSuccess()
    {
        $rqlQuery = new RqlQuery();
        $rqlQuery->setLimit(new LimitNode(2, 2));

        $object = $this->createObject();
        foreach (range(1, 5) as $id) {
            $object->create([
                $object->getIdentifier() => $this->identifierToType($id),
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ]);
        }

        $items = $object->query($rqlQuery);
        $this->assertEquals($items, [
            [
                $object->getIdentifier() => 3,
                'name' => "name3",
                'surname' => "surname3",
            ],
            [
                $object->getIdentifier() => 4,
                'name' => "name4",
                'surname' => "surname4",
            ],
        ]);
    }

    public function testQueryWithGlobValuesSuccess()
    {
        $rqlQuery = new RqlQuery();
        $rqlQuery->setQuery(new AndNode([
            new LikeGlobNode('surname', '?surname?'),
            new LikeGlobNode('name', 'name*'),
        ]));
        $items = [];

        $object = $this->createObject();

        foreach (range(1, 5) as $id) {
            $item = [
                $object->getIdentifier() => $this->identifierToType($id),
                'name' => "name{$id}{$id}{$id}",
                'surname' => "{$id}surname{$id}",
            ];

            $object->create($item);
            $items[] = $item;
        }

        $this->assertEquals($object->query($rqlQuery), $items);
    }

    public function testMultiCreateSuccess()
    {
        $object = $this->createObject();
        $items = [];

        foreach (range(1, 5) as $id) {
            $items[] = [
                $object->getIdentifier() => $this->identifierToType($id),
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ];
        }

        $object->multiCreate($items);

        foreach ($items as $item) {
            $this->assertEquals($item, $object->read($item[$object->getIdentifier()]));
        }
    }

    public function testMultiUpdateSuccess()
    {
        $object = $this->createObject();
        $items = [];

        foreach (range(1, 5) as $id) {
            $item = [
                $object->getIdentifier() => $this->identifierToType($id),
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ];

            $object->create($item);
            $items[] = $item;
        }

        foreach ($items as &$item) {
            if ($item[$object->getIdentifier()] == 1) {
                continue;
            }

            $item['name'] = 'name' . ($item[$object->getIdentifier()] + 100);
        }

        $object->multiUpdate($items);

        foreach ($items as $item) {
            if ($item[$object->getIdentifier()] == 1) {
                $item['name'] = 'name' . $item[$object->getIdentifier()];
            } else {
                $item['name'] = 'name' . ($item[$object->getIdentifier()] + 100);
            }

            $this->assertEquals($item, $object->read($item[$object->getIdentifier()]));
        }
    }

    public function testQueriedUpdateSuccess()
    {
        $object = $this->createObject();
        $items = [];

        foreach (range(1, 5) as $id) {
            $item = [
                $object->getIdentifier() => $this->identifierToType($id),
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ];

            $object->create($item);
            $items[$id] = $item;
        }

        $query = new RqlQuery('or(eq(id,1),eq(id,3))&limit(2)');
        $object->queriedUpdate([
            'surname' => "foo",
        ], $query);

        foreach ([1, 3] as $id) {
            $this->assertEquals($object->read($items[$id][$object->getIdentifier()]), [
                $object->getIdentifier() => $this->identifierToType($id),
                'name' => "name{$id}",
                'surname' => "foo",
            ]);
        }
    }

    public function testQueriedDeleteSuccess()
    {
        $object = $this->createObject();
        $items = [];

        foreach (range(1, 5) as $id) {
            $item = [
                $object->getIdentifier() => $this->identifierToType($id),
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ];

            $object->create($item);
            $items[$id] = $item;
        }

        $query = new RqlQuery('or(eq(id,1),eq(id,3))');
        $object->queriedDelete($query);
        $this->assertEquals(3, $object->count());

        $object->queriedDelete(new RqlQuery());
        $this->assertEquals(0, $object->count());
    }

    public function testRewriteSuccess()
    {
        $object = $this->createObject();
        $item = [
            $object->getIdentifier() => $this->identifierToType(1),
            'name' => "name1",
            'surname' => "surname1",
        ];

        $object->rewrite($item);
        $this->assertEquals($item, $object->read(1));

        $item = [
            $object->getIdentifier() => $this->identifierToType(1),
            'name' => "name2",
            'surname' => "surname2",
        ];
        $object->rewrite($item);
        $this->assertEquals($item, $object->read(1));
    }

    public function testMultiRewriteSuccess()
    {
        $object = $this->createObject();
        $items = [];
        $range = range(1, 5);

        foreach ($range as $id) {
            $items[$id] = [
                $object->getIdentifier() => $this->identifierToType($id),
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ];
        }

        $object->multiRewrite($items);

        foreach ($items as $item) {
            $this->assertEquals($item, $object->read($item[$object->getIdentifier()]));
        }

        $items = [];

        foreach ($range as $id) {
            $items[$id] = [
                $object->getIdentifier() => $this->identifierToType($id),
                'name' => "foo{$id}",
                'surname' => "bar{$id}",
            ];
        }

        $object->multiRewrite($items);

        foreach ($items as $item) {
            $this->assertEquals($item, $object->read($item[$object->getIdentifier()]));
        }
    }

    public function testQueryWithEmptySuccess()
    {
        $this->assertEquals($this->createObject()
            ->query(new RqlQuery()), []);
    }

    public function testGetDefaultIdentifierSuccess()
    {
        $this->assertEquals(DataStoreAbstract::DEF_ID, $this->createObject()
            ->getIdentifier());
    }

    public function testHasSuccess()
    {
        $object = $this->createObject();
        $object->create([
            $object->getIdentifier() => 1,
            'name' => "name1",
            'surname' => "surname1",
        ]);

        $this->assertTrue($object->has(1));
        $this->assertFalse($object->has(2));
    }

    public function testDeleteSuccess()
    {
        $this->assertEquals(null, $this->createObject()
            ->delete(1));
    }

    public function testCountSuccess()
    {
        $object = $this->createObject();
        $count = 5;

        foreach (range(1, 5) as $id) {
            $object->create([
                $object->getIdentifier() => $this->identifierToType($id),
                'name' => "name{$id}{$id}{$id}",
                'surname' => "{$id}surname{$id}",
            ]);
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
                $object->getIdentifier() => $this->identifierToType($id),
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

    public function testGetNextSuccess()
    {
        $object = $this->createObject();
        $reflectionMethod = new \ReflectionMethod($object, 'getNext');
        $reflectionMethod->setAccessible(true);
        $items = [];

        foreach (range(1, 10) as $id) {
            $items[] = [
                $object->getIdentifier() => $this->identifierToType($id),
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ];
        }

        $object->multiCreate($items);

        $record = current($items);
        $assertItems[] = $record;

        while (!is_null($record = $reflectionMethod->invoke($object, $record[$object->getIdentifier()]))) {
            $assertItems[] = $record;
        }

        $this->assertEquals($items, $assertItems);

        $record[$object->getIdentifier()] = null;
        $assertItems = [];

        while (!is_null($record = $reflectionMethod->invoke($object, $record[$object->getIdentifier()]))) {
            $assertItems[] = $record;
        }

        $this->assertEquals($items, $assertItems);
        $this->assertEquals(null, $reflectionMethod->invoke($object, 10));
    }
}
