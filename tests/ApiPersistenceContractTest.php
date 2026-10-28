<?php

declare(strict_types=1);

namespace FlzBqPlanning\Tests;

TestRunner::test('admin API exposes runs modules publication and settings without a waitlist endpoint', static function (): void {
    $root = dirname(__DIR__);
    $routes = (string)file_get_contents($root . '/appinfo/routes.php');
    $controllerPath = $root . '/lib/Controller/RunController.php';
    assertTrue(is_file($controllerPath), 'RunController is missing');
    $controller = (string)file_get_contents($controllerPath);
    foreach (['run#list', 'run#create', 'run#update', 'run#addModule', 'run#updateModule', 'run#moveModule', 'run#publish', 'run#settings', 'run#updateSettings', 'proposal#suggest', 'proposal#suggestYear'] as $route) {
        assertTrue(str_contains($routes, $route), 'Missing route ' . $route);
    }
    assertTrue(!str_contains(strtolower($routes), 'waitlist'));
    assertTrue(str_contains($controller, 'NoAdminRequired'));
    assertTrue(!str_contains($controller, '#[NoCSRFRequired]\n    public function create'));
    assertTrue(str_contains($controller, 'LoggerInterface'), 'Unexpected API failures are not connected to Nextcloud logging');
    assertTrue(str_contains($controller, "->error('Unexpected BQ planning failure.'"), 'Unexpected API failures are silently swallowed');
    $proposalController = (string)file_get_contents($root . '/lib/Controller/ProposalController.php');
    assertTrue(str_contains($proposalController, 'NoAdminRequired'));
    assertTrue(str_contains($proposalController, 'Unexpected BQ proposal failure.'));
});

TestRunner::test('planning API exposes the central curriculum template without weakening CSRF protection', static function (): void {
    $root = dirname(__DIR__);
    $routes = (string)file_get_contents($root . '/appinfo/routes.php');
    $controllerPath = $root . '/lib/Controller/CurriculumTemplateController.php';

    assertTrue(str_contains($routes, 'curriculumTemplate#current'), 'The read-only curriculum-template route is missing');
    assertTrue(str_contains($routes, 'curriculumTemplate#save'), 'The curriculum-template write route is missing');
    assertTrue(is_file($controllerPath), 'CurriculumTemplateController is missing');
    $controller = (string)file_get_contents($controllerPath);
    assertTrue(str_contains($controller, 'AuthorizationService::PLANNING'));
    assertTrue(str_contains($controller, 'NoAdminRequired'));
    assertTrue(str_contains($controller, 'LoggerInterface'));
    assertTrue(str_contains($controller, 'Unexpected BQ curriculum-template failure.'));
});

TestRunner::test('repository uses bound parameters and optimistic run versions', static function (): void {
    $root = dirname(__DIR__);
    $repositoryPath = $root . '/lib/Repository/RunRepository.php';
    assertTrue(is_file($repositoryPath), 'RunRepository is missing');
    $source = (string)file_get_contents($repositoryPath);
    assertTrue(str_contains($source, 'createNamedParameter'));
    assertTrue(str_contains($source, "'version'"));
    assertTrue(str_contains($source, 'beginTransaction'));
    assertTrue(str_contains($source, 'rollBack'));
    assertTrue(str_contains($source, 'updateModule'));
    assertTrue(!str_contains($source, 'SELECT '));
    assertTrue(!str_contains($source, 'INSERT '));
});
