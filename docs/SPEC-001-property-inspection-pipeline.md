# SPEC-001: PropertyInspector Component

**Version:** 2.0.0
**Status:** Final
**Date:** 2026-02-28
**Authors:** Walmir Silva
**ARFA:** 1.3

## 1. Purpose

PropertyInspector provides attribute-based property analysis and inspection for the KaririCode Framework, enabling dynamic validation, normalization, and processing of object properties via PHP 8.4+ attributes and reflection.

## 2. Architecture

```
┌─────────────────────────────────────────────┐
│              Consumer Code                   │
│  $inspector->inspect($object, $handler)     │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────┐
│         PropertyInspector (Utility)           │
│  Orchestrates analysis + handler delegation  │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────┐
│          AttributeAnalyzer                    │
│  Reflection + cache + attribute extraction   │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────┐
│       PropertyAttributeHandler               │
│  Consumer-defined processing logic           │
└──────────────────────────────────────────────┘
               │
               ▼ (optional)
┌──────────────────────────────────────────────┐
│       PropertyChangeApplier                  │
│  Writes processed values back to object      │
└──────────────────────────────────────────────┘
```

## 3. Contracts

### 3.1 AttributeAnalyzer

```php
interface AttributeAnalyzer
{
    public function analyzeObject(object $object): array;
    public function clearCache(): void;
}
```

**INV-001:** `analyzeObject()` MUST return only properties annotated with the target attribute class.

**INV-002:** `analyzeObject()` MUST use `ReflectionAttribute::IS_INSTANCEOF` for interface-based matching.

**INV-003:** `clearCache()` MUST reset all internal state, forcing re-analysis on next call.

**INV-004:** `analyzeObject()` MUST throw `PropertyInspectionException` (never raw `\ReflectionException`).

### 3.2 PropertyInspector

```php
interface PropertyInspector
{
    public function inspect(
        object $object,
        PropertyAttributeHandler $handler
    ): PropertyAttributeHandler;
}
```

**INV-005:** `inspect()` MUST call `handler->handleAttribute()` once per attribute instance per property.

**INV-006:** `inspect()` MUST return the same handler instance passed as argument.

**INV-007:** `inspect()` MUST wrap all exceptions into `PropertyInspectionException`.

### 3.3 PropertyAttributeHandler

```php
interface PropertyAttributeHandler
{
    public function handleAttribute(string $propertyName, object $attribute, mixed $value): mixed;
    public function getProcessedPropertyValues(): array;
    public function getProcessingResultMessages(): array;
    public function getProcessingResultErrors(): array;
}
```

**INV-008:** `handleAttribute()` receives the current property value at time of inspection.

**INV-009:** `getProcessingResultErrors()` MUST be keyed by property name.

### 3.4 PropertyChangeApplier

```php
interface PropertyChangeApplier
{
    public function applyChanges(object $object): void;
}
```

**INV-010:** `applyChanges()` MUST use reflection (via `PropertyAccessor`) to write values regardless of visibility.

## 4. Caching Strategy

`AttributeAnalyzer` caches reflection metadata per `class-string`:

- **Cache key:** `$object::class`
- **Cache contents:** `array<propertyName, {attributes, ReflectionProperty}>`
- **Invalidation:** Manual via `clearCache()`
- **Scope:** Instance-level (not shared across analyzer instances)

**PERF-001:** Second call to `analyzeObject()` for the same class MUST NOT invoke `ReflectionClass`.

**PERF-002:** Property values MUST be read fresh on every call (not cached).

## 5. Ecosystem Integration

### 5.1 Consumer Components

| Component | Attribute | Handler |
|-----------|-----------|---------|
| KaririCode\Validator | `#[Validate]` | `ValidateAttributeHandler` |
| KaririCode\Sanitizer | `#[Sanitize]` | `SanitizeAttributeHandler` |
| KaririCode\Transformer | `#[Transform]` | `TransformAttributeHandler` |
| KaririCode\Normalizer | `#[Normalize]` | `NormalizeAttributeHandler` |

### 5.2 DPO Processing Pattern

```php
// Typical multi-pass pipeline
$sanitizeInspector = new PropertyInspector(new AttributeAnalyzer(Sanitize::class));
$validateInspector = new PropertyInspector(new AttributeAnalyzer(Validate::class));
$transformInspector = new PropertyInspector(new AttributeAnalyzer(Transform::class));

// Pass 1: Sanitize
$sanitizeInspector->inspect($dpo, $sanitizeHandler);
$sanitizeApplier->applyChanges($dpo);

// Pass 2: Validate (on clean data)
$validateInspector->inspect($dpo, $validateHandler);

// Pass 3: Transform (on validated data)
$transformInspector->inspect($dpo, $transformHandler);
$transformApplier->applyChanges($dpo);
```

## 6. Error Codes

| Code | Constant | Trigger |
|------|----------|---------|
| 2501 | `REFLECTION_ANALYSIS_ERROR` | `ReflectionException` during analysis |
| 2502 | `REFLECTION_INSPECTION_ERROR` | `ReflectionException` during inspection |
| 2503 | `GENERAL_ANALYSIS_ERROR` | `Error` during analysis |
| 2504 | `GENERAL_INSPECTION_ERROR` | `Exception` during inspection |
| 2505 | `CRITICAL_INSPECTION_ERROR` | `Error` during inspection |

## 7. Dependencies

```
kariricode/property-inspector
├── php ^8.4
├── kariricode/contract ^3.0
└── kariricode/exception ^1.2
```

Zero external dependencies. Contract ^3.0 resolves circular dependency issues across the ecosystem (see ADR-002 in kariricode-contract).

## 8. ARFA 1.3 Compliance

| Principle | Compliance | Notes |
|-----------|------------|-------|
| P1: Immutable State | Partial | `readonly class PropertyAccessor`; AttributeAnalyzer cache is mutable by design |
| P2: Reactive Flow | N/A | Synchronous component; async via adapter at Framework level |
| P3: Adaptive Context | N/A | No adaptive behavior needed |
| P4: Protocol Agnostic | ✓ | Works with any object regardless of protocol context |
| P5: Observability | Partial | Exception chain preserved; metrics via consumer |
