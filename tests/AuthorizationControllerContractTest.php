<?php

declare(strict_types=1);

namespace AdBqPlanning\Tests;

TestRunner::test('every BQ surface delegates access to the central authorization service', static function (): void {
    $root = dirname(__DIR__);
    $run = (string)file_get_contents($root . '/lib/Controller/RunController.php');
    $teaching = (string)file_get_contents($root . '/lib/Controller/TeachingController.php');
    $proposal = (string)file_get_contents($root . '/lib/Controller/ProposalController.php');
    $page = (string)file_get_contents($root . '/lib/Controller/PageController.php');
    $navigation = (string)file_get_contents($root . '/lib/Listener/StandaloneNavigationListener.php');

    foreach ([$run, $teaching, $proposal, $page] as $controller) {
        assertTrue(str_contains($controller, 'AuthorizationService'));
        assertTrue(str_contains($controller, 'NoAdminRequired'));
    }
    assertTrue(str_contains($run, 'AuthorizationService::PLANNING'));
    assertTrue(str_contains($run, 'AuthorizationService::PUBLISHING'));
    assertTrue(str_contains($run, 'AuthorizationService::ADMIN'));
    assertTrue(str_contains($teaching, 'AuthorizationService::TEACHING'));
    assertTrue(str_contains($proposal, 'AuthorizationService::PLANNING'));
    assertTrue(str_contains($page, 'hasAnyAccess'));
    assertTrue(str_contains($page, 'TemporaryAdminAccessService'));
    assertTrue(str_contains($page, "'core', '403'"));
    assertTrue(str_contains($navigation, 'hasAnyAccess'));
});

TestRunner::test('every BQ controller resolves HTTP status constants through the public AppFramework API', static function (): void {
    $controllerDirectory = dirname(__DIR__) . '/lib/Controller';
    foreach ([
        'PageController.php',
        'RunController.php',
        'TeachingController.php',
        'RoleSettingsController.php',
        'ProposalController.php',
    ] as $controllerFile) {
        $controller = (string)file_get_contents($controllerDirectory . '/' . $controllerFile);
        assertTrue(
            str_contains($controller, 'use OCP\\AppFramework\\Http;'),
            $controllerFile . ' verwendet nicht die öffentliche Nextcloud-HTTP-Klasse.',
        );
        assertTrue(
            !str_contains($controller, 'use OCP\\Http;'),
            $controllerFile . ' verwendet den auf NC 33/34 ungültigen OCP-Http-Import.',
        );
    }
});

TestRunner::test('read-only BQ APIs omit CSRF checks while write APIs keep them', static function (): void {
    $controllerDirectory = dirname(__DIR__) . '/lib/Controller';
    $contracts = [
        'RunController.php' => [
            'read' => ['list', 'settings'],
            'write' => ['create', 'update', 'addModule', 'updateModule', 'moveModule', 'publish', 'updateSettings'],
        ],
        'RoleSettingsController.php' => [
            'read' => ['current'],
            'write' => ['update'],
        ],
        'TeachingController.php' => [
            'read' => ['lecturers', 'requests'],
            'write' => ['createLecturer', 'setLead', 'createRequest', 'transitionRequest'],
        ],
    ];

    $attributesFor = static function (string $controller, string $method): string {
        $pattern = '/((?:\s*#\[[^\]]+\]\s*)+)public function ' . preg_quote($method, '/') . '\s*\(/';
        if (preg_match($pattern, $controller, $matches) !== 1) {
            throw new \RuntimeException('Attribute für Controller-Methode fehlen: ' . $method);
        }
        return $matches[1];
    };

    foreach ($contracts as $controllerFile => $methods) {
        $controller = (string)file_get_contents($controllerDirectory . '/' . $controllerFile);
        assertTrue(
            str_contains($controller, 'use OCP\\AppFramework\\Http\\Attribute\\NoCSRFRequired;'),
            $controllerFile . ' importiert den öffentlichen NoCSRFRequired-Vertrag nicht.',
        );
        foreach ($methods['read'] as $method) {
            assertTrue(
                str_contains($attributesFor($controller, $method), 'NoCSRFRequired'),
                $controllerFile . '::' . $method . ' verlangt für den lesenden GET-Endpunkt einen CSRF-Token.',
            );
        }
        foreach ($methods['write'] as $method) {
            assertTrue(
                !str_contains($attributesFor($controller, $method), 'NoCSRFRequired'),
                $controllerFile . '::' . $method . ' darf die CSRF-Prüfung des schreibenden Endpunkts nicht abschalten.',
            );
        }
    }
});

TestRunner::test('privacy officers reach the authenticated grant API while writes keep CSRF protection', static function (): void {
    $controller = (string)file_get_contents(dirname(__DIR__) . '/lib/Controller/TemporaryAdminAccessController.php');
    $attributesFor = static function (string $method) use ($controller): string {
        $pattern = '/((?:\s*#\[[^\]]+\]\s*)+)public function ' . preg_quote($method, '/') . '\s*\(/';
        if (preg_match($pattern, $controller, $matches) !== 1) throw new \RuntimeException('Attribute fehlen: ' . $method);
        return $matches[1];
    };
    foreach (['status','activate','revoke'] as $method) assertTrue(str_contains($attributesFor($method), 'NoAdminRequired'), $method . ' bleibt fälschlich native-admin-only.');
    assertTrue(str_contains($attributesFor('status'), 'NoCSRFRequired'));
    foreach (['activate','revoke'] as $method) assertTrue(!str_contains($attributesFor($method), 'NoCSRFRequired'), $method . ' darf CSRF nicht abschalten.');
});

TestRunner::test('role settings have a dedicated admin-only API and no database schema', static function (): void {
    $root = dirname(__DIR__);
    $routes = (string)file_get_contents($root . '/appinfo/routes.php');
    $controller = (string)file_get_contents($root . '/lib/Controller/RoleSettingsController.php');
    assertTrue(str_contains($routes, 'roleSettings#current'));
    assertTrue(str_contains($routes, 'roleSettings#update'));
    assertTrue(str_contains($controller, 'AuthorizationService::ADMIN'));
    assertTrue(str_contains($controller, 'Http::STATUS_FORBIDDEN'));
    assertTrue(!is_file($root . '/lib/Migration/Version000003Date202608240101.php'));
});

TestRunner::test('page response does not expose teaching data without teaching capability', static function (): void {
    $page = (string)file_get_contents(dirname(__DIR__) . '/lib/Controller/PageController.php');

    assertTrue(str_contains($page, '$capabilities[\'teaching\'] ? $this->teaching->lecturers() : []'));
    assertTrue(str_contains($page, '$capabilities[\'teaching\'] ? $this->teaching->requests() : []'));
    assertTrue(str_contains($page, '$hasBqAccess ? array_map'));
    assertTrue(str_contains($page, "'settings' => \$hasBqAccess ? \$this->settingsService->current() : []"));
});
