<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Event;

use Doctrine\Common\Event_Args;
use Doctrine\Persistence\Object_Manager;
/**
 * Lifecycle Events are triggered by the UnitOfWork during lifecycle transitions
 * of entities.
 *
 * @template-covariant TObjectManager of ObjectManager
 */
class Lifecycle_Event_Args extends Event_Args
{
    /** @phpstan-param TObjectManager $objectManager */
    public function __construct(private readonly object $object, private readonly Object_Manager $object_manager)
    {
    }
    /** Retrieves the associated object. */
    public function get_object(): object
    {
        return $this->object;
    }
    /**
     * Retrieves the associated ObjectManager.
     *
     * @phpstan-return TObjectManager
     */
    public function get_object_manager(): Object_Manager
    {
        return $this->object_manager;
    }
}