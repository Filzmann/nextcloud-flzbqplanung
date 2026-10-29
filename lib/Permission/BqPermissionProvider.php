<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Permission;

use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionCondition;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionProvider;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionProviderDescriptor;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionProviderResult;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionRule;

final class BqPermissionProvider implements PermissionProvider {
    private const CAPABILITIES = [
        'planning' => ['bq.planning.manage', 'BQ-Durchläufe planen', 'Planungsdaten und Curriculum-Snapshots'],
        'teaching' => ['bq.teaching.manage', 'Lehre verwalten', 'Interne und externe Dozentinnenprofile sowie Lehrzuordnungen; externe Datenschutz-Subjekte sind zurückgestellt'],
        'publishing' => ['bq.publishing.manage', 'BQ-Durchläufe veröffentlichen', 'Geprüfte Durchlaufveröffentlichung'],
        'pfkPool' => ['bq.pfk_pool.manage', 'Internen PFK-Pool verwalten', 'Interne PFK-Profile aus der Organigrammrolle Pflegefachkräfte; keine externen Dozentinnendaten'],
    ];

    public function __construct(private BqPermissionSourceInterface $source) {}

    public function descriptor(): PermissionProviderDescriptor {
        return new PermissionProviderDescriptor('flzbqplanung', 'Filzmann BQ-Planer', '1.0', ['permissions']);
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
            'BQ-Berechtigungen konfigurieren',
            'Native Nextcloud-Administration',
            'app-settings',
            $temporaryAdmin,
        );
        $rules[] = $this->rule(
            'bq.admin_access.manage',
            'Admin-Vollzugriff verwalten',
            'Historie lesen sowie Freigaben ausschließlich für aktuelle native Administrationskonten erteilen oder widerrufen',
            'all',
            PermissionCondition::group('Datenschutzbeauftragte'),
            'flzbqplanung:TemporaryAdminAccessService',
        );

        foreach ($this->source->permissionRoleGroups() as $capability => $groupIds) {
            if (!isset(self::CAPABILITIES[$capability])) {
                continue;
            }
            [$permission, $label, $detail] = self::CAPABILITIES[$capability];
            foreach ($groupIds as $groupId) {
                $groupId = trim((string)$groupId);
                if ($groupId !== '') {
                    $rules[] = $this->rule($permission, $label, $detail, 'all', PermissionCondition::group($groupId));
                }
            }
        }

        return new PermissionProviderResult($rules, $this->source->complete(), $this->source->warnings());
    }

    private function rule(
        string $permission,
        string $label,
        string $detail,
        string $scope,
        PermissionCondition $condition,
        string $source = 'flzbqplanung:AuthorizationService',
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
            $source,
            'high',
        );
    }
}
