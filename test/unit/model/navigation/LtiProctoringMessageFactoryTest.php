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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */

declare(strict_types=1);

namespace oat\ltiProctoring\tests\model\navigation;

use core_kernel_classes_Resource;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\ltiProctoring\model\navigation\LtiProctoringMessageFactory;
use oat\oatbox\log\LoggerService;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiMessages\LtiMessage;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use Zend\ServiceManager\ServiceLocatorInterface;

class LtiProctoringMessageFactoryTest extends TestCase
{
    /**
     * @var DeliveryExecutionInterface|MockObject
     */
    private $deliveryExecutionMock;

    /**
     * @var core_kernel_classes_Resource|MockObject
     */
    private $deliveryExecutionStateMock;

    /**
     * @var DeliveryLog|MockObject
     */
    private $deliveryLogMock;

    /**
     * @var LtiProctoringMessageFactory
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockDeliveryExecution();

        $this->subject = new LtiProctoringMessageFactory();
        $this->subject->setServiceLocator($this->mockServiceLocator());
    }

    /**
     * @param string $deliveryExecutionState
     * @param array $logRecord
     * @param string $expectedLtiLog
     *
     * @dataProvider dataProviderTestGetLtiMessageReturnsCorrectMessage
     */
    public function testGetLtiMessageReturnsCorrectMessage(
        string $expectedMessageString,
        string $executionStateUri,
        array $logRecord,
        string $expectedLtiLog
    ): void {
        $this->deliveryExecutionStateMock->method('getUri')
            ->willReturn($executionStateUri);
        $this->deliveryExecutionStateMock->method('getLabel')
            ->willReturn($expectedMessageString);
        $this->deliveryLogMock->method('search')
            ->willReturn($logRecord);
        
        $ltiMessage = $this->subject->getLtiMessage($this->deliveryExecutionMock);

        self::assertInstanceOf(LtiMessage::class, $ltiMessage);
        self::assertSame($expectedMessageString, $ltiMessage->getMessage());
        self::assertSame($expectedLtiLog, $ltiMessage->getLog());
    }

    public function testGetLtiMessageEmptyLtiLogFoInvalidExecutionUri(): void
    {
        $expectedMessageString = 'Completed';
        $expectedLtiLog = '';
        $this->deliveryExecutionStateMock->method('getUri')
            ->willReturn('UNSUPPORTED_EXECUTION_STATE_URI');
        $this->deliveryExecutionStateMock->method('getLabel')
            ->willReturn($expectedMessageString);

        $ltiMessage = $this->subject->getLtiMessage($this->deliveryExecutionMock);

        self::assertInstanceOf(LtiMessage::class, $ltiMessage);
        self::assertSame($expectedMessageString, $ltiMessage->getMessage());
        self::assertSame($expectedLtiLog, $ltiMessage->getLog());
    }

    public function dataProviderTestGetLtiMessageReturnsCorrectMessage(): array
    {
        return [
            'Delivery without logs' => [
                'expectedMessageString' => 'Finished',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished',
                'logRecord' => [],
                'expectedLtiLog' => ''
            ],
            'Delivery with logs data empty array' => [
                'expectedMessageString' => 'Terminated',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusTerminated',
                'logRecord' => [
                    [
                        'data' => []
                    ]
                ],
                'expectedLtiLog' => ''
            ],
            'Delivery finished with correct log' => [
                'expectedMessageString' => 'Finished',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished',
                'logRecord' => [
                    [
                        'data' => [
                            'exitCode' => 'C'
                        ]
                    ]
                ],
                'expectedLtiLog' => 'Exit code: C' . PHP_EOL
            ],
            'Delivery terminated log with full reason' => [
                'expectedMessageString' => 'Terminated',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusTerminated',
                'logRecord' => [
                    [
                        'data' => [
                            'reason' => [
                                'reasons' => [
                                    'category' => 'FAKE_CATEGORY',
                                    'subCategory' => 'FAKE_SUB_CATEGORY',
                                ],
                                'comment' => 'FAKE_TERMINATED_COMMENT',
                            ],
                        ]
                    ],
                ],
                'expectedLtiLog' => 'FAKE_CATEGORY; FAKE_SUB_CATEGORY - FAKE_TERMINATED_COMMENT'
            ],
            'Delivery paused log with reason without category' => [
                'expectedMessageString' => 'Paused',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusPaused',
                'logRecord' => [
                    [
                        'data' => [
                            'reason' => [
                                'reasons' => [
                                    'subCategory' => 'FAKE_SUB_CATEGORY',
                                ],
                                'comment' => 'FAKE_PAUSED_COMMENT',
                            ],
                        ]
                    ],
                ],
                'expectedLtiLog' => '; FAKE_SUB_CATEGORY - FAKE_PAUSED_COMMENT'
            ],
            'Delivery canceled log with reason without sub category' => [
                'expectedMessageString' => 'Canceled',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusCanceled',
                'logRecord' => [
                    [
                        'data' => [
                            'reason' => [
                                'reasons' => [
                                    'category' => 'FAKE_CATEGORY',
                                ],
                                'comment' => 'FAKE_CANCELED_COMMENT',
                            ],
                        ]
                    ],
                ],
                'expectedLtiLog' => 'FAKE_CATEGORY - FAKE_CANCELED_COMMENT'
            ],
            'Delivery canceled log with reason without comment' => [
                'expectedMessageString' => 'Canceled',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusCanceled',
                'logRecord' => [
                    [
                        'data' => [
                            'reason' => [
                                'reasons' => [
                                    'category' => 'FAKE_CATEGORY',
                                    'subCategory' => 'FAKE_SUB_CATEGORY',
                                ],
                            ],
                        ]
                    ],
                ],
                'expectedLtiLog' => 'FAKE_CATEGORY; FAKE_SUB_CATEGORY'
            ],
        ];
    }

    private function mockDeliveryExecution(): void
    {
        $this->deliveryExecutionStateMock = $this->createMock(core_kernel_classes_Resource::class);

        $this->deliveryExecutionMock = $this->createMock(DeliveryExecutionInterface::class);
        $this->deliveryExecutionMock->method('getState')
            ->willReturn($this->deliveryExecutionStateMock);
        $this->deliveryExecutionMock->method('getIdentifier')
            ->willReturn('FAKE_IDENTIFIER');
    }

    private function mockServiceLocator(): ServiceLocatorInterface
    {
        $this->deliveryLogMock = $this->createMock(DeliveryLog::class);

        return $this->getServiceLocatorMock([
            DeliveryLog::SERVICE_ID => $this->deliveryLogMock,
            LoggerService::SERVICE_ID => $this->createMock(LoggerService::class),
        ]);
    }
}

