<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Controller;

use DomainException;
use OCA\AdBqPlanning\AppInfo\Application;
use OCA\AdBqPlanning\Repository\RunRepository;
use OCA\AdBqPlanning\Service\PlanningSettingsService;
use OCA\AdBqPlanning\Service\RunService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

final class RunController extends Controller {
    public function __construct(
        IRequest $request,
        private RunRepository $runs,
        private RunService $service,
        private PlanningSettingsService $settingsService,
        private IUserSession $userSession,
        private LoggerInterface $logger,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    public function list(): JSONResponse {
        return $this->respond(function (): array {
            return array_map(function (array $run): array {
                $run['modules'] = $this->runs->modules((int)$run['id']);
                return $run;
            }, $this->runs->runs());
        });
    }

    public function create(string $label, string $startsOn, string $endsOn, int $capacity): JSONResponse {
        return $this->respond(fn (): array => [
            'id' => $this->service->createRun(
                $this->runs,
                $label,
                $startsOn,
                $endsOn,
                $capacity,
                $this->settingsService->rules(),
                $this->actorUid(),
            ),
        ]);
    }

    public function update(
        int $id,
        string $label,
        string $startsOn,
        string $endsOn,
        int $capacity,
        int $version,
    ): JSONResponse {
        return $this->respond(fn (): array => $this->service->updateRun(
            $this->runs,
            $id,
            $label,
            $startsOn,
            $endsOn,
            $capacity,
            $version,
            $this->settingsService->rules(),
            $this->actorUid(),
        ));
    }

    public function addModule(
        int $id,
        string $moduleKey,
        string $title,
        int $minutes,
        string $date,
        string $startsAt,
        string $endsAt,
        int $additionalCapacity,
        int $version,
    ): JSONResponse {
        return $this->respond(fn (): array => [
            'id' => $this->service->addModule(
                $this->runs,
                $id,
                $moduleKey,
                $title,
                $minutes,
                $date,
                $startsAt,
                $endsAt,
                $additionalCapacity,
                $version,
                $this->actorUid(),
            ),
        ]);
    }

    public function updateModule(
        int $id,
        int $moduleId,
        string $title,
        int $minutes,
        string $date,
        string $startsAt,
        string $endsAt,
        int $additionalCapacity,
        int $runVersion,
        int $moduleVersion,
    ): JSONResponse {
        return $this->respond(fn (): array => $this->service->updateModule(
            $this->runs,
            $id,
            $moduleId,
            $title,
            $minutes,
            $date,
            $startsAt,
            $endsAt,
            $additionalCapacity,
            $runVersion,
            $moduleVersion,
            $this->actorUid(),
        ));
    }

    public function publish(int $id, int $version): JSONResponse {
        return $this->respond(fn (): array => $this->service->publish(
            $this->runs,
            $id,
            $version,
            $this->actorUid(),
        ));
    }

    public function settings(): JSONResponse {
        return $this->respond(fn (): array => $this->settingsService->current());
    }

    /** @param list<int> $reflectionMonthOffsets */
    public function updateSettings(
        int $workdayCount,
        int $startWeekday,
        int $defaultCapacity,
        array $reflectionMonthOffsets,
        array $bridgeDays = [],
    ): JSONResponse {
        return $this->respond(fn (): array => $this->settingsService->update(
            $workdayCount,
            $startWeekday,
            $defaultCapacity,
            $reflectionMonthOffsets,
            $bridgeDays,
        ));
    }

    private function actorUid(): string {
        $uid = $this->userSession->getUser()?->getUID();
        if ($uid === null || trim($uid) === '') {
            throw new DomainException('Für diese Aktion ist eine angemeldete Person erforderlich.');
        }
        return $uid;
    }

    private function respond(callable $callback): JSONResponse {
        try {
            return new JSONResponse(['data' => $callback()]);
        } catch (DomainException $error) {
            return new JSONResponse(['error' => $error->getMessage()], 422);
        } catch (Throwable $error) {
            $this->logger->error('Unexpected BQ planning failure.', [
                'app' => Application::APP_ID,
                'exceptionClass' => $error::class,
            ]);
            return new JSONResponse(['error' => 'Die BQ-Planung konnte nicht verarbeitet werden.'], 500);
        }
    }
}
