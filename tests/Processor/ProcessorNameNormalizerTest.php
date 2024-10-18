<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests\Processor;

use KaririCode\PropertyInspector\Processor\ProcessorNameNormalizer;
use PHPUnit\Framework\TestCase;

class ProcessorNameNormalizerTest extends TestCase
{
    private ProcessorNameNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ProcessorNameNormalizer();
    }

    public function testNormalizeWithStringKey(): void
    {
        $result = $this->normalizer->normalize('processor_name', []);

        $this->assertEquals('processor_name', $result);
    }

    public function testNormalizeWithIntegerKey(): void
    {
        $result = $this->normalizer->normalize(0, ['processor_name' => []]);

        $this->assertEquals('processor_name', $result);
    }

    public function testNormalizeWithEmptyProcessor(): void
    {
        $result = $this->normalizer->normalize(0, []);

        $this->assertEquals('', $result);
    }
}
