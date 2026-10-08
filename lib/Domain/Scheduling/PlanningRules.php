<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Domain\Scheduling;

use DomainException;

final class PlanningRules {
    /** @param list<int> $reflectionMonthOffsets */
    public function __construct(
        public readonly int $workdayCount = 7,
        public readonly int $startWeekday = 5,
        public readonly array $reflectionMonthOffsets = [1, 3, 4],
    ) {
        if ($workdayCount < 1 || $workdayCount > 30) {
            throw new DomainException('Die BQ-Dauer muss zwischen 1 und 30 Arbeitstagen liegen.');
        }
        if ($startWeekday < 1 || $startWeekday > 5) {
            throw new DomainException('Der BQ-Start muss auf einen Arbeitstag von Montag bis Freitag fallen.');
        }
        if ($reflectionMonthOffsets === []
            || count(array_unique($reflectionMonthOffsets)) !== count($reflectionMonthOffsets)) {
            throw new DomainException('Praxisreflexionsabstände müssen eindeutig angegeben werden.');
        }
        foreach ($reflectionMonthOffsets as $offset) {
            if ($offset < 1 || $offset > 24) {
                throw new DomainException('Praxisreflexionsabstände müssen zwischen 1 und 24 Monaten liegen.');
            }
        }
    }
}
