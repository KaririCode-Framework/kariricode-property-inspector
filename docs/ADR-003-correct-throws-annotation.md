# ADR-003: Correct @throws on AttributeAnalyzer Contract

**Status:** Accepted
**Date:** 2026-02-28
**Authors:** Walmir Silva

## Context

`Contract\AttributeAnalyzer::analyzeObject()` declared `@throws \ReflectionException`, but the concrete implementation catches `\ReflectionException` internally and wraps it into `PropertyInspectionException`. The interface was lying to consumers about what exceptions to expect.

## Decision

Replace `@throws \ReflectionException` with `@throws PropertyInspectionException`.

```php
// Before (incorrect)
@throws \ReflectionException

// After (matches implementation)
@throws PropertyInspectionException
```

## Consequences

**Positive:**

- Consumers catch the correct exception type
- PHPDoc aligns with actual throw semantics
- PHPStan and Psalm can validate catch blocks accurately

**Negative:**

- Consumers catching `\ReflectionException` from `analyzeObject()` will need to catch `PropertyInspectionException` instead. The original `\ReflectionException` is preserved as `$previous`.

## References

- Ousterhout, J. — "A Philosophy of Software Design", Ch. 10: Define Errors Out of Existence
- ARFA 1.3 §5.1: Exception wrapping at domain boundaries
