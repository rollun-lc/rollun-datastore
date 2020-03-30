<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Aspect;

use Zend\EventManager\Event;

/**
 * Class AbstractAspectListener
 *
 * @author Roman Ratsun <r.ratsun.rollun@gmail.com>
 */
abstract class AbstractAspectListener
{
    /**
     * @param Event $event
     */
    public function __invoke(Event $event)
    {
        $action = $event->getName();
        if (method_exists($this, $action)) {
            $this->{$action}($event);
        }
    }

    /**
     * On serialize
     *
     * @return array
     */
    public function __sleep()
    {
        return [];
    }

    /**
     * On unserialize
     */
    public function __wakeup()
    {
    }
}
