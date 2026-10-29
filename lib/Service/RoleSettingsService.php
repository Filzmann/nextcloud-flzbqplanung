<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Service;

use DomainException;
use JsonException;
use OCA\FlzBqPlanning\AppInfo\Application;
use OCP\IAppConfig;

final class RoleSettingsService {
    public const CONFIG_KEYS = [
        'planning' => 'permission_roles_planning',
        'teaching' => 'permission_roles_teaching',
        'publishing' => 'permission_roles_publishing',
        'pfkPool' => 'permission_roles_pfk_pool',
    ];

    private const RESPONSE_KEYS = [
        'planning' => 'planningRoles',
        'teaching' => 'teachingRoles',
        'publishing' => 'publishingRoles',
        'pfkPool' => 'pfkPoolRoles',
    ];

    public const DEFAULT_ROLES = ['deputy_pdl'];

    public function __construct(
        private IAppConfig $config,
        private OrganizationRolePolicy $organizationRoles,
    ) {}

    /** @return array{planningRoles:list<string>,teachingRoles:list<string>,publishingRoles:list<string>,pfkPoolRoles:list<string>} */
    public function current(): array {
        $result = [];
        foreach (self::CONFIG_KEYS as $capability => $configKey) {
            $result[self::RESPONSE_KEYS[$capability]] = self::decodeRoles(
                $this->config->getValueString(
                    Application::APP_ID,
                    $configKey,
                    self::defaultStoredRoles(),
                ),
            );
        }
        return $result;
    }

    /** @return list<string> */
    public function rolesForCapability(string $capability): array {
        if (!isset(self::CONFIG_KEYS[$capability])) {
            return [];
        }
        return self::decodeRoles($this->config->getValueString(
            Application::APP_ID,
            self::CONFIG_KEYS[$capability],
            self::defaultStoredRoles(),
        ));
    }

    /** @return array{available:bool,complete:bool,message:string,options:list<array{roleKey:string,label:string,groupId:string,exists:bool}>} */
    public function selection(): array {
        $state = $this->organizationRoles->state();
        $complete = $state['available'];
        $invalidStoredMapping = false;
        foreach (self::CONFIG_KEYS as $configKey) {
            $stored = $this->config->getValueString(
                Application::APP_ID,
                $configKey,
                self::encodeRoles(self::DEFAULT_ROLES),
            );
            if (!self::isValidStoredRoles($stored)) {
                $complete = false;
                $invalidStoredMapping = true;
            }
        }
        if ($complete) {
            $selected = array_values(array_unique(array_merge(...array_values($this->current()))));
            foreach ($selected as $roleKey) {
                if ($this->organizationRoles->groupIdForRole($roleKey) === null) {
                    $complete = false;
                    break;
                }
            }
        }
        $message = $state['message'];
        if ($invalidStoredMapping) {
            $message = 'Mindestens eine gespeicherte BQ-Berechtigungszuordnung ist ungültig. Die betroffene Berechtigung bleibt deaktiviert.';
        } elseif ($state['available'] && !$complete) {
            $message = 'Mindestens eine zugewiesene Organigrammrolle besitzt derzeit keine vorhandene Nextcloud-Gruppe. Die betroffene Berechtigung bleibt deaktiviert.';
        }
        return [...$state, 'complete' => $complete, 'message' => $message];
    }

    /** @return array<string,list<string>> */
    public function effectiveGroups(): array {
        $result = [];
        foreach (array_keys(self::CONFIG_KEYS) as $capability) {
            $groups = [];
            foreach ($this->rolesForCapability($capability) as $roleKey) {
                $groupId = $this->organizationRoles->groupIdForRole($roleKey);
                if ($groupId !== null) {
                    $groups[$groupId] = true;
                }
            }
            $result[$capability] = array_keys($groups);
        }
        return $result;
    }

    /** @return array{planningRoles:list<string>,teachingRoles:list<string>,publishingRoles:list<string>,pfkPoolRoles:list<string>} */
    public function update(array $planningRoles, array $teachingRoles, array $publishingRoles, array $pfkPoolRoles): array {
        $values = [
            'planning' => $this->validateRoles($planningRoles),
            'teaching' => $this->validateRoles($teachingRoles),
            'publishing' => $this->validateRoles($publishingRoles),
            'pfkPool' => $this->validateRoles($pfkPoolRoles),
        ];

        foreach ($values as $capability => $roles) {
            $this->config->setValueString(
                Application::APP_ID,
                self::CONFIG_KEYS[$capability],
                self::encodeRoles($roles),
            );
        }
        return $this->current();
    }

    /** @return list<string> */
    private function validateRoles(array $roles): array {
        $validated = [];
        foreach ($roles as $roleKey) {
            if (!is_string($roleKey)) {
                throw new DomainException('BQ-Berechtigungen dürfen nur Organigrammrollen enthalten.');
            }
            $roleKey = trim($roleKey);
            if ($roleKey === '' || !in_array($roleKey, OrganizationRolePolicy::ASSIGNABLE_ROLE_KEYS, true)) {
                throw new DomainException('Eine BQ-Berechtigung enthält eine unbekannte Organigrammrolle.');
            }
            if (!$this->organizationRoles->isSelectableRole($roleKey)) {
                throw new DomainException('Die gewählte Organigrammrolle besitzt derzeit keine vorhandene Nextcloud-Gruppe.');
            }
            $validated[$roleKey] = true;
        }
        $result = array_keys($validated);
        usort($result, static fn(string $left, string $right): int => array_search($left, OrganizationRolePolicy::ASSIGNABLE_ROLE_KEYS, true) <=> array_search($right, OrganizationRolePolicy::ASSIGNABLE_ROLE_KEYS, true));
        return $result;
    }

    /** @return list<string> */
    public static function decodeRoles(string $stored): array {
        try {
            $decoded = json_decode($stored, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
        if (!is_array($decoded) || !array_is_list($decoded)) {
            return [];
        }
        $roles = [];
        foreach ($decoded as $roleKey) {
            if (!is_string($roleKey) || !in_array($roleKey, OrganizationRolePolicy::ASSIGNABLE_ROLE_KEYS, true)) {
                return [];
            }
            $roles[$roleKey] = true;
        }
        $result = array_keys($roles);
        usort($result, static fn(string $left, string $right): int => array_search($left, OrganizationRolePolicy::ASSIGNABLE_ROLE_KEYS, true) <=> array_search($right, OrganizationRolePolicy::ASSIGNABLE_ROLE_KEYS, true));
        return $result;
    }

    public static function defaultStoredRoles(): string {
        return self::encodeRoles(self::DEFAULT_ROLES);
    }

    private static function isValidStoredRoles(string $stored): bool {
        try {
            $decoded = json_decode($stored, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }
        if (!is_array($decoded) || !array_is_list($decoded)) {
            return false;
        }
        foreach ($decoded as $roleKey) {
            if (!is_string($roleKey) || !in_array($roleKey, OrganizationRolePolicy::ASSIGNABLE_ROLE_KEYS, true)) {
                return false;
            }
        }
        return true;
    }

    /** @param list<string> $roles */
    private static function encodeRoles(array $roles): string {
        return json_encode(array_values($roles), JSON_THROW_ON_ERROR);
    }
}
