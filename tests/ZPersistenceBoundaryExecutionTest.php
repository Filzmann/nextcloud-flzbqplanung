<?php

declare(strict_types=1);

namespace OCP\DB {
    if (!interface_exists(ISchemaWrapper::class)) {
        interface ISchemaWrapper {
            public function hasTable(string $name): bool;
            public function createTable(string $name): mixed;
            public function getTable(string $name): mixed;
        }
    }

    if (!class_exists(Types::class)) {
        final class Types {
            public const BIGINT = 'bigint';
            public const STRING = 'string';
            public const DATE_IMMUTABLE = 'date_immutable';
            public const DATETIME_IMMUTABLE = 'datetime_immutable';
            public const SMALLINT = 'smallint';
            public const INTEGER = 'integer';
            public const BOOLEAN = 'boolean';
        }
    }
}

namespace OCP\Migration {
    if (!interface_exists(IOutput::class)) {
        interface IOutput {}
    }
    if (!class_exists(SimpleMigrationStep::class)) {
        abstract class SimpleMigrationStep {}
    }
}

namespace FlzBqPlanning\Tests {
    use DateTimeImmutable;
    use OCA\FlzBqPlanning\Migration\Version000001Date202608150101;
    use OCA\FlzBqPlanning\Migration\Version000002Date202608150202;
    use OCA\FlzBqPlanning\Migration\Version000003Date202608250001;
    use OCA\FlzBqPlanning\Repository\TemporaryAdminAccessRepository;
    use OCP\DB\ISchemaWrapper;
    use OCP\IDBConnection;
    use OCP\Migration\IOutput;
    use RuntimeException;
    use Throwable;

    final class PersistenceResult {
        /** @param list<array<string,mixed>> $rows */
        public function __construct(private array $rows) {}
        /** @return list<array<string,mixed>> */
        public function fetchAllAssociative(): array { return $this->rows; }
        public function fetchAssociative(): array|false { return $this->rows[0] ?? false; }
    }

    final class PersistenceExpression {
        public function __call(string $name, array $arguments): array { return [$name, $arguments]; }
    }

    final class PersistenceBuilder {
        /** @param list<array<string,mixed>> $rows */
        public function __construct(
            private int $affected = 1,
            private array $rows = [],
            private int|string $lastId = 1,
            private ?Throwable $error = null,
        ) {}
        public function __call(string $name, array $arguments): self { return $this; }
        public function createNamedParameter(mixed $value, mixed $type = null): mixed { return $value; }
        public function expr(): PersistenceExpression { return new PersistenceExpression(); }
        public function executeStatement(): int {
            if ($this->error !== null) throw $this->error;
            return $this->affected;
        }
        public function executeQuery(): PersistenceResult { return new PersistenceResult($this->rows); }
        public function getLastInsertId(): int|string { return $this->lastId; }
    }

    final class PersistenceConnection implements IDBConnection {
        public int $commits = 0;
        public int $rollbacks = 0;
        /** @param list<PersistenceBuilder> $builders */
        public function __construct(private array $builders) {}
        public function getQueryBuilder(): PersistenceBuilder {
            if ($this->builders === []) throw new RuntimeException('Unexpected query builder request.');
            return array_shift($this->builders);
        }
        public function beginTransaction(): void {}
        public function commit(): void { $this->commits++; }
        public function rollBack(): void { $this->rollbacks++; }
    }

    /** @return array<string,mixed> */
    function persistenceGrantRow(): array {
        return [
            'id'=>'5', 'target_uid'=>'admin-a', 'granted_by'=>'privacy-officer',
            'starts_at'=>'2026-09-09 00:00:00',
            'ends_at'=>new DateTimeImmutable('2026-09-10T00:00:00+00:00'),
            'revoked_at'=>null, 'revoked_by'=>null,
        ];
    }

    TestRunner::test('temporary admin grants replace atomically and failed inserts roll back', static function (): void {
        $connection = new PersistenceConnection([
            new PersistenceBuilder(), new PersistenceBuilder(lastId: '5'),
        ]);
        $grant = (new TemporaryAdminAccessRepository($connection))->replaceActive(
            'admin-a', 'privacy-officer',
            new DateTimeImmutable('2026-09-09T00:00:00+00:00'),
            new DateTimeImmutable('2026-09-10T00:00:00+00:00'),
        );
        assertSame('5', $grant['id']);
        assertSame(null, $grant['revokedAt']);
        assertSame(1, $connection->commits);

        $failed = new PersistenceConnection([
            new PersistenceBuilder(),
            new PersistenceBuilder(error: new RuntimeException('synthetic insert failure')),
        ]);
        assertThrows(
            static fn () => (new TemporaryAdminAccessRepository($failed))->replaceActive(
                'admin-a', 'privacy-officer',
                new DateTimeImmutable('2026-09-09T00:00:00+00:00'),
                new DateTimeImmutable('2026-09-10T00:00:00+00:00'),
            ),
            RuntimeException::class,
        );
        assertSame(1, $failed->rollbacks);
    });

