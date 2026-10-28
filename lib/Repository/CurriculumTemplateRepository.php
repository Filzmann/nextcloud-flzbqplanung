<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Repository;

use DateTimeInterface;
use DomainException;
use OCA\FlzBqPlanning\Contract\CurriculumTemplateStore;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Throwable;

final class CurriculumTemplateRepository implements CurriculumTemplateStore {
    private const CENTRAL_KEY = 'central';

    public function __construct(private IDBConnection $db, private ITimeFactory $clock) {
    }

    public function current(): ?array {
        $qb = $this->db->getQueryBuilder();
        $result = $qb->select('*')
            ->from('flz_bq_templates')
            ->where($qb->expr()->eq('template_key', $qb->createNamedParameter(self::CENTRAL_KEY)))
            ->executeQuery();
        try {
            $row = $result->fetchAssociative();
        } finally {
            $result->closeCursor();
        }
        if ($row === false) {
            return null;
        }
        $templateId = (int)$row['id'];
        return [
            'id' => $templateId,
            'key' => (string)$row['template_key'],
            'title' => (string)$row['title'],
            'version' => (int)$row['version'],
            'modules' => $this->modules($templateId),
        ];
    }

    public function save(string $title, array $modules, int $expectedVersion, string $actorUid): array {
        $this->db->beginTransaction();
        try {
            $now = $this->now();
            if ($expectedVersion === 0) {
                if ($this->current() !== null) {
                    throw new DomainException('Die Curriculum-Vorlage wurde zwischenzeitlich angelegt.');
                }
                $qb = $this->db->getQueryBuilder();
                $qb->insert('flz_bq_templates')->values([
                    'template_key' => $qb->createNamedParameter(self::CENTRAL_KEY),
                    'title' => $qb->createNamedParameter($title),
                    'version' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
                    'created_by' => $qb->createNamedParameter($actorUid),
                    'created_at' => $qb->createNamedParameter($now),
                    'updated_by' => $qb->createNamedParameter($actorUid),
                    'updated_at' => $qb->createNamedParameter($now),
                ])->executeStatement();
                $templateId = (int)$qb->getLastInsertId();
            } else {
                $qb = $this->db->getQueryBuilder();
                $affected = $qb->update('flz_bq_templates')
                    ->set('title', $qb->createNamedParameter($title))
                    ->set('version', $qb->createFunction('version + 1'))
                    ->set('updated_by', $qb->createNamedParameter($actorUid))
                    ->set('updated_at', $qb->createNamedParameter($now))
                    ->where($qb->expr()->eq('template_key', $qb->createNamedParameter(self::CENTRAL_KEY)))
                    ->andWhere($qb->expr()->eq('version', $qb->createNamedParameter($expectedVersion, IQueryBuilder::PARAM_INT)))
                    ->executeStatement();
                if ($affected !== 1) {
                    throw new DomainException('Die Curriculum-Vorlage wurde zwischenzeitlich geändert.');
                }
                $current = $this->current();
                if ($current === null) {
                    throw new DomainException('Die Curriculum-Vorlage wurde nicht gefunden.');
                }
                $templateId = (int)$current['id'];
                $delete = $this->db->getQueryBuilder();
                $delete->delete('flz_bq_template_modules')
                    ->where($delete->expr()->eq('template_id', $delete->createNamedParameter($templateId, IQueryBuilder::PARAM_INT)))
                    ->executeStatement();
            }

            foreach ($modules as $position => $module) {
                $qb = $this->db->getQueryBuilder();
                $qb->insert('flz_bq_template_modules')->values([
                    'template_id' => $qb->createNamedParameter($templateId, IQueryBuilder::PARAM_INT),
                    'module_key' => $qb->createNamedParameter($module['moduleKey']),
                    'title' => $qb->createNamedParameter($module['title']),
                    'minutes' => $qb->createNamedParameter($module['minutes'], IQueryBuilder::PARAM_INT),
                    'position' => $qb->createNamedParameter($position, IQueryBuilder::PARAM_INT),
                ])->executeStatement();
            }
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }

        $saved = $this->current();
        if ($saved === null) {
            throw new DomainException('Die gespeicherte Curriculum-Vorlage wurde nicht gefunden.');
        }
        return $saved;
    }

    /** @return list<array{moduleKey:string,title:string,minutes:int,position:int}> */
    private function modules(int $templateId): array {
        $qb = $this->db->getQueryBuilder();
        $result = $qb->select('*')
            ->from('flz_bq_template_modules')
            ->where($qb->expr()->eq('template_id', $qb->createNamedParameter($templateId, IQueryBuilder::PARAM_INT)))
            ->orderBy('position', 'ASC')
            ->addOrderBy('id', 'ASC')
            ->executeQuery();
        try {
            return array_map(static fn (array $row): array => [
                'moduleKey' => (string)$row['module_key'],
                'title' => (string)$row['title'],
                'minutes' => (int)$row['minutes'],
                'position' => (int)$row['position'],
            ], $result->fetchAllAssociative());
        } finally {
            $result->closeCursor();
        }
    }

    private function now(): string {
        $value = $this->clock->getTime();
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d H:i:s') : gmdate('Y-m-d H:i:s', (int)$value);
    }
}
