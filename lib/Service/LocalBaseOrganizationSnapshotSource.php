<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Service;

use OCA\FlzBqPlanning\Contract\OrganizationSnapshotSource;
use OCA\LocalBase\PublicApi\V1\OrganizationSnapshotService;

final class LocalBaseOrganizationSnapshotSource implements OrganizationSnapshotSource {
    public function __construct(private ?OrganizationSnapshotService $snapshots = null) {}

    public function snapshot(): ?array {
        return $this->snapshots?->snapshot()->toArray();
    }
}
