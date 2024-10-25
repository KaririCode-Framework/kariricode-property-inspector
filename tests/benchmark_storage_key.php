<?php

declare(strict_types=1);

// Implementações para teste
enum StorageType: string
{
    case Property = 'property';
}

readonly class StorageKey
{
    public function __construct(
        public string $identifier,
        public StorageType $type
    ) {
    }

    public static function forProperty(string $propertyName): self
    {
        return new self($propertyName, StorageType::Property);
    }
}

readonly class PropertyId
{
    public function __construct(public string $name)
    {
    }

    public function toString(): string
    {
        return $this->name;
    }
}

class CacheManager
{
    private WeakMap $cache;
    private array $keyCache;

    public function __construct()
    {
        $this->cache = new WeakMap();
    }

    public function set(PropertyId $id, mixed $value): void
    {
        $key = $this->getOrCreateKey($id);
        $this->cache[$key] = $value;
    }

    public function get(PropertyId $id): mixed
    {
        $key = $this->getOrCreateKey($id);

        return $this->cache[$key] ?? null;
    }

    private function getOrCreateKey(PropertyId $id): object
    {
        return $this->keyCache[$id->toString()] ??= new stdClass();
    }
}

class CompleteBenchmark
{
    private const ITERATIONS = 100000;
    private const ANSI_GREEN = "\033[32m";
    private const ANSI_YELLOW = "\033[33m";
    private const ANSI_RED = "\033[31m";
    private const ANSI_RESET = "\033[0m";
    private const ANSI_BOLD = "\033[1m";

    public function runBenchmark(): void
    {
        $results = [
            'Object Creation' => $this->benchmarkObjectCreation(),
            'Storage Operations' => $this->benchmarkStorageOperations(),
            'Memory Usage' => $this->benchmarkMemoryUsage(),
            'Batch Operations' => $this->benchmarkBatchOperations(),
        ];

        $this->printResults($results);
    }

    private function benchmarkObjectCreation(): array
    {
        // stdClass
        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $key = new stdClass();
        }
        $stdClassTime = microtime(true) - $start;

