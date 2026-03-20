<?php

declare (strict_types=1);
namespace Doctrine\Persistence;

use Doctrine\Persistence\Mapping\Class_Metadata;
use Doctrine\Persistence\Mapping\Class_Metadata_Factory;
/**
 * Base class to simplify ObjectManager decorators
 *
 * @template-covariant TObjectManager of ObjectManager
 */
abstract class Object_Manager_Decorator implements Object_Manager
{
    /** @var TObjectManager */
    protected Object_Manager $wrapped;
    /**
     * {@inheritDoc}
     */
    public function find(string $class_name, $id): object|null
    {
        return $this->wrapped->find($class_name, $id);
    }
    public function persist(object $object): void
    {
        $this->wrapped->persist($object);
    }
    public function remove(object $object): void
    {
        $this->wrapped->remove($object);
    }
    public function clear(): void
    {
        $this->wrapped->clear();
    }
    public function detach(object $object): void
    {
        $this->wrapped->detach($object);
    }
    public function refresh(object $object): void
    {
        $this->wrapped->refresh($object);
    }
    public function flush(): void
    {
        $this->wrapped->flush();
    }
    public function get_repository(string $class_name): Object_Repository
    {
        return $this->wrapped->get_repository($class_name);
    }
    public function get_class_metadata(string $class_name): Class_Metadata
    {
        return $this->wrapped->get_class_metadata($class_name);
    }
    /** @phpstan-return ClassMetadataFactory<ClassMetadata<object>> */
    public function get_metadata_factory(): Class_Metadata_Factory
    {
        return $this->wrapped->get_metadata_factory();
    }
    public function initialize_object(object $obj): void
    {
        $this->wrapped->initialize_object($obj);
    }
    public function is_uninitialized_object(mixed $value): bool
    {
        return $this->wrapped->is_uninitialized_object($value);
    }
    public function contains(object $object): bool
    {
        return $this->wrapped->contains($object);
    }
}