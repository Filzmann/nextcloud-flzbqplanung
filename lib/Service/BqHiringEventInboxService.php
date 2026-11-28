<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Service;

use OCP\IAppConfig;

/**
 * ZM-07 Minimal-Vertrag (Consumer-Seite): bucht ApplicationHiredEvent-Ereignisse
 * des optionalen Providers flzrecruitment in einer begrenzten Inbox. Es werden
 * ausschließlich Vorgangs- und Organisationskennungen gespeichert (keine Namen,
 * keine Kontaktdaten); die ältesten Einträge werden bei Erreichen des Limits
 * überschrieben.
 */
final class BqHiringEventInboxService {
    public const CONFIG_KEY = 'bq_hiring_events_v1';
    public const MAX_ENTRIES = 100;

    public function __construct(private IAppConfig $config) {
    }

    public function record(int $applicationId, string $areaKey, string $occurredAt): void {
        $entries = $this->entries();
        $entries[] = [
            'applicationId' => $applicationId,
            'areaKey' => $areaKey,
            'occurredAt' => $occurredAt,
        ];
        if (count($entries) > self::MAX_ENTRIES) {
            $entries = array_slice($entries, count($entries) - self::MAX_ENTRIES);
        }
        $this->config->setValueString('flzbqplanung', self::CONFIG_KEY, (string) json_encode($entries, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /** @return list<array{applicationId: int, areaKey: string, occurredAt: string}> */
    public function entries(): array {
        $raw = $this->config->getValueString('flzbqplanung', self::CONFIG_KEY, '');
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $entries = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry)
                || !is_int($entry['applicationId'] ?? null)
                || !is_string($entry['areaKey'] ?? null)
                || !is_string($entry['occurredAt'] ?? null)) {
                continue;
            }
            $entries[] = [
                'applicationId' => $entry['applicationId'],
                'areaKey' => $entry['areaKey'],
                'occurredAt' => $entry['occurredAt'],
            ];
        }
        return $entries;
    }
}
