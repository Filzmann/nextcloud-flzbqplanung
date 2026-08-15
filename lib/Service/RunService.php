<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Service;

use DateTimeImmutable;
use DomainException;
use OCA\AdBqPlanning\Contract\RunStore;
use OCA\AdBqPlanning\Domain\Scheduling\PlanningRules;

final class RunService {
    public const MAX_CAPACITY = 10;
    public const MAX_ADDITIONAL_CAPACITY = 10;

    public function createRun(
        RunStore $store,
        string $label,
        string $startsOn,
        string $endsOn,
        int $capacity,
        PlanningRules $rules,
        string $actorUid,
    ): int {
        $label = trim($label);
        $actorUid = trim($actorUid);
        if ($label === '' || strlen($label) > 128) {
            throw new DomainException('Ein BQ-Durchlauf benötigt eine Bezeichnung mit höchstens 128 Zeichen.');
        }
        if ($actorUid === '') {
            throw new DomainException('Ein BQ-Durchlauf benötigt eine handelnde Person.');
        }
        if ($capacity < 1 || $capacity > self::MAX_CAPACITY) {
            throw new DomainException('Die reguläre BQ-Kapazität muss zwischen 1 und 10 liegen.');
        }

        $start = $this->date($startsOn);
        $end = $this->date($endsOn);
        if ($end < $start) {
            throw new DomainException('Das BQ-Ende darf nicht vor dem Beginn liegen.');
        }
        if ((int)$start->format('N') !== $rules->startWeekday) {
            throw new DomainException('Der BQ-Beginn entspricht nicht dem konfigurierten Startwochentag.');
        }
        if ($this->workdayCount($start, $end) !== $rules->workdayCount) {
            throw new DomainException('Der BQ-Zeitraum entspricht nicht der konfigurierten Anzahl an Arbeitstagen.');
        }

        return $store->createRun([
            'label' => $label,
            'startsOn' => $startsOn,
            'endsOn' => $endsOn,
            'capacity' => $capacity,
            'createdBy' => $actorUid,
        ]);
    }

    public function addModule(
        RunStore $store,
        int $runId,
        string $moduleKey,
        string $title,
        int $minutes,
        string $date,
        string $startsAt,
        string $endsAt,
        int $additionalCapacity,
        int $expectedRunVersion,
        string $actorUid,
    ): int {
        $run = $store->run($runId);
        if (($run['status'] ?? '') !== 'draft') {
            throw new DomainException('Curriculum-Module können nur im Entwurf ergänzt werden.');
        }
        if ((int)($run['version'] ?? 0) !== $expectedRunVersion) {
            throw new DomainException('Der BQ-Durchlauf wurde zwischenzeitlich geändert.');
        }
        $moduleKey = trim($moduleKey);
        $title = trim($title);
        if (!preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', $moduleKey) || $title === '' || strlen($title) > 128) {
            throw new DomainException('Das Curriculum-Modul benötigt einen stabilen Schlüssel und einen kurzen Titel.');
        }
        if ($minutes < 1 || $minutes > 600) {
            throw new DomainException('Ein Curriculum-Modul muss zwischen 1 und 600 Minuten dauern.');
        }
        if ($additionalCapacity < 0 || $additionalCapacity > self::MAX_ADDITIONAL_CAPACITY) {
            throw new DomainException('Die modulbezogene Zusatzkapazität muss zwischen 0 und 10 liegen.');
        }
        $moduleDate = $this->date($date);
        if ($date < (string)$run['startsOn'] || $date > (string)$run['endsOn']) {
            throw new DomainException('Das Curriculum-Modul muss innerhalb des BQ-Zeitraums liegen.');
        }
        if ((int)$moduleDate->format('N') > 5) {
            throw new DomainException('Curriculum-Module müssen montags bis freitags stattfinden.');
        }
        [$startHour, $startMinute] = $this->time($startsAt);
        [$endHour, $endMinute] = $this->time($endsAt);
        $actualMinutes = ($endHour * 60 + $endMinute) - ($startHour * 60 + $startMinute);
        if ($actualMinutes !== $minutes) {
            throw new DomainException('Zeitspanne und Moduldauer stimmen nicht überein.');
        }

        return $store->addModule($runId, [
            'moduleKey' => $moduleKey,
            'title' => $title,
            'minutes' => $minutes,
            'date' => $date,
            'startsAt' => $startsAt,
            'endsAt' => $endsAt,
            'additionalCapacity' => $additionalCapacity,
        ], $expectedRunVersion, trim($actorUid));
    }

    /** @return array<string,mixed> */
    public function publish(RunStore $store, int $runId, int $expectedVersion, string $actorUid): array {
        $run = $store->run($runId);
        if (($run['status'] ?? '') !== 'draft' || (int)($run['version'] ?? 0) !== $expectedVersion) {
            throw new DomainException('Nur der aktuelle Entwurf kann veröffentlicht werden.');
        }
        $modules = $store->modules($runId);
        if ($modules === []) {
            throw new DomainException('Vor der Veröffentlichung muss mindestens ein Curriculum-Modul terminiert sein.');
        }
        foreach ($modules as $module) {
            foreach (['date', 'startsAt', 'endsAt'] as $field) {
                if (trim((string)($module[$field] ?? '')) === '') {
                    throw new DomainException('Vor der Veröffentlichung müssen alle Curriculum-Module terminiert sein.');
                }
            }
        }
        return $store->changeStatus($runId, 'draft', 'published', $expectedVersion, trim($actorUid));
    }

    private function date(string $value): DateTimeImmutable {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false || $date->format('Y-m-d') !== $value
            || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new DomainException('Es wird ein gültiges Datum im Format JJJJ-MM-TT benötigt.');
        }
        return $date;
    }

    /** @return array{0:int,1:int} */
    private function time(string $value): array {
        if (!preg_match('/^(?<hour>[01][0-9]|2[0-3]):(?<minute>[0-5][0-9])$/', $value, $matches)) {
            throw new DomainException('Es wird eine gültige Uhrzeit im Format HH:MM benötigt.');
        }
        return [(int)$matches['hour'], (int)$matches['minute']];
    }

    private function workdayCount(DateTimeImmutable $start, DateTimeImmutable $end): int {
        $count = 0;
        for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
            if ((int)$date->format('N') <= 5) {
                $count++;
            }
        }
        return $count;
    }
}
