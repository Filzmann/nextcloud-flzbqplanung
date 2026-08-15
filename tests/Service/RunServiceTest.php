<?php

declare(strict_types=1);

namespace AdBqPlanning\Tests;

use DomainException;
use OCA\AdBqPlanning\Contract\RunStore;
use OCA\AdBqPlanning\Domain\Scheduling\PlanningRules;
use OCA\AdBqPlanning\Service\RunService;

final class MemoryRunStore implements RunStore {
    /** @var array<int,array<string,mixed>> */
    public array $runRows = [];
    /** @var array<int,array<string,mixed>> */
    public array $moduleRows = [];
    public int $statusWrites = 0;

    public function createRun(array $run): int {
        $id = count($this->runRows) + 1;
        $this->runRows[$id] = ['id' => $id, 'version' => 1, 'status' => 'draft'] + $run;
        return $id;
    }

    public function runs(): array {
        return array_values($this->runRows);
    }

    public function run(int $runId): array {
        return $this->runRows[$runId];
    }

    public function addModule(int $runId, array $module, int $expectedVersion, string $actorUid): int {
        if ($this->runRows[$runId]['version'] !== $expectedVersion) {
            throw new DomainException('stale');
        }
        $id = count($this->moduleRows) + 1;
        $this->moduleRows[$id] = ['id' => $id, 'runId' => $runId, 'version' => 1] + $module;
        $this->runRows[$runId]['version']++;
        return $id;
    }

    public function modules(int $runId): array {
        return array_values(array_filter(
            $this->moduleRows,
            static fn (array $module): bool => $module['runId'] === $runId,
        ));
    }

    public function changeStatus(int $runId, string $from, string $to, int $expectedVersion, string $actorUid): array {
        $run = $this->runRows[$runId];
        if ($run['status'] !== $from || $run['version'] !== $expectedVersion) {
            throw new DomainException('stale');
        }
        $this->statusWrites++;
        $this->runRows[$runId]['status'] = $to;
        $this->runRows[$runId]['version']++;
        return $this->runRows[$runId];
    }
}

TestRunner::test('regular BQ capacity is capped at ten without a waitlist', static function (): void {
    $store = new MemoryRunStore();
    $service = new RunService();
    $rules = new PlanningRules();

    $id = $service->createRun($store, 'BQ 09/26', '2026-09-11', '2026-09-21', 10, $rules, 'hr-user');
    assertSame(10, $store->runRows[$id]['capacity']);
    assertThrows(
        static fn () => $service->createRun($store, 'Zu groß', '2026-09-11', '2026-09-21', 11, $rules, 'hr-user'),
        DomainException::class,
    );
    assertSame(1, count($store->runRows));
});

TestRunner::test('run duration counts Monday through Friday and honors the configured start weekday', static function (): void {
    $store = new MemoryRunStore();
    $service = new RunService();

    $service->createRun($store, 'BQ Freitag', '2026-09-11', '2026-09-21', 10, new PlanningRules(), 'hr-user');
    assertThrows(
        static fn () => $service->createRun($store, 'Falsche Dauer', '2026-09-11', '2026-09-18', 10, new PlanningRules(), 'hr-user'),
        DomainException::class,
    );
    assertThrows(
        static fn () => $service->createRun($store, 'Falscher Start', '2026-09-14', '2026-09-22', 10, new PlanningRules(), 'hr-user'),
        DomainException::class,
    );
});

TestRunner::test('catch-up capacity belongs to a scheduled module and not to the whole run', static function (): void {
    $store = new MemoryRunStore();
    $service = new RunService();
    $runId = $service->createRun($store, 'BQ 09/26', '2026-09-11', '2026-09-21', 10, new PlanningRules(), 'hr-user');

    $moduleId = $service->addModule(
        $store,
        $runId,
        'pflege-1',
        'Pflege 1',
        180,
        '2026-09-11',
        '09:00',
        '12:00',
        3,
        1,
        'hr-user',
    );
    assertSame(3, $store->moduleRows[$moduleId]['additionalCapacity']);
    assertTrue(!array_key_exists('additionalCapacity', $store->runRows[$runId]));
    assertThrows(
        static fn () => $service->addModule($store, $runId, 'x', 'X', 30, '2026-09-11', '09:00', '09:30', 11, 2, 'hr-user'),
        DomainException::class,
    );
});

TestRunner::test('only a complete draft can be published and rejected publication has no side effect', static function (): void {
    $store = new MemoryRunStore();
    $service = new RunService();
    $runId = $service->createRun($store, 'BQ 09/26', '2026-09-11', '2026-09-21', 10, new PlanningRules(), 'hr-user');

    assertThrows(static fn () => $service->publish($store, $runId, 1, 'hr-user'), DomainException::class);
    assertSame(0, $store->statusWrites);
    $service->addModule($store, $runId, 'pflege-1', 'Pflege 1', 180, '2026-09-11', '09:00', '12:00', 0, 1, 'hr-user');
    $published = $service->publish($store, $runId, 2, 'hr-user');
    assertSame('published', $published['status']);
    assertThrows(static fn () => $service->publish($store, $runId, 3, 'hr-user'), DomainException::class);
    assertSame(1, $store->statusWrites);
});

