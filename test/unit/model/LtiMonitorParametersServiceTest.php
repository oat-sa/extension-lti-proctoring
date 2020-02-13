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
 * Copyright (c) 2020  (original work) Open Assessment Technologies SA;
 *
 * @author Oleksandr Zagovorychev <zagovorichev@gmail.com>
 */

namespace oat\ltiProctoring\test\unit\model;

use common_exception_Error;
use core_kernel_classes_Resource;
use oat\generis\test\TestCase;
use oat\ltiDeliveryProvider\model\LtiLaunchDataService;
use oat\ltiProctoring\model\delivery\ProctorService;
use oat\ltiProctoring\model\LtiMonitorParametersService;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiService;
use oat\taoLti\models\classes\LtiVariableMissingException;
use oat\taoLti\models\classes\TaoLtiSession;

class LtiMonitorParametersServiceTest extends TestCase
{
    /**
     * @throws common_exception_Error
     * @throws LtiException
     * @throws LtiVariableMissingException
     */
    public function testGetParameters()
    {
        $LtiLaunchDataMock = $this->createMock(LtiLaunchData::class);
        $LtiLaunchDataMock
            ->expects(self::once())
            ->method('hasVariable')
            ->with(ProctorService::CUSTOM_TAG)
            ->willReturn(true);
        $LtiLaunchDataMock
            ->expects(self::once())
            ->method('getVariable')
            ->with(ProctorService::CUSTOM_TAG)
            ->willReturn('mockedTag');
        $ltiSessionMock = $this->createMock(TaoLtiSession::class);
        $ltiSessionMock->expects(self::once())->method('getLaunchData')->willReturn($LtiLaunchDataMock);
        $ltiServiceMock = $this->createMock(LtiService::class);
        $ltiServiceMock->expects($this->once())->method('getLtiSession')->willReturn($ltiSessionMock);
        $launchDataServiceMock = $this->createMock(LtiLaunchDataService::class);
        $deliveryMock = $this->createMock(core_kernel_classes_Resource::class);
        $deliveryMock->expects(self::once())->method('getUri')->willReturn('MockedDeliveryUri');
        $launchDataServiceMock
            ->expects(self::once())
            ->method('findDeliveryFromLaunchData')
            ->with($LtiLaunchDataMock)
            ->willReturn($deliveryMock);
        $serviceLocator = $this->getServiceLocatorMock([
            LtiService::class => $ltiServiceMock,
            LtiLaunchDataService::SERVICE_ID => $launchDataServiceMock,
        ]);
        $service = new LtiMonitorParametersService();
        $service->setServiceLocator($serviceLocator);
        $this->assertSame([
            'defaultTag' => 'mockedTag',
            'delivery' => 'MockedDeliveryUri',
        ], $service->getParameters());
    }
}
