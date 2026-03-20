<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping\Driver;

use function array_unique;
use Doctrine\Persistence\Mapping\Class_Metadata;
use Doctrine\Persistence\Mapping\Mapping_Exception;
use function get_declared_classes;
use function in_array;
use function is_dir;
use function method_exists;
use function realpath;
use Recursive_Directory_Iterator;
use Recursive_Iterator_Iterator;
use ReflectionClass;
/**
 * The StaticPHPDriver calls a static loadMetadata() method on your entity
 * classes where you can manually populate the ClassMetadata instance.
 */
class Static_Php_Driver implements Mapping_Driver
{
    /**
     * Paths of entity directories.
     *
     * @var array<int, string>
     */
    private array $paths = [];
    /**
     * Map of all class names.
     *
     * @var array<int, string>
     * @phpstan-var list<class-string>
     */
    private array|null $class_names = null;
    /** @param array<int, string>|string $paths */
    public function __construct(array|string $paths)
    {
        $this->add_paths((array) $paths);
    }
    /** @param array<int, string> $paths */
    public function add_paths(array $paths): void
    {
        $this->paths = array_unique([...$this->paths, ...$paths]);
    }
    public function load_metadata_for_class(string $class_name, Class_Metadata $metadata): void
    {
        $class_name::load_metadata($metadata);
    }
    /**
     * {@inheritDoc}
     *
     * @todo Same code exists in ColocatedMappingDriver, should we re-use it
     * somehow or not worry about it?
     */
    public function get_all_class_names(): array
    {
        if ($this->class_names !== null) {
            return $this->class_names;
        }
        if ($this->paths === []) {
            throw Mapping_Exception::path_required_for_driver(static::class);
        }
        $classes = [];
        $included_files = [];
        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                throw Mapping_Exception::file_mapping_drivers_require_configured_directory_path($path);
            }
            $iterator = new Recursive_Iterator_Iterator(new Recursive_Directory_Iterator($path), Recursive_Iterator_Iterator::LEAVES_ONLY);
            foreach ($iterator as $file) {
                if ($file->get_basename('.php') === $file->get_basename()) {
                    continue;
                }
                $source_file = realpath($file->get_path_name());
                require_once $source_file;
                $included_files[] = $source_file;
            }
        }
        $declared = get_declared_classes();
        foreach ($declared as $class_name) {
            $rc = new ReflectionClass($class_name);
            $source_file = $rc->get_file_name();
            if (!in_array($source_file, $included_files, true)) {
                continue;
            }
            if ($this->is_transient($class_name)) {
                continue;
            }
            $classes[] = $class_name;
        }
        $this->class_names = $classes;
        return $classes;
    }
    public function is_transient(string $class_name): bool
    {
        return !method_exists($class_name, 'loadMetadata');
    }
}