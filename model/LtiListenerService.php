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

use oat\ltiProctoring\model\delivery\ProctorService;
use oat\ltiProctoring\model\execution\LtiDeliveryExecutionService;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\execution\DeliveryExecution;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use taoLti_models_classes_LtiLaunchData as LtiLaunchData;

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
            $deliveryExecution = $event->getDeliveryExecution();
            $executionId = $deliveryExecution->getIdentifier();
            $deliveryLog = $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);

            $launchData = $session->getLaunchData();
            $contextId = $launchData->getVariable(LtiLaunchData::CONTEXT_ID);

            $tagsString = '';
            if ($launchData->hasVariable(ProctorService::CUSTOM_TAG)) {
                $tags = (array)$launchData->getVariable(ProctorService::CUSTOM_TAG);
                $tagsString = implode(',', $tags);
                $tagsString = str_pad($tagsString, strlen($tagsString) + 2, ',', STR_PAD_BOTH);
            }
            $resourceLink = $launchData->getResourceLinkID();
            $deliveryLog->log($executionId, 'LTI_DELIVERY_EXECUTION_CREATED', [
                LtiLaunchData::CONTEXT_ID => $contextId,
                LtiLaunchData::CONTEXT_LABEL => $launchData->getVariable(LtiLaunchData::CONTEXT_LABEL),
                LtiLaunchData::RESOURCE_LINK_ID => $resourceLink,
                ProctorService::CUSTOM_TAG => $tagsString,
            ]);

            $monitoringService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
            $data = $monitoringService->getData($event->getDeliveryExecution());
            $data->update(LtiLaunchData::CONTEXT_ID, $contextId);
            $data->update(LtiLaunchData::RESOURCE_LINK_ID, $resourceLink);
            $data->update(ProctorService::CUSTOM_TAG, $tagsString);

            if ($launchData->hasVariable(LtiDeliveryExecutionService::LTI_USER_NAME)) {
                $ltiUserName = $launchData->getVariable(LtiDeliveryExecutionService::LTI_USER_NAME);
                $deliveryLog->log($executionId, 'LTI_USER_NAME', $ltiUserName);

                $data->update(LtiDeliveryExecutionService::LTI_USER_NAME, $ltiUserName);
            }

            $success = $monitoringService->save($data);
            if (!$success) {
                \common_Logger::w('monitor cache for delivery ' . $deliveryExecution->getIdentifier() . ' could not be created');
            }
        }
    }

    public function executionStateChanged(DeliveryExecutionState $event)
    {
        $session = \common_session_SessionManager::getSession();
        if ($session instanceof \taoLti_models_classes_TaoLtiSession) {
            $launchData = $session->getLaunchData();
            if ($event->getState() == DeliveryExecution::STATE_ACTIVE &&
                $event->getPreviousState() == DeliveryExecution::STATE_AUTHORIZED &&
                $launchData->hasVariable(LtiDeliveryExecutionService::LTI_USER_NAME)
            ) {
                $ltiUserName = $launchData->getVariable(LtiDeliveryExecutionService::LTI_USER_NAME);
                $deliveryExecution = $event->getDeliveryExecution();
                $executionId = $deliveryExecution->getIdentifier();

                $deliveryLog = $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);
                $deliveryLog->log($executionId, 'LTI_USER_NAME', $ltiUserName);

                $monitoringService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
                $data = $monitoringService->getData($deliveryExecution);
                $data->update(LtiDeliveryExecutionService::LTI_USER_NAME, $ltiUserName);

                $success = $monitoringService->save($data);
                if (!$success) {
                    \common_Logger::w('monitor cache for delivery ' . $executionId . ' could not be updated');
                }
            }
        }
    }
}
