<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Permission;

use OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class BqPermissionProviderListener implements IEventListener {
    public function __construct(private BqPermissionProvider $provider) {}

    public function handle(Event $event): void {
        if ($event instanceof RegisterPermissionProvidersEvent) {
            $event->register($this->provider);
        }
    }
}
