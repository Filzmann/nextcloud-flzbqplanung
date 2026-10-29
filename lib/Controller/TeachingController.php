<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Controller;

use DomainException;
use OCA\FlzBqPlanning\AppInfo\Application;
use OCA\FlzBqPlanning\Exception\AccessDeniedException;
use OCA\FlzBqPlanning\Contract\TeachingStore;
use OCA\FlzBqPlanning\Service\TeachingService;
use OCA\FlzBqPlanning\Service\AuthorizationService;
use OCA\FlzBqPlanning\Service\InternalPfkDirectory;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

final class TeachingController extends Controller {
    public function __construct(
        IRequest $request,
        private TeachingStore $store,
        private TeachingService $service,
        private AuthorizationService $authorization,
        private InternalPfkDirectory $internalPfkDirectory,
        private LoggerInterface $logger,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function lecturers(): JSONResponse {
        if ($this->authorization->can(AuthorizationService::TEACHING)) {
            return $this->respond(AuthorizationService::TEACHING, fn (): array => $this->store->lecturers());
        }
        return $this->respond(AuthorizationService::PFK_POOL, fn (): array => $this->store->lecturers('internal'));
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function requests(): JSONResponse {
        return $this->respond(AuthorizationService::TEACHING, fn (): array => $this->store->requests());
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function internalPfkMembers(string $search = ''): JSONResponse {
        return $this->respond(AuthorizationService::PFK_POOL, fn (): array => $this->internalPfkDirectory->search($search));
    }

    #[NoAdminRequired]
    public function createLecturer(string $kind, string $nextcloudUid, string $displayName, string $email): JSONResponse {
        $capability = trim($kind) === 'internal' ? AuthorizationService::PFK_POOL : AuthorizationService::TEACHING;
        return $this->respond($capability, function () use ($kind, $nextcloudUid, $displayName, $email): array {
            $nextcloudUid = trim($nextcloudUid);
            if (trim($kind) === 'internal') {
                $this->internalPfkDirectory->assertSelectable($nextcloudUid);
            }
            return ['id' => $this->service->createLecturer(
                $this->store, $kind, $nextcloudUid, $displayName, $email, $this->actorUid(),
            )];
        });
    }

    #[NoAdminRequired]
    public function updateLecturerStatus(int $id, string $targetStatus, int $version): JSONResponse {
        $entryCapability = $this->authorization->can(AuthorizationService::TEACHING)
            ? AuthorizationService::TEACHING
            : AuthorizationService::PFK_POOL;
        return $this->respond($entryCapability, function () use ($id, $targetStatus, $version): array {
            $lecturer = $this->store->lecturer($id);
            $requiredCapability = ($lecturer['kind'] ?? null) === 'internal'
                ? AuthorizationService::PFK_POOL
                : AuthorizationService::TEACHING;
            $this->authorization->requireCapability($requiredCapability);
            return $this->service->updateLecturerStatus(
                $this->store,
                $id,
                $targetStatus,
                $version,
                $this->actorUid(),
            );
        });
    }

    #[NoAdminRequired]
    public function setLead(int $id, int $lecturerId, int $version): JSONResponse {
        return $this->respond(AuthorizationService::TEACHING, fn (): array => $this->service->setLead(
            $this->store, $id, $lecturerId, $version, $this->actorUid(),
        ));
    }

    #[NoAdminRequired]
    public function createRequest(
        int $id,
        int $lecturerId,
        int $runVersion,
        int $moduleVersion,
    ): JSONResponse {
        return $this->respond(AuthorizationService::TEACHING, fn (): array => ['id' => $this->service->createRequest(
            $this->store, $id, $lecturerId, $runVersion, $moduleVersion, $this->actorUid(),
        )]);
    }

    #[NoAdminRequired]
    public function transitionRequest(int $id, string $targetStatus, int $version): JSONResponse {
        return $this->respond(AuthorizationService::TEACHING, fn (): array => $this->service->transitionRequest(
            $this->store, $id, $targetStatus, $version, $this->actorUid(),
        ));
    }

    private function actorUid(): string {
        return $this->authorization->actorUid();
    }

    private function respond(string $capability, callable $callback): JSONResponse {
        try {
            return new JSONResponse(['data' => $this->authorization->execute($capability, $callback)]);
        } catch (AccessDeniedException $error) {
            return new JSONResponse(['error' => $error->getMessage()], Http::STATUS_FORBIDDEN);
        } catch (DomainException $error) {
            return new JSONResponse(['error' => $error->getMessage()], 422);
        } catch (Throwable $error) {
            $this->logger->error('Unexpected BQ teaching failure.', [
                'app' => Application::APP_ID,
                'exceptionClass' => $error::class,
            ]);
            return new JSONResponse(['error' => 'Die Dozentinnenplanung konnte nicht verarbeitet werden.'], 500);
        }
    }
}
