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

    /** @return array{workdayCount:int,startWeekday:int,defaultCapacity:int,reflectionMonthOffsets:list<int>} */
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
     * @return array{workdayCount:int,startWeekday:int,defaultCapacity:int,reflectionMonthOffsets:list<int>}
     */
    public function update(
        int $workdayCount,
        int $startWeekday,
        int $defaultCapacity,
        array $reflectionMonthOffsets,
    ): array {
        $rules = new PlanningRules($workdayCount, $startWeekday, $reflectionMonthOffsets);
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
        return $this->current();
    }
}

