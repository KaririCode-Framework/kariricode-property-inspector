<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector;

use KaririCode\PropertyInspector\Contract\AttributeAnalyzer as AttributeAnalyzerContract;
use KaririCode\PropertyInspector\Exception\PropertyInspectionException;

/**
 * Analyzes object properties for a specific PHP attribute type using reflection.
 *
 * Caches reflection metadata per class to avoid repeated introspection
 * on subsequent calls for the same class.
 */
final class AttributeAnalyzer implements AttributeAnalyzerContract
{
    /** @var array<class-string, array<string, array{attributes: list<object>, property: \ReflectionProperty}>> */
    private array $cache = [];

    /** @param class-string $attributeClass The attribute class to scan for */
    public function __construct(private readonly string $attributeClass)
    {
    }

    #[\Override]
    public function analyzeObject(object $object): array
    {
        try {
            $className = $object::class;

            if (! isset($this->cache[$className])) {
                $this->cacheObjectMetadata($object);
            }

            return $this->extractValues($object);
        } catch (\ReflectionException $e) {
            throw PropertyInspectionException::failedToAnalyzeObjectReflection($e);
        } catch (\Error $e) {
            throw PropertyInspectionException::failedToAnalyzeObjectError($e);
        }
    }

    private function cacheObjectMetadata(object $object): void
    {
        $className = $object::class;
        $reflection = new \ReflectionClass($object);
        $cachedProperties = [];

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes($this->attributeClass, \ReflectionAttribute::IS_INSTANCEOF);

            if (! empty($attributes)) {
                $attributeInstances = array_map(
                    static fn (\ReflectionAttribute $attr): object => $attr->newInstance(),
                    $attributes,
                );

                $cachedProperties[$property->getName()] = [
                    'attributes' => $attributeInstances,
                    'property' => $property,
                ];
            }
        }

        $this->cache[$className] = $cachedProperties;
    }

    /** @return array<string, array{value: mixed, attributes: list<object>}> */
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

    #[\Override]
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
