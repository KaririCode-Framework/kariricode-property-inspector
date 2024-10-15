<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Contract;

interface PropertyAttributeHandler
{
    /**
     * Handles an attribute found on a property.
     *
     * @param string $propertyName The name of the property
     * @param object $attribute The found attribute
     * @param mixed $value The property value
     *
     * @return mixed The result of handling the attribute
     */
    public function handleAttribute(string $propertyName, object $attribute, mixed $value): mixed;
}
