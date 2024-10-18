<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector;

use KaririCode\Contract\Processor\Attribute\CustomizableMessageAttribute;
use KaririCode\Contract\Processor\Attribute\ProcessableAttribute;
use KaririCode\Contract\Processor\ProcessorBuilder;
use KaririCode\PropertyInspector\Contract\PropertyAttributeHandler;
use KaririCode\PropertyInspector\Contract\PropertyChangeApplier;
use KaririCode\PropertyInspector\Processor\ProcessorConfigBuilder;
use KaririCode\PropertyInspector\Utility\PropertyAccessor;

class AttributeHandler implements PropertyAttributeHandler, PropertyChangeApplier
{
    private array $processedValues = [];
    private array $processingErrors = [];
    private array $processingMessages = [];

    public function __construct(
        private readonly string $processorType,
        private readonly ProcessorBuilder $builder,
        private readonly ProcessorConfigBuilder $configBuilder = new ProcessorConfigBuilder()
    ) {
    }

    public function handleAttribute(string $propertyName, object $attribute, mixed $value): mixed
    {
        if (!$attribute instanceof ProcessableAttribute) {
            return null;
        }

        $processorsConfig = $this->configBuilder->build($attribute);
        $messages = $this->extractCustomMessages($attribute, $processorsConfig);

        try {
            $processedValue = $this->processValue($value, $processorsConfig);
            $this->storeProcessedValue($propertyName, $processedValue, $messages);

            return $processedValue;  // Return the processed value, not the original
        } catch (\Exception $e) {
            $this->storeProcessingError($propertyName, $e->getMessage());

            return $value;
        }
    }

    private function extractCustomMessages(ProcessableAttribute $attribute, array &$processorsConfig): array
    {
        $messages = [];

        if ($attribute instanceof CustomizableMessageAttribute) {
            foreach ($processorsConfig as $processorName => &$config) {
                $customMessage = $attribute->getMessage($processorName);
                if (null !== $customMessage) {
                    $config['customMessage'] = $customMessage;
                    $messages[$processorName] = $customMessage;
                }
            }
        }

        return $messages;
    }

    private function processValue(mixed $value, array $processorsConfig): mixed
    {
        $pipeline = $this->builder->buildPipeline($this->processorType, $processorsConfig);

        return $pipeline->process($value);
    }

    private function storeProcessedValue(string $propertyName, mixed $processedValue, array $messages): void
    {
        $this->processedValues[$propertyName] = [
            'value' => $processedValue,
            'messages' => $messages,
        ];
        $this->processingMessages[$propertyName] = $messages;
    }

    private function storeProcessingError(string $propertyName, string $errorMessage): void
    {
        $this->processingErrors[$propertyName][] = $errorMessage;
    }

    public function applyChanges(object $entity): void
    {
        foreach ($this->processedValues as $propertyName => $data) {
            $accessor = new PropertyAccessor($entity, $propertyName);
            $accessor->setValue($data['value']);
        }
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
