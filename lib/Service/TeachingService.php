<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Service;

use DomainException;
use OCA\AdBqPlanning\Contract\TeachingStore;

final class TeachingService {
    private const REQUEST_TRANSITIONS = [
        'requested' => ['confirmed', 'declined', 'cancelled'],
        'confirmed' => ['cancelled'],
    ];

    public function createLecturer(
        TeachingStore $store,
        string $kind,
        string $nextcloudUid,
        string $displayName,
        string $email,
        string $actorUid,
    ): int {
        $kind = trim($kind);
        $nextcloudUid = trim($nextcloudUid);
        $displayName = trim($displayName);
        $email = trim($email);
        $actorUid = $this->actor($actorUid);
        if (!in_array($kind, ['internal', 'external'], true)) {
            throw new DomainException('Dozentinnen müssen intern oder extern sein.');
        }
        if ($kind === 'internal') {
            if (!preg_match('/^[A-Za-z0-9_.@-]{1,64}$/', $nextcloudUid) || $displayName !== '' || $email !== '') {
                throw new DomainException('Interne PFKs werden ausschließlich über ihre Nextcloud-UID referenziert.');
            }
        } else {
            if ($nextcloudUid !== '' || $displayName === '' || strlen($displayName) > 128
                || strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new DomainException('Externe Dozentinnen benötigen Name und gültige E-Mail-Adresse.');
            }
        }
        return $store->createLecturer([
            'kind' => $kind,
            'nextcloudUid' => $kind === 'internal' ? $nextcloudUid : null,
            'displayName' => $kind === 'external' ? $displayName : null,
            'email' => $kind === 'external' ? $email : null,
            'createdBy' => $actorUid,
        ]);
    }

    /** @return array<string,mixed> */
    public function setLead(
        TeachingStore $store,
        int $runId,
        int $lecturerId,
        int $expectedRunVersion,
        string $actorUid,
    ): array {
        $run = $store->run($runId);
        $lecturer = $store->lecturer($lecturerId);
        if (($run['status'] ?? '') !== 'draft') {
            throw new DomainException('Die Haupt-PFK kann nur im BQ-Entwurf geändert werden.');
        }
        if ((int)($run['version'] ?? 0) !== $expectedRunVersion) {
            throw new DomainException('Der BQ-Durchlauf wurde zwischenzeitlich geändert.');
        }
        if (($lecturer['kind'] ?? '') !== 'internal' || ($lecturer['active'] ?? false) !== true) {
            throw new DomainException('Als Haupt-PFK ist nur eine aktive interne PFK zulässig.');
        }
        return $store->setLead($runId, $lecturerId, $expectedRunVersion, $this->actor($actorUid));
    }

    public function createRequest(
        TeachingStore $store,
        int $moduleId,
        int $lecturerId,
        int $expectedRunVersion,
        int $expectedModuleVersion,
        string $actorUid,
    ): int {
        $module = $store->module($moduleId);
        $run = $store->run((int)($module['runId'] ?? 0));
        $lecturer = $store->lecturer($lecturerId);
        if (($run['status'] ?? '') !== 'draft') {
            throw new DomainException('Externe Anfragen können nur im BQ-Entwurf erstellt werden.');
        }
        if ((int)($run['version'] ?? 0) !== $expectedRunVersion) {
            throw new DomainException('Der BQ-Durchlauf wurde zwischenzeitlich geändert.');
        }
        if ((int)($module['version'] ?? 0) !== $expectedModuleVersion) {
            throw new DomainException('Das Curriculum-Modul wurde zwischenzeitlich geändert.');
        }
        if (($lecturer['kind'] ?? '') !== 'external' || ($lecturer['active'] ?? false) !== true) {
            throw new DomainException('Anfragen benötigen eine aktive externe Dozentin aus dem Pool.');
        }
        if ($store->activeRequestForModule($moduleId) !== null) {
            throw new DomainException('Für dieses Modul besteht bereits eine aktive Dozentinnenanfrage.');
        }
        return $store->createRequest([
            'moduleId' => $moduleId,
            'lecturerId' => $lecturerId,
            'status' => 'requested',
        ], $expectedRunVersion, $expectedModuleVersion, $this->actor($actorUid));
    }

    /** @return array<string,mixed> */
    public function transitionRequest(
        TeachingStore $store,
        int $requestId,
        string $targetStatus,
        int $expectedVersion,
        string $actorUid,
    ): array {
        $request = $store->request($requestId);
        $current = (string)($request['status'] ?? '');
        if ((int)($request['version'] ?? 0) !== $expectedVersion) {
            throw new DomainException('Die Dozentinnenanfrage wurde zwischenzeitlich geändert.');
        }
        if (!in_array($targetStatus, self::REQUEST_TRANSITIONS[$current] ?? [], true)) {
            throw new DomainException('Dieser Statuswechsel der Dozentinnenanfrage ist nicht zulässig.');
        }
        return $store->transitionRequest(
            $requestId,
            $current,
            $targetStatus,
            $expectedVersion,
            $this->actor($actorUid),
            $targetStatus === 'confirmed' ? 'assign' : ($current === 'confirmed' ? 'clear' : 'none'),
        );
    }

    private function actor(string $actorUid): string {
        $actorUid = trim($actorUid);
        if ($actorUid === '') {
            throw new DomainException('Für diese Änderung ist eine handelnde Person erforderlich.');
        }
        return $actorUid;
    }
}
