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
use oat\ltiProctoring\model\navigation\ProctoringLtiMessageFactory;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiMessages\LtiMessage;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use Zend\ServiceManager\ServiceLocatorInterface;

class ProctoringLtiMessageFactoryTest extends TestCase
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
     * @var ProctoringLtiMessageFactory
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockDeliveryExecution();

        $this->subject = new ProctoringLtiMessageFactory();
        $this->subject->setServiceLocator($this->mockServiceLocator());
    }

    /**
     * @param string $deliveryExecutionState
     * @param array $logRecords
     * @param string $expectedLtiLog
     *
     * @dataProvider dataProviderTestGetLtiMessageReturnsCorrectMessage
     */
    public function testGetLtiMessageReturnsCorrectMessage(
        string $executionState,
        string $executionStateUri,
        array $logRecords,
        string $expectedLtiLog
    ): void {
        $this->deliveryExecutionStateMock->method('getUri')
            ->willReturn($executionStateUri);
        $this->deliveryExecutionStateMock->method('getLabel')
            ->willReturn($executionState);
        $this->deliveryLogMock->method('get')
            ->willReturn($logRecords);
        
        $ltiMessage = $this->subject->getLtiMessage($this->deliveryExecutionMock);

        self::assertInstanceOf(LtiMessage::class, $ltiMessage);
        self::assertSame($executionState, $ltiMessage->getMessage());
        self::assertSame($expectedLtiLog, $ltiMessage->getLog());
    }

    public function dataProviderTestGetLtiMessageReturnsCorrectMessage(): array
    {
        return [
            'Delivery finished no logs' => [
                'executionState' => 'Finished',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished',
                'logRecords' => [],
                'expectedLtiLog' => ''
            ],
            'Delivery finished with logs' => [
                'executionState' => 'Finished',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished',
                'logRecords' => [
                    [
                        'data' => [
                            'exitCode' => 'C'
                        ]
                    ]
                ],
                'expectedLtiLog' => 'Exit code: C' . PHP_EOL
            ],
            'Delivery terminated no logs' => [
                'executionState' => 'Terminated',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusTerminated',
                'logRecords' => [],
                'expectedLtiLog' => ''
            ],
            'Delivery terminated multiple logs' => [
                'executionState' => 'Terminated',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusTerminated',
                'logRecords' => [
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
                    [
                        'data' => []
                    ],
                ],
                'expectedLtiLog' => ''
            ],
            'Delivery terminated log with full reason' => [
                'executionState' => 'Terminated',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusTerminated',
                'logRecords' => [
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
            'Delivery paused no logs' => [
                'executionState' => 'Paused',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusPaused',
                'logRecords' => [],
                'expectedLtiLog' => ''
            ],
            'Delivery paused multiple logs' => [
                'executionState' => 'Paused',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusPaused',
                'logRecords' => [
                    [
                        'data' => [
                            'reason' => [
                                'reasons' => [
                                    'category' => 'FAKE_CATEGORY',
                                    'subCategory' => 'FAKE_SUB_CATEGORY',
                                ],
                                'comment' => 'FAKE_PAUSED_COMMENT',
                            ],
                        ]
                    ],
                    [
                        'data' => []
                    ],
                ],
                'expectedLtiLog' => ''
            ],
            'Delivery paused log with reason without category' => [
                'executionState' => 'Paused',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusPaused',
                'logRecords' => [
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
            'Delivery canceled no logs' => [
                'executionState' => 'FAKE_CANCELED',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusCanceled',
                'logRecords' => [],
                'expectedLtiLog' => ''
            ],
            'Delivery canceled multiple logs' => [
                'executionState' => 'Canceled',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusCanceled',
                'logRecords' => [
                    [
                        'data' => []
                    ],
                    [
                        'data' => [
                            'reason' => [
                                'reasons' => [
                                    'category' => 'FAKE_CATEGORY',
                                    'subCategory' => 'FAKE_SUB_CATEGORY',
                                ],
                                'comment' => 'FAKE_CANCELED_COMMENT',
                            ],
                        ]
                    ],
                ],
                'expectedLtiLog' => 'FAKE_CATEGORY; FAKE_SUB_CATEGORY - FAKE_CANCELED_COMMENT'
            ],
            'Delivery canceled log with reason without sub category' => [
                'executionState' => 'Canceled',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusCanceled',
                'logRecords' => [
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
                'executionState' => 'Canceled',
                'executionStateUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusCanceled',
                'logRecords' => [
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
            DeliveryLog::SERVICE_ID => $this->deliveryLogMock
        ]);
    }
}

