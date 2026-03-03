<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Contract;

use KaririCode\PropertyInspector\Exception\PropertyInspectionException;

interface AttributeAnalyzer
{
    /**
     * Analyzes an object for specific attributes on its properties.
     *
     * @param object $object The object to be analyzed
     *
     * @throws PropertyInspectionException If reflection or runtime error occurs during analysis
     *
     * @return array<string, array{value: mixed, attributes: array<object>}> An associative array with the analysis results
     */
    public function analyzeObject(object $object): array;

    /**
     * Clears the internal reflection metadata cache.
     *
     * Useful for long-running processes or after dynamic class modifications
     * to force re-analysis of previously cached class structures.
     */
    public function clearCache(): void;
}
