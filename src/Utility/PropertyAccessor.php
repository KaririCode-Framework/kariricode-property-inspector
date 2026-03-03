<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Utility;

/**
 * Provides read/write access to object properties via reflection,
 * regardless of visibility (public, protected, private).
 *
 * Since PHP 8.1, ReflectionProperty::setAccessible() is a no-op —
 * all properties are accessible via reflection without explicit calls.
 */
readonly class PropertyAccessor
{
    private \ReflectionProperty $reflectionProperty;

    public function __construct(private object $object, string $propertyName)
    {
        $this->reflectionProperty = new \ReflectionProperty($this->object, $propertyName);
    }

    public function getValue(): mixed
    {
        return $this->reflectionProperty->getValue($this->object);
    }

    public function setValue(mixed $value): void
    {
        $this->reflectionProperty->setValue($this->object, $value);
    }
}
