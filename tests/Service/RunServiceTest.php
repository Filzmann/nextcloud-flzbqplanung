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

    public function updateModule(
        int $runId,
        int $moduleId,
        array $module,
        int $expectedRunVersion,
        int $expectedModuleVersion,
        string $actorUid,
    ): array {
        if (($this->runRows[$runId]['status'] ?? '') !== 'draft'
            || ($this->runRows[$runId]['version'] ?? 0) !== $expectedRunVersion
            || ($this->moduleRows[$moduleId]['runId'] ?? 0) !== $runId
            || ($this->moduleRows[$moduleId]['version'] ?? 0) !== $expectedModuleVersion) {
            throw new DomainException('stale');
        }
        $this->runRows[$runId]['version']++;
        $this->moduleRows[$moduleId] = array_replace($this->moduleRows[$moduleId], $module);
        $this->moduleRows[$moduleId]['version']++;
        return $this->moduleRows[$moduleId];
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

TestRunner::test('a current draft module can be edited with both optimistic versions', static function (): void {
    $store = new MemoryRunStore();
    $service = new RunService();
    $runId = $service->createRun($store, 'BQ 09/26', '2026-09-11', '2026-09-21', 10, new PlanningRules(), 'planner');
    $moduleId = $service->addModule($store, $runId, 'pflege-1', 'Pflege 1', 180, '2026-09-11', '09:00', '12:00', 0, 1, 'planner');

    $service->updateModule(
        $store,
        $runId,
        $moduleId,
        'Pflege und Begleitung',
        150,
        '2026-09-14',
        '09:30',
        '12:00',
        2,
        2,
        1,
        'planner',
    );

    assertSame('Pflege und Begleitung', $store->moduleRows[$moduleId]['title']);
    assertSame('2026-09-14', $store->moduleRows[$moduleId]['date']);
    assertSame(2, $store->moduleRows[$moduleId]['additionalCapacity']);
    assertSame(3, $store->runRows[$runId]['version']);
    assertSame(2, $store->moduleRows[$moduleId]['version']);
});

TestRunner::test('published stale and foreign module edits have no side effect', static function (): void {
    $store = new MemoryRunStore();
    $service = new RunService();
    $firstRun = $service->createRun($store, 'BQ 09/26', '2026-09-11', '2026-09-21', 10, new PlanningRules(), 'planner');
    $moduleId = $service->addModule($store, $firstRun, 'pflege-1', 'Pflege 1', 180, '2026-09-11', '09:00', '12:00', 0, 1, 'planner');
    $secondRun = $service->createRun($store, 'BQ 10/26', '2026-10-09', '2026-10-19', 10, new PlanningRules(), 'planner');
    $before = $store->moduleRows[$moduleId];

    assertThrows(
        static fn () => $service->updateModule($store, $secondRun, $moduleId, 'Fremd', 180, '2026-10-09', '09:00', '12:00', 0, 1, 1, 'planner'),
        DomainException::class,
    );
    assertSame($before, $store->moduleRows[$moduleId]);

    $store->runRows[$firstRun]['status'] = 'published';
    assertThrows(
        static fn () => $service->updateModule($store, $firstRun, $moduleId, 'Veröffentlicht', 180, '2026-09-11', '09:00', '12:00', 0, 2, 1, 'planner'),
        DomainException::class,
    );
    assertSame($before, $store->moduleRows[$moduleId]);

    $store->runRows[$firstRun]['status'] = 'draft';
    assertThrows(
        static fn () => $service->updateModule($store, $firstRun, $moduleId, 'Veraltet', 180, '2026-09-11', '09:00', '12:00', 0, 1, 1, 'planner'),
        DomainException::class,
    );
    assertSame($before, $store->moduleRows[$moduleId]);
});

TestRunner::test('module overlap detection reports real intersections but not adjacent slots', static function (): void {
    $service = new RunService();
    $conflicts = $service->moduleConflicts([
        ['id' => 1, 'date' => '2026-09-11', 'startsAt' => '09:00', 'endsAt' => '10:00'],
        ['id' => 2, 'date' => '2026-09-11', 'startsAt' => '09:30', 'endsAt' => '10:30'],
        ['id' => 3, 'date' => '2026-09-11', 'startsAt' => '10:30', 'endsAt' => '11:30'],
        ['id' => 4, 'date' => '2026-09-14', 'startsAt' => '09:30', 'endsAt' => '10:30'],
    ]);

    assertSame([['firstModuleId' => 1, 'secondModuleId' => 2]], $conflicts);
});
