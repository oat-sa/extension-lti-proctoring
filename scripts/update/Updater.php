<?php

namespace oat\ltiProctoring\scripts\update;

use oat\ltiDeliveryProvider\model\navigation\LtiNavigationService;
use oat\ltiProctoring\controller\DeliveryServer;
use oat\ltiProctoring\controller\Reporting;
use oat\ltiProctoring\model\delivery\ProctorService as ltiProctorService;
use oat\ltiProctoring\model\LtiLaunchDataService;
use oat\ltiProctoring\model\LtiListenerService;
use oat\ltiProctoring\model\implementation\TestSessionHistoryService;
use oat\ltiProctoring\model\LtiMonitorParametersService;
use oat\ltiProctoring\model\LtiResultCustomFieldsService;
use oat\ltiProctoring\model\navigation\LtiProctoringMessageFactory;
use oat\ltiProctoring\scripts\install\SetupTestSessionHistory;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoDelivery\model\authorization\AuthorizationService as DeliveryAuthorizationService;
use oat\taoDelivery\model\authorization\strategy\AuthorizationAggregator;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoLti\models\classes\LtiRoles;
use oat\taoOutcomeUi\model\search\ResultCustomFieldsService;
use oat\taoProctoring\controller\Monitor;
use oat\taoProctoring\model\authorization\ProctorAuthorizationProvider;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\taoDelivery\model\authorization\AuthorizationService;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationInterface;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;
use oat\ltiProctoring\model\delivery\LtiProctorAuthorizationProvider;
use oat\ltiProctoring\model\delivery\LtiTestTakerAuthorizationService;
use oat\ltiProctoring\model\ActivityMonitoringService;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoProctoring\model\implementation\TestRunnerMessageService;
use oat\taoProctoring\model\ProctorService;
use oat\taoProctoring\model\ProctorServiceDelegator;
use oat\taoProctoring\model\ProctorServiceInterface;
use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService;
use oat\ltiDeliveryProvider\model\execution\implementation\LtiDeliveryExecutionService as OntologyLtiDeliveryExecutionService;

class Updater extends \common_ext_ExtensionUpdater
{

