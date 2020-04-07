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
 */

declare(strict_types=1);

namespace oat\ltiProctoring\model;

use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use RuntimeException;
use oat\ltiDeliveryProvider\model\LtiLaunchDataService as BaseLtiLaunchDataService;

class LtiLaunchDataService extends BaseLtiLaunchDataService
{
    /**
     * @param string $deliveryExecutionId
     * @return LtiLaunchData
     */
    public function findLaunchDataByDeliveryExecutionId(string $deliveryExecutionId): LtiLaunchData
    {
        $ltiVariablesList = $this->getServiceLocator()
            ->get(DeliveryLog::SERVICE_ID)
            ->get($deliveryExecutionId, 'LTI_LAUNCH_PARAMETERS');

        if (empty($ltiVariablesList)) {
            throw new RuntimeException(sprintf('Delivery execution %s not found', $deliveryExecutionId));
        }

        if (!isset($ltiVariablesList[0]['data'])) {
            throw new RuntimeException('`data` field is absent in result of DeliveryLog::get');
        }

        return new LtiLaunchData($ltiVariablesList[0]['data'], []);
    }
}
