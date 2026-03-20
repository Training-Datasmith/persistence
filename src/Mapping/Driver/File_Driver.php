<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping\Driver;

use function array_keys;
use function array_unique;
use function array_values;
use Doctrine\Persistence\Mapping\Mapping_Exception;
use function is_file;
use function str_replace;
/**
 * Base driver for file-based metadata drivers.
 *
 * A file driver operates in a mode where it loads the mapping files of individual
 * classes on demand. This requires the user to adhere to the convention of 1 mapping
 * file per class and the file names of the mapping files must correspond to the full
 * class name, including namespace, with the namespace delimiters '\', replaced by dots '.'.
 *
 * @template T
 */
abstract class File_Driver implements Mapping_Driver
{
    protected File_Locator $locator;
    /**
     * @var mixed[]|null
     * @phpstan-var array<class-string, T>|null
     */
    protected array|null $class_cache = null;
    protected string $global_basename = '';
    /**
     * Initializes a new FileDriver that looks in the given path(s) for mapping
     * documents and operates in the specified operating mode.
     *
     * @param string|array<int, string>|FileLocator $locator A FileLocator or one/multiple paths
     *                                                       where mapping documents can be found.
     */
    public function __construct(string|array|File_Locator $locator, string|null $file_extension = null)
    {
        if ($locator instanceof File_Locator) {
            $this->locator = $locator;
        } else {
            $this->locator = new Default_File_Locator((array) $locator, $file_extension);
        }
    }
    /** Sets the global basename. */
    public function set_global_basename(string $file): void
    {
        $this->global_basename = $file;
    }
    /** Retrieves the global basename. */
    public function get_global_basename(): string
    {
        return $this->global_basename;
    }
    /**
     * Gets the element of schema meta data for the class from the mapping file.
     * This will lazily load the mapping file if it is not loaded yet.
     *
     * @phpstan-param class-string $className
     *
     * @return T The element of schema meta data.
     *
     * @throws MappingException
     */
    public function get_element(string $class_name): mixed
    {
        if ($this->class_cache === null) {
            $this->initialize();
        }
        if (isset($this->class_cache[$class_name])) {
            return $this->class_cache[$class_name];
        }
        $result = $this->load_mapping_file($this->locator->find_mapping_file($class_name));
        if (!isset($result[$class_name])) {
            throw Mapping_Exception::invalid_mapping_file($class_name, str_replace('\\', '.', $class_name) . $this->locator->get_file_extension());
        }
        $this->class_cache[$class_name] = $result[$class_name];
        return $result[$class_name];
    }
    public function is_transient(string $class_name): bool
    {
        if ($this->class_cache === null) {
            $this->initialize();
        }
        if (isset($this->class_cache[$class_name])) {
            return false;
        }
        return !$this->locator->file_exists($class_name);
    }
    /**
     * {@inheritDoc}
     */
    public function get_all_class_names(): array
    {
        if ($this->class_cache === null) {
            $this->initialize();
        }
        if ($this->class_cache === []) {
            return $this->locator->get_all_class_names($this->global_basename);
        }
        /** @phpstan-var non-empty-array<class-string, T> $classCache */
        $class_cache = $this->class_cache;
        /** @var list<class-string> $keys */
        $keys = array_keys($class_cache);
        return array_values(array_unique([...$keys, ...$this->locator->get_all_class_names($this->global_basename)]));
    }
    /**
     * Loads a mapping file with the given name and returns a map
     * from class/entity names to their corresponding file driver elements.
     *
     * @param string $file The mapping file to load.
     *
     * @return mixed[]
     * @phpstan-return array<class-string, T>
     */
    abstract protected function load_mapping_file(string $file): array;
    /**
     * Initializes the class cache from all the global files.
     *
     * Using this feature adds a substantial performance hit to file drivers as
     * more metadata has to be loaded into memory than might actually be
     * necessary. This may not be relevant to scenarios where caching of
     * metadata is in place, however hits very hard in scenarios where no
     * caching is used.
     */
    protected function initialize(): void
    {
        $this->class_cache = [];
        if ($this->global_basename === '') {
            return;
        }
        foreach ($this->locator->get_paths() as $path) {
            $file = $path . '/' . $this->global_basename . $this->locator->get_file_extension();
            if (!is_file($file)) {
                continue;
            }
            $this->class_cache = [...$this->class_cache, ...$this->load_mapping_file($file)];
        }
    }
    /** Retrieves the locator used to discover mapping files by className. */
    public function get_locator(): File_Locator
    {
        return $this->locator;
    }
    /** Sets the locator used to discover mapping files by className. */
    public function set_locator(File_Locator $locator): void
    {
        $this->locator = $locator;
    }
}