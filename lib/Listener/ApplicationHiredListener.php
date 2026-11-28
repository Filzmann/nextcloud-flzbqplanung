<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Listener;

use OCA\FlzBqPlanning\Service\BqHiringEventInboxService;
use OCA\FlzRecruitment\PublicApi\V1\ApplicationHiredEvent;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Optionaler Consumer des ZM-07-Minimal-Vertrags: ohne installiertes und
 * aktiviertes flzrecruitment ist der Listener ein No-Op (gültiger
 * Standalone-Zustand).
 *
 * @template-implements IEventListener<ApplicationHiredEvent>
 */
final class ApplicationHiredListener implements IEventListener {
    private const PROVIDER_APP_ID = 'flzrecruitment';

    public function __construct(
        private IAppManager $appManager,
        private BqHiringEventInboxService $inbox,
    ) {
    }

    public function handle(Event $event): void {
        if (!$event instanceof ApplicationHiredEvent) {
            return;
        }
        if (!$this->appManager->isEnabledForUser(self::PROVIDER_APP_ID)) {
            return;
        }
        $this->inbox->record(
            $event->applicationId(),
            $event->areaKey(),
            $event->occurredAt()->format('c'),
        );
    }
}
