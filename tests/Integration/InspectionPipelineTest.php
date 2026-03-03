<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests\Integration;

use KaririCode\PropertyInspector\AttributeAnalyzer;
use KaririCode\PropertyInspector\Contract\PropertyAttributeHandler;
use KaririCode\PropertyInspector\Contract\PropertyChangeApplier;
use KaririCode\PropertyInspector\Tests\Fixture\Attribute\Sanitize;
use KaririCode\PropertyInspector\Tests\Fixture\Attribute\Validate;
use KaririCode\PropertyInspector\Tests\Fixture\UserFixture;
use KaririCode\PropertyInspector\Utility\PropertyAccessor;
use KaririCode\PropertyInspector\Utility\PropertyInspector;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests exercising the full inspection pipeline:
 * AttributeAnalyzer → PropertyInspector → Handler → PropertyAccessor.
 *
 * These tests verify cross-component behavior that unit tests cannot cover,
 * including multi-pass inspection, handler state accumulation,
 * and round-trip property mutation via PropertyAccessor.
 */
final class InspectionPipelineTest extends TestCase
{
    // ── Full pipeline: inspect → collect → apply ─────────────────────

    public function testFullPipelineInspectCollectAndApplyChanges(): void
    {
        $user = new UserFixture(name: '  Walmir  ', email: '  W@TEST.COM  ', age: 30);

        // Pass 1: Sanitize (trim + lowercase)
        $sanitizeAnalyzer = new AttributeAnalyzer(Sanitize::class);
        $sanitizeInspector = new PropertyInspector($sanitizeAnalyzer);
        $sanitizeHandler = new TrimLowercaseHandler();

        $sanitizeInspector->inspect($user, $sanitizeHandler);

        // Apply sanitized values back to the object
        $applier = new ReflectionChangeApplier($sanitizeHandler->getProcessedPropertyValues());
        $applier->applyChanges($user);

        self::assertSame('Walmir', $user->name);
        self::assertSame('w@test.com', $user->email);

        // Pass 2: Validate (on sanitized data)
        $validateAnalyzer = new AttributeAnalyzer(Validate::class);
        $validateInspector = new PropertyInspector($validateAnalyzer);
        $validateHandler = new SimpleValidationHandler();

        $validateInspector->inspect($user, $validateHandler);

        self::assertSame([], $validateHandler->getProcessingResultErrors());
    }

    // ── Multi-pass: same analyzer, different objects ──────────────────

    public function testMultiPassInspectionSharesCachedMetadata(): void
    {
        $analyzer = new AttributeAnalyzer(Validate::class);
        $inspector = new PropertyInspector($analyzer);

        $users = [
            new UserFixture(name: 'Alice', email: 'alice@test.com', age: 25),
            new UserFixture(name: 'Bob', email: 'bob@test.com', age: 35),
            new UserFixture(name: 'Charlie', email: 'charlie@test.com', age: 45),
        ];

        $allProcessed = [];
        foreach ($users as $user) {
            $handler = new SimpleValidationHandler();
            $inspector->inspect($user, $handler);
            $allProcessed[] = $handler->getProcessedPropertyValues();
        }

        self::assertCount(3, $allProcessed);
        self::assertSame('Alice', $allProcessed[0]['name']);
        self::assertSame('Bob', $allProcessed[1]['name']);
        self::assertSame('Charlie', $allProcessed[2]['name']);
    }

    // ── PropertyAccessor round-trip integration ──────────────────────

    public function testPropertyAccessorRoundTripWithInspection(): void
    {
        $user = new UserFixture(name: 'Original', email: 'orig@test.com', age: 20);

        // Inspect to get attribute data
        $analyzer = new AttributeAnalyzer(Validate::class);
        $inspector = new PropertyInspector($analyzer);
        $handler = new SimpleValidationHandler();
        $inspector->inspect($user, $handler);

        // Mutate via PropertyAccessor
        $accessor = new PropertyAccessor($user, 'name');
        $accessor->setValue('Modified');

        // Re-inspect — should see the updated value
        $handler2 = new SimpleValidationHandler();
        $inspector->inspect($user, $handler2);

        self::assertSame('Modified', $handler2->getProcessedPropertyValues()['name']);
    }

    // ── ClearCache forces re-analysis ────────────────────────────────

