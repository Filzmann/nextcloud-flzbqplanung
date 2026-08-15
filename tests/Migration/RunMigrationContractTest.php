<?php

declare(strict_types=1);

namespace AdBqPlanning\Tests;

TestRunner::test('initial persistence is additive and deliberately has no waitlist table', static function (): void {
    $migration = dirname(__DIR__, 2) . '/lib/Migration/Version000001Date202608150101.php';
    assertTrue(is_file($migration), 'Initial BQ migration is missing');
    $source = (string)file_get_contents($migration);
    assertTrue(str_contains($source, "hasTable('adbq_runs')"));
    assertTrue(str_contains($source, "hasTable('adbq_modules')"));
    assertTrue(str_contains($source, "getTable('adbq_runs')"), 'Partial migration retry cannot reuse the run table');
    assertTrue(str_contains($source, "'capacity'"));
    assertTrue(str_contains($source, "'additional_capacity'"));
    assertTrue(str_contains($source, 'Types::DATE_IMMUTABLE'), 'Nextcloud-compatible date type is missing');
    assertTrue(!str_contains($source, 'Types::DATE_MUTABLE'), 'DATE_MUTABLE is not available in Nextcloud 34');
    assertTrue(str_contains($source, 'Types::DATETIME_IMMUTABLE'), 'Nextcloud-compatible datetime type is missing');
    assertTrue(!str_contains($source, 'Types::DATETIME_MUTABLE'), 'DATETIME_MUTABLE is not available in Nextcloud 34');
    assertTrue(!str_contains(strtolower($source), 'waitlist'));
    assertTrue(!str_contains(strtolower($source), 'waiting_list'));
});
