<?php

declare(strict_types=1);

namespace BVP\Tenjisagi;

use Carbon\CarbonImmutable as Carbon;
use RuntimeException;

/**
 * @author shimomo
 */
final class CourseChangeRepository
{
    /**
     * @param string $directory
     */
    public function __construct(private readonly string $directory)
    {
        //
    }

    /**
     * @param \Carbon\CarbonImmutable $date
     * @param array<int, array{
     *     number: int,
     *     name: string,
     *     race_count: int,
     *     changed_race_count: int,
     *     changed_race_rate: float,
     *     outer_race_count: int,
     *     outer_race_rate: float,
     *     inner_race_count: int,
     *     inner_race_rate: float,
     * }> $stats
     * @return string
     * @throws \RuntimeException
     */
    public function save(Carbon $date, array $stats): string
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            throw new RuntimeException("Failed to create directory: {$this->directory}");
        }

        $path = rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . $date->format('Ymd') . '.json';

        ksort($stats);

        $json = JsonEncoder::encode([
            'date' => $date->format('Y-m-d'),
            'racers' => $stats,
        ]);

        if (file_put_contents($path, $json) === false) {
            throw new RuntimeException("Failed to write file: {$path}");
        }

        return $path;
    }
}
