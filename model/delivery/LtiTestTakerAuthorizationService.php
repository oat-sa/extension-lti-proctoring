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

use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\authorization\UnAuthorizedException;
use oat\taoLti\models\classes\LtiMessages\LtiErrorMessage;
use oat\oatbox\user\User;

/**
 * Manage the Delivery authorization.
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class LtiTestTakerAuthorizationService extends TestTakerAuthorizationService
{

    const CUSTOM_LTI_PROCTORED = 'custom_proctored';

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
}
