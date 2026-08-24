<?php

declare(strict_types=1);

namespace AdBqPlanning\Tests;

use OCA\AdBqPlanning\Controller\RoleSettingsController;
use OCA\AdBqPlanning\Service\AuthorizationService;
use OCA\AdBqPlanning\Service\RoleSettingsService;
use OCP\IAppConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IRequest;

final class RoleControllerConfig implements IAppConfig {
    public array $values = [];
    public int $writes = 0;
    public function getValueInt(string $appId, string $key, int $default): int { return $default; }
    public function getValueString(string $appId, string $key, string $default): string { return (string)($this->values[$key] ?? $default); }
    public function setValueInt(string $appId, string $key, int $value): void {}
    public function setValueString(string $appId, string $key, string $value): void { $this->values[$key] = $value; $this->writes++; }
}

final class RoleControllerGroups implements IGroupManager {
    public function __construct(private bool $admin, private array $existing = []) {}
    public function isAdmin(string $uid): bool { return $this->admin; }
    public function isInGroup(string $uid, string $gid): bool { return false; }
    public function get(string $gid): ?IGroup { return in_array($gid, $this->existing, true) ? new class implements IGroup {} : null; }
}

TestRunner::test('role settings API denies non-admin writes and accepts validated admin groups', static function (): void {
    $config = new RoleControllerConfig();
    $groups = new RoleControllerGroups(false, ['bq-planning']);
    $authorization = new AuthorizationService(new FrameworkSession(new FrameworkUser('user-a')), $groups, $config);
    $controller = new RoleSettingsController(
        new class implements IRequest {},
        new RoleSettingsService($config, $groups),
        $authorization,
        new ProposalControllerLogger(),
    );

    $denied = $controller->update('bq-planning', '', '');
    assertSame(403, $denied->status);
    assertSame(0, $config->writes);

    $adminGroups = new RoleControllerGroups(true, ['bq-planning']);
    $admin = new RoleSettingsController(
        new class implements IRequest {},
        new RoleSettingsService($config, $adminGroups),
        new AuthorizationService(new FrameworkSession(new FrameworkUser('admin-a')), $adminGroups, $config),
        new ProposalControllerLogger(),
    );
    $saved = $admin->update('bq-planning', '', '');
    assertSame(200, $saved->status);
    assertSame('bq-planning', $saved->data['data']['planningGroup']);
    assertSame(3, $config->writes);
});
