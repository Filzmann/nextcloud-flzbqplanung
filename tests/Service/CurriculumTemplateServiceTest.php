<?php

declare(strict_types=1);

namespace FlzBqPlanning\Tests;

use DomainException;
use OCA\FlzBqPlanning\Contract\CurriculumTemplateStore;
use OCA\FlzBqPlanning\Service\CurriculumTemplateService;

final class MemoryCurriculumTemplateStore implements CurriculumTemplateStore {
    /** @var array<string,mixed>|null */
    public ?array $value = null;
    public int $writes = 0;

    public function current(): ?array { return $this->value; }

    public function save(string $title, array $modules, int $expectedVersion, string $actorUid): array {
        if (($this->value === null && $expectedVersion !== 0)
            || ($this->value !== null && (int)$this->value['version'] !== $expectedVersion)) {
            throw new DomainException('stale');
        }
        $this->writes++;
        $this->value = [
            'id' => 1,
            'key' => 'central',
            'title' => $title,
            'version' => $expectedVersion + 1,
            'modules' => array_values($modules),
        ];
        return $this->value;
    }
}

TestRunner::test('central curriculum template validates semantic modules and versions writes', static function (): void {
    $store = new MemoryCurriculumTemplateStore();
    $service = new CurriculumTemplateService();
    $created = $service->save($store, 'Basisqualifizierung', [
        ['moduleKey' => 'pflege', 'title' => 'Pflege', 'minutes' => 180],
        ['moduleKey' => 'recht', 'title' => 'Recht', 'minutes' => 90],
    ], 0, 'planner-a');
    assertSame(1, $created['version']);
    assertSame(['pflege', 'recht'], array_column($created['modules'], 'moduleKey'));

    $updated = $service->save($store, 'Basisqualifizierung 2027', [
        ['moduleKey' => 'recht', 'title' => 'Arbeitsrecht', 'minutes' => 120],
    ], 1, 'planner-b');
    assertSame(2, $updated['version']);
    assertSame('Arbeitsrecht', $updated['modules'][0]['title']);
});

TestRunner::test('invalid duplicate and stale curriculum writes have no side effect', static function (): void {
    $store = new MemoryCurriculumTemplateStore();
    $service = new CurriculumTemplateService();
    $service->save($store, 'Basisqualifizierung', [
        ['moduleKey' => 'pflege', 'title' => 'Pflege', 'minutes' => 180],
    ], 0, 'planner-a');
    $before = $store->value;

    foreach ([
        [],
        [['moduleKey' => 'PFLEGE', 'title' => 'Pflege', 'minutes' => 180]],
        [
            ['moduleKey' => 'pflege', 'title' => 'Pflege', 'minutes' => 180],
            ['moduleKey' => 'pflege', 'title' => 'Doppelt', 'minutes' => 60],
        ],
        [['moduleKey' => 'pflege', 'title' => '', 'minutes' => 180]],
        [['moduleKey' => 'pflege', 'title' => 'Pflege', 'minutes' => 0]],
    ] as $modules) {
        assertThrows(
            static fn () => $service->save($store, 'Basisqualifizierung', $modules, 1, 'planner-a'),
            DomainException::class,
        );
        assertSame($before, $store->value);
    }
    assertThrows(
        static fn () => $service->save($store, 'Basisqualifizierung', [
            ['moduleKey' => 'pflege', 'title' => 'Pflege', 'minutes' => 180],
        ], 0, 'planner-a'),
        DomainException::class,
    );
    assertSame($before, $store->value);
    assertSame(1, $store->writes);
});
