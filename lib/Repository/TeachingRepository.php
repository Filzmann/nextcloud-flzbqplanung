<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Repository;

use DomainException;
use OCA\AdBqPlanning\Contract\TeachingStore;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Throwable;

final class TeachingRepository implements TeachingStore {
    public function __construct(private IDBConnection $db, private ITimeFactory $clock) {
    }

    public function createLecturer(array $lecturer): int {
        $now = $this->now();
        $qb = $this->db->getQueryBuilder();
        $qb->insert('adbq_lecturers')->values([
            'kind' => $qb->createNamedParameter($lecturer['kind']),
            'nextcloud_uid' => $qb->createNamedParameter($lecturer['nextcloudUid']),
            'display_name' => $qb->createNamedParameter($lecturer['displayName']),
            'email' => $qb->createNamedParameter($lecturer['email']),
            'active' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
            'version' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
            'created_by' => $qb->createNamedParameter($lecturer['createdBy']),
            'created_at' => $qb->createNamedParameter($now),
            'updated_by' => $qb->createNamedParameter($lecturer['createdBy']),
            'updated_at' => $qb->createNamedParameter($now),
        ])->executeStatement();
        return (int)$qb->getLastInsertId();
    }

    public function lecturers(): array {
        $qb = $this->db->getQueryBuilder();
        $result = $qb->select('*')->from('adbq_lecturers')
            ->orderBy('kind', 'ASC')->addOrderBy('id', 'ASC')->executeQuery();
        try {
            return array_map([$this, 'mapLecturer'], $result->fetchAllAssociative());
        } finally {
            $result->closeCursor();
        }
    }

    public function lecturer(int $lecturerId): array {
        return $this->one('adbq_lecturers', $lecturerId, [$this, 'mapLecturer'], 'Die Dozentin wurde nicht gefunden.');
    }

    public function run(int $runId): array {
        return $this->one('adbq_runs', $runId, static fn (array $row): array => [
            'id' => (int)$row['id'],
            'status' => (string)$row['status'],
            'version' => (int)$row['version'],
            'leadLecturerId' => $row['lead_lecturer_id'] === null ? null : (int)$row['lead_lecturer_id'],
        ], 'Der BQ-Durchlauf wurde nicht gefunden.');
    }

    public function module(int $moduleId): array {
        return $this->one('adbq_modules', $moduleId, static fn (array $row): array => [
            'id' => (int)$row['id'],
            'runId' => (int)$row['run_id'],
            'version' => (int)$row['version'],
            'lecturerId' => $row['lecturer_id'] === null ? null : (int)$row['lecturer_id'],
        ], 'Das Curriculum-Modul wurde nicht gefunden.');
    }

    public function setLead(int $runId, int $lecturerId, int $expectedVersion, string $actorUid): array {
        $qb = $this->db->getQueryBuilder();
        $affected = $qb->update('adbq_runs')
            ->set('lead_lecturer_id', $qb->createNamedParameter($lecturerId, IQueryBuilder::PARAM_INT))
            ->set('version', $qb->createFunction('version + 1'))
            ->set('updated_by', $qb->createNamedParameter($actorUid))
            ->set('updated_at', $qb->createNamedParameter($this->now()))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($runId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('status', $qb->createNamedParameter('draft')))
            ->andWhere($qb->expr()->eq('version', $qb->createNamedParameter($expectedVersion, IQueryBuilder::PARAM_INT)))
            ->executeStatement();
        if ($affected !== 1) throw new DomainException('Der BQ-Durchlauf wurde zwischenzeitlich geändert.');
        return $this->run($runId);
    }

