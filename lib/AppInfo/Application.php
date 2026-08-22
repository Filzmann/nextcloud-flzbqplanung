<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\AppInfo;

use OCA\AdBqPlanning\Listener\StandaloneNavigationListener;
use OCA\AdBqPlanning\Contract\BlockedPeriodProvider;
use OCA\AdBqPlanning\Service\CalendarBlockedPeriodProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Navigation\Events\LoadAdditionalEntriesEvent;

final class Application extends App implements IBootstrap {
    public const APP_ID = 'adbqplanung';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(LoadAdditionalEntriesEvent::class, StandaloneNavigationListener::class);
        $context->registerServiceAlias(BlockedPeriodProvider::class, CalendarBlockedPeriodProvider::class);
    }

    public function boot(IBootContext $context): void {
    }
}
