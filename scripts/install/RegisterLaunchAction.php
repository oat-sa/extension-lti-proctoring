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

use oat\oatbox\extension\AbstractAction;
use oat\ltiProctoring\model\delivery\LtiProctorAuthorizationProvider;
use oat\tao\model\actionQueue\ActionQueue;
use oat\ltiProctoring\model\actions\GetActiveDeliveryExecution;

/**
 * Installation action that register delivery launch action in the action queue.
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RegisterLaunchAction extends AbstractAction
{

    /**
     * @param $params
     * @return \common_report_Report
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public function __invoke($params)
    {
        $actionQueue = $this->getServiceManager()->get(ActionQueue::SERVICE_ID);
        $actions = $actionQueue->getOption(ActionQueue::OPTION_ACTIONS);
        $actions[GetActiveDeliveryExecution::class] = [
            ActionQueue::ACTION_PARAM_LIMIT => 0,
            ActionQueue::ACTION_PARAM_TTL => 3600, //one hour
        ];
        $actionQueue->setOption(ActionQueue::OPTION_ACTIONS, $actions);
        $this->getServiceManager()->register(ActionQueue::SERVICE_ID, $actionQueue);
        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('GetActiveDeliveryExecution action registered'));
    }
}
