<?php

declare(strict_types=1);

class FormattedBenchmark
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
            'Match vs Ternary' => $this->benchmarkMatchVsTernary(),
            'Array Walk vs Foreach' => $this->benchmarkArrayWalkVsForeach(),
            'Array Map/Filter vs Foreach' => $this->benchmarkArrayMapVsForeach(),
        ];

        $this->printResults($results);
    }

    private function benchmarkMatchVsTernary(): array
    {
        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $result = match (true) {
                !(0 === $i % 2) => null,
                default => $i,
            };
        }
        $matchTime = microtime(true) - $start;

        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $result = (0 === $i % 2) ? $i : null;
        }
        $ternaryTime = microtime(true) - $start;

        $percentDiff = (($matchTime - $ternaryTime) / $matchTime) * 100;

        return [
            'Match Operation' => number_format($matchTime * 1000, 4),
            'Ternary Operation' => number_format($ternaryTime * 1000, 4),
            'Difference' => number_format(abs($percentDiff), 2),
            'Winner' => $ternaryTime < $matchTime ? 'Ternary' : 'Match',
        ];
    }

    private function benchmarkArrayWalkVsForeach(): array
    {
        $largeArray = array_fill(0, 10000, 'value');

        $start = microtime(true);
        array_walk($largeArray, function ($value, $key) {
            $dummy = $value . $key;
        });
        $arrayWalkTime = microtime(true) - $start;

        $start = microtime(true);
        foreach ($largeArray as $key => $value) {
            $dummy = $value . $key;
        }
        $foreachTime = microtime(true) - $start;

        $percentDiff = (($arrayWalkTime - $foreachTime) / $arrayWalkTime) * 100;

        return [
            'Array Walk' => number_format($arrayWalkTime * 1000, 4),
            'Foreach Loop' => number_format($foreachTime * 1000, 4),
            'Difference' => number_format(abs($percentDiff), 2),
            'Winner' => $foreachTime < $arrayWalkTime ? 'Foreach' : 'Array Walk',
        ];
    }

    private function benchmarkArrayMapVsForeach(): array
    {
        $processors = array_fill(0, 100, ['config' => 'value']);

        $start = microtime(true);
        $result = array_filter(array_map(
            fn ($config) => $config['config'],
            $processors
        ));
        $arrayMapTime = microtime(true) - $start;

        $start = microtime(true);
        $result = [];
        foreach ($processors as $config) {
            if ($value = $config['config']) {
                $result[] = $value;
            }
        }
        $foreachTime = microtime(true) - $start;

        $percentDiff = (($arrayMapTime - $foreachTime) / $arrayMapTime) * 100;

        return [
            'Array Map/Filter' => number_format($arrayMapTime * 1000, 4),
            'Foreach Loop' => number_format($foreachTime * 1000, 4),
            'Difference' => number_format(abs($percentDiff), 2),
            'Winner' => $foreachTime < $arrayMapTime ? 'Foreach' : 'Array Map/Filter',
        ];
    }

    private function printResults(array $results): void
    {
        echo self::ANSI_BOLD . "\nPERFORMANCE BENCHMARK RESULTS\n" . self::ANSI_RESET;
        echo str_repeat('=', 50) . "\n\n";

        foreach ($results as $testName => $data) {
            echo self::ANSI_BOLD . "$testName Test\n" . self::ANSI_RESET;
            echo str_repeat('-', 30) . "\n";

            foreach ($data as $metric => $value) {
                if ('Winner' === $metric) {
                    continue;
                }

                $color = $this->getColor($data, $metric);
                echo sprintf(
                    "%s%-20s: %s%s%s\n",
                    $color,
                    $metric,
                    $value,
                    'Difference' !== $metric ? ' ms' : '%',
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
$benchmark = new FormattedBenchmark();
$benchmark->runBenchmark();
