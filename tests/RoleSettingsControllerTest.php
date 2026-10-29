<?php

declare(strict_types=1);

namespace FlzBqPlanning\Tests;

use OCA\FlzBqPlanning\Controller\RoleSettingsController;
use OCA\FlzBqPlanning\Service\AuthorizationService;
use OCA\FlzBqPlanning\Service\RoleSettingsService;
use OCP\IAppConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

final class RoleControllerConfig implements IAppConfig {
    public array $values = [];
    public int $writes = 0;
    public function getValueInt(string $appId, string $key, int $default): int { return $default; }
    public function getValueString(string $appId, string $key, string $default): string { return (string)($this->values[$key] ?? $default); }
    public function setValueInt(string $appId, string $key, int $value): void {}
    public function setValueString(string $appId, string $key, string $value): void { $this->values[$key] = $value; $this->writes++; }
}

final class RoleControllerGroups implements IGroupManager {
    /** @param list<string> $existing */
    public function __construct(private bool $admin, private array $existing = []) {}
    public function isAdmin(string $uid): bool { return $this->admin; }
    public function isInGroup(string $uid, string $gid): bool { return false; }
    public function get(string $gid): ?IGroup { return in_array($gid, $this->existing, true) ? new class implements IGroup {} : null; }
}

final class RoleControllerLogger implements LoggerInterface {
    public function warning(string $message, array $context = []): void {}
    public function error(string $message, array $context = []): void {}
}

TestRunner::test('permission settings API denies non-admin writes and stores validated semantic role keys', static function (): void {
    $config = new RoleControllerConfig();
    $groups = new RoleControllerGroups(false, ['flz-StvPDL', 'flz-PDL', 'flz-PFK']);
    $authorization = new AuthorizationService(new FrameworkSession(new FrameworkUser('user-a')), $groups, $config);
    $controller = new RoleSettingsController(
        new class implements IRequest {},
        new RoleSettingsService($config, organizationRolePolicy($groups, [
            'deputy_pdl' => 'flz-StvPDL',
            'pdl' => 'flz-PDL',
            'pfk' => 'flz-PFK',
        ])),
        $authorization,
        new RoleControllerLogger(),
    );

    $denied = $controller->update(['deputy_pdl'], ['deputy_pdl'], ['deputy_pdl'], ['deputy_pdl']);
    assertSame(403, $denied->status);
    assertSame(0, $config->writes);

    $adminGroups = new RoleControllerGroups(true, ['flz-StvPDL', 'flz-PDL', 'flz-PFK']);
    $admin = new RoleSettingsController(
        new class implements IRequest {},
        new RoleSettingsService($config, organizationRolePolicy($adminGroups, [
            'deputy_pdl' => 'flz-StvPDL',
            'pdl' => 'flz-PDL',
            'pfk' => 'flz-PFK',
        ])),
        new AuthorizationService(new FrameworkSession(new FrameworkUser('admin-a')), $adminGroups, $config, new FrameworkGrants()),
        new RoleControllerLogger(),
    );
    $saved = $admin->update(['deputy_pdl', 'pdl'], ['deputy_pdl'], ['deputy_pdl'], ['deputy_pdl', 'pfk']);
    assertSame(200, $saved->status);
    assertSame(['deputy_pdl', 'pdl'], $saved->data['data']['planningRoles']);
    assertSame(['deputy_pdl', 'pfk'], $saved->data['data']['pfkPoolRoles']);
    assertSame(4, $config->writes);
});
