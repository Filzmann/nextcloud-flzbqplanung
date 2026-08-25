<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Service;

use OCA\AdBqPlanning\AppInfo\Application;
use OCA\AdBqPlanning\Exception\AccessDeniedException;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUserSession;

final class AuthorizationService {
    public const ADMIN = 'admin';
    public const PLANNING = 'planning';
    public const TEACHING = 'teaching';
    public const PUBLISHING = 'publishing';

    private const GROUP_KEYS = [
        self::PLANNING => 'role_group_planning',
        self::TEACHING => 'role_group_teaching',
        self::PUBLISHING => 'role_group_publishing',
    ];

    public function __construct(
        private IUserSession $session,
        private IGroupManager $groups,
        private IAppConfig $config,
        private ?TemporaryAdminAccessChecker $temporaryAdminAccess = null,
    ) {
    }

    public function actorUid(): string {
        $uid = trim((string)$this->session->getUser()?->getUID());
        if ($uid === '') {
            throw new AccessDeniedException();
        }
        return $uid;
    }

    public function isAdmin(): bool {
        try {
            $uid = $this->actorUid();
            return $this->groups->isAdmin($uid)
                && ($this->temporaryAdminAccess?->hasActiveGrant($uid) ?? false);
        } catch (AccessDeniedException) {
            return false;
        }
    }

    public function can(string $capability): bool {
        if ($capability === self::ADMIN) {
            return $this->isAdmin();
        }
        if (!isset(self::GROUP_KEYS[$capability])) {
            return false;
        }
        try {
            $uid = $this->actorUid();
        } catch (AccessDeniedException) {
            return false;
        }
        if ($this->groups->isAdmin($uid) && ($this->temporaryAdminAccess?->hasActiveGrant($uid) ?? false)) {
            return true;
        }
        $groupId = trim($this->config->getValueString(
            Application::APP_ID,
            self::GROUP_KEYS[$capability],
            '',
        ));
        return $groupId !== '' && $this->groups->isInGroup($uid, $groupId);
    }

    public function hasAnyAccess(): bool {
        return $this->isAdmin()
            || $this->can(self::PLANNING)
            || $this->can(self::TEACHING)
            || $this->can(self::PUBLISHING);
    }

    /** @return array{admin:bool,planning:bool,teaching:bool,publishing:bool} */
    public function capabilities(): array {
        return [
            'admin' => $this->isAdmin(),
            'planning' => $this->can(self::PLANNING),
            'teaching' => $this->can(self::TEACHING),
            'publishing' => $this->can(self::PUBLISHING),
        ];
    }

    public function requireAnyAccess(): void {
        if (!$this->hasAnyAccess()) {
            throw new AccessDeniedException();
        }
    }

    public function requireAdmin(): void {
        if (!$this->isAdmin()) {
            throw new AccessDeniedException();
        }
    }

    public function requireCapability(string $capability): void {
        if (!$this->can($capability)) {
            throw new AccessDeniedException();
        }
    }

    public function execute(?string $capability, callable $callback): mixed {
        if ($capability === null) {
            $this->requireAnyAccess();
        } else {
            $this->requireCapability($capability);
        }
        return $callback();
    }
}
