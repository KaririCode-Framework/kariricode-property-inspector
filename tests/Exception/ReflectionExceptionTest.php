<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests\Exception;

use KaririCode\PropertyInspector\Contract\AttributeAnalyzer as AttributeAnalyzerInterface;
use KaririCode\PropertyInspector\Contract\PropertyAttributeHandler;
use KaririCode\PropertyInspector\Exception\PropertyInspectionException;
use KaririCode\PropertyInspector\Utility\PropertyInspector;
use PHPUnit\Framework\TestCase;

final class ReflectionExceptionTest extends TestCase
{
    public function testReflectionExceptionThrownDuringInspection(): void
    {
        $attributeAnalyzer = $this->createMock(AttributeAnalyzerInterface::class);
        $handler = $this->createMock(PropertyAttributeHandler::class);
        $inspector = new PropertyInspector($attributeAnalyzer);
        $object = new \stdClass();

        $reflectionException = new \ReflectionException('Simulated ReflectionException');
        $attributeAnalyzer->method('analyzeObject')
            ->willThrowException($reflectionException);

        try {
            $inspector->inspect($object, $handler);
            $this->fail('Expected PropertyInspectionException was not thrown');
        } catch (PropertyInspectionException $e) {
            $this->assertSame(2502, $e->getCode());
            $this->assertSame('REFLECTION_INSPECTION_ERROR', $e->getErrorCode());
            $this->assertSame(
                'Failed to inspect object using reflection: Simulated ReflectionException',
                $e->getMessage()
            );
            $this->assertSame($reflectionException, $e->getPrevious());
        }
    }

    public function testErrorThrownDuringInspection(): void
    {
        $attributeAnalyzer = $this->createMock(AttributeAnalyzerInterface::class);
        $handler = $this->createMock(PropertyAttributeHandler::class);
        $inspector = new PropertyInspector($attributeAnalyzer);
        $object = new \stdClass();

        $error = new \Error('Simulated Error');
        $attributeAnalyzer->method('analyzeObject')
            ->willThrowException($error);

        try {
            $inspector->inspect($object, $handler);
            $this->fail('Expected PropertyInspectionException was not thrown');
        } catch (PropertyInspectionException $e) {
            $this->assertSame(2505, $e->getCode());
            $this->assertSame('CRITICAL_INSPECTION_ERROR', $e->getErrorCode());
            $this->assertSame(
                'A critical error occurred during object inspection: Simulated Error',
                $e->getMessage()
            );
            $this->assertSame($error, $e->getPrevious());
        }
    }

    public function testGeneralExceptionThrownDuringInspection(): void
    {
        $attributeAnalyzer = $this->createMock(AttributeAnalyzerInterface::class);
        $handler = $this->createMock(PropertyAttributeHandler::class);
        $inspector = new PropertyInspector($attributeAnalyzer);
        $object = new \stdClass();

        $exception = new \Exception('General Exception');
        $attributeAnalyzer->method('analyzeObject')
            ->willThrowException($exception);

        try {
            $inspector->inspect($object, $handler);
            $this->fail('Expected PropertyInspectionException was not thrown');
        } catch (PropertyInspectionException $e) {
            $this->assertSame(2504, $e->getCode());
            $this->assertSame('GENERAL_INSPECTION_ERROR', $e->getErrorCode());
            $this->assertSame(
                'An exception occurred during object inspection: General Exception',
                $e->getMessage()
            );
            $this->assertSame($exception, $e->getPrevious());
        }
    }
}
