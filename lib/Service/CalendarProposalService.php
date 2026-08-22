<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Service;

use DomainException;
use OCA\AdBqPlanning\Contract\BlockedPeriodProvider;
use OCA\AdBqPlanning\Domain\Scheduling\DateProposalService;
use OCA\AdBqPlanning\Domain\Scheduling\PlanningRules;

final class CalendarProposalService {
    public function __construct(
        private BlockedPeriodProvider $periods,
        private DateProposalService $proposals,
    ) {
    }

    /** @param list<string> $bridgeDays */
    public function suggest(int $year, int $month, PlanningRules $rules, array $bridgeDays): array {
        if ($year < 2000 || $year > 2200 || $month < 1 || $month > 12) {
            throw new DomainException('Planungsjahr oder -monat liegt außerhalb des zulässigen Bereichs.');
        }
        $coverage = $this->periods->forProposalMonth($year, $month, $bridgeDays);
        $message = match ($coverage->status) {
            'current' => 'Ferien, Feiertage und konfigurierte Brückentage wurden vollständig geprüft.',
            'stale' => 'Der letzte verfügbare Kalenderstand wurde geprüft; seine Aktualität ist eingeschränkt.',
            'incompatible' => 'Die Kalenderdatenversion ist nicht kompatibel; es wird kein automatischer Vorschlag erstellt.',
            default => 'Die Ferien- und Feiertagsquelle ist nicht vollständig verfügbar; es wird kein automatischer Vorschlag erstellt.',
        };
        $calendar = [
            'status' => $coverage->status,
            'usable' => $coverage->usable,
            'complete' => $coverage->complete,
            'message' => $message,
        ];
        if (!$coverage->usable) {
            return ['proposal' => null, 'calendar' => $calendar];
        }

        $proposal = $this->proposals->suggestMonthly($year, $month, $rules, $coverage->calendar);
        return [
            'proposal' => [
                'startsOn' => $proposal->startsOn,
                'endsOn' => $proposal->endsOn,
                'courseDays' => $proposal->courseDays,
                'rejectedCandidates' => $proposal->rejectedCandidates,
            ],
            'calendar' => $calendar,
        ];
    }
}
