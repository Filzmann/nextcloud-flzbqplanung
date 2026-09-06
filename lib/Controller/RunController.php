<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Controller;

use DomainException;
use OCA\AdBqPlanning\AppInfo\Application;
use OCA\AdBqPlanning\Exception\AccessDeniedException;
use OCA\AdBqPlanning\Repository\RunRepository;
use OCA\AdBqPlanning\Service\PlanningSettingsService;
use OCA\AdBqPlanning\Service\RunService;
use OCA\AdBqPlanning\Service\AuthorizationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

final class RunController extends Controller {
    public function __construct(
        IRequest $request,
        private RunRepository $runs,
        private RunService $service,
        private PlanningSettingsService $settingsService,
        private AuthorizationService $authorization,
        private LoggerInterface $logger,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    public function list(): JSONResponse {
        return $this->respond(null, function (): array {
            return array_map(function (array $run): array {
                $run['modules'] = $this->runs->modules((int)$run['id']);
                return $run;
            }, $this->runs->runs());
        });
    }

    #[NoAdminRequired]
    public function create(string $label, string $startsOn, string $endsOn, int $capacity): JSONResponse {
        return $this->respond(AuthorizationService::PLANNING, fn (): array => [
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

    #[NoAdminRequired]
    public function update(
        int $id,
        string $label,
        string $startsOn,
        string $endsOn,
        int $capacity,
        int $version,
    ): JSONResponse {
        return $this->respond(AuthorizationService::PLANNING, fn (): array => $this->service->updateRun(
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

    #[NoAdminRequired]
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
        return $this->respond(AuthorizationService::PLANNING, fn (): array => [
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

    #[NoAdminRequired]
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
        return $this->respond(AuthorizationService::PLANNING, fn (): array => $this->service->updateModule(
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

    #[NoAdminRequired]
    public function moveModule(int $id, int $moduleId, string $direction, int $version): JSONResponse {
        return $this->respond(AuthorizationService::PLANNING, fn (): array => $this->service->moveModule(
            $this->runs,
            $id,
            $moduleId,
            $direction,
            $version,
            $this->actorUid(),
        ));
    }

    #[NoAdminRequired]
    public function publish(int $id, int $version): JSONResponse {
        return $this->respond(AuthorizationService::PUBLISHING, fn (): array => $this->service->publish(
            $this->runs,
            $id,
            $version,
            $this->actorUid(),
        ));
    }

    #[NoAdminRequired]
    public function settings(): JSONResponse {
        return $this->respond(AuthorizationService::ADMIN, fn (): array => $this->settingsService->current());
    }

    /** @param list<int> $reflectionMonthOffsets */
    #[NoAdminRequired]
    public function updateSettings(
        int $workdayCount,
        int $startWeekday,
        int $defaultCapacity,
        array $reflectionMonthOffsets,
        array $bridgeDays = [],
    ): JSONResponse {
        return $this->respond(AuthorizationService::ADMIN, fn (): array => $this->settingsService->update(
            $workdayCount,
            $startWeekday,
            $defaultCapacity,
            $reflectionMonthOffsets,
            $bridgeDays,
        ));
    }

    private function actorUid(): string {
        return $this->authorization->actorUid();
    }

    private function respond(?string $capability, callable $callback): JSONResponse {
        try {
            return new JSONResponse(['data' => $this->authorization->execute($capability, $callback)]);
        } catch (AccessDeniedException $error) {
            return new JSONResponse(['error' => $error->getMessage()], Http::STATUS_FORBIDDEN);
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
