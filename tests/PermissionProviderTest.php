<?php

declare(strict_types=1);

namespace OCA\FlzPermissionMatrix\PublicApi\V1 {
    interface PermissionProvider { public function descriptor(): PermissionProviderDescriptor; public function collect(): PermissionProviderResult; }
    final class PermissionProviderDescriptor {
        public function __construct(public string $appId, public string $displayName, public string $version, public array $capabilities) {}
    }
    final class PermissionCondition {
        private function __construct(public string $operator, public ?string $groupId = null, public array $children = []) {}
        public static function group(string $groupId): self { return new self('group', $groupId); }
        public static function nextcloudAdmin(): self { return new self('nextcloud-admin'); }
        public static function temporaryAppAdminGrant(): self { return new self('app-admin-grant'); }
        public static function all(array $children): self { return new self('all', null, $children); }
    }
    final class PermissionRule {
        public function __construct(
            public string $objectType,
            public string $objectName,
            public string $detail,
            public string $permission,
            public string $permissionLabel,
            public string $effect,
            public string $scope,
            public PermissionCondition $condition,
            public string $source,
            public string $confidence,
        ) {}
    }
    final class PermissionProviderResult {
        public function __construct(public array $rules, public bool $complete = true, public array $warnings = []) {}
    }
    final class RegisterPermissionProvidersEvent extends \OCP\EventDispatcher\Event {
        public array $providers = [];
        public function register(PermissionProvider $provider): void { $this->providers[] = $provider; }
    }
}

namespace FlzBqPlanning\Tests {
    use OCA\FlzBqPlanning\Permission\BqPermissionProvider;
    use OCA\FlzBqPlanning\Permission\BqPermissionProviderListener;
    use OCA\FlzBqPlanning\Permission\BqPermissionSourceInterface;
    use OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;

    TestRunner::test('permission provider exposes four independent BQ permissions for resolved semantic roles', static function (): void {
        $source = new class implements BqPermissionSourceInterface {
            public function permissionRoleGroups(): array {
                return [
                    'planning' => ['flz-StvPDL', 'flz-PDL'],
                    'teaching' => ['flz-StvPDL'],
                    'publishing' => ['flz-StvPDL'],
                    'pfkPool' => ['flz-StvPDL', 'flz-PFK'],
                ];
            }
            public function complete(): bool { return true; }
            public function warnings(): array { return []; }
        };
        $provider = new BqPermissionProvider($source);
        $result = $provider->collect();
        $byPermission = [];
        foreach ($result->rules as $rule) {
            $byPermission[$rule->permission][] = $rule;
        }

        $groupIds = static function (array $rules): array {
            $result = [];
            foreach ($rules as $rule) if ($rule->condition->operator === 'group') $result[] = $rule->condition->groupId;
            return $result;
        };
        assertSame(['flz-StvPDL', 'flz-PDL'], $groupIds($byPermission['bq.planning.manage']));
        assertSame(['flz-StvPDL'], $groupIds($byPermission['bq.teaching.manage']));
        assertSame(['flz-StvPDL'], $groupIds($byPermission['bq.publishing.manage']));
        assertSame(['flz-StvPDL', 'flz-PFK'], $groupIds($byPermission['bq.pfk_pool.manage']));
        foreach (['bq.planning.manage', 'bq.teaching.manage', 'bq.publishing.manage', 'bq.pfk_pool.manage', 'bq.settings.manage'] as $permission) {
            $nativeRule = $byPermission[$permission][0];
            assertSame('all', $nativeRule->condition->operator ?? null);
            assertSame(['nextcloud-admin', 'app-admin-grant'], array_map(static fn($condition): string => $condition->operator, $nativeRule->condition->children));
        }
        assertSame(['Datenschutzbeauftragte'], $groupIds($byPermission['bq.admin_access.manage']));
        assertTrue(!isset($byPermission['bq.attendance.manage']), 'The reserved attendance role must grant no current capability');

        $event = new RegisterPermissionProvidersEvent();
        $listener = new BqPermissionProviderListener($provider);
        assertTrue($listener instanceof \OCP\EventDispatcher\IEventListener);
        $listener->handle($event);
        assertSame($provider, $event->providers[0] ?? null);

        $application = (string)file_get_contents(dirname(__DIR__) . '/lib/AppInfo/Application.php');
        assertTrue(str_contains($application, 'RegisterPermissionProvidersEvent::class, BqPermissionProviderListener::class'));
    });

    TestRunner::test('permission provider reports an incomplete organization projection instead of claiming grants', static function (): void {
        $source = new class implements BqPermissionSourceInterface {
            public function permissionRoleGroups(): array { return ['planning' => [], 'teaching' => [], 'publishing' => [], 'pfkPool' => []]; }
            public function complete(): bool { return false; }
            public function warnings(): array { return ['Das Organigramm ist nicht verfügbar.']; }
        };
        $result = (new BqPermissionProvider($source))->collect();
        assertSame(false, $result->complete);
        assertSame(['Das Organigramm ist nicht verfügbar.'], $result->warnings);
        foreach ($result->rules as $rule) {
            if (str_starts_with($rule->permission, 'bq.') && $rule->condition->operator === 'group') {
                assertSame('Datenschutzbeauftragte', $rule->condition->groupId);
            }
        }
    });
}
