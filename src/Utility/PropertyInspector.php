<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Utility;

use KaririCode\PropertyInspector\Contract\AttributeAnalyzer;
use KaririCode\PropertyInspector\Contract\PropertyAttributeHandler;
use KaririCode\PropertyInspector\Contract\PropertyInspector as PropertyInspectorContract;
use KaririCode\PropertyInspector\Exception\PropertyInspectionException;

final class PropertyInspector implements PropertyInspectorContract
{
    public function __construct(private readonly AttributeAnalyzer $attributeAnalyzer)
    {
    }

    public function inspect(object $object, PropertyAttributeHandler $handler): PropertyAttributeHandler
    {
        try {
            $analysisResults = $this->attributeAnalyzer->analyzeObject($object);
            foreach ($analysisResults as $propertyName => $propertyData) {
                foreach ($propertyData['attributes'] as $attribute) {
                    $handler->handleAttribute($propertyName, $attribute, $propertyData['value']);
                }
            }

            return $handler;
        } catch (\ReflectionException $e) {
            throw new PropertyInspectionException('Failed to analyze object: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new PropertyInspectionException('An exception occurred during object analysis: ' . $e->getMessage(), 0, $e);
        } catch (\Error $e) {
            throw new PropertyInspectionException('An error occurred during object analysis: ' . $e->getMessage(), 0, $e);
        }
    }
}
