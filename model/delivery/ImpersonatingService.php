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

namespace oat\ltiProctoring\model;

use oat\oatbox\service\ConfigurableService;
use oat\tao\model\SessionSubstitutionService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;

class ImpersonatingService extends ConfigurableService
{
    const SERVICE_ID = 'ltiProctoring/ImpersonatingService';

    const RETURN_URL = 'return_url';

    public function getReturnUrl()
    {
        /** @var SessionSubstitutionService $sessionSubstitutionService */
        $sessionSubstitutionService = $this->getServiceManager()->get(SessionSubstitutionService::SERVICE_ID);
        if($sessionSubstitutionService->isSubstituted()){
            if(!is_null($this->getOption(self::RETURN_URL))){
                return $this->getOption(self::RETURN_URL);
            }
        }
        return '';
    }
}