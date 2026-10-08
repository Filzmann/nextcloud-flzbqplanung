<?php

declare(strict_types=1);

namespace FlzBqPlanning\Tests;

use DateTimeImmutable;
use DomainException;
use OCA\FlzBqPlanning\Domain\Scheduling\BlockedCalendar;
use OCA\FlzBqPlanning\Domain\Scheduling\BlockedPeriod;
use OCA\FlzBqPlanning\Domain\Scheduling\DateProposalService;
use OCA\FlzBqPlanning\Domain\Scheduling\PlanningRules;
use OCA\FlzBqPlanning\Domain\Scheduling\ReflectionPlanner;

TestRunner::test('monthly proposal uses seven workdays from Friday and explains rejected conflicts', static function (): void {
    $calendar = new BlockedCalendar([
        new BlockedPeriod('2026-09-07', '2026-09-07', 'holiday', 'Beispiel-Feiertag'),
    ]);
    $result = (new DateProposalService())->suggestMonthly(2026, 9, new PlanningRules(), $calendar);

    assertSame('2026-09-11', $result->startsOn);
    assertSame('2026-09-21', $result->endsOn);
    assertSame(7, count($result->courseDays));
    assertSame('2026-09-04', $result->rejectedCandidates[0]['startsOn']);
    assertSame('2026-09-07', $result->rejectedCandidates[0]['conflicts'][0]['date']);
});

TestRunner::test('duration and start weekday are configurable within workday limits', static function (): void {
    $rules = new PlanningRules(workdayCount: 3, startWeekday: 1);
    $result = (new DateProposalService())->suggestMonthly(2026, 10, $rules, new BlockedCalendar([]));
    assertSame(['2026-10-05', '2026-10-06', '2026-10-07'], $result->courseDays);

    assertThrows(static fn () => new PlanningRules(workdayCount: 0), DomainException::class);
    assertThrows(static fn () => new PlanningRules(startWeekday: 6), DomainException::class);
    assertThrows(static fn () => new PlanningRules(reflectionMonthOffsets: []), DomainException::class);
    assertThrows(static fn () => new PlanningRules(reflectionMonthOffsets: [1, 1]), DomainException::class);
    assertThrows(static fn () => new PlanningRules(reflectionMonthOffsets: [25]), DomainException::class);
});

TestRunner::test('blocked periods reject invalid ranges, dates and classifications', static function (): void {
    assertThrows(static fn () => new BlockedPeriod('2026-09-08', '2026-09-07', 'holiday', 'Feiertag'), DomainException::class);
    assertThrows(static fn () => new BlockedPeriod('2026-02-30', '2026-03-01', 'holiday', 'Feiertag'), DomainException::class);
    assertThrows(static fn () => new BlockedPeriod('2026-09-07', '2026-09-07', 'unknown', 'Feiertag'), DomainException::class);
    assertThrows(static fn () => new BlockedPeriod('2026-09-07', '2026-09-07', 'holiday', '  '), DomainException::class);
    assertThrows(
        static fn () => (new DateProposalService())->suggestMonthly(1999, 9, new PlanningRules(), new BlockedCalendar([])),
        DomainException::class,
    );
});

TestRunner::test('a month without a valid candidate fails instead of claiming a conflict-free date', static function (): void {
    $calendar = new BlockedCalendar([
        new BlockedPeriod('2026-09-01', '2026-10-31', 'school_holiday', 'Vollständig gesperrt'),
    ]);
    assertThrows(
        static fn () => (new DateProposalService())->suggestMonthly(2026, 9, new PlanningRules(), $calendar),
        DomainException::class,
    );
});

TestRunner::test('practice reflections use month offsets and move blocked targets forward', static function (): void {
    $calendar = new BlockedCalendar([
        new BlockedPeriod('2026-10-21', '2026-10-21', 'bridge_day', 'Sperrtag'),
    ]);
    $items = (new ReflectionPlanner())->suggest(
        new DateTimeImmutable('2026-09-21'),
        new PlanningRules(),
        $calendar,
    );

    assertSame('2026-10-22', $items[0]['date']);
    assertSame('2026-10-21', $items[0]['targetDate']);
    assertSame(true, $items[0]['adjusted']);
    assertSame(['2026-10-22', '2026-12-21', '2027-01-21'], array_column($items, 'date'));
});

TestRunner::test('practice reflection month arithmetic clamps to the target month and skips weekends', static function (): void {
    $items = (new ReflectionPlanner())->suggest(
        new DateTimeImmutable('2026-01-31'),
        new PlanningRules(reflectionMonthOffsets: [1]),
        new BlockedCalendar([]),
    );
    assertSame('2026-02-28', $items[0]['targetDate']);
    assertSame('2026-03-02', $items[0]['date']);
});
