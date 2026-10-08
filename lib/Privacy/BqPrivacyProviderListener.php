<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Privacy;

use OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class BqPrivacyProviderListener implements IEventListener {
    public function __construct(private BqPersonalDataProvider $provider) {}
    public function handle(Event $event): void { if ($event instanceof RegisterPersonalDataProvidersEvent) $event->register($this->provider); }
}
