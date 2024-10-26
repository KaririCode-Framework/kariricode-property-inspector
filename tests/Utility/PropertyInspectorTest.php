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

        $reflectionException = new \ReflectionException('Test exception');
        $this->analyzer->expects($this->once())
            ->method('analyzeObject')
            ->willThrowException($reflectionException);

        try {
            $this->inspector->inspect($object, $mockHandler);
            $this->fail('Expected PropertyInspectionException was not thrown');
        } catch (PropertyInspectionException $e) {
            $this->assertSame(2502, $e->getCode());
            $this->assertSame('REFLECTION_INSPECTION_ERROR', $e->getErrorCode());
            $this->assertSame(
                'Failed to inspect object using reflection: Test exception',
                $e->getMessage()
            );
            $this->assertSame($reflectionException, $e->getPrevious());
        }
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

        $exception = new \Exception('General exception');
        $this->analyzer->expects($this->once())
            ->method('analyzeObject')
            ->willThrowException($exception);

        try {
            $this->inspector->inspect($object, $mockHandler);
            $this->fail('Expected PropertyInspectionException was not thrown');
        } catch (PropertyInspectionException $e) {
            $this->assertSame(2504, $e->getCode());
            $this->assertSame('GENERAL_INSPECTION_ERROR', $e->getErrorCode());
            $this->assertSame(
                'An exception occurred during object inspection: General exception',
                $e->getMessage()
            );
            $this->assertSame($exception, $e->getPrevious());
        }
    }

    public function testInspectWithError(): void
    {
        $object = new \stdClass();
        $mockHandler = $this->createMock(PropertyAttributeHandler::class);

        $error = new \Error('Fatal error');
        $this->analyzer->expects($this->once())
            ->method('analyzeObject')
            ->willThrowException($error);

        try {
            $this->inspector->inspect($object, $mockHandler);
            $this->fail('Expected PropertyInspectionException was not thrown');
        } catch (PropertyInspectionException $e) {
            $this->assertSame(2505, $e->getCode());
            $this->assertSame('CRITICAL_INSPECTION_ERROR', $e->getErrorCode());
            $this->assertSame(
                'A critical error occurred during object inspection: Fatal error',
                $e->getMessage()
            );
            $this->assertSame($error, $e->getPrevious());
        }
    }

    public function testFailedToAnalyzeObjectReflection(): void
    {
        $originalException = new \ReflectionException('Class does not exist');
        $exception = PropertyInspectionException::failedToAnalyzeObjectReflection($originalException);

        $this->assertInstanceOf(PropertyInspectionException::class, $exception);
        $this->assertSame(2501, $exception->getCode());
        $this->assertSame('REFLECTION_ANALYSIS_ERROR', $exception->getErrorCode());
        $this->assertSame(
            'Failed to analyze object using reflection: Class does not exist',
            $exception->getMessage()
        );
        $this->assertSame($originalException, $exception->getPrevious());
    }

    public function testFailedToAnalyzeObjectError(): void
    {
        $originalError = new \Error('Type error occurred');
        $exception = PropertyInspectionException::failedToAnalyzeObjectError($originalError);

        $this->assertInstanceOf(PropertyInspectionException::class, $exception);
        $this->assertSame(2503, $exception->getCode());
        $this->assertSame('GENERAL_ANALYSIS_ERROR', $exception->getErrorCode());
        $this->assertSame(
            'An error occurred during object analysis: Type error occurred',
            $exception->getMessage()
        );
        $this->assertSame($originalError, $exception->getPrevious());
    }

    public function testFailedToInspectObjectReflection(): void
    {
        $originalException = new \ReflectionException('Property not accessible');
        $exception = PropertyInspectionException::failedToInspectObjectReflection($originalException);

        $this->assertInstanceOf(PropertyInspectionException::class, $exception);
        $this->assertSame(2502, $exception->getCode());
        $this->assertSame('REFLECTION_INSPECTION_ERROR', $exception->getErrorCode());
        $this->assertSame(
            'Failed to inspect object using reflection: Property not accessible',
            $exception->getMessage()
        );
        $this->assertSame($originalException, $exception->getPrevious());
    }

    public function testFailedToInspectObjectException(): void
    {
        $originalException = new \Exception('Generic error');
        $exception = PropertyInspectionException::failedToInspectObjectException($originalException);

        $this->assertInstanceOf(PropertyInspectionException::class, $exception);
        $this->assertSame(2504, $exception->getCode());
        $this->assertSame('GENERAL_INSPECTION_ERROR', $exception->getErrorCode());
        $this->assertSame(
            'An exception occurred during object inspection: Generic error',
            $exception->getMessage()
        );
        $this->assertSame($originalException, $exception->getPrevious());
    }

    public function testFailedToInspectObjectError(): void
    {
        $originalError = new \Error('Fatal error occurred');
        $exception = PropertyInspectionException::failedToInspectObjectError($originalError);

        $this->assertInstanceOf(PropertyInspectionException::class, $exception);
        $this->assertSame(2505, $exception->getCode());
        $this->assertSame('CRITICAL_INSPECTION_ERROR', $exception->getErrorCode());
        $this->assertSame(
            'A critical error occurred during object inspection: Fatal error occurred',
            $exception->getMessage()
        );
        $this->assertSame($originalError, $exception->getPrevious());
    }
}
