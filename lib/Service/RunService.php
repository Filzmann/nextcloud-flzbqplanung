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
        $actorUid = trim($actorUid);
        if ($actorUid === '') {
            throw new DomainException('Ein BQ-Durchlauf benötigt eine handelnde Person.');
        }
        $values = $this->validatedRunValues($label, $startsOn, $endsOn, $capacity, $rules);
        return $store->createRun([...$values, 'createdBy' => $actorUid]);
    }

    /** @return array<string,mixed> */
    public function updateRun(
        RunStore $store,
        int $runId,
        string $label,
        string $startsOn,
        string $endsOn,
        int $capacity,
        int $expectedVersion,
        PlanningRules $rules,
        string $actorUid,
    ): array {
        $current = $store->run($runId);
        if (($current['status'] ?? '') !== 'draft') {
            throw new DomainException('Nur ein BQ-Entwurf kann bearbeitet werden.');
        }
        if ((int)($current['version'] ?? 0) !== $expectedVersion) {
            throw new DomainException('Der BQ-Durchlauf wurde zwischenzeitlich geändert.');
        }
        $actorUid = trim($actorUid);
        if ($actorUid === '') {
            throw new DomainException('Die Durchlaufbearbeitung benötigt eine handelnde Person.');
        }
        $values = $this->validatedRunValues($label, $startsOn, $endsOn, $capacity, $rules);
        foreach ($store->modules($runId) as $module) {
            if ((string)$module['date'] < $startsOn || (string)$module['date'] > $endsOn) {
                throw new DomainException('Der neue BQ-Zeitraum würde bereits terminierte Module ausschließen.');
            }
        }
        return $store->updateRun($runId, $values, $expectedVersion, $actorUid);
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
        if (!preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', $moduleKey)) {
            throw new DomainException('Das Curriculum-Modul benötigt einen stabilen Schlüssel.');
        }
        $values = $this->validatedModuleValues($run, $title, $minutes, $date, $startsAt, $endsAt, $additionalCapacity);

        return $store->addModule($runId, ['moduleKey' => $moduleKey, ...$values], $expectedRunVersion, trim($actorUid));
    }

    /** @return array<string,mixed> */
    public function updateModule(
        RunStore $store,
        int $runId,
        int $moduleId,
        string $title,
        int $minutes,
        string $date,
        string $startsAt,
        string $endsAt,
        int $additionalCapacity,
        int $expectedRunVersion,
        int $expectedModuleVersion,
        string $actorUid,
    ): array {
        $run = $store->run($runId);
        if (($run['status'] ?? '') !== 'draft') {
            throw new DomainException('Curriculum-Module können nur im Entwurf bearbeitet werden.');
        }
        if ((int)($run['version'] ?? 0) !== $expectedRunVersion) {
            throw new DomainException('Der BQ-Durchlauf wurde zwischenzeitlich geändert.');
        }
        $module = null;
        foreach ($store->modules($runId) as $candidate) {
            if ((int)($candidate['id'] ?? 0) === $moduleId) {
                $module = $candidate;
                break;
            }
        }
        if ($module === null) {
            throw new DomainException('Das Curriculum-Modul gehört nicht zu diesem BQ-Durchlauf.');
        }
        if ((int)($module['version'] ?? 0) !== $expectedModuleVersion) {
            throw new DomainException('Das Curriculum-Modul wurde zwischenzeitlich geändert.');
        }
        $actorUid = trim($actorUid);
        if ($actorUid === '') {
            throw new DomainException('Die Modulbearbeitung benötigt eine handelnde Person.');
        }

        $values = $this->validatedModuleValues($run, $title, $minutes, $date, $startsAt, $endsAt, $additionalCapacity);
        return $store->updateModule(
            $runId,
            $moduleId,
            $values,
            $expectedRunVersion,
            $expectedModuleVersion,
            $actorUid,
        );
    }

    /**
     * @param list<array<string,mixed>> $modules
     * @return list<array{firstModuleId:int,secondModuleId:int}>
     */
    public function moduleConflicts(array $modules): array {
        $conflicts = [];
        foreach ($modules as $firstIndex => $first) {
            foreach (array_slice($modules, $firstIndex + 1) as $second) {
                if (($first['date'] ?? null) !== ($second['date'] ?? null)) {
                    continue;
                }
                if ((string)($first['startsAt'] ?? '') < (string)($second['endsAt'] ?? '')
                    && (string)($second['startsAt'] ?? '') < (string)($first['endsAt'] ?? '')) {
                    $conflicts[] = [
                        'firstModuleId' => (int)$first['id'],
                        'secondModuleId' => (int)$second['id'],
                    ];
                }
            }
        }
        return $conflicts;
    }

    /** @return list<array<string,mixed>> */
    public function moveModule(
        RunStore $store,
        int $runId,
        int $moduleId,
        string $direction,
        int $expectedVersion,
        string $actorUid,
    ): array {
        $run = $store->run($runId);
        if (($run['status'] ?? '') !== 'draft') {
            throw new DomainException('Die Modulreihenfolge kann nur im Entwurf geändert werden.');
        }
        if ((int)($run['version'] ?? 0) !== $expectedVersion) {
            throw new DomainException('Der BQ-Durchlauf wurde zwischenzeitlich geändert.');
        }
        if (!in_array($direction, ['up', 'down'], true)) {
            throw new DomainException('Die gewünschte Verschieberichtung ist ungültig.');
        }
        $modules = $store->modules($runId);
        $currentIndex = null;
        foreach ($modules as $index => $module) {
            if ((int)($module['id'] ?? 0) === $moduleId) {
                $currentIndex = $index;
                break;
            }
        }
        if ($currentIndex === null) {
            throw new DomainException('Das Curriculum-Modul gehört nicht zu diesem BQ-Durchlauf.');
        }
        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        if (!isset($modules[$targetIndex])) {
            throw new DomainException('Das Curriculum-Modul kann nicht weiter verschoben werden.');
        }
        [$modules[$currentIndex], $modules[$targetIndex]] = [$modules[$targetIndex], $modules[$currentIndex]];
        $actorUid = trim($actorUid);
        if ($actorUid === '') {
            throw new DomainException('Die Reihenfolgeänderung benötigt eine handelnde Person.');
        }
        return $store->reorderModules(
            $runId,
            array_map(static fn (array $module): int => (int)$module['id'], $modules),
            $expectedVersion,
            $actorUid,
        );
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

    /** @param array<string,mixed> $run
     * @return array<string,mixed>
     */
    private function validatedModuleValues(
        array $run,
        string $title,
        int $minutes,
        string $date,
        string $startsAt,
        string $endsAt,
        int $additionalCapacity,
    ): array {
        $title = trim($title);
        if ($title === '' || strlen($title) > 128) {
            throw new DomainException('Das Curriculum-Modul benötigt einen kurzen Titel.');
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
        if (($endHour * 60 + $endMinute) - ($startHour * 60 + $startMinute) !== $minutes) {
            throw new DomainException('Zeitspanne und Moduldauer stimmen nicht überein.');
        }
        return [
            'title' => $title,
            'minutes' => $minutes,
            'date' => $date,
            'startsAt' => $startsAt,
            'endsAt' => $endsAt,
            'additionalCapacity' => $additionalCapacity,
        ];
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

    /** @return array{label:string,startsOn:string,endsOn:string,capacity:int} */
    private function validatedRunValues(
        string $label,
        string $startsOn,
        string $endsOn,
        int $capacity,
        PlanningRules $rules,
    ): array {
        $label = trim($label);
        if ($label === '' || strlen($label) > 128) {
            throw new DomainException('Ein BQ-Durchlauf benötigt eine Bezeichnung mit höchstens 128 Zeichen.');
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
        return ['label' => $label, 'startsOn' => $startsOn, 'endsOn' => $endsOn, 'capacity' => $capacity];
    }
}
