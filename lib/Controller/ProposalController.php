<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Controller;

use DomainException;
use OCA\AdBqPlanning\AppInfo\Application;
use OCA\AdBqPlanning\Service\CalendarProposalService;
use OCA\AdBqPlanning\Service\PlanningSettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

final class ProposalController extends Controller {
    public function __construct(
        IRequest $request,
        private CalendarProposalService $proposals,
        private PlanningSettingsService $settings,
        private LoggerInterface $logger,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoCSRFRequired]
    public function suggest(int $year, int $month): JSONResponse {
        try {
            $settings = $this->settings->current();
            return new JSONResponse(['data' => $this->proposals->suggest(
                $year,
                $month,
                $this->settings->rules(),
                $settings['bridgeDays'],
            )]);
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
}
