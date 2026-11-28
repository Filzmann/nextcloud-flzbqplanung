<?php

declare(strict_types=1);

namespace OCP\App
{
    if (!interface_exists(IAppManager::class)) {
        interface IAppManager
        {
            public function getAppInstalledVersions(bool $onlyEnabled = false): array;
            public function getEnabledApps(): array;
        }
    }
}

namespace OCP
{
    if (!class_exists(Server::class)) {
        final class Server
        {
            public static mixed $service = null;

            public static function get(string $class): mixed
            {
                return self::$service;
            }
        }
    }
}

namespace Psr\Log
{
    if (!interface_exists(LoggerInterface::class)) {
        interface LoggerInterface
        {
            public function warning(string $message, array $context = []): void;
            public function error(string $message, array $context = []): void;
        }
    }
}

namespace OCA\FlzOrganization\PublicApi\V1
{
    if (!class_exists(Contract::class)) {
        final class Contract
        {
            public static bool $supported = true;

            public static function supports(string $version): bool
            {
                return self::$supported && $version === '1.0';
            }
        }

        final class OrganizationSnapshot
        {
            public static string $version = '1.0';
            public static bool $valid = true;

            public function contractVersion(): string
            {
                return self::$version;
            }

            public function isValid(): bool
            {
                return self::$valid;
            }

            public function toArray(): array
            {
                return [
                    'valid' => true,
                    'provider' => 'flz_organization',
                ];
            }
        }

        final class OrganizationSnapshotService
        {
            public static bool $fail = false;

            public function snapshot(): OrganizationSnapshot
            {
                if (self::$fail) {
                    throw new \RuntimeException('organization provider unavailable');
                }
                return new OrganizationSnapshot();
            }
        }
    }
}

namespace OCA\LocalBase\PublicApi\V1
{
    if (!class_exists(OrganizationSnapshotService::class)) {
        final class OrganizationSnapshot
        {
            public function contractVersion(): string
            {
                return '1.0';
            }

            public function isValid(): bool
            {
                return true;
            }

            public function toArray(): array
            {
                return [
                    'valid' => true,
                    'provider' => 'localbase',
                ];
            }
        }

        final class OrganizationSnapshotService
        {
            public function snapshot(): OrganizationSnapshot
            {
                return new OrganizationSnapshot();
            }
        }
    }
}

namespace
{
    use FlzBqPlanning\Tests\TestRunner;
    use OCA\FlzBqPlanning\Service\UnifiedOrganizationSnapshotSource;
    use OCP\App\IAppManager;
    use OCP\Server;
    use Psr\Log\LoggerInterface;
    use function FlzBqPlanning\Tests\assertSame;

    final class BqOrganizationApps implements IAppManager
    {
        public function __construct(
            private array $enabled,
            private bool $fail = false,
        ) {
        }

        public function getAppInstalledVersions(bool $onlyEnabled = false): array
        {
            return array_fill_keys($this->enabled, '1.0.0');
        }

        public function getEnabledApps(): array
        {
            if ($this->fail) {
                throw new \RuntimeException('app registry unavailable');
            }
            return $this->enabled;
        }

        public function isEnabledForUser($appId, $user = null): bool
        {
            return in_array($appId, $this->enabled, true);
        }
    }

    final class BqOrganizationLogger implements LoggerInterface
    {
        /** @var list<array{message:string,context:array<string,mixed>}> */
        public array $warnings = [];

        public function warning(string $message, array $context = []): void
        {
            $this->warnings[] = [
                'message' => $message,
                'context' => $context,
            ];
        }

        public function error(string $message, array $context = []): void
        {
        }
    }

    function resetBqOrganizationProvider(): void
    {
        \OCA\FlzOrganization\PublicApi\V1\Contract::$supported = true;
        \OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshot::$version = '1.0';
        \OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshot::$valid = true;
        \OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshotService::$fail = false;
        Server::$service = new \OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshotService();
    }

    TestRunner::test('BQ organization source resolves compatible flz_organization V1', static function (): void
    {
        resetBqOrganizationProvider();
        $snapshot = (new UnifiedOrganizationSnapshotSource(new BqOrganizationApps(['flz_organization'])))->snapshot();
        assertSame('flz_organization', $snapshot['provider'] ?? null);
    });

    TestRunner::test('BQ organization source never falls back to LocalBase', static function (): void
    {
        Server::$service = new \OCA\LocalBase\PublicApi\V1\OrganizationSnapshotService();
        $snapshot = (new UnifiedOrganizationSnapshotSource(new BqOrganizationApps(['localbase'])))->snapshot();
        assertSame(null, $snapshot);
    });

    TestRunner::test('BQ organization source rejects incompatible invalid and unusable providers', static function (): void
    {
        resetBqOrganizationProvider();
        \OCA\FlzOrganization\PublicApi\V1\Contract::$supported = false;
        assertSame(null, (new UnifiedOrganizationSnapshotSource(
            new BqOrganizationApps(['flz_organization']),
        ))->snapshot());

        resetBqOrganizationProvider();
        \OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshot::$version = '2.0';
        assertSame(null, (new UnifiedOrganizationSnapshotSource(
            new BqOrganizationApps(['flz_organization']),
        ))->snapshot());

        resetBqOrganizationProvider();
        \OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshot::$valid = false;
        assertSame(null, (new UnifiedOrganizationSnapshotSource(
            new BqOrganizationApps(['flz_organization']),
        ))->snapshot());

        resetBqOrganizationProvider();
        Server::$service = new \stdClass();
        assertSame(null, (new UnifiedOrganizationSnapshotSource(
            new BqOrganizationApps(['flz_organization']),
        ))->snapshot());
    });

    TestRunner::test('BQ organization source logs registry failures and stays fail closed', static function (): void
    {
        $logger = new BqOrganizationLogger();
        $source = new UnifiedOrganizationSnapshotSource(
            new BqOrganizationApps(['flz_organization'], true),
            $logger,
        );

        assertSame(null, $source->snapshot());
        assertSame(1, count($logger->warnings));
        assertSame('flz_organization', $logger->warnings[0]['context']['provider'] ?? null);
        assertSame(\RuntimeException::class, $logger->warnings[0]['context']['exceptionClass'] ?? null);
    });

    TestRunner::test('BQ organization source logs provider failures and stays fail closed', static function (): void
    {
        resetBqOrganizationProvider();
        \OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshotService::$fail = true;
        $logger = new BqOrganizationLogger();

        $snapshot = (new UnifiedOrganizationSnapshotSource(
            new BqOrganizationApps(['flz_organization']),
            $logger,
        ))->snapshot();

        assertSame(null, $snapshot);
        assertSame(1, count($logger->warnings));
        assertSame('flzbqplanung', $logger->warnings[0]['context']['app'] ?? null);
        assertSame('flz_organization', $logger->warnings[0]['context']['provider'] ?? null);
        assertSame(\RuntimeException::class, $logger->warnings[0]['context']['exceptionClass'] ?? null);
    });
}
