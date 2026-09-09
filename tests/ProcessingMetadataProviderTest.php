<?php

declare(strict_types=1);

namespace AdBqPlanning\Tests;

use OCA\AdBqPlanning\Privacy\BqProcessingMetadataProvider;
use OCA\AdBqPlanning\Privacy\BqProcessingMetadataProviderListener;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
use OCP\EventDispatcher\Event;

TestRunner::test('BQ processing metadata publishes only the stable app-owned catalog', static function (): void {
    $provider = new BqProcessingMetadataProvider();
    $catalog = $provider->catalog();
    $descriptor = $provider->descriptor();

    assertSame('adbqplanung', $descriptor->appId());
    assertSame('AD BQ-Planer', $descriptor->displayName());
    assertSame('1.0', $descriptor->contractVersion());
    assertSame('adbqplanung', $catalog->appId());
    assertSame([
        'bq_run_curriculum_and_schedule_management',
        'lecturer_profile_and_assignment_management',
        'external_lecturer_request_tracking',
        'temporary_admin_full_access',
    ], $catalog->processingIds());
    assertTrue(!array_key_exists('personal_runtime_data', $catalog->toArray()));

    $registration = new RegisterProcessingMetadataProvidersEvent();
    $listener = new BqProcessingMetadataProviderListener($provider);
    $listener->handle(new Event());
    assertSame([], $registration->providers());
    $listener->handle($registration);
    assertSame(['adbqplanung'], array_keys($registration->providers()));

    $application = (string)file_get_contents(dirname(__DIR__) . '/lib/AppInfo/Application.php');
    assertTrue(str_contains($application, 'registerEventListener(RegisterProcessingMetadataProvidersEvent::class, BqProcessingMetadataProviderListener::class)'));
});
