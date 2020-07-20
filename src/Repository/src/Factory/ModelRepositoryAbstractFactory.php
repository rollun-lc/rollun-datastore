<?php


namespace rollun\repository\Factory;


use Interop\Container\ContainerInterface;
use rollun\datastore\AbstractFactoryAbstract;
use rollun\repository\Interfaces\FieldResolverInterface;
use rollun\repository\Interfaces\ModelInterface;
use rollun\repository\Interfaces\ModelRepositoryInterface;

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

    public const KEY_RESOLVER = 'resolver';

    protected const KEY_BASE_CLASS = ModelRepositoryInterface::class;

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $serviceConfig = $config[self::KEY_MODEL_REPOSITORY][$requestedName];
        $requestedClassName = $serviceConfig[self::KEY_CLASS];

        $dataStoreClassName = $serviceConfig[self::KEY_DATASTORE];
        $dataStore = $container->get($dataStoreClassName);

        if (isset($serviceConfig[self::KEY_RESOLVER])) {
            $resolverClass = $serviceConfig[self::KEY_RESOLVER];
            if (!is_a($resolverClass, FieldResolverInterface::class, true)) {
                throw new \Exception('Resolver class must implement ' . FieldResolverInterface::class);
            }
            $resolver = $container->get($resolverClass);
        }

        $modelClass = $serviceConfig[self::KEY_MODEL];
        if (!is_a($modelClass, ModelInterface::class, true)) {
            throw new \Exception('Class ' . self::KEY_MODEL . ' must implement ' . ModelInterface::class);
        }

        return new $requestedClassName($dataStore, $modelClass, $resolver ?? null);
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