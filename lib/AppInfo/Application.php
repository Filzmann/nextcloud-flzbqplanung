<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\AppInfo;

use OCA\FlzBqPlanning\Listener\StandaloneNavigationListener;
use OCA\FlzBqPlanning\Contract\BlockedPeriodProvider;
use OCA\FlzBqPlanning\Service\CalendarBlockedPeriodProvider;
use OCA\FlzBqPlanning\Privacy\BqPrivacyProviderListener;
use OCA\FlzBqPlanning\Privacy\BqProcessingMetadataProviderListener;
use OCA\FlzBqPlanning\Privacy\BqPrivacySource;
use OCA\FlzBqPlanning\Privacy\NextcloudBqPrivacySource;
use OCA\FlzBqPlanning\Permission\BqPermissionProviderListener;
use OCA\FlzBqPlanning\Permission\BqPermissionSourceInterface;
use OCA\FlzBqPlanning\Permission\NextcloudBqPermissionSource;
use OCA\FlzBqPlanning\Repository\TemporaryAdminAccessRepository;
use OCA\FlzBqPlanning\Repository\TemporaryAdminAccessRepositoryInterface;
use OCA\FlzBqPlanning\Service\TemporaryAdminAccessChecker;
use OCA\FlzBqPlanning\Service\TemporaryAdminAccessService;
use OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
use OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Navigation\Events\LoadAdditionalEntriesEvent;

final class Application extends App implements IBootstrap {
    public const APP_ID = 'flzbqplanung';

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
