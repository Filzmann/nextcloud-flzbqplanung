<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Permission;

interface BqPermissionSourceInterface {
    /** @return array{planning:string,teaching:string,publishing:string} */
    public function roleGroups(): array;
}
