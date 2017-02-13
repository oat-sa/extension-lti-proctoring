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

use oat\taoProctoring\model\authorization\ProctorAuthorizationProvider;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\taoDelivery\model\authorization\UnAuthorizedException;
use oat\oatbox\user\User;
use oat\taoLti\models\classes\LtiMessages\LtiErrorMessage;

/**
 * Manage the Delivery authorization.
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class LtiProctorAuthorizationProvider extends ProctorAuthorizationProvider
{

    const CUSTOM_LTI_PROCTORED = 'custom_proctored';

    /**
     * @param DeliveryExecution $deliveryExecution
     * @param User $user
     * @throws UnAuthorizedException
     * @throws \taoLti_models_classes_LtiException
     */
    public function verifyResumeAuthorization(DeliveryExecution $deliveryExecution, User $user)
    {
        $state = $deliveryExecution->getState()->getUri();
        if (in_array($state, [ProctoredDeliveryExecution::STATE_FINISHED, ProctoredDeliveryExecution::STATE_TERMINATED])) {
            throw new UnAuthorizedException(
                _url('index', 'DeliveryServer', 'taoProctoring'),
                'Terminated/Finished delivery cannot be resumed'
            );
        }

        $currentSession = \common_session_SessionManager::getSession();

        $proctored = true;

        if ($currentSession instanceof \taoLti_models_classes_TaoLtiSession) {
            /** @var \taoLti_models_classes_LtiLaunchData $launchData */
            $launchData = \common_session_SessionManager::getSession()->getLaunchData();
            if ($launchData->hasVariable(self::CUSTOM_LTI_PROCTORED)) {
                $var = mb_strtolower($launchData->getVariable(self::CUSTOM_LTI_PROCTORED));
                if ($var !== 'true' && $var !== 'false') {
                    throw new \taoLti_models_classes_LtiException(
                        'Wrong value of `'.self::CUSTOM_LTI_PROCTORED.'` variable.',
                        LtiErrorMessage::ERROR_WRONG_PARAMETER_VALUE
                    );
                }
                $proctored = filter_var($var, FILTER_VALIDATE_BOOLEAN);
            }
        }

        if ($proctored && $state !== ProctoredDeliveryExecution::STATE_AUTHORIZED) {
            $errorPage = _url('awaitingAuthorization', 'DeliveryServer', 'taoProctoring', array('deliveryExecution' => $deliveryExecution->getIdentifier()));
            throw new UnAuthorizedException($errorPage, 'Proctor authorization missing');
        }
    }
}
