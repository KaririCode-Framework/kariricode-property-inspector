<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests;

use KaririCode\Contract\Processor\Pipeline;
use KaririCode\Contract\Processor\ProcessableAttribute;
use KaririCode\Contract\Processor\ProcessorBuilder;
use KaririCode\ProcessorPipeline\Exception\ProcessingException;
use KaririCode\PropertyInspector\AttributeHandler;
use PHPUnit\Framework\TestCase;

final class AttributeHandlerTest extends TestCase
{
    private AttributeHandler $attributeHandler;
    private ProcessorBuilder $processorBuilder;

    protected function setUp(): void
    {
        $this->processorBuilder = $this->createMock(ProcessorBuilder::class);
        $this->attributeHandler = new AttributeHandler('testProcessor', $this->processorBuilder);
    }

    public function testHandleAttributeProcessesValue(): void
    {
        $mockAttribute = $this->createMock(ProcessableAttribute::class);
        $mockPipeline = $this->createMock(Pipeline::class);

        $mockPipeline->expects($this->once())
            ->method('process')
            ->with('initialValue')
            ->willReturn('processedValue');

        $this->processorBuilder->expects($this->once())
            ->method('buildPipeline')
            ->with('testProcessor', ['processor1'])
            ->willReturn($mockPipeline);

        $mockAttribute->expects($this->once())
            ->method('getProcessors')
            ->willReturn(['processor1']);

        $result = $this->attributeHandler->handleAttribute('testProperty', $mockAttribute, 'initialValue');
        $this->assertSame('processedValue', $result);
    }

    public function testHandleAttributeReturnsFallbackOnException(): void
    {
        $mockAttribute = $this->createMock(ProcessableAttribute::class);
        $mockPipeline = $this->createMock(Pipeline::class);

        $mockPipeline->expects($this->once())
            ->method('process')
            ->willThrowException(new ProcessingException('Test exception'));

        $this->processorBuilder->expects($this->once())
            ->method('buildPipeline')
            ->willReturn($mockPipeline);

        $mockAttribute->expects($this->once())
            ->method('getProcessors')
            ->willReturn(['processor1']);

        $mockAttribute->expects($this->once())
            ->method('getFallbackValue')
            ->willReturn('fallbackValue');

        $result = $this->attributeHandler->handleAttribute('testProperty', $mockAttribute, 'initialValue');
        $this->assertSame('fallbackValue', $result);
    }

    public function testHandleAttributeReturnsNullWhenAttributeNotProcessable(): void
    {
        $nonProcessableAttribute = new \stdClass(); // Simulate a non-ProcessableAttribute object
        $result = $this->attributeHandler->handleAttribute('testProperty', $nonProcessableAttribute, 'initialValue');
        $this->assertNull($result);
    }

    public function testApplyChangesSetsProcessedValues(): void
    {
        $mockEntity = new class {
            public string $testProperty = 'originalValue';
        };

        $mockAttribute = $this->createMock(ProcessableAttribute::class);
        $mockPipeline = $this->createMock(Pipeline::class);

        $mockPipeline->expects($this->once())
            ->method('process')
            ->with('initialValue')
            ->willReturn('processedValue');

        $this->processorBuilder->expects($this->once())
            ->method('buildPipeline')
            ->willReturn($mockPipeline);

        $mockAttribute->expects($this->once())
            ->method('getProcessors')
            ->willReturn(['processor1']);

        $this->attributeHandler->handleAttribute('testProperty', $mockAttribute, 'initialValue');
        $this->attributeHandler->applyChanges($mockEntity);

        $this->assertSame('processedValue', $mockEntity->testProperty);
    }

    public function testGetProcessedValuesReturnsProcessedData(): void
    {
        $mockAttribute = $this->createMock(ProcessableAttribute::class);
        $mockPipeline = $this->createMock(Pipeline::class);

        $mockPipeline->expects($this->once())
            ->method('process')
            ->with('initialValue')
            ->willReturn('processedValue');

        $this->processorBuilder->expects($this->once())
            ->method('buildPipeline')
            ->willReturn($mockPipeline);

        $mockAttribute->expects($this->once())
            ->method('getProcessors')
            ->willReturn(['processor1']);

        $this->attributeHandler->handleAttribute('testProperty', $mockAttribute, 'initialValue');
        $processedValues = $this->attributeHandler->getProcessedValues();

        $this->assertArrayHasKey('testProperty', $processedValues);
        $this->assertSame(['processedValue'], $processedValues['testProperty']);
    }
}
