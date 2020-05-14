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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */
declare(strict_types=1);

namespace oat\ltiProctoring\model\navigation;

use oat\ltiDeliveryProvider\model\navigation\LtiMessageFactoryInterface;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiMessages\LtiMessage;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;

class ProctoringLtiMessageFactory extends ConfigurableService implements LtiMessageFactoryInterface
{
    public function getLtiMessage(DeliveryExecutionInterface $deliveryExecution): LtiMessage
    {
        $state = $deliveryExecution->getState()->getLabel();
        /** @var DeliveryLog $deliveryLog */
        $deliveryLog = $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);
        $reason = '';
        $reasons = null;
        switch ($deliveryExecution->getState()->getUri()) {
            case ProctoredDeliveryExecution::STATE_FINISHED:
                $log = $deliveryLog->get($deliveryExecution->getIdentifier(), 'TEST_EXIT_CODE');
                if ($log) {
                    $reason .= 'Exit code: ' . $log[count($log) - 1]['data']['exitCode'] . PHP_EOL;
                }
                break;
            case ProctoredDeliveryExecution::STATE_TERMINATED:
                $log = $deliveryLog->get($deliveryExecution->getIdentifier(), 'TEST_TERMINATE');
                if ($log) {
                    $reasons = $log[count($log) - 1]['data'];
                }
                break;
            case ProctoredDeliveryExecution::STATE_PAUSED:
                $log = $deliveryLog->get($deliveryExecution->getIdentifier(), 'TEST_PAUSE');
                if ($log) {
                    $reasons = $log[count($log) - 1]['data'];
                }
                break;
            case ProctoredDeliveryExecution::STATE_CANCELED:
                $log = $deliveryLog->get($deliveryExecution->getIdentifier(), 'TEST_CANCEL');
                if ($log) {
                    $reasons = $log[count($log) - 1]['data'];
                }
                break;
        }

        if ($reasons !== null) {
            $reason .= isset($reasons['reason']['reasons']['category']) ? $reasons['reason']['reasons']['category'] : '';
            $reason .= isset($reasons['reason']['reasons']['subCategory']) ? '; ' . $reasons['reason']['reasons']['subCategory'] : '';
            $reason .= isset($reasons['reason']['comment']) ? ' - ' . $reasons['reason']['comment'] : '';
        }

        return new LtiMessage($state, $reason);
    }
}
