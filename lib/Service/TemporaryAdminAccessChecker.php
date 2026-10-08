<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Service;

interface TemporaryAdminAccessChecker {
    public function hasActiveGrant(string $uid): bool;
}
