<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests\Fixture\Attribute;

use Attribute;

/**
 * A deliberately broken attribute whose constructor throws ReflectionException.
 * Used to test the error-handling branch in AttributeAnalyzer::analyzeObject().
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class BrokenAttribute
{
    public function __construct()
    {
        throw new \ReflectionException('BrokenAttribute intentionally broken');
    }
}
