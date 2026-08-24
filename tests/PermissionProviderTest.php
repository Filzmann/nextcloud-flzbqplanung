<?php

declare(strict_types=1);

namespace OCA\FilzmannPermissionMatrix\PublicApi\V1 {
    interface PermissionProvider { public function descriptor(): PermissionProviderDescriptor; public function collect(): PermissionProviderResult; }
    final class PermissionProviderDescriptor {
        public function __construct(public string $appId, public string $displayName, public string $version, public array $capabilities) {}
    }
    final class PermissionCondition {
        private function __construct(public string $operator, public ?string $groupId = null) {}
        public static function group(string $groupId): self { return new self('group', $groupId); }
        public static function nextcloudAdmin(): self { return new self('nextcloud-admin'); }
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
    final class RegisterPermissionProvidersEvent {
        public array $providers = [];
        public function register(PermissionProvider $provider): void { $this->providers[] = $provider; }
    }
}

namespace AdBqPlanning\Tests {
    use OCA\AdBqPlanning\Permission\BqPermissionProvider;
    use OCA\AdBqPlanning\Permission\BqPermissionProviderListener;
    use OCA\AdBqPlanning\Permission\BqPermissionSourceInterface;
    use OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;

    TestRunner::test('permission provider exposes only configured canonical BQ roles', static function (): void {
        $source = new class implements BqPermissionSourceInterface {
            public function roleGroups(): array {
                return [
                    'planning' => 'bq-planning',
                    'teaching' => '',
                    'publishing' => 'bq-publishing',
                ];
            }
        };
        $provider = new BqPermissionProvider($source);
        $result = $provider->collect();
        $byPermission = [];
        foreach ($result->rules as $rule) {
            $byPermission[$rule->permission][] = $rule;
        }

        $groupId = static function (array $rules): ?string {
            foreach ($rules as $rule) {
                if ($rule->condition->operator === 'group') {
                    return $rule->condition->groupId;
                }
            }
            return null;
        };
        assertSame('bq-planning', $groupId($byPermission['bq.planning.manage']));
        assertSame('bq-publishing', $groupId($byPermission['bq.publishing.manage']));
        assertSame('nextcloud-admin', $byPermission['bq.settings.manage'][0]->condition->operator ?? null);
        assertSame(null, $groupId($byPermission['bq.teaching.manage']), 'An unconfigured role must have no group grant');
        assertTrue(!isset($byPermission['bq.attendance.manage']), 'The reserved attendance role must grant no current capability');

        $event = new RegisterPermissionProvidersEvent();
        (new BqPermissionProviderListener($provider))->handle($event);
        assertSame($provider, $event->providers[0] ?? null);

        $application = (string)file_get_contents(dirname(__DIR__) . '/lib/AppInfo/Application.php');
        assertTrue(str_contains($application, 'RegisterPermissionProvidersEvent::class, BqPermissionProviderListener::class'));
    });
}
