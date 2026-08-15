<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Controller;

use OCA\AdBqPlanning\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

final class PageController extends Controller {
    public function __construct(IRequest $request) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoCSRFRequired]
    public function index(): TemplateResponse {
        return new TemplateResponse(Application::APP_ID, 'index');
    }
}
