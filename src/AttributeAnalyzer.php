<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector;

use KaririCode\PropertyInspector\Contract\AttributeAnalyzer as AttributeAnalyzerContract;
use KaririCode\PropertyInspector\Exception\PropertyInspectionException;

final class AttributeAnalyzer implements AttributeAnalyzerContract
{
    private array $cache = [];

    public function __construct(private readonly string $attributeClass)
    {
    }

    public function analyzeObject(object $object): array
    {
        try {
            $className = $object::class;

            // Usar cache se disponível
            if (!isset($this->cache[$className])) {
                $this->cacheObjectMetadata($object);
            }

            return $this->extractValues($object);
        } catch (\ReflectionException $e) {
            throw new PropertyInspectionException('Failed to analyze object: ' . $e->getMessage(), 0, $e);
        } catch (\Error $e) {
            throw new PropertyInspectionException('An error occurred during object analysis: ' . $e->getMessage(), 0, $e);
        }
    }

    private function cacheObjectMetadata(object $object): void
    {
        $className = $object::class;
        $reflection = new \ReflectionClass($object);
        $cachedProperties = [];

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes($this->attributeClass, \ReflectionAttribute::IS_INSTANCEOF);

            if (!empty($attributes)) {
                $property->setAccessible(true);
                $attributeInstances = array_map(
                    static fn (\ReflectionAttribute $attr): object => $attr->newInstance(),
                    $attributes
                );

                $cachedProperties[$property->getName()] = [
                    'attributes' => $attributeInstances,
                    'property' => $property,
                ];
            }
        }

        $this->cache[$className] = $cachedProperties;
    }

    private function extractValues(object $object): array
    {
        $results = [];
        $className = $object::class;

        foreach ($this->cache[$className] as $propertyName => $data) {
            $results[$propertyName] = [
                'value' => $data['property']->getValue($object),
                'attributes' => $data['attributes'],
            ];
        }

        return $results;
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }
}
