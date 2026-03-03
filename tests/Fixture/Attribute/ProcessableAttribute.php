<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests\Fixture\Attribute;

interface ProcessableAttribute
{
    /** @return list<string> */
    public function getProcessors(): array;
}
