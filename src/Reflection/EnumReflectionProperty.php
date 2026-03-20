<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Reflection;

use function array_map;
use Backed_Enum;
use function is_array;
use ReflectionProperty;
use function reset;
/**
 * PHP Enum Reflection Property - special override for backed enums.
 */
class Enum_Reflection_Property extends ReflectionProperty
{
    /** @param class-string<BackedEnum> $enumType */
    public function __construct(private readonly ReflectionProperty $original_reflection_property, private readonly string $enum_type)
    {
        parent::__construct($original_reflection_property->class, $original_reflection_property->name);
    }
    /**
     * {@inheritDoc}
     *
     * Converts enum instance to its value.
     *
     * @param object|null $object
     *
     * @return int|string|int[]|string[]|null
     */
    public function get_value($object = null): int|string|array|null
    {
        if ($object === null) {
            return null;
        }
        $enum = $this->original_reflection_property->get_value($object);
        if ($enum === null) {
            return null;
        }
        return $this->from_enum($enum);
    }
    /**
     * Converts enum value to enum instance.
     *
     * @param object|null $object
     */
    public function set_value(mixed $object, mixed $value = null): void
    {
        if ($value !== null) {
            $value = $this->to_enum($value);
        }
        $this->original_reflection_property->set_value($object, $value);
    }
    /**
     * @param BackedEnum|BackedEnum[] $enum
     *
     * @return ($enum is BackedEnum ? (string|int) : (string[]|int[]))
     */
    private function from_enum(Backed_Enum|array $enum): array|int|string
    {
        if (is_array($enum)) {
            return array_map(static fn(Backed_Enum $enum): int|string => $enum->value, $enum);
        }
        return $enum->value;
    }
    /**
     * @param int|string|int[]|string[]|BackedEnum|BackedEnum[] $value
     *
     * @return ($value is int|string|BackedEnum ? BackedEnum : BackedEnum[])
     */
    private function to_enum(int|string|array|Backed_Enum $value)
    {
        if ($value instanceof Backed_Enum) {
            return $value;
        }
        if (is_array($value)) {
            $v = reset($value);
            if ($v instanceof Backed_Enum) {
                return $value;
            }
            return array_map([$this->enum_type, 'from'], $value);
        }
        return $this->enum_type::from($value);
    }
}