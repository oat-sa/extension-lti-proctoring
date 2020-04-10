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

namespace oat\ltiProctoring\scripts\install;

use common_Exception;
use common_report_Report as Report;
use oat\ltiProctoring\scripts\install\db\DeliveryMonitoringDbMigration;
use oat\oatbox\extension\InstallAction;
use oat\taoProctoring\model\monitorCache\implementation\MonitorCacheService;
use oat\taoProctoring\scripts\install\db\DbSetup;

class AlterDeliveryMonitoringTable extends InstallAction
{
    /**
     * @param $params
     * @return Report
     * @throws \common_exception_Error
     */
    public function __invoke($params): Report
    {
        $report = Report::createInfo("Start delivery monitoring table migration.");
        try {
            $monitoringCacheService = $this->getServiceManager()->get(MonitorCacheService::SERVICE_ID);
            $persistence = $monitoringCacheService->getPersistence();

            $dbMigration = new DeliveryMonitoringDbMigration();
            $dbMigration->alterTable($persistence);

            $newPrimaryColumns = array_unique(array_merge(DbSetup::getPrimaryColumns(),$dbMigration->getExtraColumns()));
            $monitoringCacheService->setOption(MonitorCacheService::OPTION_PRIMARY_COLUMNS, $newPrimaryColumns);

            $this->registerService(MonitorCacheService::SERVICE_ID, $monitoringCacheService);
            $report->add(Report::createSuccess("Migration successfully executed."));
        } catch (common_Exception $exception) {
            $report->add(Report::createFailure("Error during migration: " . $exception->getMessage()));
        }

        return $report;
    }
}