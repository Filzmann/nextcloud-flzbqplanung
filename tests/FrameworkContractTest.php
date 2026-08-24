<?php

declare(strict_types=1);

namespace OCP {
    interface IRequest {
    }

    class Http { public const STATUS_FORBIDDEN = 403; }

    if (!interface_exists(IUser::class)) {
        interface IUser { public function getUID(): string; }
    }
    if (!interface_exists(IUserSession::class)) {
        interface IUserSession { public function getUser(): ?IUser; }
    }
    if (!interface_exists(IGroup::class)) {
        interface IGroup {}
    }
    if (!interface_exists(IGroupManager::class)) {
        interface IGroupManager {
            public function isAdmin(string $uid): bool;
            public function isInGroup(string $uid, string $gid): bool;
            public function get(string $gid): ?IGroup;
        }
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
        public function __construct() {
        }
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
        public function __construct(public string $appId, public string $templateName, public array $params = [], public string $renderAs = '', public int $status = 200) {
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
    use OCA\AdBqPlanning\Service\AuthorizationService;
    use OCA\LocalBase\Service\StandaloneAppNavigationService;
    use OCP\EventDispatcher\Event;
    use OCP\Navigation\Events\LoadAdditionalEntriesEvent;
    use OCP\IAppConfig;
    use OCP\IGroup;
    use OCP\IGroupManager;
    use OCP\IUser;
    use OCP\IUserSession;

    final class FrameworkUser implements IUser {
        public function __construct(private string $uid) {}
        public function getUID(): string { return $this->uid; }
    }
    final class FrameworkSession implements IUserSession {
        public function __construct(private ?IUser $user) {}
        public function getUser(): ?IUser { return $this->user; }
    }
    final class FrameworkGroups implements IGroupManager {
        public function __construct(private bool $admin = true) {}
        public function isAdmin(string $uid): bool { return $this->admin; }
        public function isInGroup(string $uid, string $gid): bool { return false; }
        public function get(string $gid): ?IGroup { return null; }
    }
    final class FrameworkConfig implements IAppConfig {
        public function getValueInt(string $appId, string $key, int $default): int { return $default; }
        public function getValueString(string $appId, string $key, string $default): string { return $default; }
        public function setValueInt(string $appId, string $key, int $value): void {}
        public function setValueString(string $appId, string $key, string $value): void {}
    }

    function adminAuthorization(): AuthorizationService {
        return new AuthorizationService(
            new FrameworkSession(new FrameworkUser('admin-a')),
            new FrameworkGroups(),
            new FrameworkConfig(),
        );
    }

    TestRunner::test('framework entrypoint uses the approved app and template identities', static function (): void {
        $application = new Application(['sample' => 'value']);
        assertSame('adbqplanung', $application->appId);
    });

    TestRunner::test('standalone navigation delegates only the Nextcloud navigation event', static function (): void {
        $navigation = new StandaloneAppNavigationService();
        $listener = new StandaloneNavigationListener($navigation, adminAuthorization());

        $listener->handle(new Event());
        assertSame([], $navigation->calls);

        $listener->handle(new LoadAdditionalEntriesEvent());
        assertSame([['adbqplanung', 'BQ-Planer', 'app.svg']], $navigation->calls);
    });

    TestRunner::test('standalone navigation stays absent without BQ access', static function (): void {
        $navigation = new StandaloneAppNavigationService();
        $authorization = new AuthorizationService(
            new FrameworkSession(new FrameworkUser('user-a')),
            new FrameworkGroups(false),
            new FrameworkConfig(),
        );
        (new StandaloneNavigationListener($navigation, $authorization))->handle(new LoadAdditionalEntriesEvent());
        assertSame([], $navigation->calls);
    });
}
