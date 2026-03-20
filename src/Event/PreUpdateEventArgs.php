<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Event;

use Doctrine\Persistence\Object_Manager;
use InvalidArgumentException;
use function sprintf;
/**
 * Class that holds event arguments for a preUpdate event.
 *
 * @template-covariant TObjectManager of ObjectManager
 * @extends LifecycleEventArgs<TObjectManager>
 */
class Pre_Update_Event_Args extends Lifecycle_Event_Args
{
    /** @var array<string, array<int, mixed>> */
    private array $entity_change_set;
    /**
     * @param array<string, array<int, mixed>> $changeSet
     * @phpstan-param TObjectManager $objectManager
     */
    public function __construct(object $entity, Object_Manager $object_manager, array &$change_set)
    {
        parent::__construct($entity, $object_manager);
        $this->entity_change_set =& $change_set;
    }
    /**
     * Retrieves the entity changeset.
     *
     * @return array<string, array<int, mixed>>
     */
    public function get_entity_change_set(): array
    {
        return $this->entity_change_set;
    }
    /** Checks if field has a changeset. */
    public function has_changed_field(string $field): bool
    {
        return isset($this->entity_change_set[$field]);
    }
    /** Gets the old value of the changeset of the changed field. */
    public function get_old_value(string $field): mixed
    {
        $this->assert_valid_field($field);
        return $this->entity_change_set[$field][0];
    }
    /** Gets the new value of the changeset of the changed field. */
    public function get_new_value(string $field): mixed
    {
        $this->assert_valid_field($field);
        return $this->entity_change_set[$field][1];
    }
    /** Sets the new value of this field. */
    public function set_new_value(string $field, mixed $value): void
    {
        $this->assert_valid_field($field);
        $this->entity_change_set[$field][1] = $value;
    }
    /**
     * Asserts the field exists in changeset.
     *
     * @throws InvalidArgumentException
     */
    private function assert_valid_field(string $field): void
    {
        if (!isset($this->entity_change_set[$field])) {
            throw new InvalidArgumentException(sprintf('Field "%s" is not a valid field of the entity "%s" in PreUpdateEventArgs.', $field, $this->get_object()::class));
        }
    }
}