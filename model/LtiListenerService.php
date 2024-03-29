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
 * Copyright (c) 2015-2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\ltiProctoring\model;

use common_exception_Error;
use common_exception_MissingParameter;
use common_exception_NotFound;
use common_Logger;
use common_session_SessionManager;
use InvalidArgumentException;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\AgsClaim;
use oat\ltiProctoring\model\delivery\ProctorService;
use oat\ltiProctoring\model\execution\LtiDeliveryExecutionContext;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoDelivery\model\execution\DeliveryExecutionContext;
use oat\taoDelivery\model\execution\DeliveryExecutionContextInterface;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\TaoLtiSession;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\execution\DeliveryExecutionManagerService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoLti\models\classes\LtiVariableMissingException;
use oat\taoTests\models\runner\time\InvalidStorageException;

/**
 * Sample Delivery Service for proctoring
 *
 * @author Joel Bout <joel@taotesting.com>
 */
class LtiListenerService extends ConfigurableService
{
    public const SERVICE_ID = 'ltiProctoring/LtiListener';

    public const CUSTOM_LTI_EXTENDED_TIME = 'custom_extended_time';
    public const LTI_USER_NAME = 'custom_username';

    /**
     * @param DeliveryExecutionCreated $event
     * @throws LtiVariableMissingException
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     * @throws InvalidServiceManagerException
     * @throws ServiceNotFoundException
     */
    public function executionCreated(DeliveryExecutionCreated $event)
    {
        $session = common_session_SessionManager::getSession();

        if ($session instanceof TaoLtiSession) {
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
            /** @var DeliveryMonitoringData $data */
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

                if ($executionContext = $data->getDeliveryExecutionContext() === null) {
                    $executionContext = $this->createExecutionContext($executionId, $launchData);
                }
                if ($executionContext instanceof DeliveryExecutionContextInterface) {
                    $data->setDeliveryExecutionContext($executionContext);
                }

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

            $ltiCustomParameters = $this->getLtiCustomParams($session);

            // Log custom LTI parameters
            $deliveryLog->log($event->getDeliveryExecution()->getIdentifier(), 'LTI_PARAMETERS', $ltiCustomParameters);

            // Log non-custom LTI parameters
            $ltiParameters = array_diff_key($session->getLaunchData()->getVariables(), $ltiCustomParameters);

            if (isset($ltiParameters[LtiLaunchData::AGS_CLAIMS])
                && $ltiParameters[LtiLaunchData::AGS_CLAIMS] instanceof AgsClaim
            ) {
                $ltiParameters[LtiLaunchData::AGS_CLAIMS] = $ltiParameters[LtiLaunchData::AGS_CLAIMS]->normalize();
            }

            $deliveryLog->log($event->getDeliveryExecution()->getIdentifier(), 'LTI_LAUNCH_PARAMETERS', $ltiParameters);

            if ($launchData->hasVariable(self::LTI_USER_NAME)) {
                $ltiUserName = $launchData->getVariable(self::LTI_USER_NAME);
                $data->update(self::LTI_USER_NAME, $ltiUserName);
            }

            $success = $monitoringService->save($data);

            if (!$success) {
                $this->logWarning('Monitor cache for delivery ' . $executionId . ' could not be created');
            }
        }
    }

    /**
     * @param DeliveryExecutionState $event
     * @throws InvalidServiceManagerException
     * @throws InvalidStorageException
     * @throws LtiVariableMissingException
     * @throws ServiceNotFoundException
     * @throws common_exception_Error
     * @throws common_exception_MissingParameter
     * @throws common_exception_NotFound
     */
    public function executionStateChanged(DeliveryExecutionState $event)
    {
        $session = common_session_SessionManager::getSession();
        if ($session instanceof TaoLtiSession) {
            $launchData = $session->getLaunchData();
            $deliveryExecution = $event->getDeliveryExecution();
            if (
                $event->getState() == DeliveryExecutionInterface::STATE_ACTIVE
                && $launchData->hasVariable(self::LTI_USER_NAME)
            ) {
                $ltiUserName = $launchData->getVariable(self::LTI_USER_NAME);
                $executionId = $deliveryExecution->getIdentifier();
                $serviceManager = $this->getServiceManager();

                $monitoringService = $serviceManager->get(DeliveryMonitoringService::SERVICE_ID);
                $data = $monitoringService->getData($deliveryExecution);
                $data->update(self::LTI_USER_NAME, $ltiUserName);

                $success = $monitoringService->save($data);
                if (!$success) {
                    common_Logger::w('monitor cache for delivery ' . $executionId . ' could not be updated');
                }
            }

            if ($event->getPreviousState() == DeliveryExecutionInterface::STATE_PAUSED) {
                $this->checkExtendedTime($launchData, $deliveryExecution);
            }
        }
    }

    /**
     * Check extended time from LTI session
     *
     * @param LtiLaunchData $launchData
     * @param DeliveryExecutionInterface $deliveryExecution
     * @throws LtiVariableMissingException
     * @throws common_exception_MissingParameter
     * @throws InvalidStorageException
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    public function checkExtendedTime(LtiLaunchData $launchData, DeliveryExecutionInterface $deliveryExecution)
    {
        $extendedTimeMultiplier = 1.0;
        if ($launchData->hasVariable(self::CUSTOM_LTI_EXTENDED_TIME)) {
            $extendedTimeVariable = (float) $launchData->getVariable(self::CUSTOM_LTI_EXTENDED_TIME);
            if (!empty($extendedTimeVariable)) {
                $extendedTimeMultiplier = $extendedTimeVariable;
            }
        }

        /** @var DeliveryExecutionManagerService $deliveryExecutionManagerService */
        $deliveryExecutionManagerService = $this->getServiceLocator()->get(DeliveryExecutionManagerService::SERVICE_ID);
        $deliveryExecutionManagerService->updateDeliveryExtendedTime($deliveryExecution, $extendedTimeMultiplier);
    }


    /** Get LTI launch parameters which name starts from 'custom_' */
    protected function getLtiCustomParams(TaoLtiSession $session): array
    {
        return array_filter(
            $session->getLaunchData()->getVariables(),
            static fn($key) => strpos($key, 'custom_') === 0,
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param string $executionId
     * @param LtiLaunchData $launchData
     * @return DeliveryExecutionContext|null
     */
    private function createExecutionContext(
        string $executionId,
        LtiLaunchData $launchData
    ): DeliveryExecutionContextInterface {
        $executionContext = null;
        try {
            $executionContext = new DeliveryExecutionContext(
                $executionId,
                $launchData->getVariable(LtiLaunchData::CONTEXT_ID),
                LtiDeliveryExecutionContext::EXECUTION_CONTEXT_TYPE,
                $launchData->getVariable(LtiLaunchData::CONTEXT_LABEL)
            );
        } catch (InvalidArgumentException | LtiVariableMissingException $e) {
            $this->logInfo('Delivery execution context object can not be created. Reason: ' . $e->getMessage());
        }

        return $executionContext;
    }
}
