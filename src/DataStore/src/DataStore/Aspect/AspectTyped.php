<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Aspect;

use InvalidArgumentException;
use rollun\datastore\DataStore\BaseDto;
use rollun\datastore\DataStore\Formatter\FormatterInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Interfaces\SchemableInterface;
use rollun\datastore\DataStore\Type\TypeInterface;
use RuntimeException;
use Xiag\Rql\Parser\Query;

class AspectTyped extends AspectAbstract implements SchemableInterface
{
    /**
     * @var array
     */
    protected $scheme;

    /**
     * @var string
     */
    protected $dtoClassName;

    /**
     * AspectTyped constructor.
     * @param DataStoresInterface $dataStore
     * @param array $scheme
     * @param string $dtoClassName
     */
    public function __construct(DataStoresInterface $dataStore, array $scheme, string $dtoClassName)
    {
        parent::__construct($dataStore);

        foreach ($scheme as $fieldInfo) {
            if (!isset($fieldInfo['type']) || !is_a($fieldInfo['type'], TypeInterface::class, true)) {
                throw new InvalidArgumentException("Invalid option 'type' in scheme");
            }

            if (!isset($fieldInfo['formatter']) || !is_a($fieldInfo['formatter'], FormatterInterface::class, true)) {
                throw new InvalidArgumentException("Invalid option 'formatter' in scheme");
            }
        }

        if (!is_a($dtoClassName, BaseDto::class, true)) {
            throw new InvalidArgumentException("Invalid value for 'dtoClassName' property");
        }

        $this->dtoClassName = $dtoClassName;
        $this->scheme = $scheme;
    }

    /**
     * {@inheritdoc}
     */
    protected function preUpdate($itemData, $createIfAbsent = false)
    {
        if ($itemData instanceof BaseDto) {
            $itemData = $this->dtoToArray($itemData);
        }

        return  $itemData;
    }

    /**
     * {@inheritdoc}
     */
    protected function preCreate($itemData, $rewriteIfExist = false)
    {
        if ($itemData instanceof BaseDto) {
            $itemData = $this->dtoToArray($itemData);
        }

        return  $itemData;
    }

    /**
     * {@inheritdoc}
     */
    protected function postRead($result, $id)
    {
        return $this->arrayToDto($result);
    }

    /**
     * {@inheritdoc}
     */
    protected function postQuery($result, Query $query)
    {
        $dtoResult = [];

        foreach ($result as $dataItem) {
            $dtoResult[] = $this->arrayToDto($dataItem);
        }

        return $dtoResult;
    }

    /**
     * Convert BaseDto object to array
     *
     * @param BaseDto $dto
     * @return array
     */
    protected function dtoToArray(BaseDto $dto)
    {
        $itemData = [];

        foreach ($this->scheme as $fieldName => $fieldInfo) {
            $getter = 'get' . ucfirst($fieldName);

            if (!method_exists($dto, $getter)) {
                throw new RuntimeException("Undefined method '$getter' in " . get_class($dto));
            }

            $formatterClassName = $fieldInfo['formatter'];

            /** @var FormatterInterface $formatter */
            $formatter = new $formatterClassName();

            $itemData[$fieldName] = $formatter->format($dto->$getter());
        }

        return $itemData;
    }

    /**
     * Convert array to BaseDto object
     *
     * @param array $itemData
     * @return BaseDto
     */
    protected function arrayToDto(array $itemData)
    {
        foreach ($itemData as $field => $value) {
            if (!isset($this->scheme[$field])) {
                throw new InvalidArgumentException("Undefined field '$field' in scheme");
            }

            $typeClassName = $this->scheme[$field]['type'];
            $itemData[$field] = new $typeClassName($value);
        }

        return $this->dtoClassName::createFromArray($itemData);
    }

    /**
     * {@inheritdoc}
     *
     * @return array|mixed
     */
    public function getScheme()
    {
        $scheme = [];

        foreach ($this->scheme as $fieldName => $fieldInfo) {
            $typeClassName = $fieldInfo['type'];

            $scheme[$fieldName] = [
                'type' => $typeClassName::getTypeName(),
            ];
        }

        return $scheme;
    }
}
