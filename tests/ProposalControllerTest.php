<?php

declare(strict_types=1);

namespace AdBqPlanning\Tests;

use OCA\AdBqPlanning\Contract\BlockedPeriodProvider;
use OCA\AdBqPlanning\Controller\ProposalController;
use OCA\AdBqPlanning\Domain\Scheduling\BlockedCalendar;
use OCA\AdBqPlanning\Domain\Scheduling\CalendarCoverage;
use OCA\AdBqPlanning\Domain\Scheduling\DateProposalService;
use OCA\AdBqPlanning\Service\CalendarProposalService;
use OCA\AdBqPlanning\Service\PlanningSettingsService;
use OCP\IAppConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

final class ControllerBlockedPeriodProvider implements BlockedPeriodProvider {
    public bool $fail = false;

    public function forProposalMonth(int $year, int $month, array $bridgeDays): CalendarCoverage {
        if ($this->fail) {
            throw new \RuntimeException('synthetischer unerwarteter Fehler');
        }
        return new CalendarCoverage('current', true, true, new BlockedCalendar([]));
    }
}

final class ProposalControllerConfig implements IAppConfig {
    public function getValueInt(string $appId, string $key, int $default): int { return $default; }
    public function getValueString(string $appId, string $key, string $default): string { return $default; }
    public function setValueInt(string $appId, string $key, int $value): void {}
    public function setValueString(string $appId, string $key, string $value): void {}
}

final class ProposalControllerLogger implements LoggerInterface {
    public array $errors = [];
    public function warning(string $message, array $context = []): void {}
    public function error(string $message, array $context = []): void { $this->errors[] = [$message, $context]; }
}

TestRunner::test('proposal controller returns checked data validation errors and safe unexpected failures', static function (): void {
    $provider = new ControllerBlockedPeriodProvider();
    $logger = new ProposalControllerLogger();
    $controller = new ProposalController(
        new class implements IRequest {},
        new CalendarProposalService($provider, new DateProposalService()),
        new PlanningSettingsService(new ProposalControllerConfig()),
        $logger,
    );

    $success = $controller->suggest(2026, 9);
    assertSame(200, $success->status);
    assertSame('2026-09-04', $success->data['data']['proposal']['startsOn']);
    assertSame(true, $success->data['data']['calendar']['complete']);

    $invalid = $controller->suggest(2026, 13);
    assertSame(422, $invalid->status);
    assertTrue(isset($invalid->data['error']));

    $provider->fail = true;
    $failed = $controller->suggest(2026, 9);
    assertSame(500, $failed->status);
    assertSame('Der BQ-Terminvorschlag konnte nicht erstellt werden.', $failed->data['error']);
    assertTrue($logger->errors !== []);
});
