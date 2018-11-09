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
use rollun\datastore\Rql\RqlQuery;
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
        $id = $this->identifierToType(1);

        $item = [
            'id' => $id,
            'name' => 'name',
            'surname' => 'surname',
        ];

        $object->create($item);
        $object->create($item, 1);
    }

    public function testUpdateItemDoesNotExistException()
    {
        $this->expectException(DataStoreException::class);
        $object = $this->createObject();
        $id = $this->identifierToType(1);

        $item = [
            'id' => $id,
            'name' => 'name',
            'surname' => 'surname',
        ];

        $object->update($item);
    }

    public function testUpdateWithCreate()
    {
        $object = $this->createObject();
        $id = $this->identifierToType(1);

        $item = [
            'id' => $id,
            'name' => 'name',
            'surname' => 'surname',
        ];

        $object->update($item, 1);
    }

    public function testDeleteAll()
    {
        $count = 10;
        $object = $this->createObject();
        $range = range(1, $count);

        foreach ($range as $id) {
            $object->create([
                'id' => $this->identifierToType($id),
                'name' => 'name',
                'surname' => 'surname',
            ]);
        }

        $this->assertEquals($count, $object->deleteAll());
    }

    public function testQuerySuccess()
    {
        $object = $this->createObject();

        foreach (range(1,6) as $id) {
            $object->create([
                'id' => $id,
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ]);
        }

        $rqlQuery = new RqlQuery();
        $rqlQuery->setQuery(
            new AndNode([
                new OrNode([
                    new GeNode('id', 3),
                    new LeNode('id', 7),
                ]),
                new NotNode([new EqNode('id', 4)]),
                new GeNode('id', 3),
            ])
        );

        $rqlQuery->setSelect(new SelectNode([
            'id',
            'name',
            'surname',
        ]));

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

    public function testQueryWithAggregationFunctions()
    {
        $rqlQuery = new RqlQuery();
        $rqlQuery->setSelect(new SelectNode([
            new AggregateFunctionNode('count', 'id')
        ]));

        $object = $this->createObject();
        foreach (range(1,3) as $id) {
            $object->create([
                'id' => $id,
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ]);
        }

        $items = $object->query($rqlQuery);
        $this->assertEquals($items, [['count(id)' => 3]]);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('id', $this->createObject()->getIdentifier());
    }
}
