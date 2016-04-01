<?php


namespace Tps\UtilBundle\Service;


use Symfony\Bridge\Twig\TwigEngine;

class TestGeneratorService
{
    /**
     * @var TwigEngine
     */
    private $twig;

    public function __construct(TwigEngine $twig)
    {
        $this->twig = $twig;
    }

    public function generateTemplate($className)
    {
        $reflectionClass = new \ReflectionClass($className);
        $mocks = $this->assembleMockInfo($reflectionClass);
        $testNamespace = $this->getTestNamespace($className);
        $serviceMemberName = lcfirst($reflectionClass->getShortName());
        return $this->twig->render(
            'TpsUtilBundle::phpunit.template.php.twig',
            [
                'test_namespace' => $testNamespace,
                'original_short_name' => $reflectionClass->getShortName(),
                'original_full_name' => $reflectionClass->getName(),
                'service_member_name' => $serviceMemberName,
                'mocks' => $mocks
            ]
        );
    }

    /**
     * @param $className
     * @return mixed|string
     */
    public function getTestNamespace($className)
    {
        $reflectionClass = new \ReflectionClass($className);
        $namespaceName = $reflectionClass->getNamespaceName();
        if (strpos($namespaceName, 'Bundle')) {
            $testNamespace = str_replace('Bundle', 'Bundle\Tests', $namespaceName);
        } else {
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
        if ($class->getConstructor()) {
            $parameters = $class->getConstructor()->getParameters();
        } else {
            $parameters = [];
        }
        
        foreach ($parameters as $parameter) {
            $parameterClass = $parameter->getClass();

            if (!empty($parameterClass)) {
                $memberName = lcfirst($parameterClass->getShortName()) . 'Mock';
                $mocksInfo[] = [
                    'mocked_class_name' => $parameterClass->getName(),
                    'member_name' => $memberName,
                    'primitive' => false
                ];
            } else {
                $mocksInfo[] = [
                    'mocked_class_name' => null,
                    'member_name' => $parameter->name,
                    'primitive' => true
                ];
            }
        }
        
        return $mocksInfo;
    }
}