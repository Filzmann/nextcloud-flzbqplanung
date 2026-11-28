<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IAppConfig::class)) {
        interface IAppConfig {
            public function getValueInt(string $appId, string $key, int $default): int;
            public function getValueString(string $appId, string $key, string $default): string;
            public function setValueInt(string $appId, string $key, int $value): void;
            public function setValueString(string $appId, string $key, string $value): void;
        }
    }
}

namespace OCP\App {
    if (!interface_exists(IAppManager::class)) {
        interface IAppManager {
            public function isEnabledForUser($appId, $user = null): bool;
        }
    }
}

namespace OCP\EventDispatcher {
    if (!class_exists(Event::class)) {
        class Event {
            public function __construct() {
            }
        }
    }
    if (!interface_exists(IEventListener::class)) {
        interface IEventListener {
            public function handle(Event $event): void;
        }
    }
}

namespace FlzBqPlanning\Tests {
    use DateTimeImmutable;
    use OCA\FlzBqPlanning\Listener\ApplicationHiredListener;
    use OCA\FlzBqPlanning\Service\BqHiringEventInboxService;
    use OCA\FlzRecruitment\PublicApi\V1\ApplicationHiredEvent;
    use OCP\App\IAppManager;
    use OCP\EventDispatcher\Event;
    use OCP\IAppConfig;

    final class FakeAppConfig implements IAppConfig {
        /** @var array<string,string> */
        public array $values = [];

        public function getValueInt(string $appId, string $key, int $default): int {
            return (int) ($this->values[$appId . '/' . $key] ?? (string) $default);
        }

        public function getValueString(string $appId, string $key, string $default): string {
            return $this->values[$appId . '/' . $key] ?? $default;
        }

        public function setValueInt(string $appId, string $key, int $value): void {
            $this->values[$appId . '/' . $key] = (string) $value;
        }

        public function setValueString(string $appId, string $key, string $value): void {
            $this->values[$appId . '/' . $key] = $value;
        }
    }

    final class FakeAppManager implements IAppManager {
        public function __construct(public bool $providerEnabled = true) {
        }

        public function isEnabledForUser($appId, $user = null): bool {
            return $appId === 'flzrecruitment' && $this->providerEnabled;
        }

        public function getAppInstalledVersions(bool $onlyEnabled = false): array {
            return [];
        }

        public function getEnabledApps(): array {
            return $this->providerEnabled ? ['flzrecruitment'] : [];
        }
    }

    TestRunner::test('ApplicationHiredListener bucht das Provider-Event in die begrenzte Inbox', static function (): void {
        $config = new FakeAppConfig();
        $listener = new ApplicationHiredListener(new FakeAppManager(true), new BqHiringEventInboxService($config));

        $listener->handle(new ApplicationHiredEvent(7, 'approved_for_hire', 'area-south', 'hr-user', new DateTimeImmutable('2026-11-28T10:00:00+00:00')));

        $entries = (new BqHiringEventInboxService($config))->entries();
        assertSame(1, count($entries));
        assertSame(7, $entries[0]['applicationId']);
        assertSame('area-south', $entries[0]['areaKey']);
        assertSame('2026-11-28T10:00:00+00:00', $entries[0]['occurredAt']);
    });

    TestRunner::test('ApplicationHiredListener ist ohne aktiviertes flzrecruitment ein No-Op', static function (): void {
        $config = new FakeAppConfig();
        $listener = new ApplicationHiredListener(new FakeAppManager(false), new BqHiringEventInboxService($config));

        $listener->handle(new ApplicationHiredEvent(7, 'approved_for_hire', 'area-south', 'hr-user', new DateTimeImmutable()));

        assertSame([], (new BqHiringEventInboxService($config))->entries());
    });

    TestRunner::test('ApplicationHiredListener ignoriert fremde Events', static function (): void {
        $config = new FakeAppConfig();
        $listener = new ApplicationHiredListener(new FakeAppManager(true), new BqHiringEventInboxService($config));

        $listener->handle(new Event());

        assertSame([], (new BqHiringEventInboxService($config))->entries());
    });

    TestRunner::test('Inbox bleibt auf 100 Einträge begrenzt und hängt neue an', static function (): void {
        $config = new FakeAppConfig();
        $inbox = new BqHiringEventInboxService($config);
        for ($i = 1; $i <= 105; $i++) {
            $inbox->record($i, 'a', '2026-11-28T10:00:00+00:00');
        }
        $entries = $inbox->entries();
        assertSame(100, count($entries));
        assertSame(6, $entries[0]['applicationId']);
        assertSame(105, $entries[99]['applicationId']);
    });

    TestRunner::test('Inbox liest ungültige oder fremde AppConfig-Werte als leer', static function (): void {
        $config = new FakeAppConfig();
        $config->setValueString('flzbqplanung', BqHiringEventInboxService::CONFIG_KEY, '{ungültig');
        assertSame([], (new BqHiringEventInboxService($config))->entries());

        $config->setValueString('flzbqplanung', BqHiringEventInboxService::CONFIG_KEY, '[{"applicationId":"x"}]');
        assertSame([], (new BqHiringEventInboxService($config))->entries());
    });

    TestRunner::finish();
}
