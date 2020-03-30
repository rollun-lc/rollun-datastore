<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Aspect;

use rollun\datastore\DataStore\WithEventManagerInterface;
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
class AspectWithEventManagerAbstract extends AspectAbstract implements WithEventManagerInterface
{
    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @var string|null
     */
    protected $dataStoreName;

    /**
     * AspectWithEventManagerAbstract constructor.
     *
     * @param DataStoresInterface        $dataStore
     * @param EventManagerInterface|null $eventManager
     * @param string|null                $dataStoreName
     */
    public function __construct(DataStoresInterface $dataStore, EventManagerInterface $eventManager = null, string $dataStoreName = null)
    {
        parent::__construct($dataStore);

        if ($eventManager === null) {
            $eventManager = new EventManager();
        }

        $this->eventManager = $eventManager;
        $this->dataStoreName = $dataStoreName;
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
    protected function preMultiCreate($records)
    {
        $this->triggerEvent('onPreMultiCreate', ['records' => $records]);

        return parent::preMultiCreate($records);
    }

    /**
     * @inheritDoc
     */
    protected function postMultiCreate($result, $records)
    {
        $this->triggerEvent('onPostMultiCreate', ['result' => $result, 'records' => $records]);

        return parent::postMultiCreate($result, $records);
    }

    /**
     * @inheritDoc
     */
    protected function preMultiUpdate($records)
    {
        $this->triggerEvent('onPreMultiUpdate', ['records' => $records]);

        return parent::preMultiUpdate($records);
    }

    /**
     * @inheritDoc
     */
    protected function postMultiUpdate($result, $records)
    {
        $this->triggerEvent('onPostMultiUpdate', ['result' => $result, 'records' => $records]);

        return parent::postMultiUpdate($result, $records);
    }

    /**
     * @inheritDoc
     */
    protected function preQueriedUpdate(&$record, Query $query)
    {
        $this->triggerEvent('onPreQueriedUpdate', ['record' => $record, 'query' => $query]);

        return parent::preQueriedUpdate($record, $query);
    }

    /**
     * @inheritDoc
     */
    protected function postQueriedUpdate($result, $record, Query $query)
    {
        $this->triggerEvent('onPostQueriedUpdate', ['result' => $result, 'record' => $record, 'query' => $query]);

        return parent::postQueriedUpdate($result, $record, $query);
    }

    /**
     * @inheritDoc
     */
    protected function preRewrite($record)
    {
        $this->triggerEvent('onPreRewrite', ['record' => $record]);

        return parent::preRewrite($record);
    }

    /**
     * @inheritDoc
     */
    protected function postRewrite($result, $record)
    {
        $this->triggerEvent('onPostRewrite', ['result' => $result, 'record' => $record]);

        return parent::postRewrite($result, $record);
    }

    /**
     * @inheritDoc
     */
    protected function preQueriedDelete(Query $query)
    {
        $this->triggerEvent('onPreQueriedDelete', ['query' => $query]);

        return parent::preQueriedDelete($query);
    }

    /**
     * @inheritDoc
     */
    protected function postQueriedDelete($result, Query $query)
    {
        $this->triggerEvent('onPostQueriedDelete', ['result' => $result, 'query' => $query]);

        return parent::postQueriedDelete($result, $query);
    }

    /**
     * @inheritDoc
     */
    public function getEventManager(): EventManagerInterface
    {
        return $this->eventManager;
    }

    /**
     * @param string $action
     * @param array  $params
     */
    protected function triggerEvent(string $action, array $params = []): void
    {
        // set dataStore name to event
        $params['dataStoreName'] = $this->dataStoreName;

        $this->getEventManager()->trigger($action, $this, $params);
    }
}
