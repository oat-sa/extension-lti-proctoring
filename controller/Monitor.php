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
        if ($this->getServiceManager()->has('ltiProctoring/proctoring')) {
            $config = $this->getServiceManager()->get('ltiProctoring/proctoring');
            if ($config && array_key_exists('showControls', $config)) {
                return $config['showControls'];
            }
        }
        return false;
    }

    /**
     * Monitoring view of a selected delivery
     */
    public function index()
    {
        $delivery = $this->getCurrentDelivery();
        $data = array(
            'defaultTag' => (string)$this->getDefaultTag(),
            'action' => 'index',
            'controller' => 'Monitor',
            'extension' => 'taoProctoring',
        );

        $this->setData('showControls', $this->showControls());

        if (!is_null($delivery)) {
            $data['delivery'] = $delivery->getUri();
        }

        $this->composeView('delegated-view', $data, 'pages/index.tpl', 'tao');
    }
}
