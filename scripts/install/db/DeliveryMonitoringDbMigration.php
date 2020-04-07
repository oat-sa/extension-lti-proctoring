<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */
declare(strict_types=1);

namespace oat\ltiProctoring\scripts\install\db;

use common_Exception;
use common_Logger;
use common_persistence_SqlPersistence;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;

class DeliveryMonitoringDbMigration
{
    /**
     * @param common_persistence_SqlPersistence $persistence
     */
    public function alterTable(common_persistence_SqlPersistence $persistence): void
    {
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        /** @var Schema $schema */
        $schema = $schemaManager->createSchema();

        /** @var Schema $fromSchema */
        $fromSchema = clone $schema;
        try {
            /** @var Table $table */
            $table = $schema->getTable(MonitoringStorage::TABLE_NAME);
            if ($table->hasColumn(LtiLaunchData::CONTEXT_ID)) {
                common_Logger::i(
                    sprintf(
                        'Column `%s` already exists in `%s` table.',
                        LtiLaunchData::CONTEXT_ID,
                        MonitoringStorage::TABLE_NAME
                    )
                );
                return;
            }

            $table->addColumn(LtiLaunchData::CONTEXT_ID, "string", array("notnull" => false, "length" => 255));
            $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $schema);
            foreach ($queries as $query) {
                $persistence->exec($query);
            }
        } catch(SchemaException $e) {
            common_Logger::i('Database Schema already up to date.');
        } catch (common_Exception $e) {
            common_Logger::e($e->getMessage());
        }
    }

    /**
     * @return array
     */
    public function getExtraColumns(): array
    {
        return [LtiLaunchData::CONTEXT_ID];
    }
}