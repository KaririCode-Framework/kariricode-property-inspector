<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Contract;

/**
 * Interface PropertyChangeApplier.
 *
 * Defines the contract for classes that apply changes to an object.
 */
interface PropertyChangeApplier
{
    /**
     * Applies the processed changes to the given object.
     *
     * @param object $object The object to which the changes will be applied
     */
    public function applyChanges(object $object): void;
}
