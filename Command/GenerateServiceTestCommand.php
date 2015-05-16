<?php
namespace tps\UtilBundle\Command;

use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateServiceTestCommand extends ContainerAwareCommand
{
    /**
     * @var TwigEngine
     */
    private $templating;

    public function configure()
    {
        $this
            ->setName('tps:util:generate-service-test')
            ->addArgument('class', InputArgument::REQUIRED, 'fq class name of service')
            ->setDescription('Generates a basic php-unit file with mocked services');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->templating = $this->getContainer()->get('templating');

        $className = $input->getArgument('class');
        if (!class_exists($className)) {
            throw new \Exception('class not found');
        }

        $reflectionClass = new \ReflectionClass($className);
        $mocks = $this->assembleMockInfo($reflectionClass, $output);
        $testNamespace = $this->getTestNamespace($reflectionClass, $output);
        $serviceMemberName = lcfirst($reflectionClass->getShortName());
        $generatedCode = $this->templating->render(
            'UtilBundle::phpunit.template.php.twig',
            [
                'test_namespace' => $testNamespace,
                'original_short_name' => $reflectionClass->getShortName(),
                'original_full_name' => $reflectionClass->getName(),
                'service_member_name' => $serviceMemberName,
                'mocks' => $mocks
            ]
        );

        $dirGuess = 'src/' . str_replace('\\', '/', $testNamespace);
        $fullName = $dirGuess . '/' . $reflectionClass->getShortName() . 'Test.php';
        $dialog = $this->getHelper('dialog');
        if (is_dir($dirGuess) && $dialog->askConfirmation(
                $output,
                '<question>Create "'.$fullName.'"?</question>',
                false
            )) {
                file_put_contents($fullName, $generatedCode);
                return;
        } else {
            $output->writeln($generatedCode);
        }
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param OutputInterface $output
     * @return mixed|string
     */
    protected function getTestNamespace(\ReflectionClass $reflectionClass, OutputInterface $output )
    {
        $namespaceName = $reflectionClass->getNamespaceName();
        if (strpos($namespaceName, 'Bundle')) {
            $testNamespace = str_replace('Bundle', 'Bundle\Tests', $namespaceName);
        } else {
            $output->writeln('couldnt find "Bundle" in original class namespace');
            $testNamespace = $namespaceName . '\Tests';
        }
        $output->writeln('namespace for generated Test: ' . $testNamespace);
        return $testNamespace;
    }

    /**
     * @param \ReflectionClass $class
     * @param OutputInterface $output
     * @return array
     */
    protected function assembleMockInfo(\ReflectionClass $class, OutputInterface $output)
    {
        $mocksInfo = [];
        $parameters = $class->getConstructor()->getParameters();
        foreach($parameters as $parameter) {
            $output->writeln('checking parameter ' . $parameter->getClass()->getName());
            $parameterClass = $parameter->getClass();

            $memberName = lcfirst($parameterClass->getShortName());
            $mocksInfo[] = [
                'mocked_class_name' => $parameterClass->getName(),
                'member_name' => $memberName,
            ];
        }
        return $mocksInfo;
    }
}