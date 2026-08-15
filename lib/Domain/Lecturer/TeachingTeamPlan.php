<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Domain\Lecturer;

use DomainException;

final class TeachingTeamPlan {
    /** @param array<string,LecturerProfile> $moduleAssignments */
    public function __construct(
        private readonly LecturerProfile $leadLecturer,
        private readonly array $moduleAssignments = [],
    ) {
        if ($leadLecturer->type !== 'internal_pfk') {
            throw new DomainException('Als Hauptdozentin eines BQ-Durchlaufs ist eine interne PFK erforderlich.');
        }
    }

    public function assign(string $moduleId, LecturerProfile $lecturer): self {
        if (trim($moduleId) === '') {
            throw new DomainException('Eine Dozentinnenzuordnung benötigt eine Modul-ID.');
        }
        $assignments = $this->moduleAssignments;
        $assignments[$moduleId] = $lecturer;
        return new self($this->leadLecturer, $assignments);
    }

    public function lecturerFor(string $moduleId): LecturerProfile {
        return $this->moduleAssignments[$moduleId] ?? $this->leadLecturer;
    }
}
