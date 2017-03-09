<?php

namespace oat\ltiProctoring\scripts\update;

use oat\ltiProctoring\model\delivery\LtiProctorAuthorizationProvider;
use oat\taoDelivery\model\authorization\strategy\AuthorizationAggregator;
use oat\taoProctoring\model\authorization\ProctorAuthorizationProvider;
use oat\taoDelivery\model\authorization\AuthorizationService as DeliveryAuthorizationService;
use oat\ltiProctoring\model\execution\LtiDeliveryExecutionService;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\taoLti\models\classes\LtiRoles;
use oat\ltiProctoring\controller\DeliveryServer;

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
        $this->skip('0.5.0', '0.5.1');
    }
}
