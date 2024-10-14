<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\PropertyInspector\AttributeAnalyzer;
use KaririCode\PropertyInspector\Contract\PropertyAttributeHandler;
use KaririCode\PropertyInspector\Exception\PropertyInspectionException;
use KaririCode\PropertyInspector\PropertyInspector;

// Custom Attributes
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Validate
{
    public function __construct(public readonly array $rules)
    {
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Sanitize
{
    public function __construct(public readonly string $method)
    {
    }
}

// Sample Entity
final class User
{
    public function __construct(
        #[Validate(['required', 'string', 'min:3'])]
        #[Sanitize('trim')]
        public string $name,
        #[Validate(['required', 'email'])]
        #[Sanitize('lowercase')]
        public string $email,
        #[Validate(['required', 'integer', 'min:18'])]
        public int $age
    ) {
    }
}

// Custom Attribute Handler
final class CustomAttributeHandler implements PropertyAttributeHandler
{
    public function handleAttribute(object $object, string $propertyName, object $attribute, mixed $value): ?string
    {
        return match (true) {
            $attribute instanceof Validate => $this->validate($propertyName, $value, $attribute->rules),
            $attribute instanceof Sanitize => $this->sanitize($propertyName, $value, $attribute->method),
            default => null,
        };
    }

    private function validate(string $propertyName, mixed $value, array $rules): ?string
    {
        $errors = array_filter(array_map(
            fn ($rule) => $this->applyValidationRule($propertyName, $value, $rule),
            $rules
        ));

        return empty($errors) ? null : implode(' ', $errors);
    }

    private function applyValidationRule(string $propertyName, mixed $value, string $rule): ?string
    {
        return match (true) {
            'required' === $rule && empty($value) => "$propertyName is required.",
            'string' === $rule && !is_string($value) => "$propertyName must be a string.",
            str_starts_with($rule, 'min:') => $this->validateMinRule($propertyName, $value, $rule),
            'email' === $rule && !filter_var($value, FILTER_VALIDATE_EMAIL) => "$propertyName must be a valid email address.",
            'integer' === $rule && !is_int($value) => "$propertyName must be an integer.",
            default => null,
        };
    }

    private function validateMinRule(string $propertyName, mixed $value, string $rule): ?string
    {
        $minValue = (int) substr($rule, 4);

        return match (true) {
            is_string($value) && strlen($value) < $minValue => "$propertyName must be at least $minValue characters long.",
            is_int($value) && $value < $minValue => "$propertyName must be at least $minValue.",
            default => null,
        };
    }

    private function sanitize(string $propertyName, mixed $value, string $method): string
    {
        return match ($method) {
            'trim' => trim($value),
            'lowercase' => strtolower($value),
            default => (string) $value,
        };
    }
}

function runApplication(): void
{
    $attributeAnalyzer = new AttributeAnalyzer(Validate::class);
    $propertyInspector = new PropertyInspector($attributeAnalyzer);
    $handler = new CustomAttributeHandler();

    // Scenario 1: Valid User
    $validUser = new User('  WaLmir Silva  ', 'WALMIR.SILVA@EXAMPLE.COM', 25);
    processUser($propertyInspector, $handler, $validUser, 'Scenario 1: Valid User');

    // Scenario 2: Invalid User (Age below 18)
    $underageUser = new User('Walmir Silva', 'walmir@example.com', 16);
    processUser($propertyInspector, $handler, $underageUser, 'Scenario 2: Underage User');

    // Scenario 3: Invalid User (Empty name and invalid email)
    $invalidUser = new User('', 'invalid-email', 30);
    processUser($propertyInspector, $handler, $invalidUser, 'Scenario 3: Invalid User Data');

    // Scenario 4: Non-existent Attribute (to trigger an exception)
    try {
        $invalidAttributeAnalyzer = new AttributeAnalyzer('NonExistentAttribute');
        $invalidPropertyInspector = new PropertyInspector($invalidAttributeAnalyzer);
        $invalidPropertyInspector->inspect($validUser, $handler);
    } catch (PropertyInspectionException $e) {
        echo "\nScenario 4: Non-existent Attribute\n";
        echo 'Error: ' . $e->getMessage() . "\n";
    }
}

function processUser(PropertyInspector $inspector, PropertyAttributeHandler $handler, User $user, string $scenario): void
{
    echo "\n$scenario\n";
    echo 'Original User: ' . json_encode($user) . "\n";

    try {
        $results = $inspector->inspect($user, $handler);
        displayResults($results);

        if (empty($results)) {
            sanitizeUser($user);
            displaySanitizedUser($user);
        } else {
            echo "Validation failed. User was not sanitized.\n";
        }
    } catch (PropertyInspectionException $e) {
        echo "An error occurred during property inspection: {$e->getMessage()}\n";
    }
}

function displayResults(array $results): void
{
    if (empty($results)) {
        echo "All properties are valid.\n";

        return;
    }

    echo "Validation Results:\n";
    foreach ($results as $propertyName => $propertyResults) {
        echo "Property: $propertyName\n";
        foreach ($propertyResults as $result) {
            if (null !== $result) {
                echo "  - $result\n";
            }
        }
    }
}

function sanitizeUser(User $user): void
{
    $user->name = trim($user->name);
    $user->email = strtolower($user->email);
}

function displaySanitizedUser(User $user): void
{
    echo "Sanitized User:\n";
    echo json_encode($user) . "\n";
}

// Run the application
runApplication();
