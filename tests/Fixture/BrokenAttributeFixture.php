<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests\Fixture;

use KaririCode\PropertyInspector\Tests\Fixture\Attribute\BrokenAttribute;

/**
 * Fixture with a property annotated with BrokenAttribute.
 * Used to force ReflectionException inside AttributeAnalyzer::analyzeObject()
 * when newInstance() is called during cacheObjectMetadata().
 */
final class BrokenAttributeFixture
{
    #[BrokenAttribute]
    public string $broken = 'value';
}
