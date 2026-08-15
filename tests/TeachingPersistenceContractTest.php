<?php

declare(strict_types=1);

namespace AdBqPlanning\Tests;

TestRunner::test('teaching persistence is additive minimal and contains no applicant data', static function (): void {
    $root = dirname(__DIR__);
    $migration = $root . '/lib/Migration/Version000002Date202608150202.php';
    assertTrue(is_file($migration), 'Teaching migration is missing');
    $source = (string)file_get_contents($migration);
    foreach (['adbq_lecturers', 'adbq_lecturer_requests', "'lead_lecturer_id'", "'lecturer_id'"] as $expected) {
        assertTrue(str_contains($source, $expected), 'Missing teaching schema part ' . $expected);
    }
    assertTrue(!str_contains(strtolower($source), 'applicant'));
    assertTrue(!str_contains(strtolower($source), 'candidate'));
    assertTrue(!str_contains(strtolower($source), 'request_message'));
});

TestRunner::test('admin teaching API exposes pool lead request and transition without public access', static function (): void {
    $root = dirname(__DIR__);
    $routes = (string)file_get_contents($root . '/appinfo/routes.php');
    $controllerPath = $root . '/lib/Controller/TeachingController.php';
    assertTrue(is_file($controllerPath), 'TeachingController is missing');
    $controller = (string)file_get_contents($controllerPath);
    foreach (['teaching#lecturers', 'teaching#createLecturer', 'teaching#setLead', 'teaching#createRequest', 'teaching#transitionRequest'] as $route) {
        assertTrue(str_contains($routes, $route), 'Missing route ' . $route);
    }
    assertTrue(!str_contains($controller, 'NoAdminRequired'));
    assertTrue(str_contains($controller, 'LoggerInterface'));
    assertTrue(str_contains($controller, 'IUserManager'), 'Internal PFK identities are not checked against Nextcloud');
    assertTrue(str_contains($controller, 'userExists($nextcloudUid)'), 'Unknown Nextcloud UIDs can enter the lecturer pool');
});
