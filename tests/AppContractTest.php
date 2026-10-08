<?php

declare(strict_types=1);

namespace FlzBqPlanning\Tests;

TestRunner::test('app metadata and route keep the approved identity', static function (): void {
    $root = dirname(__DIR__);
    $info = (string)file_get_contents($root . '/appinfo/info.xml');
    $routes = (string)file_get_contents($root . '/appinfo/routes.php');
    assertTrue(str_contains($info, '<id>flzbqplanung</id>'));
    assertTrue(str_contains($info, '<namespace>FlzBqPlanning</namespace>'));
    assertTrue(!str_contains($info, '<navigations>'), 'Static navigation bypasses the suite contract');
    assertTrue(str_contains($routes, "'name' => 'page#index'"));
    $application = (string)file_get_contents($root . '/lib/AppInfo/Application.php');
    $listener = (string)file_get_contents($root . '/lib/Listener/StandaloneNavigationListener.php');
    assertTrue(str_contains($application, 'LoadAdditionalEntriesEvent::class'));
    assertTrue(str_contains($listener, 'StandaloneAppNavigationService'));
});

TestRunner::test('initial page remains centrally protected and documents calendar completeness', static function (): void {
    $root = dirname(__DIR__);
    $controller = (string)file_get_contents($root . '/lib/Controller/PageController.php');
    $template = (string)file_get_contents($root . '/templates/index.php');
    assertTrue(str_contains($controller, 'use OCP\\AppFramework\\Http\\Attribute\\NoAdminRequired;'));
    assertTrue(str_contains($controller, '#[NoAdminRequired]'));
    assertTrue(str_contains($controller, 'hasAnyAccess'));
    assertTrue(str_contains($controller, 'canManageAdminAccess'));
    assertTrue(str_contains($controller, 'showMissingAdminGrant'));
    assertTrue(str_contains($template, 'Automatische Vorschläge werden erst als konfliktfrei bezeichnet'));
    assertTrue(str_contains($template, 'Monat vorschlagen'));
    assertTrue(str_contains($template, 'Brückentage'));
    assertTrue(str_contains($template, 'data-orgsuite data-suite="flz" data-current-app="flzbqplanung"'));
    assertTrue(!preg_match('/data-endpoint="[^"]*(applicant|bewerb|candidate)/i', $template), 'Applicant assignment must remain in Filzmann Recruitment');
    assertTrue(!preg_match('/[\'\"]url[\'\"]\s*=>\s*[\'\"][^\'\"]*(applicant|bewerb|candidate)/i', (string)file_get_contents($root . '/appinfo/routes.php')), 'BQ planning must not expose applicant-assignment routes');
});
