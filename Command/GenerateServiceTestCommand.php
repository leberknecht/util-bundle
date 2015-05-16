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
        $testNamespace = $this->getTestNamespace($output, $reflectionClass);


        $parameters = $reflectionClass->getConstructor()->getParameters();
        $useStatments = [];
        $mocks = [];
        foreach($parameters as $parameter) {
            $parameterClass = $parameter->getClass();
            $output->writeln('checking parameter ' . $parameterClass->getName());
            $memberName = lcfirst($parameterClass->getShortName());
            $mocks[] = [
                'mocked_class_name' => $parameterClass->getName(),
                'member_name' => $memberName,
            ];
        }
        $serviceMemberName = lcfirst($reflectionClass->getShortName());
        $generatedCode = $this->templating->render(
            'UtilBundle::phpunit.template.php.twig',
            [
                'test_namespace' => $testNamespace,
                'use_statements' => $useStatments,
                'original_short_name' => $reflectionClass->getShortName(),
                'original_full_name' => $reflectionClass->getName(),
                'service_member_name' => $serviceMemberName,
                'mocks' => $mocks
            ]
        );
        $output->writeln($generatedCode);

        throw new \LogicException('You must override the execute() method in the concrete command class.');
    }

    /**
     * @param OutputInterface $output
     * @param \ReflectionClass $reflectionClass
     * @return mixed|string
     */
    protected function getTestNamespace(OutputInterface $output, \ReflectionClass $reflectionClass)
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
}