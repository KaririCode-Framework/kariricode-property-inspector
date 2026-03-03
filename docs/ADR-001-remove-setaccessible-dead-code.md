# ADR-001: Remove setAccessible() Dead Code

**Status:** Accepted
**Date:** 2026-02-28
**Authors:** Walmir Silva

## Context

`PropertyAccessor` and `AttributeAnalyzer` contained calls to `ReflectionProperty::setAccessible(true)` and a full accessibility save/restore cycle (`$wasAccessible`, `makeAccessible()`, `restoreAccessibility()`).

Since PHP 8.1 (RFC: "Make ReflectionProperty::setAccessible() no-op"), `setAccessible()` is a no-op — all properties are accessible via reflection regardless of visibility. The KaririCode ecosystem targets PHP 8.4+ per ARFA 1.3.

## Decision

Remove all `setAccessible()` calls and the associated save/restore infrastructure:

| File | Removed |
|------|---------|
| `Utility/PropertyAccessor.php` | `$wasAccessible`, `makeAccessible()`, `restoreAccessibility()`, 2× `setAccessible()` |
| `AttributeAnalyzer.php` | 1× `setAccessible(true)` in `cacheObjectMetadata()` |

## Consequences

**Positive:**

- 15 fewer lines of dead code in PropertyAccessor (47 → 32 LOC)
- 1 fewer line in AttributeAnalyzer
- Removes false sense of security (the restore call never actually re-restricted access)
- Eliminates misleading code paths for contributors

**Negative:**

- None. Behavior is identical on PHP 8.1+.

**Compatibility:**

- No change to public API surface
- No change to runtime behavior on any supported PHP version (8.4+)

## References

- [PHP RFC: Make ReflectionProperty::setAccessible() no-op](https://wiki.php.net/rfc/make-reflection-setaccessible-no-op)
- PHP 8.1 Changelog
- ARFA 1.3 §2.1: Target runtime PHP 8.4+
