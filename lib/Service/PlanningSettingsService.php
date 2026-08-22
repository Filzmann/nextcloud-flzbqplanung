<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Service;

use DomainException;
use OCA\AdBqPlanning\AppInfo\Application;
use OCA\AdBqPlanning\Domain\Scheduling\PlanningRules;
use OCP\IAppConfig;

final class PlanningSettingsService {
    public function __construct(private IAppConfig $config) {
    }

    /** @return array{workdayCount:int,startWeekday:int,defaultCapacity:int,reflectionMonthOffsets:list<int>,bridgeDays:list<string>} */
    public function current(): array {
        $offsets = array_values(array_filter(array_map(
            static fn (string $value): int => (int)trim($value),
            explode(',', $this->config->getValueString(Application::APP_ID, 'reflection_month_offsets', '1,3,4')),
        ), static fn (int $value): bool => $value > 0));
        $rules = new PlanningRules(
            $this->config->getValueInt(Application::APP_ID, 'workday_count', 7),
            $this->config->getValueInt(Application::APP_ID, 'start_weekday', 5),
            $offsets,
        );
        $capacity = $this->config->getValueInt(Application::APP_ID, 'default_capacity', 10);
        if ($capacity < 1 || $capacity > RunService::MAX_CAPACITY) {
            throw new DomainException('Die gespeicherte Standardkapazität ist ungültig.');
        }
        return [
            'workdayCount' => $rules->workdayCount,
            'startWeekday' => $rules->startWeekday,
            'defaultCapacity' => $capacity,
            'reflectionMonthOffsets' => $rules->reflectionMonthOffsets,
            'bridgeDays' => $this->normalizeBridgeDays(array_filter(array_map(
                'trim',
                explode(',', $this->config->getValueString(Application::APP_ID, 'bridge_days', '')),
            ))),
        ];
    }

    public function rules(): PlanningRules {
        $current = $this->current();
        return new PlanningRules(
            $current['workdayCount'],
            $current['startWeekday'],
            $current['reflectionMonthOffsets'],
        );
    }

    /** @param list<int> $reflectionMonthOffsets
     * @param list<string> $bridgeDays
     * @return array{workdayCount:int,startWeekday:int,defaultCapacity:int,reflectionMonthOffsets:list<int>,bridgeDays:list<string>}
     */
    public function update(
        int $workdayCount,
        int $startWeekday,
        int $defaultCapacity,
        array $reflectionMonthOffsets,
        array $bridgeDays = [],
    ): array {
        $rules = new PlanningRules($workdayCount, $startWeekday, $reflectionMonthOffsets);
        $bridgeDays = $this->normalizeBridgeDays($bridgeDays);
        if ($defaultCapacity < 1 || $defaultCapacity > RunService::MAX_CAPACITY) {
            throw new DomainException('Die Standardkapazität muss zwischen 1 und 10 liegen.');
        }

        $this->config->setValueInt(Application::APP_ID, 'workday_count', $rules->workdayCount);
        $this->config->setValueInt(Application::APP_ID, 'start_weekday', $rules->startWeekday);
        $this->config->setValueInt(Application::APP_ID, 'default_capacity', $defaultCapacity);
        $this->config->setValueString(
            Application::APP_ID,
            'reflection_month_offsets',
            implode(',', $rules->reflectionMonthOffsets),
        );
        $this->config->setValueString(Application::APP_ID, 'bridge_days', implode(',', $bridgeDays));
        return $this->current();
    }

    /** @param array<array-key,mixed> $dates
     * @return list<string>
     */
    private function normalizeBridgeDays(array $dates): array {
        if (count($dates) > 366) {
            throw new DomainException('Es können höchstens 366 Brückentage konfiguriert werden.');
        }
        $normalized = [];
        foreach ($dates as $value) {
            if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
                throw new DomainException('Brückentage benötigen gültige ISO-Daten im Format JJJJ-MM-TT.');
            }
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if ($date === false || $date->format('Y-m-d') !== $value) {
                throw new DomainException('Brückentage benötigen gültige ISO-Daten im Format JJJJ-MM-TT.');
            }
            $normalized[$value] = true;
        }
        $values = array_keys($normalized);
        sort($values);
        return $values;
    }
}
