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
 */

namespace oat\ltiProctoring\scripts\install;

use oat\ltiProctoring\model\implementation\TestSessionHistoryService;
use oat\oatbox\extension\InstallAction;
use oat\ltiProctoring\model\execution\LtiDeliveryExecutionService;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationInterface;
use oat\ltiProctoring\model\delivery\LtiTestTakerAuthorizationService;
use oat\oatbox\service\ServiceNotFoundException;
use oat\ltiProctoring\model\ActivityMonitoringService;

/**
 * Action to register necessary extension services
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RegisterServices extends InstallAction
{
    /**
     * @param $params
     * @throws \common_exception_Error
     * @throws \common_Exception
     */
    public function __invoke($params)
    {
        $service = $this->getServiceManager()->get(LtiDeliveryExecutionService::SERVICE_ID);
        $newService = new LtiDeliveryExecutionService($service->getOptions());
        $this->registerService(LtiDeliveryExecutionService::SERVICE_ID, $newService);
        
        $service = new TestSessionHistoryService();
        $this->registerService(TestSessionHistoryService::SERVICE_ID, $service);

        $delegator = $this->getServiceManager()->get(TestTakerAuthorizationInterface::SERVICE_ID);
        $delegator->registerHandler(new LtiTestTakerAuthorizationService());
        $this->getServiceManager()->register(TestTakerAuthorizationInterface::SERVICE_ID, $delegator);

        try {
            $oldActivityMonitoringService = $this->getServiceManager()->get(ActivityMonitoringService::SERVICE_ID);
            $options = $oldActivityMonitoringService->getOptions();
        } catch (ServiceNotFoundException $error) {
            $options = [
                ActivityMonitoringService::OPTION_ACTIVE_USER_THRESHOLD => 300
            ];
        }

        $options = array_merge($options,
            [\oat\taoProctoring\model\ActivityMonitoringService::OPTION_USER_ACTIVITY_WIDGETS => [
                'queueTestTakers' => [
                    'container' => 'queue-test-takers',
                    'label' => __('Queued test-takers'),
                    'value' => 0,
                    'icon' => 'takers',
                    'size' => 4
                ]],
            ]);

        $newActivityMonitoringService = new ActivityMonitoringService($options);
        $this->getServiceManager()->register(ActivityMonitoringService::SERVICE_ID, $newActivityMonitoringService);
    }
}
