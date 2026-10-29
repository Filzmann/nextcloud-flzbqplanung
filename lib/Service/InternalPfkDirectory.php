<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Service;

use DomainException;
use OCP\IGroupManager;
use OCP\IUserManager;

final class InternalPfkDirectory {
    private const MAX_RESULTS = 20;

    public function __construct(
        private IGroupManager $groups,
        private IUserManager $users,
        private OrganizationRolePolicy $organizationRoles,
    ) {}

    /** @return list<array{uid:string,displayName:string}> */
    public function search(string $query): array {
        $groupId = $this->pfkGroupId();
        $query = trim($query);
        if (strlen($query) > 64) {
            throw new DomainException('Die PFK-Suche ist zu lang.');
        }

        $members = $this->groups->displayNamesInGroup($groupId, $query, self::MAX_RESULTS, 0);
        $result = [];
        foreach ($members as $uid => $displayName) {
            $uid = trim((string)$uid);
            if (!preg_match('/^[A-Za-z0-9_.@-]{1,64}$/', $uid)
                || !$this->users->userExists($uid)
                || !$this->groups->isInGroup($uid, $groupId)) {
                continue;
            }
            $result[] = [
                'uid' => $uid,
                'displayName' => trim((string)$displayName) !== '' ? trim((string)$displayName) : $uid,
            ];
        }
        return $result;
    }

    public function assertSelectable(string $uid): void {
        $uid = trim($uid);
        $groupId = $this->pfkGroupId();
        if (!preg_match('/^[A-Za-z0-9_.@-]{1,64}$/', $uid)
            || !$this->users->userExists($uid)
            || !$this->groups->isInGroup($uid, $groupId)) {
            throw new DomainException('Die interne PFK muss ein vorhandenes Mitglied der Organigrammrolle Pflegefachkräfte sein.');
        }
    }

    private function pfkGroupId(): string {
        $groupId = $this->organizationRoles->groupIdForRole('pfk');
        if ($groupId === null) {
            throw new DomainException('Die Organigrammrolle Pflegefachkräfte ist derzeit nicht verfügbar.');
        }
        return $groupId;
    }
}
