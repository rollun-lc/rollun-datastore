<?php


namespace unit\DataStore\DataStore\Traits;


use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Traits\MappingFieldsTrait;

class MappingFieldsTraitTest extends TestCase
{
    protected function getInputData()
    {
        return [
            'Name' => 'Hello',
            'Price' => [
                'Value' => '10'
            ],
            'Data' => [
                'Test' => 'Value'
            ]
        ];
    }

    protected function getFieldMapping()
    {
        return [
            'name' => 'Name',
            'price' => 'Price.Value',
        ];
    }

    public function testPrepareDataArray()
    {
        $instance = new class() extends \stdClass
        {
            use MappingFieldsTrait;

            protected $fields;
        };

        $input = $this->getInputData();
        $mapping = $this->getFieldMapping();

        $instance->setFields($mapping);

        $expected = [
            'name' => 'Hello',
            'price' => 10,
        ];
        $actual = $instance->prepareData($input);

        $this->assertEquals($expected, $actual);
    }

    public function testPrepareDataObject()
    {
        $instance = new class() extends \stdClass
        {
            use MappingFieldsTrait;

            protected $fields;
        };

        $input = json_decode(json_encode($this->getInputData()), false);
        $mapping = $this->getFieldMapping();

        $instance->setFields($mapping);

        $expected = [
            'name' => 'Hello',
            'price' => 10,
        ];
        $actual = $instance->prepareData($input);

        $this->assertEquals($expected, $actual);
    }

    public function testFormatField()
    {
        $instance = new class() extends \stdClass
        {
            use MappingFieldsTrait;

            protected $fields;

            public function formatPriceField($value)
            {
                return $value + 5;
            }
        };

        $input = $this->getInputData();
        $mapping = $this->getFieldMapping();

        $instance->setFields($mapping);

        $expected = [
            'name' => 'Hello',
            'price' => 15,
        ];
        $actual = $instance->prepareData($input);

        $this->assertEquals($expected, $actual);
    }

    public function testCastField()
    {
        $instance = new class() extends \stdClass
        {
            use MappingFieldsTrait;

            protected $fields;

            protected $casting;

            public function castInteger($value)
            {
                return (int) $value;
            }
        };

        $input = $this->getInputData();
        $mapping = $this->getFieldMapping();

        $instance->setFields($mapping);
        $instance->addField('data', 'Data');
        $instance->addCasting('price', 'integer');
        $instance->addCasting('data', 'json');

        $expected = [
            'name' => 'Hello',
            'price' => 10,
            'data' => json_encode($input['Data'])
        ];
        $actual = $instance->prepareData($input);

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public function testCastFieldCustom()
    {
        $instance = new class() extends \stdClass
        {
            use MappingFieldsTrait;

            protected $fields;

            protected $casting;

            public function castFromJsonToArray($value)
            {
                return json_decode($value, true);
            }
        };

        $input = $this->getInputData();
        $input['Data'] = json_encode($input['Data']);
        $mapping = $this->getFieldMapping();

        $instance->setFields($mapping);
        $instance->addField('data', 'Data');
        $instance->addCasting('data', 'from_json_to_array');

        $expected = [
            'name' => 'Hello',
            'price' => '10',
            'data' =>$this->getInputData()['Data']
        ];
        $actual = $instance->prepareData($input);

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public function testMappingWithCallback()
    {
        $instance = new class() extends \stdClass
        {
            use MappingFieldsTrait;

            protected $fields;
        };

        $input = $this->getInputData();
        $mapping = $this->getFieldMapping();

        $instance->setFields($mapping);

        $expected = [
            'name' => [
                'name' => 'name',
                'value' => 'Hello',
                'origin' => $this->getInputData(),
            ],
            'price' => [
                'name' => 'price',
                'value' => 10,
                'origin' => $this->getInputData(),
            ],
        ];
        $actual = $instance->prepareData($input, fn($value, $name, $origin) => [
            'name' => $name,
            'value' => $value,
            'origin' => $origin
        ]);

        $this->assertEquals($expected, $actual);
    }
}