        // StorageKey
        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $key = StorageKey::forProperty("property_$i");
        }
        $storageKeyTime = microtime(true) - $start;

        // Hybrid
        $start = microtime(true);
        $cacheManager = new CacheManager();
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $id = new PropertyId("property_$i");
            $cacheManager->set($id, "value_$i");
        }
        $hybridTime = microtime(true) - $start;

        return [
            'stdClass' => number_format($stdClassTime * 1000, 4),
            'StorageKey' => number_format($storageKeyTime * 1000, 4),
            'Hybrid' => number_format($hybridTime * 1000, 4),
            'Best' => $this->findBest([
                'stdClass' => $stdClassTime,
                'StorageKey' => $storageKeyTime,
                'Hybrid' => $hybridTime,
            ]),
        ];
    }

    private function benchmarkStorageOperations(): array
    {
        $weakMapStd = new WeakMap();
        $weakMapStorage = new WeakMap();
        $cacheManager = new CacheManager();

        // stdClass
        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $key = new stdClass();
            $weakMapStd[$key] = "value_$i";
            $value = $weakMapStd[$key];
        }
        $stdClassTime = microtime(true) - $start;

        // StorageKey
        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $key = StorageKey::forProperty("property_$i");
            $weakMapStorage[$key] = "value_$i";
            $value = $weakMapStorage[$key];
        }
        $storageKeyTime = microtime(true) - $start;

        // Hybrid
        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $id = new PropertyId("property_$i");
            $cacheManager->set($id, "value_$i");
            $value = $cacheManager->get($id);
        }
        $hybridTime = microtime(true) - $start;

        return [
            'stdClass' => number_format($stdClassTime * 1000, 4),
            'StorageKey' => number_format($storageKeyTime * 1000, 4),
            'Hybrid' => number_format($hybridTime * 1000, 4),
            'Best' => $this->findBest([
                'stdClass' => $stdClassTime,
                'StorageKey' => $storageKeyTime,
                'Hybrid' => $hybridTime,
            ]),
        ];
    }

    private function benchmarkMemoryUsage(): array
    {
        // stdClass
        $start = memory_get_usage();
        $keys = [];
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $keys[] = new stdClass();
        }
        $stdClassMemory = memory_get_usage() - $start;
        unset($keys);

        // StorageKey
        $start = memory_get_usage();
        $keys = [];
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $keys[] = StorageKey::forProperty("property_$i");
        }
        $storageKeyMemory = memory_get_usage() - $start;
        unset($keys);

        // Hybrid
        $start = memory_get_usage();
        $cacheManager = new CacheManager();
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $id = new PropertyId("property_$i");
            $cacheManager->set($id, "value_$i");
        }
        $hybridMemory = memory_get_usage() - $start;

        return [
            'stdClass' => number_format($stdClassMemory / 1024, 2),
            'StorageKey' => number_format($storageKeyMemory / 1024, 2),
            'Hybrid' => number_format($hybridMemory / 1024, 2),
            'Best' => $this->findBest([
                'stdClass' => $stdClassMemory,
                'StorageKey' => $storageKeyMemory,
                'Hybrid' => $hybridMemory,
            ]),
        ];
    }

    private function benchmarkBatchOperations(): array
    {
        $dataSize = 10000;

        // stdClass
        $start = microtime(true);
        $weakMap = new WeakMap();
        for ($i = 0; $i < $dataSize; ++$i) {
            $key = new stdClass();
            $weakMap[$key] = "value_$i";
        }
        for ($i = 0; $i < $dataSize; ++$i) {
            $key = new stdClass();
            $weakMap[$key] = null;
        }
        $stdClassTime = microtime(true) - $start;

        // StorageKey
        $start = microtime(true);
        $weakMap = new WeakMap();
        for ($i = 0; $i < $dataSize; ++$i) {
            $key = StorageKey::forProperty("property_$i");
            $weakMap[$key] = "value_$i";
        }
        for ($i = 0; $i < $dataSize; ++$i) {
            $key = StorageKey::forProperty("property_$i");
            $weakMap[$key] = null;
        }
        $storageKeyTime = microtime(true) - $start;

        // Hybrid
        $start = microtime(true);
        $cacheManager = new CacheManager();
        for ($i = 0; $i < $dataSize; ++$i) {
            $id = new PropertyId("property_$i");
            $cacheManager->set($id, "value_$i");
        }
        for ($i = 0; $i < $dataSize; ++$i) {
            $id = new PropertyId("property_$i");
            $cacheManager->set($id, null);
        }
        $hybridTime = microtime(true) - $start;

        return [
            'stdClass' => number_format($stdClassTime * 1000, 4),
            'StorageKey' => number_format($storageKeyTime * 1000, 4),
            'Hybrid' => number_format($hybridTime * 1000, 4),
            'Best' => $this->findBest([
                'stdClass' => $stdClassTime,
                'StorageKey' => $storageKeyTime,
                'Hybrid' => $hybridTime,
            ]),
        ];
    }

    private function findBest(array $times): string
    {
        return array_keys($times, min($times))[0];
    }

    private function printResults(array $results): void
    {
        echo self::ANSI_BOLD . "\nCOMPLETE PERFORMANCE BENCHMARK\n" . self::ANSI_RESET;
        echo str_repeat('=', 50) . "\n\n";

        foreach ($results as $testName => $data) {
            echo self::ANSI_BOLD . "$testName Test\n" . self::ANSI_RESET;
            echo str_repeat('-', 30) . "\n";

            foreach ($data as $metric => $value) {
                if ('Best' === $metric) {
                    continue;
                }

                $color = $this->getColor($data, $metric);
                $unit = $this->getUnit($testName);
                echo sprintf(
                    "%s%-20s: %s%s%s\n",
                    $color,
                    $metric,
                    $value,
                    $unit,
                    self::ANSI_RESET
                );
            }

            echo sprintf(
                "\n%s%s%s\n\n",
                self::ANSI_GREEN,
                "Best Performance: {$data['Best']}",
                self::ANSI_RESET
            );
        }

        echo str_repeat('=', 50) . "\n";
    }

    private function getUnit(string $testName): string
    {
        return match ($testName) {
            'Memory Usage' => ' KB',
            default => ' ms'
        };
    }

    private function getColor(array $data, string $metric): string
    {
        if ('Difference' === $metric) {
            return self::ANSI_YELLOW;
        }

        $best = $data['Best'];

        return ($metric === $best) ? self::ANSI_GREEN : self::ANSI_RED;
    }
}

// Run the benchmark
$benchmark = new CompleteBenchmark();
$benchmark->runBenchmark();
