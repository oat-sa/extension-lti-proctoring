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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\ltiProctoring\controller;

use common_exception_Error;
use oat\ltiProctoring\model\LtiMonitorParametersService;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiVariableMissingException;

/**
 * LTI monitoring controller
 *
 * @author joel bout
 */
class Monitor extends SimplePageModule
{
    /**
     * Monitoring view of a selected delivery
     *
     * @throws common_exception_Error
     * @throws LtiException
     * @throws LtiVariableMissingException
     */
    public function index()
    {
        /** @var LtiMonitorParametersService $monitoringParamsService */
        $monitoringParamsService = $this->getServiceLocator()->get(LtiMonitorParametersService::SERVICE_ID);
        $this->setClientRoute(_url('index', 'Monitor', 'taoProctoring', $monitoringParamsService->getParameters()));
        $this->composeView('delegated-view', null, 'pages/index.tpl', 'tao');
    }
}
