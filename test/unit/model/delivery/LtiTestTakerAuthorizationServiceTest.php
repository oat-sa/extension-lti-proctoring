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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\ltiProctoring\test\unit\model\delivery;

use common_session_Session;
use core_kernel_classes_Resource;
use core_kernel_classes_Property;
use oat\generis\test\TestCase;
use oat\generis\model\data\Ontology;
use oat\ltiProctoring\model\delivery\LtiTestTakerAuthorizationService;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use oat\taoDelivery\model\authorization\UnAuthorizedException;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiInvalidVariableException;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\TaoLtiSession;
use oat\taoProctoring\model\execution\DeliveryExecution;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class LtiTestTakerAuthorizationServiceTest extends TestCase
{
    /** @var LtiTestTakerAuthorizationService */
    private $object;

    /** @var Ontology|MockObject */
    private $ontologyMock;

    /** @var core_kernel_classes_Resource|MockObject */
    private $deliveryResourceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deliveryResourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $this->ontologyMock = $this->createMock(Ontology::class);
        $this->object = new LtiTestTakerAuthorizationService();
    }

    /**
     * @dataProvider dataProviderTestIsProctored
     */
    public function testIsProctored(
        string $proctoredPropertyUriValue,
        string $sessionType,
        bool $ltiVarExists,
        bool $ltiVarValue,
        bool $expectedResult
    ) {
        $deliveryUri = 'FAKE_DELIVERY_URI';
        $user = $this->createMock(User::class);

        $this->mockParentIsProctoredBehavior($proctoredPropertyUriValue);

        // Test LTI parameters functionality
        $ltiVarName = 'custom_proctored';
        $launchDataMock = $this->getLtiLaunchDataMock($ltiVarName, $ltiVarExists, $ltiVarValue);
        $sessionServiceMock = $this->getSessionServiceMock($sessionType, $launchDataMock);

        $slMock = $this->getServiceLocatorMock([
            Ontology::SERVICE_ID => $this->ontologyMock,
            SessionService::SERVICE_ID => $sessionServiceMock,
        ]);
        $this->object->setServiceLocator($slMock);
        $result = $this->object->isProctored($deliveryUri, $user);

        $this->assertEquals($expectedResult, $result, 'Result of isProctored() check must be as expected.');
    }

    public function testIsProctoredInvalidLtiVariableThrowsException(): void
    {
        $deliveryUri = 'FAKE_DELIVERY_URI';
        $user = $this->createMock(User::class);

        $this->mockParentIsProctoredBehavior("DUMMY_PROPERTY_VALUE");

        $ltiVarName = 'custom_proctored';
        $launchDataMock = $this->createMock(LtiLaunchData::class);
        $launchDataMock->method('hasVariable')
            ->with($ltiVarName)
            ->willReturn(true);
        $launchDataMock->method('getBooleanVariable')
            ->willThrowException(new LtiInvalidVariableException("Exception message"));

        $sessionServiceMock = $this->getSessionServiceMock(TaoLtiSession::class, $launchDataMock);

        $slMock = $this->getServiceLocatorMock([
            Ontology::SERVICE_ID => $this->ontologyMock,
            SessionService::SERVICE_ID => $sessionServiceMock,
        ]);
        $this->object->setServiceLocator($slMock);

        $this->expectException(LtiException::class);

        $this->object->isProctored($deliveryUri, $user);
    }

    public function testVerifyResumeAuthorizationWillThrowUnAuthorizedException(): void
    {
        $user = $this->createMock(User::class);

        $this->mockParentIsProctoredBehavior('http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled');

        $delivery = $this->createMock(core_kernel_classes_Resource::class);
        $delivery->method('getUri')
            ->willReturn('FAKE_DELIVERY_URI');

        $state = $this->createMock(core_kernel_classes_Resource::class);
        $state->method('getUri')
            ->willReturn(DeliveryExecution::STATE_AUTHORIZED);

        $deliveryExecution = $this->createMock(DeliveryExecutionInterface::class);
        $deliveryExecution->method('getState')
            ->willReturn($state);

        $deliveryExecution->method('getDelivery')
            ->willReturn($delivery);

        $launchDataMock = $this->getLtiLaunchDataMock('custom_proctored', true, true);
        $sessionServiceMock = $this->getSessionServiceMock(common_session_Session::class, $launchDataMock);

        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')
            ->willReturn('/ltiDeliveryProvider/DeliveryRunner/runDeliveryExecution');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')
            ->willReturn($uri);

        $this->object->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    Ontology::SERVICE_ID => $this->ontologyMock,
                    SessionService::SERVICE_ID => $sessionServiceMock,
                    ServerRequestInterface::class => $request
                ]
            )
        );

        $this->expectException(UnAuthorizedException::class);

        $this->object->verifyResumeAuthorization($deliveryExecution, $user);
    }

    public function dataProviderTestIsProctored(): array
    {
        return [
            'Not LTI session, parent - proctored' => [
                'proctoredPropertyUriValue' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled',
                'sessionType' => common_session_Session::class,
                'ltiVarExists' => true, // NOT_IMPORTANT
                'ltiVarValue' => true, // NOT_IMPORTANT
                'expectedResult' => true,
            ],
            'Not LTI session, parent - not proctored' => [
                'proctoredPropertyUriValue' => 'NOT_PROCTORED_URI_VALUE',
                'sessionType' => common_session_Session::class,
                'ltiVarExists' => true, // NOT_IMPORTANT
                'ltiVarValue' => true, // NOT_IMPORTANT
                'expectedResult' => false,
            ],
            'LTI session, parent - proctored, lti variable does not exists' => [
                'proctoredPropertyUriValue' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled',
                'sessionType' => TaoLtiSession::class,
                'ltiVarExists' => false,
                'ltiVarValue' => true, // NOT_IMPORTANT
                'expectedResult' => true,
            ],
            'LTI session, parent - not proctored, lti variable does not exists' => [
                'proctoredPropertyUriValue' => 'NOT_PROCTORED_URI_VALUE',
                'sessionType' => TaoLtiSession::class,
                'ltiVarExists' => false,
                'ltiVarValue' => true, // NOT_IMPORTANT
                'expectedResult' => false,
            ],
            'LTI session, parent - proctored, lti variable exists, lti var value - true' => [
                'proctoredPropertyUriValue' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled',
                'sessionType' => TaoLtiSession::class,
                'ltiVarExists' => true,
                'ltiVarValue' => true,
                'expectedResult' => true,
            ],
            'LTI session, parent - not proctored, lti variable exists, lti var value - true' => [
                'proctoredPropertyUriValue' => 'NOT_PROCTORED_URI_VALUE',
                'sessionType' => TaoLtiSession::class,
                'ltiVarExists' => true,
                'ltiVarValue' => true,
                'expectedResult' => true,
            ],
            'LTI session, parent - proctored, lti variable exists, lti var value - false' => [
                'proctoredPropertyUriValue' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled',
                'sessionType' => TaoLtiSession::class,
                'ltiVarExists' => true,
                'ltiVarValue' => false,
                'expectedResult' => false,
            ],
        ];
    }

    private function mockParentIsProctoredBehavior(string $proctoredPropertyUriValue): void
    {
        $proctoredPropertyMock = $this->createMock(core_kernel_classes_Property::class);
        $proctoredPropertyMock->method('getUri')->willReturn($proctoredPropertyUriValue);

        $this->deliveryResourceMock->method('getOnePropertyValue')->willReturn($proctoredPropertyMock);

        $this->ontologyMock->method('getResource')->willReturn($this->deliveryResourceMock);
        $this->ontologyMock->method('getProperty')->willReturn(new core_kernel_classes_Property('FAKE_PROPERTY'));
    }

    private function getSessionServiceMock(string $sessionType, LtiLaunchData $launchDataMock): MockObject
    {
        $currentSessionMock = $this->createMock($sessionType);

        if ($currentSessionMock instanceof TaoLtiSession) {
            $currentSessionMock->method('getLaunchData')->willReturn($launchDataMock);
        }

        $sessionServiceMock = $this->createMock(SessionService::class);
        $sessionServiceMock->method('getCurrentSession')->willReturn($currentSessionMock);

        return $sessionServiceMock;
    }

    private function getLtiLaunchDataMock(string $ltiVarName, bool $ltiVarExists, $ltiVarValue): MockObject
    {
        $launchDataMock = $this->createMock(LtiLaunchData::class);
        $launchDataMock->method('hasVariable')
            ->with($ltiVarName)
            ->willReturn($ltiVarExists);
        $launchDataMock->method('getBooleanVariable')->willReturn($ltiVarValue);

        return $launchDataMock;
    }
}
