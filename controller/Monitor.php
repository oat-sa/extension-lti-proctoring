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

/**
 * LTI monitoring controller
 * 
 * @author joel bout
 */
class Monitor  extends \tao_actions_CommonModule
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
        return $launchData->hasVariable(\taoLti_models_classes_LtiLaunchData::CUSTOM_TAG)?$launchData->getVariable(\taoLti_models_classes_LtiLaunchData::CUSTOM_TAG):'';
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

        if (!is_null($delivery)) {
            $data['delivery'] = $delivery->getUri();
        }

        $this->defaultData();
        $this->setData('data', $data);
        $this->setView('layout.tpl', \Context::getInstance()->getExtensionName());
    }
}
