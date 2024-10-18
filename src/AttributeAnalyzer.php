<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector;

use KaririCode\PropertyInspector\Contract\AttributeAnalyzer as AttributeAnalyzerContract;
use KaririCode\PropertyInspector\Exception\PropertyInspectionException;

final class AttributeAnalyzer implements AttributeAnalyzerContract
{
    public function __construct(private readonly string $attributeClass)
    {
    }

    public function analyzeObject(object $object): array
    {
        try {
            $results = [];
            $reflection = new \ReflectionClass($object);

            foreach ($reflection->getProperties() as $property) {
                $propertyResult = $this->analyzeProperty($object, $property);
                if (null !== $propertyResult) {
                    $results[$property->getName()] = $propertyResult;
                }
            }

            return $results;
        } catch (\ReflectionException $e) {
            throw new PropertyInspectionException('Failed to analyze object: ' . $e->getMessage(), 0, $e);
        } catch (\Error $e) {
            throw new PropertyInspectionException('An error occurred during object analysis: ' . $e->getMessage(), 0, $e);
        }
    }

    private function analyzeProperty(object $object, \ReflectionProperty $property): ?array
    {
        $attributes = $property->getAttributes($this->attributeClass, \ReflectionAttribute::IS_INSTANCEOF);
        if (empty($attributes)) {
            return null;
        }

        $property->setAccessible(true);
        $propertyValue = $property->getValue($object);

        $attributeInstances = array_map(
            static fn (\ReflectionAttribute $attr): object => $attr->newInstance(),
            $attributes
        );

        return [
            'value' => $propertyValue,
            'attributes' => $attributeInstances,
        ];
    }
}
