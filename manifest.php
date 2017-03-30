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
use oat\taoLti\models\classes\LtiRoles;
use oat\tao\model\user\TaoRoles;
use oat\ltiProctoring\scripts\install\SetupProctoringEventListeners;
use oat\ltiProctoring\scripts\install\RegisterAuthProvider;
use oat\ltiProctoring\scripts\install\RegisterServices;
use oat\ltiProctoring\controller\DeliveryServer;

/**
 * Generated using taoDevTools 2.17.0
 */
return array(
    'name' => 'ltiProctoring',
    'label' => 'LTI Proctoring',
    'description' => 'Grants access to the proctoring functionalities using LTI',
    'license' => 'GPL-2.0',
    'version' => '0.8.3',
    'author' => 'Open Assessment Technologies SA',
    'requires' => array(
        'taoLti' => '>=1.11.0',
        'taoProctoring' => '>=4.10.3',
        'ltiDeliveryProvider' => '>=2.2.0',
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
    ),
    'install' => array(
        'php' => [
            SetupProctoringEventListeners::class,
            RegisterAuthProvider::class,
            RegisterServices::class,
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
        
        #BASE WWW required by JS
        'BASE_WWW' => ROOT_URL.'ltiProctoring/views/'
    )
);
