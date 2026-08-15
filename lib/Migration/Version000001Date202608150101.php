<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version000001Date202608150101 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('adbq_runs')) {
            $runs = $schema->createTable('adbq_runs');
            $runs->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $runs->addColumn('label', Types::STRING, ['notnull' => true, 'length' => 128]);
            $runs->addColumn('starts_on', Types::DATE_IMMUTABLE, ['notnull' => true]);
            $runs->addColumn('ends_on', Types::DATE_IMMUTABLE, ['notnull' => true]);
            $runs->addColumn('capacity', Types::SMALLINT, ['notnull' => true, 'unsigned' => true, 'default' => 10]);
            $runs->addColumn('status', Types::STRING, ['notnull' => true, 'length' => 32, 'default' => 'draft']);
            $runs->addColumn('version', Types::INTEGER, ['notnull' => true, 'unsigned' => true, 'default' => 1]);
            $runs->addColumn('created_by', Types::STRING, ['notnull' => true, 'length' => 64]);
            $runs->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
            $runs->addColumn('updated_by', Types::STRING, ['notnull' => true, 'length' => 64]);
            $runs->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
            $runs->setPrimaryKey(['id']);
            $runs->addIndex(['starts_on'], 'adbq_runs_start_idx');
            $runs->addIndex(['status'], 'adbq_runs_status_idx');
        } else {
            $runs = $schema->getTable('adbq_runs');
        }

        if (!$schema->hasTable('adbq_modules')) {
            $modules = $schema->createTable('adbq_modules');
            $modules->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $modules->addColumn('run_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $modules->addColumn('module_key', Types::STRING, ['notnull' => true, 'length' => 64]);
            $modules->addColumn('title', Types::STRING, ['notnull' => true, 'length' => 128]);
            $modules->addColumn('minutes', Types::SMALLINT, ['notnull' => true, 'unsigned' => true]);
            $modules->addColumn('module_date', Types::DATE_IMMUTABLE, ['notnull' => true]);
            $modules->addColumn('starts_at', Types::STRING, ['notnull' => true, 'length' => 5]);
            $modules->addColumn('ends_at', Types::STRING, ['notnull' => true, 'length' => 5]);
            $modules->addColumn('additional_capacity', Types::SMALLINT, ['notnull' => true, 'unsigned' => true, 'default' => 0]);
            $modules->addColumn('position', Types::INTEGER, ['notnull' => true, 'unsigned' => true, 'default' => 0]);
            $modules->addColumn('version', Types::INTEGER, ['notnull' => true, 'unsigned' => true, 'default' => 1]);
            $modules->addColumn('created_by', Types::STRING, ['notnull' => true, 'length' => 64]);
            $modules->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
            $modules->setPrimaryKey(['id']);
            $modules->addUniqueIndex(['run_id', 'module_key'], 'adbq_module_key_uniq');
            $modules->addIndex(['run_id', 'position'], 'adbq_module_order_idx');
            $modules->addForeignKeyConstraint($runs, ['run_id'], ['id'], ['onDelete' => 'CASCADE'], 'adbq_module_run_fk');
        }

        return $schema;
    }
}
