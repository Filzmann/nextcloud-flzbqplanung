<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Domain\Scheduling;

use DateTimeImmutable;
use DomainException;

final class ReflectionPlanner {
    /** @return list<array{monthOffset:int,targetDate:string,date:string,adjusted:bool}> */
    public function suggest(
        DateTimeImmutable $courseEnd,
        PlanningRules $rules,
        BlockedCalendar $calendar,
    ): array {
        $items = [];
        foreach ($rules->reflectionMonthOffsets as $offset) {
            $target = $this->addCalendarMonthsClamped($courseEnd, $offset);
            $date = $target;
            $attempts = 0;
            while ((int)$date->format('N') > 5 || $calendar->isBlocked($date)) {
                $date = $date->modify('+1 day');
                if (++$attempts > 366) {
                    throw new DomainException('Für eine Praxisreflexion wurde innerhalb eines Jahres kein freier Arbeitstag gefunden.');
                }
            }
            $items[] = [
                'monthOffset' => $offset,
                'targetDate' => $target->format('Y-m-d'),
                'date' => $date->format('Y-m-d'),
                'adjusted' => $date != $target,
            ];
        }
        return $items;
    }

    private function addCalendarMonthsClamped(DateTimeImmutable $date, int $months): DateTimeImmutable {
        $targetMonth = $date->modify('first day of this month')->modify('+' . $months . ' months');
        $day = min((int)$date->format('j'), (int)$targetMonth->format('t'));
        return $targetMonth->setDate(
            (int)$targetMonth->format('Y'),
            (int)$targetMonth->format('n'),
            $day,
        );
    }
}
