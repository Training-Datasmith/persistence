<?php

declare (strict_types=1);
namespace Doctrine\Persistence;

use function assert;
use InvalidArgumentException;
use ReflectionClass;
use function sprintf;
/**
 * Abstract implementation of the ManagerRegistry contract.
 */
abstract class Abstract_Manager_Registry implements Manager_Registry
{
    /**
     * @param array<string, string> $connections
     * @param array<string, string> $managers
     * @phpstan-param class-string $proxyInterfaceName
     */
    public function __construct(private readonly string $name, private array $connections, private array $managers, private readonly string $default_connection, private readonly string $default_manager, private readonly string $proxy_interface_name)
    {
    }
    /**
     * Fetches/creates the given services.
     *
     * A service in this context is connection or a manager instance.
     *
     * @param string $name The name of the service.
     *
     * @return object The instance of the given service.
     */
    abstract protected function get_service(string $name): object;
    /**
     * Resets the given services.
     *
     * A service in this context is connection or a manager instance.
     *
     * @param string $name The name of the service.
     */
    abstract protected function reset_service(string $name): void;
    /** Gets the name of the registry. */
    public function get_name(): string
    {
        return $this->name;
    }
    public function get_connection(string|null $name = null): object
    {
        if ($name === null) {
            $name = $this->default_connection;
        }
        if (!isset($this->connections[$name])) {
            throw new InvalidArgumentException(sprintf('Doctrine %s Connection named "%s" does not exist.', $this->name, $name));
        }
        return $this->get_service($this->connections[$name]);
    }
    /**
     * {@inheritDoc}
     */
    public function get_connection_names(): array
    {
        return $this->connections;
    }
    /**
     * {@inheritDoc}
     */
    public function get_connections(): array
    {
        $connections = [];
        foreach ($this->connections as $name => $id) {
            $connections[$name] = $this->get_service($id);
        }
        return $connections;
    }
    public function get_default_connection_name(): string
    {
        return $this->default_connection;
    }
    public function get_default_manager_name(): string
    {
        return $this->default_manager;
    }
    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     */
    public function get_manager(string|null $name = null): Object_Manager
    {
        if ($name === null) {
            $name = $this->default_manager;
        }
        if (!isset($this->managers[$name])) {
            throw new InvalidArgumentException(sprintf('Doctrine %s Manager named "%s" does not exist.', $this->name, $name));
        }
        $service = $this->get_service($this->managers[$name]);
        assert($service instanceof Object_Manager);
        return $service;
    }
    public function get_manager_for_class(string $class): Object_Manager|null
    {
        // Guard against triggering the autoloader for non-existent classes, which can
        // produce unexpected filesystem I/O, expose directory layout via error messages,
        // or cause a ReflectionException to propagate uncaught.
        if (!class_exists($class, false) && !interface_exists($class, false)) {
            return null;
        }
        $proxy_class = new ReflectionClass($class);
        if ($proxy_class->is_anonymous()) {
            return null;
        }
        if ($proxy_class->implements_interface($this->proxy_interface_name)) {
            $parent_class = $proxy_class->get_parent_class();
            if ($parent_class === false) {
                return null;
            }
            $class = $parent_class->get_name();
        }
        foreach ($this->managers as $id) {
            $manager = $this->get_service($id);
            assert($manager instanceof Object_Manager);
            if (!$manager->get_metadata_factory()->is_transient($class)) {
                return $manager;
            }
        }
        return null;
    }
    /**
     * {@inheritDoc}
     */
    public function get_manager_names(): array
    {
        return $this->managers;
    }
    /**
     * {@inheritDoc}
     */
    public function get_managers(): array
    {
        $managers = [];
        foreach ($this->managers as $name => $id) {
            $manager = $this->get_service($id);
            assert($manager instanceof Object_Manager);
            $managers[$name] = $manager;
        }
        return $managers;
    }
    public function get_repository(string $persistent_object, string|null $persistent_manager_name = null): Object_Repository
    {
        return $this->select_manager($persistent_object, $persistent_manager_name)->get_repository($persistent_object);
    }
    public function reset_manager(string|null $name = null): Object_Manager
    {
        if ($name === null) {
            $name = $this->default_manager;
        }
        if (!isset($this->managers[$name])) {
            throw new InvalidArgumentException(sprintf('Doctrine %s Manager named "%s" does not exist.', $this->name, $name));
        }
        // force the creation of a new document manager
        // if the current one is closed
        $this->reset_service($this->managers[$name]);
        return $this->get_manager($name);
    }
    /** @phpstan-param class-string $persistentObject */
    private function select_manager(string $persistent_object, string|null $persistent_manager_name = null): Object_Manager
    {
        if ($persistent_manager_name !== null) {
            return $this->get_manager($persistent_manager_name);
        }
        return $this->get_manager_for_class($persistent_object) ?? $this->get_manager();
    }
}