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
 */

namespace oat\ltiProctoring\controller;

use oat\generis\model\OntologyAwareTrait;
use oat\ltiProctoring\model\delivery\ProctorService;
use oat\tao\model\theme\ThemeService;
use oat\taoLti\models\classes\theme\LtiHeadless;

/**
 * Base LTI proctoring interface controller
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
abstract class SimplePageModule extends \tao_actions_SinglePageModule
{
    use OntologyAwareTrait;

    /**
     * Retrieve the data from the url and make the base initialization
     *
     * @return void
     */
    protected function defaultData()
    {
        parent::defaultData();
        $this->setData('showControls', $this->showControls());
        
        $launchData = \taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLaunchData();
        $this->setData('logout', $launchData->getReturnUrl());
    }

    protected function getCurrentDelivery()
    {
        $launchData = \taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLaunchData();
        $deliveryId = $launchData->getCustomParameter('delivery');
        return is_null($deliveryId) ? null : $this->getResource($deliveryId);
    }

    protected function getDefaultTag()
    {
        $launchData = \taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLaunchData();
        return $launchData->hasVariable(ProctorService::CUSTOM_TAG) ? $launchData->getVariable(ProctorService::CUSTOM_TAG) : '';
    }

    /**
     * Gets the path to the layout
     * @return array
     */
    protected function getLayout()
    {
        return ['layout.tpl', 'ltiProctoring'];
    }

    /**
     * Defines if the top and bottom action menu should be displayed or not
     *
     * @return boolean
     */
    protected function showControls() {
        $themeService = $this->getServiceManager()->get(ThemeService::SERVICE_ID);
        if ($themeService instanceof LtiHeadless) {
            return !$themeService->isHeadless();
        }
        return false;
    }
}
