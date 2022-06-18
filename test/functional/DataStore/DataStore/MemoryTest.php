<?php


namespace rollun\test\functional\DataStore\DataStore;


use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\Rql\Node\AlikeNode;
use rollun\datastore\Rql\Node\ContainsNode;
use rollun\datastore\Rql\Node\LikeGlobNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LikeNode;
use Xiag\Rql\Parser\Node\SortNode;
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

    protected function getTestSortData(int $allCount, int $everyNull)
    {
        static $date;
        if (!$date) {
            $date = new \DateTime();
        }
        $date->setTimestamp(random_int(1640995200, 1643500800));
        $items = [];
        for ($i = 1; $i <= $allCount; $i++) {
            $item = [
                'id' => $i,
                'name' => 'name' . $i,
                'created_at' => $date->format('Y-m-d H:i:s'),
            ];
            if ($i % $everyNull === 0) {
                //$item['created_at'] = null;
                unset($item['created_at']);
            }
            $items[] = $item;
        }
        return $items;
    }

    public function testQuerySortWithNullSmall()
    {
        $allCount = 10;
        $everyNull = 5;
        $items = $this->getTestSortData($allCount, $everyNull);
        $object = new Memory();
        $object->multiCreate($items);

        $query = new Query();
        $query->setSort(new SortNode(['created_at' => SortNode::SORT_ASC, 'name' => SortNode::SORT_ASC]));
        $result = $object->query($query);

        $withNullChunk = array_chunk($result, $allCount / $everyNull)[0];
        $this->assertEmpty(array_filter($withNullChunk, function ($item) {
            return isset($item['created_at']);
        }));
    }

    public function testQuerySortWithNull()
    {
        $allCount = 1000;
        $everyNull = 100;
        $items = $this->getTestSortData($allCount, $everyNull);
        $object = new Memory();
        $object->multiCreate($items);

        $query = new Query();
        $query->setSort(new SortNode(['created_at' => SortNode::SORT_ASC]));
        $result = $object->query($query);

        $withNullChunk = array_chunk($result, $allCount / $everyNull)[0];
        $this->assertEmpty(array_filter($withNullChunk, function ($item) {
            return isset($item['created_at']);
        }));
    }
}