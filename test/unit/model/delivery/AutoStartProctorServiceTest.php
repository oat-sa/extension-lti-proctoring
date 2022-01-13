<?php

declare(strict_types=1);

namespace oat\ltiProctoring\test\unit\model\delivery;

use common_session_Session;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\user\LtiUser;
use PHPUnit\Framework\TestCase;
use oat\ltiProctoring\model\delivery\AutoStartProctorService;
use oat\oatbox\log\LoggerService;
use oat\oatbox\user\User;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoQtiTest\models\TestSessionService;

final class AutoStartProctorServiceTest extends TestCase
{
    /**
     * @var AutoStartProctorService
     */
    private $autoStartProctorService;

    /**
     * @var TestSessionService
     */
    private $testSessionService;

    /**
     * @var LoggerService
     */
    private $loggerService;

    protected function setUp(): void
    {
        $this->testSessionService = $this->createMock(TestSessionService::class);
        $this->loggerService = $this->createMock(LoggerService::class);
        $this->autoStartProctorService = new AutoStartProctorService($this->testSessionService, $this->loggerService);
    }

    public function testExecuteWithoutSessionReturnNull(): void
    {
        $deliveryExecution = $this->createMock(DeliveryExecution::class);
        $user = $this->createMock(User::class);
        $session = $this->createMock(common_session_Session::class);
        $url = $this->autoStartProctorService->execute($deliveryExecution, $user, $session);

        self::assertNull($url);
    }

    public function testExecuteWithoutCustomAutoStartReturnNull(): void
    {
        $deliveryExecution = $this->createMock(DeliveryExecution::class);

        $ltiLaunchData = $this->createMock(LtiLaunchData::class);
        $ltiLaunchData->method('hasVariable')->willReturn(false);

        $user = $this->createMock(LtiUser::class);
        $user->method('getLaunchData')->willReturn($ltiLaunchData);

        $session = $this->createMock(common_session_Session::class);

        $url = $this->autoStartProctorService->execute($deliveryExecution, $user, $session);

        self::assertNull($url);
    }

    public function testExecuteLogWarningReturnNull(): void
    {
        $deliveryExecution = $this->createMock(DeliveryExecution::class);

        $ltiLaunchData = $this->createMock(LtiLaunchData::class);
        $ltiLaunchData->method('hasVariable')->willReturn(true);
        $ltiLaunchData->method('getBooleanVariable')->willThrowException(new LtiException());

        $user = $this->createMock(LtiUser::class);
        $user->method('getLaunchData')->willReturn($ltiLaunchData);

        $session = $this->createMock(common_session_Session::class);

        $this->loggerService->expects($this->once())->method('warning');

        $url = $this->autoStartProctorService->execute($deliveryExecution, $user, $session);

        self::assertNull($url);
    }

    public function testExecuteUpdateStateReturnUrl(): void
    {
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

        $session = $this->createMock(common_session_Session::class);

        $url = $this->autoStartProctorService->execute($deliveryExecution, $user, $session);

        self::assertSame('taoProctoring/DeliveryServer/runDeliveryExecution?deliveryExecution=id', $url);
    }
}
