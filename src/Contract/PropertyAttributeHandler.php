<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Contract;

interface PropertyAttributeHandler
{
    /**
     * Handles an attribute found on a property.
     *
     * @param object $object The object being inspected
     * @param string $propertyName The name of the property
     * @param object $attribute The found attribute
     * @param mixed $value The property value
     *
     * @return mixed The result of handling the attribute
     */
    public function handleAttribute(object $object, string $propertyName, object $attribute, mixed $value): mixed;
}
