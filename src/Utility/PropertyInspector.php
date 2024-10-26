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
            throw PropertyInspectionException::failedToInspectObjectReflection($e);
        } catch (\Exception $e) {
            throw PropertyInspectionException::failedToInspectObjectException($e);
        } catch (\Error $e) {
            throw PropertyInspectionException::failedToInspectObjectError($e);
        }
    }
}
