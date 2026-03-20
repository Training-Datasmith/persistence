<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Reflection;

use Doctrine\Persistence\Proxy;
use function ltrim;
use function method_exists;
use ReflectionProperty;
/**
 * PHP Runtime Reflection Property.
 *
 * Avoids triggering lazy loading if the provided object
 * is a {@see \Doctrine\Persistence\Proxy}.
 */
class Runtime_Reflection_Property extends ReflectionProperty
{
    private readonly string $key;
    /** @param class-string $class */
    public function __construct(string $class, string $name)
    {
        parent::__construct($class, $name);
        $this->key = $this->is_private() ? "\x00" . ltrim($class, '\\') . "\x00" . $name : ($this->is_protected() ? "\x00*\x00" . $name : $name);
    }
    public function get_value(object|null $object = null): mixed
    {
        if ($object === null) {
            return parent::get_value($object);
        }
        return ((array) $object)[$this->key] ?? null;
    }
    /**
     * {@inheritDoc}
     *
     * @param object|null $object
     */
    public function set_value(mixed $object, mixed $value = null): void
    {
        if (!($object instanceof Proxy && !$object->__is_initialized())) {
            parent::set_value($object, $value);
            return;
        }
        if (!method_exists($object, '__setInitialized')) {
            return;
        }
        $object->__set_initialized(true);
        parent::set_value($object, $value);
        $object->__set_initialized(false);
    }
}