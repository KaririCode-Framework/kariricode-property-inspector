<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Utility;

readonly class PropertyAccessor
{
    private \ReflectionProperty $reflectionProperty;
    private bool $wasAccessible;

    public function __construct(private object $object, string $propertyName)
    {
        $this->reflectionProperty = new \ReflectionProperty($this->object, $propertyName);
        $this->wasAccessible = $this->reflectionProperty->isPublic();
    }

    public function getValue(): mixed
    {
        $this->makeAccessible();
        $value = $this->reflectionProperty->getValue($this->object);
        $this->restoreAccessibility();

        return $value;
    }

    public function setValue(mixed $value): void
    {
        $this->makeAccessible();
        $this->reflectionProperty->setValue($this->object, $value);
        $this->restoreAccessibility();
    }

    private function makeAccessible(): void
    {
        if (!$this->wasAccessible) {
            $this->reflectionProperty->setAccessible(true);
        }
    }

    private function restoreAccessibility(): void
    {
        if (!$this->wasAccessible) {
            $this->reflectionProperty->setAccessible(false);
        }
    }
}
