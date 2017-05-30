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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\ltiProctoring\scripts\install;

use oat\ltiProctoring\model\LtiListenerService;
use oat\oatbox\extension\InstallAction;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoLti\models\classes\LtiRoles;
use oat\taoProctoring\model\TestSessionHistoryService;

/**
 * Register a listener for newly created deliveries
 */
class SetupTestSessionHistory extends InstallAction
{
    /**
     * @param $params
     */
    public function __invoke($params)
    {
        /** @var TestSessionHistoryService $historyService */
        $historyService = $this->getServiceManager()->get(TestSessionHistoryService::SERVICE_ID);
        $roles = $historyService->getOption(TestSessionHistoryService::PROCTOR_ROLES);
        if(is_null($roles)){
            $roles = [];
        }
        $roles[] = LtiRoles::CONTEXT_TEACHING_ASSISTANT;
        $historyService->setOption(TestSessionHistoryService::PROCTOR_ROLES, $roles);
        $this->getServiceManager()->register(TestSessionHistoryService::SERVICE_ID, $historyService);
    }
}
