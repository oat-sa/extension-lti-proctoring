<?php

namespace oat\ltiProctoring\scripts\update;

use oat\ltiProctoring\controller\DeliveryServer;
use oat\ltiProctoring\controller\Reporting;
use oat\ltiProctoring\model\LtiListenerService;
use oat\ltiProctoring\model\execution\LtiDeliveryExecutionService;
use oat\ltiProctoring\model\implementation\TestSessionHistoryService;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoDelivery\model\authorization\AuthorizationService as DeliveryAuthorizationService;
use oat\taoDelivery\model\authorization\strategy\AuthorizationAggregator;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoLti\models\classes\LtiRoles;
use oat\taoProctoring\controller\Monitor;
use oat\taoProctoring\model\authorization\ProctorAuthorizationProvider;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\taoProctoring\model\ProctorService;
use oat\taoDelivery\model\authorization\AuthorizationService;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;
use oat\ltiProctoring\model\delivery\LtiProctorAuthorizationProvider;
use oat\ltiProctoring\model\delivery\LtiTestTakerAuthorizationService;
use oat\ltiProctoring\model\ActivityMonitoringService;
use oat\oatbox\service\ServiceNotFoundException;

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
            $service = new LtiDeliveryExecutionService([]);
            $this->getServiceManager()->register(LtiDeliveryExecutionService::SERVICE_ID, $service);
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
            $this->setVersion('1.1.1');
        }
        $this->skip('1.1.1', '1.3.0');
    }
}
