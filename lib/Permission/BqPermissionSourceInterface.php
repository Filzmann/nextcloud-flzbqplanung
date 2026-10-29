<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Permission;

interface BqPermissionSourceInterface {
    /** @return array<string,list<string>> */
    public function permissionRoleGroups(): array;

    public function complete(): bool;

    /** @return list<string> */
    public function warnings(): array;
}
