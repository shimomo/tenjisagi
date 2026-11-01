<?php

declare(strict_types=1);

namespace BVP\Tenjisagi\Tests;

use BVP\Tenjisagi\CourseChangeAggregator;
use BVP\Tenjisagi\JsonEncoder;
use Carbon\CarbonImmutable as Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author shimomo
 */
final class CourseChangeAggregatorTest extends TestCase
{
    /**
     * @var string
     */
    private string $directory;

    /**
     * @return void
     */
    #[Test]
    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/tenjisagi-test-' . uniqid();
        mkdir($this->directory, 0755, true);
    }

    /**
     * @return void
     */
    #[Test]
    protected function tearDown(): void
    {
        array_map('unlink', glob($this->directory . '/*') ?: []);
        rmdir($this->directory);
    }

    /**
     * @return void
     */
    #[Test]
    public function testRacesAreSummedAcrossFilesWithinTheWindow(): void
    {
        $this->writeDay(Carbon::now()->subDays(10), [
            1001 => [
                'number' => 1001, 'name' => 'A', 'race_count' => 20,
                'changed_race_count' => 4, 'changed_race_rate' => 0.2,
                'outer_race_count' => 3, 'outer_race_rate' => 0.15,
                'inner_race_count' => 1, 'inner_race_rate' => 0.05,
            ],
        ]);
        $this->writeDay(Carbon::now()->subDays(5), [
            1001 => [
                'number' => 1001, 'name' => 'A', 'race_count' => 15,
                'changed_race_count' => 6, 'changed_race_rate' => 0.4,
                'outer_race_count' => 5, 'outer_race_rate' => 0.3333,
                'inner_race_count' => 1, 'inner_race_rate' => 0.0667,
            ],
        ]);

        $stats = (new CourseChangeAggregator())->aggregate($this->directory);

        self::assertSame([
            'number' => 1001,
            'name' => 'A',
            'race_count' => 35,
            'changed_race_count' => 10,
            'changed_race_rate' => round(10 / 35, 4),
            'outer_race_count' => 8,
            'outer_race_rate' => round(8 / 35, 4),
            'inner_race_count' => 2,
            'inner_race_rate' => round(2 / 35, 4),
        ], $stats[1001]);
    }

    /**
     * @return void
     */
    #[Test]
    public function testRacersBelowTheMinimumRaceCountAreExcluded(): void
    {
        $this->writeDay(Carbon::now()->subDays(1), [
            2002 => ['number' => 2002, 'name' => 'B', 'race_count' => 29, 'changed_race_count' => 29, 'changed_race_rate' => 1.0],
        ]);

        $stats = (new CourseChangeAggregator())->aggregate($this->directory);

        self::assertArrayNotHasKey(2002, $stats);
    }

    /**
     * @return void
     */
    #[Test]
    public function testRacersAtTheMinimumRaceCountAreIncluded(): void
    {
        $this->writeDay(Carbon::now()->subDays(1), [
            2003 => ['number' => 2003, 'name' => 'C', 'race_count' => 30, 'changed_race_count' => 3, 'changed_race_rate' => 0.1],
        ]);

        $stats = (new CourseChangeAggregator())->aggregate($this->directory);

        self::assertArrayHasKey(2003, $stats);
    }

    /**
     * @return void
     */
    #[Test]
    public function testFilesOlderThanTheWindowAreIgnored(): void
    {
        $this->writeDay(Carbon::now()->subDays(400), [
            3003 => ['number' => 3003, 'name' => 'D', 'race_count' => 100, 'changed_race_count' => 50, 'changed_race_rate' => 0.5],
        ]);

        $stats = (new CourseChangeAggregator())->aggregate($this->directory);

        self::assertArrayNotHasKey(3003, $stats);
    }

    /**
     * @return void
     */
    #[Test]
    public function testFilesWithNonDateNamesAreIgnored(): void
    {
        file_put_contents($this->directory . '/readme.json', JsonEncoder::encode(['racers' => [
            4004 => ['number' => 4004, 'name' => 'E', 'race_count' => 50, 'changed_race_count' => 10, 'changed_race_rate' => 0.2],
        ]]));

        $stats = (new CourseChangeAggregator())->aggregate($this->directory);

        self::assertArrayNotHasKey(4004, $stats);
    }

    /**
     * @param array<int, array{number: int, name: string, race_count: int, changed_race_count: int, changed_race_rate: float}> $racers
     */
    #[Test]
    private function writeDay(Carbon $date, array $racers): void
    {
        $path = $this->directory . DIRECTORY_SEPARATOR . $date->format('Ymd') . '.json';

        file_put_contents($path, JsonEncoder::encode(['date' => $date->format('Y-m-d'), 'racers' => $racers]));
    }
}
