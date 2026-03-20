<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Event;

use Doctrine\Common\Event_Args;
use Doctrine\Persistence\Object_Manager;
/**
 * Provides event arguments for the onClear event.
 *
 * @template-covariant TObjectManager of ObjectManager
 */
class On_Clear_Event_Args extends Event_Args
{
    /**
     * @param ObjectManager $objectManager The object manager.
     * @phpstan-param TObjectManager $objectManager
     */
    public function __construct(private readonly Object_Manager $object_manager)
    {
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