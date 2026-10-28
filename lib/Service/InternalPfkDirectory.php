<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Service;

use DomainException;
use OCA\FlzBqPlanning\AppInfo\Application;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUserManager;

final class InternalPfkDirectory {
    private const MAX_RESULTS = 20;

    public function __construct(
        private IAppConfig $config,
        private IGroupManager $groups,
        private IUserManager $users,
    ) {
    }

    /** @return list<array{uid:string,displayName:string}> */
    public function search(string $query): array {
        $groupId = $this->configuredGroupId();
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
        $groupId = $this->configuredGroupId();
        if (!preg_match('/^[A-Za-z0-9_.@-]{1,64}$/', $uid)
            || !$this->users->userExists($uid)
            || !$this->groups->isInGroup($uid, $groupId)) {
            throw new DomainException('Die interne PFK muss ein vorhandenes Mitglied der konfigurierten PFK-Gruppe sein.');
        }
    }

    private function configuredGroupId(): string {
        $groupId = trim($this->config->getValueString(Application::APP_ID, 'role_group_pfk', ''));
        if ($groupId === '' || $this->groups->get($groupId) === null) {
            throw new DomainException('Für interne PFKs ist keine gültige Nextcloud-Gruppe konfiguriert.');
        }
        return $groupId;
    }
}
