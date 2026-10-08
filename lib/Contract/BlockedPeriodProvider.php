<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Contract;

use OCA\FlzBqPlanning\Domain\Scheduling\CalendarCoverage;

interface BlockedPeriodProvider {
    /** @param list<string> $bridgeDays */
    public function forProposalMonth(int $year, int $month, array $bridgeDays): CalendarCoverage;
}
