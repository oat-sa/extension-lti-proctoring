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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\ltiProctoring\model\delivery;

use oat\oatbox\log\LoggerService;
use oat\oatbox\user\User;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\user\LtiUser;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationDelegator;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\taoQtiTest\models\TestSessionService;

class AutoStartProctoredDeliveryService
{
    private const CUSTOM_LTI_AUTOSTART = 'custom_autostart';

    /**
     * @var TestSessionService
     */
    private $testSessionService;

    /**
     * @var TestTakerAuthorizationDelegator
     */
    private $testTakerAuthorizationDelegator;

    /**
     * @var LoggerService
     */
    private $logger;

    public function __construct(
        TestSessionService $testSessionService,
        TestTakerAuthorizationDelegator $testTakerAuthorizationDelegator,
        LoggerService $logger
    ) {
        $this->testSessionService = $testSessionService;
        $this->testTakerAuthorizationDelegator = $testTakerAuthorizationDelegator;
        $this->logger = $logger;
    }

    public function execute(DeliveryExecution $deliveryExecution, User $user): bool
    {
        $deliveryUri = $deliveryExecution->getDelivery()->getUri();
        if (false === $this->testTakerAuthorizationDelegator->isProctored($deliveryUri, $user)) {
            return false;
        }

        if (false === $this->shouldSkipManualAuthorization($deliveryExecution, $user)) {
            return false;
        }

        $this->changeStateToAuthorized($deliveryExecution);

        return true;
    }

    private function shouldSkipManualAuthorization(DeliveryExecution $deliveryExecution, User $user): bool
    {
        if ($this->isInitialStart($deliveryExecution)) {
            return $this->isAutostartEnabled($user);
        }

        return false;
    }

    private function changeStateToAuthorized(DeliveryExecution $deliveryExecution): void
    {
        $deliveryExecution->getImplementation()->setState(ProctoredDeliveryExecution::STATE_AUTHORIZED);

        $this->logger->info(
            sprintf(
                'Changes in the status of delivery execution to authorized. Delivery Execution Identifier: %s',
                $deliveryExecution->getIdentifier()
            ),
            [
                'identifier' => $deliveryExecution->getIdentifier(),
                'uri' => $deliveryExecution->getDelivery()->getUri(),
                'state' => ProctoredDeliveryExecution::STATE_AUTHORIZED
            ]
        );
    }

    private function isInitialStart(DeliveryExecution $deliveryExecution): bool
    {
        return null === $this->testSessionService->getTestSession($deliveryExecution);
    }

    private function isAutostartEnabled(User $user): bool
    {
        if (false === $user instanceof LtiUser) {
            return false;
        }

        try {
            $ltiLaunchData = $user->getLaunchData();
            if ($ltiLaunchData->hasVariable(self::CUSTOM_LTI_AUTOSTART)) {
                return $ltiLaunchData->getBooleanVariable(self::CUSTOM_LTI_AUTOSTART);
            }
        } catch (LtiException $exception) {
            $this->logger->warning(
                'Invalid custom LTI parameter',
                ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }

        return false;
    }
}
