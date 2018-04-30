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
 */

namespace oat\ltiProctoring\model\execution;

use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\ltiDeliveryProvider\model\execution\implementation\LtiDeliveryExecutionService as BaseImplementation;

/**
 * Class LtiDeliveryExecutionService
 * @package oat\ltiDeliveryProvider\model\execution
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class LtiDeliveryExecutionService extends BaseImplementation
{
    const LTI_USER_NAME = 'custom_username';
    
    /**
     * Returns an array of DeliveryExecution
     *
     * @param \core_kernel_classes_Resource $delivery
     * @param \core_kernel_classes_Resource $link
     * @param string $userId
     * @return DeliveryExecution[]
     */
    public function getLinkedDeliveryExecutions(\core_kernel_classes_Resource $delivery, \core_kernel_classes_Resource $link, $userId)
    {
        $result = parent::getLinkedDeliveryExecutions($delivery, $link, $userId);
        return array_filter($result, function($execution) {
            return $execution->getState()->getUri() !== ProctoredDeliveryExecution::STATE_CANCELED;
        });
    }

    /**
     * @return int
     */
    public function getNumberOfActiveDeliveryExecutions()
    {
        /** @var DeliveryMonitoringService $deliveryMonitoring */
        $deliveryMonitoring = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        return $deliveryMonitoring->count([
            [DeliveryMonitoringService::STATUS => ProctoredDeliveryExecution::STATE_ACTIVE],
        ]);
    }
}
