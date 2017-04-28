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
 *
 */

namespace oat\ltiProctoring\model;

use oat\taoProctoring\model\ActivityMonitoringService as BaseActivityMonitoringService;
use oat\taoLti\models\classes\LtiRoles;
use oat\taoProctoring\model\ProctorService;

/**
 * Service to manage and monitor assessment activity
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class ActivityMonitoringService extends BaseActivityMonitoringService
{

    /**
     * Return comprehensive activity monitoring data.
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();
        $proctors = $this->getNumberOfActiveUsers(ProctorService::ROLE_PROCTOR) +
            $this->getNumberOfActiveUsers(LtiRoles::CONTEXT_TEACHING_ASSISTANT);
        $data['active_proctors'] = $proctors;
        return $data;
    }

}
