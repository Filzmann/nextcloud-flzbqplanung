<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Contract;

use OCA\AdBqPlanning\Domain\Scheduling\CalendarCoverage;

interface BlockedPeriodProvider {
    /** @param list<string> $bridgeDays */
    public function forProposalMonth(int $year, int $month, array $bridgeDays): CalendarCoverage;
}
