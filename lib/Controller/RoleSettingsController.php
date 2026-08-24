<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Controller;

use DomainException;
use OCA\AdBqPlanning\AppInfo\Application;
use OCA\AdBqPlanning\Exception\AccessDeniedException;
use OCA\AdBqPlanning\Service\AuthorizationService;
use OCA\AdBqPlanning\Service\RoleSettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Http;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

final class RoleSettingsController extends Controller {
    public function __construct(
        IRequest $request,
        private RoleSettingsService $settings,
        private AuthorizationService $authorization,
        private LoggerInterface $logger,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    public function current(): JSONResponse {
        return $this->respond(fn (): array => $this->settings->current());
    }

    #[NoAdminRequired]
    public function update(string $planningGroup, string $teachingGroup, string $publishingGroup): JSONResponse {
        return $this->respond(fn (): array => $this->settings->update(
            $planningGroup,
            $teachingGroup,
            $publishingGroup,
        ));
    }

    private function respond(callable $callback): JSONResponse {
        try {
            return new JSONResponse(['data' => $this->authorization->execute(AuthorizationService::ADMIN, $callback)]);
        } catch (AccessDeniedException $error) {
            return new JSONResponse(['error' => $error->getMessage()], Http::STATUS_FORBIDDEN);
        } catch (DomainException $error) {
            return new JSONResponse(['error' => $error->getMessage()], 422);
        } catch (Throwable $error) {
            $this->logger->error('Unexpected BQ role settings failure.', [
                'app' => Application::APP_ID,
                'exceptionClass' => $error::class,
            ]);
            return new JSONResponse(['error' => 'Die BQ-Rollengruppen konnten nicht verarbeitet werden.'], 500);
        }
    }
}
