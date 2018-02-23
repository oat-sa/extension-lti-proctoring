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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\ltiProctoring\scripts\install;

use oat\ltiProctoring\model\LtiListenerService;
use oat\oatbox\extension\InstallAction;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;

/**
 * Register a listener for newly created deliveries
 */
class SetupProctoringEventListeners extends InstallAction
{
    /**
     * @param $params
     * @return \common_report_Report
     */
    public function __invoke($params)
    {
        // monitoring cache
        $this->registerEvent(DeliveryExecutionCreated::class, [LtiListenerService::SERVICE_ID, 'executionCreated']);
        $this->registerEvent(DeliveryExecutionState::class, [LtiListenerService::SERVICE_ID, 'executionStateChanged']);

        return \common_report_Report::createSuccess("successfull ");
    }
}
