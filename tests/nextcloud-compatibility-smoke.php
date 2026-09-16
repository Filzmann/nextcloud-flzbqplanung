<?php

declare(strict_types=1);

use OCA\AdBqPlanning\Service\AuthorizationService;
use OCA\AdBqPlanning\Service\TemporaryAdminAccessService;

return [
    'providerRegistrations' => [
        'filzmann_data_protection' => [
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent::class,
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent::class,
        ],
        'filzmann_permission_matrix' => [
            OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent::class,
        ],
    ],
    'uiPath' => '/index.php/apps/adbqplanung/',
    'preGrantUiStatuses' => [403],
    'postGrantUiStatuses' => [200],
    'grantService' => TemporaryAdminAccessService::class,
    'permissionProbe' => static fn(string $uid): bool => OCP\Server::get(AuthorizationService::class)
        ->can(AuthorizationService::ADMIN),
    'apiSmokes' => [
        ['/index.php/apps/adbqplanung/api/runs', [200]],
    ],
];
