<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Privacy;

use OCP\IDBConnection;

final class NextcloudBqPrivacySource implements BqPrivacySource {
    public function __construct(private IDBConnection $db) {}

    public function forSubject(string $uid, int $limit): array {
        $records = [];
        $this->append($records, $this->profiles($uid, $limit), 'profile', $limit);
        $this->append($records, $this->leadAssignments($uid, $limit - count($records)), 'lead', $limit);
        $this->append($records, $this->moduleAssignments($uid, $limit - count($records)), 'module', $limit);
        $this->append($records, $this->actorRows('adbq_runs', true, 'run_updated', $uid, $limit - count($records)), 'activity', $limit);
        $this->append($records, $this->actorRows('adbq_modules', false, 'module_created', $uid, $limit - count($records)), 'activity', $limit);
        $this->append($records, $this->actorRows('adbq_lecturers', true, 'lecturer_updated', $uid, $limit - count($records)), 'activity', $limit);
        $this->append($records, $this->actorRows('adbq_lecturer_requests', true, 'request_updated', $uid, $limit - count($records)), 'activity', $limit);
        return $records;
    }

    private function append(array &$target, array $rows, string $kind, int $limit): void {
        foreach ($rows as $row) {
            if (count($target) >= $limit) return;
            $target[] = ['kind'=>$kind] + $row;
        }
    }

    private function profiles(string $uid, int $limit): array {
        if ($limit < 1) return [];
        $qb = $this->db->getQueryBuilder();
        return $this->all($qb->select('id','active','created_at','updated_at')->from('adbq_lecturers')
            ->where($qb->expr()->eq('kind', $qb->createNamedParameter('internal')))
            ->andWhere($qb->expr()->eq('nextcloud_uid', $qb->createNamedParameter($uid)))
            ->orderBy('id', 'ASC')->setMaxResults($limit));
    }

    private function leadAssignments(string $uid, int $limit): array {
        if ($limit < 1) return [];
        $qb = $this->db->getQueryBuilder();
        return $this->all($qb->select('r.id','r.label','r.starts_on','r.ends_on','r.status')
            ->from('adbq_runs', 'r')->innerJoin('r', 'adbq_lecturers', 'l', $qb->expr()->eq('l.id', 'r.lead_lecturer_id'))
            ->where($qb->expr()->eq('l.kind', $qb->createNamedParameter('internal')))
            ->andWhere($qb->expr()->eq('l.nextcloud_uid', $qb->createNamedParameter($uid)))
            ->orderBy('r.id', 'ASC')->setMaxResults($limit));
    }

    private function moduleAssignments(string $uid, int $limit): array {
        if ($limit < 1) return [];
        $qb = $this->db->getQueryBuilder();
        return $this->all($qb->select('m.id','m.run_id','m.title','m.module_date','m.starts_at','m.ends_at')
            ->from('adbq_modules', 'm')->innerJoin('m', 'adbq_lecturers', 'l', $qb->expr()->eq('l.id', 'm.lecturer_id'))
            ->where($qb->expr()->eq('l.kind', $qb->createNamedParameter('internal')))
            ->andWhere($qb->expr()->eq('l.nextcloud_uid', $qb->createNamedParameter($uid)))
            ->orderBy('m.id', 'ASC')->setMaxResults($limit));
    }

    private function actorRows(string $table, bool $hasUpdatedBy, string $activity, string $uid, int $limit): array {
        if ($limit < 1) return [];
        $qb = $this->db->getQueryBuilder();
        $dateColumn = $hasUpdatedBy ? 'updated_at' : 'created_at';
        $query = $qb->select('id', $dateColumn)->from($table);
        if ($hasUpdatedBy) {
            $query->where($qb->expr()->orX(
                $qb->expr()->eq('created_by', $qb->createNamedParameter($uid)),
                $qb->expr()->eq('updated_by', $qb->createNamedParameter($uid)),
            ));
        } else {
            $query->where($qb->expr()->eq('created_by', $qb->createNamedParameter($uid)));
        }
        return array_map(static fn(array $row): array => ['id'=>$row['id'], 'activity'=>$activity, 'occurred_at'=>$row[$dateColumn]], $this->all($query->orderBy('id', 'ASC')->setMaxResults($limit)));
    }

    private function all(object $query): array {
        $result = $query->executeQuery();
        try { return $result->fetchAllAssociative(); } finally { $result->closeCursor(); }
    }
}
