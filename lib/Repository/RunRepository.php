<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Repository;

use DateTimeInterface;
use DomainException;
use OCA\AdBqPlanning\Contract\RunStore;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Throwable;

final class RunRepository implements RunStore {
    public function __construct(private IDBConnection $db, private ITimeFactory $clock) {
    }

    public function createRun(array $run): int {
        $now = $this->now();
        $qb = $this->db->getQueryBuilder();
        $qb->insert('adbq_runs')
            ->values([
                'label' => $qb->createNamedParameter($run['label']),
                'starts_on' => $qb->createNamedParameter($run['startsOn']),
                'ends_on' => $qb->createNamedParameter($run['endsOn']),
                'capacity' => $qb->createNamedParameter($run['capacity'], IQueryBuilder::PARAM_INT),
                'status' => $qb->createNamedParameter('draft'),
                'version' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
                'created_by' => $qb->createNamedParameter($run['createdBy']),
                'created_at' => $qb->createNamedParameter($now),
                'updated_by' => $qb->createNamedParameter($run['createdBy']),
                'updated_at' => $qb->createNamedParameter($now),
            ])
            ->executeStatement();
        return (int)$qb->getLastInsertId();
    }

    public function runs(): array {
        $qb = $this->db->getQueryBuilder();
        $result = $qb->select('*')
            ->from('adbq_runs')
            ->orderBy('starts_on', 'ASC')
            ->addOrderBy('id', 'ASC')
            ->executeQuery();
        try {
            return array_map([$this, 'mapRun'], $result->fetchAllAssociative());
        } finally {
            $result->closeCursor();
        }
    }

    public function run(int $runId): array {
        $qb = $this->db->getQueryBuilder();
        $result = $qb->select('*')
            ->from('adbq_runs')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($runId, IQueryBuilder::PARAM_INT)))
            ->executeQuery();
        try {
            $row = $result->fetchAssociative();
        } finally {
            $result->closeCursor();
        }
        if ($row === false) {
            throw new DomainException('Der BQ-Durchlauf wurde nicht gefunden.');
        }
        return $this->mapRun($row);
    }

    public function addModule(int $runId, array $module, int $expectedVersion, string $actorUid): int {
        $this->db->beginTransaction();
        try {
            $version = $this->db->getQueryBuilder();
            $affected = $version->update('adbq_runs')
                ->set('version', $version->createFunction('version + 1'))
                ->set('updated_by', $version->createNamedParameter($actorUid))
                ->set('updated_at', $version->createNamedParameter($this->now()))
                ->where($version->expr()->eq('id', $version->createNamedParameter($runId, IQueryBuilder::PARAM_INT)))
                ->andWhere($version->expr()->eq('status', $version->createNamedParameter('draft')))
                ->andWhere($version->expr()->eq('version', $version->createNamedParameter($expectedVersion, IQueryBuilder::PARAM_INT)))
                ->executeStatement();
            if ($affected !== 1) {
                throw new DomainException('Der BQ-Durchlauf wurde zwischenzeitlich geändert.');
            }

            $position = count($this->modules($runId));
            $qb = $this->db->getQueryBuilder();
            $qb->insert('adbq_modules')
                ->values([
                    'run_id' => $qb->createNamedParameter($runId, IQueryBuilder::PARAM_INT),
                    'module_key' => $qb->createNamedParameter($module['moduleKey']),
                    'title' => $qb->createNamedParameter($module['title']),
                    'minutes' => $qb->createNamedParameter($module['minutes'], IQueryBuilder::PARAM_INT),
                    'module_date' => $qb->createNamedParameter($module['date']),
                    'starts_at' => $qb->createNamedParameter($module['startsAt']),
                    'ends_at' => $qb->createNamedParameter($module['endsAt']),
                    'additional_capacity' => $qb->createNamedParameter($module['additionalCapacity'], IQueryBuilder::PARAM_INT),
                    'position' => $qb->createNamedParameter($position, IQueryBuilder::PARAM_INT),
                    'version' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
                    'created_by' => $qb->createNamedParameter($actorUid),
                    'created_at' => $qb->createNamedParameter($this->now()),
                ])
                ->executeStatement();
            $id = (int)$qb->getLastInsertId();
            $this->db->commit();
            return $id;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function modules(int $runId): array {
        $qb = $this->db->getQueryBuilder();
        $result = $qb->select('*')
            ->from('adbq_modules')
            ->where($qb->expr()->eq('run_id', $qb->createNamedParameter($runId, IQueryBuilder::PARAM_INT)))
            ->orderBy('position', 'ASC')
            ->addOrderBy('id', 'ASC')
            ->executeQuery();
        try {
            return array_map([$this, 'mapModule'], $result->fetchAllAssociative());
        } finally {
            $result->closeCursor();
        }
    }

    public function changeStatus(int $runId, string $from, string $to, int $expectedVersion, string $actorUid): array {
        $qb = $this->db->getQueryBuilder();
        $affected = $qb->update('adbq_runs')
            ->set('status', $qb->createNamedParameter($to))
            ->set('version', $qb->createFunction('version + 1'))
            ->set('updated_by', $qb->createNamedParameter($actorUid))
            ->set('updated_at', $qb->createNamedParameter($this->now()))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($runId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('status', $qb->createNamedParameter($from)))
            ->andWhere($qb->expr()->eq('version', $qb->createNamedParameter($expectedVersion, IQueryBuilder::PARAM_INT)))
            ->executeStatement();
        if ($affected !== 1) {
            throw new DomainException('Der BQ-Durchlauf wurde zwischenzeitlich geändert.');
        }
        return $this->run($runId);
    }

    /** @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function mapRun(array $row): array {
        return [
            'id' => (int)$row['id'],
            'label' => (string)$row['label'],
            'startsOn' => $this->dateValue($row['starts_on']),
            'endsOn' => $this->dateValue($row['ends_on']),
            'capacity' => (int)$row['capacity'],
            'status' => (string)$row['status'],
            'version' => (int)$row['version'],
            'leadLecturerId' => $row['lead_lecturer_id'] === null ? null : (int)$row['lead_lecturer_id'],
        ];
    }

    /** @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function mapModule(array $row): array {
        return [
            'id' => (int)$row['id'],
            'runId' => (int)$row['run_id'],
            'moduleKey' => (string)$row['module_key'],
            'title' => (string)$row['title'],
            'minutes' => (int)$row['minutes'],
            'date' => $this->dateValue($row['module_date']),
            'startsAt' => (string)$row['starts_at'],
            'endsAt' => (string)$row['ends_at'],
            'additionalCapacity' => (int)$row['additional_capacity'],
            'position' => (int)$row['position'],
            'version' => (int)$row['version'],
            'lecturerId' => $row['lecturer_id'] === null ? null : (int)$row['lecturer_id'],
        ];
    }

    private function dateValue(mixed $value): string {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : substr((string)$value, 0, 10);
    }

    private function now(): string {
        return gmdate('Y-m-d H:i:s', $this->clock->getTime());
    }
}
