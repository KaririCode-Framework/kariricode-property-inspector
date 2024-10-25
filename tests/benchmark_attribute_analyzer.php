<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests;

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\PropertyInspector\AttributeAnalyzer;
use KaririCode\PropertyInspector\Contract\AttributeAnalyzer as AttributeAnalyzerContract;
use KaririCode\PropertyInspector\Exception\PropertyInspectionException;

#[\Attribute]
class TestAttribute
{
    public function __construct(public string $value = '')
    {
    }
}

class TestClass
{
    #[TestAttribute('test1')]
    private string $prop1 = 'value1';

    #[TestAttribute('test2')]
    private int $prop2 = 42;

    #[TestAttribute('test3')]
    private array $prop3 = ['test'];
}

class AttributeAnalyzerBenchmark
{
    private const ITERATIONS = 10000;
    private const ANSI_GREEN = "\033[32m";
    private const ANSI_RED = "\033[31m";
    private const ANSI_YELLOW = "\033[33m";
    private const ANSI_RESET = "\033[0m";
    private const ANSI_BOLD = "\033[1m";

    private AttributeAnalyzerContract $originalAnalyzer;
    private AttributeAnalyzerContract $optimizedAnalyzer;
    private array $testObjects;

    public function __construct()
    {
        $this->originalAnalyzer = new AttributeAnalyzer(TestAttribute::class);
        $this->optimizedAnalyzer = new OptimizedAttributeAnalyzer(TestAttribute::class);
        $this->testObjects = array_fill(0, self::ITERATIONS, new TestClass());
    }

    public function runBenchmark(): void
    {
        $results = [
            'Memory Usage' => $this->benchmarkMemoryUsage(),
            'Processing Time' => $this->benchmarkProcessingTime(),
            'Property Access' => $this->benchmarkPropertyAccess(),
        ];

        $this->printResults($results);
    }

    private function calculateDifference(float $original, float $optimized): float
    {
        if (0.0 === $original) {
            return 0.0 === $optimized ? 0.0 : 100.0;
        }

        return (($original - $optimized) / $original) * 100;
    }

    private function benchmarkMemoryUsage(): array
    {
        // Limpar cache e garbage collector antes de começar
        gc_collect_cycles();

        // Original Version
        $startMemory = memory_get_usage(true);
        foreach ($this->testObjects as $object) {
            $this->originalAnalyzer->analyzeObject($object);
        }
        $originalMemory = memory_get_usage(true) - $startMemory;

        // Limpar entre testes
        gc_collect_cycles();

        // Optimized Version
        $startMemory = memory_get_usage(true);
        foreach ($this->testObjects as $object) {
            $this->optimizedAnalyzer->analyzeObject($object);
        }
        $optimizedMemory = memory_get_usage(true) - $startMemory;

        $difference = $this->calculateDifference($originalMemory, $optimizedMemory);

        return [
            'Original' => number_format($originalMemory / 1024, 2),
            'Optimized' => number_format($optimizedMemory / 1024, 2),
            'Difference' => number_format(abs($difference), 2),
            'Improvement' => $difference > 0 ? 'Yes' : 'No',
        ];
    }

    private function benchmarkProcessingTime(): array
    {
        // Aquecimento
        foreach ($this->testObjects as $object) {
            $this->originalAnalyzer->analyzeObject($object);
            $this->optimizedAnalyzer->analyzeObject($object);
        }

        // Original Version
        $start = microtime(true);
        foreach ($this->testObjects as $object) {
            $this->originalAnalyzer->analyzeObject($object);
        }
        $originalTime = microtime(true) - $start;

        // Optimized Version
        $start = microtime(true);
        foreach ($this->testObjects as $object) {
            $this->optimizedAnalyzer->analyzeObject($object);
        }
        $optimizedTime = microtime(true) - $start;

        $difference = $this->calculateDifference($originalTime, $optimizedTime);

        return [
            'Original' => number_format($originalTime * 1000, 4),
            'Optimized' => number_format($optimizedTime * 1000, 4),
            'Difference' => number_format(abs($difference), 2),
            'Improvement' => $difference > 0 ? 'Yes' : 'No',
        ];
    }

    private function benchmarkPropertyAccess(): array
    {
        $object = new TestClass();

        // Aquecimento
        for ($i = 0; $i < 1000; ++$i) {
            $this->originalAnalyzer->analyzeObject($object);
            $this->optimizedAnalyzer->analyzeObject($object);
        }

        // Original Version
        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $this->originalAnalyzer->analyzeObject($object);
        }
        $originalTime = microtime(true) - $start;