    public function activeRequestForModule(int $moduleId): ?array {
        $qb = $this->db->getQueryBuilder();
        $result = $qb->select('*')->from('adbq_lecturer_requests')
            ->where($qb->expr()->eq('module_id', $qb->createNamedParameter($moduleId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('status', $qb->createNamedParameter('requested')),
                $qb->expr()->eq('status', $qb->createNamedParameter('confirmed')),
            ))->setMaxResults(1)->executeQuery();
        try {
            $row = $result->fetchAssociative();
            return $row === false ? null : $this->mapRequest($row);
        } finally {
            $result->closeCursor();
        }
    }

    public function createRequest(array $request, int $expectedRunVersion, int $expectedModuleVersion, string $actorUid): int {
        $module = $this->module((int)$request['moduleId']);
        $this->db->beginTransaction();
        try {
            $run = $this->db->getQueryBuilder();
            $runAffected = $run->update('adbq_runs')
                ->set('version', $run->createFunction('version + 1'))
                ->set('updated_by', $run->createNamedParameter($actorUid))
                ->set('updated_at', $run->createNamedParameter($this->now()))
                ->where($run->expr()->eq('id', $run->createNamedParameter($module['runId'], IQueryBuilder::PARAM_INT)))
                ->andWhere($run->expr()->eq('status', $run->createNamedParameter('draft')))
                ->andWhere($run->expr()->eq('version', $run->createNamedParameter($expectedRunVersion, IQueryBuilder::PARAM_INT)))
                ->executeStatement();
            if ($runAffected !== 1) throw new DomainException('Der BQ-Durchlauf wurde zwischenzeitlich geändert.');

            $moduleQb = $this->db->getQueryBuilder();
            $moduleAffected = $moduleQb->update('adbq_modules')
                ->set('version', $moduleQb->createFunction('version + 1'))
                ->where($moduleQb->expr()->eq('id', $moduleQb->createNamedParameter($request['moduleId'], IQueryBuilder::PARAM_INT)))
                ->andWhere($moduleQb->expr()->eq('version', $moduleQb->createNamedParameter($expectedModuleVersion, IQueryBuilder::PARAM_INT)))
                ->executeStatement();
            if ($moduleAffected !== 1) throw new DomainException('Das Curriculum-Modul wurde zwischenzeitlich geändert.');

            $now = $this->now();
            $qb = $this->db->getQueryBuilder();
            $qb->insert('adbq_lecturer_requests')->values([
                'module_id' => $qb->createNamedParameter($request['moduleId'], IQueryBuilder::PARAM_INT),
                'lecturer_id' => $qb->createNamedParameter($request['lecturerId'], IQueryBuilder::PARAM_INT),
                'status' => $qb->createNamedParameter('requested'),
                'version' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
                'created_by' => $qb->createNamedParameter($actorUid),
                'created_at' => $qb->createNamedParameter($now),
                'updated_by' => $qb->createNamedParameter($actorUid),
                'updated_at' => $qb->createNamedParameter($now),
            ])->executeStatement();
            $id = (int)$qb->getLastInsertId();
            $this->db->commit();
            return $id;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function request(int $requestId): array {
        return $this->one('adbq_lecturer_requests', $requestId, [$this, 'mapRequest'], 'Die Dozentinnenanfrage wurde nicht gefunden.');
    }

    public function requests(): array {
        $qb = $this->db->getQueryBuilder();
        $result = $qb->select('*')->from('adbq_lecturer_requests')
            ->orderBy('id', 'DESC')->executeQuery();
        try {
            return array_map([$this, 'mapRequest'], $result->fetchAllAssociative());
        } finally {
            $result->closeCursor();
        }
    }

    public function transitionRequest(
        int $requestId,
        string $from,
        string $to,
        int $expectedVersion,
        string $actorUid,
        string $assignmentAction,
    ): array {
        $request = $this->request($requestId);
        $this->db->beginTransaction();
        try {
            $qb = $this->db->getQueryBuilder();
            $affected = $qb->update('adbq_lecturer_requests')
                ->set('status', $qb->createNamedParameter($to))
                ->set('version', $qb->createFunction('version + 1'))
                ->set('updated_by', $qb->createNamedParameter($actorUid))
                ->set('updated_at', $qb->createNamedParameter($this->now()))
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($requestId, IQueryBuilder::PARAM_INT)))
                ->andWhere($qb->expr()->eq('status', $qb->createNamedParameter($from)))
                ->andWhere($qb->expr()->eq('version', $qb->createNamedParameter($expectedVersion, IQueryBuilder::PARAM_INT)))
                ->executeStatement();
            if ($affected !== 1) throw new DomainException('Die Dozentinnenanfrage wurde zwischenzeitlich geändert.');

            if ($assignmentAction !== 'none') {
                $module = $this->db->getQueryBuilder();
                $value = $assignmentAction === 'assign'
                    ? $module->createNamedParameter($request['lecturerId'], IQueryBuilder::PARAM_INT)
                    : $module->createNamedParameter(null);
                $module->update('adbq_modules')
                    ->set('lecturer_id', $value)
                    ->set('version', $module->createFunction('version + 1'))
                    ->where($module->expr()->eq('id', $module->createNamedParameter($request['moduleId'], IQueryBuilder::PARAM_INT)));
                if ($assignmentAction === 'clear') {
                    $module->andWhere($module->expr()->eq('lecturer_id', $module->createNamedParameter($request['lecturerId'], IQueryBuilder::PARAM_INT)));
                }
                $module->executeStatement();
            }
            $this->db->commit();
            return $this->request($requestId);
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    private function one(string $table, int $id, callable $mapper, string $missingMessage): array {
        $qb = $this->db->getQueryBuilder();
        $result = $qb->select('*')->from($table)
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->executeQuery();
        try {
            $row = $result->fetchAssociative();
        } finally {
            $result->closeCursor();
        }
        if ($row === false) throw new DomainException($missingMessage);
        return $mapper($row);
    }

    private function mapLecturer(array $row): array {
        return [
            'id' => (int)$row['id'], 'kind' => (string)$row['kind'],
            'nextcloudUid' => $row['nextcloud_uid'] === null ? null : (string)$row['nextcloud_uid'],
            'displayName' => $row['display_name'] === null ? null : (string)$row['display_name'],
            'email' => $row['email'] === null ? null : (string)$row['email'],
            'active' => (bool)$row['active'], 'version' => (int)$row['version'],
        ];
    }

    private function mapRequest(array $row): array {
        return [
            'id' => (int)$row['id'], 'moduleId' => (int)$row['module_id'],
            'lecturerId' => (int)$row['lecturer_id'], 'status' => (string)$row['status'],
            'version' => (int)$row['version'],
        ];
    }

    private function now(): string {
        return gmdate('Y-m-d H:i:s', $this->clock->getTime());
    }
}
