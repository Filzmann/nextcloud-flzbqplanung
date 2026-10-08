<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version000002Date202608150202 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('flz_bq_lecturers')) {
            $lecturers = $schema->createTable('flz_bq_lecturers');
            $lecturers->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $lecturers->addColumn('kind', Types::STRING, ['notnull' => true, 'length' => 16]);
            $lecturers->addColumn('nextcloud_uid', Types::STRING, ['notnull' => false, 'length' => 64]);
            $lecturers->addColumn('display_name', Types::STRING, ['notnull' => false, 'length' => 128]);
            $lecturers->addColumn('email', Types::STRING, ['notnull' => false, 'length' => 254]);
            $lecturers->addColumn('active', Types::BOOLEAN, ['notnull' => true, 'default' => true]);
            $lecturers->addColumn('version', Types::INTEGER, ['notnull' => true, 'unsigned' => true, 'default' => 1]);
            $lecturers->addColumn('created_by', Types::STRING, ['notnull' => true, 'length' => 64]);
            $lecturers->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
            $lecturers->addColumn('updated_by', Types::STRING, ['notnull' => true, 'length' => 64]);
            $lecturers->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
            $lecturers->setPrimaryKey(['id']);
            $lecturers->addUniqueIndex(['nextcloud_uid'], 'flz_bq_lecturer_uid_uniq');
            $lecturers->addIndex(['kind', 'active'], 'flz_bq_lecturer_kind_idx');
        } else {
            $lecturers = $schema->getTable('flz_bq_lecturers');
        }

        $runs = $schema->getTable('flz_bq_runs');
        if (!$runs->hasColumn('lead_lecturer_id')) {
            $runs->addColumn('lead_lecturer_id', Types::BIGINT, ['notnull' => false, 'unsigned' => true]);
        }
        if (!$runs->hasForeignKey('flz_bq_run_lead_fk')) {
            $runs->addForeignKeyConstraint($lecturers, ['lead_lecturer_id'], ['id'], ['onDelete' => 'SET NULL'], 'flz_bq_run_lead_fk');
        }

        $modules = $schema->getTable('flz_bq_modules');
        if (!$modules->hasColumn('lecturer_id')) {
            $modules->addColumn('lecturer_id', Types::BIGINT, ['notnull' => false, 'unsigned' => true]);
        }
        if (!$modules->hasForeignKey('flz_bq_module_lecturer_fk')) {
            $modules->addForeignKeyConstraint($lecturers, ['lecturer_id'], ['id'], ['onDelete' => 'SET NULL'], 'flz_bq_module_lecturer_fk');
        }

        if (!$schema->hasTable('flz_bq_lecturer_requests')) {
            $requests = $schema->createTable('flz_bq_lecturer_requests');
            $requests->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $requests->addColumn('module_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $requests->addColumn('lecturer_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $requests->addColumn('status', Types::STRING, ['notnull' => true, 'length' => 16, 'default' => 'requested']);
            $requests->addColumn('version', Types::INTEGER, ['notnull' => true, 'unsigned' => true, 'default' => 1]);
            $requests->addColumn('created_by', Types::STRING, ['notnull' => true, 'length' => 64]);
            $requests->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
            $requests->addColumn('updated_by', Types::STRING, ['notnull' => true, 'length' => 64]);
            $requests->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
            $requests->setPrimaryKey(['id']);
            $requests->addIndex(['module_id', 'status'], 'flz_bq_request_module_idx');
            $requests->addIndex(['lecturer_id', 'status'], 'flz_bq_request_lecturer_idx');
            $requests->addForeignKeyConstraint($modules, ['module_id'], ['id'], ['onDelete' => 'CASCADE'], 'flz_bq_request_module_fk');
            $requests->addForeignKeyConstraint($lecturers, ['lecturer_id'], ['id'], ['onDelete' => 'RESTRICT'], 'flz_bq_request_lecturer_fk');
        }

        return $schema;
    }
}
