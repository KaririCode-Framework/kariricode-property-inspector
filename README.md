# KaririCode PropertyInspector

<div align="center">

[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-22c55e.svg)](LICENSE)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-Level%209-4F46E5)](https://phpstan.org/)
[![Tests](https://img.shields.io/badge/Tests-40%20passing-22c55e)](https://kariricode.org)
[![ARFA](https://img.shields.io/badge/ARFA-1.3-orange)](https://kariricode.org)
[![KaririCode Framework](https://img.shields.io/badge/KaririCode-Framework-orange)](https://kariricode.org)

**Attribute-based property analysis and inspection for the KaririCode Framework —  
multi-pass pipelines, reflection caching, and zero-overhead property mutation, PHP 8.4+.**

[Installation](#installation) · [Quick Start](#quick-start) · [Features](#features) · [Pipeline](#the-inspection-pipeline) · [Architecture](#architecture)

</div>

---

## The Problem

PHP reflection is boilerplate-heavy, error-prone, and slow when repeated across object graphs:

```php
// The old way: raw reflection on every request
$ref = new ReflectionClass($user);
foreach ($ref->getProperties() as $prop) {
    $attrs = $prop->getAttributes(Validate::class);
    foreach ($attrs as $attr) {
        $prop->setAccessible(true); // deprecated in PHP 8.4
        $value = $prop->getValue($user);
        // now what? where does the result go? how do you write it back?
    }
}
```

No caching, no mutation abstraction, no error isolation, no handler contract — just raw loops you repeat in every project.

## The Solution

```php
use KaririCode\PropertyInspector\AttributeAnalyzer;
use KaririCode\PropertyInspector\Utility\PropertyInspector;
use KaririCode\PropertyInspector\Utility\PropertyAccessor;

// 1. Configure which attribute to scan for
$analyzer  = new AttributeAnalyzer(Validate::class);
$inspector = new PropertyInspector($analyzer);

// 2. Inspect — results cached after first call per class
$handler = new MyValidationHandler();
$inspector->inspect($user, $handler);

// 3. Read processed values and errors
$values = $handler->getProcessedPropertyValues();
$errors = $handler->getProcessingResultErrors();

// 4. Write back changed values via PropertyAccessor
$accessor = new PropertyAccessor($user, 'email');
$accessor->setValue(strtolower($accessor->getValue()));
```

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.4 or higher |
| kariricode/contract | ^2.8 |
| kariricode/exception | ^1.2 |

---

## Installation

```bash
composer require kariricode/property-inspector
```

---

## Quick Start

Define an attribute, an entity, a handler — and inspect:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Attribute;
use KaririCode\PropertyInspector\AttributeAnalyzer;
use KaririCode\PropertyInspector\Contract\PropertyAttributeHandler;
use KaririCode\PropertyInspector\Utility\PropertyInspector;

// 1. Define a custom attribute
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Validate
{
    public function __construct(public readonly array $rules = []) {}
}

// 2. Define an entity with annotated properties
final class User
{
    public function __construct(
        #[Validate(['required', 'min:3'])]
        public string $name = '',

        #[Validate(['required', 'email'])]
        public string $email = '',

        #[Validate(['required', 'min:18'])]
        public int $age = 0,
    ) {}
}

// 3. Implement a handler
final class ValidationHandler implements PropertyAttributeHandler
{
    private array $processed = [];
    private array $errors    = [];

    public function handleAttribute(string $propertyName, object $attribute, mixed $value): mixed
    {
        $this->processed[$propertyName] = $value;

        if ($attribute instanceof Validate) {
            foreach ($attribute->rules as $rule) {
                if ($rule === 'required' && ($value === '' || $value === null)) {
                    $this->errors[$propertyName]['required'] = 'Field is required';
                }
                if (str_starts_with($rule, 'min:')) {
                    $min = (int) substr($rule, 4);
                    if (is_string($value) && strlen($value) < $min) {
                        $this->errors[$propertyName]['min'] = "Min {$min} chars required";
                    }
                    if (is_int($value) && $value < $min) {
                        $this->errors[$propertyName]['min'] = "Must be at least {$min}";
                    }
                }
            }
        }

        return $value;
    }

    public function getProcessedPropertyValues(): array { return $this->processed; }
    public function getProcessingResultMessages(): array { return []; }
    public function getProcessingResultErrors(): array   { return $this->errors; }
}

// 4. Run the pipeline
$user      = new User(name: 'Walmir', email: 'walmir@kariricode.org', age: 30);
$analyzer  = new AttributeAnalyzer(Validate::class);
$inspector = new PropertyInspector($analyzer);
$handler   = new ValidationHandler();

$inspector->inspect($user, $handler);

var_dump($handler->getProcessedPropertyValues());
// ['name' => 'Walmir', 'email' => 'walmir@kariricode.org', 'age' => 30]

var_dump($handler->getProcessingResultErrors());
// [] — all good
```

---

## Features

### Reflection Caching

`AttributeAnalyzer` caches reflection metadata after the first analysis per class. Subsequent calls for the same class — even with different object instances — skip `ReflectionClass` entirely:

```php
$analyzer = new AttributeAnalyzer(Validate::class);
$inspector = new PropertyInspector($analyzer);

// First call: reflection + cache build
$inspector->inspect($user1, $handler1);

// Subsequent calls: metadata from cache — zero reflection overhead
$inspector->inspect($user2, $handler2);
$inspector->inspect($user3, $handler3);

// Force re-analysis when needed (e.g., after metadata change)
$analyzer->clearCache();
```

### Multi-Pass Inspection

Run multiple independent passes over the same object with different attribute types:

```php
// Pass 1: sanitize
$sanitizeInspector = new PropertyInspector(new AttributeAnalyzer(Sanitize::class));
$sanitizeHandler   = new TrimLowercaseHandler();
$sanitizeInspector->inspect($user, $sanitizeHandler);

// Apply sanitized values back to the object
foreach ($sanitizeHandler->getProcessedPropertyValues() as $prop => $value) {
    (new PropertyAccessor($user, $prop))->setValue($value);
}

// Pass 2: validate on sanitized data
$validateInspector = new PropertyInspector(new AttributeAnalyzer(Validate::class));
$validateHandler   = new ValidationHandler();
$validateInspector->inspect($user, $validateHandler);

$errors = $validateHandler->getProcessingResultErrors(); // [] if clean
```

### PropertyAccessor — Safe Property Mutation

Read and write any property (public, protected, private) without `setAccessible` boilerplate:

```php
use KaririCode\PropertyInspector\Utility\PropertyAccessor;

$accessor = new PropertyAccessor($user, 'email');

$current = $accessor->getValue();           // read
$accessor->setValue(strtolower($current));  // write (no setAccessible needed)
```

### Attribute Polymorphism

`AttributeAnalyzer` uses `ReflectionAttribute::IS_INSTANCEOF` — it matches **attribute hierarchies**, not just exact class names:

```php
// Matches Validate + any subclass of Validate
$analyzer = new AttributeAnalyzer(Validate::class);
```

### Isolated Error Handling

All exceptions from reflection or handler code are wrapped in `PropertyInspectionException` — your calling code only needs to catch one type:

```php
use KaririCode\PropertyInspector\Exception\PropertyInspectionException;

try {
    $inspector->inspect($user, $handler);
} catch (PropertyInspectionException $e) {
    // ReflectionException, TypeError, Error — all caught and re-wrapped
}
```

---

## The Inspection Pipeline

```
$inspector->inspect($object, $handler)
        │
        ▼
AttributeAnalyzer::analyzeObject($object)
  ├── Check class cache
  ├── If miss: ReflectionClass → getProperties()
  │       └── foreach property:
  │               getAttributes($attributeClass, IS_INSTANCEOF)
  │               newInstance() → cache [{attributes, property}]
  └── extractValues($object): [{value, attributes}]
        │
        ▼
foreach property → foreach attribute:
    $handler->handleAttribute($propertyName, $attribute, $value)
        │
        ▼
return $handler  (accumulates processed values + errors)
```

---

## Architecture

### Source layout

```
src/
├── AttributeAnalyzer.php      Core analyzer — reflection + cache + attribute extraction
├── Contract/
│   ├── AttributeAnalyzer.php       Interface: analyzeObject · clearCache
│   ├── PropertyAttributeHandler.php Interface: handleAttribute · getProcessed* · getErrors
│   ├── PropertyChangeApplier.php   Interface: applyChanges
│   └── PropertyInspector.php       Interface: inspect
├── Exception/
│   └── PropertyInspectionException.php  Named factory methods per failure mode
└── Utility/
    ├── PropertyAccessor.php   Safe property read/write (private, protected, public)
    └── PropertyInspector.php  Orchestrator: delegates analysis → handler
```

### Key design decisions

| Decision | Rationale | ADR |
|---|---|---|
| Reflection cache per class | One `ReflectionClass` call per type, not per instance | — |
| Remove `setAccessible` | Deprecated in PHP 8.1, removed in PHP 9; `PropertyAccessor` handles this | [ADR-001](docs/ADR-001-remove-setaccessible-dead-code.md) |
| `clearCache()` on interface | Enables test isolation and dynamic class reloading | [ADR-002](docs/ADR-002-add-clearcache-to-interface.md) |
| Wrapped exception hierarchy | Callers catch `PropertyInspectionException`, not reflection internals | [ADR-003](docs/ADR-003-correct-throws-annotation.md) |
| Handler-returned values | Handler decides the processed value — supports chaining and transformation | — |

### Specifications

| Spec | Covers |
|---|---|
| [SPEC-001](docs/SPEC-001-property-inspection-pipeline.md) | Full pipeline: analysis → handler → mutation |

---

## Integration with the KaririCode Ecosystem

PropertyInspector is the **reflection engine** used internally by other KaririCode components:

| Component | Role |
|---|---|
| `kariricode/validator` | Uses `PropertyInspector` to discover `#[Rule]` attributes and dispatch to rule processors |
| `kariricode/sanitizer` | Uses `PropertyInspector` to discover `#[Sanitize]` attributes and apply transformers |
| `kariricode/normalizer` | Uses `PropertyInspector` for attribute-driven normalization passes |

Any component that needs **declarative, attribute-based property processing** can be built on top of this pipeline.

---

## Project Stats

| Metric | Value |
|---|---|
| PHP source files | 7 |
| External runtime dependencies | 2 (contract · exception) |
| Test suite | 40 tests · 96 assertions |
| PHPStan level | 9 |
| PHP version | 8.4+ |
| ARFA compliance | 1.3 |
| Test suites | Unit + Integration |
| Reflection cache | Per-class, per-`AttributeAnalyzer` instance |

---

## Contributing

```bash
git clone https://github.com/KaririCode-Framework/kariricode-property-inspector.git
cd kariricode-property-inspector
composer install
kcode init
kcode quality  # Must pass before opening a PR
```

---

## License

[MIT License](LICENSE) © [Walmir Silva](mailto:community@kariricode.org)

---

<div align="center">

Part of the **[KaririCode Framework](https://kariricode.org)** ecosystem.

[kariricode.org](https://kariricode.org) · [GitHub](https://github.com/KaririCode-Framework/kariricode-property-inspector) · [Packagist](https://packagist.org/packages/kariricode/property-inspector) · [Issues](https://github.com/KaririCode-Framework/kariricode-property-inspector/issues)

</div>
