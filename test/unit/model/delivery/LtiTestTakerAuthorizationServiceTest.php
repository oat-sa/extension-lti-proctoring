<?php

namespace oat\ltiProctoring\test\unit\model\delivery;

use common_session_Session;
use core_kernel_classes_Resource;
use core_kernel_classes_Property;
use oat\generis\test\TestCase;
use oat\generis\test\MockObject;
use oat\generis\model\data\Ontology;
use oat\ltiProctoring\model\delivery\LtiTestTakerAuthorizationService;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiInvalidVariableException;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\TaoLtiSession;
use oat\taoLti\models\classes\user\LtiUser;

class LtiTestTakerAuthorizationServiceTest extends TestCase
{
    private $object;

    /**
     * @var Ontology|MockObject
     */
    private $ontologyMock;

    /**
     * @var core_kernel_classes_Resource|MockObject
     */
    private $deliveryResourceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deliveryResourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $this->ontologyMock = $this->createMock(Ontology::class);

        $this->object = new LtiTestTakerAuthorizationService();
    }

    /**
     * @param string $proctoredPropertyUriValue
     * @param string $sessionType
     * @param bool $ltiVarExists
     * @param bool $ltiVarValue
     * @param bool $expectedResult
     *
     * @dataProvider dataProviderTestIsProctored
     *
     * @throws \oat\taoLti\models\classes\LtiException
     * @throws \oat\taoLti\models\classes\LtiVariableMissingException
     */
    public function testIsProctored($proctoredPropertyUriValue, $sessionType, $ltiVarExists, $ltiVarValue, $expectedResult)
    {
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

    public function testIsProctoredInvalidLtiVariableThrowsException()
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

    public function dataProviderTestIsProctored()
    {
        return [
            'Not LTI session, parent - proctored' => [
                'proctoredPropertyUriValue' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled',
                'sessionType' => common_session_Session::class,
                'ltiVarExists' => 'NOT_IMPORTANT',
                'ltiVarValue' => 'NOT_IMPORTANT',
                'expectedResult' => true,
            ],
            'Not LTI session, parent - not proctored' => [
                'proctoredPropertyUriValue' => 'NOT_PROCTORED_URI_VALUE',
                'sessionType' => common_session_Session::class,
                'ltiVarExists' => 'NOT_IMPORTANT',
                'ltiVarValue' => 'NOT_IMPORTANT',
                'expectedResult' => false,
            ],
            'LTI session, parent - proctored, lti variable does not exists' => [
                'proctoredPropertyUriValue' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled',
                'sessionType' => TaoLtiSession::class,
                'ltiVarExists' => false,
                'ltiVarValue' => 'NOT_IMPORTANT',
                'expectedResult' => true,
            ],
            'LTI session, parent - not proctored, lti variable does not exists' => [
                'proctoredPropertyUriValue' => 'NOT_PROCTORED_URI_VALUE',
                'sessionType' => TaoLtiSession::class,
                'ltiVarExists' => false,
                'ltiVarValue' => 'NOT_IMPORTANT',
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

    /**
     * @param string $proctoredPropertyUriValue
     */
    private function mockParentIsProctoredBehavior($proctoredPropertyUriValue)
    {
        $proctoredPropertyMock = $this->createMock(core_kernel_classes_Property::class);
        $proctoredPropertyMock->method('getUri')->willReturn($proctoredPropertyUriValue);

        $this->deliveryResourceMock->method('getOnePropertyValue')->willReturn($proctoredPropertyMock);

        $this->ontologyMock->method('getResource')->willReturn($this->deliveryResourceMock);
        $this->ontologyMock->method('getProperty')->willReturn(new core_kernel_classes_Property('FAKE_PROPERTY'));
    }

    /**
     * @param $sessionType
     * @param MockObject $launchDataMock
     * @return MockObject
     */
    private function getSessionServiceMock($sessionType, LtiLaunchData $launchDataMock)
    {
        $currentSessionMock = $this->createMock($sessionType);
        if ($currentSessionMock instanceof TaoLtiSession) {
            $currentSessionMock->method('getLaunchData')->willReturn($launchDataMock);
        }

        $sessionServiceMock = $this->createMock(SessionService::class);
        $sessionServiceMock->method('getCurrentSession')->willReturn($currentSessionMock);

        return $sessionServiceMock;
    }

    /**
     * @param $ltiVarExists
     * @param $ltiVarValue
     * @param string $ltiVarName
     * @return MockObject
     */
    private function getLtiLaunchDataMock($ltiVarName, $ltiVarExists, $ltiVarValue)
    {
        $launchDataMock = $this->createMock(LtiLaunchData::class);
        $launchDataMock->method('hasVariable')
            ->with($ltiVarName)
            ->willReturn($ltiVarExists);
        $launchDataMock->method('getBooleanVariable')->willReturn($ltiVarValue);

        return $launchDataMock;
    }
}
