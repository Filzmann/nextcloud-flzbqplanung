<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Permission;

use OCA\AdBqPlanning\Service\RoleSettingsService;

final class NextcloudBqPermissionSource implements BqPermissionSourceInterface {
    public function __construct(private RoleSettingsService $settings) {}

    public function roleGroups(): array {
        $settings = $this->settings->current();
        return [
            'planning' => $settings['planningGroup'],
            'teaching' => $settings['teachingGroup'],
            'publishing' => $settings['publishingGroup'],
        ];
    }
}