    TestRunner::test('temporary admin grants are time-bounded mapped and revocable', static function (): void {
        assertSame(true, (new TemporaryAdminAccessRepository(new PersistenceConnection([
            new PersistenceBuilder(),
        ])))->revokeActive(
            'admin-a', 'privacy-officer', new DateTimeImmutable('2026-09-09T12:00:00+00:00'),
        ));
        assertSame(false, (new TemporaryAdminAccessRepository(new PersistenceConnection([
            new PersistenceBuilder(affected: 0),
        ])))->revokeActive(
            'admin-a', 'privacy-officer', new DateTimeImmutable('2026-09-09T12:00:00+00:00'),
        ));

        $active = (new TemporaryAdminAccessRepository(new PersistenceConnection([
            new PersistenceBuilder(rows: [persistenceGrantRow()]),
        ])))->activeFor('admin-a', new DateTimeImmutable('2026-09-09T12:00:00+00:00'));
        assertSame('2026-09-09T00:00:00+00:00', $active['startsAt']->format(DATE_ATOM));
        assertSame('2026-09-10T00:00:00+00:00', $active['endsAt']->format(DATE_ATOM));
        assertSame(null, $active['revokedAt']);

        assertSame(null, (new TemporaryAdminAccessRepository(new PersistenceConnection([
            new PersistenceBuilder(rows: []),
        ])))->activeFor('admin-a', new DateTimeImmutable('2026-09-11T00:00:00+00:00')));

        $historyRow = persistenceGrantRow();
        $historyRow['revoked_at'] = new DateTimeImmutable('2026-09-09T13:00:00+00:00');
        $historyRow['revoked_by'] = 'privacy-officer';
        $history = (new TemporaryAdminAccessRepository(new PersistenceConnection([
            new PersistenceBuilder(rows: [$historyRow]),
        ])))->history();
        assertSame('privacy-officer', $history[0]['revokedBy']);
        assertSame('2026-09-09T13:00:00+00:00', $history[0]['revokedAt']->format(DATE_ATOM));
    });

    final class PersistenceTable {
        public array $columns = [];
        public array $indexes = [];
        public array $foreignKeys = [];
        public array $primaryKey = [];
        public function addColumn(string $name, string $type, array $options): void { $this->columns[$name] = [$type, $options]; }
        public function hasColumn(string $name): bool { return isset($this->columns[$name]); }
        public function setPrimaryKey(array $columns): void { $this->primaryKey = $columns; }
        public function addIndex(array $columns, string $name): void { $this->indexes[$name] = $columns; }
        public function addUniqueIndex(array $columns, string $name): void { $this->indexes[$name] = $columns; }
        public function hasForeignKey(string $name): bool { return isset($this->foreignKeys[$name]); }
        public function addForeignKeyConstraint(
            self $foreignTable,
            array $localColumns,
            array $foreignColumns,
            array $options,
            string $name,
        ): void {
            $this->foreignKeys[$name] = [$foreignTable, $localColumns, $foreignColumns, $options];
        }
    }

    final class PersistenceSchema implements ISchemaWrapper {
        /** @var array<string,PersistenceTable> */
        public array $tables = [];
        public function hasTable(string $name): bool { return isset($this->tables[$name]); }
        public function createTable(string $name): PersistenceTable {
            return $this->tables[$name] = new PersistenceTable();
        }
        public function getTable(string $name): PersistenceTable { return $this->tables[$name]; }
    }

    final class PersistenceOutput implements IOutput {}

    TestRunner::test('fresh-install migrations create the complete BQ schema and remain repeatable', static function (): void {
        $schema = new PersistenceSchema();
        $schemaClosure = static fn (): PersistenceSchema => $schema;
        $output = new PersistenceOutput();

        assertSame($schema, (new Version000001Date202608150101())->changeSchema($output, $schemaClosure, []));
        assertTrue($schema->hasTable('flz_bq_runs'));
        assertTrue($schema->hasTable('flz_bq_modules'));
        assertTrue(isset($schema->getTable('flz_bq_modules')->foreignKeys['flz_bq_module_run_fk']));
        assertSame($schema, (new Version000001Date202608150101())->changeSchema($output, $schemaClosure, []));

        assertSame($schema, (new Version000002Date202608150202())->changeSchema($output, $schemaClosure, []));
        assertTrue($schema->hasTable('flz_bq_lecturers'));
        assertTrue($schema->hasTable('flz_bq_lecturer_requests'));
        assertTrue($schema->getTable('flz_bq_runs')->hasColumn('lead_lecturer_id'));
        assertTrue($schema->getTable('flz_bq_modules')->hasColumn('lecturer_id'));
        assertSame($schema, (new Version000002Date202608150202())->changeSchema($output, $schemaClosure, []));

        assertSame($schema, (new Version000003Date202608250001())->changeSchema($output, $schemaClosure, []));
        assertTrue($schema->hasTable('flz_bq_admin_access'));
        assertSame(null, (new Version000003Date202608250001())->changeSchema($output, $schemaClosure, []));
    });
}
