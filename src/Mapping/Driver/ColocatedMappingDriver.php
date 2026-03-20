<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping\Driver;

use function array_filter;
use function array_merge;
use function array_unique;
use function array_values;
use Doctrine\Persistence\Mapping\Mapping_Exception;
/**
 * The ColocatedMappingDriver reads the mapping metadata located near the code.
 */
trait Colocated_Mapping_Driver
{
    private Class_Locator|null $class_locator = null;
    /**
     * The directory paths where to look for mapping files.
     *
     * @var array<int, string>
     */
    protected array $paths = [];
    /**
     * The paths excluded from path where to look for mapping files.
     *
     * @var array<int, string>
     */
    protected array $exclude_paths = [];
    /** The file extension of mapping documents. */
    protected string $file_extension = '.php';
    /**
     * Cache for {@see getAllClassNames()}.
     *
     * @var array<int, string>|null
     * @phpstan-var list<class-string>|null
     */
    protected array|null $class_names = null;
    /**
     * Appends lookup paths to metadata driver.
     *
     * @param array<int, string> $paths
     */
    public function add_paths(array $paths): void
    {
        $this->paths = array_unique(array_merge($this->paths, $paths));
    }
    /**
     * Retrieves the defined metadata lookup paths.
     *
     * @return array<int, string>
     */
    public function get_paths(): array
    {
        return $this->paths;
    }
    /**
     * Append exclude lookup paths to a metadata driver.
     *
     * @param string[] $paths
     */
    public function add_exclude_paths(array $paths): void
    {
        $this->exclude_paths = array_unique(array_merge($this->exclude_paths, $paths));
    }
    /**
     * Retrieve the defined metadata lookup exclude paths.
     *
     * @return array<int, string>
     */
    public function get_exclude_paths(): array
    {
        return $this->exclude_paths;
    }
    /** Gets the file extension used to look for mapping files under. */
    public function get_file_extension(): string
    {
        return $this->file_extension;
    }
    /** Sets the file extension used to look for mapping files under. */
    public function set_file_extension(string $file_extension): void
    {
        $this->file_extension = $file_extension;
    }
    /**
     * {@inheritDoc}
     *
     * Returns whether the class with the specified name is transient. Only non-transient
     * classes, that is entities and mapped superclasses, should have their metadata loaded.
     *
     * @phpstan-param class-string $className
     */
    abstract public function is_transient(string $class_name): bool;
    /**
     * Gets the names of all mapped classes known to this driver.
     *
     * @return string[] The names of all mapped classes known to this driver.
     * @phpstan-return list<class-string>
     */
    public function get_all_class_names(): array
    {
        if ($this->class_names !== null) {
            return $this->class_names;
        }
        if ($this->paths === [] && $this->class_locator === null) {
            throw Mapping_Exception::path_required_for_driver(static::class);
        }
        $class_names = $this->class_locator?->get_class_names() ?? [];
        if ($this->paths !== []) {
            $class_names = array_unique([...File_Class_Locator::create_from_directories($this->paths, $this->exclude_paths, $this->file_extension)->get_class_names(), ...$class_names]);
        }
        return $this->class_names = array_values(array_filter($class_names, fn(string $class_name): bool => !$this->is_transient($class_name)));
    }
}