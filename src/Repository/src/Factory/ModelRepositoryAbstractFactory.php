<?php


namespace rollun\repository\Factory;


use Interop\Container\ContainerInterface;
use rollun\datastore\AbstractFactoryAbstract;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Factory\DataStoreAbstractFactory;
use rollun\repository\Interfaces\ModelInterface;
use rollun\repository\ModelRepository;

/**
 * Class ModelDataStoreAbstractFactory
 * @package rollun\datastore\DataStore\Model
 *
 * Config example
 *
 * dataStore => [
 *      'class' => ModelRepository::class,
 *      'dataStore' => Memory:class,
 *      'model' => Inventory::class,
 * ],
 */
class ModelRepositoryAbstractFactory extends AbstractFactoryAbstract
{
    public const KEY_MODEL_REPOSITORY = 'modelRepository';

    public const KEY_DATASTORE = 'dataStore';

    public const KEY_MODEL = 'modelClass';

    protected const KEY_BASE_CLASS = ModelRepository::class;

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $serviceConfig = $config[self::KEY_MODEL_REPOSITORY][$requestedName];
        $requestedClassName = $serviceConfig[self::KEY_CLASS];

        $dataStoreClassName = $serviceConfig[self::KEY_DATASTORE];
        $dataStore = $container->get($dataStoreClassName);

        $modelClass = $serviceConfig[self::KEY_MODEL];
        if (!is_a($modelClass, ModelInterface::class, true)) {
            throw new \Exception('Class ' . self::KEY_MODEL . ' must implement ' . ModelInterface::class);
        }
        $model = new $modelClass();

        return new $requestedClassName($dataStore, $model);
    }

    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');

        if (!isset($config[static::KEY_MODEL_REPOSITORY][$requestedName][static::KEY_CLASS])) {
            return false;
        }

        $requestedClassName = $config[static::KEY_MODEL_REPOSITORY][$requestedName][static::KEY_CLASS];
        return is_a($requestedClassName, self::KEY_BASE_CLASS, true);
    }
}