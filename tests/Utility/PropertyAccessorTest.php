<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests\Utility;

use KaririCode\PropertyInspector\Utility\PropertyAccessor;
use PHPUnit\Framework\TestCase;

final class PropertyAccessorTest extends TestCase
{
    private object $object;
    private PropertyAccessor $propertyAccessor;

    protected function setUp(): void
    {
        // Removendo o tipo específico `stdClass` e utilizando uma classe anônima.
        $this->object = new class {
            private string $privateProperty = 'initial';
            public string $publicProperty = 'publicValue';
        };
    }

    public function testGetValueFromPrivateProperty(): void
    {
        $this->propertyAccessor = new PropertyAccessor($this->object, 'privateProperty');

        $value = $this->propertyAccessor->getValue();

        $this->assertSame('initial', $value);
    }

    public function testSetValueToPrivateProperty(): void
    {
        $this->propertyAccessor = new PropertyAccessor($this->object, 'privateProperty');
        $this->propertyAccessor->setValue('newValue');

        $value = $this->propertyAccessor->getValue();

        $this->assertSame('newValue', $value);
    }

    public function testGetValueFromPublicProperty(): void
    {
        $this->propertyAccessor = new PropertyAccessor($this->object, 'publicProperty');

        $value = $this->propertyAccessor->getValue();

        $this->assertSame('publicValue', $value);
    }

    public function testSetValueToPublicProperty(): void
    {
        $this->propertyAccessor = new PropertyAccessor($this->object, 'publicProperty');
        $this->propertyAccessor->setValue('updatedPublicValue');

        $value = $this->propertyAccessor->getValue();

        $this->assertSame('updatedPublicValue', $value);
    }

    public function testRestoresAccessibilityAfterGetValue(): void
    {
        $reflectionProperty = new \ReflectionProperty($this->object, 'privateProperty');
        $initialAccessibility = $reflectionProperty->isPublic();

        $this->propertyAccessor = new PropertyAccessor($this->object, 'privateProperty');
        $this->propertyAccessor->getValue();

        $finalAccessibility = $reflectionProperty->isPublic();

        $this->assertSame($initialAccessibility, $finalAccessibility, 'Accessibility should be restored after getValue');
    }

    public function testRestoresAccessibilityAfterSetValue(): void
    {
        $reflectionProperty = new \ReflectionProperty($this->object, 'privateProperty');
        $initialAccessibility = $reflectionProperty->isPublic();

        $this->propertyAccessor = new PropertyAccessor($this->object, 'privateProperty');
        $this->propertyAccessor->setValue('temporaryValue');

        $finalAccessibility = $reflectionProperty->isPublic();

        $this->assertSame($initialAccessibility, $finalAccessibility, 'Accessibility should be restored after setValue');
    }
}
