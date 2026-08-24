<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\AppInfo;

use OCA\AdBqPlanning\Listener\StandaloneNavigationListener;
use OCA\AdBqPlanning\Contract\BlockedPeriodProvider;
use OCA\AdBqPlanning\Service\CalendarBlockedPeriodProvider;
use OCA\AdBqPlanning\Privacy\BqPrivacyProviderListener;
use OCA\AdBqPlanning\Privacy\BqPrivacySource;
use OCA\AdBqPlanning\Privacy\NextcloudBqPrivacySource;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
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
        $context->registerEventListener(RegisterPersonalDataProvidersEvent::class, BqPrivacyProviderListener::class);
        $context->registerServiceAlias(BlockedPeriodProvider::class, CalendarBlockedPeriodProvider::class);
        $context->registerServiceAlias(BqPrivacySource::class, NextcloudBqPrivacySource::class);
    }

    public function boot(IBootContext $context): void {
    }
}
