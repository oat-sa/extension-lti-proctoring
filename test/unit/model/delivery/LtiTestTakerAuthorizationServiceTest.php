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

    protected function setUp()
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

    /**
     * @param string $testRunnerFeatures
     * @param string $sessionType
     * @param bool $ltiVarExists
     * @param bool $ltiVarValue
     * @param bool $expectedResult
     *
     * @dataProvider dataProviderTestIsSecure
     *
     * @throws LtiException
     * @throws \common_Exception
     */
    public function testIsSecure($testRunnerFeatures, $sessionType, $ltiVarExists, $ltiVarValue, $expectedResult)
    {
        $deliveryId = 'FAKE_DELIVERY_ID';

        $this->mockParentIsSecureBehavior($testRunnerFeatures);

        $ltiVarName = 'custom_secure';
        $launchDataMock = $this->getLtiLaunchDataMock($ltiVarName, $ltiVarExists, $ltiVarValue);
        $sessionServiceMock = $this->getSessionServiceMock($sessionType, $launchDataMock);

        $serviceLocatorMock = $this->getServiceLocatorMock([
            Ontology::SERVICE_ID => $this->ontologyMock,
            SessionService::SERVICE_ID => $sessionServiceMock
        ]);
        $this->object->setServiceLocator($serviceLocatorMock);

        $result = $this->object->isSecure($deliveryId);
        $this->assertEquals($expectedResult, $result, 'Result of isSecure() check must be as expected.');
    }

    public function testIsSecureInvalidLtiVariableThrowsException()
    {
        $deliveryId = 'FAKE_DELIVERY_ID';

        $this->mockParentIsSecureBehavior("FAKE_TEST_RUNNER_FEATURES");

        $ltiVarName = 'custom_secure';
        $launchDataMock = $this->createMock(LtiLaunchData::class);
        $launchDataMock->method('hasVariable')
            ->with($ltiVarName)
            ->willReturn(true);
        $launchDataMock->method('getBooleanVariable')
            ->willThrowException(new LtiInvalidVariableException("Exception message"));

        $sessionServiceMock = $this->getSessionServiceMock(TaoLtiSession::class, $launchDataMock);

        $serviceLocatorMock = $this->getServiceLocatorMock([
            Ontology::SERVICE_ID => $this->ontologyMock,
            SessionService::SERVICE_ID => $sessionServiceMock
        ]);
        $this->object->setServiceLocator($serviceLocatorMock);

        $this->expectException(LtiException::class);
        $this->object->isSecure($deliveryId);
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

    public function dataProviderTestIsSecure()
    {
        return [
            'Not LTI session, parent - secure' => [
                'testRunnerFeatures' => 'security',
                'sessionType' => common_session_Session::class,
                'ltiVarExists' => 'NOT_IMPORTANT',
                'ltiVarValue' => 'NOT_IMPORTANT',
                'expectedResult' => true,
            ],
            'Not LTI session, parent - not secure' => [
                'testRunnerFeatures' => 'NO_SECURITY_VALUE',
                'sessionType' => common_session_Session::class,
                'ltiVarExists' => 'NOT_IMPORTANT',
                'ltiVarValue' => 'NOT_IMPORTANT',
                'expectedResult' => false,
            ],
            'LTI session, parent - secure, lti variable does not exists' => [
                'testRunnerFeatures' => 'security',
                'sessionType' => TaoLtiSession::class,
                'ltiVarExists' => false,
                'ltiVarValue' => 'NOT_IMPORTANT',
                'expectedResult' => true,
            ],
            'LTI session, parent - not secure, lti variable does not exists' => [
                'testRunnerFeatures' => 'NO_SECURITY_VALUE',
                'sessionType' => TaoLtiSession::class,
                'ltiVarExists' => false,
                'ltiVarValue' => 'NOT_IMPORTANT',
                'expectedResult' => false,
            ],
            'LTI session, parent - secure, lti variable exists, lti var value - true' => [
                'testRunnerFeatures' => 'security',
                'sessionType' => TaoLtiSession::class,
                'ltiVarExists' => true,
                'ltiVarValue' => true,
                'expectedResult' => true,
            ],
            'LTI session, parent - not secure secure, lti variable exists, lti var value - true' => [
                'testRunnerFeatures' => 'NO_SECURITY_VALUE',
                'sessionType' => TaoLtiSession::class,
                'ltiVarExists' => true,
                'ltiVarValue' => true,
                'expectedResult' => true,
            ],
            'LTI session, parent - secure, lti variable exists, lti var value - false' => [
                'testRunnerFeatures' => 'security',
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
     * @param string $testRunnerFeatures
     */
    private function mockParentIsSecureBehavior($testRunnerFeatures)
    {
        $this->deliveryResourceMock->method('getOnePropertyValue')->willReturn($testRunnerFeatures);

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
        $currentSessionMock->method('getLaunchData')->willReturn($launchDataMock);

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
