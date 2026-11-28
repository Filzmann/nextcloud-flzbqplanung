<?php

declare(strict_types=1);

namespace OCP {
    interface IRequest {
    }

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
    class Http { public const STATUS_FORBIDDEN = 403; }

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

namespace OCP\App {
    if (!interface_exists(IAppManager::class)) {
        interface IAppManager {
            public function isEnabledForUser($appId, $user = null): bool;
            public function getAppInstalledVersions(bool $onlyEnabled = false): array;
            public function getEnabledApps(): array;
        }
    }
}

namespace OCP {
    interface IURLGenerator { public function linkToRoute(string $routeName, array $arguments = []): string; public function imagePath(string $appName, string $file): string; }
}

namespace OCP\Navigation {
    interface INavigationManager { public const TYPE_APPS = 'link'; public function add(callable $entry): void; }
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

namespace FlzBqPlanning\Tests {
    use OCA\FlzBqPlanning\AppInfo\Application;
    use OCA\FlzBqPlanning\Listener\StandaloneNavigationListener;
    use OCA\FlzBqPlanning\Contract\OrganizationSnapshotSource;
    use OCA\FlzBqPlanning\Service\AuthorizationService;
    use OCA\FlzBqPlanning\Service\OrganizationRolePolicy;
    use OCA\FlzBqPlanning\Service\TemporaryAdminAccessChecker;
    use OCP\App\IAppManager;
    use OCP\EventDispatcher\Event;
    use OCP\IURLGenerator;
    use OCP\Navigation\INavigationManager;
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
    final class FrameworkNavigation implements INavigationManager {
        public array $entries = [];

        public function add(callable $entry): void {
            $this->entries[] = $entry;
        }
    }

    final class FrameworkAppManager implements IAppManager {
        public function isEnabledForUser($appId, $user = null): bool {
            return false;
        }

        public function getAppInstalledVersions(bool $onlyEnabled = false): array {
            return [];
        }

        public function getEnabledApps(): array {
            return [];
        }
    }

    final class FrameworkUrlGenerator implements IURLGenerator {
        public function linkToRoute(string $routeName, array $arguments = []): string {
            return $routeName;
        }

        public function imagePath(string $appName, string $file): string {
            return "$appName/$file";
        }
    }

    final class FrameworkGrants implements TemporaryAdminAccessChecker {
        public function __construct(private array $active = ['admin-a']) {}
        public function hasActiveGrant(string $uid): bool { return in_array($uid,$this->active,true); }
    }

    /** @param array<string,string> $roleGroups */
    function organizationRolePolicy(IGroupManager $groups, array $roleGroups): OrganizationRolePolicy {
        $labels = [
            'deputy_pdl' => 'Stellvertretende Pflegedienstleitung',
            'pdl' => 'Pflegedienstleitung',
            'pfk' => 'Pflegefachkräfte',
        ];
        $roles = [];
        foreach ($roleGroups as $roleKey => $groupId) {
            $roles[$roleKey] = ['groupId' => $groupId, 'label' => $labels[$roleKey] ?? $roleKey];
        }
        $source = new class($roles) implements OrganizationSnapshotSource {
            public function __construct(private array $roles) {}
            public function snapshot(): ?array {
                return ['contractVersion' => '1.0', 'valid' => true, 'roles' => $this->roles, 'areas' => []];
            }
        };
        return new OrganizationRolePolicy($source, $groups);
    }

    function adminAuthorization(): AuthorizationService {
        return new AuthorizationService(
            new FrameworkSession(new FrameworkUser('admin-a')),
            new FrameworkGroups(),
            new FrameworkConfig(),
            new FrameworkGrants(),
        );
    }

    TestRunner::test('framework entrypoint uses the approved app and template identities', static function (): void {
        $application = new Application(['sample' => 'value']);
        assertSame('flzbqplanung', $application->appId);
    });

    TestRunner::test('standalone navigation delegates only the Nextcloud navigation event', static function (): void {
        $navigation = new FrameworkNavigation();
        $listener = new StandaloneNavigationListener(
            adminAuthorization(),
            new FrameworkSession(new FrameworkUser('admin-a')),
            new FrameworkAppManager(),
            $navigation,
            new FrameworkUrlGenerator(),
        );

        $listener->handle(new Event());
        assertSame([], $navigation->entries);

        $listener->handle(new LoadAdditionalEntriesEvent());
        $entry = ($navigation->entries[0] ?? static fn(): array => [])();
        assertSame('flzbqplanung', $entry['id']);
        assertSame('flzbqplanung.page.index', $entry['href']);
        assertSame('BQ-Planer', $entry['name']);
    });

    TestRunner::test('standalone navigation stays absent without BQ access', static function (): void {
        $navigation = new FrameworkNavigation();
        $authorization = new AuthorizationService(
            new FrameworkSession(new FrameworkUser('user-a')),
            new FrameworkGroups(false),
            new FrameworkConfig(),
        );
        $listener = new StandaloneNavigationListener(
            $authorization,
            new FrameworkSession(new FrameworkUser('user-a')),
            new FrameworkAppManager(),
            $navigation,
            new FrameworkUrlGenerator(),
        );
        $listener->handle(new LoadAdditionalEntriesEvent());
        assertSame([], $navigation->entries);
    });
}
