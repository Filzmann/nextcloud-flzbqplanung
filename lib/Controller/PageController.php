<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Controller;

use OCA\AdBqPlanning\AppInfo\Application;
use OCA\AdBqPlanning\Repository\RunRepository;
use OCA\AdBqPlanning\Repository\TeachingRepository;
use OCA\AdBqPlanning\Service\PlanningSettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

final class PageController extends Controller {
    public function __construct(
        IRequest $request,
        private RunRepository $runs,
        private PlanningSettingsService $settingsService,
        private TeachingRepository $teaching,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoCSRFRequired]
    public function index(): TemplateResponse {
        $runs = array_map(function (array $run): array {
            $run['modules'] = $this->runs->modules((int)$run['id']);
            return $run;
        }, $this->runs->runs());
        return new TemplateResponse(Application::APP_ID, 'index', [
            'runs' => $runs,
            'settings' => $this->settingsService->current(),
            'lecturers' => $this->teaching->lecturers(),
            'teachingRequests' => $this->teaching->requests(),
        ]);
    }
}
