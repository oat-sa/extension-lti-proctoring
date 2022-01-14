<?php

declare(strict_types=1);

namespace oat\ltiProctoring\test\unit\model\delivery;

use core_kernel_classes_Resource;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\user\LtiUser;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationDelegator;
use PHPUnit\Framework\TestCase;
use oat\ltiProctoring\model\delivery\AutoStartProctoredDeliveryService;
use oat\oatbox\log\LoggerService;
use oat\oatbox\user\User;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoQtiTest\models\TestSessionService;

final class AutoStartProctoredDeliveryServiceTest extends TestCase
{
    /**
     * @var AutoStartProctoredDeliveryService
     */
    private $autoStartProctoredDeliveryService;

    /**
     * @var TestSessionService
     */
    private $testSessionService;

    /**
     * @var TestTakerAuthorizationDelegator
     */
    private $testTakerAuthorizationDelegator;

    /**
     * @var LoggerService
     */
    private $loggerService;

    protected function setUp(): void
    {
        $this->testSessionService = $this->createMock(TestSessionService::class);
        $this->testTakerAuthorizationDelegator = $this->createMock(TestTakerAuthorizationDelegator::class);
        $this->loggerService = $this->createMock(LoggerService::class);
        $this->autoStartProctoredDeliveryService = new AutoStartProctoredDeliveryService(
            $this->testSessionService,
            $this->testTakerAuthorizationDelegator,
            $this->loggerService
        );
    }

    public function testExecuteIsNotProctoredReturnNull(): void
    {
        $this->testTakerAuthorizationDelegator->method('isProctored')->willReturn(false);

        $deliveryResource = $this->createMock(core_kernel_classes_Resource::class);

        $deliveryExecution = $this->createMock(DeliveryExecution::class);
        $deliveryExecution->method('getDelivery')->willReturn($deliveryResource);

        $user = $this->createMock(User::class);

        self::assertFalse($this->autoStartProctoredDeliveryService->execute($deliveryExecution, $user));
    }

    public function testExecuteWithoutSessionReturnNull(): void
    {
        $this->testTakerAuthorizationDelegator->method('isProctored')->willReturn(true);

        $deliveryResource = $this->createMock(core_kernel_classes_Resource::class);

        $deliveryExecution = $this->createMock(DeliveryExecution::class);
        $deliveryExecution->method('getDelivery')->willReturn($deliveryResource);

        $user = $this->createMock(User::class);

        self::assertFalse($this->autoStartProctoredDeliveryService->execute($deliveryExecution, $user));
    }

    public function testExecuteWithoutCustomAutoStartReturnNull(): void
    {
        $this->testTakerAuthorizationDelegator->method('isProctored')->willReturn(true);

        $deliveryResource = $this->createMock(core_kernel_classes_Resource::class);

        $deliveryExecution = $this->createMock(DeliveryExecution::class);
        $deliveryExecution->method('getDelivery')->willReturn($deliveryResource);

        $ltiLaunchData = $this->createMock(LtiLaunchData::class);
        $ltiLaunchData->method('hasVariable')->willReturn(false);

        $user = $this->createMock(LtiUser::class);
        $user->method('getLaunchData')->willReturn($ltiLaunchData);

        self::assertFalse($this->autoStartProctoredDeliveryService->execute($deliveryExecution, $user));
    }

    public function testExecuteLogWarningReturnNull(): void
    {
        $this->testTakerAuthorizationDelegator->method('isProctored')->willReturn(true);

        $deliveryResource = $this->createMock(core_kernel_classes_Resource::class);

        $deliveryExecution = $this->createMock(DeliveryExecution::class);
        $deliveryExecution->method('getDelivery')->willReturn($deliveryResource);

        $ltiLaunchData = $this->createMock(LtiLaunchData::class);
        $ltiLaunchData->method('hasVariable')->willReturn(true);
        $ltiLaunchData->method('getBooleanVariable')->willThrowException(new LtiException());

        $user = $this->createMock(LtiUser::class);
        $user->method('getLaunchData')->willReturn($ltiLaunchData);

        $this->loggerService->expects($this->once())->method('warning');

        self::assertFalse($this->autoStartProctoredDeliveryService->execute($deliveryExecution, $user));
    }

    public function testExecuteUpdateStateReturnUrl(): void
    {
        $this->testTakerAuthorizationDelegator->method('isProctored')->willReturn(true);

        $deliveryExecutionImplementation = $this->createMock(DeliveryExecutionInterface::class);
        $deliveryExecutionImplementation->expects($this->once())->method('setState');

        $deliveryResource = $this->createMock(core_kernel_classes_Resource::class);

        $deliveryExecution = $this->createMock(DeliveryExecution::class);
        $deliveryExecution->method('getIdentifier')->willReturn('id');
        $deliveryExecution->method('getDelivery')->willReturn($deliveryResource);
        $deliveryExecution->method('getImplementation')->willReturn($deliveryExecutionImplementation);

        $ltiLaunchData = $this->createMock(LtiLaunchData::class);
        $ltiLaunchData->method('hasVariable')->willReturn(true);
        $ltiLaunchData->method('getBooleanVariable')->willReturn(true);

        $user = $this->createMock(LtiUser::class);
        $user->method('getLaunchData')->willReturn($ltiLaunchData);

        self::assertTrue($this->autoStartProctoredDeliveryService->execute($deliveryExecution, $user));
    }
}
