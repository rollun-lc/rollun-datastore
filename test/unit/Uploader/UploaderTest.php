<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\Uploader;

use rollun\datastore\DataStore\Memory;
use rollun\uploader\Uploader;
use PHPUnit\Framework\TestCase;
use Xiag\Rql\Parser\Query;

class UploaderTest extends TestCase
{
    /** @var Uploader */
    protected $object;

    /** @var Memory */
    protected $inMemDataStore;

    /** @var Memory */
    protected $outMemDataStore;

    protected function setUp(): void
    {
        $this->inMemDataStore = new Memory();
        $this->outMemDataStore = new Memory();
        $this->object = new Uploader($this->outMemDataStore, $this->inMemDataStore);
    }

    /**
     * @return array
     */
    public function outDataProvider()
    {
        return [
            [[]],
            [
                [
                    ["id" => 1, "name" => "name1"],
                    ["id" => 2, "name" => "name2"],
                    ["id" => 3, "name" => "name3"],
                ],
            ],
        ];
    }

    /**
     * @param $outData
     * @dataProvider outDataProvider
     * @throws \rollun\datastore\DataStore\DataStoreException
     */
    public function testUpload($outData)
    {
        foreach ($outData as $datum) {
            $this->outMemDataStore->create($datum);
        }

        $this->object->upload();
        $this->assertEquals($this->outMemDataStore->query(new Query()), $this->inMemDataStore->query(new Query()));
    }
}
