<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Permission;

use OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;

final class BqPermissionProviderListener {
    public function __construct(private BqPermissionProvider $provider) {}

    public function handle(object $event): void {
        if ($event instanceof RegisterPermissionProvidersEvent) {
            $event->register($this->provider);
        }
    }
}
