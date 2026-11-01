<?php

declare(strict_types=1);

namespace BVP\Tenjisagi;

use Carbon\CarbonImmutable as Carbon;
use RuntimeException;

/**
 * @author shimomo
 */
final class CourseChangeAggregator
{
    private const WINDOW_IN_DAYS = 365;
    private const MIN_RACE_COUNT = 30;

    /**
     * @param string $directory
     * @return array<int, array{
     *     number: int,
     *     name: string,
     *     race_count: int,
     *     changed_race_count: int,
     *     changed_race_rate: float,
     *     outer_race_count: int,
     *     outer_race_rate: float,
     *     inner_race_count: int,
     *     inner_race_rate: float,
     * }>
     * @throws \RuntimeException
     */
    public function aggregate(string $directory): array
    {
        $paths = glob(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . '*.json');

        if ($paths === false) {
            throw new RuntimeException("Failed to list JSON files in: {$directory}");
        }

        $since = Carbon::now()->subDays(self::WINDOW_IN_DAYS);
        $stats = [];

        foreach ($paths as $path) {
            $filename = pathinfo($path, PATHINFO_FILENAME);

            if (!preg_match('/^\d{8}$/', $filename) || Carbon::createFromFormat('Ymd', $filename)?->lessThan($since)) {
                continue;
            }

            /**
             * @var array{
             *     racers: array<int ,array{
             *         number: int,
             *         name: string,
             *         race_count: int,
             *         changed_race_count: int,
             *         changed_race_rate: float,
             *         outer_race_count?: int,
             *         outer_race_rate?: float,
             *         inner_race_count?: int,
             *         inner_race_rate?: float,
             *     }>,
             * }
             */
            $payload = JsonDecoder::decode((string) file_get_contents($path), $path);

            foreach ($payload['racers'] as $racer) {
                $number = $racer['number'] ?? null;
                $name = $racer['name'] ?? null;

                if ($number === null || $name === null) {
                    continue;
                }

                $stats[$number] ??= [
                    'number' => $number,
                    'name' => $name,
                    'race_count' => 0,
                    'changed_race_count' => 0,
                    'changed_race_rate' => 0.0,
                    'outer_race_count' => 0,
                    'outer_race_rate' => 0.0,
                    'inner_race_count' => 0,
                    'inner_race_rate' => 0.0,
                ];

                $stats[$number]['race_count'] += $racer['race_count'] ?? 0;
                $stats[$number]['changed_race_count'] += $racer['changed_race_count'] ?? 0;
                $stats[$number]['outer_race_count'] += $racer['outer_race_count'] ?? 0;
                $stats[$number]['inner_race_count'] += $racer['inner_race_count'] ?? 0;
            }
        }

        foreach ($stats as &$stat) {
            $stat['changed_race_rate'] = self::rate($stat['changed_race_count'], $stat['race_count']);
            $stat['outer_race_rate'] = self::rate($stat['outer_race_count'], $stat['race_count']);
            $stat['inner_race_rate'] = self::rate($stat['inner_race_count'], $stat['race_count']);
        }

        unset($stat);

        return array_filter($stats, static fn (array $stat): bool => $stat['race_count'] >= self::MIN_RACE_COUNT);
    }

    /**
     * @param int $count
     * @param int $raceCount
     * @return float
     */
    private static function rate(int $count, int $raceCount): float
    {
        return $raceCount > 0 ? round($count / $raceCount, 4) : 0.0;
    }
}