    /**
     * @param $initialVersion
     * @return string $versionUpdatedTo
     */
    public function update($initialVersion)
    {
        if ($this->isVersion('0.1.0')) {
            /** @var AuthorizationAggregator $service */
            $service = $this->getServiceManager()->get(DeliveryAuthorizationService::SERVICE_ID);
            if ($service instanceof AuthorizationAggregator) {
                $service->unregister(ProctorAuthorizationProvider::class);
                $service->addProvider(new LtiProctorAuthorizationProvider());
                $this->getServiceManager()->register(AuthorizationAggregator::SERVICE_ID, $service);
            }

            $this->setVersion('0.2.0');
        }
        $this->skip('0.2.0', '0.2.1');

        if ($this->isVersion('0.2.1')) {
            $this->setVersion('0.3.0');
        }

        $this->skip('0.3.0', '0.4.1');

        if ($this->isVersion('0.4.1')) {
            AclProxy::applyRule(new AccessRule('grant', LtiRoles::CONTEXT_LEARNER, DeliveryServer::class));
            $this->setVersion('0.5.0');
        }
        $this->skip('0.5.0', '0.7.1');

        if ($this->isVersion('0.7.1')) {
            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->attach(DeliveryExecutionState::class, [LtiListenerService::SERVICE_ID, 'executionStateChanged']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            $this->setVersion('0.8.0');
        }

        if ($this->isVersion('0.8.0')) {
            $service = new TestSessionHistoryService();
            $this->getServiceManager()->register(TestSessionHistoryService::SERVICE_ID, $service);

            AclProxy::applyRule(new AccessRule('grant', LtiRoles::CONTEXT_TEACHING_ASSISTANT, Reporting::class));

            $this->setVersion('0.8.1');
        }

        if ($this->isVersion('0.8.1')) {
            OntologyUpdater::syncModels();
            AclProxy::applyRule(new AccessRule('grant', LtiRoles::CONTEXT_TEACHING_ASSISTANT, Monitor::class));
            AclProxy::applyRule(new AccessRule('grant', LtiRoles::CONTEXT_TEACHING_ASSISTANT, \oat\taoProctoring\controller\Reporting::class));
            $this->setVersion('0.8.2');
        }

        $this->skip('0.8.2', '0.10.0');

        if ($this->isVersion('0.10.0')) {
            $authService = $this->safeLoadService(AuthorizationService::SERVICE_ID);
            if (!$authService instanceof AuthorizationAggregator) {
                throw new \common_exception_Error('Incompatible AuthorizationService "'.get_class($authService).'" found.');
            }
            $authService->unregister('oat\ltiProctoring\model\delivery\LtiProctorAuthorizationProvider');
            $authService->addProvider(new ProctorAuthorizationProvider());
            $this->getServiceManager()->register(AuthorizationService::SERVICE_ID, $authService);

            $this->getServiceManager()->register(TestTakerAuthorizationService::SERVICE_ID, new LtiTestTakerAuthorizationService());
            $this->setVersion('1.0.0');
        }

        if ($this->isVersion('1.0.0')) {
            /** @var ActivityMonitoringService $oldService */
            try {
                $oldService = $this->safeLoadService(ActivityMonitoringService::SERVICE_ID);
                $options = $oldService->getOptions();
            } catch (ServiceNotFoundException $error) {
                $options = [
                    ActivityMonitoringService::OPTION_ACTIVE_USER_THRESHOLD => 300
                ];
            }
            $newService = new ActivityMonitoringService($options);
            $this->getServiceManager()->register(ActivityMonitoringService::SERVICE_ID, $newService);
            $this->setVersion('1.1.0');
        }

        $this->skip('1.1.0', '2.3.1');

        if ($this->isVersion('2.3.1')) {
            $this->runExtensionScript(SetupTestSessionHistory::class);
            $this->setVersion('2.3.2');
        }

        $this->skip('2.3.2', '2.4.2');

        if ($this->isVersion('2.4.2')) {
            /** @var ProctorServiceDelegator $delegator */
            $delegator = $this->getServiceManager()->get(ProctorServiceInterface::SERVICE_ID);
            $delegator->registerHandler(new ltiProctorService());
            $this->getServiceManager()->register(ltiProctorService::SERVICE_ID, $delegator);
            $this->setVersion('2.5.0');
        }

        $this->skip('2.5.0', '2.6.0');

        if ($this->isVersion('2.6.0')) {
            $delegator = $this->getServiceManager()->get(TestTakerAuthorizationInterface::SERVICE_ID);
            $delegator->registerHandler(new LtiTestTakerAuthorizationService());
            $this->getServiceManager()->register(TestTakerAuthorizationInterface::SERVICE_ID, $delegator);
            $this->setVersion('3.0.0');
        }

        $this->skip('3.0.0', '3.3.3');

        if ($this->isVersion('3.3.3')) {
            OntologyUpdater::syncModels();
            AclProxy::applyRule(new AccessRule('grant', LtiRoles::CONTEXT_ADMINISTRATOR, \oat\taoProctoring\controller\MonitorProctorAdministrator::class));
            AclProxy::applyRule(new AccessRule('grant', LtiRoles::CONTEXT_ADMINISTRATOR, \oat\taoProctoring\controller\Reporting::class));
            AclProxy::applyRule(new AccessRule('grant', LtiRoles::CONTEXT_ADMINISTRATOR, \oat\taoProctoring\controller\Monitor::class));
            AclProxy::applyRule(new AccessRule('grant', LtiRoles::CONTEXT_ADMINISTRATOR, \oat\ltiProctoring\controller\Monitor::class));
            AclProxy::applyRule(new AccessRule('grant', LtiRoles::CONTEXT_ADMINISTRATOR, \oat\ltiProctoring\controller\Reporting::class));

            $this->setVersion('3.4.0');
        }

        $this->skip('3.4.0', '3.4.1');

        if ($this->isVersion('3.4.1')) {
            /** @var TestRunnerMessageService $testRunnerMessageService */
            $testRunnerMessageService = $this->getServiceManager()->get(TestRunnerMessageService::SERVICE_ID);
            $roles = $testRunnerMessageService->getOption(TestRunnerMessageService::PROCTOR_ROLES_OPTION);
            $roles[] = LtiRoles::CONTEXT_TEACHING_ASSISTANT;
            $roles[] = LtiRoles::CONTEXT_ADMINISTRATOR;
            $testRunnerMessageService->setOption(TestRunnerMessageService::PROCTOR_ROLES_OPTION, $roles);
            $this->getServiceManager()->register(TestRunnerMessageService::SERVICE_ID, $testRunnerMessageService);

            $this->setVersion('3.4.2');
        }

        if ($this->isVersion('3.4.2')) {
            /** @var ActivityMonitoringService $service */
            $service = $this->getServiceManager()->get(ActivityMonitoringService::SERVICE_ID);
            $options = $service->getOptions();
            $options = array_merge($options,
                [\oat\taoProctoring\model\ActivityMonitoringService::OPTION_USER_ACTIVITY_WIDGETS => [
                    'queueTestTakers' => [
                        'container' => 'queue-test-takers',
                        'label' => __('Queued test-takers'),
                        'value' => 0,
                        'icon' => 'takers',
                        'size' => 4
                    ]],
                ]);
            $service->setOptions($options);
            $this->getServiceManager()->register(ActivityMonitoringService::SERVICE_ID, $service);
            $this->setVersion('3.5.0');
        }

        $this->skip('3.5.0', '3.9.0');

        if ($this->isVersion('3.9.0')) {
            $lLtiDeliveryExecutionService = $this->safeLoadService(LtiDeliveryExecutionService::SERVICE_ID);
            if (!$lLtiDeliveryExecutionService instanceof LtiDeliveryExecutionService) {
                $this->getServiceManager()->register(
                    LtiDeliveryExecutionService::SERVICE_ID,
                    new OntologyLtiDeliveryExecutionService($lLtiDeliveryExecutionService->getOptions())
                );
            }
            $this->setVersion('4.0.0');
        }

        $this->skip('4.0.0', '6.0.0');

        if ($this->isVersion('6.0.0')) {
            $this->getServiceManager()->register(
                LtiMonitorParametersService::SERVICE_ID,
                new LtiMonitorParametersService()
            );
            $this->setVersion('6.1.0');
        }

        $this->skip('6.1.0', '8.0.0');

        if ($this->isVersion('8.0.0')) {
            try {
                $ltiLaunchDataServiceOptions = $this->getServiceManager()
                    ->get(LtiLaunchDataService::SERVICE_ID)
                    ->getOptions();
            } catch (\Throwable $ex) {
                $ltiLaunchDataServiceOptions = [];
            }
            $newLtiLaunchDataService = new LtiLaunchDataService($ltiLaunchDataServiceOptions);
            $this->getServiceManager()->register(LtiLaunchDataService::SERVICE_ID, $newLtiLaunchDataService);

            try {
                $resultCustomFieldsServiceOptions = $this->getServiceManager()
                    ->get(ResultCustomFieldsService::SERVICE_ID)
                    ->getOptions();
            } catch (\Throwable $ex) {
                $resultCustomFieldsServiceOptions = [];
            }
            $ltiResultCustomFieldsService = new LtiResultCustomFieldsService($resultCustomFieldsServiceOptions);
            $this->getServiceManager()->register(LtiResultCustomFieldsService::SERVICE_ID, $ltiResultCustomFieldsService);

            $this->setVersion('8.1.0');
        }

        if ($this->isVersion('8.1.0')) {
            $script = 'sudo -u www-data php index.php \'oat\taoProctoring\scripts\tools\KvMonitoringMigration\' -f context_id -d 1 -s 0 -pc -l 255';
            $this->addReport(\common_report_Report::createInfo("Run script :'" . $script . "' to finish updating. It may take few hours depending of amount of data."));

            $this->setVersion('8.2.0');
        }

        $this->skip('8.2.0', '8.3.0');
        if ($this->isVersion('8.3.0')) {
            $ltiNavigationService = $this->getServiceManager()->get(LtiNavigationService::SERVICE_ID);
            $ltiNavigationService->setOption(LtiNavigationService::OPTION_MESSAGE_FACTORY, new LtiProctoringMessageFactory());
            $this->getServiceManager()->register(LtiNavigationService::SERVICE_ID, $ltiNavigationService);

            $this->setVersion('9.0.0');
        }
    }
}
