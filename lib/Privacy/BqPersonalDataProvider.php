<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Privacy;

use InvalidArgumentException;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataEntry;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\ProviderDescriptor;

final class BqPersonalDataProvider implements PersonalDataProvider {
    public function __construct(private BqPrivacySource $source) {}

    public function descriptor(): ProviderDescriptor {
        return new ProviderDescriptor('adbqplanung', 'AD BQ-Planer', '1.0', ['nextcloud-user'], ['personal-data'], 500);
    }

    public function collect(PersonalDataRequest $request): PersonalDataPage {
        if ($request->subject()->subjectType() !== 'nextcloud-user') return new PersonalDataPage('not_applicable');
        if ($request->cursor() !== null) throw new InvalidArgumentException('AD BQ-Planer does not support cursor paging.');
        $rows = $this->source->forSubject($request->subject()->subjectId(), $request->pageLimit() + 1);
        $limited = count($rows) > $request->pageLimit();
        if ($limited) $rows = array_slice($rows, 0, $request->pageLimit());
        $items = array_map(fn(array $row): PersonalDataEntry => $this->item($row), $rows);
        if ($items === []) return new PersonalDataPage('not_applicable');
        return new PersonalDataPage($limited ? 'partial' : 'complete', $items, $limited ? ['Ausgabelimit erreicht; weitere interne BQ-Bezüge können vorhanden sein.'] : []);
    }

    private function item(array $row): PersonalDataEntry {
        return match ((string)$row['kind']) {
            'profile' => $this->entry('lecturer_profile', 'Internes Dozentinnenprofil', $row, 'Internes Dozentinnenprofil', 'Verwaltung des internen Lehrendenpools', [
                'Aktiv'=>(bool)$row['active'], 'Angelegt am'=>self::date($row['created_at']), 'Geändert am'=>self::date($row['updated_at']),
            ]),
            'lead' => $this->entry('lead_assignment', 'Hauptdozentinnen-Zuordnung', $row, 'Hauptdozentin für ' . (string)$row['label'], 'Planung der durchgängigen fachlichen Leitung eines BQ-Durchlaufs', [
                'Durchlauf'=>(string)$row['label'], 'Beginn'=>self::date($row['starts_on']), 'Ende'=>self::date($row['ends_on']), 'Status'=>(string)$row['status'],
            ]),
            'module' => $this->entry('module_assignment', 'Modulzuordnung', $row, (string)$row['title'] . ' am ' . self::date($row['module_date']), 'Planung einer konkreten Lehrtätigkeit im BQ-Curriculum', [
                'Modul'=>(string)$row['title'], 'Datum'=>self::date($row['module_date']), 'Beginn'=>(string)$row['starts_at'], 'Ende'=>(string)$row['ends_at'],
            ]),
            default => $this->activity($row),
        };
    }

    private function activity(array $row): PersonalDataEntry {
        $label = match ((string)$row['activity']) { 'run_updated'=>'Durchlauf bearbeitet', 'module_created'=>'Curriculum-Modul angelegt', 'lecturer_updated'=>'Dozentinnenprofil bearbeitet', default=>'Dozentinnenanfrage bearbeitet' };
        return $this->entry('planning_activity', 'Bearbeitungsnachweis', $row, $label, 'Nachvollziehbarkeit administrativer BQ-Planung', ['Vorgang'=>$label, 'Zeitpunkt'=>self::date($row['occurred_at'])], (string)$row['activity']);
    }

    private function entry(string $category, string $label, array $row, string $summary, string $purpose, array $attributes, ?string $referenceKind = null): PersonalDataEntry {
        return new PersonalDataEntry(
            categoryId:$category, categoryLabel:$label, reference:($referenceKind ?? $category) . ':' . (int)$row['id'], summary:$summary,
            purpose:$purpose, source:'BQ-Planungsdaten mit explizitem Bezug zur angefragten Nextcloud-UID',
            recipientCategories:['Nextcloud-Administrierende und künftig ausdrücklich berechtigte BQ-Planungsverantwortliche'],
            retention:'Keine feste Löschfrist festgelegt; Retention-Trigger und Maßnahmen sind noch fachlich zu entscheiden.',
            thirdCountryTransfer:'Der BQ-Planer selbst sieht keine Drittlandübermittlung vor.',
            automatedDecision:'Planungsvorschläge unterstützen die Terminierung; es findet keine automatisierte Entscheidung mit rechtlicher oder vergleichbar erheblicher Wirkung statt.',
            thirdPartyContentNotice:'Andere interne oder externe Lehrkräfte und deren Kontaktdaten werden nicht ausgegeben.', attributes:$attributes,
        );
    }

    private static function date(mixed $value): string {
        if ($value instanceof \DateTimeInterface) return $value->format('d.m.Y');
        $timestamp = strtotime((string)$value);
        return $timestamp === false ? (string)$value : date('d.m.Y', $timestamp);
    }
}
