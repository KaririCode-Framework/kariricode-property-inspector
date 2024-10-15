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
            ->willReturn('handled result');

        $result = $this->inspector->inspect($object, $mockHandler);

        $this->assertEquals(['property1' => ['handled result']], $result);
    }

    public function testInspectWithNoResults(): void
    {
        $object = new \stdClass();
        $mockHandler = $this->createMock(PropertyAttributeHandler::class);

        $this->analyzer->expects($this->once())
            ->method('analyzeObject')
            ->willReturn([]);

        $result = $this->inspector->inspect($object, $mockHandler);

        $this->assertEmpty($result);
    }

    public function testInspectWithAnalyzerException(): void
    {
        $object = new \stdClass();
        $mockHandler = $this->createMock(PropertyAttributeHandler::class);

        $this->analyzer->expects($this->once())
            ->method('analyzeObject')
            ->willThrowException(new PropertyInspectionException('Test exception'));

        $this->expectException(PropertyInspectionException::class);
        $this->expectExceptionMessage('An error occurred during object analysis: Test exception');

        $this->inspector->inspect($object, $mockHandler);
    }
}
