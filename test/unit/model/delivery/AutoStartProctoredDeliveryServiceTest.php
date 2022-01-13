<?php

declare(strict_types=1);

namespace oat\ltiProctoring\test\unit\model\delivery;

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

        $deliveryExecution = $this->createMock(DeliveryExecution::class);
        $user = $this->createMock(User::class);
        $url = $this->autoStartProctoredDeliveryService->execute($deliveryExecution, $user);

        self::assertNull($url);
    }

    public function testExecuteWithoutSessionReturnNull(): void
    {
        $this->testTakerAuthorizationDelegator->method('isProctored')->willReturn(true);

        $deliveryExecution = $this->createMock(DeliveryExecution::class);
        $user = $this->createMock(User::class);
        $url = $this->autoStartProctoredDeliveryService->execute($deliveryExecution, $user);

        self::assertNull($url);
    }

    public function testExecuteWithoutCustomAutoStartReturnNull(): void
    {
        $this->testTakerAuthorizationDelegator->method('isProctored')->willReturn(true);

        $deliveryExecution = $this->createMock(DeliveryExecution::class);

        $ltiLaunchData = $this->createMock(LtiLaunchData::class);
        $ltiLaunchData->method('hasVariable')->willReturn(false);

        $user = $this->createMock(LtiUser::class);
        $user->method('getLaunchData')->willReturn($ltiLaunchData);

        $url = $this->autoStartProctoredDeliveryService->execute($deliveryExecution, $user);

        self::assertNull($url);
    }

    public function testExecuteLogWarningReturnNull(): void
    {
        $this->testTakerAuthorizationDelegator->method('isProctored')->willReturn(true);

        $deliveryExecution = $this->createMock(DeliveryExecution::class);

        $ltiLaunchData = $this->createMock(LtiLaunchData::class);
        $ltiLaunchData->method('hasVariable')->willReturn(true);
        $ltiLaunchData->method('getBooleanVariable')->willThrowException(new LtiException());

        $user = $this->createMock(LtiUser::class);
        $user->method('getLaunchData')->willReturn($ltiLaunchData);

        $this->loggerService->expects($this->once())->method('warning');

        $url = $this->autoStartProctoredDeliveryService->execute($deliveryExecution, $user);

        self::assertNull($url);
    }

    public function testExecuteUpdateStateReturnUrl(): void
    {
        $this->testTakerAuthorizationDelegator->method('isProctored')->willReturn(true);

        $deliveryExecutionImplementation = $this->createMock(DeliveryExecutionInterface::class);
        $deliveryExecutionImplementation->expects($this->once())->method('setState');

        $deliveryExecution = $this->createMock(DeliveryExecution::class);
        $deliveryExecution->method('getIdentifier')->willReturn('id');
        $deliveryExecution->method('getImplementation')->willReturn($deliveryExecutionImplementation);

        $ltiLaunchData = $this->createMock(LtiLaunchData::class);
        $ltiLaunchData->method('hasVariable')->willReturn(true);
        $ltiLaunchData->method('getBooleanVariable')->willReturn(true);

        $user = $this->createMock(LtiUser::class);
        $user->method('getLaunchData')->willReturn($ltiLaunchData);

        $url = $this->autoStartProctoredDeliveryService->execute($deliveryExecution, $user);

        self::assertSame('ltiProctoring/DeliveryServer/runDeliveryExecution?deliveryExecution=id', $url);
    }
}
