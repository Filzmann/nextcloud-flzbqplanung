<?php

declare(strict_types=1);

namespace OCP\Settings {
    if (!interface_exists(ISettings::class)) {
        interface ISettings {
            public function getForm(): \OCP\AppFramework\Http\TemplateResponse;
            public function getSection(): string;
            public function getPriority(): int;
        }
    }
}

namespace FlzBqPlanning\Tests {
    use OCA\FlzBqPlanning\Service\AuthorizationService;
    use OCA\FlzBqPlanning\Service\RoleSettingsService;
    use OCA\FlzBqPlanning\Settings\Admin;
    use OCP\IAppConfig;
    use OCP\IGroup;
    use OCP\IGroupManager;

    final class AdminSettingsConfig implements IAppConfig {
        public function getValueInt(string $appId, string $key, int $default): int { return $default; }
        public function getValueString(string $appId, string $key, string $default): string { return $default; }
        public function setValueInt(string $appId, string $key, int $value): void {}
        public function setValueString(string $appId, string $key, string $value): void {}
    }

    final class AdminSettingsGroups implements IGroupManager {
        public function isAdmin(string $uid): bool { return $uid === 'admin-a'; }
        public function isInGroup(string $uid, string $gid): bool { return false; }
        public function get(string $gid): ?IGroup { return new class implements IGroup {}; }
    }

    TestRunner::test('Nextcloud admin section renders semantic permission defaults only with active app-local admin access', static function (): void {
        $config = new AdminSettingsConfig();
        $groups = new AdminSettingsGroups();
        $settings = new RoleSettingsService($config, organizationRolePolicy($groups, [
            'deputy_pdl' => 'flz-StvPDL',
            'pdl' => 'flz-PDL',
            'pfk' => 'flz-PFK',
        ]));

        $withoutGrant = (new Admin(
            $settings,
            new AuthorizationService(new FrameworkSession(new FrameworkUser('admin-a')), $groups, $config),
        ))->getForm();
        assertSame(false, $withoutGrant->params['canConfigurePermissions']);
        assertSame([], $withoutGrant->params['permissionSettings']);

        $withGrant = (new Admin(
            $settings,
            new AuthorizationService(
                new FrameworkSession(new FrameworkUser('admin-a')),
                $groups,
                $config,
                new FrameworkGrants(['admin-a']),
            ),
        ))->getForm();
        assertSame(true, $withGrant->params['canConfigurePermissions']);
        assertSame(['deputy_pdl'], $withGrant->params['permissionSettings']['planningRoles']);
        assertSame(['deputy_pdl'], $withGrant->params['permissionSettings']['pfkPoolRoles']);
    });
}
