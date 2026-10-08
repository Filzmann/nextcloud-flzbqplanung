<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Domain\Scheduling;

use DateTimeImmutable;
use DomainException;

final class DateProposalService {
    public function suggestMonthly(
        int $year,
        int $month,
        PlanningRules $rules,
        BlockedCalendar $calendar,
    ): DateProposal {
        if ($year < 2000 || $year > 2200 || $month < 1 || $month > 12) {
            throw new DomainException('Planungsjahr oder -monat liegt außerhalb des zulässigen Bereichs.');
        }

        $candidate = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $rejected = [];
        while ((int)$candidate->format('n') === $month) {
            if ((int)$candidate->format('N') !== $rules->startWeekday) {
                $candidate = $candidate->modify('+1 day');
                continue;
            }

            $courseDates = $this->workdaysFrom($candidate, $rules->workdayCount);
            $conflicts = [];
            foreach ($courseDates as $courseDate) {
                array_push($conflicts, ...$calendar->conflictsOn($courseDate));
            }
            if ($conflicts === []) {
                return new DateProposal(
                    $candidate->format('Y-m-d'),
                    $courseDates[array_key_last($courseDates)]->format('Y-m-d'),
                    array_map(static fn (DateTimeImmutable $date): string => $date->format('Y-m-d'), $courseDates),
                    $rejected,
                );
            }
            $rejected[] = ['startsOn' => $candidate->format('Y-m-d'), 'conflicts' => $conflicts];
            $candidate = $candidate->modify('+1 day');
        }

        throw new DomainException(sprintf(
            'Für %04d-%02d wurde kein konfliktfreier BQ-Termin gefunden.',
            $year,
            $month,
        ));
    }

    /** @return list<DateTimeImmutable> */
    private function workdaysFrom(DateTimeImmutable $start, int $count): array {
        $days = [];
        $date = $start;
        while (count($days) < $count) {
            if ((int)$date->format('N') <= 5) {
                $days[] = $date;
            }
            $date = $date->modify('+1 day');
        }
        return $days;
    }
}
