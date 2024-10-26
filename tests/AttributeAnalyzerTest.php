<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests;

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
        $attributeClass = 'FakeAttributeClass';
        $analyzer = new AttributeAnalyzer($attributeClass);
        $object = new class {
            private $inaccessibleProperty;

            public function __construct()
            {
                $this->inaccessibleProperty = null;
            }
        };

        $this->expectException(PropertyInspectionException::class);
        $this->expectExceptionCode(2503); // CODE_GENERAL_ANALYSIS_ERROR
        $this->expectExceptionMessage('An error occurred during object analysis: Class "FakeAttributeClass" not found');

        $analyzer->analyzeObject($object);
    }

    public function testErrorThrownDuringAnalyzeProperty(): void
    {
        $attributeClass = 'FakeAttributeClass';
        $analyzer = new AttributeAnalyzer($attributeClass);
        $object = new class {
            private $errorProperty;

            public function __construct()
            {
                $this->errorProperty = null;
            }
        };

        $this->expectException(PropertyInspectionException::class);
        $this->expectExceptionCode(2503); // CODE_GENERAL_ANALYSIS_ERROR
        $this->expectExceptionMessage('An error occurred during object analysis: Class "FakeAttributeClass" not found');

        $analyzer->analyzeObject($object);
    }

    public function testClearCacheInvocation(): void
    {
        $object = new class {
            #[TestAttribute]
            public string $testProperty = 'test value';
        };

        $this->analyzer->analyzeObject($object);

        $reflection = new \ReflectionClass(AttributeAnalyzer::class);
        $cacheProperty = $reflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $this->assertNotEmpty($cacheProperty->getValue($this->analyzer));
        $this->analyzer->clearCache();

        $this->assertEmpty($cacheProperty->getValue($this->analyzer));

        $result = $this->analyzer->analyzeObject($object);
        $this->assertNotEmpty($result);
    }
}
