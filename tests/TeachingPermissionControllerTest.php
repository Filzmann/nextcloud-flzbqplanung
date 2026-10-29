<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IUserManager::class)) {
        interface IUserManager { public function userExists($uid); }
    }
}

namespace FlzBqPlanning\Tests {
    use OCA\FlzBqPlanning\Contract\TeachingStore;
    use OCA\FlzBqPlanning\Controller\TeachingController;
    use OCA\FlzBqPlanning\Service\AuthorizationService;
    use OCA\FlzBqPlanning\Service\InternalPfkDirectory;
    use OCA\FlzBqPlanning\Service\TeachingService;
    use OCP\IAppConfig;
    use OCP\IGroup;
    use OCP\IGroupManager;
    use OCP\IRequest;
    use OCP\IUserManager;
    use Psr\Log\LoggerInterface;

    final class TeachingPermissionGroups implements IGroupManager {
        public function isAdmin(string $uid): bool { return false; }
        public function isInGroup(string $uid, string $gid): bool {
            return ($uid === 'deputy-a' && $gid === 'flz-StvPDL')
                || ($uid === 'pfk-a' && $gid === 'flz-PFK');
        }
        public function get(string $gid): ?IGroup {
            return in_array($gid, ['flz-StvPDL', 'flz-PDL', 'flz-PFK'], true) ? new class implements IGroup {} : null;
        }
        public function displayNamesInGroup($gid, $search = '', $limit = -1, $offset = 0): array {
            return $gid === 'flz-PFK' ? ['pfk-a' => 'PFK Alpha'] : [];
        }
    }

    final class TeachingPermissionUsers implements IUserManager {
        public function userExists($uid): bool { return $uid === 'pfk-a'; }
    }

    final class TeachingPermissionLogger implements LoggerInterface {
        public function warning(string $message, array $context = []): void {}
        public function error(string $message, array $context = []): void {}
    }

