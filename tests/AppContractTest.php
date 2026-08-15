<?php

declare(strict_types=1);

namespace AdBqPlanning\Tests;

TestRunner::test('app metadata and route keep the approved identity', static function (): void {
    $root = dirname(__DIR__);
    $info = (string)file_get_contents($root . '/appinfo/info.xml');
    $routes = (string)file_get_contents($root . '/appinfo/routes.php');
    assertTrue(str_contains($info, '<id>adbqplanung</id>'));
    assertTrue(str_contains($info, '<namespace>AdBqPlanning</namespace>'));
    assertTrue(str_contains($routes, "'name' => 'page#index'"));
});

TestRunner::test('initial page remains admin-only and documents calendar completeness', static function (): void {
    $root = dirname(__DIR__);
    $controller = (string)file_get_contents($root . '/lib/Controller/PageController.php');
    $template = (string)file_get_contents($root . '/templates/index.php');
    assertTrue(!str_contains($controller, 'NoAdminRequired'));
    assertTrue(str_contains($template, 'Automatische Vorschläge werden erst als konfliktfrei bezeichnet'));
});
