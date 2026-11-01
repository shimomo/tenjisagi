<?php

declare(strict_types=1);

namespace BVP\Tenjisagi;

use Carbon\CarbonImmutable as Carbon;
use RuntimeException;

/**
 * @author shimomo
 */
final class CourseChangeSummaryRepository
{
    /**
     * @param string $path
     */
    public function __construct(private readonly string $path)
    {
        //
    }

    /**
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
    public function save(array $stats): string
    {
        $directory = dirname($this->path);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException("Failed to create directory: {$directory}");
        }

        if (is_file($this->path) && !unlink($this->path)) {
            throw new RuntimeException("Failed to remove existing file: {$this->path}");
        }

        uasort($stats, static fn (array $a, array $b): int => $b['outer_race_rate'] <=> $a['outer_race_rate']);

        $json = JsonEncoder::encode([
            'generated_at' => Carbon::now()->toIso8601String(),
            'racers' => $stats,
        ]);

        if (file_put_contents($this->path, $json) === false) {
            throw new RuntimeException("Failed to write file: {$this->path}");
        }

        return $this->path;
    }
}
