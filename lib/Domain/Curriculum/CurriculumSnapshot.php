<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Domain\Curriculum;

use DateTimeImmutable;
use DomainException;

final class CurriculumSnapshot {
    /** @param list<array<string,mixed>> $modules */
    private function __construct(
        public readonly string $templateId,
        public readonly int $templateVersion,
        private readonly array $modules,
    ) {
    }

    public static function fromTemplate(CurriculumTemplate $template): self {
        return new self($template->id, $template->version, array_values($template->modules));
    }

    /** @return list<array<string,mixed>> */
    public function modules(): array {
        return $this->modules;
    }

    /** @return array<string,mixed> */
    public function module(string $moduleId): array {
        $index = $this->indexOf($moduleId);
        return $this->modules[$index];
    }

    public function exchange(string $firstModuleId, string $secondModuleId): self {
        $first = $this->indexOf($firstModuleId);
        $second = $this->indexOf($secondModuleId);
        $modules = $this->modules;
        [$modules[$first], $modules[$second]] = [$modules[$second], $modules[$first]];
        return new self($this->templateId, $this->templateVersion, $modules);
    }

    public function schedule(string $moduleId, string $date, string $startsAt, string $endsAt): self {
        $index = $this->indexOf($moduleId);
        $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $startsAt);
        $end = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $endsAt);
        if ($start === false || $end === false
            || $start->format('Y-m-d H:i') !== $date . ' ' . $startsAt
            || $end->format('Y-m-d H:i') !== $date . ' ' . $endsAt
            || $end <= $start) {
            throw new DomainException('Ein Curriculum-Termin benötigt ein gültiges Datum und eine positive Zeitspanne.');
        }
        $modules = $this->modules;
        $modules[$index]['date'] = $date;
        $modules[$index]['startsAt'] = $startsAt;
        $modules[$index]['endsAt'] = $endsAt;
        return new self($this->templateId, $this->templateVersion, $modules);
    }

    private function indexOf(string $moduleId): int {
        foreach ($this->modules as $index => $module) {
            if (($module['id'] ?? null) === $moduleId) {
                return $index;
            }
        }
        throw new DomainException('Das Curriculum-Modul wurde im Snapshot nicht gefunden.');
    }
}
