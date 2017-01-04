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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\ltiProctoring\model;

use oat\oatbox\service\ConfigurableService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use \taoLti_models_classes_LtiLaunchData as LtiLaunchData;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;

/**
 * Sample Delivery Service for proctoring
 *
 * @author Joel Bout <joel@taotesting.com>
 */
class LtiListenerService extends ConfigurableService
{
    const SERVICE_ID = 'ltiProctoring/LtiListener';
    
    public function executionCreated(DeliveryExecutionCreated $event)
    {
        $session = \common_session_SessionManager::getSession();
        if ($session instanceof \taoLti_models_classes_TaoLtiSession) {
            $contextId = $session->getLaunchData()->getVariable(LtiLaunchData::CONTEXT_ID);
            $resourceLink = $session->getLaunchData()->getResourceLinkID();

            $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID)->log(
                $event->getDeliveryExecution()->getIdentifier(), 'LTI_DELIVERY_EXECUTION_CREATED', [
                    LtiLaunchData::CONTEXT_ID => $contextId,
                    LtiLaunchData::CONTEXT_LABEL => $session->getLaunchData()->getVariable(LtiLaunchData::CONTEXT_LABEL),
                    LtiLaunchData::RESOURCE_LINK_ID => $resourceLink,
                ]
            );
            $monitoringService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
            $data = $monitoringService->getData($event->getDeliveryExecution());
            $data->update(LtiLaunchData::CONTEXT_ID, $contextId);
            $data->update(LtiLaunchData::RESOURCE_LINK_ID, $resourceLink);
            $success = $monitoringService->save($data);
            if (!$success) {
                \common_Logger::w('monitor cache for delivery ' . $event->getDeliveryExecution()->getIdentifier() . ' could not be created');
            }
        }
    }
}
