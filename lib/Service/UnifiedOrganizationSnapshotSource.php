<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Service;

use OCA\FlzBqPlanning\Contract\OrganizationSnapshotSource;
use OCA\FlzOrganization\PublicApi\V1\Contract;
use OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshotService;
use OCP\App\IAppManager;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Lazy, fail-closed consumer of the standalone organization V1 runtime.
 *
 * LocalBase is intentionally not a fallback because it is no longer an
 * independent source of organization truth.
 */
final class UnifiedOrganizationSnapshotSource implements OrganizationSnapshotSource
{
    private const PROVIDER_APP_ID = 'flz_organization';
    private const SUPPORTED_CONTRACT_VERSION = '1.0';

    public function __construct(
        private IAppManager $apps,
        private ?LoggerInterface $logger = null,
    )
    {
    }

    public function snapshot(): ?array
    {
        try
        {
            if (!in_array(self::PROVIDER_APP_ID, $this->apps->getEnabledApps(), true))
            {
                return null;
            }
        }
        catch (Throwable $error)
        {
            return $this->providerFailure($error);
        }

        if (!class_exists(Contract::class)
            || !Contract::supports(self::SUPPORTED_CONTRACT_VERSION)
            || !class_exists(OrganizationSnapshotService::class))
        {
            return null;
        }

        try
        {
            $service = Server::get(OrganizationSnapshotService::class);
            if (!$service instanceof OrganizationSnapshotService)
            {
                return null;
            }
            $snapshot = $service->snapshot();
            if ($snapshot->contractVersion() !== self::SUPPORTED_CONTRACT_VERSION || !$snapshot->isValid())
            {
                return null;
            }
            return $snapshot->toArray();
        }
        catch (Throwable $error)
        {
            return $this->providerFailure($error);
        }
    }

    private function providerFailure(Throwable $error): null
    {
        $this->logger?->warning('BQ-Organisationsprovider konnte nicht gelesen werden.', [
            'app' => 'flzbqplanung',
            'provider' => self::PROVIDER_APP_ID,
            'exceptionClass' => $error::class,
        ]);
        return null;
    }
}