        // Optimized Version
        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $this->optimizedAnalyzer->analyzeObject($object);
        }
        $optimizedTime = microtime(true) - $start;

        $difference = $this->calculateDifference($originalTime, $optimizedTime);

        return [
            'Original' => number_format($originalTime * 1000, 4),
            'Optimized' => number_format($optimizedTime * 1000, 4),
            'Difference' => number_format(abs($difference), 2),
            'Improvement' => $difference > 0 ? 'Yes' : 'No',
        ];
    }

    private function printResults(array $results): void
    {
        echo self::ANSI_BOLD . "\nATTRIBUTE ANALYZER BENCHMARK\n" . self::ANSI_RESET;
        echo str_repeat('=', 50) . "\n\n";

        foreach ($results as $testName => $data) {
            echo self::ANSI_BOLD . "$testName\n" . self::ANSI_RESET;
            echo str_repeat('-', 30) . "\n";

            foreach ($data as $metric => $value) {
                if ('Improvement' === $metric) {
                    continue;
                }

                $unit = 'Difference' === $metric ? '%' : ('Memory Usage' === $testName ? ' KB' : ' ms');
                $color = $this->getMetricColor($metric, $data);

                echo sprintf(
                    "%s%-15s: %s%s%s\n",
                    $color,
                    $metric,
                    $value,
                    $unit,
                    self::ANSI_RESET
                );
            }

            echo sprintf(
                "\n%s%s%s\n\n",
                'Yes' === $data['Improvement'] ? self::ANSI_GREEN : self::ANSI_RED,
                'Improvement: ' . ('Yes' === $data['Improvement'] ? 'Yes' : 'No'),
                self::ANSI_RESET
            );
        }

        echo str_repeat('=', 50) . "\n";
    }

    private function getMetricColor(string $metric, array $data): string
    {
        if ('Difference' === $metric) {
            return self::ANSI_YELLOW;
        }

        if ('Yes' === $data['Improvement']) {
            return 'Optimized' === $metric ? self::ANSI_GREEN : self::ANSI_RED;
        }

        return 'Original' === $metric ? self::ANSI_GREEN : self::ANSI_RED;
    }
}

final class OptimizedAttributeAnalyzer implements AttributeAnalyzerContract
{
    private array $cache = [];

    public function __construct(private readonly string $attributeClass)
    {
    }

    public function analyzeObject(object $object): array
    {
        try {
            $className = $object::class;

            // Usar cache se disponível
            if (!isset($this->cache[$className])) {
                $this->cacheObjectMetadata($object);
            }

            return $this->extractValues($object);
        } catch (\ReflectionException $e) {
            throw new PropertyInspectionException('Failed to analyze object: ' . $e->getMessage(), 0, $e);
        } catch (\Error $e) {
            throw new PropertyInspectionException('An error occurred during object analysis: ' . $e->getMessage(), 0, $e);
        }
    }

    private function cacheObjectMetadata(object $object): void
    {
        $className = $object::class;
        $reflection = new \ReflectionClass($object);
        $cachedProperties = [];

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes($this->attributeClass, \ReflectionAttribute::IS_INSTANCEOF);

            if (!empty($attributes)) {
                $property->setAccessible(true);
                $attributeInstances = array_map(
                    static fn (\ReflectionAttribute $attr): object => $attr->newInstance(),
                    $attributes
                );

                $cachedProperties[$property->getName()] = [
                    'attributes' => $attributeInstances,
                    'property' => $property,
                ];
            }
        }

        $this->cache[$className] = $cachedProperties;
    }

    private function extractValues(object $object): array
    {
        $results = [];
        $className = $object::class;

        foreach ($this->cache[$className] as $propertyName => $data) {
            $results[$propertyName] = [
                'value' => $data['property']->getValue($object),
                'attributes' => $data['attributes'],
            ];
        }

        return $results;
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }
}

// Before
// final readonly class AttributeAnalyzer implements AttributeAnalyzerContract
// {
//     public function __construct(private string $attributeClass)
//     {
//     }

//     public function analyzeObject(object $object): array
//     {
//         try {
//             $results = [];
//             $reflection = new \ReflectionClass($object);

//             foreach ($reflection->getProperties() as $property) {
//                 $propertyResult = $this->analyzeProperty($object, $property);
//                 if (null !== $propertyResult) {
//                     $results[$property->getName()] = $propertyResult;
//                 }
//             }

//             return $results;
//         } catch (\ReflectionException $e) {
//             throw new PropertyInspectionException('Failed to analyze object: ' . $e->getMessage(), 0, $e);
//         } catch (\Error $e) {
//             throw new PropertyInspectionException('An error occurred during object analysis: ' . $e->getMessage(), 0, $e);
//         }
//     }

//     private function analyzeProperty(object $object, \ReflectionProperty $property): ?array
//     {
//         $attributes = $property->getAttributes($this->attributeClass, \ReflectionAttribute::IS_INSTANCEOF);
//         if (empty($attributes)) {
//             return null;
//         }

//         $property->setAccessible(true);
//         $propertyValue = $property->getValue($object);

//         $attributeInstances = array_map(
//             static fn (\ReflectionAttribute $attr): object => $attr->newInstance(),
//             $attributes
//         );

//         return [
//             'value' => $propertyValue,
//             'attributes' => $attributeInstances,
//         ];
//     }
// }

// Executar o benchmark
$benchmark = new AttributeAnalyzerBenchmark();
$benchmark->runBenchmark();
