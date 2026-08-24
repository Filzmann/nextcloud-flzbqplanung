<?php

declare(strict_types=1);

namespace AdBqPlanning\Tests;

use InvalidArgumentException;
use OCA\AdBqPlanning\Privacy\BqPersonalDataProvider;
use OCA\AdBqPlanning\Privacy\BqPrivacyProviderListener;
use OCA\AdBqPlanning\Privacy\BqPrivacySource;
use OCA\FilzmannDataProtection\PublicApi\V1\DataSubjectRef;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;

TestRunner::test('internal BQ lecturer privacy excludes external identities and foreign teaching data', static function (): void {
    assertTrue(class_exists(BqPersonalDataProvider::class), 'BQ privacy provider is missing');
    $source = new class implements BqPrivacySource {
        public function forSubject(string $uid, int $limit): array {
            return array_slice([
                ['kind'=>'profile','id'=>1,'active'=>true,'created_at'=>'2026-08-01','updated_at'=>'2026-08-02','display_name'=>'Externe Person','email'=>'external@example.invalid'],
                ['kind'=>'lead','id'=>2,'run_id'=>10,'label'=>'BQ Herbst','starts_on'=>'2026-09-04','ends_on'=>'2026-09-14','status'=>'published','other_lecturer_uid'=>'other-person'],
                ['kind'=>'module','id'=>3,'run_id'=>10,'title'=>'Datenschutz','module_date'=>'2026-09-08','starts_at'=>'09:00','ends_at'=>'12:00','external_name'=>'Externe Person'],
                ['kind'=>'activity','id'=>4,'activity'=>'run_updated','occurred_at'=>'2026-08-03','affected_lecturer_email'=>'external@example.invalid'],
            ], 0, $limit);
        }
    };
    $provider = new BqPersonalDataProvider($source);
    $descriptor = $provider->descriptor();
    assertSame('adbqplanung', $descriptor->appId());
    assertSame('1.0', $descriptor->contractVersion());
    assertTrue($descriptor->supportsSubjectType('nextcloud-user'));

    $subject = new DataSubjectRef('nextcloud-user', 'self');
    $page = $provider->collect(new PersonalDataRequest($subject, 'de', 'access-report', 50, []));
    assertSame('complete', $page->status());
    assertSame(4, count($page->entries()));
    $json = json_encode(array_map(static fn($entry): array => [
        'category'=>$entry->categoryId(), 'reference'=>$entry->reference(), 'summary'=>$entry->summary(), 'attributes'=>$entry->attributes(),
    ], $page->entries()), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    foreach (['Internes Dozentinnenprofil','BQ Herbst','04.09.2026','Datenschutz','08.09.2026','Durchlauf bearbeitet'] as $expected) {
        assertTrue(str_contains($json, $expected), "BQ-Metadatum fehlt: {$expected}");
    }
    foreach (['Externe Person','external@example.invalid','other-person'] as $forbidden) {
        assertTrue(!str_contains($json, $forbidden), "BQ-Auskunft verrät ausgeschlossene Identität: {$forbidden}");
    }

    $limited = $provider->collect(new PersonalDataRequest($subject, 'de', 'access-report', 1, []));
    assertSame('partial', $limited->status());
    assertSame(1, count($limited->entries()));
    $unsupported = $provider->collect(new PersonalDataRequest(new DataSubjectRef('external-lecturer', 'ext:1'), 'de', 'access-report', 50, []));
    assertSame('not_applicable', $unsupported->status());
    assertSame([], $unsupported->entries());
    assertThrows(static fn() => $provider->collect((new PersonalDataRequest($subject, 'de', 'access-report', 50, ['adbqplanung'=>'opaque']))->forProvider('adbqplanung', 50)), InvalidArgumentException::class);

    $listener = new BqPrivacyProviderListener($provider);
    $event = new RegisterPersonalDataProvidersEvent();
    $listener->handle($event);
    assertSame(['adbqplanung'], array_keys($event->providers()));
    $application = (string)file_get_contents(dirname(__DIR__) . '/lib/AppInfo/Application.php');
    assertTrue(str_contains($application, 'registerEventListener(RegisterPersonalDataProvidersEvent::class, BqPrivacyProviderListener::class)'));
});
