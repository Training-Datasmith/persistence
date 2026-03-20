<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping\Driver;

use function array_keys;
use Doctrine\Persistence\Mapping\Class_Metadata;
use Doctrine\Persistence\Mapping\Mapping_Exception;
use function spl_object_id;
/**
 * The DriverChain allows you to add multiple other mapping drivers for
 * certain namespaces.
 */
class Mapping_Driver_Chain implements Mapping_Driver
{
    /**
     * The default driver.
     */
    private Mapping_Driver|null $default_driver = null;
    /** @var array<string, MappingDriver> */
    private array $drivers = [];
    /** Gets the default driver. */
    public function get_default_driver(): Mapping_Driver|null
    {
        return $this->default_driver;
    }
    /** Set the default driver. */
    public function set_default_driver(Mapping_Driver $driver): void
    {
        $this->default_driver = $driver;
    }
    /** Adds a nested driver. */
    public function add_driver(Mapping_Driver $nested_driver, string $namespace): void
    {
        $this->drivers[$namespace] = $nested_driver;
    }
    /**
     * Gets the array of nested drivers.
     *
     * @return array<string, MappingDriver> $drivers
     */
    public function get_drivers(): array
    {
        return $this->drivers;
    }
    public function load_metadata_for_class(string $class_name, Class_Metadata $metadata): void
    {
        foreach ($this->drivers as $namespace => $driver) {
            if (str_starts_with($class_name, $namespace)) {
                $driver->load_metadata_for_class($class_name, $metadata);
                return;
            }
        }
        if ($this->default_driver !== null) {
            $this->default_driver->load_metadata_for_class($class_name, $metadata);
            return;
        }
        throw Mapping_Exception::class_not_found_in_namespaces($class_name, array_keys($this->drivers));
    }
    /**
     * {@inheritDoc}
     */
    public function get_all_class_names(): array
    {
        $class_names = [];
        $driver_classes = [];
        foreach ($this->drivers as $namespace => $driver) {
            $oid = spl_object_id($driver);
            if (!isset($driver_classes[$oid])) {
                $driver_classes[$oid] = $driver->get_all_class_names();
            }
            foreach ($driver_classes[$oid] as $class_name) {
                if (!str_starts_with($class_name, $namespace)) {
                    continue;
                }
                $class_names[$class_name] = true;
            }
        }
        if ($this->default_driver !== null) {
            foreach ($this->default_driver->get_all_class_names() as $class_name) {
                $class_names[$class_name] = true;
            }
        }
        return array_keys($class_names);
    }
    public function is_transient(string $class_name): bool
    {
        foreach ($this->drivers as $namespace => $driver) {
            if (str_starts_with($class_name, $namespace)) {
                return $driver->is_transient($class_name);
            }
        }
        if ($this->default_driver !== null) {
            return $this->default_driver->is_transient($class_name);
        }
        return true;
    }
}