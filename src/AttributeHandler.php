<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector;

use KaririCode\Contract\Processor\Attribute\CustomizableMessageAttribute;
use KaririCode\Contract\Processor\Attribute\ProcessableAttribute;
use KaririCode\Contract\Processor\ProcessorBuilder;
use KaririCode\ProcessorPipeline\Exception\ProcessingException;
use KaririCode\PropertyInspector\Contract\PropertyAttributeHandler;
use KaririCode\PropertyInspector\Contract\PropertyChangeApplier;
use KaririCode\PropertyInspector\Utility\PropertyAccessor;

class AttributeHandler implements PropertyAttributeHandler, PropertyChangeApplier
{
    private array $processedValues = [];
    private array $processingErrors = [];

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

        $processors = $attribute->getProcessors();

        if ($attribute instanceof CustomizableMessageAttribute) {
            foreach ($processors as $processorName => &$processorConfig) {
                $customMessage = $attribute->getMessage($processorName);
                if (null !== $customMessage) {
                    $processorConfig['customMessage'] = $customMessage;
                }
            }
            unset($processorConfig); // Break the reference after use
        }

        $pipeline = $this->builder->buildPipeline($this->processorType, $processors);

        try {
            $processedValue = $pipeline->process($value);
            $this->processedValues[$propertyName] = $processedValue;

            return $processedValue;
        } catch (ProcessingException $e) {
            $this->processingErrors[$propertyName][] = $e->getMessage();

            return $value; // Return original value in case of processing error
        }
    }

    public function applyChanges(object $entity): void
    {
        foreach ($this->processedValues as $propertyName => $value) {
            $accessor = new PropertyAccessor($entity, $propertyName);
            $accessor->setValue($value);
        }
        $this->processedValues = []; // Clear the processed values after applying
    }

    public function getProcessedValues(): array
    {
        return $this->processedValues;
    }

    public function getProcessingErrors(): array
    {
        return $this->processingErrors;
    }
}
