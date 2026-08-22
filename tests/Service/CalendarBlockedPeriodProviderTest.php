<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar {
    final class HolidayCalendar {
        public function __construct(private array $data) {
        }

        public function toArray(): array {
            return $this->data;
        }
    }

    final class HolidayCalendarService {
        /** @var array<int,array<string,mixed>|\Throwable> */
        public array $years = [];

        public function forYear(int $year): HolidayCalendar {
            $result = $this->years[$year] ?? new \RuntimeException('Kein synthetischer Kalenderstand.');
            if ($result instanceof \Throwable) {
                throw $result;
            }
            return new HolidayCalendar($result);
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

namespace AdBqPlanning\Tests {
    use DateTimeImmutable;
    use OCA\AdBqPlanning\Service\CalendarBlockedPeriodProvider;
    use OCA\LocalBase\Calendar\HolidayCalendarService;
    use Psr\Log\LoggerInterface;

    final class CalendarProviderLogger implements LoggerInterface {
        public array $warnings = [];

        public function warning(string $message, array $context = []): void {
            $this->warnings[] = [$message, $context];
        }

        public function error(string $message, array $context = []): void {
        }
    }

    function sharedCalendar(int $year, string $status = 'current', int $version = 1): array {
        return [
            'version' => $version,
            'year' => $year,
            'cacheStatus' => $status,
            'schoolHolidays' => [[
                'type' => 'school',
                'name' => 'Winterferien',
                'startDate' => "{$year}-02-02",
                'endDate' => "{$year}-02-07",
            ]],
            'publicHolidays' => [[
                'type' => 'public',
                'name' => 'Feiertag',
                'startDate' => "{$year}-03-08",
                'endDate' => "{$year}-03-08",
            ]],
        ];
    }

    TestRunner::test('shared calendar and configured bridge days become BQ blocking periods', static function (): void {
        $shared = new HolidayCalendarService();
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
        $shared = new HolidayCalendarService();
        $shared->years[2026] = sharedCalendar(2026, 'stale');
        $coverage = (new CalendarBlockedPeriodProvider($shared, new CalendarProviderLogger()))
            ->forProposalMonth(2026, 9, []);

        assertSame('stale', $coverage->status);
        assertTrue($coverage->usable);
        assertTrue(!$coverage->complete);
    });

    TestRunner::test('unavailable incompatible and failed providers cannot claim a checked proposal', static function (): void {
        $logger = new CalendarProviderLogger();
        $shared = new HolidayCalendarService();
        $shared->years[2026] = sharedCalendar(2026, 'unavailable');
        $provider = new CalendarBlockedPeriodProvider($shared, $logger);
        $unavailable = $provider->forProposalMonth(2026, 9, ['2026-09-11']);
        assertSame('unavailable', $unavailable->status);
        assertTrue(!$unavailable->usable);
        assertSame('bridge_day', $unavailable->calendar->conflictsOn(new DateTimeImmutable('2026-09-11'))[0]['type']);

        $shared->years[2026] = sharedCalendar(2026, 'current', 2);
        assertSame('incompatible', $provider->forProposalMonth(2026, 9, [])->status);

        $shared->years[2026] = new \RuntimeException('synthetischer Providerausfall');
        assertSame('unavailable', $provider->forProposalMonth(2026, 9, [])->status);
        assertTrue($logger->warnings !== []);
    });
}
