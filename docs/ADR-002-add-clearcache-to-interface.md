# ADR-002: Add clearCache() to AttributeAnalyzer Interface

**Status:** Accepted
**Date:** 2026-02-28
**Authors:** Walmir Silva

## Context

`AttributeAnalyzer` (the concrete class) exposed a `clearCache()` method that was not declared in the `Contract\AttributeAnalyzer` interface. This violated the Interface Segregation Principle (ISP): consumers holding the interface reference had no way to clear the cache without downcasting.

The cache is a critical concern for long-running processes (Swoole, RoadRunner, ReactPHP) where class structures may change dynamically or memory pressure requires periodic cleanup.

## Decision

Add `clearCache(): void` to `Contract\AttributeAnalyzer`.

```php
interface AttributeAnalyzer
{
    public function analyzeObject(object $object): array;
    public function clearCache(): void;  // ← Added
}
```

## Consequences

**Positive:**

- Consumers can clear cache through the interface (dependency inversion)
- Long-running process support without downcasting
- Full contract coverage — no hidden implementation surface

**Negative:**

- All implementations must now provide `clearCache()`. For implementations without caching, this is a trivial no-op.

**Migration:**

Any custom `AttributeAnalyzer` implementation must add:

```php
public function clearCache(): void
{
    // No-op if implementation does not cache
}
```

## References

- Martin, R.C. — "Interface Segregation Principle" (SOLID)
- ARFA 1.3 §4.3: Contract completeness
- PHP-FIG PSR-6/PSR-16 precedent: `clear()` on cache interfaces
