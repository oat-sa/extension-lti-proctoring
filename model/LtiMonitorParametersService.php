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

namespace oat\ltiProctoring\model;

use common_exception_Error;
use oat\ltiDeliveryProvider\model\LtiLaunchDataService;
use oat\ltiProctoring\model\delivery\ProctorService;
use oat\oatbox\service\ConfigurableService;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiService;
use oat\taoLti\models\classes\LtiVariableMissingException;

class LtiMonitorParametersService extends ConfigurableService
{
    const SERVICE_ID = 'ltiProctoring/LtiMonitorParameters';

    private $launchData;

    /**
     * @return array
     * @throws LtiException
     * @throws LtiVariableMissingException
     * @throws common_exception_Error
     */
    public function getParameters()
    {
        $params = ['defaultTag' => $this->getDefaultTag()];

        $delivery = $this->getCurrentDelivery();
        if ($delivery !== null) {
            $params['delivery'] = $delivery->getUri();
        }

        return $params;
    }

    /**
     * @return LtiLaunchData
     * @throws common_exception_Error
     * @throws LtiException
     */
    protected function getLaunchData()
    {
        if (!$this->launchData) {
            /** @var LtiService $service */
            $service = $this->getServiceLocator()->get(LtiService::class);
            $this->launchData = $service->getLtiSession()->getLaunchData();
        }
        return $this->launchData;
    }

    /**
     * @return string
     * @throws LtiException
     * @throws LtiVariableMissingException
     * @throws common_exception_Error
     */
    private function getDefaultTag()
    {
        return (string) $this->getLaunchData()->hasVariable(ProctorService::CUSTOM_TAG)
            ? $this->getLaunchData()->getVariable(ProctorService::CUSTOM_TAG)
            : '';
    }

    /**
     * @return LtiLaunchDataService
     */
    private function getLtiLaunchDataService()
    {
        return $this->getServiceLocator()->get(LtiLaunchDataService::SERVICE_ID);
    }

    /**
     * @return mixed
     * @throws common_exception_Error
     * @throws LtiException
     */
    protected function getCurrentDelivery()
    {
        return $this->getLtiLaunchDataService()
            ->findDeliveryFromLaunchData($this->getLaunchData());
    }
}
