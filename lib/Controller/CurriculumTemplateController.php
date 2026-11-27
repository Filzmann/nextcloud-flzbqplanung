<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Controller;

use DomainException;
use OCA\FlzBqPlanning\AppInfo\Application;
use OCA\FlzBqPlanning\Exception\AccessDeniedException;
use OCA\FlzBqPlanning\Repository\CurriculumTemplateRepository;
use OCA\FlzBqPlanning\Service\AuthorizationService;
use OCA\FlzBqPlanning\Service\CurriculumTemplateService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Throwable;

final class CurriculumTemplateController extends Controller {
    public function __construct(
        IRequest $request,
        private CurriculumTemplateRepository $templates,
        private CurriculumTemplateService $service,
        private AuthorizationService $authorization,
        private LoggerInterface $logger,
        private ?IL10N $l10n = null
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function current(): JSONResponse {
        return $this->respond(fn (): ?array => $this->templates->current());
    }

    /** @param list<array<string,mixed>> $modules */
    #[NoAdminRequired]
    public function save(string $title, array $modules, int $expectedVersion): JSONResponse {
        return $this->respond(fn (): array => $this->service->save(
            $this->templates,
            $title,
            $modules,
            $expectedVersion,
            $this->authorization->actorUid(),
        ));
    }

    private function respond(callable $callback): JSONResponse {
        try {
            return new JSONResponse([
                'data' => $this->authorization->execute(AuthorizationService::PLANNING, $callback),
            ]);
        } catch (AccessDeniedException $error) {
            return new JSONResponse(['error' => $this->t($error->getMessage())], Http::STATUS_FORBIDDEN);
        } catch (DomainException $error) {
            return new JSONResponse(['error' => $this->t($error->getMessage())], 422);
        } catch (Throwable $error) {
            $this->logger->error('Unexpected BQ curriculum-template failure.', [
                'app' => Application::APP_ID,
                'exceptionClass' => $error::class,
            ]);
            return new JSONResponse(['error' => $this->t('Die Curriculum-Vorlage konnte nicht verarbeitet werden.')], 500);
        }
    }
}
