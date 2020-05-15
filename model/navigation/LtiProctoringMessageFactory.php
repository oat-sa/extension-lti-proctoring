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

use InvalidArgumentException;
use oat\ltiDeliveryProvider\model\navigation\LtiMessageFactoryInterface;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiMessages\LtiMessage;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;

class LtiProctoringMessageFactory extends ConfigurableService implements LtiMessageFactoryInterface
{
    /**
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return LtiMessage
     * @throws \common_exception_NotFound
     */
    public function getLtiMessage(DeliveryExecutionInterface $deliveryExecution): LtiMessage
    {
        $state = $deliveryExecution->getState()->getLabel();
        $deliveryLog = $this->getLastDeliveryLogByEvent($deliveryExecution);
        $ltiLogMessage = $this->prepareLtiLogMessage($deliveryExecution->getState()->getUri(), $deliveryLog);

        return new LtiMessage($state, $ltiLogMessage);
    }

    private function getEventId(string $executionStateUri): string
    {
        switch ($executionStateUri) {
            case DeliveryExecutionInterface::STATE_FINISHED:
                $eventId = 'TEST_EXIT_CODE';
                break;
            case DeliveryExecutionInterface::STATE_TERMINATED:
                $eventId = 'TEST_TERMINATE';
                break;
            case DeliveryExecutionInterface::STATE_PAUSED:
                $eventId = 'TEST_PAUSE';
                break;
            case ProctoredDeliveryExecution::STATE_CANCELED:
                $eventId = 'TEST_CANCEL';
                break;
            default:
                throw new InvalidArgumentException("Not supported delivery execution state URI provided: {$executionStateUri}");
        }

        return $eventId;
    }

    private function getLastDeliveryLogByEvent(DeliveryExecutionInterface $deliveryExecution): array
    {
        $logRecord = [];
        try {
            $eventId = $this->getEventId($deliveryExecution->getState()->getUri());
            /** @var DeliveryLog $deliveryLog */
            $deliveryLog = $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);
            $logs = $deliveryLog->get($deliveryExecution->getIdentifier(), $eventId);

            if (count($logs) > 0) {
                $logRecord = array_pop($logs);
            }
        } catch (InvalidArgumentException $e) {
            $this->logWarning($e->getMessage());
        }

        return $logRecord;
    }

    private function prepareLtiLogMessage(string $executionStateUri, array $deliveryLog): string
    {
        $ltiLogMessage = '';
        if (!isset($deliveryLog['data']) || empty($deliveryLog['data'])) {
            return $ltiLogMessage;
        }

        $logData = $deliveryLog['data'];
        if ($executionStateUri === DeliveryExecutionInterface::STATE_FINISHED) {
            $ltiLogMessage = 'Exit code: ' . $logData['exitCode'] . PHP_EOL;
        } else {
            $ltiLogMessage .= $logData['reason']['reasons']['category'] ?? '';
            $ltiLogMessage .= isset($logData['reason']['reasons']['subCategory']) ? '; ' . $logData['reason']['reasons']['subCategory'] : '';
            $ltiLogMessage .= isset($logData['reason']['comment']) ? ' - ' . $logData['reason']['comment'] : '';
        }

        return $ltiLogMessage;
    }
}
