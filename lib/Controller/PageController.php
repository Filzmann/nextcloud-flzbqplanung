<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Controller;

use OCA\AdBqPlanning\AppInfo\Application;
use OCA\AdBqPlanning\Repository\RunRepository;
use OCA\AdBqPlanning\Repository\TeachingRepository;
use OCA\AdBqPlanning\Service\PlanningSettingsService;
use OCA\AdBqPlanning\Service\RunService;
use OCA\AdBqPlanning\Service\AuthorizationService;
use OCA\AdBqPlanning\Service\RoleSettingsService;
use OCA\AdBqPlanning\Service\TemporaryAdminAccessService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http;
use OCP\IRequest;

final class PageController extends Controller {
    public function __construct(
        IRequest $request,
        private RunRepository $runs,
        private PlanningSettingsService $settingsService,
        private TeachingRepository $teaching,
        private RunService $runService,
        private AuthorizationService $authorization,
        private RoleSettingsService $roleSettings,
        private TemporaryAdminAccessService $temporaryAdminAccess,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function index(): TemplateResponse {
        $hasBqAccess = $this->authorization->hasAnyAccess();
        $canManageAdminAccess = $this->temporaryAdminAccess->canManage();
        $showMissingAdminGrant = $this->temporaryAdminAccess->currentAdminNeedsGrant();
        if (!$hasBqAccess && !$canManageAdminAccess && !$showMissingAdminGrant) {
            return new TemplateResponse('core', '403', [], 'guest', Http::STATUS_FORBIDDEN);
        }
        $capabilities = $hasBqAccess ? $this->authorization->capabilities() : [
            'admin' => false,
            'planning' => false,
            'teaching' => false,
            'publishing' => false,
        ];
        $runs = $hasBqAccess ? array_map(function (array $run): array {
                $run['modules'] = $this->runs->modules((int)$run['id']);
                $run['moduleConflicts'] = $this->runService->moduleConflicts($run['modules']);
                return $run;
            }, $this->runs->runs()) : [];
        return new TemplateResponse(Application::APP_ID, 'index', [
            'runs' => $runs,
            'settings' => $hasBqAccess ? $this->settingsService->current() : [],
            'lecturers' => $capabilities['teaching'] ? $this->teaching->lecturers() : [],
            'teachingRequests' => $capabilities['teaching'] ? $this->teaching->requests() : [],
            'capabilities' => $capabilities,
            'roleSettings' => $capabilities['admin'] ? $this->roleSettings->current() : [],
            'canManageAdminAccess' => $canManageAdminAccess,
            'showMissingAdminGrant' => $showMissingAdminGrant,
            'showAdminAccessLink' => $canManageAdminAccess && $showMissingAdminGrant,
            'hasBqAccess' => $hasBqAccess,
        ]);
    }
}
