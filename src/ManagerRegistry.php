<?php

declare (strict_types=1);
namespace Doctrine\Persistence;

/**
 * Contract covering object managers for a Doctrine persistence layer ManagerRegistry class to implement.
 */
interface Manager_Registry extends Connection_Registry
{
    /**
     * Gets the default object manager name.
     *
     * @return string The default object manager name.
     */
    public function get_default_manager_name(): string;
    /**
     * Gets a named object manager.
     *
     * @param string|null $name The object manager name (null for the default one).
     */
    public function get_manager(string|null $name = null): Object_Manager;
    /**
     * Gets an array of all registered object managers.
     *
     * @return array<string, ObjectManager> An array of ObjectManager instances
     */
    public function get_managers(): array;
    /**
     * Resets a named object manager.
     *
     * This method is useful when an object manager has been closed
     * because of a rollbacked transaction AND when you think that
     * it makes sense to get a new one to replace the closed one.
     *
     * Be warned that you will get a brand new object manager as
     * the existing one is not useable anymore. This means that any
     * other object with a dependency on this object manager will
     * hold an obsolete reference. You can inject the registry instead
     * to avoid this problem.
     *
     * @param string|null $name The object manager name (null for the default one).
     */
    public function reset_manager(string|null $name = null): Object_Manager;
    /**
     * Gets all object manager names and associated service IDs. A service ID
     * is a string that allows to obtain an object manager, typically from a
     * PSR-11 container.
     *
     * @return array<string,string> An array with object manager names as keys,
     *                              and service IDs as values.
     */
    public function get_manager_names(): array;
    /**
     * Gets the ObjectRepository for a persistent object.
     *
     * @param string      $persistentObject      The name of the persistent object.
     * @param string|null $persistentManagerName The object manager name (null for the default one).
     * @phpstan-param class-string<T> $persistentObject
     *
     * @phpstan-return ObjectRepository<T>
     *
     * @template T of object
     */
    public function get_repository(string $persistent_object, string|null $persistent_manager_name = null): Object_Repository;
    /**
     * Gets the object manager associated with a given class.
     *
     * @param class-string $class A persistent object class name.
     */
    public function get_manager_for_class(string $class): Object_Manager|null;
}