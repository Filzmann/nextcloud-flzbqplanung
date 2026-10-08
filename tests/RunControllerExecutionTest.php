<?php

declare(strict_types=1);

namespace FlzBqPlanning\Tests;

use FlzBqPlanning\Tests\Support\InMemoryDatabase;
use OCA\FlzBqPlanning\Controller\RunController;
use OCA\FlzBqPlanning\Repository\RunRepository;
use OCA\FlzBqPlanning\Service\AuthorizationService;
use OCA\FlzBqPlanning\Service\PlanningSettingsService;
use OCA\FlzBqPlanning\Service\RunService;
use OCP\IAppConfig;
use OCP\IRequest;

final class RunControllerConfig implements IAppConfig {
    /** @var array<string,int|string> */
    public array $values = [];
    public function getValueInt(string $appId, string $key, int $default): int {
        return (int)($this->values[$key] ?? $default);
    }
    public function getValueString(string $appId, string $key, string $default): string {
        return (string)($this->values[$key] ?? $default);
    }
    public function setValueInt(string $appId, string $key, int $value): void { $this->values[$key] = $value; }
    public function setValueString(string $appId, string $key, string $value): void { $this->values[$key] = $value; }
}

/** @return array{0:RunController,1:InMemoryDatabase,2:ProposalControllerLogger} */
function runControllerFixture(?AuthorizationService $authorization = null): array {
    $database = new InMemoryDatabase();
    $config = new RunControllerConfig();
    $logger = new ProposalControllerLogger();
    return [
        new RunController(
            new class implements IRequest {},
            new RunRepository($database, new RepositoryClock()),
            new RunService(),
            new PlanningSettingsService($config),
            $authorization ?? adminAuthorization(),
            $logger,
        ),
        $database,
        $logger,
    ];
}

TestRunner::test('run controller executes the complete protected draft lifecycle', static function (): void {
    [$controller] = runControllerFixture();

    $settings = $controller->settings();
    assertSame(200, $settings->status);
    assertSame(7, $settings->data['data']['workdayCount']);
    $updatedSettings = $controller->updateSettings(7, 5, 10, [1, 3, 4], ['2026-05-15']);
    assertSame(['2026-05-15'], $updatedSettings->data['data']['bridgeDays']);

    $created = $controller->create('BQ Herbst', '2026-09-04', '2026-09-14', 10);
    assertSame(200, $created->status);
    $runId = $created->data['data']['id'];

    $updated = $controller->update($runId, 'BQ Herbst 2026', '2026-09-04', '2026-09-14', 9, 1);
    assertSame(2, $updated->data['data']['version']);

    $first = $controller->addModule(
        $runId, 'recht', 'Recht', 60, '2026-09-07', '09:00', '10:00', 0, 2,
    );
    $second = $controller->addModule(
        $runId, 'praxis', 'Praxis', 90, '2026-09-08', '10:00', '11:30', 2, 3,
    );
    assertSame(200, $first->status);
    assertSame(200, $second->status);

    $firstModuleId = $first->data['data']['id'];
    $secondModuleId = $second->data['data']['id'];
    $module = $controller->updateModule(
        $runId, $firstModuleId, 'Arbeitsrecht', 60, '2026-09-07', '09:00', '10:00', 1, 4, 1,
    );
    assertSame('Arbeitsrecht', $module->data['data']['title']);

    $moved = $controller->moveModule($runId, $secondModuleId, 'up', 5);
    assertSame([$secondModuleId, $firstModuleId], array_column($moved->data['data'], 'id'));

    $listed = $controller->list();
    assertSame(2, count($listed->data['data'][0]['modules']));

    $published = $controller->publish($runId, 6);
    assertSame('published', $published->data['data']['status']);
});

TestRunner::test('run controller denies mutations before execution and classifies safe failures', static function (): void {
    $deniedAuthorization = new AuthorizationService(
        new FrameworkSession(new FrameworkUser('unassigned')),
        new FrameworkGroups(false),
        new FrameworkConfig(),
    );
    [$denied, $deniedDatabase] = runControllerFixture($deniedAuthorization);
    $forbidden = $denied->create('Nicht erlaubt', '2026-09-04', '2026-09-14', 10);
    assertSame(403, $forbidden->status);
    assertSame([], $deniedDatabase->rows);

    [$controller, $database, $logger] = runControllerFixture();
    $invalid = $controller->create('Ungültig', '2026-09-04', '2026-09-14', 11);
    assertSame(422, $invalid->status);
    assertSame([], $database->rows);

    $database->failNextBuilder = true;
    $failed = $controller->list();
    assertSame(500, $failed->status);
    assertSame('Die BQ-Planung konnte nicht verarbeitet werden.', $failed->data['error']);
    assertTrue($logger->errors !== []);
});
