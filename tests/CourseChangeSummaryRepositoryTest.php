<?php

declare(strict_types=1);

namespace BVP\Tenjisagi\Tests;

use BVP\Tenjisagi\CourseChangeSummaryRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author shimomo
 */
final class CourseChangeSummaryRepositoryTest extends TestCase
{
    /**
     * @var string
     */
    private string $directory;

    /**
     * @var string
     */
    private string $path;

    /**
     * @return void
     */
    #[Test]
    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/tenjisagi-test-' . uniqid();
        $this->path = $this->directory . '/summary.json';
    }

    /**
     * @return void
     */
    #[Test]
    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }

        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function testSaveCreatesTheDirectoryAndWritesTheFile(): void
    {
        $repository = new CourseChangeSummaryRepository($this->path);

        $path = $repository->save([]);

        self::assertSame($this->path, $path);
        self::assertFileExists($path);
    }

    /**
     * @return void
     */
    #[Test]
    public function testSaveSortsRacersByOuterRaceRateDescending(): void
    {
        $repository = new CourseChangeSummaryRepository($this->path);

        $stats = [
            1001 => $this->racer(1001, 'A', changed: 1, outer: 1, inner: 0),
            2002 => $this->racer(2002, 'B', changed: 9, outer: 2, inner: 7),
            3003 => $this->racer(3003, 'C', changed: 5, outer: 5, inner: 0),
        ];

        $path = $repository->save($stats);
        $saved = json_decode((string) file_get_contents($path), true);

        self::assertSame([3003, 2002, 1001], array_map('intval', array_keys($saved['racers'])));
        self::assertArrayHasKey('generated_at', $saved);
    }

    /**
     * @return void
     */
    #[Test]
    public function testSaveReplacesAnExistingFileInsteadOfMerging(): void
    {
        $repository = new CourseChangeSummaryRepository($this->path);

        $repository->save([
            1001 => $this->racer(1001, 'A', changed: 1, outer: 1, inner: 0),
        ]);

        $path = $repository->save([
            2002 => $this->racer(2002, 'B', changed: 9, outer: 2, inner: 7),
        ]);

        $saved = json_decode((string) file_get_contents($path), true);

        self::assertSame([2002], array_map('intval', array_keys($saved['racers'])));
    }

    /**
     * @return array{
     *     number: int,
     *     name: string,
     *     race_count: int,
     *     changed_race_count: int,
     *     changed_race_rate: float,
     *     outer_race_count: int,
     *     outer_race_rate: float,
     *     inner_race_count: int,
     *     inner_race_rate: float,
     * }
     */
    private function racer(int $number, string $name, int $changed, int $outer, int $inner): array
    {
        return [
            'number' => $number,
            'name' => $name,
            'race_count' => 10,
            'changed_race_count' => $changed,
            'changed_race_rate' => $changed / 10,
            'outer_race_count' => $outer,
            'outer_race_rate' => $outer / 10,
            'inner_race_count' => $inner,
            'inner_race_rate' => $inner / 10,
        ];
    }
}
