<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Settings;

use OCA\FlzBqPlanning\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

final class Admin implements ISettings {
    public function getForm(): TemplateResponse { return new TemplateResponse(Application::APP_ID,'admin'); }
    public function getSection(): string { return Application::APP_ID; }
    public function getPriority(): int { return 30; }
}
