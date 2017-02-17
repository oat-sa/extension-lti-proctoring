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
 */

namespace oat\ltiProctoring\model\delivery;

use oat\taoProctoring\model\ProctorService as DefaultProctorService;
use oat\oatbox\user\User;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use \taoLti_models_classes_LtiLaunchData as LtiLaunchData;

/**
 * Delivery Service for proctoring via LTI
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class ProctorService extends DefaultProctorService
{

    const CUSTOM_TAG = 'custom_tag';

    /**
     * @param User $proctor
     * @param \core_kernel_classes_Resource $delivery
     * @param null $context
     * @param array $options
     * @return array
     */
    public function getProctorableDeliveryExecutions(User $proctor, $delivery = null, $context = null, $options = [])
    {
        $monitoringService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);

        $useTagsCriteria = false;

        if (array_key_exists('filters', $options)) {

            $useTagsCriteria = in_array(true, array_map(function ($e) {
                return filter_var($e, FILTER_VALIDATE_BOOLEAN);
            }, array_column($options['filters'], 'tag')), true);

            $options['filters'] = array_filter((array)$options['filters'], function ($filter) {
                return !array_key_exists('tag', $filter);
            });
        }

        $criteria = $this->getCriteria($delivery, $context, $options);
        $currentSession = \common_session_SessionManager::getSession();
        if ($currentSession instanceof \taoLti_models_classes_TaoLtiSession) {
            /** @var \taoLti_models_classes_LtiLaunchData $launchData */
            $launchData = $currentSession->getLaunchData();
            if ($launchData->hasVariable(LtiLaunchData::CONTEXT_ID)) {
                $contextId = $launchData->getVariable(LtiLaunchData::CONTEXT_ID);
                $criteria[] = [LtiLaunchData::CONTEXT_ID => $contextId];
            }
            if ($launchData->hasVariable(self::CUSTOM_TAG)) {
                if ($useTagsCriteria) {
                    $tag = $launchData->getVariable(self::CUSTOM_TAG);
                    $criteria[] = [self::CUSTOM_TAG => 'LIKE%,' . $tag . ',%'];
                }
            }
        }
        $options['asArray'] = true;
        return $monitoringService->find($criteria, $options, true);
    }
}
