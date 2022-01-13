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

use common_session_Session as Session;
use oat\oatbox\log\LoggerService;
use oat\oatbox\user\User;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\TaoLtiSession;
use oat\taoLti\models\classes\user\LtiUser;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\taoQtiTest\models\TestSessionService;

final class AutoStartProctorService
{
    private const CUSTOM_LTI_AUTOSTART = 'custom_autostart';

    /**
     * @var TestSessionService
     */
    private $testSessionService;

    /**
     * @var LoggerService
     */
    private $logger;

    public function __construct(TestSessionService $testSessionService, LoggerService $logger)
    {
        $this->testSessionService = $testSessionService;
        $this->logger = $logger;
    }

    public function execute(DeliveryExecution $deliveryExecution, User $user, Session $session): ?string
    {
        if (false === $this->validateAutoStart($deliveryExecution, $user)) {
            return null;
        }

        $deliveryExecution->getImplementation()->setState(ProctoredDeliveryExecution::STATE_AUTHORIZED);

        return $this->getUrlRunDeliveryExecution($deliveryExecution, $session);
    }

    private function validateAutoStart(DeliveryExecution $deliveryExecution, User $user): bool
    {
        if (null === $this->testSessionService->getTestSession($deliveryExecution)) {
            return $this->isAutostartEnabled($user);
        }

        return false;
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
                "Invalid custom LTI parameter",
                ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }

        return false;
    }

    public function getUrlRunDeliveryExecution(DeliveryExecutionInterface $deliveryExecution, Session $session): string
    {
        return _url(
            'runDeliveryExecution',
            'DeliveryServer',
            $session instanceof TaoLtiSession ? 'ltiProctoring' : 'taoProctoring',
            ['deliveryExecution' => $deliveryExecution->getIdentifier()]
        );
    }
}
