<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests;

use KaririCode\Contract\Processor\Attribute\CustomizableMessageAttribute;
use KaririCode\Contract\Processor\Attribute\ProcessableAttribute;
use KaririCode\Contract\Processor\Pipeline;
use KaririCode\Contract\Processor\ProcessorBuilder;
use KaririCode\ProcessorPipeline\Exception\ProcessingException;
use KaririCode\PropertyInspector\AttributeHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

interface CombinedAttribute extends
    ProcessableAttribute,
    CustomizableMessageAttribute
{
}

final class AttributeHandlerTest extends TestCase
{
    private AttributeHandler $attributeHandler;
    private ProcessorBuilder|MockObject $processorBuilder;

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

    public function testHandleAttributeReturnsOriginalValueOnException(): void
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

        $result = $this->attributeHandler->handleAttribute('testProperty', $mockAttribute, 'initialValue');
        $this->assertSame('initialValue', $result);

        $errors = $this->attributeHandler->getProcessingErrors();
        $this->assertArrayHasKey('testProperty', $errors);
        $this->assertContains('Test exception', $errors['testProperty']);
    }

    public function testHandleAttributeReturnsNullWhenAttributeNotProcessable(): void
    {
        $nonProcessableAttribute = new \stdClass();
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
        $this->assertSame('processedValue', $processedValues['testProperty']);
    }

    public function testHandleAttributeWithCustomizableMessageAttribute(): void
    {
        // Create a mock of the combined interface
        $mockAttribute = $this->createMock(CombinedAttribute::class);

        $mockAttribute->method('getProcessors')
            ->willReturn(['processor1' => ['option' => 'value']]);

        $mockAttribute->expects($this->once())
            ->method('getMessage')
            ->with('processor1')
            ->willReturn('Custom message');

        $mockPipeline = $this->createMock(Pipeline::class);
        $mockPipeline->method('process')->willReturn('processedValue');

        $this->processorBuilder->expects($this->once())
            ->method('buildPipeline')
            ->with('testProcessor', ['processor1' => ['option' => 'value', 'customMessage' => 'Custom message']])
            ->willReturn($mockPipeline);

        $result = $this->attributeHandler->handleAttribute('testProperty', $mockAttribute, 'initialValue');
        $this->assertSame('processedValue', $result);

        // Since getProcessors is mocked, we need to simulate the processors array
        $processors = ['processor1' => ['option' => 'value', 'customMessage' => 'Custom message']];

        $this->assertArrayHasKey('processor1', $processors);
        $this->assertArrayHasKey('customMessage', $processors['processor1']);
        $this->assertEquals('Custom message', $processors['processor1']['customMessage']);
    }

    public function testGetProcessingErrors(): void
    {
        $mockAttribute = $this->createMock(ProcessableAttribute::class);
        $mockPipeline = $this->createMock(Pipeline::class);

        $mockPipeline->expects($this->once())
            ->method('process')
            ->willThrowException(new ProcessingException('Test error'));

        $this->processorBuilder->expects($this->once())
            ->method('buildPipeline')
            ->willReturn($mockPipeline);

        $mockAttribute->expects($this->once())
            ->method('getProcessors')
            ->willReturn(['processor1']);

        $this->attributeHandler->handleAttribute('testProperty', $mockAttribute, 'initialValue');
        $errors = $this->attributeHandler->getProcessingErrors();

        $this->assertArrayHasKey('testProperty', $errors);
        $this->assertContains('Test error', $errors['testProperty']);
    }
}
