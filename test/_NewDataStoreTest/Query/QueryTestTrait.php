<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 13.04.18
 * Time: 5:02 PM
 */

namespace rollun\test\datastore\DataStore\Query;


use PHPUnit\Framework\Assert;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\test\datastore\DataStore\AbstractDataStoreTest;
use Xiag\Rql\Parser\Node\AbstractQueryNode;
use Xiag\Rql\Parser\Node\Query\ArrayOperator\InNode;
use Xiag\Rql\Parser\Node\Query\ArrayOperator\OutNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\OrNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LikeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\NeNode;
use Xiag\Rql\Parser\Query;

trait QueryTestTrait
{

    /*public function testMatchSuccess($field, $value, $expectedResult) {}*/
    /**
     * @var DataStoresInterface
     */
    protected $object;

    /**
     * @param $field
     * @param $value
     * @param $expectedResult
     * @dataProvider provideEqSuccessData
     */
    public function testEqSuccess($field, $value, $expectedResult) {
        $query = new Query();
        $query->setQuery(new EqNode($field, $value));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }


    /**
     * @param $field
     * @param $value
     * @param $expectedResult
     * @dataProvider provideNeSuccessData
     */
    public function testNeSuccess($field, $value, $expectedResult) {
        $query = new Query();
        $query->setQuery(new NeNode($field, $value));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }


    /**
     * @param $field
     * @param $value
     * @param $expectedResult
     * @dataProvider provideGeSuccessData
     */
    public function testGeSuccess($field, $value, $expectedResult) {
        $query = new Query();
        $query->setQuery(new GeNode($field, $value));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }


    /**
     * @param $field
     * @param $value
     * @param $expectedResult
     * @dataProvider provideGtSuccessData
     */
    public function testGtSuccess($field, $value, $expectedResult) {
        $query = new Query();
        $query->setQuery(new GtNode($field, $value));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }


    /**
     * @param $field
     * @param $value
     * @param $expectedResult
     * @dataProvider provideLeSuccessData
     */
    public function testLeSuccess($field, $value, $expectedResult) {
        $query = new Query();
        $query->setQuery(new LeNode($field, $value));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param $field
     * @param $value
     * @param $expectedResult
     * @dataProvider provideLtSuccessData
     */
    public function testLtSuccess($field, $value, $expectedResult) {
        $query = new Query();
        $query->setQuery(new LtNode($field, $value));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param $field
     * @param $values
     * @param $expectedResult
     * @dataProvider provideInSuccessData
     */
    public function testInSuccess($field, $values, $expectedResult) {
        $query = new Query();
        $query->setQuery(new InNode($field, $values));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param $field
     * @param $values
     * @param $expectedResult
     * @dataProvider provideOutSuccessData
     */
    public function testOutSuccess($field, $values, $expectedResult) {
        $query = new Query();
        $query->setQuery(new OutNode($field, $values));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param $field
     * @param $value
     * @param $expectedResult
     * @dataProvider provideLikeSuccessData
     */
    public function testLikeSuccess($field, $value, $expectedResult) {
        $query = new Query();
        $query->setQuery(new LikeNode($field, $value));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param array $nodes
     * @param $expectedResult
     * @dataProvider provideAndSuccessData
     */
    public function testAndSuccess(array $nodes, $expectedResult) {
        $query = new Query();
        $query->setQuery(new AndNode($nodes));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param array $nodes
     * @param $expectedResult
     * @dataProvider provideOrSuccessData
     */
    public function testOrSuccess(array $nodes, $expectedResult) {
        $query = new Query();
        $query->setQuery(new OrNode($nodes));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param AbstractQueryNode $node
     * @param $expectedResult
     * @dataProvider provideMixedSuccessData
     */
    public function testMixedSuccess(AbstractQueryNode $node,$expectedResult) {
        $query = new Query();
        $query->setQuery($node);
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }
}