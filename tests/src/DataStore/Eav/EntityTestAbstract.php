<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 26.10.16
 * Time: 2:16 PM
 */

namespace rollun\test\datastore\DataStore\Eav;

use Interop\Container\ContainerInterface;
use Xiag\Rql\Parser\DataType\DateTime;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Eav\Entity;
use rollun\datastore\DataStore\Eav\Example\StoreCatalog;
use rollun\datastore\DataStore\Eav\Prop;
use rollun\datastore\DataStore\Eav\SysEntities;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use Zend\Db\TableGateway\TableGateway;

abstract class EntityTestAbstract extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Entity
     */
    protected $object;

    /** @var  ContainerInterface */
    protected $container;

    protected function setUp()
    {
        $this->container = include 'config/container.php';
        $sysEntities = $this->container->get(SysEntities::TABLE_NAME);
        $sysEntities->deleteAll();
    }

    abstract protected function __init();

    public function test__create_Product()
    {
        $this->__init();
        $this->object->create(['title' => 'title_1', 'price' => 100]);
        $this->assertEquals(1, $this->object->count());
    }

    public function test__create_Product_PropLinkedUrl()
    {
        $this->__init();
        $this->object->create([
            'title' => 'title_1',
            'price' => 100,
            StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                ['url' => 'http://google.com', 'alt' => 'Pot1'],
                ['url' => 'http://google.com1', 'alt' => 'Pot2'],
            ]
        ]);
        $prop = $this->container->get(StoreCatalog::PROP_LINKED_URL_TABLE_NAME);
        $this->assertEquals(2, $prop->count());

        $propData = $prop->query(new Query);
        $this->assertArrayHasKey('id', $propData[0]);

        $sysEntities = $this->container->get(SysEntities::TABLE_NAME);
        $this->assertNotEmpty($sysEntities->read($propData[0]['sys_entities_id']));

        unset($propData[0]['id']);
        unset($propData[0]['sys_entities_id']);
        $this->assertEquals(
            ['url' => 'http://google.com', 'alt' => 'Pot1'], $propData[0]
        );
    }

    public function test__create_Product_if_exist()
    {
        $this->__init();
        $data = $this->object->create(['title' => 'title_1', 'price' => 100]);

        $data['price'] = 250;
        $this->object->create($data, true);
        $this->assertEquals(1, $this->object->count());

        $query = new Query();
        $query->setQuery(new EqNode($this->object->getIdentifier(), $data[$this->object->getIdentifier()]));

        $result = $this->object->query($query);
        $this->assertEquals(250, $result[0]['price']);
    }

    public function test__query_Product_PropLinkedUrl()
    {
        $this->__init();
        $this->object->create([
            'title' => 'title_1',
            'price' => 100,
            StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                ['url' => 'http://google.com', 'alt' => 'Pot1'],
                ['url' => 'http://google.com1', 'alt' => 'Pot2'],
            ]
        ]);
        $query = new Query;
        $query->setSelect(new SelectNode([StoreCatalog::PROP_LINKED_URL_TABLE_NAME]));
        $entityData = $this->object->query($query);
        $propData = $entityData[0]['prop_linked_url'];
        unset($propData[0]['id']);
        unset($propData[0]['sys_entities_id']);
        $this->assertEquals(
            ['url' => 'http://google.com', 'alt' => 'Pot1'], $propData[0]
        );
    }

    public function test__query()
    {
        $this->__init();
        for ($i = 1; $i < 10; $i++) {
            $data = [
                'title' => 'title_' . $i,
                'price' => 100 * $i
            ];

            $data['id'] = $this->object->create($data)['id'];
            $this->assertEquals($i, $this->object->count());

            $query = new Query();

            $query->setQuery(new EqNode('title', $data['title']));

            $result = $this->object->query($query);

            $unset = array_diff(array_keys($result[0]), array_keys($data));

            foreach ($unset as $key) {
                unset($result[0][$key]);
            }

            $this->assertEquals(true, is_array($result) && isset($result[0]));
            $this->assertEquals(1, count($result));
            $this->assertEquals($data, $result[0]);
        }
    }

    public function test__update()
    {
        $this->__init();
        $data = $this->object->create(['title' => 'title_1', 'price' => 100]);

        $data['price'] = 250;
        $this->object->update($data);
        $this->assertEquals(1, $this->object->count());

        $query = new Query();
        $query->setQuery(new EqNode($this->object->getIdentifier(), $data[$this->object->getIdentifier()]));

        $result = $this->object->query($query);
        $this->assertEquals(250, $result[0]['price']);
    }

    public function test__update_with_Props_new()
    {
        $this->__init();
        $data = $this->object->create([
            'title' => 'title_1',
            'price' => 100,
            StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                ['url' => 'http://google.com', 'alt' => 'Pot1'],
                ['url' => 'http://google.com1', 'alt' => 'Pot2'],
            ]
        ]);

        $data[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][] = ['url' => 'http://google.com', 'alt' => 'Pot3'];

        $newData = $this->object->update($data);
        $this->assertEquals(1, $this->object->count());

        $prop = $this->container->get(StoreCatalog::PROP_LINKED_URL_TABLE_NAME);
        $this->assertEquals(3, $prop->count());

        $this->assertEquals('Pot3', $newData[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][2]['alt']);

    }

    public function test__update_with_Props_update()
    {
        $this->__init();
        $data = $this->object->create([
            'title' => 'title_1',
            'price' => 100,
            StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                ['url' => 'http://google.com', 'alt' => 'Pot1'],
                ['url' => 'http://google.com1', 'alt' => 'Pot2'],
            ]
        ]);
        $data[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][0]['alt'] = 'Pot3';

        $newData = $this->object->update($data);
        $this->assertEquals(1, $this->object->count());

        $prop = $this->container->get(StoreCatalog::PROP_LINKED_URL_TABLE_NAME);
        $this->assertEquals(2, $prop->count());
        $this->assertEquals('Pot3', $newData[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][0]['alt']);
    }

    public function test__update_with_Props_deleted()
    {
        $this->__init();
        $data = $this->object->create([
            'title' => 'title_1',
            'price' => 100,
            StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                ['url' => 'http://google.com', 'alt' => 'Pot1'],
                ['url' => 'http://google.com1', 'alt' => 'Pot2'],
                ['url' => 'http://google.com2', 'alt' => 'Pot3'],
            ]
        ]);
        unset($data[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][2]);

        $newData = $this->object->update($data);
        $this->assertEquals(1, $this->object->count());

        $prop = $this->container->get(StoreCatalog::PROP_LINKED_URL_TABLE_NAME);
        $this->assertEquals(2, $prop->count());
        $this->assertEquals(false, isset($newData[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][2]));
    }

    public function test__update_with_Props_combo()
    {
        $this->__init();
        $data = $this->object->create([
            'title' => 'title_1',
            'price' => 100,
            StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                ['url' => 'http://google.com', 'alt' => 'Pot1'],
                ['url' => 'http://google.com1', 'alt' => 'Pot2'],
                ['url' => 'http://google.com2', 'alt' => 'Pot3'],
            ]
        ]);
        unset($data[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][2]);
        $data[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][] = ['url' => 'http://google.com', 'alt' => 'Pot3'];
        $data[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][0]['alt'] = 'Pot4';
        unset($data[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][1]['alt']);
        unset($data[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][1]['url']);
        unset($data[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][1]['sys_entities_id']);

        $newData = $this->object->update($data);
        $this->assertEquals(1, $this->object->count());

        $prop = $this->container->get(StoreCatalog::PROP_LINKED_URL_TABLE_NAME);
        $this->assertEquals(3, $prop->count());
        $this->assertEquals('Pot4', $newData[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][0]['alt']);
        $this->assertEquals('Pot3', $newData[StoreCatalog::PROP_LINKED_URL_TABLE_NAME][2]['alt']);
    }

    public function test__query_with_select()
    {
        $this->__init();
        for ($i = 1; $i < 10; $i++) {
            $data = [
                'title' => 'title_' . $i,
                'price' => 100 * $i
            ];

            $this->object->create($data);
            $this->assertEquals($i, $this->object->count());

            $query = new Query();

            $query->setQuery(new EqNode('title', $data['title']));
            $query->setSelect(new SelectNode(['price']));

            $result = $this->object->query($query);
            $unset = array_diff(array_keys($result[0]), array_keys($data));

            foreach ($unset as $key) {
                unset($result[0][$key]);
            }

            $this->assertEquals(1, count($result[0]));
            $this->assertEquals($data['price'], $result[0]['price']);
        }
    }

    public function test__query_with_select_aggregate()
    {
        $this->__init();
        for ($i = 1; $i < 10; $i++) {
            $data = [
                'title' => 'title_' . $i,
                'price' => 100 * $i
            ];

            $this->object->create($data);
        }
        $query = new Query();

        $query->setSelect(new SelectNode([new AggregateFunctionNode('max', 'price')]));

        $result = $this->object->query($query);
        $this->assertEquals(900, $result[0]['price->max']);
    }

    public function test__query_with_select_sys_entities_aggregate()
    {
        $this->__init();
        $time = (new DateTime())->format("Y-m-d") . " 00:00:00";
        for ($i = 1; $i < 10; $i++) {
            $data = [
                'title' => 'title_' . $i,
                'price' => 100 * $i
            ];

            $this->object->create($data);
        }
        $query = new Query();
        $query->setSelect(new SelectNode([new AggregateFunctionNode('max', 'sys_entities.add_date')]));

        $result = $this->object->query($query);
        $this->assertEquals(true, $result[0]['sys_entities.add_date->max'] >= $time);
    }

    public function test__query_with_sort()
    {
        $this->__init();
        for ($i = 1; $i < 10; $i++) {
            $data = [
                'title' => 'title_' . $i,
                'price' => 100 * $i
            ];

            $this->object->create($data);
            $this->assertEquals($i, $this->object->count());
        }

        $query = new Query();

        $query->setSort(new SortNode([SysEntities::TABLE_NAME . '.add_date' => -1]));

        $result = $this->object->query($query);

        $res = true;

        $prev = $result[0];
        if (count($result) == 1) {
            $res = true;
        }
        for ($i = 0; $i < count($result); $i++) {
            $curr = $result[$i];
            if ($prev['add_date'] < $curr['add_date']) {
                $res = false;
                break;
            }
            $prev = $curr;
        }
        $this->assertEquals(true, $res);
    }

    public function test__delete()
    {
        $this->__init();
        for ($i = 1; $i < 10; $i++) {
            $data = [
                'title' => 'title_' . $i,
                'price' => 100 * $i
            ];

            $id = $this->object->create($data)['id'];
            $this->object->delete($id);
            $this->assertEquals(null, $this->object->read($id));

            /** @var SysEntities $sysEntities */
            $sysEntities = $this->container->get(SysEntities::TABLE_NAME);
            $this->assertEquals(null, $sysEntities->read($id));
        }
    }

    public function test__delete_with_props()
    {
        $this->__init();
        for ($i = 1; $i < 10; $i++) {
            $data = [
                'title' => 'title_' . $i,
                'price' => 100 * $i,
                StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                    ['url' => 'http://google.com', 'alt' => 'Pot1'],
                    ['url' => 'http://google.com1', 'alt' => 'Pot2'],
                ]
            ];

            $createdItem = $this->object->create($data);
            $this->object->delete($createdItem['id']);
            $this->assertEquals(null, $this->object->read($createdItem['id']));

            /** @var SysEntities $sysEntities */
            $sysEntities = $this->container->get(SysEntities::TABLE_NAME);
            $this->assertEquals(null, $sysEntities->read($createdItem['id']));

            foreach ($createdItem[StoreCatalog::PROP_LINKED_URL_TABLE_NAME] as $propItem) {
                $prop = new Prop(new TableGateway(StoreCatalog::PROP_LINKED_URL_TABLE_NAME, $this->container->get('db')));
                $this->assertEquals(null, $prop->read($propItem[$prop->getIdentifier()]));
            }
        }
    }

    public function test__delete_all()
    {
        //$idArr = [];
        $this->__init();
        for ($i = 1; $i < 10; $i++) {
            $data = [
                'title' => 'title_' . $i,
                'price' => 100 * $i
            ];

            //$idArr[] = $this->object->create($data)['id'];
        }
        $this->object->deleteAll();
        $result = $this->object->query(new Query());
        $this->assertEquals(true, empty($result));
    }

    public function test__create_nested_rollback()
    {
        $this->__init();
        try {
            $this->object->create([
                'title' => 'title_1',
                'price' => 100,
                StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                    ['url' => 'http://google.com-1', 'alt' => 'Pot1-1'],
                    ['url' => 'http://google.com2', 'alt' => 'Pot3', 'ttt' => 'asd'],
                ]
            ]);
        } catch (DataStoreException $e) {
            $query = new Query();
            $query->setQuery(
                new AndNode([
                    new EqNode('url', 'http://google.com-1'),
                    new EqNode('alt', 'Pot1-1')
                ])
            );
            $prop = new Prop(new TableGateway(StoreCatalog::PROP_LINKED_URL_TABLE_NAME, $this->container->get('db')));
            $result = $prop->query($query);
            $this->assertEquals(0, count($result));


            $query = new Query();
            $query->setQuery(
                new AndNode([
                    new EqNode('title', 'title_1'),
                    new EqNode('price', 100)
                ])
            );
            $result = $this->object->query($query);
            $this->assertEquals(0, count($result));
            return;
        }
        $this->fail("An expected exception has not been raised.");
    }

    public function test__update_nested_rollback()
    {
        $this->__init();
        $data = $this->object->create([
            'title' => 'title_1',
            'price' => 100,
            StoreCatalog::PROP_LINKED_URL_TABLE_NAME => [
                ['url' => 'http://google.com', 'alt' => 'Pot1'],
                ['url' => 'http://google.com2', 'alt' => 'Pot3'],
            ]
        ]);

        $data[StoreCatalog::PROP_LINKED_URL_TABLE_NAME] = ['url' => 'http://google.com3', 'alt' => 'Pot4', 'ttt' => 'asd'];
        $data[StoreCatalog::PROP_LINKED_URL_TABLE_NAME] = ['url' => 'http://google.com-1', 'alt' => 'Pot1-1'];
        try {
            $this->object->update($data);
        } catch (DataStoreException $e) {
            $query = new Query();
            $query->setQuery(
                new AndNode([
                    new EqNode('url', 'http://google.com-1'),
                    new EqNode('alt', 'Pot1-1')
                ])
            );
            $prop = new Prop(new TableGateway(StoreCatalog::PROP_LINKED_URL_TABLE_NAME, $this->container->get('db')));
            $result = $prop->query($query);
            $this->assertEquals(0, count($result));
            return ;
        }
        $this->fail("An expected exception has not been raised.");
    }
}