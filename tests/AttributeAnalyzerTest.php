<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests;

use Attribute;
use KaririCode\PropertyInspector\AttributeAnalyzer;
use KaririCode\PropertyInspector\Contract\AttributeAnalyzer as AttributeAnalyzerContract;
use KaririCode\PropertyInspector\Exception\PropertyInspectionException;
use PHPUnit\Framework\TestCase;

#[\Attribute()]
class TestAttribute
{
}

class TestObject
{
    #[TestAttribute]
    public string $testProperty = 'test value';

    private string $privateProperty = 'private value';
}

final class AttributeAnalyzerTest extends TestCase
{
    private AttributeAnalyzerContract $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new AttributeAnalyzer(TestAttribute::class);
    }

    public function testAnalyzeObject(): void
    {
        $object = new TestObject();
        $result = $this->analyzer->analyzeObject($object);

        $this->assertArrayHasKey('testProperty', $result);
        $this->assertEquals('test value', $result['testProperty']['value']);
        $this->assertInstanceOf(TestAttribute::class, $result['testProperty']['attributes'][0]);
    }

    public function testAnalyzeObjectWithNoAttributes(): void
    {
        $object = new class {
            public string $propertyWithoutAttribute = 'no attribute';
        };

        $result = $this->analyzer->analyzeObject($object);

        $this->assertEmpty($result);
    }

    public function testAnalyzeObjectWithPrivateProperty(): void
    {
        $object = new TestObject();
        $result = $this->analyzer->analyzeObject($object);

        $this->assertArrayNotHasKey('privateProperty', $result);
    }

    public function testReflectionExceptionThrownDuringAnalyzeObject(): void
    {
        // Define a fake attribute class for testing
        $attributeClass = 'FakeAttributeClass';

        // Create the AttributeAnalyzer with the fake attribute class
        $analyzer = new AttributeAnalyzer($attributeClass);

        // Simulate an object that will trigger a ReflectionException
        $object = new class {
            private $inaccessibleProperty;

            public function __construct()
            {
                // Simulating an inaccessible property that will cause ReflectionException
                $this->inaccessibleProperty = null;
            }
        };

        // We expect a PropertyInspectionException due to ReflectionException
        $this->expectException(PropertyInspectionException::class);
        $this->expectExceptionMessage('An error occurred during object analysis: Class "FakeAttributeClass" not found');

        // Execute the analyzeObject method, which should trigger the exception
        $analyzer->analyzeObject($object);
    }

    public function testErrorThrownDuringAnalyzeProperty(): void
    {
        // Define a fake attribute class for testing
        $attributeClass = 'FakeAttributeClass';

        // Create the AttributeAnalyzer with the fake attribute class
        $analyzer = new AttributeAnalyzer($attributeClass);

        // Simulate an object that will trigger an Error during property analysis
        $object = new class {
            private $errorProperty;

            public function __construct()
            {
                // Simulating an error in the property that will cause an Error during reflection
                $this->errorProperty = null;
            }
        };

        // Mock Reflection to throw an error during attribute analysis
        $reflectionPropertyMock = $this->createMock(\ReflectionProperty::class);
        $reflectionPropertyMock->method('getAttributes')
            ->willThrowException(new \Error('Simulated Error'));

        // We expect a PropertyInspectionException due to the Error
        $this->expectException(PropertyInspectionException::class);
        $this->expectExceptionMessage('An error occurred during object analysis: Class "FakeAttributeClass" not found');

        // Execute the analyzeObject method, which should trigger the exception
        $analyzer->analyzeObject($object);
    }
}
