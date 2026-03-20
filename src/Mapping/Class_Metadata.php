<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping;

use ReflectionClass;
/**
 * Contract for a Doctrine persistence layer ClassMetadata class to implement.
 *
 * @template-covariant T of object
 */
interface Class_Metadata
{
    /**
     * Gets the fully-qualified class name of this persistent class.
     *
     * @phpstan-return class-string<T>
     */
    public function get_name(): string;
    /**
     * Gets the mapped identifier field name.
     *
     * The returned structure is an array of the identifier field names.
     *
     * @return array<int, string>
     * @phpstan-return list<string>
     */
    public function get_identifier(): array;
    /**
     * Gets the ReflectionClass instance for this mapped class.
     *
     * @return ReflectionClass<covariant T>
     */
    public function get_reflection_class(): ReflectionClass;
    /** Checks if the given field name is a mapped identifier for this class. */
    public function is_identifier(string $field_name): bool;
    /** Checks if the given field is a mapped property for this class. */
    public function has_field(string $field_name): bool;
    /** Checks if the given field is a mapped association for this class. */
    public function has_association(string $field_name): bool;
    /** Checks if the given field is a mapped single valued association for this class. */
    public function is_single_valued_association(string $field_name): bool;
    /** Checks if the given field is a mapped collection valued association for this class. */
    public function is_collection_valued_association(string $field_name): bool;
    /**
     * A numerically indexed list of field names of this persistent class.
     *
     * This array includes identifier fields if present on this class.
     *
     * @return array<int, string>
     */
    public function get_field_names(): array;
    /**
     * Returns an array of identifier field names numerically indexed.
     *
     * @return array<int, string>
     */
    public function get_identifier_field_names(): array;
    /**
     * Returns a numerically indexed list of association names of this persistent class.
     *
     * This array includes identifier associations if present on this class.
     *
     * @return array<int, string>
     */
    public function get_association_names(): array;
    /**
     * Returns a type name of this field.
     *
     * This type names can be implementation specific but should at least include the php types:
     * integer, string, boolean, float/double, datetime.
     */
    public function get_type_of_field(string $field_name): string|null;
    /**
     * Returns the target class name of the given association.
     *
     * @phpstan-return class-string|null
     */
    public function get_association_target_class(string $assoc_name): string|null;
    /** Checks if the association is the inverse side of a bidirectional association. */
    public function is_association_inverse_side(string $assoc_name): bool;
    /** Returns the target field of the owning side of the association. */
    public function get_association_mapped_by_target_field(string $assoc_name): string;
    /**
     * Returns the identifier of this object as an array with field name as key.
     *
     * Has to return an empty array if no identifier isset.
     *
     * @return array<string, mixed>
     */
    public function get_identifier_values(object $object): array;
}