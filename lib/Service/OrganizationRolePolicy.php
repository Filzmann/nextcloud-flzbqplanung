<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Service;

use OCA\FlzBqPlanning\Contract\OrganizationSnapshotSource;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;
use Throwable;

final class OrganizationRolePolicy {
    public const ASSIGNABLE_ROLE_KEYS = ['deputy_pdl', 'pdl', 'pfk'];

    /** @var array{available:bool,message:string,options:list<array{roleKey:string,label:string,groupId:string,exists:bool}>}|null */
    private ?array $cachedState = null;

    public function __construct(
        private OrganizationSnapshotSource $source,
        private IGroupManager $groups,
        private ?LoggerInterface $logger = null,
    ) {}

    /** @return array{available:bool,message:string,options:list<array{roleKey:string,label:string,groupId:string,exists:bool}>} */
    public function state(): array {
        if ($this->cachedState !== null) {
            return $this->cachedState;
        }
        try {
            $snapshot = $this->source->snapshot();
        } catch (Throwable $error) {
            return $this->providerFailure($error);
        }
        if ($snapshot === null) {
            return $this->unavailable('Die Organisations-App stellt derzeit kein Organigramm bereit. BQ-Berechtigungen bleiben deaktiviert.');
        }
        if (($snapshot['contractVersion'] ?? null) !== '1.0' || ($snapshot['valid'] ?? null) !== true) {
            return $this->unavailable('Das Organigramm ist ungültig oder inkompatibel. BQ-Berechtigungen bleiben deaktiviert.');
        }
        if (!is_array($snapshot['roles'] ?? null)) {
            return $this->unavailable('Das Organigramm enthält keine verwendbaren Rollen. BQ-Berechtigungen bleiben deaktiviert.');
        }

        try {
            $options = [];
            foreach (self::ASSIGNABLE_ROLE_KEYS as $roleKey) {
                $role = $snapshot['roles'][$roleKey] ?? null;
                $groupId = is_array($role) ? trim((string)($role['groupId'] ?? '')) : '';
                $label = is_array($role) ? trim((string)($role['label'] ?? '')) : '';
                if ($groupId === '' || $label === '') {
                    return $this->unavailable('Das Organigramm enthält nicht alle für den BQ-Planer erforderlichen Rollen. BQ-Berechtigungen bleiben deaktiviert.');
                }
                $options[] = [
                    'roleKey' => $roleKey,
                    'label' => $label,
                    'groupId' => $groupId,
                    'exists' => $this->groups->get($groupId) !== null,
                ];
            }
        } catch (Throwable $error) {
            return $this->providerFailure($error);
        }

        return $this->cachedState = ['available' => true, 'message' => '', 'options' => $options];
    }

    public function groupIdForRole(string $roleKey): ?string {
        $roleKey = trim($roleKey);
        $state = $this->state();
        if (!$state['available']) {
            return null;
        }
        foreach ($state['options'] as $option) {
            if ($option['roleKey'] === $roleKey && $option['exists']) {
                return $option['groupId'];
            }
        }
        return null;
    }

    public function isSelectableRole(string $roleKey): bool {
        return $this->groupIdForRole($roleKey) !== null;
    }

    /** @return array{available:false,message:string,options:array{}} */
    private function unavailable(string $message): array {
        return $this->cachedState = ['available' => false, 'message' => $message, 'options' => []];
    }

    /** @return array{available:false,message:string,options:array{}} */
    private function providerFailure(Throwable $error): array {
        $this->logger?->warning('BQ-Organigrammrollen konnten nicht aufgelöst werden.', [
            'exceptionClass' => $error::class,
        ]);
        return $this->unavailable('Das Organigramm konnte nicht gelesen werden. BQ-Berechtigungen bleiben deaktiviert.');
    }
}
