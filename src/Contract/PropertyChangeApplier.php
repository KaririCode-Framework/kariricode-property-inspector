<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Contract;

/**
 * Applies processed attribute handler results back to the inspected object's properties.
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
