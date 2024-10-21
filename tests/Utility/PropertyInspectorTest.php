<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests;

use KaririCode\PropertyInspector\Contract\AttributeAnalyzer;
use KaririCode\PropertyInspector\Contract\PropertyAttributeHandler;
use KaririCode\PropertyInspector\Contract\PropertyInspector as PropertyInspectorContract;
use KaririCode\PropertyInspector\Exception\PropertyInspectionException;
use KaririCode\PropertyInspector\Utility\PropertyInspector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PropertyInspectorTest extends TestCase
{
    private AttributeAnalyzer|MockObject $analyzer;
    private PropertyInspectorContract $inspector;

    protected function setUp(): void
    {
        $this->analyzer = $this->createMock(AttributeAnalyzer::class);
        $this->inspector = new PropertyInspector($this->analyzer);
    }

    public function testInspect(): void
    {
        $object = new \stdClass();
        $mockHandler = $this->createMock(PropertyAttributeHandler::class);

        $this->analyzer->expects($this->once())
            ->method('analyzeObject')
            ->with($object)
            ->willReturn([
                'property1' => [
                    'value' => 'value1',
                    'attributes' => [new \stdClass()],
                ],
            ]);

        $mockHandler->expects($this->once())
            ->method('handleAttribute')
            ->with('property1', $this->isInstanceOf(\stdClass::class), 'value1');

        $result = $this->inspector->inspect($object, $mockHandler);

        $this->assertSame($mockHandler, $result);
    }

    public function testInspectWithNoResults(): void
    {
        $object = new \stdClass();
        $mockHandler = $this->createMock(PropertyAttributeHandler::class);

        $this->analyzer->expects($this->once())
            ->method('analyzeObject')
            ->willReturn([]);

        $mockHandler->expects($this->never())
            ->method('handleAttribute');

        $result = $this->inspector->inspect($object, $mockHandler);

        $this->assertSame($mockHandler, $result);
    }

    public function testInspectWithAnalyzerException(): void
    {
        $object = new \stdClass();
        $mockHandler = $this->createMock(PropertyAttributeHandler::class);

        $this->analyzer->expects($this->once())
            ->method('analyzeObject')
            ->willThrowException(new \ReflectionException('Test exception'));

        $this->expectException(PropertyInspectionException::class);
        $this->expectExceptionMessage('Failed to analyze object: Test exception');

        $this->inspector->inspect($object, $mockHandler);
    }

    public function testInspectWithMultipleAttributes(): void
    {
        $object = new \stdClass();
        $mockHandler = $this->createMock(PropertyAttributeHandler::class);

        $this->analyzer->expects($this->once())
            ->method('analyzeObject')
            ->willReturn([
                'property1' => [
                    'value' => 'value1',
                    'attributes' => [new \stdClass(), new \stdClass()],
                ],
            ]);

        $mockHandler->expects($this->exactly(2))
            ->method('handleAttribute')
            ->with('property1', $this->isInstanceOf(\stdClass::class), 'value1');

        $result = $this->inspector->inspect($object, $mockHandler);

        $this->assertSame($mockHandler, $result);
    }

    public function testInspectWithGeneralException(): void
    {
        $object = new \stdClass();
        $mockHandler = $this->createMock(PropertyAttributeHandler::class);

        $this->analyzer->expects($this->once())
            ->method('analyzeObject')
            ->willThrowException(new \Exception('General exception'));

        $this->expectException(PropertyInspectionException::class);
        $this->expectExceptionMessage('An exception occurred during object analysis: General exception');

        $this->inspector->inspect($object, $mockHandler);
    }

    public function testInspectWithError(): void
    {
        $object = new \stdClass();
        $mockHandler = $this->createMock(PropertyAttributeHandler::class);

        $this->analyzer->expects($this->once())
            ->method('analyzeObject')
            ->willThrowException(new \Error('Fatal error'));

        $this->expectException(PropertyInspectionException::class);
        $this->expectExceptionMessage('An error occurred during object analysis: Fatal error');

        $this->inspector->inspect($object, $mockHandler);
    }
}
