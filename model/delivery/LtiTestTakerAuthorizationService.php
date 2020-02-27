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

use common_session_Session;
use oat\oatbox\session\SessionService;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiInvalidVariableException;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\TaoLtiSession;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\authorization\UnAuthorizedException;
use oat\oatbox\user\User;
use oat\taoProctoring\model\DelegatedServiceHandler;
use oat\taoLti\models\classes\LtiRoles;

/**
 * Manage the Delivery authorization.
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class LtiTestTakerAuthorizationService extends TestTakerAuthorizationService implements DelegatedServiceHandler
{

    const CUSTOM_LTI_PROCTORED = 'custom_proctored';

    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\authorization\TestTakerAuthorizationService::isProctored()
     * @param $deliveryId
     * @param User $user
     * @return bool|mixed
     * @throws LtiException
     * @throws \oat\taoLti\models\classes\LtiVariableMissingException
     */
    public function isProctored($deliveryId, User $user)
    {
        try {
            $proctored = parent::isProctored($deliveryId, $user);
            $currentSession = $this->getSession();
            if ($currentSession instanceof TaoLtiSession) {
                /** @var LtiLaunchData $launchData */
                $launchData = $currentSession->getLaunchData();
                if ($launchData->hasVariable(self::CUSTOM_LTI_PROCTORED)) {
                    $proctored = $launchData->getBooleanVariable(self::CUSTOM_LTI_PROCTORED);
                }
            }

            return $proctored;
        } catch (LtiInvalidVariableException $e) {
            throw new LtiException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @return common_session_Session
     */
    private function getSession()
    {
        return $this->getServiceLocator()->get(SessionService::SERVICE_ID)->getCurrentSession();
    }


    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\authorization\TestTakerAuthorizationService::throwUnAuthorizedException()
     * @param DeliveryExecution $deliveryExecution
     * @throws UnAuthorizedException
     * @throws \common_exception_Error
     */
    protected function throwUnAuthorizedException(DeliveryExecution $deliveryExecution)
    {
        if ($this->getSession() instanceof TaoLtiSession) {
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
        $ltiRoles = array_intersect([
            LtiRoles::CONTEXT_INSTRUCTOR,
            LtiRoles::CONTEXT_LEARNER,
            LtiRoles::CONTEXT_TEACHING_ASSISTANT,
            LtiRoles::CONTEXT_ADMINISTRATOR,
        ], $user->getRoles());
        return !empty($ltiRoles);
    }
}
