<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping;

use function array_combine;
use function array_keys;
use function array_map;
use function array_reverse;
use function array_unshift;
use function assert;
use function class_exists;
use Doctrine\Persistence\Mapping\Driver\Mapping_Driver;
use Doctrine\Persistence\Proxy;
use function ltrim;
use Psr\Cache\Cache_Item_Pool_Interface;
use ReflectionClass;
use Reflection_Exception;
use function str_contains;
use function str_replace;
use function strrpos;
use function substr;
/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping informations of a class which describes how a class should be mapped
 * to a relational database.
 *
 * This class was abstracted from the ORM ClassMetadataFactory.
 *
 * @template CMTemplate of ClassMetadata
 * @template-implements ClassMetadataFactory<CMTemplate>
 */
abstract class Abstract_Class_Metadata_Factory implements Class_Metadata_Factory
{
    /** Salt used by specific Object Manager implementation. */
    protected string $cache_salt = '__CLASSMETADATA__';
    private Cache_Item_Pool_Interface|null $cache = null;
    /**
     * @var array<string, ClassMetadata>
     * @phpstan-var CMTemplate[]
     */
    private array $loaded_metadata = [];
    protected bool $initialized = false;
    private Reflection_Service|null $reflection_service = null;
    private Proxy_Class_Name_Resolver|null $proxy_class_name_resolver = null;
    public function set_cache(Cache_Item_Pool_Interface $cache): void
    {
        $this->cache = $cache;
    }
    final protected function get_cache(): Cache_Item_Pool_Interface|null
    {
        return $this->cache;
    }
    /**
     * Returns an array of all the loaded metadata currently in memory.
     *
     * @return ClassMetadata[]
     * @phpstan-return CMTemplate[]
     */
    public function get_loaded_metadata(): array
    {
        return $this->loaded_metadata;
    }
    /**
     * {@inheritDoc}
     */
    public function get_all_metadata(): array
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $driver = $this->get_driver();
        $metadata = [];
        foreach ($driver->get_all_class_names() as $class_name) {
            $metadata[] = $this->get_metadata_for($class_name);
        }
        return $metadata;
    }
    public function set_proxy_class_name_resolver(Proxy_Class_Name_Resolver $resolver): void
    {
        $this->proxy_class_name_resolver = $resolver;
    }
    /**
     * Lazy initialization of this stuff, especially the metadata driver,
     * since these are not needed at all when a metadata cache is active.
     */
    abstract protected function initialize(): void;
    /** Returns the mapping driver implementation. */
    abstract protected function get_driver(): Mapping_Driver;
    /**
     * Wakes up reflection after ClassMetadata gets unserialized from cache.
     *
     * @phpstan-param CMTemplate $class
     */
    abstract protected function wakeup_reflection(Class_Metadata $class, Reflection_Service $refl_service): void;
    /**
     * Initializes Reflection after ClassMetadata was constructed.
     *
     * @phpstan-param CMTemplate $class
     */
    abstract protected function initialize_reflection(Class_Metadata $class, Reflection_Service $refl_service): void;
    /**
     * Checks whether the class metadata is an entity.
     *
     * This method should return false for mapped superclasses or embedded classes.
     *
     * @phpstan-param CMTemplate $class
     */
    abstract protected function is_entity(Class_Metadata $class): bool;
    /**
     * Removes the prepended backslash of a class string to conform with how php outputs class names
     *
     * @phpstan-param class-string $className
     *
     * @phpstan-return class-string
     */
    private function normalize_class_name(string $class_name): string
    {
        return ltrim($class_name, '\\');
    }
    /**
     * {@inheritDoc}
     *
     * @throws ReflectionException
     * @throws MappingException
     */
    public function get_metadata_for(string $class_name): Class_Metadata
    {
        $class_name = $this->normalize_class_name($class_name);
        if (isset($this->loaded_metadata[$class_name])) {
            return $this->loaded_metadata[$class_name];
        }
        if (class_exists($class_name, false) && (new ReflectionClass($class_name))->is_anonymous()) {
            throw Mapping_Exception::class_is_anonymous($class_name);
        }
        if (!class_exists($class_name, false) && str_contains($class_name, ':')) {
            throw Mapping_Exception::non_existing_class($class_name);
        }
        $real_class_name = $this->get_real_class($class_name);
        if (isset($this->loaded_metadata[$real_class_name])) {
            // We do not have the alias name in the map, include it
            return $this->loaded_metadata[$class_name] = $this->loaded_metadata[$real_class_name];
        }
        try {
            if ($this->cache !== null) {
                $cached = $this->cache->get_item($this->get_cache_key($real_class_name))->get();
                if ($cached instanceof Class_Metadata) {
                    /** @phpstan-var CMTemplate $cached */
                    $this->loaded_metadata[$real_class_name] = $cached;
                    $this->wakeup_reflection($cached, $this->get_reflection_service());
                } else {
                    $loaded_metadata = $this->load_metadata($real_class_name);
                    $class_names = array_combine(array_map($this->get_cache_key(...), $loaded_metadata), $loaded_metadata);
                    foreach ($this->cache->get_items(array_keys($class_names)) as $item) {
                        if (!isset($class_names[$item->get_key()])) {
                            continue;
                        }
                        $item->set($this->loaded_metadata[$class_names[$item->get_key()]]);
                        $this->cache->save_deferred($item);
                    }
                    $this->cache->commit();
                }
            } else {
                $this->load_metadata($real_class_name);
            }
        } catch (Mapping_Exception $loading_exception) {
            $fallback_metadata_response = $this->on_not_found_metadata($real_class_name);
            if ($fallback_metadata_response === null) {
                throw $loading_exception;
            }
            $this->loaded_metadata[$real_class_name] = $fallback_metadata_response;
        }
        if ($class_name !== $real_class_name) {
            // We do not have the alias name in the map, include it
            $this->loaded_metadata[$class_name] = $this->loaded_metadata[$real_class_name];
        }
        return $this->loaded_metadata[$class_name];
    }
    public function has_metadata_for(string $class_name): bool
    {
        $class_name = $this->normalize_class_name($class_name);
        return isset($this->loaded_metadata[$class_name]);
    }
    /**
     * Sets the metadata descriptor for a specific class.
     *
     * NOTE: This is only useful in very special cases, like when generating proxy classes.
     *
     * @phpstan-param class-string $className
     * @phpstan-param CMTemplate $class
     */
    public function set_metadata_for(string $class_name, Class_Metadata $class): void
    {
        $this->loaded_metadata[$this->normalize_class_name($class_name)] = $class;
    }
    /**
     * Gets an array of parent classes for the given entity class.
     *
     * @phpstan-param class-string $name
     *
     * @return string[]
     * @phpstan-return list<class-string>
     */
    protected function get_parent_classes(string $name): array
    {
        // Collect parent classes, ignoring transient (not-mapped) classes.
        $parent_classes = [];
        foreach (array_reverse($this->get_reflection_service()->get_parent_classes($name)) as $parent_class) {
            if ($this->get_driver()->is_transient($parent_class)) {
                continue;
            }
            $parent_classes[] = $parent_class;
        }
        return $parent_classes;
    }
    /**
     * Loads the metadata of the class in question and all it's ancestors whose metadata
     * is still not loaded.
     *
     * Important: The class $name does not necessarily exist at this point here.
     * Scenarios in a code-generation setup might have access to XML/YAML
     * Mapping files without the actual PHP code existing here. That is why the
     * {@see \Doctrine\Persistence\Mapping\ReflectionService} interface
     * should be used for reflection.
     *
     * @param string $name The name of the class for which the metadata should get loaded.
     * @phpstan-param class-string $name
     *
     * @return array<int, string>
     * @phpstan-return list<string>
     */
    protected function load_metadata(string $name): array
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $loaded = [];
        $parent_classes = $this->get_parent_classes($name);
        $parent_classes[] = $name;
        // Move down the hierarchy of parent classes, starting from the topmost class
        $parent = null;
        $root_entity_found = false;
        $visited = [];
        $refl_service = $this->get_reflection_service();
        foreach ($parent_classes as $class_name) {
            if (isset($this->loaded_metadata[$class_name])) {
                $parent = $this->loaded_metadata[$class_name];
                if ($this->is_entity($parent)) {
                    $root_entity_found = true;
                    array_unshift($visited, $class_name);
                }
                continue;
            }
            $class = $this->new_class_metadata_instance($class_name);
            $this->initialize_reflection($class, $refl_service);
            $this->do_load_metadata($class, $parent, $root_entity_found, $visited);
            $this->loaded_metadata[$class_name] = $class;
            $parent = $class;
            if ($this->is_entity($class)) {
                $root_entity_found = true;
                array_unshift($visited, $class_name);
            }
            $this->wakeup_reflection($class, $refl_service);
            $loaded[] = $class_name;
        }
        return $loaded;
    }
    /**
     * Provides a fallback hook for loading metadata when loading failed due to reflection/mapping exceptions
     *
     * Override this method to implement a fallback strategy for failed metadata loading
     *
     * @phpstan-return CMTemplate|null
     */
    protected function on_not_found_metadata(string $class_name): Class_Metadata|null
    {
        return null;
    }
    /**
     * Actually loads the metadata from the underlying metadata.
     *
     * @param bool               $rootEntityFound      True when there is another entity (non-mapped superclass) class above the current class in the PHP class hierarchy.
     * @param list<class-string> $nonSuperclassParents All parent class names that are not marked as mapped superclasses, with the direct parent class being the first and the root entity class the last element.
     * @phpstan-param CMTemplate $class
     * @phpstan-param CMTemplate|null $parent
     */
    abstract protected function do_load_metadata(Class_Metadata $class, Class_Metadata|null $parent, bool $root_entity_found, array $non_superclass_parents): void;
    /**
     * Creates a new ClassMetadata instance for the given class name.
     *
     * @phpstan-param class-string<T> $className
     *
     * @return ClassMetadata<T>
     * @phpstan-return CMTemplate
     *
     * @template T of object
     */
    abstract protected function new_class_metadata_instance(string $class_name): Class_Metadata;
    public function is_transient(string $class_name): bool
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        if (class_exists($class_name, false) && (new ReflectionClass($class_name))->is_anonymous()) {
            return false;
        }
        if (!class_exists($class_name, false) && str_contains($class_name, ':')) {
            throw Mapping_Exception::non_existing_class($class_name);
        }
        /** @phpstan-var class-string $className */
        return $this->get_driver()->is_transient($class_name);
    }
    /** Sets the reflectionService. */
    public function set_reflection_service(Reflection_Service $reflection_service): void
    {
        $this->reflection_service = $reflection_service;
    }
    /** Gets the reflection service associated with this metadata factory. */
    public function get_reflection_service(): Reflection_Service
    {
        if ($this->reflection_service === null) {
            $this->reflection_service = new Runtime_Reflection_Service();
        }
        return $this->reflection_service;
    }
    protected function get_cache_key(string $real_class_name): string
    {
        return str_replace('\\', '__', $real_class_name) . $this->cache_salt;
    }
    /**
     * Gets the real class name of a class name that could be a proxy.
     *
     * @phpstan-param class-string<Proxy<T>>|class-string<T> $class
     *
     * @phpstan-return class-string<T>
     *
     * @template T of object
     */
    private function get_real_class(string $class): string
    {
        if ($this->proxy_class_name_resolver === null) {
            $this->create_default_proxy_class_name_resolver();
        }
        assert($this->proxy_class_name_resolver !== null);
        return $this->proxy_class_name_resolver->resolve_class_name($class);
    }
    private function create_default_proxy_class_name_resolver(): void
    {
        $this->proxy_class_name_resolver = new class implements Proxy_Class_Name_Resolver
        {
            /**
             * @phpstan-param class-string<Proxy<T>>|class-string<T> $className
             *
             * @phpstan-return class-string<T>
             *
             * @template T of object
             */
            public function resolve_class_name(string $class_name): string
            {
                $pos = strrpos($class_name, '\\' . Proxy::MARKER . '\\');
                if ($pos === false) {
                    /** @phpstan-var class-string<T> */
                    return $class_name;
                }
                /** @phpstan-var class-string<T> */
                return substr($class_name, $pos + Proxy::MARKER_LENGTH + 2);
            }
        };
    }
}