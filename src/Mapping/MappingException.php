<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping;

use Exception;
use function implode;
use function sprintf;
/**
 * A MappingException indicates that something is wrong with the mapping setup.
 */
class Mapping_Exception extends Exception
{
    /** @param array<int, string> $namespaces */
    public static function class_not_found_in_namespaces(string $class_name, array $namespaces): self
    {
        return new self(sprintf("The class '%s' was not found in the chain configured namespaces %s", $class_name, implode(', ', $namespaces)));
    }
    /** @param class-string $driverClassName */
    public static function path_required_for_driver(string $driver_class_name): self
    {
        return new self(sprintf('Specifying source file paths to your entities is required when using %s to retrieve all class names.', $driver_class_name));
    }
    public static function file_mapping_drivers_require_configured_directory_path(string|null $path = null): self
    {
        if ($path !== null) {
            $path = '[' . $path . ']';
        }
        return new self(sprintf('File mapping drivers must have a valid directory path, ' . 'however the given path %s seems to be incorrect!', (string) $path));
    }
    public static function mapping_file_not_found(string $entity_name, string $file_name): self
    {
        return new self(sprintf("No mapping file found named '%s' for class '%s'.", $file_name, $entity_name));
    }
    public static function invalid_mapping_file(string $entity_name, string $file_name): self
    {
        return new self(sprintf("Invalid mapping file '%s' for class '%s'.", $file_name, $entity_name));
    }
    public static function non_existing_class(string $class_name): self
    {
        return new self(sprintf("Class '%s' does not exist", $class_name));
    }
    /** @param class-string $className */
    public static function class_is_anonymous(string $class_name): self
    {
        return new self(sprintf('Class "%s" is anonymous', $class_name));
    }
}