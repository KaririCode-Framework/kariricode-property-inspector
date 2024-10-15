<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector;

use KaririCode\Contract\Processor\ProcessableAttribute;
use KaririCode\Contract\Processor\ProcessorBuilder;
use KaririCode\ProcessorPipeline\Exception\ProcessingException;
use KaririCode\PropertyInspector\Contract\PropertyAttributeHandler;
use KaririCode\PropertyInspector\Utility\PropertyAccessor;

class AttributeHandler implements PropertyAttributeHandler
{
    private array $processedValues = [];

    public function __construct(
        private readonly string $processorType,
        private readonly ProcessorBuilder $builder,
    ) {
    }

    public function handleAttribute(string $propertyName, object $attribute, mixed $value): mixed
    {
        if (!$attribute instanceof ProcessableAttribute) {
            return null;
        }

        try {
            $pipeline = $this->builder->buildPipeline($this->processorType, $attribute->getProcessors());
            $processedValue = $pipeline->process($value);
            $this->processedValues[$propertyName][] = $processedValue;

            return $processedValue;
        } catch (ProcessingException $e) {
            $fallbackValue = $attribute->getFallbackValue() ?? $value;
            $this->processedValues[$propertyName][] = $fallbackValue;

            return $fallbackValue;
        }
    }

    public function applyChanges(object $entity): void
    {
        foreach ($this->processedValues as $propertyName => $values) {
            if (!empty($values)) {
                $finalValue = end($values);
                $accessor = new PropertyAccessor($entity, $propertyName);
                $accessor->setValue($finalValue);
            }
        }
        $this->processedValues = []; // Clear the processed values after applying
    }

    public function getProcessedValues(): array
    {
        return $this->processedValues;
    }
}