    final class TeachingPermissionConfig implements IAppConfig {
        public array $values = [
            'permission_roles_planning' => '[]',
            'permission_roles_teaching' => '[]',
            'permission_roles_publishing' => '[]',
            'permission_roles_pfk_pool' => '["deputy_pdl"]',
        ];
        public function getValueInt(string $appId, string $key, int $default): int { return $default; }
        public function getValueString(string $appId, string $key, string $default): string { return (string)($this->values[$key] ?? $default); }
        public function setValueInt(string $appId, string $key, int $value): void {}
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$key] = $value; }
    }

    final class TeachingPermissionStore implements TeachingStore {
        public int $writes = 0;
        /** @var array<int,array<string,mixed>> */
        public array $rows = [
            1 => ['id' => 1, 'kind' => 'internal', 'nextcloudUid' => 'pfk-a', 'displayName' => null, 'email' => null, 'active' => true, 'version' => 1],
            2 => ['id' => 2, 'kind' => 'external', 'nextcloudUid' => null, 'displayName' => 'Externe Lehrkraft', 'email' => 'extern@example.invalid', 'active' => true, 'version' => 1],
        ];
        public function createLecturer(array $lecturer): int {
            $this->writes++;
            $id = count($this->rows) + 1;
            $this->rows[$id] = ['id' => $id, 'active' => true, 'version' => 1, ...$lecturer];
            return $id;
        }
        public function lecturers(?string $kind = null): array {
            return array_values(array_filter($this->rows, static fn(array $row): bool => $kind === null || $row['kind'] === $kind));
        }
        public function lecturer(int $lecturerId): array { return $this->rows[$lecturerId]; }
        public function updateLecturerStatus(int $lecturerId, bool $fromActive, bool $toActive, int $expectedVersion, string $actorUid): array {
            $this->writes++;
            $this->rows[$lecturerId]['active'] = $toActive;
            $this->rows[$lecturerId]['version']++;
            return $this->rows[$lecturerId];
        }
        public function run(int $runId): array { return []; }
        public function module(int $moduleId): array { return []; }
        public function setLead(int $runId, int $lecturerId, int $expectedVersion, string $actorUid): array { $this->writes++; return []; }
        public function activeRequestForModule(int $moduleId): ?array { return null; }
        public function createRequest(array $request, int $expectedRunVersion, int $expectedModuleVersion, string $actorUid): int { $this->writes++; return 1; }
        public function request(int $requestId): array { return []; }
        public function requests(): array { return [['id' => 1, 'lecturerId' => 2]]; }
        public function transitionRequest(int $requestId, string $from, string $to, int $expectedVersion, string $actorUid, string $assignmentAction): array { $this->writes++; return []; }
    }

    TestRunner::test('PFK pool permission exposes and mutates internal profiles only', static function (): void {
        $groups = new TeachingPermissionGroups();
        $config = new TeachingPermissionConfig();
        $policy = organizationRolePolicy($groups, [
            'deputy_pdl' => 'flz-StvPDL',
            'pdl' => 'flz-PDL',
            'pfk' => 'flz-PFK',
        ]);
        $authorization = new AuthorizationService(
            new FrameworkSession(new FrameworkUser('deputy-a')),
            $groups,
            $config,
            null,
            $policy,
        );
        $store = new TeachingPermissionStore();
        $controller = new TeachingController(
            new class implements IRequest {},
            $store,
            new TeachingService(),
            $authorization,
            new InternalPfkDirectory($groups, new TeachingPermissionUsers(), $policy),
            new TeachingPermissionLogger(),
        );

        $listed = $controller->lecturers();
        assertSame(200, $listed->status);
        assertSame(['internal'], array_column($listed->data['data'], 'kind'));
        assertTrue(!str_contains(json_encode($listed->data, JSON_THROW_ON_ERROR), 'extern@example.invalid'));
        assertSame(403, $controller->requests()->status);

        $deniedCreate = $controller->createLecturer('external', '', 'Externe Lehrkraft', 'extern@example.invalid');
        assertSame(403, $deniedCreate->status);
        assertSame(0, $store->writes);

        $deniedStatus = $controller->updateLecturerStatus(2, 'inactive', 1);
        assertSame(403, $deniedStatus->status);
        assertSame(0, $store->writes);

        $allowedStatus = $controller->updateLecturerStatus(1, 'inactive', 1);
        assertSame(200, $allowedStatus->status);
        assertSame(false, $store->rows[1]['active']);
        assertSame(1, $store->writes);

        $allowedCreate = $controller->createLecturer('internal', 'pfk-a', '', '');
        assertSame(200, $allowedCreate->status);
        assertSame(2, $store->writes);
    });

TestRunner::test('teaching permission cannot mutate internal PFK pool profiles', static function (): void {
    $groups = new TeachingPermissionGroups();
    $config = new TeachingPermissionConfig();
    $config->values['permission_roles_teaching'] = '["deputy_pdl"]';
    $config->values['permission_roles_pfk_pool'] = '[]';
    $policy = organizationRolePolicy($groups, [
        'deputy_pdl' => 'flz-StvPDL',
        'pdl' => 'flz-PDL',
        'pfk' => 'flz-PFK',
    ]);
    $controller = new TeachingController(
        new class implements IRequest {},
        $store = new TeachingPermissionStore(),
        new TeachingService(),
        new AuthorizationService(
            new FrameworkSession(new FrameworkUser('deputy-a')),
            $groups,
            $config,
            null,
            $policy,
        ),
        new InternalPfkDirectory($groups, new TeachingPermissionUsers(), $policy),
        new TeachingPermissionLogger(),
    );

    $allowed = $controller->updateLecturerStatus(2, 'inactive', 1);
    assertSame(200, $allowed->status);
    assertSame(false, $store->rows[2]['active']);
    assertSame(1, $store->writes);

    $denied = $controller->updateLecturerStatus(1, 'inactive', 1);

    assertSame(403, $denied->status);
    assertSame(1, $store->writes);
    assertSame(true, $store->rows[1]['active']);
});
}
