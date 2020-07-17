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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */

namespace oat\ltiProctoring\controller;

use common_Exception;
use common_exception_Error;
use common_exception_NotFound;
use common_exception_Unauthorized;
use InterruptedActionException;
use oat\tao\helpers\UrlHelper;
use oat\taoDelivery\model\authorization\UnAuthorizedException;
use oat\taoProctoring\controller\DeliveryServer as ProctoringDeliveryServer;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\ltiDeliveryProvider\model\LTIDeliveryTool;
use oat\taoLti\models\classes\LtiService;
use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiMessages\LtiErrorMessage;
use oat\taoQtiTest\models\QtiTestExtractionFailedException;

/**
 * Override the default DeliveryServer Controller
 *
 * @package ltiProctoring
 */
class DeliveryServer extends ProctoringDeliveryServer
{
    /**
     * Delivery execution authorization awaiting screen
     * Overrides cancel URL
     */
    public function awaitingAuthorization()
    {
        try {
            parent::awaitingAuthorization();

        }catch (QtiTestExtractionFailedException $e) {
            throw new LtiException($e->getMessage());
        }
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        $this->setData('cancelUrl', _url('cancelExecution', 'DeliveryServer', 'ltiProctoring', ['deliveryExecution' => $deliveryExecution->getIdentifier()]));
    }

    /**
     * @throws LtiException
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_exception_NotFound|InterruptedActionException
     */
    public function runDeliveryExecution()
    {
        $deliveryExecution = $this->getCurrentDeliveryExecution();

        if (!$this->hasSessionAttribute(DeliveryExecution::getDeliveryIdSessionKey($deliveryExecution->getIdentifier()))) {
            $this->setSessionAttribute(
                DeliveryExecution::getDeliveryIdSessionKey($deliveryExecution->getIdentifier()),
                $deliveryExecution->getDelivery()->getUri()
            );
        }

        try {
            $this->verifyDeliveryExecutionAuthorized($deliveryExecution);

            parent::runDeliveryExecution();
        } catch (UnAuthorizedException $e) {
            // for the lti - correct lti error (link with error message)
            $redirectUrl = $this->getServiceLocator()->get(LTIDeliveryTool::class)->getFinishUrl($deliveryExecution);
            $this->redirect($redirectUrl);
        }
    }

    /**
     * Redirect user to return URL
     */
    public function finishDeliveryExecution()
    {
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        if ($deliveryExecution->getState()->getUri() == ProctoredDeliveryExecution::STATE_PAUSED) {
            $redirectUrl = $this->getServiceLocator()->get(UrlHelper::class)->buildUrl(
                'awaitingAuthorization',
                'DeliveryServer',
                'ltiProctoring',
                [
                    'deliveryExecution' => $deliveryExecution->getIdentifier()
                ]
            );
        } else {
            $redirectUrl = $this->getServiceLocator()->get(LTIDeliveryTool::class)->getFinishUrl($deliveryExecution);
        }

        $this->redirect($redirectUrl);
    }

    /**
     * Overrides the return URL
     * @return string the URL
     */
    protected function getReturnUrl()
    {
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        return _url('finishDeliveryExecution', 'DeliveryServer', 'ltiProctoring',
            ['deliveryExecution' => $deliveryExecution->getIdentifier()]
        );
    }

    /**
     * @return DeliveryExecution
     * @throws LtiException if given delivery exection does not correspond to current lti session
     * @throws \Exception
     */
    protected function getCurrentDeliveryExecution()
    {
        $deliveryExecution = parent::getCurrentDeliveryExecution();
        $link = LtiService::singleton()->getLtiSession()->getLtiLinkResource();
        $user = \common_session_SessionManager::getSession()->getUser();
        /** @var LtiDeliveryExecutionService $deliveryExecutionService */
        $deliveryExecutionService = $this->getServiceLocator()->get(LtiDeliveryExecutionService::SERVICE_ID);
        $linkedDeliveryExecutions = $deliveryExecutionService->getLinkedDeliveryExecutions($deliveryExecution->getDelivery(), $link, $user->getIdentifier());

        foreach ($linkedDeliveryExecutions as $linkedDeliveryExecution) {
            if ($linkedDeliveryExecution->getIdentifier() === $deliveryExecution->getIdentifier()) {
                return $deliveryExecution;
            }
        }

        throw new LtiException(
            'Delivery execution identifier is not linked with current resource_link_id',
            LtiErrorMessage::ERROR_INVALID_PARAMETER
        );
    }
}
