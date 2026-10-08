<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Domain\Scheduling;

use DateTimeImmutable;
use DomainException;

final class BlockedPeriod {
    public readonly DateTimeImmutable $start;
    public readonly DateTimeImmutable $end;

    public function __construct(
        string $startsOn,
        string $endsOn,
        public readonly string $type,
        public readonly string $label,
    ) {
        $this->start = self::date($startsOn);
        $this->end = self::date($endsOn);
        if ($this->end < $this->start) {
            throw new DomainException('Das Ende einer Sperrperiode darf nicht vor ihrem Beginn liegen.');
        }
        if (!in_array($type, ['school_holiday', 'holiday', 'bridge_day', 'blocked'], true)) {
            throw new DomainException('Der Typ der Sperrperiode ist ungültig.');
        }
        if (trim($label) === '') {
            throw new DomainException('Eine Sperrperiode benötigt eine verständliche Bezeichnung.');
        }
    }

    public function contains(DateTimeImmutable $date): bool {
        return $date >= $this->start && $date <= $this->end;
    }

    private static function date(string $value): DateTimeImmutable {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false || $date->format('Y-m-d') !== $value
            || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new DomainException('Sperrperioden benötigen gültige ISO-Daten.');
        }
        return $date;
    }
}
