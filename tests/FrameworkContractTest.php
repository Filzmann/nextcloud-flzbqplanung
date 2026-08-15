<?php

declare(strict_types=1);

namespace OCP {
    interface IRequest {
    }
}

namespace OCP\AppFramework {
    class App {
        public function __construct(public string $appId, public array $urlParams = []) {
        }
    }

    class Controller {
        public function __construct(public string $appId, public \OCP\IRequest $request) {
        }
    }
}

namespace OCP\AppFramework\Http\Attribute {
    use Attribute;

    #[Attribute(Attribute::TARGET_METHOD)]
    class NoCSRFRequired {
    }
}

namespace OCP\AppFramework\Http {
    class TemplateResponse {
        public function __construct(public string $appId, public string $templateName) {
        }
    }
}

namespace AdBqPlanning\Tests {
    use OCA\AdBqPlanning\AppInfo\Application;
    use OCA\AdBqPlanning\Controller\PageController;
    use OCP\IRequest;

    final class RequestFake implements IRequest {
    }

    TestRunner::test('framework entrypoint uses the approved app and template identities', static function (): void {
        $application = new Application(['sample' => 'value']);
        assertSame('adbqplanung', $application->appId);

        $response = (new PageController(new RequestFake()))->index();
        assertSame('adbqplanung', $response->appId);
        assertSame('index', $response->templateName);
    });
}
