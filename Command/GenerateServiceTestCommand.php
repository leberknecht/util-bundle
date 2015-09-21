<?php
namespace Tps\UtilBundle\Command;

use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpKernel\Kernel;

class GenerateServiceTestCommand extends ContainerAwareCommand
{
    /**
     * @var TwigEngine
     */
    private $templating;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var InputInterface
     */
    private $input;

    public function configure()
    {
        $this
            ->setName('tps:util:generate-service-test')
            ->addArgument('class', InputArgument::REQUIRED, 'full-qualified class name of service')
            ->setDescription('Generates a base php-unit file with mocked services');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->templating = $this->getContainer()->get('templating');
        $this->output = $output;
        $this->input = $input;

        $className = $input->getArgument('class');
        if (!class_exists($className)) {
            throw new \Exception('class not found: ' . $className);
        }

        $reflectionClass = new \ReflectionClass($className);
        $mocks = $this->assembleMockInfo($reflectionClass);
        $testNamespace = $this->getTestNamespace($reflectionClass);
        $serviceMemberName = lcfirst($reflectionClass->getShortName());
        $generatedCode = $this->templating->render(
            'TpsUtilBundle::phpunit.template.php.twig',
            [
                'test_namespace' => $testNamespace,
                'original_short_name' => $reflectionClass->getShortName(),
                'original_full_name' => $reflectionClass->getName(),
                'service_member_name' => $serviceMemberName,
                'mocks' => $mocks
            ]
        );
        $this->output->writeln($generatedCode);
        $this->writeTestFile($reflectionClass, $generatedCode);
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @return mixed|string
     */
    protected function getTestNamespace(\ReflectionClass $reflectionClass )
    {
        $namespaceName = $reflectionClass->getNamespaceName();
        if (strpos($namespaceName, 'Bundle')) {
            $testNamespace = str_replace('Bundle', 'Bundle\Tests', $namespaceName);
        } else {
            $this->output->writeln('<error>couldnt find "Bundle" in original class namespace</error>');
            $testNamespace = $namespaceName . '\Tests';
        }
        return $testNamespace;
    }

    /**
     * @param \ReflectionClass $class
     * @return array
     */
    protected function assembleMockInfo(\ReflectionClass $class)
    {
        $mocksInfo = [];
        $parameters = $class->getConstructor()->getParameters();
        foreach($parameters as $parameter) {
            $this->output->writeln('checking parameter ' . $parameter->getClass()->getName());
            $parameterClass = $parameter->getClass();

            $memberName = lcfirst($parameterClass->getShortName()) . 'Mock';
            $mocksInfo[] = [
                'mocked_class_name' => $parameterClass->getName(),
                'member_name' => $memberName,
            ];
        }
        return $mocksInfo;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param $generatedCode
     */
    protected function writeTestFile(\ReflectionClass $reflectionClass, $generatedCode)
    {
        $dirGuess = 'src/' . str_replace('\\', '/', $this->getTestNamespace($reflectionClass));
        $fullName = $dirGuess . '/' . $reflectionClass->getShortName() . 'Test.php';

        if (is_dir($dirGuess)) {
            $question = '<question>Create "' . $fullName . '"?</question>';
            if (is_file($fullName)) {
                $this->output->writeln('<error>File "' . $fullName . '" exists!<error>');
                $question = '<question>Overwrite file "' . $fullName . '"?</question>';
            }
            if ($this->askQuestion($question)){
                file_put_contents($fullName, $generatedCode);
            }
        }
    }

    /**
     * @param $question
     * @return string
     * @@codeCoverageIgnore
     */
    protected function askQuestion($question)
    {
        if (version_compare(Kernel::VERSION, '2.5.0', '>=')) {
            /** @var QuestionHelper $questionHelper */
            $questionHelper = $this->getHelper('question');
            return $questionHelper->ask($this->input, $this->output, new Question($question, false));
        } else {
            /** @var DialogHelper $dialogHelper */
            $dialogHelper = $this->getHelper('dialog');
            return $dialogHelper->askConfirmation($this->output, $question, false);
        }
    }
}
