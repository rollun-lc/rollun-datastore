<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 13.04.18
 * Time: 2:18 PM
 */

namespace rollun\test\datastore\DataStore\Select;

use PHPUnit\Framework\Assert;
use rollun\test\datastore\DataStore\AbstractDataStoreTest;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Query;

abstract class AbstractSelectTest extends AbstractDataStoreTest
{

    /**
     * DataProvider testSelectSuccess
     * @return mixed
     */
    abstract public function provideSelectSuccessData();

    /**
     * DataProvider testSelectUndefinedException
     * @return mixed
     */
    abstract public function provideSelectUndefinedExceptionData();

    /**
     * @param array $fields
     * @param array $expectedResult
     * @dataProvider provideSelectSuccessData
     */
    public function testSelectSuccess(array $fields, array $expectedResult) {
        $query = new Query();
        $query->setSelect(new SelectNode($fields));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param array $fields
     * @dataProvider provideSelectUndefinedExceptionData
     * @expectedException \rollun\datastore\DataStore\DataStoreException
     */
    public function testSelectUndefinedException(array $fields) {
        $query = new Query();
        $query->setSelect(new SelectNode($fields));
        $this->object->query($query);
    }
}