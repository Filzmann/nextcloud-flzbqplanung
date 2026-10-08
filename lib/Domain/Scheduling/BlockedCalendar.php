<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Domain\Scheduling;

use DateTimeImmutable;

final class BlockedCalendar {
    /** @param list<BlockedPeriod> $periods */
    public function __construct(private readonly array $periods) {
    }

    /** @return list<array{date:string,type:string,label:string}> */
    public function conflictsOn(DateTimeImmutable $date): array {
        $conflicts = [];
        foreach ($this->periods as $period) {
            if (!$period->contains($date)) {
                continue;
            }
            $conflicts[] = [
                'date' => $date->format('Y-m-d'),
                'type' => $period->type,
                'label' => $period->label,
            ];
        }
        return $conflicts;
    }

    public function isBlocked(DateTimeImmutable $date): bool {
        return $this->conflictsOn($date) !== [];
    }
}
