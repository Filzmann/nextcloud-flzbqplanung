<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IDBConnection::class)) {
        interface IDBConnection { public function getQueryBuilder(); }
    }
}

namespace OCP\DB\QueryBuilder {
    if (!interface_exists(IQueryBuilder::class)) {
        interface IQueryBuilder {
            public const PARAM_INT = 1;
            public const PARAM_STR = 2;
            public const PARAM_NULL = 3;
            public const PARAM_DATETIME_IMMUTABLE = 4;
        }
    }
}

namespace OCP\AppFramework\Utility {
    if (!interface_exists(ITimeFactory::class)) {
        interface ITimeFactory {}
    }
}

namespace FlzBqPlanning\Tests {
    use DateTimeImmutable;
    use DomainException;
    use OCA\FlzBqPlanning\Repository\RunRepository;
    use OCA\FlzBqPlanning\Repository\TeachingRepository;
    use OCP\AppFramework\Utility\ITimeFactory;
    use FlzBqPlanning\Tests\Support\InMemoryDatabase;

    final class RepositoryClock implements ITimeFactory {
        public function getTime(): int { return 1786096800; }
        public function now(): DateTimeImmutable { return new DateTimeImmutable('@' . $this->getTime()); }
    }

    TestRunner::test('run repository persists maps orders and protects optimistic updates transactionally', static function (): void {
        $db = new InMemoryDatabase();
        $repository = new RunRepository($db, new RepositoryClock());

        $runId = $repository->createRun([
            'label' => 'BQ Herbst', 'startsOn' => '2026-09-04', 'endsOn' => '2026-09-14',
            'capacity' => 10, 'createdBy' => 'planner-a',
        ]);
        assertSame(1, $runId);
        assertSame('2026-09-04', $repository->run($runId)['startsOn']);
        assertSame(null, $repository->run($runId)['leadLecturerId']);

        $updated = $repository->updateRun($runId, [
            'label' => 'BQ Herbst aktualisiert', 'startsOn' => '2026-09-04',
            'endsOn' => '2026-09-14', 'capacity' => 9,
        ], 1, 'planner-b');
        assertSame(2, $updated['version']);
        assertSame(9, $updated['capacity']);
        assertThrows(static fn () => $repository->updateRun($runId, [
            'label' => 'Veraltet', 'startsOn' => '2026-09-04', 'endsOn' => '2026-09-14', 'capacity' => 8,
        ], 1, 'planner-b'), DomainException::class);

        $first = $repository->addModule($runId, [
            'moduleKey' => 'recht', 'title' => 'Recht', 'minutes' => 60,
            'date' => '2026-09-07', 'startsAt' => '09:00', 'endsAt' => '10:00', 'additionalCapacity' => 0,
        ], 2, 'planner-a');
        $second = $repository->addModule($runId, [
            'moduleKey' => 'praxis', 'title' => 'Praxis', 'minutes' => 90,
            'date' => '2026-09-08', 'startsAt' => '10:00', 'endsAt' => '11:30', 'additionalCapacity' => 2,
        ], 3, 'planner-a');
        assertSame([$first, $second], array_column($repository->modules($runId), 'id'));

        $beforeRollback = $db->rows;
        assertThrows(static fn () => $repository->updateModule($runId, $first, [
            'title' => 'Manipuliert', 'minutes' => 60, 'date' => '2026-09-07',
            'startsAt' => '09:00', 'endsAt' => '10:00', 'additionalCapacity' => 0,
        ], 4, 99, 'planner-a'), DomainException::class);
        assertSame($beforeRollback, $db->rows, 'A rejected stale module update must leave no run mutation');

        $module = $repository->updateModule($runId, $first, [
            'title' => 'Arbeitsrecht', 'minutes' => 60, 'date' => '2026-09-07',
            'startsAt' => '09:00', 'endsAt' => '10:00', 'additionalCapacity' => 1,
        ], 4, 1, 'planner-a');
        assertSame('Arbeitsrecht', $module['title']);
        assertSame(2, $module['version']);

        $ordered = $repository->reorderModules($runId, [$second, $first], 5, 'planner-a');
        assertSame([$second, $first], array_column($ordered, 'id'));
        $beforeRollback = $db->rows;
        assertThrows(static fn () => $repository->reorderModules($runId, [999, $first], 6, 'planner-a'), DomainException::class);
        assertSame($beforeRollback, $db->rows, 'An unknown module ID must roll back the complete reorder');

        $published = $repository->changeStatus($runId, 'draft', 'published', 6, 'publisher-a');
        assertSame('published', $published['status']);
        assertThrows(static fn () => $repository->changeStatus($runId, 'draft', 'published', 6, 'publisher-a'), DomainException::class);
        assertThrows(static fn () => $repository->run(999), DomainException::class);
        assertTrue($db->commits >= 4);
        assertTrue($db->rollbacks >= 2);
    });

