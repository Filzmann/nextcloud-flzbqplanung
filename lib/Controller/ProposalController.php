<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Controller;

use DomainException;
use OCA\FlzBqPlanning\AppInfo\Application;
use OCA\FlzBqPlanning\Exception\AccessDeniedException;
use OCA\FlzBqPlanning\Service\AuthorizationService;
use OCA\FlzBqPlanning\Service\CalendarProposalService;
use OCA\FlzBqPlanning\Service\PlanningSettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

final class ProposalController extends Controller {
    public function __construct(
        IRequest $request,
        private CalendarProposalService $proposals,
        private PlanningSettingsService $settings,
        private AuthorizationService $authorization,
        private LoggerInterface $logger,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function suggest(int $year, int $month): JSONResponse {
        try {
            return new JSONResponse(['data' => $this->authorization->execute(AuthorizationService::PLANNING, function () use ($year, $month): array {
                $settings = $this->settings->current();
                return $this->proposals->suggest(
                $year,
                $month,
                $this->settings->rules(),
                $settings['bridgeDays'],
                );
            })]);
        } catch (AccessDeniedException $error) {
            return new JSONResponse(['error' => $error->getMessage()], Http::STATUS_FORBIDDEN);
        } catch (DomainException $error) {
            return new JSONResponse(['error' => $error->getMessage()], 422);
        } catch (Throwable $error) {
            $this->logger->error('Unexpected BQ proposal failure.', [
                'app' => Application::APP_ID,
                'exceptionClass' => $error::class,
            ]);
            return new JSONResponse(['error' => 'Der BQ-Terminvorschlag konnte nicht erstellt werden.'], 500);
        }
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function suggestYear(int $year): JSONResponse {
        try {
            return new JSONResponse(['data' => $this->authorization->execute(AuthorizationService::PLANNING, function () use ($year): array {
                $settings = $this->settings->current();
                return $this->proposals->suggestYear(
                $year,
                $this->settings->rules(),
                $settings['bridgeDays'],
                );
            })]);
        } catch (AccessDeniedException $error) {
            return new JSONResponse(['error' => $error->getMessage()], Http::STATUS_FORBIDDEN);
        } catch (DomainException $error) {
            return new JSONResponse(['error' => $error->getMessage()], 422);
        } catch (Throwable $error) {
            $this->logger->error('Unexpected BQ annual proposal failure.', [
                'app' => Application::APP_ID,
                'exceptionClass' => $error::class,
            ]);
            return new JSONResponse(['error' => 'Die BQ-Jahresvorschau konnte nicht erstellt werden.'], 500);
        }
    }
}
