<?php

declare(strict_types=1);

namespace FlzBqPlanning\Tests;

use OCA\FlzBqPlanning\Contract\BlockedPeriodProvider;
use OCA\FlzBqPlanning\Domain\Scheduling\BlockedCalendar;
use OCA\FlzBqPlanning\Domain\Scheduling\CalendarCoverage;
use OCA\FlzBqPlanning\Domain\Scheduling\DateProposalService;
use OCA\FlzBqPlanning\Domain\Scheduling\PlanningRules;
use OCA\FlzBqPlanning\Service\CalendarProposalService;

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

TestRunner::test('calendar proposal service keeps all twelve annual results when one month has no free date', static function (): void {
    $blockedFridays = [];
    for ($day = 1; $day <= 31; $day++) {
        $date = new \DateTimeImmutable(sprintf('2026-05-%02d', $day));
        if ((int)$date->format('N') === 5) {
            $blockedFridays[] = new \OCA\FlzBqPlanning\Domain\Scheduling\BlockedPeriod(
                $date->format('Y-m-d'),
                $date->format('Y-m-d'),
                'blocked',
                'Sperrtag',
            );
        }
    }
    $provider = new class($blockedFridays) implements BlockedPeriodProvider {
        public int $calls = 0;
        public function __construct(private array $blockedFridays) {}
        public function forProposalMonth(int $year, int $month, array $bridgeDays): CalendarCoverage {
            $this->calls++;
            return new CalendarCoverage(
                'current',
                true,
                true,
                new BlockedCalendar($month === 5 ? $this->blockedFridays : []),
            );
        }
    };

    $result = (new CalendarProposalService($provider, new DateProposalService()))
        ->suggestYear(2026, new PlanningRules(), []);

    assertSame(12, count($result));
    assertSame(12, $provider->calls);
    assertSame('2026-01-02', $result[0]['proposal']['startsOn']);
    assertSame(null, $result[4]['proposal']);
    assertTrue(str_contains($result[4]['error'], 'kein konfliktfreier'));
    assertSame('2026-06-05', $result[5]['proposal']['startsOn']);
});

TestRunner::test('invalid annual proposal year is rejected before the shared provider is called', static function (): void {
    $provider = new FakeBlockedPeriodProvider(new CalendarCoverage('current', true, true, new BlockedCalendar([])));
    $service = new CalendarProposalService($provider, new DateProposalService());

    assertThrows(static fn () => $service->suggestYear(1999, new PlanningRules(), []), \DomainException::class);
    assertSame(0, $provider->calls);
});