    TestRunner::test('teaching repository preserves minimal profiles and rolls back stale request writes', static function (): void {
        $db = new InMemoryDatabase();
        $clock = new RepositoryClock();
        $runs = new RunRepository($db, $clock);
        $teaching = new TeachingRepository($db, $clock);
        $runId = $runs->createRun([
            'label' => 'BQ Winter', 'startsOn' => '2026-11-06', 'endsOn' => '2026-11-16',
            'capacity' => 10, 'createdBy' => 'planner-a',
        ]);
        $moduleId = $runs->addModule($runId, [
            'moduleKey' => 'kommunikation', 'title' => 'Kommunikation', 'minutes' => 60,
            'date' => '2026-11-09', 'startsAt' => '09:00', 'endsAt' => '10:00', 'additionalCapacity' => 0,
        ], 1, 'planner-a');
        $internalId = $teaching->createLecturer([
            'kind' => 'internal', 'nextcloudUid' => 'pfk-a', 'displayName' => null,
            'email' => null, 'createdBy' => 'teacher-admin',
        ]);
        $externalId = $teaching->createLecturer([
            'kind' => 'external', 'nextcloudUid' => null, 'displayName' => 'Externe Lehrkraft',
            'email' => 'lehrkraft@example.invalid', 'createdBy' => 'teacher-admin',
        ]);
        assertSame(['external', 'internal'], array_column($teaching->lecturers(), 'kind'));
        assertSame(null, $teaching->lecturer($internalId)['email']);
        assertSame('Externe Lehrkraft', $teaching->lecturer($externalId)['displayName']);
        assertThrows(static fn () => $teaching->lecturer(999), DomainException::class);

        $lead = $teaching->setLead($runId, $internalId, 2, 'teacher-admin');
        assertSame($internalId, $lead['leadLecturerId']);
        assertThrows(static fn () => $teaching->setLead($runId, $internalId, 2, 'teacher-admin'), DomainException::class);

        $beforeRollback = $db->rows;
        assertThrows(static fn () => $teaching->createRequest([
            'moduleId' => $moduleId, 'lecturerId' => $externalId,
        ], 3, 99, 'teacher-admin'), DomainException::class);
        assertSame($beforeRollback, $db->rows, 'A stale request must not increment the run version');

        $requestId = $teaching->createRequest([
            'moduleId' => $moduleId, 'lecturerId' => $externalId,
        ], 3, 1, 'teacher-admin');
        assertSame('requested', $teaching->activeRequestForModule($moduleId)['status']);
        assertSame($requestId, $teaching->requests()[0]['id']);
        assertSame(null, $teaching->activeRequestForModule(999));

        $confirmed = $teaching->transitionRequest($requestId, 'requested', 'confirmed', 1, 'teacher-admin', 'assign');
        assertSame('confirmed', $confirmed['status']);
        assertSame($externalId, $teaching->module($moduleId)['lecturerId']);
        $cancelled = $teaching->transitionRequest($requestId, 'confirmed', 'cancelled', 2, 'teacher-admin', 'clear');
        assertSame('cancelled', $cancelled['status']);
        assertSame(null, $teaching->module($moduleId)['lecturerId']);

        $beforeRollback = $db->rows;
        assertThrows(static fn () => $teaching->transitionRequest($requestId, 'confirmed', 'cancelled', 2, 'teacher-admin', 'none'), DomainException::class);
        assertSame($beforeRollback, $db->rows, 'A stale transition must leave request and assignment unchanged');
        assertThrows(static fn () => $teaching->request(999), DomainException::class);
    });
}
