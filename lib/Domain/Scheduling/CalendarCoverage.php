<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Domain\Scheduling;

use DomainException;

final class CalendarCoverage {
    public function __construct(
        public readonly string $status,
        public readonly bool $usable,
        public readonly bool $complete,
        public readonly BlockedCalendar $calendar,
    ) {
        if (!in_array($status, ['current', 'stale', 'unavailable', 'incompatible'], true)) {
            throw new DomainException('Der Kalenderabdeckungsstatus ist ungültig.');
        }
        if ($complete && (!$usable || $status !== 'current')) {
            throw new DomainException('Nur ein aktueller nutzbarer Kalenderstand darf vollständig sein.');
        }
        if (!$usable && !in_array($status, ['unavailable', 'incompatible'], true)) {
            throw new DomainException('Ein nicht nutzbarer Kalenderstand benötigt einen Fehlerstatus.');
        }
    }
}
