<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Listener;

use OCA\FlzBqPlanning\Service\AuthorizationService;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\IL10N;
use OCP\EventDispatcher\IEventListener;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Navigation\Events\LoadAdditionalEntriesEvent;
use OCP\INavigationManager;

/** @template-implements IEventListener<LoadAdditionalEntriesEvent> */
final class StandaloneNavigationListener implements IEventListener {
    private const SUITE_APP_ID = 'orgsuite';

    public function __construct(
        private AuthorizationService $authorization,
        private IUserSession $userSession,
        private IAppManager $appManager,
        private INavigationManager $navigation,
        private IURLGenerator $url,
        private ?IL10N $l10n = null,
    ) {
    }

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }

    public function handle(Event $event): void {
        if (!$event instanceof LoadAdditionalEntriesEvent) {
            return;
        }
        if (!$this->authorization->hasAnyAccess()) {
            return;
        }
        $user = $this->userSession->getUser();
        if ($user === null || $this->appManager->isEnabledForUser(self::SUITE_APP_ID, $user)) return;
        $this->navigation->add(fn(): array => [
            'id' => 'flzbqplanung',
            'type' => INavigationManager::TYPE_APPS,
            'app' => 'flzbqplanung',
            'href' => $this->url->linkToRoute('flzbqplanung.page.index'),
            'icon' => $this->url->imagePath('flzbqplanung', 'app.svg'),
            'name' => $this->t('BQ-Planer'),
            'order' => 85,
        ]);
    }
}
