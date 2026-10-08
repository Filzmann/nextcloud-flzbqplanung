<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Controller;

use DomainException;
use OCA\FlzBqPlanning\AppInfo\Application;
use OCA\FlzBqPlanning\Exception\AccessDeniedException;
use OCA\FlzBqPlanning\Repository\TeachingRepository;
use OCA\FlzBqPlanning\Service\TeachingService;
use OCA\FlzBqPlanning\Service\AuthorizationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Throwable;

final class TeachingController extends Controller {
    public function __construct(
        IRequest $request,
        private TeachingRepository $store,
        private TeachingService $service,
        private AuthorizationService $authorization,
        private IUserManager $userManager,
        private LoggerInterface $logger,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function lecturers(): JSONResponse {
        return $this->respond(fn (): array => $this->store->lecturers());
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function requests(): JSONResponse {
        return $this->respond(fn (): array => $this->store->requests());
    }

    #[NoAdminRequired]
    public function createLecturer(string $kind, string $nextcloudUid, string $displayName, string $email): JSONResponse {
        return $this->respond(function () use ($kind, $nextcloudUid, $displayName, $email): array {
            $nextcloudUid = trim($nextcloudUid);
            if (trim($kind) === 'internal' && !$this->userManager->userExists($nextcloudUid)) {
                throw new DomainException('Die angegebene interne PFK existiert nicht in Nextcloud.');
            }
            return ['id' => $this->service->createLecturer(
                $this->store, $kind, $nextcloudUid, $displayName, $email, $this->actorUid(),
            )];
        });
    }

    #[NoAdminRequired]
    public function setLead(int $id, int $lecturerId, int $version): JSONResponse {
        return $this->respond(fn (): array => $this->service->setLead(
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
        return $this->respond(fn (): array => ['id' => $this->service->createRequest(
            $this->store, $id, $lecturerId, $runVersion, $moduleVersion, $this->actorUid(),
        )]);
    }

    #[NoAdminRequired]
    public function transitionRequest(int $id, string $targetStatus, int $version): JSONResponse {
        return $this->respond(fn (): array => $this->service->transitionRequest(
            $this->store, $id, $targetStatus, $version, $this->actorUid(),
        ));
    }

    private function actorUid(): string {
        return $this->authorization->actorUid();
    }

    private function respond(callable $callback): JSONResponse {
        try {
            return new JSONResponse(['data' => $this->authorization->execute(AuthorizationService::TEACHING, $callback)]);
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
