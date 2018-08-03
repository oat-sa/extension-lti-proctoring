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
use oat\ltiDeliveryProvider\model\LtiLaunchDataService;
use oat\ltiProctoring\model\delivery\ProctorService;
use oat\tao\model\theme\ThemeServiceInterface;
use oat\taoLti\models\classes\LtiService;
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
     * @throws \common_exception_Error
     * @throws \oat\taoLti\models\classes\LtiException
     */
    protected function defaultData()
    {
        parent::defaultData();
        $this->setData('showControls', $this->showControls());
        
        $launchData = LtiService::singleton()->getLtiSession()->getLaunchData();
        if($launchData->hasReturnUrl()){
            $this->setData('exit', $launchData->getReturnUrl());
        }

    }

    /**
     * @return mixed
     * @throws \common_exception_Error
     * @throws \oat\taoLti\models\classes\LtiException
     */
    protected function getCurrentDelivery()
    {
        $launchData =LtiService::singleton()->getLtiSession()->getLaunchData();
        /** @var LtiLaunchDataService $service */
        $ltiLaunchDataService = $this->getServiceManager()->get(LtiLaunchDataService::SERVICE_ID);
        $delivery = $ltiLaunchDataService->findDeliveryFromLaunchData($launchData);
        return $delivery;
    }

    /**
     * @return mixed|string
     * @throws \common_exception_Error
     * @throws \oat\taoLti\models\classes\LtiException
     * @throws \oat\taoLti\models\classes\LtiVariableMissingException
     */
    protected function getDefaultTag()
    {
        $launchData = LtiService::singleton()->getLtiSession()->getLaunchData();
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
        $themeService = $this->getServiceManager()->get(ThemeServiceInterface::SERVICE_ID);
        if ($themeService instanceof ThemeServiceInterface || $themeService instanceof LtiHeadless) {
            return !$themeService->isHeadless();
        }
        return false;
    }

    /**
     * @param string $scope
     * @param array $data
     * @param string $template
     * @param string $extension
     * @throws \common_exception_Error
     * @throws \oat\taoLti\models\classes\LtiException
     */
    protected function composeView($scope = '', $data = array(), $template = '', $extension = '')
    {
        $this->setExitUrl();

        parent::composeView($scope, $data, $template, $extension);
    }

    /**
     * @throws \common_exception_Error
     * @throws \oat\taoLti\models\classes\LtiException
     */
    private function setExitUrl()
    {
        $launchData = LtiService::singleton()->getLtiSession()->getLaunchData();

        if($launchData->hasReturnUrl()){
            $exitUrl = $launchData->getReturnUrl();
            $url = explode('?', $exitUrl);

            $this->setClientParam('redirectUrl', ['redirectUrl'=>$url[0]]);
        }
    }
}
