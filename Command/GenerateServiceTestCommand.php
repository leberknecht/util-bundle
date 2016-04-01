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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use Tps\UtilBundle\Service\TestGeneratorService;

class GenerateServiceTestCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var TestGeneratorService
     */
    private $testGeneratorService;

    public function configure()
    {
        $this
            ->setName('tps:util:generate-service-test')
            ->addArgument('class', InputArgument::REQUIRED, 'full-qualified class name of service')
            ->setDescription('Generates a base php-unit file with mocked services');
    }

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->testGeneratorService = $container->get('tps.test_generator');
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;

        $className = $input->getArgument('class');
        if (!class_exists($className)) {
            throw new \Exception('class not found: ' . $className);
        }

        $generatedCode = $this->testGeneratorService->generateTemplate($className);
        $this->output->writeln($generatedCode);
        $this->writeTestFile(
            $this->testGeneratorService->getTestNamespace($className),
            (new \ReflectionClass($className))->getShortName(),
            $generatedCode
        );
    }

    /**
     * @param $pathGuess
     * @param $shortname
     * @param $generatedCode
     */
    protected function writeTestFile($pathGuess, $shortname, $generatedCode)
    {
        $dirGuess = 'src/' . str_replace('\\', '/', $pathGuess);
        $fullName = $dirGuess . '/' . $shortname . 'Test.php';

        if (is_dir($dirGuess)) {
            $question = '<question>Create "' . $fullName . '"?</question>';
            if (is_file($fullName)) {
                $this->output->writeln('<error>File "' . $fullName . '" exists!<error>');
                $question = '<question>Overwrite file "' . $fullName . '"?</question>';
            }
            if ($this->askQuestion($question)) {
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
