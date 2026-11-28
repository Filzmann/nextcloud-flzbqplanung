<?php

declare(strict_types=1);


namespace OCA\FlzOrganization\PublicApi\V1 {
    if (!class_exists(HolidayCalendarSnapshot::class)) {
        final class HolidayCalendarSnapshot {
            public function __construct(
                private int $year,
                private string $timezone,
                private string $cacheStatus,
                private array $publicHolidayDates,
                private ?string $fetchedAt,
                private array $publicHolidays = [],
                private array $schoolHolidays = [],
            ) {
            }

            public function contractVersion(): string { return '1.0'; }

            public function toArray(): array {
                return [
                    'contractVersion' => '1.0',
                    'year' => $this->year,
                    'timezone' => $this->timezone,
                    'cacheStatus' => $this->cacheStatus,
                    'publicHolidayDates' => $this->publicHolidayDates,
                    'publicHolidays' => $this->publicHolidays,
                    'schoolHolidays' => $this->schoolHolidays,
                    'fetchedAt' => $this->fetchedAt,
                ];
            }
        }
    }
}

namespace Psr\Log {
    if (!interface_exists(LoggerInterface::class)) {
        interface LoggerInterface {
            public function warning(string $message, array $context = []): void;
        }
    }
}

namespace FlzBqPlanning\Tests {
    use DateTimeImmutable;
    use OCA\FlzBqPlanning\Service\CalendarBlockedPeriodProvider;
    use OCA\FlzBqPlanning\Service\OrganizationHolidayCalendarService;
    use OCA\FlzOrganization\PublicApi\V1\HolidayCalendarSnapshot;
    use Psr\Log\LoggerInterface;

    final class CalendarProviderLogger implements LoggerInterface {
        public array $warnings = [];

        public function warning(string $message, array $context = []): void {
            $this->warnings[] = [$message, $context];
        }

        public function error(string $message, array $context = []): void {
        }
    }

    final class FakeHolidayGateway extends OrganizationHolidayCalendarService {
        /** @var array<int,HolidayCalendarSnapshot|\Throwable> */
        public array $years = [];

        public function __construct() {}

        public function snapshotForYear(int $year): ?HolidayCalendarSnapshot {
            if (!array_key_exists($year, $this->years)) {
                throw new \RuntimeException('Kein synthetischer Kalenderstand.');
            }
            $result = $this->years[$year];
            if ($result instanceof \Throwable) {
                throw $result;
            }
            return $result;
        }
    }

    function sharedCalendar(int $year, string $status = 'current', string $contractVersion = '1.0'): HolidayCalendarSnapshot {
        return new HolidayCalendarSnapshot(
            $year,
            'Europe/Berlin',
            $status,
            ["{$year}-03-08"],
            null,
            [[
                'name' => 'Feiertag',
                'startDate' => "{$year}-03-08",
                'endDate' => "{$year}-03-08",
            ]],
            [[
                'name' => 'Winterferien',
                'startDate' => "{$year}-02-02",
                'endDate' => "{$year}-02-07",
            ]],
        );
    }

    TestRunner::test('shared calendar and configured bridge days become BQ blocking periods', static function (): void {
        $shared = new FakeHolidayGateway();
        $shared->years[2026] = sharedCalendar(2026);
        $coverage = (new CalendarBlockedPeriodProvider($shared, new CalendarProviderLogger()))
            ->forProposalMonth(2026, 2, ['2026-02-13']);

        assertSame('current', $coverage->status);
        assertTrue($coverage->usable);
        assertTrue($coverage->complete);
        assertSame('school_holiday', $coverage->calendar->conflictsOn(new DateTimeImmutable('2026-02-03'))[0]['type']);
        assertSame('bridge_day', $coverage->calendar->conflictsOn(new DateTimeImmutable('2026-02-13'))[0]['type']);
    });

    TestRunner::test('stale shared data remains usable but is never reported as complete', static function (): void {
        $shared = new FakeHolidayGateway();
        $shared->years[2026] = sharedCalendar(2026, 'stale');
        $coverage = (new CalendarBlockedPeriodProvider($shared, new CalendarProviderLogger()))
            ->forProposalMonth(2026, 9, []);

        assertSame('stale', $coverage->status);
        assertTrue($coverage->usable);
        assertTrue(!$coverage->complete);
    });

    TestRunner::test('unavailable incompatible and failed providers cannot claim a checked proposal', static function (): void {
        $logger = new CalendarProviderLogger();
        $shared = new FakeHolidayGateway();
        $shared->years[2026] = sharedCalendar(2026, 'unavailable');
        $provider = new CalendarBlockedPeriodProvider($shared, $logger);
        $unavailable = $provider->forProposalMonth(2026, 9, ['2026-09-11']);
        assertSame('unavailable', $unavailable->status);
        assertTrue(!$unavailable->usable);
        assertSame('bridge_day', $unavailable->calendar->conflictsOn(new DateTimeImmutable('2026-09-11'))[0]['type']);

        $shared->years[2026] = null;
        assertSame('incompatible', $provider->forProposalMonth(2026, 9, [])->status);

        $shared->years[2026] = new \RuntimeException('synthetischer Providerausfall');
        assertSame('unavailable', $provider->forProposalMonth(2026, 9, [])->status);
        assertTrue($logger->warnings !== []);
    });
}
