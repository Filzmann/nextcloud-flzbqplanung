<?php

declare(strict_types=1);

namespace AdBqPlanning\Tests;

use OCA\AdBqPlanning\Contract\BlockedPeriodProvider;
use OCA\AdBqPlanning\Domain\Scheduling\BlockedCalendar;
use OCA\AdBqPlanning\Domain\Scheduling\CalendarCoverage;
use OCA\AdBqPlanning\Domain\Scheduling\DateProposalService;
use OCA\AdBqPlanning\Domain\Scheduling\PlanningRules;
use OCA\AdBqPlanning\Service\CalendarProposalService;

final class FakeBlockedPeriodProvider implements BlockedPeriodProvider {
    public int $calls = 0;

    public function __construct(private CalendarCoverage $coverage) {
    }

    public function forProposalMonth(int $year, int $month, array $bridgeDays): CalendarCoverage {
        $this->calls++;
        return $this->coverage;
    }
}

TestRunner::test('calendar proposal service returns a checked monthly proposal for current data', static function (): void {
    $coverage = new CalendarCoverage('current', true, true, new BlockedCalendar([]));
    $result = (new CalendarProposalService(new FakeBlockedPeriodProvider($coverage), new DateProposalService()))
        ->suggest(2026, 9, new PlanningRules(), []);

    assertSame('2026-09-04', $result['proposal']['startsOn']);
    assertSame(true, $result['calendar']['complete']);
    assertSame('current', $result['calendar']['status']);
});

TestRunner::test('invalid proposal months are rejected before the shared provider is called', static function (): void {
    $provider = new FakeBlockedPeriodProvider(new CalendarCoverage('current', true, true, new BlockedCalendar([])));
    $service = new CalendarProposalService($provider, new DateProposalService());

    assertThrows(static fn () => $service->suggest(2026, 13, new PlanningRules(), []), \DomainException::class);
    assertSame(0, $provider->calls);
});

TestRunner::test('calendar proposal service refuses automatic output when provider coverage is unavailable', static function (): void {
    $coverage = new CalendarCoverage('unavailable', false, false, new BlockedCalendar([]));
    $result = (new CalendarProposalService(new FakeBlockedPeriodProvider($coverage), new DateProposalService()))
        ->suggest(2026, 9, new PlanningRules(), []);

    assertSame(null, $result['proposal']);
    assertSame(false, $result['calendar']['complete']);
    assertTrue(str_contains($result['calendar']['message'], 'nicht vollständig'));
});
