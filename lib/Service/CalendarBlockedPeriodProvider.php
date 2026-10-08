<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Service;

use OCA\FlzBqPlanning\Contract\BlockedPeriodProvider;
use OCA\FlzBqPlanning\Domain\Scheduling\BlockedCalendar;
use OCA\FlzBqPlanning\Domain\Scheduling\BlockedPeriod;
use OCA\FlzBqPlanning\Domain\Scheduling\CalendarCoverage;
use OCA\LocalBase\Calendar\HolidayCalendarService;
use Psr\Log\LoggerInterface;
use Throwable;

/** Projiziert den öffentlichen LocalBase-Jahresvertrag auf BQ-Sperrperioden. */
final class CalendarBlockedPeriodProvider implements BlockedPeriodProvider {
    private const SUPPORTED_CONTRACT_VERSION = 1;

    public function __construct(
        private HolidayCalendarService $shared,
        private LoggerInterface $logger,
    ) {
    }

    public function forProposalMonth(int $year, int $month, array $bridgeDays): CalendarCoverage {
        $periods = array_map(
            static fn (string $date): BlockedPeriod => new BlockedPeriod($date, $date, 'bridge_day', 'Konfigurierter Brückentag'),
            $bridgeDays,
        );
        $years = [$year];
        if ($month === 12) {
            $years[] = $year + 1;
        }

        $stale = false;
        try {
            foreach ($years as $calendarYear) {
                $calendar = $this->shared->forYear($calendarYear)->toArray();
                if (($calendar['version'] ?? null) !== self::SUPPORTED_CONTRACT_VERSION) {
                    return new CalendarCoverage('incompatible', false, false, new BlockedCalendar($periods));
                }
                $status = $calendar['cacheStatus'] ?? null;
                if ($status === 'unavailable') {
                    return new CalendarCoverage('unavailable', false, false, new BlockedCalendar($periods));
                }
                if (!in_array($status, ['fresh', 'current', 'stale'], true)) {
                    return new CalendarCoverage('incompatible', false, false, new BlockedCalendar($periods));
                }
                $stale = $stale || $status === 'stale';
                array_push($periods, ...$this->periods($calendar['schoolHolidays'] ?? null, 'school_holiday'));
                array_push($periods, ...$this->periods($calendar['publicHolidays'] ?? null, 'holiday'));
            }
        } catch (Throwable $error) {
            $this->logger->warning('BQ-Kalenderdaten konnten nicht bereitgestellt werden.', [
                'year' => $year,
                'exceptionClass' => $error::class,
            ]);
            return new CalendarCoverage('unavailable', false, false, new BlockedCalendar($periods));
        }

        return new CalendarCoverage(
            $stale ? 'stale' : 'current',
            true,
            !$stale,
            new BlockedCalendar($periods),
        );
    }

    /** @return list<BlockedPeriod> */
    private function periods(mixed $items, string $type): array {
        if (!is_array($items) || !array_is_list($items)) {
            throw new \UnexpectedValueException('Der Kalendervertrag enthält keine gültige Periodenliste.');
        }
        return array_map(static function (mixed $item) use ($type): BlockedPeriod {
            if (!is_array($item)) {
                throw new \UnexpectedValueException('Der Kalendervertrag enthält eine ungültige Periode.');
            }
            return new BlockedPeriod(
                (string)($item['startDate'] ?? ''),
                (string)($item['endDate'] ?? ''),
                $type,
                (string)($item['name'] ?? ''),
            );
        }, $items);
    }
}
