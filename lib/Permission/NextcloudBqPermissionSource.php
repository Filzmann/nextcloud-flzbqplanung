<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Permission;

use OCA\FlzBqPlanning\Service\RoleSettingsService;

final class NextcloudBqPermissionSource implements BqPermissionSourceInterface {
    public function __construct(private RoleSettingsService $settings) {}

    public function permissionRoleGroups(): array {
        return $this->settings->effectiveGroups();
    }

    public function complete(): bool {
        return $this->settings->selection()['complete'];
    }

    public function warnings(): array {
        $selection = $this->settings->selection();
        return $selection['message'] === '' ? [] : [$selection['message']];
    }
}
