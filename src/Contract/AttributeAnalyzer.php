<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Contract;

interface AttributeAnalyzer
{
    /**
     * Analyzes an object for specific attributes on its properties.
     *
     * @param object $object The object to be analyzed
     *
     * @throws \ReflectionException If there's an error analyzing the object
     *
     * @return array<string, array{value: mixed, attributes: array<object>}> An associative array with the analysis results
     */
    public function analyzeObject(object $object): array;
}
