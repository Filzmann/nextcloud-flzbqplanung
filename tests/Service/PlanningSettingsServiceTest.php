<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IAppConfig::class)) {
        interface IAppConfig {
            public function getValueInt(string $appId, string $key, int $default): int;
            public function getValueString(string $appId, string $key, string $default): string;
            public function setValueInt(string $appId, string $key, int $value): void;
            public function setValueString(string $appId, string $key, string $value): void;
        }
    }
}

namespace FlzBqPlanning\Tests {
    use DomainException;
    use OCA\FlzBqPlanning\Service\PlanningSettingsService;
    use OCP\IAppConfig;

    final class MemoryAppConfig implements IAppConfig {
        /** @var array<string,int|string> */
        public array $values = [];
        public int $writes = 0;

        public function getValueInt(string $appId, string $key, int $default): int {
            return (int)($this->values[$key] ?? $default);
        }

        public function getValueString(string $appId, string $key, string $default): string {
            return (string)($this->values[$key] ?? $default);
        }

        public function setValueInt(string $appId, string $key, int $value): void {
            $this->values[$key] = $value;
            $this->writes++;
        }

        public function setValueString(string $appId, string $key, string $value): void {
            $this->values[$key] = $value;
            $this->writes++;
        }
    }

    TestRunner::test('planning defaults are seven weekdays from Friday with capacity ten', static function (): void {
        $settings = new PlanningSettingsService(new MemoryAppConfig());
        $current = $settings->current();
        assertSame(7, $current['workdayCount']);
        assertSame(5, $current['startWeekday']);
        assertSame(10, $current['defaultCapacity']);
        assertSame([1, 3, 4], $current['reflectionMonthOffsets']);
        assertSame([], $current['bridgeDays']);
    });

    TestRunner::test('invalid planning settings are rejected without partial writes', static function (): void {
        $config = new MemoryAppConfig();
        $settings = new PlanningSettingsService($config);
        assertThrows(
            static fn () => $settings->update(7, 5, 11, [1, 3, 4]),
            DomainException::class,
        );
        assertSame(0, $config->writes);

        $updated = $settings->update(8, 4, 9, [1, 2, 4], ['2026-05-15', '2026-05-15', '2026-01-02']);
        assertSame(8, $updated['workdayCount']);
        assertSame(4, $updated['startWeekday']);
        assertSame(9, $updated['defaultCapacity']);
        assertSame(['2026-01-02', '2026-05-15'], $updated['bridgeDays']);
        assertSame(5, $config->writes);

        $writes = $config->writes;
        assertThrows(
            static fn () => $settings->update(7, 5, 10, [1, 3, 4], ['02.01.2026']),
            DomainException::class,
        );
        assertSame($writes, $config->writes);
    });
}
