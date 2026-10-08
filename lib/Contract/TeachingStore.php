<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Contract;

interface TeachingStore {
    /** @param array<string,mixed> $lecturer */
    public function createLecturer(array $lecturer): int;

    /** @return list<array<string,mixed>> */
    public function lecturers(): array;

    /** @return array<string,mixed> */
    public function lecturer(int $lecturerId): array;

    /** @return array<string,mixed> */
    public function run(int $runId): array;

    /** @return array<string,mixed> */
    public function module(int $moduleId): array;

    /** @return array<string,mixed> */
    public function setLead(int $runId, int $lecturerId, int $expectedVersion, string $actorUid): array;

    public function activeRequestForModule(int $moduleId): ?array;

    /** @param array<string,mixed> $request */
    public function createRequest(array $request, int $expectedRunVersion, int $expectedModuleVersion, string $actorUid): int;

    /** @return array<string,mixed> */
    public function request(int $requestId): array;

    /** @return list<array<string,mixed>> */
    public function requests(): array;

    /** @return array<string,mixed> */
    public function transitionRequest(int $requestId, string $from, string $to, int $expectedVersion, string $actorUid, string $assignmentAction): array;
}
