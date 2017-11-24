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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\ltiProctoring\scripts\install;


use oat\oatbox\extension\InstallAction;
use oat\taoLti\models\classes\LtiRoles;
use oat\taoProctoring\model\implementation\TestRunnerMessageService;

/**
 * Register a listener for newly created deliveries
 */
class SetupTestRunnerMessageService extends InstallAction
{
    /**
     * @param $params
     */
    public function __invoke($params)
    {
        /** @var TestRunnerMessageService $testRunnerMessageService */
        $testRunnerMessageService = $this->getServiceManager()->get(TestRunnerMessageService::SERVICE_ID);
        $roles = $testRunnerMessageService->getOption(TestRunnerMessageService::PROCTOR_ROLES_OPTION);
        $roles[] = LtiRoles::CONTEXT_TEACHING_ASSISTANT;
        $roles[] = LtiRoles::CONTEXT_ADMINISTRATOR;
        $testRunnerMessageService->setOption(TestRunnerMessageService::PROCTOR_ROLES_OPTION, $roles);
        $this->getServiceManager()->register(TestRunnerMessageService::SERVICE_ID, $testRunnerMessageService);
    }
}
