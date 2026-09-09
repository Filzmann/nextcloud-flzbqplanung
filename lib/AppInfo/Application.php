<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\AppInfo;

use OCA\AdBqPlanning\Listener\StandaloneNavigationListener;
use OCA\AdBqPlanning\Contract\BlockedPeriodProvider;
use OCA\AdBqPlanning\Service\CalendarBlockedPeriodProvider;
use OCA\AdBqPlanning\Privacy\BqPrivacyProviderListener;
use OCA\AdBqPlanning\Privacy\BqProcessingMetadataProviderListener;
use OCA\AdBqPlanning\Privacy\BqPrivacySource;
use OCA\AdBqPlanning\Privacy\NextcloudBqPrivacySource;
use OCA\AdBqPlanning\Permission\BqPermissionProviderListener;
use OCA\AdBqPlanning\Permission\BqPermissionSourceInterface;
use OCA\AdBqPlanning\Permission\NextcloudBqPermissionSource;
use OCA\AdBqPlanning\Repository\TemporaryAdminAccessRepository;
use OCA\AdBqPlanning\Repository\TemporaryAdminAccessRepositoryInterface;
use OCA\AdBqPlanning\Service\TemporaryAdminAccessChecker;
use OCA\AdBqPlanning\Service\TemporaryAdminAccessService;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
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
        $context->registerEventListener(RegisterProcessingMetadataProvidersEvent::class, BqProcessingMetadataProviderListener::class);
        $context->registerEventListener(RegisterPermissionProvidersEvent::class, BqPermissionProviderListener::class);
        $context->registerServiceAlias(BlockedPeriodProvider::class, CalendarBlockedPeriodProvider::class);
        $context->registerServiceAlias(BqPrivacySource::class, NextcloudBqPrivacySource::class);
        $context->registerServiceAlias(BqPermissionSourceInterface::class, NextcloudBqPermissionSource::class);
        $context->registerServiceAlias(TemporaryAdminAccessChecker::class, TemporaryAdminAccessService::class);
        $context->registerServiceAlias(TemporaryAdminAccessRepositoryInterface::class, TemporaryAdminAccessRepository::class);
    }

    public function boot(IBootContext $context): void {
    }
}
