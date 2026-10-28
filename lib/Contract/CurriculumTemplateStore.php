<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Contract;

interface CurriculumTemplateStore {
    /** @return array<string,mixed>|null */
    public function current(): ?array;

    /**
     * @param list<array{moduleKey:string,title:string,minutes:int}> $modules
     * @return array<string,mixed>
     */
    public function save(string $title, array $modules, int $expectedVersion, string $actorUid): array;
}
