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

    private $expected ='checking parameter Symfony\Component\Form\Form
checking parameter Symfony\Bundle\TwigBundle\TwigEngine
<?php
namespace tps\UtilBundle\Tests\Tests\Fixtures;

use PHPUnit_Framework_MockObject_MockObject;
use tps\UtilBundle\Tests\Fixtures\ExampleClass;

class ExampleClassTest extends \PHPUnit_Framework_TestCase
{
   /**
    * @var PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Form\Form
    */
    private $formMock;
   /**
    * @var PHPUnit_Framework_MockObject_MockObject|\Symfony\Bundle\TwigBundle\TwigEngine
    */
    private $twigEngineMock;

   /**
    * @var PHPUnit_Framework_MockObject_MockObject|\tps\UtilBundle\Tests\Fixtures\ExampleClass
    */
    private $exampleClass;

    public function setUp()
    {
        $this->formMock = $this->getMockBuilder(\'Symfony\Component\Form\Form\')
            ->disableOriginalConstructor()->getMock();
        $this->twigEngineMock = $this->getMockBuilder(\'Symfony\Bundle\TwigBundle\TwigEngine\')
            ->disableOriginalConstructor()->getMock();

        $this->exampleClass = new ExampleClass(
            $this->formMock,            $this->twigEngineMock
        );
    }
}
';

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

        $client = $this->createClient();
        $application = new Application();
        $generateServiceTestCommand = new GenerateServiceTestCommand();
        $generateServiceTestCommand->setContainer($client->getContainer());
        $application->add($generateServiceTestCommand);

        $command = $application->find('tps:util:generate-service-test');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName(),
            'class' => 'tps\UtilBundle\Tests\Fixtures\ExampleClass'
        ));
        $this->assertEquals($this->expected, $commandTester->getDisplay());

    }

    protected static function getKernelClass()
    {
        require_once __DIR__.'/../Fixtures/app/AppKernel.php';

        return 'tps\UtilBundle\Tests\Command\AppKernel';
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