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
 * @author Ivan Klimchuk <ivan@taotesting.com>
 */

declare(strict_types=1);

namespace oat\ltiProctoring\scripts\tools;

use common_Exception;
use common_exception_Error;
use common_exception_MissingParameter;
use common_exception_NotFound;
use common_session_SessionManager;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\AgsClaim;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\reporting\Report;
use oat\oatbox\reporting\ReportInterface;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use Laminas\ServiceManager\ServiceLocatorAwareTrait;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\StateServiceInterface;
use oat\taoDelivery\scripts\tools\ScoreEmptyResponseVariables;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiVariableMissingException;
use oat\taoLti\models\classes\TaoLtiSession;
use oat\taoLti\models\classes\user\Lti1p3User;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use oat\taoProctoring\scripts\TerminatePausedAssessment;
use qtism\runtime\storage\common\StorageException;

/**
 * Script for gracefully finishing stale delivery executions.
 *
 * During invoking it emulates Lti 1.3 session which is based on entries in delivery_log.
 * It is needed for proper catching up finished state and sending AGS back.
 *
 * # Wet run is the default behaviour and parameter can be omitted
 * sudo php index.php 'oat\ltiProctoring\scripts\tools\FinishStaleDeliveryExecutions'
 *
 * # Dry run shows touched executions but does nothing
 * sudo php index.php 'oat\ltiProctoring\scripts\tools\FinishStaleDeliveryExecutions' 0
 */
final class FinishStaleDeliveryExecutions extends TerminatePausedAssessment
{
    use ServiceLocatorAwareTrait;
    use LoggerAwareTrait;

    protected function getTerminationReasons(): array
    {
        return ['category' => 'Technical', 'subCategory' => 'Cleanup'];
    }

    public function __invoke($params)
    {
        $report = parent::__invoke($params);

        if ($this->wetRun === true) {
            $subReports = $report->getChildren();

            /** @var Report $lastMessage */
            $lastMessage = end($subReports);

            $this->logInfo(sprintf('%s Logged at %s', $lastMessage->getMessage(), date(DATE_RFC3339)));
        }

        return $report;
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     * @throws InvalidServiceManagerException
     * @throws LtiVariableMissingException
     * @throws common_exception_MissingParameter
     * @throws StorageException
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    protected function terminateExecution(DeliveryExecution $deliveryExecution): void
    {
        if ($this->wetRun !== true) {
            $this->addReport(
                ReportInterface::TYPE_INFO,
                "Delivery execution {$deliveryExecution->getIdentifier()} should be finished."
            );
            return;
        }

        /** @var DeliveryExecutionStateService $deliveryExecutionStateService */
        $deliveryExecutionStateService = $this->getServiceLocator()->get(StateServiceInterface::SERVICE_ID);

        // Pause execution before scoring
        if ($deliveryExecution->getState()->getUri() === DeliveryExecutionInterface::STATE_ACTIVE) {
            $deliveryExecutionStateService->pause($deliveryExecution);
        }

        // Score missed items
        $this->report->add($this->scoreMissedItems($deliveryExecution));

        // Create fake LTI session
        $this->initiateLtiSession($deliveryExecution);

        $deliveryExecutionStateService->finish(
            $deliveryExecution,
            [
                'reasons' => $this->getTerminationReasons(),
                'comment' => __('The assessment was automatically finished by the system due to inactivity.'),
            ]
        );

        $this->addReport(
            ReportInterface::TYPE_WARNING,
            "Delivery execution {$deliveryExecution->getIdentifier()} has been gracefully finished."
        );
    }

    /**
     * @throws LtiVariableMissingException
     * @throws common_exception_Error
     * @throws common_Exception
     */
    private function initiateLtiSession(DeliveryExecution $execution): bool
    {
        /** @var DeliveryLog $deliveryLog */
        $deliveryLog = $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);

        $launchParameters = current($deliveryLog->get($execution->getIdentifier(), 'LTI_LAUNCH_PARAMETERS'));

        // Patch AGS Claim
        $launchParameters['data'][LtiLaunchData::AGS_CLAIMS] = AgsClaim::denormalize($launchParameters['data'][LtiLaunchData::AGS_CLAIMS]);

        $ltiLaunchData = new LtiLaunchData($launchParameters['data'], []);

        $user = new Lti1p3User($ltiLaunchData);
        $user->setRegistrationId($ltiLaunchData->getVariable(LtiLaunchData::TOOL_CONSUMER_INSTANCE_ID));

        return common_session_SessionManager::startSession(TaoLtiSession::fromVersion1p3($user));
    }

    private function scoreMissedItems(DeliveryExecution $execution): Report
    {
        return ServiceManager::getServiceManager()->propagate(new ScoreEmptyResponseVariables())(
            $this->mapActionOptions([
                ScoreEmptyResponseVariables::OPTION_DELIVERY_EXECUTION_IDS => $execution->getIdentifier(),
                ScoreEmptyResponseVariables::OPTION_WET_RUN => 1
            ])
        );
    }

    private function mapActionOptions(array $options): array
    {
        return array_merge(...array_map(fn ($k, $v) => ['--' . $k, $v], array_keys($options), $options));
    }
}
