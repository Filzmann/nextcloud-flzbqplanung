<?php

declare(strict_types=1);

namespace AdBqPlanning\Tests;

use DomainException;
use OCA\AdBqPlanning\Contract\TeachingStore;
use OCA\AdBqPlanning\Service\TeachingService;

final class MemoryTeachingStore implements TeachingStore {
    public array $lecturerRows = [];
    public array $runRows = [1 => ['id' => 1, 'status' => 'draft', 'version' => 1, 'leadLecturerId' => null]];
    public array $moduleRows = [4 => ['id' => 4, 'runId' => 1, 'version' => 1, 'lecturerId' => null]];
    public array $requestRows = [];
    public int $transitionWrites = 0;

    public function createLecturer(array $lecturer): int {
        $id = count($this->lecturerRows) + 1;
        $this->lecturerRows[$id] = ['id' => $id, 'version' => 1, 'active' => true] + $lecturer;
        return $id;
    }

    public function lecturers(): array { return array_values($this->lecturerRows); }
    public function lecturer(int $lecturerId): array { return $this->lecturerRows[$lecturerId]; }
    public function run(int $runId): array { return $this->runRows[$runId]; }
    public function module(int $moduleId): array { return $this->moduleRows[$moduleId]; }

    public function setLead(int $runId, int $lecturerId, int $expectedVersion, string $actorUid): array {
        if ($this->runRows[$runId]['version'] !== $expectedVersion) throw new DomainException('stale');
        $this->runRows[$runId]['leadLecturerId'] = $lecturerId;
        $this->runRows[$runId]['version']++;
        return $this->runRows[$runId];
    }

    public function activeRequestForModule(int $moduleId): ?array {
        foreach ($this->requestRows as $request) {
            if ($request['moduleId'] === $moduleId && in_array($request['status'], ['requested', 'confirmed'], true)) return $request;
        }
        return null;
    }

    public function createRequest(array $request, int $expectedRunVersion, int $expectedModuleVersion, string $actorUid): int {
        if ($this->runRows[1]['version'] !== $expectedRunVersion) throw new DomainException('stale');
        if ($this->moduleRows[$request['moduleId']]['version'] !== $expectedModuleVersion) throw new DomainException('stale');
        $id = count($this->requestRows) + 1;
        $this->requestRows[$id] = ['id' => $id, 'version' => 1] + $request;
        $this->moduleRows[$request['moduleId']]['version']++;
        $this->runRows[1]['version']++;
        return $id;
    }

    public function request(int $requestId): array { return $this->requestRows[$requestId]; }
    public function requests(): array { return array_values($this->requestRows); }

    public function transitionRequest(int $requestId, string $from, string $to, int $expectedVersion, string $actorUid, string $assignmentAction): array {
        $request = $this->requestRows[$requestId];
        if ($request['status'] !== $from || $request['version'] !== $expectedVersion) throw new DomainException('stale');
        $this->transitionWrites++;
        $this->requestRows[$requestId]['status'] = $to;
        $this->requestRows[$requestId]['version']++;
        if ($assignmentAction === 'assign') $this->moduleRows[$request['moduleId']]['lecturerId'] = $request['lecturerId'];
        if ($assignmentAction === 'clear' && $this->moduleRows[$request['moduleId']]['lecturerId'] === $request['lecturerId']) {
            $this->moduleRows[$request['moduleId']]['lecturerId'] = null;
        }
        return $this->requestRows[$requestId];
    }
}

TestRunner::test('internal PFK and external lecturer keep separate minimal identities', static function (): void {
    $store = new MemoryTeachingStore();
    $service = new TeachingService();
    $internal = $service->createLecturer($store, 'internal', 'ad-demo-pfk-a', '', '', 'admin');
    $external = $service->createLecturer($store, 'external', '', 'Erika Beispiel', 'erika@example.invalid', 'admin');
    assertSame('ad-demo-pfk-a', $store->lecturerRows[$internal]['nextcloudUid']);
    assertSame(null, $store->lecturerRows[$internal]['email']);
    assertSame('Erika Beispiel', $store->lecturerRows[$external]['displayName']);
    assertSame(null, $store->lecturerRows[$external]['nextcloudUid']);
    assertThrows(static fn () => $service->createLecturer($store, 'external', '', 'Ohne Kontakt', '', 'admin'), DomainException::class);
    assertSame(2, count($store->lecturerRows));
});

TestRunner::test('only an active internal PFK can lead a draft run', static function (): void {
    $store = new MemoryTeachingStore();
    $service = new TeachingService();
    $pfk = $service->createLecturer($store, 'internal', 'ad-demo-pfk-a', '', '', 'admin');
    $external = $service->createLecturer($store, 'external', '', 'Erika Beispiel', 'erika@example.invalid', 'admin');
    $run = $service->setLead($store, 1, $pfk, 1, 'admin');
    assertSame($pfk, $run['leadLecturerId']);
    assertThrows(static fn () => $service->setLead($store, 1, $external, 2, 'admin'), DomainException::class);
});

TestRunner::test('external requests have guarded transitions and confirmation assigns only that module', static function (): void {
    $store = new MemoryTeachingStore();
    $service = new TeachingService();
    $external = $service->createLecturer($store, 'external', '', 'Erika Beispiel', 'erika@example.invalid', 'admin');
    $requestId = $service->createRequest($store, 4, $external, 1, 1, 'admin');
    assertSame('requested', $store->requestRows[$requestId]['status']);
    assertThrows(static fn () => $service->createRequest($store, 4, $external, 2, 2, 'admin'), DomainException::class);
    assertSame(1, count($store->requestRows));
    $confirmed = $service->transitionRequest($store, $requestId, 'confirmed', 1, 'admin');
    assertSame('confirmed', $confirmed['status']);
    assertSame($external, $store->moduleRows[4]['lecturerId']);
    assertThrows(static fn () => $service->transitionRequest($store, $requestId, 'declined', 2, 'admin'), DomainException::class);
    assertSame(1, $store->transitionWrites);
    $cancelled = $service->transitionRequest($store, $requestId, 'cancelled', 2, 'admin');
    assertSame('cancelled', $cancelled['status']);
    assertSame(null, $store->moduleRows[4]['lecturerId']);
});
