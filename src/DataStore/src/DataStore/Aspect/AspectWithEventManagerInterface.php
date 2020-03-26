<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace rollun\datastore\DataStore\Aspect;

use Zend\EventManager\EventManagerInterface;

/**
 * Class AspectWithEventManagerInterface
 *
 * @author Roman Ratsun <r.ratsun.rollun@gmail.com>
 */
interface AspectWithEventManagerInterface
{
    /**
     * @return EventManagerInterface
     */
    public function getEventManager(): EventManagerInterface;

    /**
     * @return string
     */
    public function getDataStoreName(): string;
}
