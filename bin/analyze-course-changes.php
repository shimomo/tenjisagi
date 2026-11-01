#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use BVP\Tenjisagi\CourseChangeAnalyzer;
use BVP\Tenjisagi\CourseChangeRepository;
use BVP\Tenjisagi\ProgramsFetcher;
use BVP\Tenjisagi\ProgramsNotFoundException;
use Carbon\CarbonImmutable as Carbon;
use GuzzleHttp\Client;

$date = isset($argv[1]) ? Carbon::parse($argv[1]) : Carbon::today();
$directory = $argv[2] ?? __DIR__ . '/../docs/course-changes';

$fetcher = new ProgramsFetcher(new Client());

try {
    $programs = $fetcher->fetch($date);
} catch (ProgramsNotFoundException $exception) {
    fwrite(STDOUT, "Skipped {$date->format('Y-m-d')}: {$exception->getMessage()}" . PHP_EOL);
    exit(0);
}

$analyzer = new CourseChangeAnalyzer();
$stats = $analyzer->analyze($programs);

if ($stats === []) {
    fwrite(STDOUT, "Skipped {$date->format('Y-m-d')}: no results with course numbers yet" . PHP_EOL);
    exit(0);
}

$repository = new CourseChangeRepository($directory);
$path = $repository->save($date, $stats);

fwrite(STDOUT, "Saved: {$path}" . PHP_EOL);
