<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IUserManager::class)) {
        interface IUserManager {
            public function userExists($uid);
        }
    }
}

namespace FlzBqPlanning\Tests {
    use DomainException;
    use OCA\FlzBqPlanning\Service\InternalPfkDirectory;
    use OCP\IGroup;
    use OCP\IGroupManager;
    use OCP\IUserManager;

    final class PfkDirectoryGroups implements IGroupManager {
        /** @param array<string,string> $members */
        public function __construct(
            public array $members = ['pfk-a' => 'PFK Alpha', 'pfk-b' => 'PFK Beta'],
            private bool $groupExists = true,
        ) {}
        public function isAdmin(string $uid): bool { return false; }
        public function isInGroup(string $uid, string $gid): bool { return $gid === 'flz-PFK' && isset($this->members[$uid]); }
        public function get(string $gid): ?IGroup { return $this->groupExists && $gid === 'flz-PFK' ? new class implements IGroup {} : null; }
        public function displayNamesInGroup($gid, $search = '', $limit = -1, $offset = 0): array {
            if ($gid !== 'flz-PFK') return [];
            $search = strtolower((string)$search);
            return array_slice(array_filter(
                $this->members,
                static fn (string $name, string $uid): bool => $search === '' || str_contains(strtolower($uid . ' ' . $name), $search),
                ARRAY_FILTER_USE_BOTH,
            ), $offset, $limit, true);
        }
    }

    final class PfkDirectoryUsers implements IUserManager {
        /** @param list<string> $existing */
        public function __construct(private array $existing = ['pfk-a', 'pfk-b', 'user-outside']) {}
        public function userExists($uid): bool { return in_array($uid, $this->existing, true); }
    }

    TestRunner::test('internal PFK eligibility always uses the semantic PFK role without contact data', static function (): void {
        $groups = new PfkDirectoryGroups();
        $directory = new InternalPfkDirectory(
            $groups,
            new PfkDirectoryUsers(),
            authorizationRolePolicy($groups),
        );

        assertSame(
            [['uid' => 'pfk-b', 'displayName' => 'PFK Beta']],
            $directory->search(' beta '),
        );
        assertSame(['uid', 'displayName'], array_keys($directory->search('pfk-a')[0]));
    });

    TestRunner::test('internal PFK eligibility fails closed for invalid UIDs or a missing semantic PFK group', static function (): void {
        $groups = new PfkDirectoryGroups();
        $users = new PfkDirectoryUsers();
        $directory = new InternalPfkDirectory($groups, $users, authorizationRolePolicy($groups));

        $directory->assertSelectable('pfk-a');
        assertThrows(static fn () => $directory->assertSelectable('missing'), DomainException::class);
        assertThrows(static fn () => $directory->assertSelectable('user-outside'), DomainException::class);

        $missingGroup = new PfkDirectoryGroups(groupExists: false);
        assertThrows(
            static fn () => (new InternalPfkDirectory($missingGroup, $users, authorizationRolePolicy($missingGroup)))->search('pfk'),
            DomainException::class,
        );
    });
}
