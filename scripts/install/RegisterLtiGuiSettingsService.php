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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 *
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien@taotesting.com>
 */

namespace oat\ltiProctoring\scripts\install;

use oat\ltiProctoring\model\LtiGuiSettingsService;
use oat\oatbox\extension\InstallAction;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoProctoring\model\GuiSettingsService;

/**
 * Class RegisterLtiGuiSettingsService
 * @package oat\ltiProctoring\scripts\install
 */
class RegisterLtiGuiSettingsService extends InstallAction
{

    /**
     * Configure and register the GuiSettingsService
     */
    public function __invoke($params)
    {
        try {
            $options = $this->getServiceManager()->get(GuiSettingsService::SERVICE_ID)->getOptions();
        } catch(ServiceNotFoundException $e) {
            $options = [
                GuiSettingsService::PROCTORING_REFRESH_BUTTON => true,
                GuiSettingsService::PROCTORING_AUTO_REFRESH => 0,
                GuiSettingsService::PROCTORING_ALLOW_PAUSE => true,
            ];
        }
        
        $service = new LtiGuiSettingsService($options);
        $this->getServiceManager()->propagate($service);
        $this->getServiceManager()->register(GuiSettingsService::SERVICE_ID, $service);
    }
}
