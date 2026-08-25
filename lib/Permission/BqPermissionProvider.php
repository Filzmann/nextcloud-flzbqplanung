<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Permission;

use OCA\FilzmannPermissionMatrix\PublicApi\V1\PermissionCondition;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\PermissionProvider;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\PermissionProviderDescriptor;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\PermissionProviderResult;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\PermissionRule;

final class BqPermissionProvider implements PermissionProvider {
    private const CAPABILITIES = [
        'planning' => ['bq.planning.manage', 'BQ-Durchläufe planen', 'Planungsdaten und Curriculum-Snapshots'],
        'teaching' => ['bq.teaching.manage', 'Lehre verwalten', 'Interne und externe Dozentinnenprofile sowie Lehrzuordnungen; externe Datenschutz-Subjekte sind zurückgestellt'],
        'publishing' => ['bq.publishing.manage', 'BQ-Durchläufe veröffentlichen', 'Geprüfte Durchlaufveröffentlichung'],
    ];

    public function __construct(private BqPermissionSourceInterface $source) {}

    public function descriptor(): PermissionProviderDescriptor {
        return new PermissionProviderDescriptor('adbqplanung', 'AD BQ-Planer', '1.0', ['permissions']);
    }

    public function collect(): PermissionProviderResult {
        $rules = [];
        $temporaryAdmin = PermissionCondition::all([
            PermissionCondition::nextcloudAdmin(),
            PermissionCondition::temporaryAppAdminGrant(),
        ]);
        foreach (self::CAPABILITIES as [$permission, $label, $detail]) {
            $rules[] = $this->rule($permission, $label, $detail, 'all', $temporaryAdmin);
        }
        $rules[] = $this->rule(
            'bq.settings.manage',
            'BQ-Rollengruppen konfigurieren',
            'Native Nextcloud-Administration',
            'app-settings',
            $temporaryAdmin,
        );

        foreach ($this->source->roleGroups() as $role => $groupId) {
            $groupId = trim((string)$groupId);
            if ($groupId === '' || !isset(self::CAPABILITIES[$role])) {
                continue;
            }
            [$permission, $label, $detail] = self::CAPABILITIES[$role];
            $rules[] = $this->rule($permission, $label, $detail, 'all', PermissionCondition::group($groupId));
        }

        return new PermissionProviderResult($rules);
    }

    private function rule(
        string $permission,
        string $label,
        string $detail,
        string $scope,
        PermissionCondition $condition,
    ): PermissionRule {
        return new PermissionRule(
            'BQ-Funktion',
            $label,
            $detail,
            $permission,
            $label,
            'allow',
            $scope,
            $condition,
            'adbqplanung:AuthorizationService',
            'high',
        );
    }
}
