<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version000004Date202610260001 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('flz_bq_templates')) {
            $templates = $schema->createTable('flz_bq_templates');
            $templates->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $templates->addColumn('template_key', Types::STRING, ['notnull' => true, 'length' => 64]);
            $templates->addColumn('title', Types::STRING, ['notnull' => true, 'length' => 128]);
            $templates->addColumn('version', Types::INTEGER, ['notnull' => true, 'unsigned' => true, 'default' => 1]);
            $templates->addColumn('created_by', Types::STRING, ['notnull' => true, 'length' => 64]);
            $templates->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
            $templates->addColumn('updated_by', Types::STRING, ['notnull' => true, 'length' => 64]);
            $templates->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
            $templates->setPrimaryKey(['id']);
            $templates->addUniqueIndex(['template_key'], 'flz_bq_template_key_uniq');
        } else {
            $templates = $schema->getTable('flz_bq_templates');
        }

        if (!$schema->hasTable('flz_bq_template_modules')) {
            $modules = $schema->createTable('flz_bq_template_modules');
            $modules->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $modules->addColumn('template_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $modules->addColumn('module_key', Types::STRING, ['notnull' => true, 'length' => 64]);
            $modules->addColumn('title', Types::STRING, ['notnull' => true, 'length' => 128]);
            $modules->addColumn('minutes', Types::SMALLINT, ['notnull' => true, 'unsigned' => true]);
            $modules->addColumn('position', Types::INTEGER, ['notnull' => true, 'unsigned' => true, 'default' => 0]);
            $modules->setPrimaryKey(['id']);
            $modules->addUniqueIndex(['template_id', 'module_key'], 'flz_bq_template_module_key_uniq');
            $modules->addIndex(['template_id', 'position'], 'flz_bq_template_module_order_idx');
            $modules->addForeignKeyConstraint($templates, ['template_id'], ['id'], ['onDelete' => 'CASCADE'], 'flz_bq_template_module_template_fk');
        }

        $runs = $schema->getTable('flz_bq_runs');
        if (!$runs->hasColumn('template_id')) {
            $runs->addColumn('template_id', Types::BIGINT, ['notnull' => false, 'unsigned' => true]);
        }
        if (!$runs->hasColumn('template_version')) {
            $runs->addColumn('template_version', Types::INTEGER, ['notnull' => false, 'unsigned' => true]);
        }
        if (!$runs->hasForeignKey('flz_bq_run_template_fk')) {
            $runs->addForeignKeyConstraint($templates, ['template_id'], ['id'], ['onDelete' => 'SET NULL'], 'flz_bq_run_template_fk');
        }

        $runModules = $schema->getTable('flz_bq_modules');
        $runModules->changeColumn('module_date', ['notnull' => false]);
        $runModules->changeColumn('starts_at', ['notnull' => false]);
        $runModules->changeColumn('ends_at', ['notnull' => false]);

        return $schema;
    }
}
