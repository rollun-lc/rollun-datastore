<?php

namespace rollun\test\functional\DataStore\DataStore\QueryTest\DateField;

use DateTimeImmutable;
use DateTimeInterface;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\test\functional\FunctionalTestCase;
use Xiag\Rql\Parser\Node\AbstractQueryNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LtNode;
use Xiag\Rql\Parser\Query;

abstract class BaseTest extends FunctionalTestCase
{
    protected const ID_NAME = 'id';
    protected const FIELD_NAME = 'date';
    protected const TABLE_NAME = 'date_test';

    public function filterDataProvider(): array
    {
        return [
            // Test equals
            'Equal to itself' => [
                $expectedRecord = [self::ID_NAME => 1, self::FIELD_NAME => $date = $this->format(new DateTimeImmutable())],
                new EqNode(self::FIELD_NAME, $date),
                [$expectedRecord],
            ],
            'Do not equal to another date' => [
                [self::ID_NAME => 1, self::FIELD_NAME => $this->format(new DateTimeImmutable())],
                new EqNode(self::FIELD_NAME, $this->format(new DateTimeImmutable('-1 day'))),
                [],
            ],
            //            'Do not equal to ATOM date format' => [
            //                [self::ID_NAME => 1, self::FIELD_NAME => $this->format($date = new DateTimeImmutable())],
            //                new EqNode(self::FIELD_NAME, $date->format(DateTimeInterface::ATOM)),
            //                [],
            //            ],
            //            'Do not equal to RFC822 date format' => [
            //                [self::ID_NAME => 1, self::FIELD_NAME => $this->format($date = new DateTimeImmutable())],
            //                new EqNode(self::FIELD_NAME, $date->format(DateTimeInterface::RFC822)),
            //                [],
            //            ],

            // Test greater than
            'Greater than test date is greater' => [
                [self::ID_NAME => 1, self::FIELD_NAME => $this->format($date = new DateTimeImmutable())],
                new GtNode(self::FIELD_NAME, $this->format($date->modify('+1 second'))),
                [],
            ],
            'Greater than test date is greater (short)' => [
                [self::ID_NAME => 1, self::FIELD_NAME => $this->shortFormat($date = new DateTimeImmutable())],
                new GtNode(self::FIELD_NAME, $this->shortFormat($date->modify('+1 second'))),
                [],
            ],
            'Greater than test date is lower' => [
                $expectedRecord = [self::ID_NAME => 1, self::FIELD_NAME => $this->format($date = new DateTimeImmutable())],
                new GtNode(self::FIELD_NAME, $this->format($date->modify('-1 second'))),
                [$expectedRecord],
            ],
            'Greater than test date is same' => [
                [self::ID_NAME => 1, self::FIELD_NAME => $date = $this->format(new DateTimeImmutable())],
                new GtNode(self::FIELD_NAME, $date),
                [],
            ],
            'Greater than test date is same (short)' => [
                [self::ID_NAME => 1, self::FIELD_NAME => $this->format($date = new DateTimeImmutable('2023-12-30 00:00:00'))],
                new GtNode(self::FIELD_NAME, $this->format(new DateTimeImmutable('2024-12-30'))),
                [],
            ],

            // Test lower than
            'Lower than test date is greater' => [
                $expectedRecord = [self::ID_NAME => 1, self::FIELD_NAME => $this->format($date = new DateTimeImmutable())],
                new LtNode(self::FIELD_NAME, $this->format($date->modify('+1 second'))),
                [$expectedRecord],
            ],
            'Lower than test date is lower' => [
                [self::ID_NAME => 1, self::FIELD_NAME => $this->format($date = new DateTimeImmutable())],
                new LtNode(self::FIELD_NAME, $this->format($date->modify('-1 second'))),
                [],
            ],
            'Lower than test date is same' => [
                [self::ID_NAME => 1, self::FIELD_NAME => $date = $this->format(new DateTimeImmutable())],
                new LtNode(self::FIELD_NAME, $date),
                [],
            ],

            // Test greater than or equals
            'Greater than or equal test date is greater' => [
                [self::ID_NAME => 1, self::FIELD_NAME => $this->format($date = new DateTimeImmutable())],
                new GeNode(self::FIELD_NAME, $this->format($date->modify('+1 second'))),
                [],
            ],
            'Greater than or equal test date is lower' => [
                $expectedRecord = [self::ID_NAME => 1, self::FIELD_NAME => $this->format($date = new DateTimeImmutable())],
                new GeNode(self::FIELD_NAME, $this->format($date->modify('-1 second'))),
                [$expectedRecord],
            ],
            'Greater than or equal test date is same' => [
                $expectedRecord = [self::ID_NAME => 1, self::FIELD_NAME => $date = $this->format(new DateTimeImmutable())],
                new GeNode(self::FIELD_NAME, $date),
                [$expectedRecord],
            ],
            'Greater than or equal test date is same (short)' => [
                [self::ID_NAME => 1, self::FIELD_NAME => $this->format(new DateTimeImmutable('2023-12-30 00:00:00'))],
                new GtNode(self::FIELD_NAME, $this->format(new DateTimeImmutable('2024-12-30'))),
                [],
            ],

            // Test lower than or equals
            'Lower than or equal test date is greater' => [
                $expectedRecord = [self::ID_NAME => 1, self::FIELD_NAME => $this->format($date = new DateTimeImmutable())],
                new LeNode(self::FIELD_NAME, $this->format($date->modify('+1 second'))),
                [$expectedRecord],
            ],
            'Lower than or equal test date is lower' => [
                [self::ID_NAME => 1, self::FIELD_NAME => $this->format($date = new DateTimeImmutable())],
                new LeNode(self::FIELD_NAME, $this->format($date->modify('-1 second'))),
                [],
            ],
            'Lower than or equal test date is same' => [
                $expectedRecord = [self::ID_NAME => 1, self::FIELD_NAME => $date = $this->format(new DateTimeImmutable())],
                new LeNode(self::FIELD_NAME, $date),
                [$expectedRecord],
            ],
        ];
    }

    /**
     * @dataProvider filterDataProvider
     */
    public function testFilter(array $record, AbstractQueryNode $queryNode, array $expectedRecords): void
    {
        $dataStore = $this->getDataStore();
        $dataStore->create($record);

        $query = new Query();
        $query->setQuery($queryNode);
        $records = $dataStore->query($query);

        self::assertEquals($expectedRecords, $records);
    }

    private function format(DateTimeInterface $dateTime): string
    {
        return $dateTime->format('Y-m-d H:i:s');
    }

    private function shortFormat(DateTimeInterface $dateTime): string
    {
        return $dateTime->format('Y-m-d');
    }

    abstract protected function getDataStore(): DataStoreInterface;
}
