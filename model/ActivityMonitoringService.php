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

namespace oat\ltiProctoring\model;

use oat\ltiDeliveryProvider\model\actions\GetActiveDeliveryExecution;
use oat\tao\model\actionQueue\ActionQueue;
use oat\tao\model\actionQueue\implementation\InstantActionQueue;
use oat\taoProctoring\model\ActivityMonitoringService as BaseActivityMonitoringService;
use oat\taoLti\models\classes\LtiRoles;
use oat\taoProctoring\model\ProctorService;

/**
 * Service to manage and monitor assessment activity
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class ActivityMonitoringService extends BaseActivityMonitoringService
{

    /** Total testtakers awaiting in the queue */
    const FIELD_LOGIN_QUEUE = 'queue-test-takers';

    /**
     * Return comprehensive activity monitoring data.
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();
        $proctors = $this->getNumberOfActiveUsers(ProctorService::ROLE_PROCTOR) +
            $this->getNumberOfActiveUsers(LtiRoles::CONTEXT_TEACHING_ASSISTANT) +
            $this->getNumberOfActiveUsers(LtiRoles::CONTEXT_ADMINISTRATOR);

        $data[self::GROUPFIELD_USER_ACTIVITY][self::FIELD_ACTIVE_PROCTORS] = $proctors;
        $data[self::GROUPFIELD_USER_ACTIVITY][self::FIELD_LOGIN_QUEUE] = $this->getLoginQueueLength();

        return $data;
    }

    /**
     * Retrieve amount of users awaiting for available login slot
     * @return int
     */
    protected function getLoginQueueLength()
    {
        /** @var InstantActionQueue $actionQueue */
        $actionQueue = $this->getServiceLocator()->get(ActionQueue::SERVICE_ID);
        $activeDeliveryExecution = new GetActiveDeliveryExecution();

        if ($actionQueue->isActionEnabled($activeDeliveryExecution)) {
            $queue = $actionQueue->getPosition($activeDeliveryExecution);
        } else {
            $queue = __('Turned off');
        }

        return $queue;
    }

}
