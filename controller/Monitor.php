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

use oat\generis\model\OntologyAwareTrait;
use oat\ltiProctoring\model\delivery\ProctorService;
use oat\tao\model\theme\ThemeService;
use oat\taoLti\models\classes\theme\LtiHeadless;

/**
 * LTI monitoring controller
 * 
 * @author joel bout
 */
class Monitor  extends \tao_actions_SinglePageModule
{
    use OntologyAwareTrait;
    
    protected function getCurrentDelivery()
    {
        $launchData = \taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLaunchData();
        $delieryId = $launchData->getCustomParameter('delivery');
        return is_null($delieryId) ? null : $this->getResource($delieryId);
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

    /**
     * Monitoring view of a selected delivery
     */
    public function index()
    {
        $delivery = $this->getCurrentDelivery();

        $this->setData('showControls', $this->showControls());

        $params = [
            'defaultTag' => (string)$this->getDefaultTag(),
        ];

        if (!is_null($delivery)) {
            $params['delivery'] = $delivery->getUri();
        }

        $this->setClientRoute(_url('index', 'Monitor', 'taoProctoring', $params));
        $this->composeView('delegated-view', null, 'pages/index.tpl', 'tao');
    }
}
