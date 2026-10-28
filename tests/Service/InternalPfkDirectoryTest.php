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
    use OCP\IAppConfig;
    use OCP\IGroup;
    use OCP\IGroupManager;
    use OCP\IUserManager;

    final class PfkDirectoryConfig implements IAppConfig {
        public function __construct(public string $groupId = 'bq-pfk') {}
        public function getValueInt(string $appId, string $key, int $default): int { return $default; }
        public function getValueString(string $appId, string $key, string $default): string {
            return $key === 'role_group_pfk' ? $this->groupId : $default;
        }
        public function setValueInt(string $appId, string $key, int $value): void {}
        public function setValueString(string $appId, string $key, string $value): void {}
    }

    final class PfkDirectoryGroups implements IGroupManager {
        /** @param array<string,string> $members */
        public function __construct(public array $members = ['pfk-a' => 'PFK Alpha', 'pfk-b' => 'PFK Beta']) {}
        public function isAdmin(string $uid): bool { return false; }
        public function isInGroup(string $uid, string $gid): bool { return $gid === 'bq-pfk' && isset($this->members[$uid]); }
        public function get(string $gid): ?IGroup { return $gid === 'bq-pfk' ? new class implements IGroup {} : null; }
        public function displayNamesInGroup($gid, $search = '', $limit = -1, $offset = 0): array {
            if ($gid !== 'bq-pfk') return [];
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

    TestRunner::test('internal PFK directory searches only configured group members without contact data', static function (): void {
        $directory = new InternalPfkDirectory(new PfkDirectoryConfig(), new PfkDirectoryGroups(), new PfkDirectoryUsers());

        assertSame(
            [['uid' => 'pfk-b', 'displayName' => 'PFK Beta']],
            $directory->search(' beta '),
        );
        assertSame(['uid', 'displayName'], array_keys($directory->search('pfk-a')[0]));
    });

    TestRunner::test('internal PFK directory rejects unconfigured unknown and out-of-group UIDs', static function (): void {
        $groups = new PfkDirectoryGroups();
        $users = new PfkDirectoryUsers();
        $directory = new InternalPfkDirectory(new PfkDirectoryConfig(), $groups, $users);

        $directory->assertSelectable('pfk-a');
        assertThrows(static fn () => $directory->assertSelectable('missing'), DomainException::class);
        assertThrows(static fn () => $directory->assertSelectable('user-outside'), DomainException::class);
        assertThrows(
            static fn () => (new InternalPfkDirectory(new PfkDirectoryConfig(''), $groups, $users))->search('pfk'),
            DomainException::class,
        );
    });
}
