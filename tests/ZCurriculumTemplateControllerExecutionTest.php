<?php

declare(strict_types=1);

namespace FlzBqPlanning\Tests;

use FlzBqPlanning\Tests\Support\InMemoryDatabase;
use OCA\FlzBqPlanning\Controller\CurriculumTemplateController;
use OCA\FlzBqPlanning\Repository\CurriculumTemplateRepository;
use OCA\FlzBqPlanning\Service\AuthorizationService;
use OCA\FlzBqPlanning\Service\CurriculumTemplateService;
use OCP\IRequest;

TestRunner::test('curriculum template API saves semantic modules with planning authorization and optimistic versioning', static function (): void {
    assertTrue(class_exists(CurriculumTemplateController::class), 'CurriculumTemplateController is missing');

    $database = new InMemoryDatabase();
    $logger = new ProposalControllerLogger();
    $controller = new CurriculumTemplateController(
        new class implements IRequest {},
        new CurriculumTemplateRepository($database, new RepositoryClock()),
        new CurriculumTemplateService(),
        adminAuthorization(),
        $logger,
    );

    $empty = $controller->current();
    assertSame(200, $empty->status);
    assertSame(null, $empty->data['data']);

    $saved = $controller->save('Basisqualifizierung', [
        ['moduleKey' => 'grundlagen', 'title' => 'Grundlagen', 'minutes' => 120],
        ['moduleKey' => 'recht', 'title' => 'Recht', 'minutes' => 90],
    ], 0);
    assertSame(200, $saved->status);
    assertSame(1, $saved->data['data']['version']);
    assertSame(['grundlagen', 'recht'], array_column($saved->data['data']['modules'], 'moduleKey'));
    assertTrue(!array_key_exists('date', $saved->data['data']['modules'][0]));

    $beforeRejected = $database->rows;
    $stale = $controller->save('Veraltet', [
        ['moduleKey' => 'grundlagen', 'title' => 'Manipuliert', 'minutes' => 120],
    ], 0);
    assertSame(422, $stale->status);
    assertSame($beforeRejected, $database->rows);

    $invalid = $controller->save('Leer', [], 1);
    assertSame(422, $invalid->status);
    assertSame($beforeRejected, $database->rows);
});

TestRunner::test('curriculum template API rejects unassigned actors before persistence and logs unexpected failures safely', static function (): void {
    assertTrue(class_exists(CurriculumTemplateController::class), 'CurriculumTemplateController is missing');

    $database = new InMemoryDatabase();
    $logger = new ProposalControllerLogger();
    $denied = new CurriculumTemplateController(
        new class implements IRequest {},
        new CurriculumTemplateRepository($database, new RepositoryClock()),
        new CurriculumTemplateService(),
        new AuthorizationService(
            new FrameworkSession(new FrameworkUser('unassigned')),
            new FrameworkGroups(false),
            new FrameworkConfig(),
        ),
        $logger,
    );
    $forbidden = $denied->save('Nicht erlaubt', [
        ['moduleKey' => 'grundlagen', 'title' => 'Grundlagen', 'minutes' => 120],
    ], 0);
    assertSame(403, $forbidden->status);
    assertSame([], $database->rows);

    $allowed = new CurriculumTemplateController(
        new class implements IRequest {},
        new CurriculumTemplateRepository($database, new RepositoryClock()),
        new CurriculumTemplateService(),
        adminAuthorization(),
        $logger,
    );
    $database->failNextBuilder = true;
    $failed = $allowed->current();
    assertSame(500, $failed->status);
    assertSame('Die Curriculum-Vorlage konnte nicht verarbeitet werden.', $failed->data['error']);
    assertTrue($logger->errors !== []);
});
