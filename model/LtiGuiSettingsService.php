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

namespace oat\ltiProctoring\model;

use oat\taoProctoring\model\GuiSettingsService;

/**
 * Class LtiGuiSettingsService
 * @package oat\ltiProctoring\model
 */
class LtiGuiSettingsService extends GuiSettingsService
{
    /**
     * Gets the URL that exits the proctor pages
     * @return string|null
     */
    public function getExitUrl()
    {
        $session = \common_session_SessionManager::getSession();
        if ($session instanceof \taoLti_models_classes_TaoLtiSession) {
            $launchData = $session->getLaunchData();
            return $launchData->getReturnUrl();
        }
        
        return parent::getExitUrl();
    }
}
