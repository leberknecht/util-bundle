<?php


namespace Tps\UtilBundle\Tests\Service;


use PHPUnit_Framework_MockObject_MockObject;
use Tps\UtilBundle\Service\TestGeneratorService;
use Tps\Util\Tests\Fixtures\EmptyConstructorClass;

class TestGeneratorServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|\Symfony\Bridge\Twig\TwigEngine
     */
    private $twigEngineMock;
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
     */
    private $loggerInterfaceMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|\Tps\UtilBundle\Service\TestGeneratorService
     */
    private $testGeneratorService;

    public function setUp()
    {
        $this->twigEngineMock = $this->getMockBuilder('Symfony\Bridge\Twig\TwigEngine')
            ->disableOriginalConstructor()->getMock();
        $this->loggerInterfaceMock = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()->getMock();

        $this->testGeneratorService = new TestGeneratorService(
            $this->twigEngineMock,
            $this->loggerInterfaceMock
        );
    }

    public function testGetNamespaceNameWithBundleName()
    {
        $actual = $this->testGeneratorService->getTestNamespace(__CLASS__);
        $this->assertEquals('Tps\UtilBundle\Tests\Tests\Service', $actual);
    }

    public function testGetNamespaceNameWithoutBundleName()
    {
        $actual = $this->testGeneratorService->getTestNamespace('Tps\Util\Tests\Fixtures\WeirdClass');
        $this->assertEquals('Tps\Util\Tests\Fixtures\Tests', $actual);
    }

    public function testClassWithoutContructor()
    {
        $this->twigEngineMock->expects($this->once())
            ->method('render')
            ->with('TpsUtilBundle::phpunit.template.php.twig', [
                'test_namespace' => 'Tps\UtilBundle\Tests\Tests\Fixtures',
                'original_short_name' => 'EmptyConstructorClass',
                'original_full_name' => 'Tps\UtilBundle\Tests\Fixtures\EmptyConstructorClass',
                'service_member_name' => 'emptyConstructorClass',
                'mocks' => []
            ]);

        $this->testGeneratorService->generateTemplate(
            'Tps\UtilBundle\Tests\Fixtures\EmptyConstructorClass'
        );

    }
}