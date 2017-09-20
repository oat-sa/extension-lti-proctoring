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
namespace oat\ltiProctoring\model\delivery;

use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\authorization\UnAuthorizedException;
use oat\taoLti\models\classes\LtiMessages\LtiErrorMessage;
use oat\oatbox\user\User;
use oat\taoProctoring\model\DelegatedServiceHandler;
use oat\taoProctoring\model\execution\DeliveryExecutionManagerService;
use oat\taoQtiTest\models\runner\time\QtiTimer;

/**
 * Manage the Delivery authorization.
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class LtiTestTakerAuthorizationService extends TestTakerAuthorizationService implements DelegatedServiceHandler
{

    const CUSTOM_LTI_PROCTORED = 'custom_proctored';
    const CUSTOM_LTI_EXTENDED_TIME = 'custom_extended_time';

    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\authorization\TestTakerAuthorizationService::isProctored()
     */
    public function isProctored($deliveryId, User $user)
    {
        $proctored = parent::isProctored($deliveryId, $user);
        $currentSession = \common_session_SessionManager::getSession();
        if ($currentSession instanceof \taoLti_models_classes_TaoLtiSession) {
            /** @var \taoLti_models_classes_LtiLaunchData $launchData */
            $launchData = \common_session_SessionManager::getSession()->getLaunchData();
            if ($launchData->hasVariable(self::CUSTOM_LTI_PROCTORED)) {
                $var = mb_strtolower($launchData->getVariable(self::CUSTOM_LTI_PROCTORED));
                if ($var !== 'true' && $var !== 'false') {
                    throw new \taoLti_models_classes_LtiException(
                        'Wrong value of `'.self::CUSTOM_LTI_PROCTORED.'` variable.',
                        LtiErrorMessage::ERROR_INVALID_PARAMETER
                    );
                }
                $proctored = filter_var($var, FILTER_VALIDATE_BOOLEAN);
                $this->checkExtendedTime();
            }
        }
        return $proctored;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\authorization\TestTakerAuthorizationService::throwUnAuthorizedException()
     */
    protected function throwUnAuthorizedException(DeliveryExecution $deliveryExecution)
    {
        $currentSession = \common_session_SessionManager::getSession();
        if ($currentSession instanceof \taoLti_models_classes_TaoLtiSession) {
            $errorPage = _url('awaitingAuthorization', 'DeliveryServer', 'ltiProctoring', array('deliveryExecution' => $deliveryExecution->getIdentifier()));
        } else {
            $errorPage = _url('awaitingAuthorization', 'DeliveryServer', 'taoProctoring', array('deliveryExecution' => $deliveryExecution->getIdentifier()));
        }
        throw new UnAuthorizedException($errorPage, 'Proctor authorization missing');
    }

    /**
     * @param User $user
     * @param null $deliveryId
     * @return bool
     */
    public function isSuitable(User $user, $deliveryId = null)
    {
        return is_a($user, \taoLti_models_classes_LtiUser::class);
    }

    /**
     * Check extended time from LTI session
     */
    protected function checkExtendedTime()
    {
        $request = \Context::getInstance()->getRequest();
        $deliveryExecution = null;
        if ($deliveryExecutionUri = $request->getParameter('deliveryExecution')) {
            $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($deliveryExecutionUri);
        }

        $launchData = \taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLaunchData();
        $extendedTime = 0;
        if ($launchData->hasVariable(self::CUSTOM_LTI_EXTENDED_TIME)) {
            $extendedTime = floatval($launchData->getVariable(self::CUSTOM_LTI_EXTENDED_TIME));
        }

        $this->updateDeliveryExtendedTime($deliveryExecution, $extendedTime);
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     * @param $extendedTime
     */
    protected function updateDeliveryExtendedTime(DeliveryExecution $deliveryExecution, $extendedTime)
    {
        /** @var DeliveryExecutionManagerService  $deliveryExecutionManagerService */
        $deliveryExecutionManagerService = $this->getServiceLocator()->get(DeliveryExecutionManagerService::SERVICE_ID);
        /** @var QtiTimer $timer */
        $timer = $deliveryExecutionManagerService->getDeliveryTimer($deliveryExecution);
        $deliveryExecutionArray = [
            $deliveryExecution
        ];

        $extendedTime = (!$extendedTime) ? 1 : $extendedTime;
        if ($extendedTime) {
            $deliveryExecutionManagerService->setExtraTime(
                $deliveryExecutionArray,
                $timer->getExtraTime(),
                $extendedTime
            );
        }
    }
}
