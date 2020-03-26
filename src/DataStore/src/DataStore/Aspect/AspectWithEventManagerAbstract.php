<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Aspect;

use rollun\datastore\DataStore\Factory\DataStoreEventManagerFactory;
use Xiag\Rql\Parser\Query;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;

/**
 * Class AspectWithEventManagerAbstract
 *
 * This is wrapper for any type of dataStore which triggered pre' and 'post' actions to EventManager
 *
 * @author Roman Ratsun <r.ratsun.rollun@gmail.com>
 */
class AspectWithEventManagerAbstract extends AspectAbstract implements AspectWithEventManagerInterface
{
    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @var string
     */
    protected $dataStoreName;

    /**
     * AspectWithEventManagerAbstract constructor.
     *
     * @param DataStoresInterface        $dataStore
     * @param string|null                $dataStoreName
     * @param EventManagerInterface|null $eventManager
     */
    public function __construct(DataStoresInterface $dataStore, string $dataStoreName = null, EventManagerInterface $eventManager = null)
    {
        parent::__construct($dataStore);

        if ($dataStoreName === null) {
            $dataStoreName = 'aspectWithEventManager' . time();
        }

        $this->dataStoreName = $dataStoreName;

        if ($eventManager === null) {
            $eventManager = new EventManager();
        }

        $this->eventManager = $eventManager;
    }

    /**
     * @inheritDoc
     */
    protected function preGetIterator()
    {
        $this->triggerEvent('onPreGetIterator');

        parent::preGetIterator();
    }

    /**
     * @inheritDoc
     */
    protected function postGetIterator(\Traversable $iterator)
    {
        $this->triggerEvent('onPostGetIterator', ['iterator' => $iterator]);

        return parent::postGetIterator($iterator);
    }

    /**
     * @inheritDoc
     */
    protected function preCreate($itemData, $rewriteIfExist = false)
    {
        $this->triggerEvent('onPreCreate', ['itemData' => $itemData]);

        return parent::preCreate($itemData, $rewriteIfExist);
    }

    /**
     * @inheritDoc
     */
    protected function postCreate($result, $itemData, $rewriteIfExist)
    {
        $this->triggerEvent('onPostCreate', ['itemData' => $itemData, 'result' => $result]);

        return parent::postCreate($result, $itemData, $rewriteIfExist);
    }

    /**
     * @inheritDoc
     */
    protected function preUpdate($itemData, $createIfAbsent = false)
    {
        $this->triggerEvent('onPreUpdate', ['itemData' => $itemData]);

        return parent::preUpdate($itemData, $createIfAbsent);
    }

    /**
     * @inheritDoc
     */
    protected function postUpdate($result, $itemData, $createIfAbsent)
    {
        $this->triggerEvent('onPostUpdate', ['itemData' => $itemData, 'result' => $result]);

        return parent::postUpdate($result, $itemData, $createIfAbsent);
    }

    /**
     * @inheritDoc
     */
    protected function preDelete($id)
    {
        $this->triggerEvent('onPreDelete', ['id' => $id]);

        parent::preDelete($id);
    }

    /**
     * @inheritDoc
     */
    protected function postDelete($result, $id)
    {
        $this->triggerEvent('onPostDelete', ['id' => $id, 'result' => $result]);

        return parent::postDelete($result, $id);
    }

    /**
     * @inheritDoc
     */
    protected function preDeleteAll()
    {
        $this->triggerEvent('onPreDeleteAll');

        parent::preDeleteAll();
    }

    /**
     * @inheritDoc
     */
    protected function postDeleteAll($result)
    {
        $this->triggerEvent('onPostDeleteAll', ['result' => $result]);

        return parent::postDeleteAll($result);
    }

    /**
     * @inheritDoc
     */
    protected function preGetIdentifier()
    {
        $this->triggerEvent('onPreGetIdentifier');

        parent::preGetIdentifier();
    }

    /**
     * @inheritDoc
     */
    protected function postGetIdentifier($result)
    {
        $this->triggerEvent('onPostGetIdentifier', ['result' => $result]);

        return parent::postGetIdentifier($result);
    }

    /**
     * @inheritDoc
     */
    protected function preRead($id)
    {
        $this->triggerEvent('onPreRead', ['id' => $id]);

        parent::preRead($id);
    }

    /**
     * @inheritDoc
     */
    protected function postRead($result, $id)
    {
        $this->triggerEvent('onPostRead', ['id' => $id, 'result' => $result]);

        return parent::postRead($result, $id);
    }

    /**
     * @inheritDoc
     */
    protected function preHas($id)
    {
        $this->triggerEvent('onPreHas', ['id' => $id]);

        parent::preHas($id);
    }

    /**
     * @inheritDoc
     */
    protected function postHas($result, $id)
    {
        $this->triggerEvent('onPostHas', ['id' => $id, 'result' => $result]);

        return parent::postHas($result, $id);
    }

    /**
     * @inheritDoc
     */
    protected function preQuery(Query $query)
    {
        $this->triggerEvent('onPreQuery', ['query' => $query]);

        return parent::preQuery($query);
    }

    /**
     * @inheritDoc
     */
    protected function postQuery($result, Query $query)
    {
        $this->triggerEvent('onPostQuery', ['query' => $query, 'result' => $result]);

        return parent::postQuery($result, $query);
    }

    /**
     * @inheritDoc
     */
    protected function preCount()
    {
        $this->triggerEvent('onPreCount');

        parent::preCount();
    }

    /**
     * @inheritDoc
     */
    protected function postCount($result)
    {
        $this->triggerEvent('onPostCount', ['result' => $result]);

        return parent::postCount($result);
    }

    /**
     * @inheritDoc
     */
    public function getEventManager(): EventManagerInterface
    {
        return $this->eventManager;
    }

    /**
     * @inheritDoc
     */
    public function getDataStoreName(): string
    {
        return $this->dataStoreName;
    }

    /**
     * @param string $action
     *
     * @return string
     */
    public function createEventName(string $action): string
    {
        return DataStoreEventManagerFactory::EVENT_KEY . '.' . $this->getDataStoreName() . '.' . $action;
    }

    /**
     * @param string $action
     * @param array  $params
     */
    protected function triggerEvent(string $action, array $params = []): void
    {
        $this
            ->getEventManager()
            ->trigger($this->createEventName($action), $this, $params);
    }
}
