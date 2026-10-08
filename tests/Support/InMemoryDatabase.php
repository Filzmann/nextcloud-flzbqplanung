<?php

declare(strict_types=1);

namespace FlzBqPlanning\Tests\Support;

use OCP\IDBConnection;

final class IncrementExpression {
    public function __construct(public string $column) {
    }
}

final class InMemoryExpressionBuilder {
    public function eq(string $left, mixed $right): array {
        return ['eq', $left, $right];
    }

    public function orX(mixed ...$conditions): array {
        return ['or', $conditions];
    }
}

final class InMemoryResult {
    /** @param list<array<string,mixed>> $rows */
    public function __construct(private array $rows) {
    }

    /** @return list<array<string,mixed>> */
    public function fetchAllAssociative(): array {
        return $this->rows;
    }

    /** @return array<string,mixed>|false */
    public function fetchAssociative(): array|false {
        return $this->rows[0] ?? false;
    }

    public function closeCursor(): void {
    }
}

final class InMemoryDatabase implements IDBConnection {
    /** @var array<string,list<array<string,mixed>>> */
    public array $rows = [];
    public int $commits = 0;
    public int $rollbacks = 0;
    public bool $failNextBuilder = false;
    /** @var array<string,list<array<string,mixed>>>|null */
    private ?array $snapshot = null;

    public function getQueryBuilder(): InMemoryQueryBuilder {
        if ($this->failNextBuilder) {
            $this->failNextBuilder = false;
            throw new \RuntimeException('synthetischer Datenbankfehler');
        }
        return new InMemoryQueryBuilder($this);
    }

    public function beginTransaction(): void {
        $this->snapshot = $this->rows;
    }

    public function commit(): void {
        $this->commits++;
        $this->snapshot = null;
    }

    public function rollBack(): void {
        $this->rollbacks++;
        if ($this->snapshot !== null) {
            $this->rows = $this->snapshot;
        }
        $this->snapshot = null;
    }

    /** @param array<string,mixed> $row */
    public function seed(string $table, array $row): int {
        $row = match ($table) {
            'flz_bq_runs' => ['lead_lecturer_id' => null] + $row,
            'flz_bq_modules' => ['lecturer_id' => null] + $row,
            default => $row,
        };
        $id = isset($row['id']) ? (int)$row['id'] : $this->nextId($table);
        $this->rows[$table][] = ['id' => $id, ...$row];
        return $id;
    }

    public function nextId(string $table): int {
        $ids = array_map(static fn (array $row): int => (int)$row['id'], $this->rows[$table] ?? []);
        return $ids === [] ? 1 : max($ids) + 1;
    }
}

final class InMemoryQueryBuilder {
    private string $operation = '';
    private string $table = '';
    /** @var array<string,mixed> */
    private array $values = [];
    /** @var list<mixed> */
    private array $conditions = [];
    /** @var list<array{0:string,1:string}> */
    private array $order = [];
    private ?int $limit = null;
    private int $lastInsertId = 0;

    public function __construct(private InMemoryDatabase $db) {
    }

    public function insert(string $table): self {
        $this->operation = 'insert';
        $this->table = $table;
        return $this;
    }

    /** @param array<string,mixed> $values */
    public function values(array $values): self {
        $this->values = $values;
        return $this;
    }

    public function update(string $table): self {
        $this->operation = 'update';
        $this->table = $table;
        return $this;
    }

    public function set(string $column, mixed $value): self {
        $this->values[$column] = $value;
        return $this;
    }

    public function select(string ...$columns): self {
        $this->operation = 'select';
        return $this;
    }

    public function from(string $table, ?string $alias = null): self {
        $this->table = $table;
        return $this;
    }

    public function where(mixed $condition): self {
        $this->conditions = [$condition];
        return $this;
    }

    public function andWhere(mixed $condition): self {
        $this->conditions[] = $condition;
        return $this;
    }

    public function orderBy(string $column, string $direction): self {
        $this->order = [[$column, $direction]];
        return $this;
    }

    public function addOrderBy(string $column, string $direction): self {
        $this->order[] = [$column, $direction];
        return $this;
    }

    public function setMaxResults(int $limit): self {
        $this->limit = $limit;
        return $this;
    }

    public function createNamedParameter(mixed $value, mixed $type = null): mixed {
        return $value;
    }

    public function createFunction(string $expression): IncrementExpression {
        return new IncrementExpression(trim(str_replace('+ 1', '', $expression)));
    }

    public function expr(): InMemoryExpressionBuilder {
        return new InMemoryExpressionBuilder();
    }

    public function executeStatement(): int {
        if ($this->operation === 'insert') {
            $this->lastInsertId = $this->db->seed($this->table, $this->values);
            return 1;
        }
        if ($this->operation !== 'update') {
            throw new \LogicException('Unsupported statement operation ' . $this->operation);
        }
        $affected = 0;
        foreach ($this->db->rows[$this->table] ?? [] as $index => $row) {
            if (!$this->matches($row)) {
                continue;
            }
            foreach ($this->values as $column => $value) {
                $row[$column] = $value instanceof IncrementExpression
                    ? (int)($row[$value->column] ?? 0) + 1
                    : $value;
            }
            $this->db->rows[$this->table][$index] = $row;
            $affected++;
        }
        return $affected;
    }

    public function executeQuery(): InMemoryResult {
        $rows = array_values(array_filter(
            $this->db->rows[$this->table] ?? [],
            fn (array $row): bool => $this->matches($row),
        ));
        if ($this->order !== []) {
            usort($rows, function (array $left, array $right): int {
                foreach ($this->order as [$column, $direction]) {
                    $comparison = ($left[$column] ?? null) <=> ($right[$column] ?? null);
                    if ($comparison !== 0) {
                        return strtoupper($direction) === 'DESC' ? -$comparison : $comparison;
                    }
                }
                return 0;
            });
        }
        if ($this->limit !== null) {
            $rows = array_slice($rows, 0, $this->limit);
        }
        return new InMemoryResult($rows);
    }

    public function getLastInsertId(): int {
        return $this->lastInsertId;
    }

    /** @param array<string,mixed> $row */
    private function matches(array $row): bool {
        foreach ($this->conditions as $condition) {
            if (!$this->matchesCondition($row, $condition)) {
                return false;
            }
        }
        return true;
    }

    /** @param array<string,mixed> $row */
    private function matchesCondition(array $row, mixed $condition): bool {
        if (($condition[0] ?? null) === 'eq') {
            $column = (string)$condition[1];
            $column = str_contains($column, '.') ? substr($column, (int)strrpos($column, '.') + 1) : $column;
            return ($row[$column] ?? null) === $condition[2]
                || (string)($row[$column] ?? '') === (string)$condition[2];
        }
        if (($condition[0] ?? null) === 'or') {
            foreach ($condition[1] as $nested) {
                if ($this->matchesCondition($row, $nested)) {
                    return true;
                }
            }
            return false;
        }
        throw new \LogicException('Unsupported condition');
    }
}
