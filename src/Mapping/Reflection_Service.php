<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping;

use ReflectionClass;
use ReflectionProperty;
/**
 * Very simple reflection service abstraction.
 *
 * This is required inside metadata layers that may require either
 * static or runtime reflection.
 */
interface Reflection_Service
{
    /**
     * Returns an array of the parent classes (not interfaces) for the given class.
     *
     * @phpstan-param class-string $class
     *
     * @return string[]
     * @phpstan-return class-string[]
     *
     * @throws MappingException
     */
    public function get_parent_classes(string $class): array;
    /**
     * Returns the shortname of a class.
     *
     * @phpstan-param class-string $class
     */
    public function get_class_short_name(string $class): string;
    /** @phpstan-param class-string $class */
    public function get_class_namespace(string $class): string;
    /**
     * Returns a reflection class instance or null.
     *
     * @phpstan-param class-string<T> $class
     *
     * @phpstan-return ReflectionClass<T>
     *
     * @template T of object
     */
    public function get_class(string $class): ReflectionClass;
    /**
     * Returns an accessible property or null.
     *
     * @phpstan-param class-string $class
     */
    public function get_accessible_property(string $class, string $property): ReflectionProperty|null;
    /**
     * Checks if the class have a public method with the given name.
     *
     * @phpstan-param class-string $class
     */
    public function has_public_method(string $class, string $method): bool;
}