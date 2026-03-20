<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping\Driver;

use function array_keys;
use function assert;
use const DIRECTORY_SEPARATOR;
use Doctrine\Persistence\Mapping\Mapping_Exception;
use InvalidArgumentException;
use function is_dir;
use function is_file;
use function is_int;
use function realpath;
use Recursive_Directory_Iterator;
use Recursive_Iterator_Iterator;
use RuntimeException;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strrpos;
use function strtr;
use function substr;
/**
 * The Symfony File Locator makes a simplifying assumptions compared
 * to the DefaultFileLocator. By assuming paths only contain entities of a certain
 * namespace the mapping files consists of the short classname only.
 */
class Symfony_File_Locator implements File_Locator
{
    /**
     * The paths where to look for mapping files.
     *
     * @var array<int, string>
     */
    protected array $paths = [];
    /**
     * A map of mapping directory path to namespace prefix used to expand class shortnames.
     *
     * @var array<string, string>
     */
    protected array $prefixes = [];
    /**
     * Represents PHP namespace delimiters when looking for files
     */
    private readonly string $ns_separator;
    /**
     * @param array<string, string> $prefixes
     * @param string                $nsSeparator String which would be used when converting FQCN
     *                                           to filename and vice versa. Should not be empty
     */
    public function __construct(
        array $prefixes,
        /** File extension that is searched for. */
        protected string|null $file_extension = '',
        string $ns_separator = '.'
    )
    {
        $this->add_namespace_prefixes($prefixes);
        if ($ns_separator === '') {
            throw new InvalidArgumentException('Namespace separator should not be empty');
        }
        $this->ns_separator = $ns_separator;
    }
    /**
     * Adds Namespace Prefixes.
     *
     * @param array<string, string> $prefixes
     */
    public function add_namespace_prefixes(array $prefixes): void
    {
        $this->prefixes = [...$this->prefixes, ...$prefixes];
        $this->paths = [...$this->paths, ...array_keys($prefixes)];
    }
    /**
     * Gets Namespace Prefixes.
     *
     * @return string[]
     */
    public function get_namespace_prefixes(): array
    {
        return $this->prefixes;
    }
    /**
     * {@inheritDoc}
     */
    public function get_paths(): array
    {
        return $this->paths;
    }
    public function get_file_extension(): string|null
    {
        return $this->file_extension;
    }
    /**
     * Sets the file extension used to look for mapping files under.
     *
     * @param string $fileExtension The file extension to set.
     */
    public function set_file_extension(string $file_extension): void
    {
        $this->file_extension = $file_extension;
    }
    public function file_exists(string $class_name): bool
    {
        $default_file_name = str_replace('\\', $this->ns_separator, $class_name) . $this->file_extension;
        foreach ($this->paths as $path) {
            if (!isset($this->prefixes[$path])) {
                // global namespace class
                if (is_file($path . DIRECTORY_SEPARATOR . $default_file_name)) {
                    return true;
                }
                continue;
            }
            $prefix = $this->prefixes[$path];
            if (!str_starts_with($class_name, $prefix . '\\')) {
                continue;
            }
            $filename = $path . '/' . strtr(substr($class_name, strlen($prefix) + 1), '\\', $this->ns_separator) . $this->file_extension;
            if (is_file($filename)) {
                return true;
            }
        }
        return false;
    }
    /**
     * {@inheritDoc}
     */
    public function get_all_class_names(string|null $global_basename = null): array
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
                if (isset($this->prefixes[$path])) {
                    // Calculate namespace suffix for given prefix as a relative path from basepath to file path
                    $ns_suffix = strtr(substr($this->realpath($file->get_path()), strlen($this->realpath($path))), $this->ns_separator, '\\');
                    /** @phpstan-var class-string */
                    $class = $this->prefixes[$path] . str_replace(DIRECTORY_SEPARATOR, '\\', $ns_suffix) . '\\' . str_replace($this->ns_separator, '\\', $file_name);
                } else {
                    /** @phpstan-var class-string */
                    $class = str_replace($this->ns_separator, '\\', $file_name);
                }
                $classes[] = $class;
            }
        }
        return $classes;
    }
    public function find_mapping_file(string $class_name): string
    {
        $default_file_name = str_replace('\\', $this->ns_separator, $class_name) . $this->file_extension;
        foreach ($this->paths as $path) {
            if (!isset($this->prefixes[$path])) {
                if (is_file($path . DIRECTORY_SEPARATOR . $default_file_name)) {
                    return $path . DIRECTORY_SEPARATOR . $default_file_name;
                }
                continue;
            }
            $prefix = $this->prefixes[$path];
            if (!str_starts_with($class_name, $prefix . '\\')) {
                continue;
            }
            $filename = $path . '/' . strtr(substr($class_name, strlen($prefix) + 1), '\\', $this->ns_separator) . $this->file_extension;
            if (is_file($filename)) {
                return $filename;
            }
        }
        $pos = strrpos($class_name, '\\');
        assert(is_int($pos));
        throw Mapping_Exception::mapping_file_not_found($class_name, substr($class_name, $pos + 1) . $this->file_extension);
    }
    private function realpath(string $path): string
    {
        $realpath = realpath($path);
        if ($realpath === false) {
            throw new RuntimeException(sprintf('Could not get realpath for %s', $path));
        }
        return $realpath;
    }
}