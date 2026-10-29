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

namespace FlzBqPlanning\Tests {
    use OCA\FlzBqPlanning\Contract\OrganizationSnapshotSource;
    use OCA\FlzBqPlanning\Exception\AccessDeniedException;
    use OCA\FlzBqPlanning\Service\AuthorizationService;
    use OCA\FlzBqPlanning\Service\OrganizationRolePolicy;
    use OCA\FlzBqPlanning\Service\RoleSettingsService;
    use OCA\FlzBqPlanning\Service\TemporaryAdminAccessChecker;
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
        /** @param array<string,list<string>> $memberships @param list<string> $admins @param list<string> $existing */
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

    final class AuthorizationGrants implements TemporaryAdminAccessChecker {
        public function __construct(private array $active = []) {}
        public function hasActiveGrant(string $uid): bool { return in_array($uid, $this->active, true); }
    }

    function authorizationRolePolicy(IGroupManager $groups, ?array $snapshot = null): OrganizationRolePolicy {
        $snapshot ??= [
            'contractVersion' => '1.0',
            'valid' => true,
            'roles' => [
                'deputy_pdl' => ['groupId' => 'flz-StvPDL', 'label' => 'Stellvertretende Pflegedienstleitung'],
                'pdl' => ['groupId' => 'flz-PDL', 'label' => 'Pflegedienstleitung'],
                'pfk' => ['groupId' => 'flz-PFK', 'label' => 'Pflegefachkräfte'],
            ],
            'areas' => [],
        ];
        $source = new class($snapshot) implements OrganizationSnapshotSource {
            public function __construct(private ?array $snapshot) {}
            public function snapshot(): ?array { return $this->snapshot; }
        };
        return new OrganizationRolePolicy($source, $groups);
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
            'pfkPool' => false,
        ], $unassigned->capabilities());
    });

    TestRunner::test('Nextcloud admins need an active app-local grant for all four BQ capabilities', static function (): void {
        $groups = new AuthorizationGroups(admins: ['admin-a']);
        $withoutGrant = new AuthorizationService(
            new AuthorizationSession(new AuthorizationUser('admin-a')),
            $groups,
            new AuthorizationConfig(),
        );
        assertSame(false, $withoutGrant->hasAnyAccess());

        $service = new AuthorizationService(
            new AuthorizationSession(new AuthorizationUser('admin-a')),
            $groups,
            new AuthorizationConfig(),
            new AuthorizationGrants(['admin-a']),
        );
        foreach ([AuthorizationService::PLANNING, AuthorizationService::TEACHING, AuthorizationService::PUBLISHING, AuthorizationService::PFK_POOL] as $capability) {
            assertSame(true, $service->can($capability));
        }
        assertSame(true, $service->capabilities()['admin']);
    });

    TestRunner::test('deputy PDL is the default for every BQ permission while PDL and PFK can be assigned independently', static function (): void {
        $config = new AuthorizationConfig();
        $config->values = [
            'permission_roles_planning' => '["pdl","pfk"]',
            'permission_roles_teaching' => '["deputy_pdl"]',
            'permission_roles_publishing' => '["deputy_pdl"]',
            'permission_roles_pfk_pool' => '["pfk"]',
        ];
        $groups = new AuthorizationGroups(
            memberships: [
                'deputy-a' => ['flz-StvPDL'],
                'pfk-a' => ['flz-PFK'],
            ],
            existing: ['flz-StvPDL', 'flz-PDL', 'flz-PFK'],
        );
        $policy = authorizationRolePolicy($groups);

        $deputy = new AuthorizationService(new AuthorizationSession(new AuthorizationUser('deputy-a')), $groups, new AuthorizationConfig(), null, $policy);
        foreach ([AuthorizationService::PLANNING, AuthorizationService::TEACHING, AuthorizationService::PUBLISHING, AuthorizationService::PFK_POOL] as $capability) {
            assertSame(true, $deputy->can($capability));
        }

        $pfk = new AuthorizationService(new AuthorizationSession(new AuthorizationUser('pfk-a')), $groups, $config, null, $policy);
        assertSame(true, $pfk->can(AuthorizationService::PLANNING));
        assertSame(false, $pfk->can(AuthorizationService::TEACHING));
        assertSame(false, $pfk->can(AuthorizationService::PUBLISHING));
        assertSame(true, $pfk->can(AuthorizationService::PFK_POOL));
    });

    TestRunner::test('semantic permission assignments survive a current organization group rename', static function (): void {
        $config = new AuthorizationConfig();
        $groups = new AuthorizationGroups(
            memberships: ['deputy-a' => ['custom-StvPDL']],
            existing: ['custom-StvPDL', 'flz-PDL', 'flz-PFK'],
        );
        $policy = authorizationRolePolicy($groups, [
            'contractVersion' => '1.0',
            'valid' => true,
            'roles' => [
                'deputy_pdl' => ['groupId' => 'custom-StvPDL', 'label' => 'Stellvertretende Pflegedienstleitung'],
                'pdl' => ['groupId' => 'flz-PDL', 'label' => 'Pflegedienstleitung'],
                'pfk' => ['groupId' => 'flz-PFK', 'label' => 'Pflegefachkräfte'],
            ],
            'areas' => [],
        ]);
        $service = new AuthorizationService(
            new AuthorizationSession(new AuthorizationUser('deputy-a')),
            $groups,
            $config,
            null,
            $policy,
        );

        assertSame(true, $service->can(AuthorizationService::PLANNING));
        assertSame([], $config->values, 'A group rename must not rewrite the semantic AppConfig mapping.');
    });

    TestRunner::test('missing snapshots missing groups and malformed mappings fail closed', static function (): void {
        $config = new AuthorizationConfig();
        $groups = new AuthorizationGroups(
            memberships: ['deputy-a' => ['flz-StvPDL']],
            existing: ['flz-StvPDL'],
        );
        $missingSource = new class implements OrganizationSnapshotSource {
            public function snapshot(): ?array { return null; }
        };
        $service = new AuthorizationService(
            new AuthorizationSession(new AuthorizationUser('deputy-a')),
            $groups,
            $config,
            null,
            new OrganizationRolePolicy($missingSource, $groups),
        );
        assertSame(false, $service->can(AuthorizationService::PLANNING));

        $missingGroup = new AuthorizationGroups(memberships: ['deputy-a' => ['flz-StvPDL']]);
        $service = new AuthorizationService(new AuthorizationSession(new AuthorizationUser('deputy-a')), $missingGroup, $config, null, authorizationRolePolicy($missingGroup));
        assertSame(false, $service->can(AuthorizationService::PLANNING));

        $config->values['permission_roles_planning'] = '{manipulated';
        $service = new AuthorizationService(new AuthorizationSession(new AuthorizationUser('deputy-a')), $groups, $config, null, authorizationRolePolicy($groups));
        assertSame(false, $service->can(AuthorizationService::PLANNING));
        $settings = new RoleSettingsService($config, authorizationRolePolicy($groups));
        assertSame(false, $settings->selection()['complete']);
        assertTrue(str_contains($settings->selection()['message'], 'ungültig'));
    });

    TestRunner::test('denied authorization never executes the protected side effect', static function (): void {
        $service = new AuthorizationService(
            new AuthorizationSession(new AuthorizationUser('user-a')),
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

    TestRunner::test('permission role settings reject manipulated or unavailable roles without partial writes', static function (): void {
        $config = new AuthorizationConfig();
        $groups = new AuthorizationGroups(existing: ['flz-StvPDL', 'flz-PDL']);
        $settings = new RoleSettingsService($config, authorizationRolePolicy($groups));

        assertSame([
            'planningRoles' => ['deputy_pdl'],
            'teachingRoles' => ['deputy_pdl'],
            'publishingRoles' => ['deputy_pdl'],
            'pfkPoolRoles' => ['deputy_pdl'],
        ], $settings->current());

        assertThrows(
            static fn () => $settings->update(['deputy_pdl', 'finance'], ['deputy_pdl'], ['deputy_pdl'], ['deputy_pdl']),
            \DomainException::class,
        );
        assertSame(0, $config->writes);

        assertThrows(
            static fn () => $settings->update(['pfk'], ['deputy_pdl'], ['deputy_pdl'], ['deputy_pdl']),
            \DomainException::class,
        );
        assertSame(0, $config->writes, 'A missing current Nextcloud group must not leave a partial mapping.');

        $current = $settings->update([' deputy_pdl ', 'pdl'], ['deputy_pdl'], ['pdl'], []);
        assertSame([
            'planningRoles' => ['deputy_pdl', 'pdl'],
            'teachingRoles' => ['deputy_pdl'],
            'publishingRoles' => ['pdl'],
            'pfkPoolRoles' => [],
        ], $current);
        assertSame(4, $config->writes);
        assertSame('["deputy_pdl","pdl"]', $config->values['permission_roles_planning']);
    });
}
