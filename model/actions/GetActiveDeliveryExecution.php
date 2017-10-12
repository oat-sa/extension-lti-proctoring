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

namespace oat\ltiProctoring\model\actions;

use oat\tao\model\actionQueue\AbstractQueuedAction;
use oat\ltiDeliveryProvider\model\LTIDeliveryTool;
use oat\ltiDeliveryProvider\controller\DeliveryTool;
use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;

/**
 * Class GetActiveDeliveryExecution
 * @package oat\ltiProctoring\model\actions
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class GetActiveDeliveryExecution extends AbstractQueuedAction
{
    protected $delivery;

    public function __construct(\core_kernel_classes_Resource $delivery)
    {
        $this->delivery = $delivery;
    }

    /**
     * @param $params
     * @return DeliveryExecution
     * @throws \common_exception_Error
     * @throws \common_exception_Unauthorized
     * @throws \oat\taoLti\models\classes\LtiVariableMissingException
     * @throws \taoLti_models_classes_LtiException
     */
    public function __invoke($params)
    {
        $remoteLink = \taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLtiLinkResource();
        $user = \common_session_SessionManager::getSession()->getUser();

        $launchData = \taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLaunchData();
        /** @var LtiDeliveryExecutionService $deliveryExecutionService */
        $deliveryExecutionService = $this->getServiceManager()->get(LtiDeliveryExecutionService::SERVICE_ID);
        if ($launchData->hasVariable(DeliveryTool::PARAM_FORCE_RESTART) && $launchData->getVariable(DeliveryTool::PARAM_FORCE_RESTART) == 'true') {
            // ignore existing executions to force restart
            $executions = array();
        } else {
            $executions = $deliveryExecutionService->getLinkedDeliveryExecutions($this->delivery, $remoteLink, $user->getIdentifier());
        }

        $active = null;

        if (empty($executions)) {
            $active = $this->getTool()->startDelivery($this->delivery, $remoteLink, $user);
        } else {
            foreach ($executions as $deliveryExecution) {
                if (!$deliveryExecutionService->isFinished($deliveryExecution)) {
                    $active = $deliveryExecution;
                    break;
                }
            }
        }
        return $active;
    }

    /**
     * @return int
     * @throws \common_exception_Error
     */
    public function getNumberOfActiveActions()
    {
        /** @var DeliveryMonitoringService $deliveryMonitoring */
        $deliveryMonitoring = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        $result = $deliveryMonitoring->count([
            ['status' => DeliveryExecution::STATE_ACTIVE]
        ]);
        return $result;
    }

    /**
     * @return LTIDeliveryTool
     */
    protected function getTool()
    {
        return LTIDeliveryTool::singleton();
    }
}