<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\Kernel;
use Tps\UtilBundle\Command\GenerateServiceTestCommand;

class GenerateServiceTestCommandTest extends Symfony\Bundle\FrameworkBundle\Test\WebTestCase
{
    const EXPECTED_DIR = 'src/Tps/UtilBundle/Tests/Tests/Fixtures';
    const EXPECTED_FILE = 'src/Tps/UtilBundle/Tests/Tests/Fixtures/ExampleClassTest.php';
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
    /**
     * @var \Symfony\Component\Console\Helper\QuestionHelper | \PHPUnit_Framework_MockObject_MockObject
     */
    private $questionHelperMock;
    /**
     * @var \Symfony\Component\Console\Helper\DialogHelper | \PHPUnit_Framework_MockObject_MockObject
     */
    private $dialogHelperMock;

    private $expected ='checking parameter testForm
checking parameter templating
checking parameter primitiveParameter
<?php
namespace Tps\UtilBundle\Tests\Tests\Fixtures;

use PHPUnit_Framework_MockObject_MockObject;
use Tps\UtilBundle\Tests\Fixtures\ExampleClass;

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
    * @var int|string|boolean|array
    */
    private $primitiveParameter = null;

   /**
    * @var PHPUnit_Framework_MockObject_MockObject|\Tps\UtilBundle\Tests\Fixtures\ExampleClass
    */
    private $exampleClass;

    public function setUp()
    {
        $this->formMock = $this->getMockBuilder(\'Symfony\Component\Form\Form\')
            ->disableOriginalConstructor()->getMock();
        $this->twigEngineMock = $this->getMockBuilder(\'Symfony\Bundle\TwigBundle\TwigEngine\')
            ->disableOriginalConstructor()->getMock();

        $this->exampleClass = new ExampleClass(
            $this->formMock,
            $this->twigEngineMock,
            $this->primitiveParameter
        );
    }
}
';


    public function setUp()
    {
        $this->clearTempFiles();
        $this->containerMock = $this->shortGetMock('Symfony\Component\DependencyInjection\Container');
        $this->twigMock = $this->shortGetMock('Symfony\Bundle\TwigBundle\TwigEngine');
        $this->kernelMock = $this->shortGetMock('Symfony\Component\HttpKernel\Kernel');
        $this->questionHelperMock = $this->shortGetMock('Symfony\Component\Console\Helper\QuestionHelper');
        $this->dialogHelperMock = $this->shortGetMock('Symfony\Component\Console\Helper\DialogHelper');
        $this->kernelMock->expects($this->any())
            ->method('getContainer')->willReturn($this->containerMock);
    }

    public function testExecuteMissingClass()
    {
        $command = $this->getCommandWithMocks();
        $commandTester = new CommandTester($command);
        $this->setExpectedException('RuntimeException');
        $commandTester->execute(['command' => $command->getName()]);
    }

    public function testExecuteInvalidClass()
    {
        $command = $this->getCommandWithMocks();
        $commandTester = new CommandTester($command);

        $this->containerMock->expects($this->once())
            ->method('get')
            ->with('templating')
            ->willReturn($this->twigMock);

        $this->setExpectedException('Exception', 'class not found');
        $commandTester->execute([
            'command' => $command->getName(),
            'class' => 'ThisClassDoesntExistsHopefully'
        ]);
    }

    public function testExecuteValidFileWrite()
    {
        $command = $this->getCommandWithMocks();
        $commandTester = new CommandTester($command);

        $this->containerMock->expects($this->once())
            ->method('get')
            ->with('templating')
            ->willReturn($this->twigMock);
        $this->twigMock->expects($this->once())
            ->method('render')
            ->willReturn('test successfull');

        mkdir(self::EXPECTED_DIR, 0777, true);
        $this->questionHelperMock->expects($this->any())
            ->method('ask')
            ->willReturn(true);
        $this->dialogHelperMock->expects($this->any())
            ->method('askConfirmation')
            ->willReturn(true);

        $commandTester->execute([
            'command' => $command->getName(),
            'class' => 'Tps\UtilBundle\Tests\Fixtures\ExampleClass'
        ]);
        $fileContents = file_get_contents(self::EXPECTED_FILE);
        $this->clearTempFiles();
        $this->assertEquals('test successfull', $fileContents);
    }

    public function testExecuteValidDontOverwrite()
    {
        $this->containerMock->expects($this->once())
            ->method('get')
            ->with('templating')
            ->willReturn($this->twigMock);
        $this->twigMock->expects($this->once())
            ->method('render')
            ->willReturn('test successfull');

        $command = $this->getCommandWithMocks();
        $commandTester = new CommandTester($command);
        mkdir(self::EXPECTED_DIR, 0777, true);
        file_put_contents(self::EXPECTED_FILE, 'dont kill me');
        $this->questionHelperMock->expects($this->any())
            ->method('ask')
            ->willReturn(false);
        $this->dialogHelperMock->expects($this->any())
            ->method('askConfirmation')
            ->willReturn(false);

        $commandTester->execute([
            'command' => $command->getName(),
            'class' => 'Tps\UtilBundle\Tests\Fixtures\ExampleClass'
        ]);
        $fileContents = file_get_contents(self::EXPECTED_FILE);
        $this->clearTempFiles();
        $this->assertEquals('dont kill me', $fileContents);
    }

    public function testExecuteValidClassWithRendering()
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
            'class' => 'Tps\UtilBundle\Tests\Fixtures\ExampleClass'
        ));
        $this->assertEquals($this->expected, $commandTester->getDisplay());
    }

    public function testExecuteValidClassFilewrite()
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
            'class' => 'Tps\UtilBundle\Tests\Fixtures\ExampleClass'
        ));
        $this->assertEquals($this->expected, $commandTester->getDisplay());
    }

    public function testNoBundleInClassName()
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
            'class' => 'Tps\Util\Tests\Fixtures\WeirdClass'
        ));
        $this->assertContains('namespace Tps\Util\Tests\Fixtures\Tests;', $commandTester->getDisplay());
    }

    protected static function getKernelClass()
    {
        require_once __DIR__.'/../Fixtures/app/AppKernel.php';

        return 'Tps\UtilBundle\Tests\Command\AppKernel';
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

    private function clearTempFiles()
    {
        @unlink(self::EXPECTED_FILE);
        @rmdir(self::EXPECTED_DIR);
    }

    /**
     * @return \Symfony\Component\Console\Command\Command
     */
    private function getCommandWithMocks()
    {
        $application = new Application($this->kernelMock);
        $generateServiceTestCommand = new GenerateServiceTestCommand();
        $generateServiceTestCommand->setContainer($this->kernelMock->getContainer());
        $application->add($generateServiceTestCommand);
        $command = $application->find('tps:util:generate-service-test');
        if (version_compare(Kernel::VERSION, '2.5.0', '>=')) {
            $command->getHelperSet()->set($this->questionHelperMock, 'question');
        } else {
            $command->getHelperSet()->set($this->dialogHelperMock, 'dialog');
        }

        return $command;
    }

}