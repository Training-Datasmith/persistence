<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping;

use function array_key_exists;
use function assert;
use function class_exists;
use function class_parents;
use Doctrine\Persistence\Reflection\Runtime_Reflection_Property;
use Doctrine\Persistence\Reflection\Typed_No_Default_Reflection_Property;
use ReflectionClass;
use Reflection_Exception;
use ReflectionMethod;
/**
 * PHP Runtime Reflection Service.
 */
class Runtime_Reflection_Service implements Reflection_Service
{
    /**
     * {@inheritDoc}
     */
    public function get_parent_classes(string $class): array
    {
        if (!class_exists($class)) {
            throw Mapping_Exception::non_existing_class($class);
        }
        $parents = class_parents($class);
        assert($parents !== false);
        return $parents;
    }
    public function get_class_short_name(string $class): string
    {
        $reflection_class = new ReflectionClass($class);
        return $reflection_class->get_short_name();
    }
    public function get_class_namespace(string $class): string
    {
        $reflection_class = new ReflectionClass($class);
        return $reflection_class->get_namespace_name();
    }
    /**
     * @phpstan-param class-string<T> $class
     *
     * @phpstan-return ReflectionClass<T>
     *
     * @template T of object
     */
    public function get_class(string $class): ReflectionClass
    {
        return new ReflectionClass($class);
    }
    public function get_accessible_property(string $class, string $property): Runtime_Reflection_Property
    {
        if (!array_key_exists($property, $this->get_class($class)->get_default_properties())) {
            return new Typed_No_Default_Reflection_Property($class, $property);
        }
        return new Runtime_Reflection_Property($class, $property);
    }
    public function has_public_method(string $class, string $method): bool
    {
        try {
            $reflection_method = new ReflectionMethod($class, $method);
        } catch (Reflection_Exception) {
            return false;
        }
        return $reflection_method->is_public();
    }
}