<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector;

use KaririCode\Contract\Processor\Attribute\CustomizableMessageAttribute;
use KaririCode\Contract\Processor\Attribute\ProcessableAttribute;
use KaririCode\Contract\Processor\ProcessorBuilder;
use KaririCode\PropertyInspector\Contract\PropertyAttributeHandler;
use KaririCode\PropertyInspector\Contract\PropertyChangeApplier;
use KaririCode\PropertyInspector\Processor\ProcessorConfigBuilder;
use KaririCode\PropertyInspector\Processor\ProcessorValidator;
use KaririCode\PropertyInspector\Utility\PropertyAccessor;

class AttributeHandler implements PropertyAttributeHandler, PropertyChangeApplier
{
    private array $processedPropertyValues = [];
    private array $processingResultErrors = [];
    private array $processingResultMessages = [];

    public function __construct(
        private readonly string $processorType,
        private readonly ProcessorBuilder $builder,
        private readonly ProcessorValidator $validator = new ProcessorValidator(),
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
            $errors = $this->validateProcessors($processorsConfig, $messages);

            $this->storeProcessedPropertyValue($propertyName, $processedValue, $messages);

            if (!empty($errors)) {
                $this->storeProcessingResultErrors($propertyName, $errors);
            }

            return $processedValue;
        } catch (\Exception $e) {
            $this->storeProcessingResultError($propertyName, $e->getMessage());

            return $value;
        }
    }

    private function validateProcessors(array $processorsConfig, array $messages): array
    {
        $errors = [];
        foreach ($processorsConfig as $processorName => $config) {
            $processor = $this->builder->build($this->processorType, $processorName, $config);
            $validationError = $this->validator->validate(
                $processor,
                $processorName,
                $messages
            );

            if ($this->shouldAddValidationError($validationError, $errors, $processorName)) {
                $errors[$processorName] = $validationError;
            }
        }

        return $errors;
    }

    private function shouldAddValidationError(?array $validationError, array $errors, string $processorName): bool
    {
        return null !== $validationError && !isset($errors[$processorName]);
    }

    private function storeProcessingResultErrors(string $propertyName, array $errors): void
    {
        $this->processingResultErrors[$propertyName] = $errors;
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
        $pipeline = $this->builder->buildPipeline(
            $this->processorType,
            $processorsConfig
        );

        return $pipeline->process($value);
    }

    private function storeProcessedPropertyValue(string $propertyName, mixed $processedValue, array $messages): void
    {
        $this->processedPropertyValues[$propertyName] = [
            'value' => $processedValue,
            'messages' => $messages,
        ];
        $this->processingResultMessages[$propertyName] = $messages;
    }

    private function storeProcessingResultError(string $propertyName, string $errorMessage): void
    {
        $this->processingResultErrors[$propertyName][] = $errorMessage;
    }

    public function applyChanges(object $entity): void
    {
        foreach ($this->processedPropertyValues as $propertyName => $data) {
            $accessor = new PropertyAccessor($entity, $propertyName);
            $accessor->setValue($data['value']);
        }
    }

    public function getProcessedPropertyValues(): array
    {
        return $this->processedPropertyValues;
    }

    public function getProcessingResultErrors(): array
    {
        return $this->processingResultErrors;
    }

    public function getProcessingResultMessages(): array
    {
        return $this->processingResultMessages;
    }
}
