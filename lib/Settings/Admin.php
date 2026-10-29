<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Settings;

use OCA\FlzBqPlanning\AppInfo\Application;
use OCA\FlzBqPlanning\Service\AuthorizationService;
use OCA\FlzBqPlanning\Service\RoleSettingsService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

final class Admin implements ISettings {
    public function __construct(
        private RoleSettingsService $roleSettings,
        private AuthorizationService $authorization,
    ) {}

    public function getForm(): TemplateResponse {
        $canConfigure = $this->authorization->isAdmin();
        return new TemplateResponse(Application::APP_ID, 'admin', [
            'canConfigurePermissions' => $canConfigure,
            'permissionSettings' => $canConfigure ? $this->roleSettings->current() : [],
            'roleSelection' => $canConfigure ? $this->roleSettings->selection() : [],
        ]);
    }
    public function getSection(): string { return Application::APP_ID; }
    public function getPriority(): int { return 30; }
}
