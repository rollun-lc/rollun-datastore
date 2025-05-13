<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Aspect;

use InvalidArgumentException;
use rollun\datastore\DataStore\BaseDto;
use rollun\datastore\DataStore\Formatter\FormatterInterface;
use rollun\datastore\DataStore\Formatter\FormatterPluginManager;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Interfaces\SchemableInterface;
use rollun\datastore\DataStore\Type\TypePluginManager;
use RuntimeException;
use Xiag\Rql\Parser\Query;
use Laminas\ServiceManager\ServiceManager;

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
     * @var TypePluginManager
     */
    protected $typePluginManager;

    /**
     * @var FormatterPluginManager
     */
    protected $formatterPluginManager;

    /**
     * AspectTyped constructor.
     * @param DataStoresInterface $dataStore
     * @param array $scheme
     * @param string $dtoClassName
     * @param TypePluginManager|null $typePluginManager
     * @param FormatterPluginManager|null $formatterPluginManager
     */
    public function __construct(
        DataStoresInterface $dataStore,
        array $scheme,
        string $dtoClassName,
        TypePluginManager $typePluginManager = null,
        FormatterPluginManager $formatterPluginManager = null
    ) {
        parent::__construct($dataStore);

        foreach ($scheme as $field => $fieldInfo) {
            if (!isset($fieldInfo['type'])) {
                throw new InvalidArgumentException("Invalid option 'type' in scheme for field '{$field}'");
            }

            if (!isset($fieldInfo['formatter'])) {
                throw new InvalidArgumentException("Invalid option 'formatter' in scheme for field '{$field}'");
            }
        }

        if (!is_a($dtoClassName, BaseDto::class, true)) {
            throw new InvalidArgumentException("Invalid value for 'dtoClassName' property");
        }

        if (is_null($typePluginManager)) {
            $this->typePluginManager = new TypePluginManager(new ServiceManager());
        } else {
            $this->typePluginManager = $typePluginManager;
        }

        if (is_null($formatterPluginManager)) {
            $this->formatterPluginManager = new FormatterPluginManager(new ServiceManager());
        } else {
            $this->formatterPluginManager = $formatterPluginManager;
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
    protected function postUpdate($result, $itemData, $rewriteIfExist)
    {
        if (is_array($result)) {
            return $this->arrayToDto($result);
        }

        return $result;
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
    protected function postCreate($result, $itemData, $rewriteIfExist)
    {
        if (is_array($result)) {
            return $this->arrayToDto($result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function postRead($result, $id)
    {
        if (is_array($result)) {
            return $this->arrayToDto($result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function postDelete($result, $id)
    {
        if (is_array($result)) {
            return $this->arrayToDto($result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function postQuery($result, Query $query)
    {
        $dtoResult = [];

        foreach ($result as $dataItem) {
            if (is_array($result)) {
                $dtoResult[] = $this->arrayToDto($dataItem);
            }
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
                throw new RuntimeException("Undefined method '$getter' in " . $dto::class);
            }

            /** @var FormatterInterface $formatter */
            $formatter = $this->formatterPluginManager->get($fieldInfo['formatter']);

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

            $typeService = $this->scheme[$field]['type'];
            $itemData[$field] = $this->typePluginManager->get($typeService, ['value' => $value]);
        }

        return new $this->dtoClassName($itemData);
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
                'type' => call_user_func([$typeClassName, 'getTypeName']),
            ];
        }

        return $scheme;
    }
}
