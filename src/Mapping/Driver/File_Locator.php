<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping\Driver;

/**
 * Locates the file that contains the metadata information for a given class name.
 *
 * This behavior is independent of the actual content of the file. It just detects
 * the file which is responsible for the given class name.
 */
interface File_Locator
{
    /** Locates mapping file for the given class name. */
    public function find_mapping_file(string $class_name): string;
    /**
     * Gets all class names that are found with this file locator.
     *
     * @param string $globalBasename Passed to allow excluding the basename.
     *
     * @return array<int, string>
     * @phpstan-return list<class-string>
     */
    public function get_all_class_names(string $global_basename): array;
    /** Checks if a file can be found for this class name. */
    public function file_exists(string $class_name): bool;
    /**
     * Gets all the paths that this file locator looks for mapping files.
     *
     * @return array<int, string>
     */
    public function get_paths(): array;
    /** Gets the file extension that mapping files are suffixed with. */
    public function get_file_extension(): string|null;
}