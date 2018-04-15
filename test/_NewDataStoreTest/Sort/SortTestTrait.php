<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 13.04.18
 * Time: 2:18 PM
 */

namespace rollun\test\datastore\DataStore\Sort;

use PHPUnit\Framework\Assert;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\test\datastore\DataStore\AbstractDataStoreTest;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;

trait SortTestTrait
{

    /**
     * @var DataStoresInterface
     */
    protected $object;

    /**
     * Prepare datastore for initialized with transmitted data
     * @param array $data
     * @return void
     */
    abstract protected function setInitialData(array $data);


    /**
     * Return dataStore Identifier field name
     * @return string
     */
    abstract protected function getDataStoreIdentifier();

    /**
     * Data provider for testSortField
     * @return mixed
     */
    abstract function provideSortFieldData();

    /**
     * Data provider for testSortException
     * @return mixed
     */
    abstract function provideSortExceptionData();

    /**
     * @param array $fields
     * @param $expectedResult
     * @dataProvider provideSortFieldData
     */
    public function testSortField(array $fields, $expectedResult)
    {
        $query = new Query();
        $query->setSort(new SortNode($fields));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param array $fields
     * @expectedException \rollun\datastore\DataStore\DataStoreException
     * @dataProvider provideSortExceptionData
     */
    public function testSortException(array $fields)
    {
        $query = new Query();
        $query->setSort(new SortNode($fields));
        $this->object->query($query);
    }
}