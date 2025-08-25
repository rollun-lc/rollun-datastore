<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Aspect;

use Graviton\RqlParser\Query;
use rollun\datastore\DataStore\Entity\EntityFactory;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Query\NullQueryAdapter;
use rollun\datastore\DataStore\Query\QueryAdapter;
use rollun\datastore\DataStore\Scheme\Scheme;

class AspectSchema extends AspectEntityMapper
{
    /**
     * @var QueryAdapter
     */
    private $queryAdapter;

    public function __construct(
        DataStoresInterface $dataStore,
        private Scheme $scheme,
        private EntityFactory $entityFactory,
        ?QueryAdapter $queryAdapter = null
    ) {
        parent::__construct($dataStore);
        $this->queryAdapter = $queryAdapter ?? new NullQueryAdapter();
    }

    protected function mapEntityToRecord($itemData): array
    {
        $record = [];
        foreach ($this->scheme->toArray() as $fieldName => $fieldInfo) {
            $value = $fieldInfo->getGetter()->get($itemData);
            $formattedValue = $fieldInfo->getFormatter()->format($value);
            $record[$fieldName] = $formattedValue;
        }
        return $record;
    }

    /**
     * @param $record
     * @return object|array
     */
    protected function mapRecordToEntity($record)
    {
        $typedRecord = [];
        foreach ($record as $fieldName => $fieldValue) {
            $fieldInfo = $this->scheme->findInfoByFieldName($fieldName);
            $typedValue = $fieldInfo->isNullable() && $fieldValue === null ?
                null : $fieldInfo->getTypeFactory()->create($fieldValue)->toTypeValue();
            $typedRecord[$fieldName] = $typedValue;
        }
        return $this->entityFactory->fromRecord($typedRecord);
    }

    protected function preProcessQuery(Query $query): Query
    {
        return $this->queryAdapter->adapt($query);
    }
}
