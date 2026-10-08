<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Domain\Curriculum;

use DomainException;

final class CurriculumTemplate {
    /** @param list<array{id:string,title:string,minutes:int}> $modules */
    public function __construct(
        public readonly string $id,
        public readonly int $version,
        public readonly array $modules,
    ) {
        if (trim($id) === '' || $version < 1 || $modules === []) {
            throw new DomainException('Eine Curriculum-Vorlage benötigt ID, Version und mindestens ein Modul.');
        }
        $ids = [];
        foreach ($modules as $module) {
            if (trim($module['id'] ?? '') === '' || trim($module['title'] ?? '') === '' || ($module['minutes'] ?? 0) < 1) {
                throw new DomainException('Curriculum-Module benötigen ID, Titel und eine positive Dauer.');
            }
            $ids[] = $module['id'];
        }
        if (count(array_unique($ids)) !== count($ids)) {
            throw new DomainException('Curriculum-Modul-IDs müssen innerhalb einer Vorlage eindeutig sein.');
        }
    }
}
