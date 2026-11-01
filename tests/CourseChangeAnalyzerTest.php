<?php

declare(strict_types=1);

namespace BVP\Tenjisagi\Tests;

use BVP\Tenjisagi\CourseChangeAnalyzer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author shimomo
 */
final class CourseChangeAnalyzerTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function testRaceWithoutACourseChangeIsCounted(): void
    {
        $stats = (new CourseChangeAnalyzer())->analyze($this->programsWithOneRace([
            $this->racer(entryNumber: 1, previewCourse: 1, resultCourse: 1, number: 1001, name: 'A'),
        ]));

        self::assertSame([
            1001 => [
                'number' => 1001,
                'name' => 'A',
                'race_count' => 1,
                'changed_race_count' => 0,
                'changed_race_rate' => 0.0,
                'outer_race_count' => 0,
                'outer_race_rate' => 0.0,
                'inner_race_count' => 0,
                'inner_race_rate' => 0.0,
            ],
        ], $stats);
    }

    /**
     * @return void
     */
    #[Test]
    public function testRaceWithACourseChangeIsCounted(): void
    {
        $stats = (new CourseChangeAnalyzer())->analyze($this->programsWithOneRace([
            $this->racer(entryNumber: 2, previewCourse: 2, resultCourse: 3, number: 1002, name: 'B'),
        ]));

        self::assertSame(1, $stats[1002]['race_count']);
        self::assertSame(1, $stats[1002]['changed_race_count']);
        self::assertSame(1.0, $stats[1002]['changed_race_rate']);
    }

    /**
     * @return void
     */
    #[Test]
    public function testRaceWithAnOuterCourseIsCountedAsOuter(): void
    {
        $stats = (new CourseChangeAnalyzer())->analyze($this->programsWithOneRace([
            $this->racer(entryNumber: 2, previewCourse: 2, resultCourse: 3, number: 1002, name: 'B'),
        ]));

        self::assertSame(1, $stats[1002]['outer_race_count']);
        self::assertSame(1.0, $stats[1002]['outer_race_rate']);
        self::assertSame(0, $stats[1002]['inner_race_count']);
        self::assertSame(0.0, $stats[1002]['inner_race_rate']);
    }

    /**
     * @return void
     */
    #[Test]
    public function testRaceWithAnInnerCourseIsCountedAsInner(): void
    {
        $stats = (new CourseChangeAnalyzer())->analyze($this->programsWithOneRace([
            $this->racer(entryNumber: 5, previewCourse: 5, resultCourse: 2, number: 1003, name: 'B'),
        ]));

        self::assertSame(1, $stats[1003]['changed_race_count']);
        self::assertSame(0, $stats[1003]['outer_race_count']);
        self::assertSame(1, $stats[1003]['inner_race_count']);
        self::assertSame(1.0, $stats[1003]['inner_race_rate']);
    }

    /**
     * @return void
     */
    #[Test]
    public function testRatesAreAccumulatedAcrossMultipleRaces(): void
    {
        $programs = [
            'stadiums' => [
                1 => [
                    'races' => [
                        1 => $this->race([$this->racer(entryNumber: 1, previewCourse: 1, resultCourse: 1, number: 2001, name: 'C')]),
                        2 => $this->race([$this->racer(entryNumber: 1, previewCourse: 1, resultCourse: 4, number: 2001, name: 'C')]),
                    ],
                ],
            ],
        ];

        $stats = (new CourseChangeAnalyzer())->analyze($programs);

        self::assertSame(2, $stats[2001]['race_count']);
        self::assertSame(1, $stats[2001]['changed_race_count']);
        self::assertSame(0.5, $stats[2001]['changed_race_rate']);
        self::assertSame(1, $stats[2001]['outer_race_count']);
        self::assertSame(0.5, $stats[2001]['outer_race_rate']);
    }

    /**
     * @return void
     */
    #[Test]
    public function testRacerIsSkippedWhenNumberIsMissing(): void
    {
        $stats = (new CourseChangeAnalyzer())->analyze($this->programsWithOneRace([
            $this->racer(entryNumber: 1, previewCourse: 1, resultCourse: 1, number: null, name: 'D'),
        ]));

        self::assertSame([], $stats);
    }

    /**
     * @return void
     */
    #[Test]
    public function testRacerIsSkippedWhenNameIsMissing(): void
    {
        $stats = (new CourseChangeAnalyzer())->analyze($this->programsWithOneRace([
            $this->racer(entryNumber: 1, previewCourse: 1, resultCourse: 1, number: 3001, name: null),
        ]));

        self::assertSame([], $stats);
    }

    /**
     * @return void
     */
    #[Test]
    public function testRacerIsSkippedWhenThereIsNoMatchingPreviewEntry(): void
    {
        $race = [
            'preview' => ['racers' => []],
            'result' => ['racers' => [
                1 => ['entry_number' => 1, 'course_number' => 1, 'number' => 4001, 'name' => 'E'],
            ]],
        ];

        $stats = (new CourseChangeAnalyzer())->analyze($this->programsWithOneRace([], $race));

        self::assertSame([], $stats);
    }

    /**
     * @return void
     */
    #[Test]
    public function testMultipleStadiumsAndRacesAreAllAnalyzed(): void
    {
        $programs = [
            'stadiums' => [
                1 => ['races' => [1 => $this->race([
                    $this->racer(entryNumber: 1, previewCourse: 1, resultCourse: 1, number: 5001, name: 'F'),
                ])]],
                2 => ['races' => [1 => $this->race([
                    $this->racer(entryNumber: 1, previewCourse: 1, resultCourse: 2, number: 5002, name: 'G'),
                ])]],
            ],
        ];

        $stats = (new CourseChangeAnalyzer())->analyze($programs);

        self::assertCount(2, $stats);
        self::assertSame(0, $stats[5001]['changed_race_count']);
        self::assertSame(1, $stats[5002]['changed_race_count']);
    }

    /**
     * @param array<int, array{entry_number: int, course_number: int, number: ?int, name: ?string}> $racers
     * @param ?array $race
     * @return array
     */
    #[Test]
    private function programsWithOneRace(array $racers, ?array $race = null): array
    {
        return [
            'stadiums' => [
                1 => [
                    'races' => [
                        1 => $race ?? $this->race($racers),
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<int, array{entry_number: int, course_number: int, number: ?int, name: ?string}> $racers
     * @return array
     */
    #[Test]
    private function race(array $racers): array
    {
        $previewRacers = [];
        $resultRacers = [];

        foreach ($racers as $racer) {
            $previewRacers[$racer['entry_number']] = [
                'entry_number' => $racer['entry_number'],
                'course_number' => $racer['preview_course'],
            ];

            $resultRacers[$racer['entry_number']] = [
                'entry_number' => $racer['entry_number'],
                'course_number' => $racer['result_course'],
                'number' => $racer['number'],
                'name' => $racer['name'],
            ];
        }

        return [
            'preview' => ['racers' => $previewRacers],
            'result' => ['racers' => $resultRacers],
        ];
    }

    /**
     * @return array{entry_number: int, preview_course: int, result_course: int, number: ?int, name: ?string}
     */
    #[Test]
    private function racer(int $entryNumber, int $previewCourse, int $resultCourse, ?int $number, ?string $name): array
    {
        return [
            'entry_number' => $entryNumber,
            'preview_course' => $previewCourse,
            'result_course' => $resultCourse,
            'number' => $number,
            'name' => $name,
        ];
    }
}
