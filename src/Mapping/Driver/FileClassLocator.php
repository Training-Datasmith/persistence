<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping\Driver;

use Append_Iterator;
use function array_key_exists;
use function array_map;
use function assert;
use Callback_Filter_Iterator;
use Doctrine\Persistence\Mapping\Mapping_Exception;
use Filesystem_Iterator;
use function get_debug_type;
use function get_declared_classes;
use InvalidArgumentException;
use function is_dir;
use Iterator;
use function preg_quote;
use function realpath;
use Recursive_Directory_Iterator;
use Recursive_Iterator_Iterator;
use ReflectionClass;
use Regex_Iterator;
use Spl_File_Info;
use function sprintf;
use function str_replace;
use function str_starts_with;
/**
 * ClassLocator implementation that uses a list of file names to locate PHP files
 * and extract class names from them.
 *
 * It is compatible with the Symfony Finder component, but does not require it.
 */
final class File_Class_Locator implements Class_Locator
{
    /** @param iterable<SplFileInfo> $files An iterable of files to include. */
    public function __construct(private readonly iterable $files)
    {
    }
    /** @return list<class-string> */
    public function get_class_names(): array
    {
        $included_files = [];
        foreach ($this->files as $file) {
            // @phpstan-ignore function.alreadyNarrowedType, instanceof.alwaysTrue
            assert($file instanceof Spl_File_Info, new InvalidArgumentException(sprintf('Expected an iterable of SplFileInfo, got %s', get_debug_type($file))));
            // Skip non-files
            if (!$file->is_file()) {
                continue;
            }
            // getRealPath() returns false if the file is in a phar archive
            // @phpstan-ignore ternary.shortNotAllowed (false is the only falsy value getRealPath() may return)
            $file_name = $file->get_real_path() ?: $file->get_pathname();
            $included_files[$file_name] = true;
            require_once $file_name;
        }
        $classes = [];
        foreach (get_declared_classes() as $class_name) {
            $file_name = (new ReflectionClass($class_name))->get_file_name();
            if ($file_name === false) {
                continue;
            }
            if (!array_key_exists($file_name, $included_files)) {
                continue;
            }
            $classes[] = $class_name;
        }
        return $classes;
    }
    /**
     * Creates a FileClassLocator from an array of directories.
     *
     * @param list<string> $directories
     * @param list<string> $excludedDirectories Directories to exclude from the search.
     * @param string       $fileExtension       The file extension to look for (default is '.php').
     *
     * @throws MappingException if any of the directories are not valid.
     */
    public static function create_from_directories(array $directories, array $excluded_directories = [], string $file_extension = '.php'): self
    {
        $files_iterator = new Append_Iterator();
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                throw Mapping_Exception::file_mapping_drivers_require_configured_directory_path($directory);
            }
            /** @var Iterator<array-key,SplFileInfo> $iterator */
            $iterator = new Regex_Iterator(new Recursive_Iterator_Iterator(new Recursive_Directory_Iterator($directory, Filesystem_Iterator::SKIP_DOTS), Recursive_Iterator_Iterator::LEAVES_ONLY), sprintf('/%s$/', preg_quote($file_extension, '/')), Regex_Iterator::MATCH);
            $files_iterator->append($iterator);
        }
        if ($excluded_directories !== []) {
            $excluded_directories = array_map(
                // realpath() returns false if the file is in a phar archive
                // @phpstan-ignore ternary.shortNotAllowed (false is the only falsy value realpath() may return)
                static fn(string $dir): string => str_replace('\\', '/', realpath($dir) ?: $dir),
                $excluded_directories
            );
            $files_iterator = new Callback_Filter_Iterator($files_iterator, static function (Spl_File_Info $file) use ($excluded_directories): bool {
                // getRealPath() returns false if the file is in a phar archive
                // @phpstan-ignore ternary.shortNotAllowed (false is the only falsy value getRealPath() may return)
                $source_file = str_replace('\\', '/', $file->get_real_path() ?: $file->get_pathname());
                foreach ($excluded_directories as $excluded_directory) {
                    if (str_starts_with($source_file, $excluded_directory)) {
                        return false;
                    }
                }
                return true;
            });
        }
        return new self($files_iterator);
    }
}