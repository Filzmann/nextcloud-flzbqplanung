<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Contract;

interface RunStore {
    /** @param array<string,mixed> $run */
    public function createRun(array $run): int;

    /** @param array<string,mixed> $run
     * @return array<string,mixed>
     */
    public function updateRun(int $runId, array $run, int $expectedVersion, string $actorUid): array;

    /** @return list<array<string,mixed>> */
    public function runs(): array;

    /** @return array<string,mixed> */
    public function run(int $runId): array;

    /** @param array<string,mixed> $module */
    public function addModule(int $runId, array $module, int $expectedVersion, string $actorUid): int;

    /** @param array<string,mixed> $module
     * @return array<string,mixed>
     */
    public function updateModule(
        int $runId,
        int $moduleId,
        array $module,
        int $expectedRunVersion,
        int $expectedModuleVersion,
        string $actorUid,
    ): array;

    /** @return list<array<string,mixed>> */
    public function modules(int $runId): array;

    /** @return array<string,mixed> */
    public function changeStatus(int $runId, string $from, string $to, int $expectedVersion, string $actorUid): array;
}
