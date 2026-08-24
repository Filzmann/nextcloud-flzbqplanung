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
    assertTrue(str_contains($page, 'requireAnyAccess'));
    assertTrue(str_contains($page, "'core', '403'"));
    assertTrue(str_contains($navigation, 'hasAnyAccess'));
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
});
