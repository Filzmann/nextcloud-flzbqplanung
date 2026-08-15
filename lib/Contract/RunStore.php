<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Contract;

interface RunStore {
    /** @param array<string,mixed> $run */
    public function createRun(array $run): int;

    /** @return list<array<string,mixed>> */
    public function runs(): array;

    /** @return array<string,mixed> */
    public function run(int $runId): array;

    /** @param array<string,mixed> $module */
    public function addModule(int $runId, array $module, int $expectedVersion, string $actorUid): int;

    /** @return list<array<string,mixed>> */
    public function modules(int $runId): array;

    /** @return array<string,mixed> */
    public function changeStatus(int $runId, string $from, string $to, int $expectedVersion, string $actorUid): array;
}

