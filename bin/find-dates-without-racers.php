#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use BVP\Tenjisagi\CourseChangeRepository;
use Carbon\CarbonImmutable as Carbon;

$days = isset($argv[1]) ? (int) $argv[1] : 14;
$until = isset($argv[2]) ? Carbon::parse($argv[2]) : Carbon::today();
$directory = $argv[3] ?? __DIR__ . '/../docs/course-changes';

$repository = new CourseChangeRepository($directory);

foreach ($repository->findDatesWithoutRacers($until, $days) as $date) {
    fwrite(STDOUT, $date->format('Y-m-d') . PHP_EOL);
}
