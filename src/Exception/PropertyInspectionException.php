<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Exception;

use KaririCode\Exception\AbstractException;

final class PropertyInspectionException extends AbstractException
{
    private const CODE_REFLECTION_ANALYSIS_ERROR = 2501;
    private const CODE_REFLECTION_INSPECTION_ERROR = 2502;
    private const CODE_GENERAL_ANALYSIS_ERROR = 2503;
    private const CODE_GENERAL_INSPECTION_ERROR = 2504;
    private const CODE_CRITICAL_INSPECTION_ERROR = 2505;

    public static function failedToAnalyzeObjectReflection(\ReflectionException $e): self
    {
        return self::createException(
            self::CODE_REFLECTION_ANALYSIS_ERROR,
            'REFLECTION_ANALYSIS_ERROR',
            "Failed to analyze object using reflection: {$e->getMessage()}",
            $e
        );
    }

    public static function failedToAnalyzeObjectError(\Error $e): self
    {
        return self::createException(
            self::CODE_GENERAL_ANALYSIS_ERROR,
            'GENERAL_ANALYSIS_ERROR',
            "An error occurred during object analysis: {$e->getMessage()}",
            $e
        );
    }

    public static function failedToInspectObjectReflection(\ReflectionException $e): self
    {
        return self::createException(
            self::CODE_REFLECTION_INSPECTION_ERROR,
            'REFLECTION_INSPECTION_ERROR',
            "Failed to inspect object using reflection: {$e->getMessage()}",
            $e
        );
    }

    public static function failedToInspectObjectException(\Exception $e): self
    {
        return self::createException(
            self::CODE_GENERAL_INSPECTION_ERROR,
            'GENERAL_INSPECTION_ERROR',
            "An exception occurred during object inspection: {$e->getMessage()}",
            $e
        );
    }

    public static function failedToInspectObjectError(\Error $e): self
    {
        return self::createException(
            self::CODE_CRITICAL_INSPECTION_ERROR,
            'CRITICAL_INSPECTION_ERROR',
            "A critical error occurred during object inspection: {$e->getMessage()}",
            $e
        );
    }
}
