<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Domain\Lecturer;

use DomainException;

final class LecturerProfile {
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $displayName,
    ) {
        if (trim($id) === '' || trim($displayName) === '') {
            throw new DomainException('Ein Dozentinnenprofil benötigt ID und Anzeigenamen.');
        }
        if (!in_array($type, ['internal_pfk', 'external'], true)) {
            throw new DomainException('Der Dozentinnentyp ist ungültig.');
        }
    }
}
