<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IUser::class)) {
        interface IUser { public function getUID(): string; }
    }
    if (!interface_exists(IUserSession::class)) {
        interface IUserSession { public function getUser(): ?IUser; }
    }
    if (!interface_exists(IGroup::class)) {
        interface IGroup {}
    }
    if (!interface_exists(IGroupManager::class)) {
        interface IGroupManager {
            public function isAdmin(string $uid): bool;
            public function isInGroup(string $uid, string $gid): bool;
            public function get(string $gid): ?IGroup;
        }
    }
}

namespace AdBqPlanning\Tests {
    use OCA\AdBqPlanning\Exception\AccessDeniedException;
    use OCA\AdBqPlanning\Service\AuthorizationService;
    use OCA\AdBqPlanning\Service\RoleSettingsService;
    use OCP\IAppConfig;
    use OCP\IGroup;
    use OCP\IGroupManager;
    use OCP\IUser;
    use OCP\IUserSession;

    final class AuthorizationUser implements IUser {
        public function __construct(private string $uid) {}
        public function getUID(): string { return $this->uid; }
    }

    final class AuthorizationSession implements IUserSession {
        public function __construct(public ?IUser $user) {}
        public function getUser(): ?IUser { return $this->user; }
    }

    final class AuthorizationGroups implements IGroupManager {
        /** @param list<string> $existing */
        public function __construct(
            public array $memberships = [],
            public array $admins = [],
            public array $existing = [],
        ) {}
        public function isAdmin(string $uid): bool { return in_array($uid, $this->admins, true); }
        public function isInGroup(string $uid, string $gid): bool { return in_array($gid, $this->memberships[$uid] ?? [], true); }
        public function get(string $gid): ?IGroup {
            return in_array($gid, $this->existing, true) ? new class implements IGroup {} : null;
        }
    }

    final class AuthorizationConfig implements IAppConfig {
        public array $values = [];
        public int $writes = 0;
        public function getValueInt(string $appId, string $key, int $default): int { return $default; }
        public function getValueString(string $appId, string $key, string $default): string { return (string)($this->values[$key] ?? $default); }
        public function setValueInt(string $appId, string $key, int $value): void {}
        public function setValueString(string $appId, string $key, string $value): void {
            $this->values[$key] = $value;
            $this->writes++;
        }
    }

    TestRunner::test('authorization denies anonymous and unassigned users by default', static function (): void {
        $config = new AuthorizationConfig();
        $groups = new AuthorizationGroups();
        $anonymous = new AuthorizationService(new AuthorizationSession(null), $groups, $config);
        assertSame(false, $anonymous->hasAnyAccess());
        assertThrows(static fn () => $anonymous->requireCapability(AuthorizationService::PLANNING), AccessDeniedException::class);

        $unassigned = new AuthorizationService(new AuthorizationSession(new AuthorizationUser('user-a')), $groups, $config);
        assertSame(false, $unassigned->hasAnyAccess());
        assertSame([
            'admin' => false,
            'planning' => false,
            'teaching' => false,
            'publishing' => false,
        ], $unassigned->capabilities());
    });

    TestRunner::test('Nextcloud admins retain all BQ capabilities without configured groups', static function (): void {
        $service = new AuthorizationService(
            new AuthorizationSession(new AuthorizationUser('admin-a')),
            new AuthorizationGroups(admins: ['admin-a']),
            new AuthorizationConfig(),
        );
        assertSame(true, $service->hasAnyAccess());
        foreach ([AuthorizationService::PLANNING, AuthorizationService::TEACHING, AuthorizationService::PUBLISHING] as $capability) {
            assertSame(true, $service->can($capability));
        }
        assertSame(true, $service->capabilities()['admin']);
    });

    TestRunner::test('configured Nextcloud groups grant only their matching BQ capability', static function (): void {
        $config = new AuthorizationConfig();
        $config->values = [
            'role_group_planning' => 'bq-planning',
            'role_group_teaching' => 'bq-teaching',
            'role_group_publishing' => 'bq-publishing',
        ];
        $groups = new AuthorizationGroups(memberships: ['planner-a' => ['bq-planning']]);
        $service = new AuthorizationService(new AuthorizationSession(new AuthorizationUser('planner-a')), $groups, $config);

        assertSame(true, $service->can(AuthorizationService::PLANNING));
        assertSame(false, $service->can(AuthorizationService::TEACHING));
        assertSame(false, $service->can(AuthorizationService::PUBLISHING));
        assertThrows(static fn () => $service->requireCapability(AuthorizationService::TEACHING), AccessDeniedException::class);
    });

    TestRunner::test('denied authorization never executes the protected side effect', static function (): void {
        $service = new AuthorizationService(
            new AuthorizationSession(new AuthorizationUser('planner-a')),
            new AuthorizationGroups(),
            new AuthorizationConfig(),
        );
        $writes = 0;
        assertThrows(
            static function () use ($service, &$writes): void {
                $service->execute(AuthorizationService::PLANNING, static function () use (&$writes): void { $writes++; });
            },
            AccessDeniedException::class,
        );
        assertSame(0, $writes);
    });

    TestRunner::test('role settings reject unknown groups without partial AppConfig writes', static function (): void {
        $config = new AuthorizationConfig();
        $groups = new AuthorizationGroups(existing: ['bq-planning', 'bq-publishing']);
        $settings = new RoleSettingsService($config, $groups);

        assertThrows(
            static fn () => $settings->update('bq-planning', 'missing-group', 'bq-publishing'),
            \DomainException::class,
        );
        assertSame(0, $config->writes);

        $current = $settings->update(' bq-planning ', '', 'bq-publishing');
        assertSame([
            'planningGroup' => 'bq-planning',
            'teachingGroup' => '',
            'publishingGroup' => 'bq-publishing',
        ], $current);
        assertSame(3, $config->writes);
    });
}
