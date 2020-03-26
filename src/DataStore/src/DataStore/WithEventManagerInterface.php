<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace rollun\datastore\DataStore;

use Zend\EventManager\EventManagerInterface;

/**
 * Class WithEventManagerInterface
 *
 * @author Roman Ratsun <r.ratsun.rollun@gmail.com>
 */
interface WithEventManagerInterface
{
    /**
     * @return EventManagerInterface
     */
    public function getEventManager(): EventManagerInterface;
}
