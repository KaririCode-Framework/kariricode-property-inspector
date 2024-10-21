<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Contract;

interface PropertyAttributeHandler
{
    /**
     * Handles an attribute found on a property.
     *
     * @param string $propertyName The name of the property
     * @param object $attribute The found attribute
     * @param mixed $value The property value
     *
     * @return mixed The result of handling the attribute
     */
    public function handleAttribute(string $propertyName, object $attribute, mixed $value): mixed;

    /**
     * Retrieves the values after processing.
     *
     * @return array<string, mixed> The processed values indexed by property name
     */
    public function getProcessedPropertyValues(): array;

    /**
     * Retrieves the messages generated during processing.
     *
     * @return array<string, array<string, string>> The processing messages indexed by property name and processor
     */
    public function getProcessingResultMessages(): array;

    /**
     * Retrieves the errors encountered during processing.
     *
     * @return array<string, array<string, string>> The processing errors indexed by property name and processor
     */
    public function getProcessingResultErrors(): array;
}
