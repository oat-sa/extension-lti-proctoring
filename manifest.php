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
use oat\ltiProctoring\controller\ProctoringTool;
use oat\ltiProctoring\controller\Monitor;
use oat\ltiProctoring\controller\Reporting;
use oat\ltiProctoring\scripts\install\RegisterProctoringLtiMessageFactory;
use oat\ltiProctoring\scripts\install\SetupTestRunnerMessageService;
use oat\taoLti\models\classes\LtiRoles;
use oat\tao\model\user\TaoRoles;
use oat\ltiProctoring\scripts\install\SetupProctoringEventListeners;
use oat\ltiProctoring\scripts\install\RegisterServices;
use oat\ltiProctoring\controller\DeliveryServer;
use oat\ltiProctoring\scripts\install\SetupTestSessionHistory;
use oat\ltiProctoring\scripts\install\AlterDeliveryMonitoringTable;
use oat\ltiProctoring\scripts\install\OverrideProctorService;

/**
 * Generated using taoDevTools 2.17.0
 */
return array(
    'name' => 'ltiProctoring',
    'label' => 'LTI Proctoring',
    'description' => 'Grants access to the proctoring functionalities using LTI',
    'license' => 'GPL-2.0',
    'version' => '9.0.0',
    'author' => 'Open Assessment Technologies SA',
    'requires' => array(
        'generis' => '>=12.15.0',
        'tao' => '>=41.9.0',
        'taoLti' => '>=11.3.0',
        'taoProctoring' => '>=19.6.0',
        'taoDelivery' => '>=12.5.0',
        'ltiDeliveryProvider' => '>=11.0.0',
        'taoOutcomeUi' => '>=7.0.0',
    ),
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#ltiProctoringManager',
    'acl' => array(
        array('grant', 'http://www.tao.lu/Ontologies/generis.rdf#ltiProctoringManager', array('ext'=>'ltiProctoring')),
        array('grant', TaoRoles::ANONYMOUS, ProctoringTool::class),
        array('grant', LtiRoles::CONTEXT_TEACHING_ASSISTANT, \oat\taoProctoring\controller\Monitor::class),
        array('grant', LtiRoles::CONTEXT_TEACHING_ASSISTANT, \oat\taoProctoring\controller\Reporting::class),
        array('grant', LtiRoles::CONTEXT_TEACHING_ASSISTANT, Monitor::class),
        array('grant', LtiRoles::CONTEXT_TEACHING_ASSISTANT, Reporting::class),
        array('grant', LtiRoles::CONTEXT_LEARNER, DeliveryServer::class),
        array('grant', LtiRoles::CONTEXT_ADMINISTRATOR, \oat\taoProctoring\controller\MonitorProctorAdministrator::class),
        array('grant', LtiRoles::CONTEXT_ADMINISTRATOR, \oat\taoProctoring\controller\Reporting::class),
        array('grant', LtiRoles::CONTEXT_ADMINISTRATOR, \oat\taoProctoring\controller\Monitor::class),
        array('grant', LtiRoles::CONTEXT_ADMINISTRATOR, \oat\ltiProctoring\controller\Monitor::class),
        array('grant', LtiRoles::CONTEXT_ADMINISTRATOR, \oat\ltiProctoring\controller\Reporting::class),
    ),
    'install' => array(
        'php' => [
            SetupProctoringEventListeners::class,
            RegisterServices::class,
            SetupTestSessionHistory::class,
            SetupTestRunnerMessageService::class,
            OverrideProctorService::class,
            AlterDeliveryMonitoringTable::class,
            RegisterProctoringLtiMessageFactory::class,
        ],
        'rdf' => array(
            __DIR__.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'ltiroles.rdf'
        )
    ),
    'uninstall' => array(
    ),
    'update' => 'oat\\ltiProctoring\\scripts\\update\\Updater',
    'routes' => array(
        '/ltiProctoring' => 'oat\\ltiProctoring\\controller'
    ),
    'constants' => array(
        # views directory
        "DIR_VIEWS" => __DIR__.DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR,

        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL.'ltiProctoring/',
    )
);
