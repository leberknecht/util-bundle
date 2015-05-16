<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use tps\UtilBundle\Command\GenerateServiceTestCommand;

class GenerateServiceTestCommandTest extends Symfony\Bundle\FrameworkBundle\Test\WebTestCase
{
    /**
     * @var \Symfony\Component\HttpKernel\Kernel | \PHPUnit_Framework_MockObject_MockObject
     */
    private $kernelMock;
    /**
     * @var \Symfony\Component\DependencyInjection\Container | \PHPUnit_Framework_MockObject_MockObject
     */
    private $containerMock;
    /**
     * @var \Symfony\Bundle\TwigBundle\TwigEngine | \PHPUnit_Framework_MockObject_MockObject
     */
    private $twigMock;

    public function testExecuteMissingClass()
    {
        $application = new Application();
        $application->add(new GenerateServiceTestCommand());

        $command = $application->find('tps:util:generate-service-test');
        $commandTester = new CommandTester($command);
        $this->setExpectedException('RuntimeException');
        $commandTester->execute(['command' => $command->getName()]);
    }

    public function testExecuteInvalidClass()
    {
        $this->containerMock = $this->shortGetMock('Symfony\Component\DependencyInjection\Container');
        $this->twigMock = $this->shortGetMock('Symfony\Bundle\TwigBundle\TwigEngine');
        $this->kernelMock = $this->shortGetMock('Symfony\Component\HttpKernel\Kernel');

        $this->kernelMock->expects($this->any())
            ->method('getContainer')->willReturn($this->containerMock);
        $this->containerMock->expects($this->once())
            ->method('get')
            ->with('templating')
            ->willReturn($this->twigMock);

        $application = new Application($this->kernelMock);
        $generateServiceTestCommand = new GenerateServiceTestCommand();
        $generateServiceTestCommand->setContainer($this->kernelMock->getContainer());
        $application->add($generateServiceTestCommand);

        $command = $application->find('tps:util:generate-service-test');
        $commandTester = new CommandTester($command);
        $this->setExpectedException('Exception', 'class not found');
        $commandTester->execute([
            'command' => $command->getName(),
            'class' => 'ThisClassDoesntExistsHopefully'
        ]);
    }

    public function testExecuteValidClass()
    {
        $expected ='checking parameter Doctrine\ORM\EntityManager
checking parameter Symfony\Bundle\TwigBundle\TwigEngine
namespace for generated Test: tps\UtilBundle\Tests\Tests\Stubs
<?php
namespace tps\UtilBundle\Tests\Tests\Stubs;

use PHPUnit_Framework_MockObject_MockObject;
use tps\UtilBundle\Tests\Stubs\ExampleClass;

class ExampleClassTest extends \PHPUnit_Framework_TestCase
{
   /**
    * @var PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\EntityManager
    */
    private $entityManagerMock;
   /**
    * @var PHPUnit_Framework_MockObject_MockObject|\Symfony\Bundle\TwigBundle\TwigEngine
    */
    private $twigEngineMock;

   /**
    * @var PHPUnit_Framework_MockObject_MockObject|\tps\UtilBundle\Tests\Stubs\ExampleClass
    */
    private $exampleClass;

    public function setUp()
    {
        $this->entityManagerMock = $this->getMockBuilder(\'Doctrine\ORM\EntityManager\')
            ->disableOriginalConstructor()->getMock();
        $this->twigEngineMock = $this->getMockBuilder(\'Symfony\Bundle\TwigBundle\TwigEngine\')
            ->disableOriginalConstructor()->getMock();

        $this->exampleClass = new ExampleClass(
            $this->entityManagerMock,            $this->twigEngineMock
        );
    }
}
namespace for generated Test: tps\UtilBundle\Tests\Tests\Stubs
';

        $client = $this->createClient();
        $application = new Application();
        $generateServiceTestCommand = new GenerateServiceTestCommand();
        $generateServiceTestCommand->setContainer($client->getContainer());
        $application->add($generateServiceTestCommand);

        $command = $application->find('tps:util:generate-service-test');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName(),
            'class' => 'tps\UtilBundle\Tests\Stubs\ExampleClass'
        ));

        $this->assertEquals($expected, $commandTester->getDisplay());

    }

    /**
     * @param $className
     * @param array $methods
     * @param bool|array $constructorData
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function shortGetMock($className, array $methods = [],  $constructorData = false) {
        $mb = $this->getMockBuilder($className);
        if (false === $constructorData) {
            $mb->disableOriginalConstructor();
        } else {
            $mb->setConstructorArgs($constructorData);
        }
        if (!empty($methods)) {
            $mb->setMethods($methods);
        }

        return $mb->getMock();
    }

}