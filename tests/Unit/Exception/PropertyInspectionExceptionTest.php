<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests\Unit\Exception;

use KaririCode\PropertyInspector\Exception\PropertyInspectionException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class PropertyInspectionExceptionTest extends TestCase
{
    public function testFailedToAnalyzeObjectReflectionWrapsReflectionException(): void
    {
        $cause = new \ReflectionException('class not found');
        $exception = PropertyInspectionException::failedToAnalyzeObjectReflection($cause);

        self::assertInstanceOf(PropertyInspectionException::class, $exception);
        self::assertStringContainsString('class not found', $exception->getMessage());
        self::assertSame($cause, $exception->getPrevious());
        self::assertSame(2501, $exception->getCode());
    }

    public function testFailedToAnalyzeObjectErrorWrapsError(): void
    {
        $cause = new \Error('type mismatch');
        $exception = PropertyInspectionException::failedToAnalyzeObjectError($cause);

        self::assertInstanceOf(PropertyInspectionException::class, $exception);
        self::assertStringContainsString('type mismatch', $exception->getMessage());
        self::assertSame($cause, $exception->getPrevious());
        self::assertSame(2503, $exception->getCode());
    }

    public function testFailedToInspectObjectReflectionWrapsReflectionException(): void
    {
        $cause = new \ReflectionException('property not found');
        $exception = PropertyInspectionException::failedToInspectObjectReflection($cause);

        self::assertInstanceOf(PropertyInspectionException::class, $exception);
        self::assertStringContainsString('property not found', $exception->getMessage());
        self::assertSame($cause, $exception->getPrevious());
        self::assertSame(2502, $exception->getCode());
    }

    public function testFailedToInspectObjectExceptionWrapsException(): void
    {
        $cause = new \RuntimeException('runtime issue');
        $exception = PropertyInspectionException::failedToInspectObjectException($cause);

        self::assertInstanceOf(PropertyInspectionException::class, $exception);
        self::assertStringContainsString('runtime issue', $exception->getMessage());
        self::assertSame($cause, $exception->getPrevious());
        self::assertSame(2504, $exception->getCode());
    }

    public function testFailedToInspectObjectErrorWrapsError(): void
    {
        $cause = new \Error('critical failure');
        $exception = PropertyInspectionException::failedToInspectObjectError($cause);

        self::assertInstanceOf(PropertyInspectionException::class, $exception);
        self::assertStringContainsString('critical failure', $exception->getMessage());
        self::assertSame($cause, $exception->getPrevious());
        self::assertSame(2505, $exception->getCode());
    }

    public function testErrorCodesAreUnique(): void
    {
        $codes = [
            PropertyInspectionException::failedToAnalyzeObjectReflection(new \ReflectionException())->getCode(),
            PropertyInspectionException::failedToInspectObjectReflection(new \ReflectionException())->getCode(),
            PropertyInspectionException::failedToAnalyzeObjectError(new \Error())->getCode(),
            PropertyInspectionException::failedToInspectObjectException(new \Exception())->getCode(),
            PropertyInspectionException::failedToInspectObjectError(new \Error())->getCode(),
        ];

        self::assertCount(5, array_unique($codes));
    }
}
