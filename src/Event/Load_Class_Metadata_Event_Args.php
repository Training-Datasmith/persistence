<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Event;

use Doctrine\Common\Event_Args;
use Doctrine\Persistence\Mapping\Class_Metadata;
use Doctrine\Persistence\Object_Manager;
/**
 * Class that holds event arguments for a loadMetadata event.
 *
 * @template-covariant TClassMetadata of ClassMetadata<object>
 * @template-covariant TObjectManager of ObjectManager
 */
class Load_Class_Metadata_Event_Args extends Event_Args
{
    /**
     * @phpstan-param TClassMetadata $classMetadata
     * @phpstan-param TObjectManager $objectManager
     */
    public function __construct(private readonly Class_Metadata $class_metadata, private readonly Object_Manager $object_manager)
    {
    }
    /**
     * Retrieves the associated ClassMetadata.
     *
     * @phpstan-return TClassMetadata
     */
    public function get_class_metadata(): Class_Metadata
    {
        return $this->class_metadata;
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