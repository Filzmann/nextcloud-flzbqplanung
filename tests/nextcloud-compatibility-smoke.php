<?php

declare(strict_types=1);

use OCA\AdBqPlanning\Service\AuthorizationService;
use OCA\AdBqPlanning\Service\TemporaryAdminAccessService;

return [
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
