<?php

declare (strict_types=1);
namespace Doctrine\Persistence;

/**
 * Contract for classes that are potential listeners of a {@see NotifyPropertyChanged}
 * implementor.
 */
interface Property_Changed_Listener
{
    /**
     * Collect information about a property change.
     *
     * @param object $sender       The object on which the property changed.
     * @param string $propertyName The name of the property that changed.
     * @param mixed  $oldValue     The old value of the property that changed.
     * @param mixed  $newValue     The new value of the property that changed.
     */
    public function property_changed(object $sender, string $property_name, mixed $old_value, mixed $new_value): void;
}