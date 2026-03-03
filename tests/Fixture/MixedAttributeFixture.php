<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests\Fixture;

use KaririCode\PropertyInspector\Tests\Fixture\Attribute\Transform;
use KaririCode\PropertyInspector\Tests\Fixture\Attribute\Validate;

final class MixedAttributeFixture
{
    #[Validate(processors: ['required'])]
    #[Transform(processors: ['uppercase'])]
    public string $name = 'test';

    #[Transform(processors: ['slug'])]
    public string $title = 'My Title';
}
