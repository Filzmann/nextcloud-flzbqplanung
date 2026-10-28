<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Service;

use DomainException;
use OCA\FlzBqPlanning\Contract\CurriculumTemplateStore;

final class CurriculumTemplateService {
    /**
     * @param list<array<string,mixed>> $modules
     * @return array<string,mixed>
     */
    public function save(
        CurriculumTemplateStore $store,
        string $title,
        array $modules,
        int $expectedVersion,
        string $actorUid,
    ): array {
        $title = trim($title);
        if ($title === '' || strlen($title) > 128) {
            throw new DomainException('Die Curriculum-Vorlage benötigt einen kurzen Titel.');
        }
        if ($expectedVersion < 0) {
            throw new DomainException('Die Vorlagenversion ist ungültig.');
        }
        $actorUid = trim($actorUid);
        if ($actorUid === '') {
            throw new DomainException('Die Vorlagenbearbeitung benötigt eine handelnde Person.');
        }
        if ($modules === []) {
            throw new DomainException('Die Curriculum-Vorlage benötigt mindestens ein Modul.');
        }

        $validated = [];
        $keys = [];
        foreach ($modules as $module) {
            $moduleKey = trim((string)($module['moduleKey'] ?? ''));
            $moduleTitle = trim((string)($module['title'] ?? ''));
            $minutes = filter_var($module['minutes'] ?? null, FILTER_VALIDATE_INT);
            if (!preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', $moduleKey)) {
                throw new DomainException('Jedes Vorlagenmodul benötigt einen stabilen Schlüssel.');
            }
            if (isset($keys[$moduleKey])) {
                throw new DomainException('Modulschlüssel müssen innerhalb der Vorlage eindeutig sein.');
            }
            if ($moduleTitle === '' || strlen($moduleTitle) > 128) {
                throw new DomainException('Jedes Vorlagenmodul benötigt einen kurzen Titel.');
            }
            if ($minutes === false || $minutes < 1 || $minutes > 600) {
                throw new DomainException('Ein Vorlagenmodul muss zwischen 1 und 600 Minuten dauern.');
            }
            $keys[$moduleKey] = true;
            $validated[] = ['moduleKey' => $moduleKey, 'title' => $moduleTitle, 'minutes' => $minutes];
        }

        return $store->save($title, $validated, $expectedVersion, $actorUid);
    }
}
