<?php

declare(strict_types=1);

namespace OCP { interface IDBConnection { public function getQueryBuilder(); } }

namespace AdBqPlanning\Tests {
    use OCA\AdBqPlanning\Privacy\NextcloudBqPrivacySource;
    use OCP\IDBConnection;

    final class PrivacySourceResult {
        public function fetchAllAssociative(): array { return [[
            'id'=>1,'active'=>true,'created_at'=>'2026-08-01','updated_at'=>'2026-08-02','label'=>'BQ Test',
            'starts_on'=>'2026-09-04','ends_on'=>'2026-09-14','status'=>'draft','run_id'=>1,'title'=>'Modul',
            'module_date'=>'2026-09-08','starts_at'=>'09:00','ends_at'=>'12:00',
        ]]; }
        public function closeCursor(): void {}
    }
    final class PrivacySourceExpression {
        public function eq(string $left, mixed $right): array { return [$left,$right]; }
        public function orX(mixed ...$conditions): array { return $conditions; }
    }
    final class PrivacySourceBuilder {
        public array $selected = [];
        public array $bindings = [];
        public ?int $limit = null;
        public function select(string ...$columns): self { $this->selected=$columns; return $this; }
        public function from(string $table, ?string $alias=null): self { return $this; }
        public function innerJoin(string $fromAlias,string $table,string $alias,mixed $condition): self { return $this; }
        public function where(mixed $condition): self { return $this; }
        public function andWhere(mixed $condition): self { return $this; }
        public function orderBy(string $column,string $direction): self { return $this; }
        public function setMaxResults(int $limit): self { $this->limit=$limit; return $this; }
        public function createNamedParameter(mixed $value): array { $this->bindings[]=$value; return ['value'=>$value]; }
        public function expr(): PrivacySourceExpression { return new PrivacySourceExpression(); }
        public function executeQuery(): PrivacySourceResult { return new PrivacySourceResult(); }
    }
    final class PrivacySourceConnection implements IDBConnection {
        public array $builders=[];
        public function getQueryBuilder(): PrivacySourceBuilder { $builder=new PrivacySourceBuilder(); $this->builders[]=$builder; return $builder; }
    }

    TestRunner::test('BQ privacy source binds every internal profile teaching and activity query', static function (): void {
        $connection = new PrivacySourceConnection();
        $records = (new NextcloudBqPrivacySource($connection))->forSubject('self', 20);
        assertSame(['admin_access','profile','lead','module','activity','activity','activity','activity'], array_column($records, 'kind'));
        assertSame(8, count($connection->builders));
        foreach ($connection->builders as $index => $builder) {
            assertTrue(in_array('self', $builder->bindings, true), "Privacy query {$index} is not subject-bound");
            assertTrue($builder->limit !== null && $builder->limit > 0, "Privacy query {$index} is not bounded");
            foreach (['display_name','email','nextcloud_uid'] as $forbidden) {
                assertTrue(!in_array($forbidden, $builder->selected, true), "Privacy query selects foreign identity field {$forbidden}");
            }
        }
        assertSame(['self','self','self'], $connection->builders[0]->bindings);
        assertSame(['internal','self'], $connection->builders[1]->bindings);
        assertSame(['internal','self'], $connection->builders[2]->bindings);
        assertSame(['internal','self'], $connection->builders[3]->bindings);
    });
}
