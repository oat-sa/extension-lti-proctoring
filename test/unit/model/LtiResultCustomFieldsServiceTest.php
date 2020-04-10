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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\ltiProctoring\test\unit\model;

use oat\generis\test\TestCase;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\ltiProctoring\model\LtiResultCustomFieldsService;

/**
 * Class LtiResultCustomFieldsServiceTest
 */
class LtiResultCustomFieldsServiceTest extends TestCase
{
    const DE_ID = 'foo';

    /**
     * @throws \common_exception_NotFound
     */
    public function testGetCustomFields()
    {
        $service = new LtiResultCustomFieldsService();

        $logData = [
            [
                'id' => 1,
                'delivery_execution_id' => self::DE_ID,
                'event_id' => 'LTI_DELIVERY_EXECUTION_CREATED',
                'data' =>
                    [
                        'custom_tag' => '',
                        'context_id' => 'S3294476',
                        'context_label' => 'ST101',
                        'resource_link_id' => '429785226',
                    ],
                'created_at' => '1586327790.7391',
                'created_by' => 'user',
            ]
        ];
        $serviceLocator = $this->getServiceLocatorMock([
            DeliveryLog::SERVICE_ID => $this->getDeliveryLogMock($logData)
        ]);
        $service->setServiceLocator($serviceLocator);
        /** @var DeliveryExecutionInterface $deliveryExecution */
        $deliveryExecution = $this->getMockBuilder(DeliveryExecutionInterface::class)->getMock();
        $deliveryExecution->method('getIdentifier')->willReturn(self::DE_ID);

        $this->assertEquals($logData[0]['data'], $service->getCustomFields($deliveryExecution));


        $serviceLocator = $this->getServiceLocatorMock([
            DeliveryLog::SERVICE_ID => $this->getDeliveryLogMock([])
        ]);
        $service->setServiceLocator($serviceLocator);
        $this->assertEquals([], $service->getCustomFields($deliveryExecution));
    }

    /**
     * @param $returnValue
     * @return DeliveryLog
     */
    private function getDeliveryLogMock($returnValue)
    {
        $deliveryLogMock = $this->getMockBuilder(DeliveryLog::class)->disableOriginalConstructor()->getMock();
        $deliveryLogMock->method('get')->willReturn($returnValue);

        return $deliveryLogMock;
    }

}
