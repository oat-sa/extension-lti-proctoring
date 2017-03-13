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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA ;
 */

namespace oat\ltiProctoring\model\implementation;

use oat\taoProctoring\model\implementation\TestSessionHistoryService as TestSessionHistoryServiceProctoring;

/**
 * Service is used to retrieve test session history
 *
 * @package oat\ltiProctoring
 */
class TestSessionHistoryService extends TestSessionHistoryServiceProctoring
{
    /**
     * Gets the url that leads to the page listing the history
     * @param $delivery
     * @return string
     */
    public function getHistoryUrl($delivery = null)
    {
        $session = \common_session_SessionManager::getSession();
        if ($session instanceof \taoLti_models_classes_TaoLtiSession) {
            $params = [];
            if ($delivery) {
                if ($delivery instanceof \core_kernel_classes_Resource) {
                    $delivery = $delivery->getUri();
                }
                $params['delivery'] = $delivery . '';
            }
            return _url('index', 'Reporting', 'ltiProctoring', $params);
        }
        return parent::getHistoryUrl($delivery);
    }


    /**
     * Gets the back url that returns to the page listing the sessions
     * @param $delivery
     * @return string
     */
    public function getBackUrl($delivery = null)
    {
        $session = \common_session_SessionManager::getSession();
        if ($session instanceof \taoLti_models_classes_TaoLtiSession) {
            $params = [];
            if ($delivery) {
                if ($delivery instanceof \core_kernel_classes_Resource) {
                    $delivery = $delivery->getUri();
                }
                $params['delivery'] = $delivery . '';
            }
            return _url('index', 'Monitor', 'ltiProctoring', $params);    
        }
        return parent::getBackUrl($delivery);
    }
}
