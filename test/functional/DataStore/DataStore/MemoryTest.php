<?php


namespace rollun\test\functional\DataStore\DataStore;


use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\Rql\Node\ContainsNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Query;

class MemoryTest extends TestCase
{
    /*public function testPregMatch()
    {
        $string = 'test';
        $result = preg_match($string, $string);

        $this->assertTrue($result);
    }*/

    /*public function testQueryLike()
    {
        $item = [
            'id' => 1,
            'name' => 'hello',
        ];
        $object = new Memory();
        $object->create($item);

        $query = new Query();
        $query->setQuery(new LikeNode('name', 'hello'));
        $result = $object->query($query);

        //$this->assertNotEmpty($result);
        $this->assertIsArray($result);
    }*/

    /*public function testQueryAlike()
    {
        $item = [
            'id' => 1,
            'name' => 'hello',
        ];
        $object = new Memory();
        $object->create($item);

        $query = new Query();
        $query->setQuery(new AlikeNode('name', 'hello'));
        $result = $object->query($query);

        //$this->assertNotEmpty($result);
        $this->assertIsArray($result);
    }*/

    /*public function testQueryLikeGlob()
    {
        $item = [
            'id' => 1,
            'name' => 'hello',
        ];
        $object = new Memory();
        $object->create($item);

        $query = new Query();
        $query->setQuery(new LikeGlobNode('name', 'hello'));
        $result = $object->query($query);

        $this->assertNotEmpty($result);
    }*/

    public function testQueryContains()
    {
        $item = [
            'id' => 1,
            'name' => 'hello',
        ];
        $object = new Memory();
        $object->create($item);

        $query = new Query();
        $query->setQuery(new ContainsNode('name', 'hello'));
        $result = $object->query($query);

        $this->assertNotEmpty($result);
    }

    public function testIntWithLeadingZeros()
    {
        $item = [
            'id' => 1,
            'name' => '094057',
        ];
        $object = new Memory();
        $object->create($item);

        $query = new Query();
        $query->setQuery(new EqNode('name', '094057'));
        $result = $object->query($query);

        $this->assertNotEmpty($result);
    }
}
