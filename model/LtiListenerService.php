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
use oat\taoLti\models\classes\LtiVariableMissingException;

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
            $serviceManager = $this->getServiceManager();
            $deliveryLog = $serviceManager->get(DeliveryLog::SERVICE_ID);

            $launchData = $session->getLaunchData();
            $tagsString = '';
            if ($launchData->hasVariable(ProctorService::CUSTOM_TAG)) {
                $tags = (array)$launchData->getVariable(ProctorService::CUSTOM_TAG);
                $tagsString = implode(',', $tags);
                $tagsString = str_pad($tagsString, strlen($tagsString) + 2, ',', STR_PAD_BOTH);
            }

            $monitoringService = $serviceManager->get(DeliveryMonitoringService::SERVICE_ID);
            $data = $monitoringService->getData($deliveryExecution);

            // tag data
            $logData = [
                ProctorService::CUSTOM_TAG => $tagsString,
            ];
            $data->update(ProctorService::CUSTOM_TAG, $tagsString);

            // context
            try {
                $contextId = $launchData->getVariable(LtiLaunchData::CONTEXT_ID);
                $data->update(LtiLaunchData::CONTEXT_ID, $contextId);
                $logData[LtiLaunchData::CONTEXT_ID] = $contextId;
                $logData[LtiLaunchData::CONTEXT_LABEL] = $launchData->getVariable(LtiLaunchData::CONTEXT_LABEL);
            } catch (LtiVariableMissingException $e) {
            }

            // resource
            try {
                $resourceLink = $launchData->getResourceLinkID();
                $logData[LtiLaunchData::RESOURCE_LINK_ID] = $resourceLink;
                $data->update(LtiLaunchData::RESOURCE_LINK_ID, $resourceLink);
            } catch (LtiVariableMissingException $e) {
            }
            $deliveryLog->log($executionId, 'LTI_DELIVERY_EXECUTION_CREATED', $logData);

            $ltiParameters = $this->getLtiCustomParams($session);
            $deliveryLog->log($event->getDeliveryExecution()->getIdentifier(), 'LTI_PARAMETERS', $ltiParameters);

            if ($launchData->hasVariable(LtiDeliveryExecutionService::LTI_USER_NAME)) {
                $ltiUserName = $launchData->getVariable(LtiDeliveryExecutionService::LTI_USER_NAME);
                $data->update(LtiDeliveryExecutionService::LTI_USER_NAME, $ltiUserName);
            }

            $success = $monitoringService->save($data);
            if (!$success) {
                \common_Logger::w('monitor cache for delivery ' . $executionId . ' could not be created');
            }
        }
    }

    public function executionStateChanged(DeliveryExecutionState $event)
    {
        $session = \common_session_SessionManager::getSession();
        if ($session instanceof \taoLti_models_classes_TaoLtiSession) {
            $launchData = $session->getLaunchData();
            if ($event->getState() == DeliveryExecution::STATE_ACTIVE &&
                $launchData->hasVariable(LtiDeliveryExecutionService::LTI_USER_NAME)
            ) {
                $ltiUserName = $launchData->getVariable(LtiDeliveryExecutionService::LTI_USER_NAME);
                $deliveryExecution = $event->getDeliveryExecution();
                $executionId = $deliveryExecution->getIdentifier();
                $serviceManager = $this->getServiceManager();

                $monitoringService = $serviceManager->get(DeliveryMonitoringService::SERVICE_ID);
                $data = $monitoringService->getData($deliveryExecution);
                $data->update(LtiDeliveryExecutionService::LTI_USER_NAME, $ltiUserName);

                $success = $monitoringService->save($data);
                if (!$success) {
                    \common_Logger::w('monitor cache for delivery ' . $executionId . ' could not be updated');
                }
            }
        }
    }

    /**
     * Get LTI launch parameters which name starts from 'custom_'
     * @param \taoLti_models_classes_TaoLtiSession $session
     * @return array
     */
    protected function getLtiCustomParams(\taoLti_models_classes_TaoLtiSession $session)
    {
        $ltiParameters = array_filter(
            $session->getLaunchData()->getVariables(),
            function ($key) {
                return strpos($key, 'custom_') === 0;
            },
            ARRAY_FILTER_USE_KEY
        );
        return $ltiParameters;
    }
}
