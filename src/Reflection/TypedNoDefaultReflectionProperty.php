<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Reflection;

use function assert;
use Closure;
/**
 * PHP Typed No Default Reflection Property - special override for typed properties without a default value.
 */
class Typed_No_Default_Reflection_Property extends Runtime_Reflection_Property
{
    /**
     * {@inheritDoc}
     *
     * Checks that a typed property is initialized before accessing its value.
     * This is necessary to avoid PHP error "Error: Typed property must not be accessed before initialization".
     * Should be used only for reflecting typed properties without a default value.
     */
    public function get_value(object|null $object = null): mixed
    {
        return $object !== null && $this->is_initialized($object) ? parent::get_value($object) : null;
    }
    /**
     * {@inheritDoc}
     *
     * Works around the problem with setting typed no default properties to
     * NULL which is not supported, instead unset() to uninitialize.
     *
     * @link https://github.com/doctrine/orm/issues/7999
     *
     * @param object|null $object
     */
    public function set_value(mixed $object, mixed $value = null): void
    {
        if ($value === null && $this->has_type() && !$this->get_type()->allows_null()) {
            $property_name = $this->get_name();
            $unsetter = function () use ($property_name): void {
                unset($this->{$property_name});
            };
            $unsetter = $unsetter->bind_to($object, $this->get_declaring_class()->get_name());
            assert($unsetter instanceof Closure);
            $unsetter();
            return;
        }
        parent::set_value($object, $value);
    }
}