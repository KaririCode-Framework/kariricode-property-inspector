<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests\Exception;

use KaririCode\PropertyInspector\Contract\AttributeAnalyzer as AttributeAnalyzerInterface;
use KaririCode\PropertyInspector\Contract\PropertyAttributeHandler;
use KaririCode\PropertyInspector\Exception\PropertyInspectionException;
use KaririCode\PropertyInspector\PropertyInspector;
use PHPUnit\Framework\TestCase;

class ReflectionExceptionTest extends TestCase
{
    public function testReflectionExceptionThrownDuringInspection(): void
    {
        $attributeAnalyzer = $this->createMock(AttributeAnalyzerInterface::class);
        $handler = $this->createMock(PropertyAttributeHandler::class);
        $inspector = new PropertyInspector($attributeAnalyzer);

        $object = new \stdClass();

        $attributeAnalyzer->method('analyzeObject')
            ->willThrowException(new \ReflectionException('Simulated ReflectionException'));

        $this->expectException(PropertyInspectionException::class);
        $this->expectExceptionMessage('Failed to analyze object: Simulated ReflectionException');

        $inspector->inspect($object, $handler);
    }

    public function testReflectionExceptionThrownDuringAnalyzeObject(): void
    {
        $attributeAnalyzer = $this->createMock(AttributeAnalyzerInterface::class);
        $object = new \stdClass();

        $attributeAnalyzer->method('analyzeObject')
            ->willThrowException(new PropertyInspectionException('Failed to analyze property: Class "FakeAttributeClass" not found'));

        $this->expectException(PropertyInspectionException::class);
        $this->expectExceptionMessage('Failed to analyze property: Class "FakeAttributeClass" not found');

        $attributeAnalyzer->analyzeObject($object);
    }

    public function testReflectionExceptionInAnalyzeObject(): void
    {
        $attributeAnalyzer = $this->createMock(AttributeAnalyzerInterface::class);
        $object = new \stdClass();

        $attributeAnalyzer->method('analyzeObject')
            ->willThrowException(new \ReflectionException('Test ReflectionException'));

        $inspector = new PropertyInspector($attributeAnalyzer);
        $handler = $this->createMock(PropertyAttributeHandler::class);

        $this->expectException(PropertyInspectionException::class);
        $this->expectExceptionMessage('Failed to analyze object: Test ReflectionException');

        $inspector->inspect($object, $handler);
    }

    public function testErrorInAnalyzeObject(): void
    {
        $attributeAnalyzer = $this->createMock(AttributeAnalyzerInterface::class);
        $object = new \stdClass();

        $attributeAnalyzer->method('analyzeObject')
            ->willThrowException(new \Error('Test Error'));

        $inspector = new PropertyInspector($attributeAnalyzer);
        $handler = $this->createMock(PropertyAttributeHandler::class);

        $this->expectException(PropertyInspectionException::class);
        $this->expectExceptionMessage('An error occurred during object analysis: Test Error');

        $inspector->inspect($object, $handler);
    }

    public function testErrorThrownDuringInspection(): void
    {
        $attributeAnalyzer = $this->createMock(AttributeAnalyzerInterface::class);
        $handler = $this->createMock(PropertyAttributeHandler::class);
        $inspector = new PropertyInspector($attributeAnalyzer);

        $object = new \stdClass();

        $attributeAnalyzer->method('analyzeObject')
            ->willThrowException(new \Error('Simulated Error'));

        $this->expectException(PropertyInspectionException::class);
        $this->expectExceptionMessage('An error occurred during object analysis: Simulated Error');

        $inspector->inspect($object, $handler);
    }
}
