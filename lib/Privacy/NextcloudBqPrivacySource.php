<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Privacy;

use OCP\IDBConnection;

final class NextcloudBqPrivacySource implements BqPrivacySource {
    public function __construct(private IDBConnection $db) {}

    public function forSubject(string $uid, int $limit): array {
        $records = [];
        $this->append($records, $this->adminAccess($uid, $limit), 'admin_access', $limit);
        $this->append($records, $this->profiles($uid, $limit), 'profile', $limit);
        $this->append($records, $this->leadAssignments($uid, $limit - count($records)), 'lead', $limit);
        $this->append($records, $this->moduleAssignments($uid, $limit - count($records)), 'module', $limit);
        $this->append($records, $this->actorRows('flz_bq_runs', true, 'run_updated', $uid, $limit - count($records)), 'activity', $limit);
        $this->append($records, $this->actorRows('flz_bq_modules', false, 'module_created', $uid, $limit - count($records)), 'activity', $limit);
        $this->append($records, $this->actorRows('flz_bq_lecturers', true, 'lecturer_updated', $uid, $limit - count($records)), 'activity', $limit);
        $this->append($records, $this->actorRows('flz_bq_lecturer_requests', true, 'request_updated', $uid, $limit - count($records)), 'activity', $limit);
        return $records;
    }

    private function adminAccess(string $uid, int $limit): array {
        if ($limit < 1) return [];
        $qb = $this->db->getQueryBuilder();
        $rows = $this->all($qb->select('id','target_uid','granted_by','starts_at','ends_at','revoked_at','revoked_by','created_at')
            ->from('flz_bq_admin_access')
            ->where($qb->expr()->orX(
                $qb->expr()->eq('target_uid', $qb->createNamedParameter($uid)),
                $qb->expr()->eq('granted_by', $qb->createNamedParameter($uid)),
                $qb->expr()->eq('revoked_by', $qb->createNamedParameter($uid)),
            ))
            ->orderBy('created_at','DESC')->setMaxResults($limit));
        return array_map(static fn(array $row): array => ['subject_uid'=>$uid]+$row, $rows);
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
        return $this->all($qb->select('id','active','created_at','updated_at')->from('flz_bq_lecturers')
            ->where($qb->expr()->eq('kind', $qb->createNamedParameter('internal')))
            ->andWhere($qb->expr()->eq('nextcloud_uid', $qb->createNamedParameter($uid)))
            ->orderBy('id', 'ASC')->setMaxResults($limit));
    }

    private function leadAssignments(string $uid, int $limit): array {
        if ($limit < 1) return [];
        $qb = $this->db->getQueryBuilder();
        return $this->all($qb->select('r.id','r.label','r.starts_on','r.ends_on','r.status')
            ->from('flz_bq_runs', 'r')->innerJoin('r', 'flz_bq_lecturers', 'l', $qb->expr()->eq('l.id', 'r.lead_lecturer_id'))
            ->where($qb->expr()->eq('l.kind', $qb->createNamedParameter('internal')))
            ->andWhere($qb->expr()->eq('l.nextcloud_uid', $qb->createNamedParameter($uid)))
            ->orderBy('r.id', 'ASC')->setMaxResults($limit));
    }

    private function moduleAssignments(string $uid, int $limit): array {
        if ($limit < 1) return [];
        $qb = $this->db->getQueryBuilder();
        return $this->all($qb->select('m.id','m.run_id','m.title','m.module_date','m.starts_at','m.ends_at')
            ->from('flz_bq_modules', 'm')->innerJoin('m', 'flz_bq_lecturers', 'l', $qb->expr()->eq('l.id', 'm.lecturer_id'))
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
