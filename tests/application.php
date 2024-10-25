<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\ProcessorPipeline\ProcessorRegistry;
use KaririCode\Validator\Attribute\Validate;
use KaririCode\Validator\Processor\Input\EmailValidator;
use KaririCode\Validator\Processor\Input\LengthValidator;
use KaririCode\Validator\Processor\Logic\RequiredValidator;
use KaririCode\Validator\Processor\Numeric\IntegerValidator;
use KaririCode\Validator\Processor\Numeric\RangeValidator;
use KaririCode\Validator\Validator;

// 1. Define the entity class with validation rules
class User
{
    public function __construct(
        #[Validate(
            processors: [
                'required',
                'length' => ['minLength' => 3, 'maxLength' => 50],
            ],
            messages: [
                'required' => 'Name is required',
                'length' => 'Name must be between 3 and 50 characters',
            ]
        )]
        private string $name = '',
        #[Validate(
            processors: ['required', 'email'],
            messages: [
                'required' => 'Email is required',
                'email' => 'Invalid email format',
            ]
        )]
        private string $email = '',
        #[Validate(
            processors: [
                'required',
                'integer',
                'range' => ['min' => 18, 'max' => 120],
            ],
            messages: [
                'required' => 'Age is required',
                'integer' => 'Age must be a whole number',
                'range' => 'Age must be between 18 and 120',
            ]
        )]
        private int $age = 0
    ) {
    }

    // Getters and setters
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }
}

// 2. Set up the validator registry
function setupValidatorRegistry(): ProcessorRegistry
{
    $registry = new ProcessorRegistry();

    // Register all required validators
    $registry->register('validator', 'required', new RequiredValidator());
    $registry->register('validator', 'email', new EmailValidator());
    $registry->register('validator', 'length', new LengthValidator());
    $registry->register('validator', 'integer', new IntegerValidator());
    $registry->register('validator', 'range', new RangeValidator());

    return $registry;
}

// 3. Helper function to display validation results
function displayValidationResults(array $errors): void
{
    if (empty($errors)) {
        echo "\033[32mValidation passed successfully!\033[0m\n";

        return;
    }

    echo "\033[31mValidation failed:\033[0m\n";
    foreach ($errors as $property => $propertyErrors) {
        foreach ($propertyErrors as $error) {
            echo "\033[31m- {$property}: {$error['message']}\033[0m\n";
        }
    }
}

// 4. Test cases function
function runTestCases(Validator $validator): void
{
    // Test Case 1: Valid User
    echo "\n\033[1mTest Case 1: Valid User\033[0m\n";
    $validUser = new User();
    $validUser->setName('Walmir Silva');
    $validUser->setEmail('walmir.silva@example.com');
    $validUser->setAge(25);

    $result = $validator->validate($validUser);
    displayValidationResults($result->getErrors());

    // Test Case 2: Invalid User (Short name, invalid email, underage)
    echo "\n\033[1mTest Case 2: Invalid User\033[0m\n";
    $invalidUser = new User();
    $invalidUser->setName('Wa');
    $invalidUser->setEmail('walmir.silva.invalid');
    $invalidUser->setAge(16);

    $result = $validator->validate($invalidUser);
    displayValidationResults($result->getErrors());

    // Test Case 3: Empty User
    echo "\n\033[1mTest Case 3: Empty User\033[0m\n";
    $emptyUser = new User();

    $result = $validator->validate($emptyUser);
    displayValidationResults($result->getErrors());

    // Test Case 4: User with Extra Whitespace
    echo "\n\033[1mTest Case 4: User with Extra Whitespace\033[0m\n";
    $whitespaceUser = new User();
    $whitespaceUser->setName('  Walmir  Silva  ');
    $whitespaceUser->setEmail('  WALMIR.SILVA@EXAMPLE.COM  ');
    $whitespaceUser->setAge(30);

    $result = $validator->validate($whitespaceUser);
    displayValidationResults($result->getErrors());
}

// 5. Main application execution
function main(): void
{
    try {
        echo "\033[1mKaririCode Validator Demo\033[0m\n";
        echo "================================\n";

        // Setup
        $registry = setupValidatorRegistry();
        $validator = new Validator($registry);

        // Run test cases
        runTestCases($validator);
    } catch (Exception $e) {
        echo "\033[31mError: {$e->getMessage()}\033[0m\n";
        echo "\033[33mStack trace:\033[0m\n";
        echo $e->getTraceAsString() . "\n";
    }
}

// Run the application
main();
