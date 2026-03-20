<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping\Driver;

use function array_unique;
use function assert;
use const DIRECTORY_SEPARATOR;
use Doctrine\Persistence\Mapping\Mapping_Exception;
use function is_dir;
use function is_file;
use function is_string;
use Recursive_Directory_Iterator;
use Recursive_Iterator_Iterator;
use function str_replace;
/**
 * Locates the file that contains the metadata information for a given class name.
 *
 * This behavior is independent of the actual content of the file. It just detects
 * the file which is responsible for the given class name.
 */
class Default_File_Locator implements File_Locator
{
    /**
     * The paths where to look for mapping files.
     *
     * @var array<int, string>
     */
    protected array $paths = [];
    /**
     * Initializes a new FileDriver that looks in the given path(s) for mapping
     * documents and operates in the specified operating mode.
     *
     * @param string|array<int, string> $paths         One or multiple paths where mapping documents
     *                                                 can be found.
     * @param string|null               $fileExtension The file extension of mapping documents,
     *                                                 usually prefixed with a dot.
     */
    public function __construct(string|array $paths, protected string|null $file_extension = null)
    {
        $this->add_paths((array) $paths);
    }
    /**
     * Appends lookup paths to metadata driver.
     *
     * @param array<int, string> $paths
     */
    public function add_paths(array $paths): void
    {
        $this->paths = array_unique([...$this->paths, ...$paths]);
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
    /** Gets the file extension used to look for mapping files under. */
    public function get_file_extension(): string|null
    {
        return $this->file_extension;
    }
    /**
     * Sets the file extension used to look for mapping files under.
     *
     * @param string|null $fileExtension The file extension to set.
     */
    public function set_file_extension(string|null $file_extension): void
    {
        $this->file_extension = $file_extension;
    }
    public function find_mapping_file(string $class_name): string
    {
        $file_name = str_replace('\\', '.', $class_name) . $this->file_extension;
        // Check whether file exists
        foreach ($this->paths as $path) {
            if (is_file($path . DIRECTORY_SEPARATOR . $file_name)) {
                return $path . DIRECTORY_SEPARATOR . $file_name;
            }
        }
        throw Mapping_Exception::mapping_file_not_found($class_name, $file_name);
    }
    /**
     * {@inheritDoc}
     */
    public function get_all_class_names(string $global_basename): array
    {
        if ($this->paths === []) {
            return [];
        }
        $classes = [];
        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                throw Mapping_Exception::file_mapping_drivers_require_configured_directory_path($path);
            }
            $iterator = new Recursive_Iterator_Iterator(new Recursive_Directory_Iterator($path), Recursive_Iterator_Iterator::LEAVES_ONLY);
            foreach ($iterator as $file) {
                $file_name = $file->get_basename($this->file_extension);
                if ($file_name === $file->get_basename()) {
                    continue;
                }
                if ($file_name === $global_basename) {
                    continue;
                }
                // NOTE: All files found here means classes are not transient!
                assert(is_string($file_name));
                /** @phpstan-var class-string */
                $class = str_replace('.', '\\', $file_name);
                $classes[] = $class;
            }
        }
        return $classes;
    }
    public function file_exists(string $class_name): bool
    {
        $file_name = str_replace('\\', '.', $class_name) . $this->file_extension;
        // Check whether file exists
        foreach ($this->paths as $path) {
            if (is_file($path . DIRECTORY_SEPARATOR . $file_name)) {
                return true;
            }
        }
        return false;
    }
}