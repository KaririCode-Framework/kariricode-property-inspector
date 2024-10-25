<?php

declare(strict_types=1);

class WeakMapBenchmark
{
    private const ITERATIONS = 10000;
    private const ANSI_GREEN = "\033[32m";
    private const ANSI_YELLOW = "\033[33m";
    private const ANSI_RED = "\033[31m";
    private const ANSI_RESET = "\033[0m";
    private const ANSI_BOLD = "\033[1m";

    public function runBenchmark(): void
    {
        $results = [
            'Memory Usage' => $this->benchmarkMemoryUsage(),
            'Cache Access Speed' => $this->benchmarkCacheAccess(),
            'Garbage Collection' => $this->benchmarkGarbageCollection(),
        ];

        $this->printResults($results);
    }

    private function benchmarkMemoryUsage(): array
    {
        // Array Cache
        $start = memory_get_usage();
        $arrayCache = [];
        $objects = [];

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $obj = new stdClass();
            $objects[] = $obj;
            $arrayCache[spl_object_hash($obj)] = "data_$i";
        }

        $arrayMemory = memory_get_usage() - $start;
        unset($arrayCache, $objects);

        // WeakMap Cache
        $start = memory_get_usage();
        $weakMap = new WeakMap();
        $objects = [];

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $obj = new stdClass();
            $objects[] = $obj;
            $weakMap[$obj] = "data_$i";
        }

        $weakMapMemory = memory_get_usage() - $start;
        unset($weakMap, $objects);

        $percentDiff = (($arrayMemory - $weakMapMemory) / $arrayMemory) * 100;

        return [
            'Array Cache' => number_format($arrayMemory / 1024, 2),
            'WeakMap Cache' => number_format($weakMapMemory / 1024, 2),
            'Difference' => number_format(abs($percentDiff), 2),
            'Winner' => $weakMapMemory < $arrayMemory ? 'WeakMap' : 'Array',
        ];
    }

    private function benchmarkCacheAccess(): array
    {
        // Array Cache
        $arrayCache = [];
        $objects = [];
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $obj = new stdClass();
            $objects[] = $obj;
            $arrayCache[spl_object_hash($obj)] = "data_$i";
        }

        $start = microtime(true);
        foreach ($objects as $obj) {
            $data = $arrayCache[spl_object_hash($obj)];
        }
        $arrayTime = microtime(true) - $start;

        // WeakMap Cache
        $weakMap = new WeakMap();
        $objects = [];
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $obj = new stdClass();
            $objects[] = $obj;
            $weakMap[$obj] = "data_$i";
        }

        $start = microtime(true);
        foreach ($objects as $obj) {
            $data = $weakMap[$obj];
        }
        $weakMapTime = microtime(true) - $start;

        $percentDiff = (($arrayTime - $weakMapTime) / $arrayTime) * 100;

        return [
            'Array Access' => number_format($arrayTime * 1000, 4),
            'WeakMap Access' => number_format($weakMapTime * 1000, 4),
            'Difference' => number_format(abs($percentDiff), 2),
            'Winner' => $weakMapTime < $arrayTime ? 'WeakMap' : 'Array',
        ];
    }

    private function benchmarkGarbageCollection(): array
    {
        // Array Cache
        $start = microtime(true);
        $arrayCache = [];
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $obj = new stdClass();
            $arrayCache[spl_object_hash($obj)] = "data_$i";
            unset($obj);
        }
        gc_collect_cycles();
        $arrayTime = microtime(true) - $start;

        // WeakMap Cache
        $start = microtime(true);
        $weakMap = new WeakMap();
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $obj = new stdClass();
            $weakMap[$obj] = "data_$i";
            unset($obj);
        }
        gc_collect_cycles();
        $weakMapTime = microtime(true) - $start;

        $percentDiff = (($arrayTime - $weakMapTime) / $arrayTime) * 100;

        return [
            'Array GC' => number_format($arrayTime * 1000, 4),
            'WeakMap GC' => number_format($weakMapTime * 1000, 4),
            'Difference' => number_format(abs($percentDiff), 2),
            'Winner' => $weakMapTime < $arrayTime ? 'WeakMap' : 'Array',
        ];
    }

    private function printResults(array $results): void
    {
        echo self::ANSI_BOLD . "\nWEAKMAP VS ARRAY CACHE BENCHMARK\n" . self::ANSI_RESET;
        echo str_repeat('=', 50) . "\n\n";

        foreach ($results as $testName => $data) {
            echo self::ANSI_BOLD . "$testName Test\n" . self::ANSI_RESET;
            echo str_repeat('-', 30) . "\n";

            foreach ($data as $metric => $value) {
                if ('Winner' === $metric) {
                    continue;
                }

                $color = $this->getColor($data, $metric);
                $unit = $this->getUnit($testName, $metric);
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
                "Winner: {$data['Winner']}",
                self::ANSI_RESET
            );
        }

        echo str_repeat('=', 50) . "\n";
    }

    private function getUnit(string $testName, string $metric): string
    {
        if ('Difference' === $metric) {
            return '%';
        }

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

        $winner = $data['Winner'];
        $isWinner = false !== strpos($metric, $winner);

        return $isWinner ? self::ANSI_GREEN : self::ANSI_RED;
    }
}

// Run the benchmark
$benchmark = new WeakMapBenchmark();
$benchmark->runBenchmark();
