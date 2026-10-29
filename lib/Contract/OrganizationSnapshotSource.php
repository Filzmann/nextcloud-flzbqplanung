<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Contract;

interface OrganizationSnapshotSource {
    /** @return array<string,mixed>|null */
    public function snapshot(): ?array;
}
