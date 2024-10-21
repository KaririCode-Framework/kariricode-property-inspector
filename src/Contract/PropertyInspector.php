<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Contract;

use KaririCode\PropertyInspector\Exception\PropertyInspectionException;

interface PropertyInspector
{
    /**
     * Inspects an object and processes its attributes.
     *
     * @param object $object The object to be inspected
     * @param PropertyAttributeHandler $handler The attribute handler
     *
     * @throws PropertyInspectionException If there's an error inspecting the object
     *
     * @return PropertyAttributeHandler The inspection results
     */
    public function inspect(object $object, PropertyAttributeHandler $handler): PropertyAttributeHandler;
}
