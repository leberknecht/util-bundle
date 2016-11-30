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

    /**
     * @var | \PHPUnit_Framework_MockObject_MockObject
     */
    private $testGenerator;

    private $expected = null;

    public function setUp()
    {
        $this->clearTempFiles();
        $this->expected = file_get_contents(__DIR__ . '/../Fixtures/expected.template');
        $this->containerMock = $this->shortGetMock('Symfony\Component\DependencyInjection\Container');
        $this->twigMock = $this->shortGetMock('Symfony\Bundle\TwigBundle\TwigEngine');
        $this->kernelMock = $this->shortGetMock('Symfony\Component\HttpKernel\Kernel');
        $this->questionHelperMock = $this->shortGetMock('Symfony\Component\Console\Helper\QuestionHelper');
        $this->dialogHelperMock = $this->shortGetMock('Symfony\Component\Console\Helper\DialogHelper');
        $this->testGenerator = new \Tps\UtilBundle\Service\TestGeneratorService($this->twigMock);

        $this->containerMock->expects($this->any())
            ->method('get')
            ->with('tps.test_generator')
            ->willReturn($this->testGenerator);

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

        $this->twigMock->expects($this->once())
            ->method('render')
            ->willReturn('test successfull');

        mkdir(self::EXPECTED_DIR, 0777, true);
        $this->questionHelperMock->expects($this->any())
            ->method('ask')
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
            ->with('tps.test_generator')
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
        file_put_contents('t1.log', $this->expected);
        file_put_contents('t2.log', $commandTester->getDisplay());
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

    /**
     * @param string $name
     * @param null|string $message
     */
    private function setExpectedException($name, $message = null)
    {
        if (method_exists($this, 'expectException')) {
            $this->expectException($name);
            if (!empty($message)) {
                $this->expectExceptionMessage($message);
            }
        } else if (method_exists($this, 'setExpectedException')) {
            $this->setExpectedException($name, $message);
        }
    }
}
