<?php

declare(strict_types=1);

namespace OCP {
    interface IRequest {
    }

    if (!interface_exists(IAppConfig::class)) {
        interface IAppConfig {
            public function getValueInt(string $appId, string $key, int $default): int;
            public function getValueString(string $appId, string $key, string $default): string;
            public function setValueInt(string $appId, string $key, int $value): void;
            public function setValueString(string $appId, string $key, string $value): void;
        }
    }
}

namespace OCP\AppFramework {
    class App {
        public function __construct(public string $appId, public array $urlParams = []) {
        }
    }

    class Controller {
        public function __construct(public string $appId, public \OCP\IRequest $request) {
        }
    }
}

namespace OCP\AppFramework\Bootstrap {
    interface IBootstrap {
        public function register(IRegistrationContext $context): void;
        public function boot(IBootContext $context): void;
    }

    interface IRegistrationContext {
        public function registerEventListener(string $event, string $listener): void;
        public function registerServiceAlias(string $service, string $target): void;
    }

    interface IBootContext {
    }
}

namespace OCP\EventDispatcher {
    class Event {
    }

    interface IEventListener {
        public function handle(Event $event): void;
    }
}

namespace OCP\Navigation\Events {
    class LoadAdditionalEntriesEvent extends \OCP\EventDispatcher\Event {
    }
}

namespace OCA\LocalBase\Service {
    class StandaloneAppNavigationService {
        public array $calls = [];

        public function addCatalogProductWhenStandalone(string $appId, string $label, string $icon): void {
            $this->calls[] = [$appId, $label, $icon];
        }
    }
}

namespace OCP\AppFramework\Http\Attribute {
    use Attribute;

    #[Attribute(Attribute::TARGET_METHOD)]
    class NoCSRFRequired {
    }
}

namespace OCP\AppFramework\Http {
    class TemplateResponse {
        public function __construct(public string $appId, public string $templateName) {
        }
    }

    class JSONResponse {
        public function __construct(public array $data, public int $status = 200) {
        }
    }
}

namespace Psr\Log {
    interface LoggerInterface {
        public function warning(string $message, array $context = []): void;
        public function error(string $message, array $context = []): void;
    }
}

namespace AdBqPlanning\Tests {
    use OCA\AdBqPlanning\AppInfo\Application;
    use OCA\AdBqPlanning\Listener\StandaloneNavigationListener;
    use OCA\LocalBase\Service\StandaloneAppNavigationService;
    use OCP\EventDispatcher\Event;
    use OCP\Navigation\Events\LoadAdditionalEntriesEvent;

    TestRunner::test('framework entrypoint uses the approved app and template identities', static function (): void {
        $application = new Application(['sample' => 'value']);
        assertSame('adbqplanung', $application->appId);
    });

    TestRunner::test('standalone navigation delegates only the Nextcloud navigation event', static function (): void {
        $navigation = new StandaloneAppNavigationService();
        $listener = new StandaloneNavigationListener($navigation);

        $listener->handle(new Event());
        assertSame([], $navigation->calls);

        $listener->handle(new LoadAdditionalEntriesEvent());
        assertSame([['adbqplanung', 'BQ-Planer', 'app.svg']], $navigation->calls);
    });
}