    public function testClearCacheForcesReAnalysisOnNextInspection(): void
    {
        $analyzer = new AttributeAnalyzer(Validate::class);
        $inspector = new PropertyInspector($analyzer);

        $user = new UserFixture(name: 'Before', email: 'b@test.com', age: 30);
        $handler1 = new SimpleValidationHandler();
        $inspector->inspect($user, $handler1);

        $analyzer->clearCache();

        $user->name = 'After';
        $handler2 = new SimpleValidationHandler();
        $inspector->inspect($user, $handler2);

        self::assertSame('After', $handler2->getProcessedPropertyValues()['name']);
    }

    // ── Error accumulation across properties ─────────────────────────

    public function testValidationErrorsAccumulateAcrossProperties(): void
    {
        $user = new UserFixture(name: '', email: 'not-an-email', age: 10);

        $analyzer = new AttributeAnalyzer(Validate::class);
        $inspector = new PropertyInspector($analyzer);
        $handler = new SimpleValidationHandler();

        $inspector->inspect($user, $handler);

        $errors = $handler->getProcessingResultErrors();
        self::assertArrayHasKey('name', $errors);
        self::assertArrayHasKey('email', $errors);
        self::assertArrayHasKey('age', $errors);
    }
}

// ── Test-scoped handler: Trim + Lowercase ────────────────────────────

final class TrimLowercaseHandler implements PropertyAttributeHandler
{
    /** @var array<string, mixed> */
    private array $processed = [];

    public function handleAttribute(string $propertyName, object $attribute, mixed $value): mixed
    {
        if ($attribute instanceof Sanitize) {
            $sanitized = $value;
            foreach ($attribute->sanitizers as $sanitizer) {
                $sanitized = match ($sanitizer) {
                    'trim' => is_string($sanitized) ? trim($sanitized) : $sanitized,
                    'lowercase' => is_string($sanitized) ? strtolower($sanitized) : $sanitized,
                    default => $sanitized,
                };
            }
            $this->processed[$propertyName] = $sanitized;

            return $sanitized;
        }

        return $value;
    }

    public function getProcessedPropertyValues(): array
    {
        return $this->processed;
    }

    public function getProcessingResultMessages(): array
    {
        return [];
    }

    public function getProcessingResultErrors(): array
    {
        return [];
    }
}

// ── Test-scoped handler: Simple validation ───────────────────────────

final class SimpleValidationHandler implements PropertyAttributeHandler
{
    /** @var array<string, mixed> */
    private array $processed = [];

    /** @var array<string, array<string, string>> */
    private array $errors = [];

    public function handleAttribute(string $propertyName, object $attribute, mixed $value): mixed
    {
        $this->processed[$propertyName] = $value;

        if ($attribute instanceof Validate) {
            foreach ($attribute->processors as $rule) {
                $error = $this->validateRule($rule, $value);
                if ($error !== null) {
                    $this->errors[$propertyName][$rule] = $error;
                }
            }
        }

        return $value;
    }

    private function validateRule(string $rule, mixed $value): ?string
    {
        return match (true) {
            $rule === 'required' && ($value === '' || $value === null) => 'Field is required',
            $rule === 'email' && is_string($value) && !str_contains($value, '@') => 'Invalid email',
            str_starts_with($rule, 'min:') && is_int($value) => $this->validateMin($rule, $value),
            str_starts_with($rule, 'min:') && is_string($value) => $this->validateMinLength($rule, $value),
            default => null,
        };
    }

    private function validateMin(string $rule, int $value): ?string
    {
        $min = (int) substr($rule, 4);

        return $value < $min ? "Value must be at least {$min}" : null;
    }

    private function validateMinLength(string $rule, string $value): ?string
    {
        $min = (int) substr($rule, 4);

        return strlen($value) < $min ? "Must be at least {$min} characters" : null;
    }

    public function getProcessedPropertyValues(): array
    {
        return $this->processed;
    }

    public function getProcessingResultMessages(): array
    {
        return [];
    }

    public function getProcessingResultErrors(): array
    {
        return $this->errors;
    }
}

// ── Test-scoped applier: uses PropertyAccessor to write back ─────────

final class ReflectionChangeApplier implements PropertyChangeApplier
{
    /** @param array<string, mixed> $changes */
    public function __construct(private readonly array $changes)
    {
    }

    public function applyChanges(object $object): void
    {
        foreach ($this->changes as $propertyName => $value) {
            $accessor = new PropertyAccessor($object, $propertyName);
            $accessor->setValue($value);
        }
    }
}
