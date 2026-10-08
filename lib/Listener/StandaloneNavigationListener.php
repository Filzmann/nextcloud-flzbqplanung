<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Listener;

use OCA\FlzBqPlanning\Service\AuthorizationService;
use OCA\LocalBase\Service\StandaloneAppNavigationService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Navigation\Events\LoadAdditionalEntriesEvent;

/** @template-implements IEventListener<LoadAdditionalEntriesEvent> */
final class StandaloneNavigationListener implements IEventListener {
    public function __construct(
        private StandaloneAppNavigationService $navigation,
        private AuthorizationService $authorization,
    ) {
    }

    public function handle(Event $event): void {
        if (!$event instanceof LoadAdditionalEntriesEvent) {
            return;
        }
        if (!$this->authorization->hasAnyAccess()) {
            return;
        }

        $this->navigation->addCatalogProductWhenStandalone('flzbqplanung', 'BQ-Planer', 'app.svg');
    }
}
